<?php

namespace App\Http\Controllers;

use App\Services\StudioJobCleanupService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
// use App\Models\StudioJob; // Removed to use file-based storage
use Symfony\Component\Process\Process;
use ZipArchive;

class StudioController extends Controller
{
    private $storagePath;
    private $limitMB = 2097152; // 2TB Working Limit
    private $ownerDriveAccessToken = null;
    private $ownerDriveAccessTokenExpiresAt = null;
    private StudioJobCleanupService $jobCleanup;

    public function __construct(StudioJobCleanupService $jobCleanup)
    {
        $this->jobCleanup = $jobCleanup;
        $this->storagePath = storage_path('app/tasks');
        if (!file_exists($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    private function getUsedStorageBytes()
    {
        $totalSize = 0;
        if (!file_exists($this->storagePath)) return 0;
        
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->storagePath));
        foreach ($files as $file) {
            if ($file->isFile()) {
                $totalSize += $file->getSize();
            }
        }
        return $totalSize;
    }

    private function checkStorageLimit()
    {
        Log::info("Storage Limit Check starting...");

        $this->maybePurgeExpiredJobs();

        try {
            $used = $this->getUsedStorageBytes();
            $limit = $this->limitMB * 1024 * 1024;
            Log::info("Storage Check: Used " . round($used / 1024 / 1024, 2) . "MB / Limit " . $this->limitMB . "MB");
            return $used < $limit;
        } catch (\Exception $e) {
            Log::error("Failed to calculate storage usage: " . $e->getMessage());
            return true; // Allow upload if check fails to be safe, or false to be strict
        }
    }

    public function getStorageStats()
    {
        Log::info("GetStorageStats requested");
        $canViewDriveRoot = (bool) Auth::user()?->is_admin;

        if ($this->hasOwnerManagedGoogleDriveStorage()) {
            try {
                $stats = Cache::remember('google_drive_owner_storage_stats', now()->addSeconds(30), function () {
                    $token = $this->getOwnerManagedGoogleDriveAccessToken();
                    $quota = $this->fetchGoogleDriveStorageQuota($token);
                    $rootFolder = $this->ensureGoogleDriveRootFolder($token);

                    return [
                        'provider' => 'google_drive',
                        'total_mb' => (int) round($quota['limit_bytes'] / 1024 / 1024),
                        'used_bytes' => $quota['used_bytes'],
                        'remaining_bytes' => $quota['remaining_bytes'],
                        'percent' => round($quota['percent'], 2),
                        'status' => $quota['status'],
                        'root_folder_id' => $rootFolder['root_folder_id'],
                        'root_folder_name' => $rootFolder['root_folder_name'],
                        'root_folder_url' => $rootFolder['root_folder_url'],
                    ];
                });

                if (!$canViewDriveRoot) {
                    $stats['root_folder_id'] = null;
                    $stats['root_folder_name'] = null;
                    $stats['root_folder_url'] = null;
                }

                return response()->json($stats);
            } catch (\Throwable $e) {
                Log::warning('Owner Google Drive quota lookup failed: ' . $e->getMessage());

                return response()->json([
                    'provider' => 'google_drive',
                    'total_mb' => 0,
                    'used_bytes' => 0,
                    'remaining_bytes' => 0,
                    'percent' => 0,
                    'status' => 'error',
                    'error' => $this->normalizeGoogleDriveQuotaError($e->getMessage()),
                ]);
            }
        }

        $usedBytes = $this->getUsedStorageBytes();
        $limitBytes = $this->limitMB * 1024 * 1024;
        $remainingBytes = max(0, $limitBytes - $usedBytes);
        $percent = ($usedBytes / $limitBytes) * 100;
        
        $status = 'normal';
        if ($percent >= 100) $status = 'full';
        elseif ($percent >= 80) $status = 'warning';

        return response()->json([
            'provider' => 'local',
            'total_mb' => $this->limitMB,
            'used_bytes' => $usedBytes,
            'remaining_bytes' => $remainingBytes,
            'percent' => round($percent, 2),
            'status' => $status
        ]);
    }

    public function initDriveUpload(Request $request)
    {
        $files = $request->input('files', []);
        if (!is_array($files) || $files === []) {
            return response()->json(['error' => 'No files provided'], 400);
        }

        $normalizedFiles = [];
        $totalSize = 0;

        foreach ($files as $file) {
            if (!is_array($file)) {
                continue;
            }

            $name = trim((string) ($file['name'] ?? ''));
            $size = (int) ($file['size'] ?? 0);
            $type = trim((string) ($file['type'] ?? 'application/octet-stream')) ?: 'application/octet-stream';

            if ($name === '' || $size <= 0) {
                return response()->json(['error' => 'Each file requires a valid name and size'], 422);
            }

            $normalizedFiles[] = [
                'name' => $name,
                'size' => $size,
                'type' => $type,
            ];
            $totalSize += $size;
        }

        if ($normalizedFiles === []) {
            return response()->json(['error' => 'No valid files provided'], 422);
        }

        if ($totalSize > 10737418240) {
            return response()->json(['error' => 'Total upload exceeds 10GB limit.'], 413);
        }

        $driveToken = $this->resolveGoogleDriveAccessToken($request->input('drive_token'));
        if ($driveToken === '') {
            return response()->json(['error' => 'Google Drive is not available for direct uploads.'], 400);
        }

        try {
            $jobId = (string) Str::uuid();
            $folder = $this->ensureGoogleDriveJobFolder($driveToken, $jobId);
            $driveStorage = $this->buildDriveStorageState([
                'enabled' => true,
                'provider' => 'google_drive',
                'status' => 'uploading',
                'error' => null,
                'inputs_synced' => 0,
                'outputs_synced' => 0,
            ], $folder);

            $sessions = [];
            foreach ($normalizedFiles as $index => $file) {
                $upload = $this->createGoogleDriveUploadSession(
                    $driveToken,
                    $file['name'],
                    $folder['job_folder_id'],
                    $file['size'],
                    $file['type']
                );

                $sessions[] = [
                    'index' => $index,
                    'name' => $file['name'],
                    'size' => $file['size'],
                    'type' => $file['type'],
                    'upload_url' => $upload['upload_url'],
                ];
            }

            $this->updateJob($jobId, [
                'job_id' => $jobId,
                'user_id' => Auth::id(),
                'status' => 'drive_uploading',
                'files' => [],
                'progress' => 0,
                'progress_message' => 'Uploading files to managed Google Drive...',
                'uploaded_file_count' => count($normalizedFiles),
                'uploaded_file_names' => array_values(array_map(fn (array $file) => $file['name'], $normalizedFiles)),
                'drive_storage' => $driveStorage,
            ]);

            return response()->json([
                'job_id' => $jobId,
                'uploads' => $sessions,
                'drive_storage' => $driveStorage,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Drive upload initialization failed: ' . $e->getMessage());

            return response()->json([
                'error' => 'Managed Google Drive upload initialization failed.',
                'detail' => $e->getMessage(),
            ], 500);
        }
    }

    public function completeDriveUpload(Request $request)
    {
        $driveFiles = $request->input('drive_files', []);
        if (!is_array($driveFiles) || $driveFiles === []) {
            return response()->json(['error' => 'No uploaded Drive files provided'], 400);
        }

        $normalizedFiles = [];
        foreach ($driveFiles as $file) {
            if (!is_array($file)) {
                continue;
            }

            $fileId = trim((string) ($file['id'] ?? $file['file_id'] ?? ''));
            $name = trim((string) ($file['name'] ?? ''));
            if ($fileId === '') {
                return response()->json(['error' => 'Each Drive file requires an id'], 422);
            }

            $normalizedFiles[] = [
                'id' => $fileId,
                'name' => $name,
                'size' => isset($file['size']) ? (int) $file['size'] : null,
                'webViewLink' => isset($file['webViewLink']) ? (string) $file['webViewLink'] : null,
                'webContentLink' => isset($file['webContentLink']) ? (string) $file['webContentLink'] : null,
            ];
        }

        if ($normalizedFiles === []) {
            return response()->json(['error' => 'No valid Drive files provided'], 422);
        }

        $jobId = trim((string) $request->input('job_id'));
        if ($jobId === '') {
            $jobId = (string) Str::uuid();
        }

        $driveToken = $this->resolveGoogleDriveAccessToken($request->input('drive_token', $request->input('token')));
        if ($driveToken === '') {
            return response()->json(['error' => 'Google Drive access token is required.'], 400);
        }

        try {
            $job = $this->importDriveFilesToJob($jobId, $normalizedFiles, $driveToken);
            $this->purgeOldJobs();

            return response()->json([
                'job_id' => $jobId,
                'status' => 'uploaded',
                'files' => $job->files ?? [],
                'drive_storage' => $job->drive_storage ?? [],
            ]);
        } catch (\Throwable $e) {
            Log::warning("Drive upload completion failed for job {$jobId}: " . $e->getMessage());

            return response()->json([
                'error' => 'Drive import failed.',
                'detail' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle direct file uploads
     */
    public function upload(Request $request)
    {
        if (!$this->checkStorageLimit()) {
            return response()->json(['error' => 'Storage is full. Please contact support or wait for automatic cleanup.'], 403);
        }

        $jobId = (string) Str::uuid();
        $jobDir = $this->storagePath . '/' . $jobId . '/input';
        if (!file_exists($jobDir)) mkdir($jobDir, 0755, true);

        $files = $request->file('files');
        if (!$files) return response()->json(['error' => 'No files provided'], 400);

        // Limit Check
        $totalSize = 0;
        foreach($files as $f) { $totalSize += $f->getSize(); }
        if ($totalSize > 10737418240) {
            return response()->json(['error' => 'Total upload exceeds 10GB limit.'], 413);
        }

        $savedFiles = [];
        foreach ($files as $file) {
            $name = $file->getClientOriginalName();
            $file->move($jobDir, $name);
            $savedFiles[] = [
                'name' => $name,
                'path' => $jobDir . '/' . $name,
                'size' => filesize($jobDir . '/' . $name)
            ];
        }

        $driveStorage = [];
        $driveToken = $this->resolveGoogleDriveAccessToken($request->input('drive_token'));
        if ($driveToken !== '') {
            try {
                if ($this->shouldDeferInputDriveSync($savedFiles)) {
                    $driveStorage = $this->buildDeferredInputDriveStorage($jobId, $driveToken, $savedFiles);
                } else {
                    $driveSync = $this->syncManagedFilesToGoogleDrive($jobId, $savedFiles, $driveToken);
                    $savedFiles = $driveSync['files'];
                    $driveStorage = $driveSync['drive_storage'];
                    $driveStorage['inputs_synced'] = count($savedFiles);
                }
            } catch (\Throwable $e) {
                Log::warning("Initial Drive sync failed for job {$jobId}: " . $e->getMessage());
                $driveStorage = $this->buildDriveStorageErrorState([], $e->getMessage());
            }
        }

        $this->updateJob($jobId, [
            'job_id' => $jobId,
            'user_id' => Auth::id(),
            'status' => 'uploaded',
            'files' => $savedFiles,
            'progress' => 0,
            'drive_storage' => $driveStorage,
        ]);

        $this->purgeOldJobs();

        return response()->json([
            'job_id' => $jobId,
            'status' => 'uploaded',
            'drive_storage' => $driveStorage,
        ]);
    }

    public function uploadChunk(Request $request)
    {
        Log::info("UploadChunk Start: " . $request->input('file_name') . " (Chunk " . $request->input('chunk_index') . ")");
        if (!$this->checkStorageLimit()) {
            return response()->json(['error' => 'Storage is full. Please contact support or wait for automatic cleanup.'], 403);
        }

        $jobId = $request->input('job_id');
        $fileName = $request->input('file_name');
        $fileKey = trim((string) $request->input('file_key', ''));
        $chunkIndex = (int) $request->input('chunk_index');
        $totalChunks = (int) $request->input('total_chunks');
        $fileSize = max(0, (int) $request->input('file_size', 0));
        $file = $request->file('chunk');

        if (!$jobId || !$fileName || !$file) {
            return response()->json(['error' => 'Missing data'], 400);
        }

        $jobDir = $this->storagePath . '/' . $jobId . '/input';
        if (!file_exists($jobDir)) mkdir($jobDir, 0755, true);

        if ($fileKey !== '') {
            try {
                $receivedChunks = $this->storeChunkUpload($jobDir, $fileKey, $fileName, $file, $chunkIndex, $totalChunks, $fileSize);

                return response()->json([
                    'status' => 'chunk_saved',
                    'completed' => false,
                    'received_chunks' => $receivedChunks,
                ]);
            } catch (\Throwable $e) {
                Log::warning("Parallel chunk upload failed for {$fileName}: " . $e->getMessage());

                return response()->json(['error' => $e->getMessage()], 500);
            }
        }

        $tempPath = $jobDir . '/' . $fileName . '.part';
        
        // Append chunk to the file
        $sourcePath = $file->getPathname();
        if (!is_string($sourcePath) || $sourcePath === '' || !is_file($sourcePath)) {
            return response()->json(['error' => 'Uploaded chunk could not be read'], 422);
        }

        $input = fopen($sourcePath, 'rb');
        $out = fopen($tempPath, $chunkIndex === 0 ? 'wb' : 'ab');

        if ($input === false || $out === false) {
            if (is_resource($input)) {
                fclose($input);
            }
            if (is_resource($out)) {
                fclose($out);
            }

            return response()->json(['error' => 'Failed to open upload streams'], 500);
        }

        stream_copy_to_stream($input, $out);
        fclose($input);
        fclose($out);

        if ($chunkIndex === $totalChunks - 1) {
            // Last chunk, rename to original
            $finalPath = $jobDir . '/' . $fileName;
            @rename($tempPath, $finalPath);
            return response()->json(['status' => 'chunk_saved', 'completed' => true]);
        }

        return response()->json(['status' => 'chunk_saved', 'completed' => false]);
    }

    public function finalizeUpload(Request $request)
    {
        $jobId = $request->input('job_id');
        if (!$jobId) return response()->json(['error' => 'Job ID required'], 400);

        $jobDir = $this->storagePath . '/' . $jobId . '/input';
        if (!file_exists($jobDir)) return response()->json(['error' => 'Upload dir not found'], 404);

        try {
            $this->assembleChunkedUploads($jobDir);
        } catch (\Throwable $e) {
            Log::warning("Chunk assembly failed for job {$jobId}: " . $e->getMessage());
            $this->cleanupChunkUploadArtifacts($jobDir);

            return response()->json([
                'error' => 'Upload assembly failed.',
                'detail' => $e->getMessage(),
            ], 422);
        }

        $files = File::files($jobDir);
        $savedFiles = [];
        foreach ($files as $file) {
            if (str_ends_with($file->getFilename(), '.part')) continue;
            if (basename($file->getFilename()) === 'job.json') continue;
            
            $savedFiles[] = [
                'name' => $file->getFilename(),
                'path' => $file->getRealPath(),
                'size' => $file->getSize()
            ];
        }

        $driveStorage = [];
        $driveToken = $this->resolveGoogleDriveAccessToken($request->input('drive_token'));
        if ($driveToken !== '') {
            try {
                if ($this->shouldDeferInputDriveSync($savedFiles)) {
                    $driveStorage = $this->buildDeferredInputDriveStorage($jobId, $driveToken, $savedFiles);
                } else {
                    $driveSync = $this->syncManagedFilesToGoogleDrive($jobId, $savedFiles, $driveToken);
                    $savedFiles = $driveSync['files'];
                    $driveStorage = $driveSync['drive_storage'];
                    $driveStorage['inputs_synced'] = count($savedFiles);
                }
            } catch (\Throwable $e) {
                Log::warning("Finalize upload Drive sync failed for job {$jobId}: " . $e->getMessage());
                $driveStorage = $this->buildDriveStorageErrorState([], $e->getMessage());
            }
        }

        $this->updateJob($jobId, [
            'job_id' => $jobId,
            'user_id' => Auth::id(),
            'status' => 'uploaded',
            'files' => $savedFiles,
            'progress' => 0,
            'drive_storage' => $driveStorage,
        ]);

        $this->purgeOldJobs();

        // Log task in DB
        try {
            \App\Models\Task::create([
                'id' => $jobId,
                'user_id' => Auth::id(),
                'file_name' => count($savedFiles) === 1 ? $savedFiles[0]['name'] : 'Batch Upload (' . count($savedFiles) . ' files)',
                'stored_path' => $jobDir,
                'file_size' => collect($savedFiles)->sum('size'),
                'status' => 'uploaded'
            ]);
        } catch (\Exception $e) {}

        return response()->json([
            'status' => 'uploaded',
            'job_id' => $jobId,
            'drive_storage' => $driveStorage,
        ]);
    }

    private function storeChunkUpload(
        string $jobDir,
        string $fileKey,
        string $fileName,
        UploadedFile $file,
        int $chunkIndex,
        int $totalChunks,
        int $fileSize = 0
    ): int {
        if ($chunkIndex < 0 || $totalChunks < 1 || $chunkIndex >= $totalChunks) {
            throw new \InvalidArgumentException('Chunk metadata is invalid.');
        }

        $sourcePath = $file->getPathname();
        if (!is_string($sourcePath) || $sourcePath === '' || !is_file($sourcePath)) {
            throw new \RuntimeException('Uploaded chunk could not be read.');
        }

        $chunkDir = $this->chunkUploadDirectory($jobDir, $fileKey);
        if (!File::isDirectory($chunkDir)) {
            File::makeDirectory($chunkDir, 0755, true);
        }

        $manifestPath = $chunkDir . DIRECTORY_SEPARATOR . 'manifest.json';
        $existingManifest = File::exists($manifestPath)
            ? json_decode((string) File::get($manifestPath), true)
            : [];

        if (is_array($existingManifest)) {
            $existingName = trim((string) ($existingManifest['file_name'] ?? ''));
            $existingTotal = (int) ($existingManifest['total_chunks'] ?? 0);
            if (($existingName !== '' && $existingName !== $fileName) || ($existingTotal > 0 && $existingTotal !== $totalChunks)) {
                throw new \RuntimeException('Chunk upload metadata changed mid-stream.');
            }
        }

        $manifest = [
            'file_name' => $fileName,
            'total_chunks' => $totalChunks,
            'file_size' => $fileSize,
            'updated_at' => now()->toIso8601String(),
        ];

        $manifestJson = json_encode($this->sanitizeForJson($manifest), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($manifestJson === false || file_put_contents($manifestPath, $manifestJson, LOCK_EX) === false) {
            throw new \RuntimeException('Failed to write chunk manifest.');
        }

        $chunkPath = $chunkDir . DIRECTORY_SEPARATOR . sprintf('%08d.part', $chunkIndex);
        $input = fopen($sourcePath, 'rb');
        $out = fopen($chunkPath, 'wb');

        if ($input === false || $out === false) {
            if (is_resource($input)) {
                fclose($input);
            }
            if (is_resource($out)) {
                fclose($out);
            }

            throw new \RuntimeException('Failed to open upload streams.');
        }

        try {
            stream_copy_to_stream($input, $out);
        } finally {
            fclose($input);
            fclose($out);
        }

        return count(glob($chunkDir . DIRECTORY_SEPARATOR . '*.part') ?: []);
    }

    private function assembleChunkedUploads(string $jobDir): void
    {
        $chunksRoot = $jobDir . DIRECTORY_SEPARATOR . '.chunks';
        if (!File::isDirectory($chunksRoot)) {
            return;
        }

        foreach (File::directories($chunksRoot) as $chunkDir) {
            $manifestPath = $chunkDir . DIRECTORY_SEPARATOR . 'manifest.json';
            if (!File::exists($manifestPath)) {
                throw new \RuntimeException('Upload manifest is missing for a chunked file.');
            }

            $manifest = json_decode((string) File::get($manifestPath), true);
            if (!is_array($manifest)) {
                throw new \RuntimeException('Upload manifest is invalid.');
            }

            $fileName = trim((string) ($manifest['file_name'] ?? ''));
            $totalChunks = (int) ($manifest['total_chunks'] ?? 0);
            if ($fileName === '' || $totalChunks < 1) {
                throw new \RuntimeException('Upload manifest is incomplete.');
            }

            $finalPath = $this->makeUniqueLocalPath($jobDir, $fileName);
            $out = fopen($finalPath, 'wb');
            if ($out === false) {
                throw new \RuntimeException("Failed to create assembled file for {$fileName}.");
            }

            try {
                for ($chunkIndex = 0; $chunkIndex < $totalChunks; $chunkIndex++) {
                    $chunkPath = $chunkDir . DIRECTORY_SEPARATOR . sprintf('%08d.part', $chunkIndex);
                    if (!is_file($chunkPath)) {
                        throw new \RuntimeException("Chunk " . ($chunkIndex + 1) . " of {$totalChunks} is missing for {$fileName}.");
                    }

                    $input = fopen($chunkPath, 'rb');
                    if ($input === false) {
                        throw new \RuntimeException("Failed to open chunk " . ($chunkIndex + 1) . " for {$fileName}.");
                    }

                    try {
                        stream_copy_to_stream($input, $out);
                    } finally {
                        fclose($input);
                    }
                }
            } catch (\Throwable $e) {
                if (is_file($finalPath)) {
                    @unlink($finalPath);
                }

                throw $e;
            } finally {
                fclose($out);
            }

            File::deleteDirectory($chunkDir);
        }

        $this->cleanupChunkUploadArtifacts($jobDir);
    }

    private function chunkUploadDirectory(string $jobDir, string $fileKey): string
    {
        return $jobDir
            . DIRECTORY_SEPARATOR
            . '.chunks'
            . DIRECTORY_SEPARATOR
            . preg_replace('/[^A-Za-z0-9._-]/', '_', $fileKey);
    }

    private function cleanupChunkUploadArtifacts(string $jobDir): void
    {
        $chunksRoot = $jobDir . DIRECTORY_SEPARATOR . '.chunks';
        if (File::isDirectory($chunksRoot)) {
            File::deleteDirectory($chunksRoot);
        }
    }

    /**
     * Purge expired jobs based on the configured retention window.
     */
    private function purgeOldJobs(): void
    {
        try {
            $this->jobCleanup->purgeExpiredJobs();
        } catch (\Throwable $e) {
            Log::warning('Expired studio job purge failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle cloud URL import
     */
    /**
     * Handle cloud URL import
     */
    public function uploadUrl(Request $request)
    {
        if (!$this->checkStorageLimit()) {
            return response()->json(['error' => 'Storage is full. Please click Clear Memory before starting a new task.'], 403);
        }

        $url = $request->input('url');
        $fileId = $request->input('file_id'); // Support direct File ID from Picker
        $token = $request->input('token'); // Support OAuth token for private files

        if (!$url && !$fileId) {
            return response()->json(['error' => 'URL or File ID is required'], 400);
        }

        $jobId = (string) Str::uuid();
        $jobDir = $this->storagePath . '/' . $jobId . '/input';
        if (!file_exists($jobDir)) mkdir($jobDir, 0755, true);

        try {
            $filename = "cloud_import.zip";
            
            if ($fileId) {
                return $this->downloadFromGoogleDriveApi($fileId, $jobId, $jobDir, $token);
            }

            // Fallback to legacy URL parsing if no API fileId provided
            // 1. HEAD request to check size before downloading
            $headResponse = Http::timeout(10)->head($url);
            $contentLength = $headResponse->header('Content-Length');
            if ($contentLength && $contentLength > 10737418240) {
                return response()->json(['error' => 'The cloud file is too large (>10GB).'], 413);
            }

            if (preg_match('/(?:drive\.google\.com\/(?:file\/d\/|open\?id=)|docs\.google\.com\/uc\?id=)([\w-]+)/', $url, $matches)) {
                $fileId = $matches[1];
                
                // If we have API config, use it even for pasted links for better reliability
                if (env('GOOGLE_DRIVE_API_KEY')) {
                    return $this->downloadFromGoogleDriveApi($fileId, $jobId, $jobDir, $token);
                }

                $downloadUrl = "https://docs.google.com/uc?export=download&id=" . $fileId;
                $response = Http::timeout(180)->get($downloadUrl);
                
                if (str_contains($response->body(), 'confirm=')) {
                    preg_match('/confirm=([\w-]+)/', $response->body(), $cMatches);
                    if (isset($cMatches[1])) {
                        $response = Http::timeout(180)->get($downloadUrl . "&confirm=" . $cMatches[1]);
                    }
                }
                
                if ($response->header('Content-Disposition')) {
                    if (preg_match('/filename="?([^";\n]+)"?/', $response->header('Content-Disposition'), $fMatches)) {
                        $filename = $fMatches[1];
                    }
                }
            } else {
                $response = Http::timeout(180)->get($url);
                $filename = basename(parse_url($url, PHP_URL_PATH)) ?: "imported_asset.zip";
            }

            if (!$response->successful()) throw new \Exception("Cloud download failed.");

            $filePath = $jobDir . '/' . $filename;
            file_put_contents($filePath, $response->body());

            $this->updateJob($jobId, [
                'job_id' => $jobId,
                'user_id' => Auth::id(),
                'status' => 'uploaded',
                'files' => [[
                    'name' => $filename,
                    'path' => $filePath,
                    'size' => filesize($filePath)
                ]],
                'progress' => 0
            ]);

            return response()->json(['job_id' => $jobId, 'status' => 'uploaded']);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    private function downloadFromGoogleDriveApi($fileId, $jobId, $jobDir, $token = null)
    {
        $downloaded = $this->downloadGoogleDriveFileToLocal((string) $fileId, $jobDir, $token);

        $job = $this->updateJob($jobId, [
            'job_id' => $jobId,
            'user_id' => Auth::id(),
            'status' => 'uploaded',
            'files' => [[
                'name' => $downloaded['name'],
                'path' => $downloaded['path'],
                'size' => $downloaded['size'],
                'drive_file_id' => $downloaded['id'],
                'drive_url' => $downloaded['webViewLink'] ?? $this->buildGoogleDriveFileUrl($downloaded['id']),
                'drive_download_url' => $downloaded['webContentLink'] ?? null,
                'storage_provider' => 'google_drive',
            ]],
            'progress' => 0,
            'progress_message' => 'Cloud import complete.',
        ]);

        return response()->json([
            'job_id' => $jobId,
            'status' => 'uploaded',
            'drive_storage' => $job->drive_storage ?? [],
        ]);
    }

    private function importDriveFilesToJob(string $jobId, array $driveFiles, string $token): object
    {
        $jobDir = $this->storagePath . '/' . $jobId . '/input';
        if (!file_exists($jobDir)) {
            mkdir($jobDir, 0755, true);
        }

        $existingJob = $this->getJob($jobId);
        $driveStorage = is_array($existingJob?->drive_storage ?? null) ? $existingJob->drive_storage : [];

        $this->updateJob($jobId, [
            'job_id' => $jobId,
            'user_id' => Auth::id(),
            'status' => 'importing',
            'progress' => 1,
            'progress_message' => 'Importing files from Google Drive...',
            'uploaded_file_count' => count($driveFiles),
            'uploaded_file_names' => array_values(array_map(
                fn (array $file) => trim((string) ($file['name'] ?? 'Google Drive file')) ?: 'Google Drive file',
                $driveFiles
            )),
            'drive_storage' => $driveStorage,
        ]);

        $savedFiles = [];
        $totalFiles = count($driveFiles);
        $safeTotalFiles = max($totalFiles, 1);

        foreach ($driveFiles as $index => $driveFile) {
            $downloaded = $this->downloadGoogleDriveFileToLocal((string) $driveFile['id'], $jobDir, $token);

            $savedFiles[] = [
                'name' => $downloaded['name'],
                'path' => $downloaded['path'],
                'size' => $downloaded['size'],
                'drive_file_id' => $downloaded['id'],
                'drive_url' => $driveFile['webViewLink'] ?? $downloaded['webViewLink'] ?? $this->buildGoogleDriveFileUrl($downloaded['id']),
                'drive_download_url' => $driveFile['webContentLink'] ?? $downloaded['webContentLink'] ?? null,
                'storage_provider' => 'google_drive',
            ];

            $this->updateJob($jobId, [
                'status' => 'importing',
                'progress' => min(99, 5 + (int) ((($index + 1) / $safeTotalFiles) * 90)),
                'progress_message' => "Importing " . ($index + 1) . "/{$totalFiles}: " . $downloaded['name'],
                'progress_meta' => [
                    'phase' => 'upload',
                    'total_files' => $safeTotalFiles,
                    'completed_files' => $index + 1,
                    'current_file_index' => $index + 1,
                    'current_file_name' => $downloaded['name'],
                    'action' => 'Importing from Google Drive',
                ],
            ]);
        }

        if ($driveStorage !== []) {
            $driveStorage['enabled'] = true;
            $driveStorage['provider'] = 'google_drive';
            $driveStorage['status'] = 'synced';
            $driveStorage['error'] = null;
            $driveStorage['inputs_synced'] = count($savedFiles);
            $driveStorage['last_synced_at'] = now()->toIso8601String();
        }

        return $this->updateJob($jobId, [
            'job_id' => $jobId,
            'user_id' => Auth::id(),
            'status' => 'uploaded',
            'files' => $savedFiles,
            'progress' => 0,
            'progress_message' => 'Cloud import complete.',
            'progress_meta' => [
                'phase' => 'upload',
                'total_files' => $safeTotalFiles,
                'completed_files' => $safeTotalFiles,
                'current_file_index' => $safeTotalFiles,
                'current_file_name' => $savedFiles[count($savedFiles) - 1]['name'] ?? 'Google Drive file',
                'action' => 'Upload complete',
            ],
            'drive_storage' => $driveStorage,
        ]);
    }

    private function downloadGoogleDriveFileToLocal(string $fileId, string $jobDir, ?string $token = null): array
    {
        $apiKey = env('GOOGLE_DRIVE_API_KEY');
        if (!$apiKey && !$token) {
            throw new \RuntimeException("Google Drive API is not configured (Missing API Key or Token).");
        }

        $client = Http::timeout(300);
        if ($token) {
            $client = $client->withToken($token);
            $metaUrl = "https://www.googleapis.com/drive/v3/files/{$fileId}";
            $contentUrl = "https://www.googleapis.com/drive/v3/files/{$fileId}?alt=media";
        } else {
            $metaUrl = "https://www.googleapis.com/drive/v3/files/{$fileId}?key={$apiKey}";
            $contentUrl = "https://www.googleapis.com/drive/v3/files/{$fileId}?alt=media&key={$apiKey}";
        }

        $metaResponse = $client->get($metaUrl);
        if (!$metaResponse->successful()) {
            throw new \RuntimeException("Failed to fetch file metadata from Google API: " . $metaResponse->body());
        }

        $meta = $metaResponse->json();
        $filename = trim((string) ($meta['name'] ?? 'google_drive_file.zip')) ?: 'google_drive_file.zip';
        $filePath = $this->makeUniqueLocalPath($jobDir, $filename);

        $response = $client->withOptions([
            'sink' => $filePath,
        ])->get($contentUrl);

        if (!$response->successful()) {
            throw new \RuntimeException("Google Drive API download failed. Status: " . $response->status());
        }

        return [
            'id' => $fileId,
            'name' => basename($filePath),
            'path' => $filePath,
            'size' => filesize($filePath) ?: 0,
            'webViewLink' => $meta['webViewLink'] ?? $this->buildGoogleDriveFileUrl($fileId),
            'webContentLink' => $meta['webContentLink'] ?? null,
        ];
    }

    private function createGoogleDriveUploadSession(
        string $token,
        string $name,
        string $parentId,
        int $size,
        string $mimeType = 'application/octet-stream'
    ): array {
        $metadata = [
            'name' => $name,
            'parents' => [$parentId],
        ];

        $initResponse = Http::timeout(120)
            ->withToken($token)
            ->withHeaders([
                'Content-Type' => 'application/json; charset=UTF-8',
                'X-Upload-Content-Type' => $mimeType,
                'X-Upload-Content-Length' => (string) $size,
            ])
            ->withBody(json_encode($metadata, JSON_THROW_ON_ERROR), 'application/json; charset=UTF-8')
            ->send('POST', 'https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable&fields=id,name,size,webViewLink,webContentLink');

        if (!$initResponse->successful()) {
            throw new \RuntimeException('Google Drive resumable upload start failed: ' . $initResponse->body());
        }

        $uploadUrl = $initResponse->header('Location');
        if (!is_string($uploadUrl) || trim($uploadUrl) === '') {
            throw new \RuntimeException('Google Drive did not return a resumable upload URL.');
        }

        return [
            'upload_url' => $uploadUrl,
        ];
    }

    /**
     * Save Config — store user identity settings in DB
     */
    public function saveConfig(Request $request, $jobId)
    {
        $job = $this->getJob($jobId);
        if (!$job) return response()->json(['error' => 'Job not found'], 404);

        $confDir = $this->storagePath . '/' . $jobId . '/config';
        if (!file_exists($confDir)) mkdir($confDir, 0755, true);

        $storeName = trim((string) $request->input('store_name', ''));
        $finalZipName = trim((string) $request->input('final_zip_name', '')) ?: ($storeName ?: null);

        $updateData = [
            'store_name'    => $storeName,
            'author_name'   => $request->input('author_name'),
            'final_zip_name'=> $finalZipName,
        ];

        $userDir = $this->storagePath . '/users/' . Auth::id();
        if (!file_exists($userDir)) @mkdir($userDir, 0755, true);

        file_put_contents($userDir . '/prefs.json', json_encode([
            'store_name'     => $storeName,
            'author_name'    => $request->input('author_name'),
            'final_zip_name' => $finalZipName,
        ]));

        // Save uploaded Author Picture
        if ($request->hasFile('author_pic_file')) {
            $pic = $request->file('author_pic_file');
            $picPath = $confDir . '/AuthorPicture.' . $pic->getClientOriginalExtension();
            $pic->move($confDir, basename($picPath));
            @copy($picPath, $userDir . '/AuthorPicture.png');
            $updateData['author_pic_path'] = $picPath;
        }

        // Save uploaded Signature
        if ($request->hasFile('signature_file')) {
            $sig = $request->file('signature_file');
            $sigPath = $confDir . '/SignaturePicture.' . $sig->getClientOriginalExtension();
            $sig->move($confDir, basename($sigPath));
            @copy($sigPath, $userDir . '/SignaturePicture.png');
            $updateData['sig_pic_path'] = $sigPath;
        }

        $this->updateJob($jobId, $updateData);

        return response()->json([
            'status'         => 'config_saved',
            'store_name'     => $job->store_name,
            'author_name'    => $job->author_name,
            'final_zip_name' => $job->final_zip_name,
        ]);
    }

    /**
     * Scan files
     */
    public function scan(Request $request, $jobId)
    {
        $job = $this->getJob($jobId);
        if (!$job) return $this->jsonResponseSafe(['error' => 'Job not found'], 404);

        $sourceFiles = is_array($job->files ?? null) ? $job->files : [];
        $safeTotalFiles = max(count($sourceFiles), 1);
        $initialMeta = [
            'phase' => 'scan',
            'total_files' => $safeTotalFiles,
            'completed_files' => 0,
            'current_file_index' => 1,
            'current_file_name' => $sourceFiles[0]['name'] ?? 'Package',
            'assets_found' => 0,
            'detected_authors' => 0,
            'elapsed_seconds' => 0,
        ];

        if (!$request->boolean('_background')) {
            $this->updateJob($jobId, [
                'status' => 'scanning',
                'progress' => 2,
                'progress_message' => 'Initializing intelligent scan...',
                'progress_meta' => $initialMeta,
                'error' => null,
                'error_detail' => null,
                'error_reason_code' => null,
            ]);

            $backgroundRequest = $request->duplicate(
                $request->query->all(),
                $request->request->all(),
                $request->attributes->all(),
                $request->cookies->all(),
                $request->files->all(),
                $request->server->all()
            );
            $backgroundRequest->request->set('_background', '1');

            $userId = Auth::id();
            app()->terminating(function () use ($backgroundRequest, $jobId, $userId): void {
                try {
                    if ($userId) {
                        Auth::onceUsingId($userId);
                    }

                    $this->scan($backgroundRequest, $jobId);
                } catch (\Throwable $e) {
                    Log::error("Scan bootstrap failed for job {$jobId}: " . $e->getMessage(), [
                        'exception' => $e,
                    ]);

                    $failure = $this->buildStudioFailurePayload('scan', $e);
                    $this->updateJob($jobId, [
                        'status' => 'failed',
                        'error' => $failure['error'],
                        'error_detail' => $failure['detail'],
                        'error_reason_code' => $failure['reason_code'],
                        'progress_message' => 'Scan failed.',
                        'progress_meta' => array_merge($initialMeta, [
                            'reason_code' => $failure['reason_code'],
                        ]),
                    ]);
                }
            });

            return $this->jsonResponseSafe([
                'status' => 'scanning',
                'progress' => 2,
                'progress_message' => 'Initializing intelligent scan...',
                'progress_meta' => $initialMeta,
            ], 202);
        }

        @set_time_limit(600);
        @ignore_user_abort(true);

        $scanStartedAt = microtime(true);
        $currentSourceName = $initialMeta['current_file_name'];

        try {
            $extractRoot = $this->storagePath . '/' . $jobId . '/work';
            Log::info("Scan starting. Extraction root: {$extractRoot}");
            if (file_exists($extractRoot)) {
                File::deleteDirectory($extractRoot);
            }
            mkdir($extractRoot, 0755, true);

            $this->updateJob($jobId, [
                'status' => 'scanning',
                'progress' => 2,
                'progress_message' => 'Initializing intelligent scan...',
                'progress_meta' => $initialMeta,
                'error' => null,
                'error_detail' => null,
                'error_reason_code' => null,
            ]);

            $manifest = ['assets' => [], 'source_files' => [], 'detected_authors' => [], 'rename_candidates' => []];
            $allAuthorTags = [];
            $allRenameCandidates = [];
            $totalExtractedSize = 0;
            $toolExts = ['brushset', 'brush', 'procreate', 'swatches', 'usdz'];
            $imgExts = ['png', 'jpg', 'jpeg', 'webp'];
            $totalFiles = count($sourceFiles);

            foreach ($sourceFiles as $index => $source) {
                $currentSourceName = (string) ($source['name'] ?? 'Package');
                $currentProgress = 5 + (int) (($index / ($totalFiles ?: 1)) * 90);
                $this->updateJob($jobId, [
                    'progress' => $currentProgress,
                    'progress_message' => "Scanning " . ($index + 1) . "/$totalFiles: " . $currentSourceName,
                    'progress_meta' => [
                        'phase' => 'scan',
                        'total_files' => $safeTotalFiles,
                        'completed_files' => $index,
                        'current_file_index' => $index + 1,
                        'current_file_name' => $currentSourceName,
                        'assets_found' => count($manifest['assets']),
                        'detected_authors' => count($this->uniqueValues($allAuthorTags)),
                        'elapsed_seconds' => (int) floor(microtime(true) - $scanStartedAt),
                    ],
                ]);

                $subDir = $extractRoot . '/' . pathinfo($currentSourceName, PATHINFO_FILENAME);
                if (!file_exists($subDir)) mkdir($subDir, 0755, true);

                $sourcePath = (string) ($source['path'] ?? '');
                if ($sourcePath === '' || !File::exists($sourcePath)) {
                    throw new \RuntimeException("Source file is missing: {$currentSourceName}");
                }

                $isZip = false;
                $zip = new ZipArchive;
                if ($zip->open($sourcePath) === true) {
                    if ($zip->extractTo($subDir) !== true) {
                        $zip->close();
                        throw new \RuntimeException("The archive could not be extracted: {$currentSourceName}");
                    }
                    $zip->close();
                    $isZip = true;
                } else {
                    if (!@copy($sourcePath, $subDir . '/' . basename($currentSourceName))) {
                        throw new \RuntimeException("The source file could not be prepared for scanning: {$currentSourceName}");
                    }
                }

                if (!File::exists($subDir)) {
                    throw new \RuntimeException("The extracted scan folder is missing: {$currentSourceName}");
                }

                try {
                    $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($subDir, \FilesystemIterator::SKIP_DOTS));
                    $totalExtracted = iterator_count($iterator);
                    $iterator->rewind();
                } catch (\Throwable $e) {
                    throw new \RuntimeException("Failed to inspect extracted files for {$currentSourceName}", previous: $e);
                }

                $currentExtractedSize = 0;
                $lastReportedProgress = -1;
                $authorsInFile = [];
                $renameCandidatesInFile = $this->extractAuthorCandidates($currentSourceName);
                $renameCandidatesInFile = array_merge($renameCandidatesInFile, $this->extractAuthorCandidates(pathinfo($currentSourceName, PATHINFO_FILENAME)));
                $assetCount = 0;

                $counter = 0;
                foreach ($iterator as $f) {
                    if ($f->isDir()) continue;

                    $subProgress = ($counter / ($totalExtracted ?: 1)) * (90 / ($totalFiles ?: 1));
                    $newProgress = 5 + (int) (($index / ($totalFiles ?: 1)) * 90 + $subProgress);

                    if ($newProgress > $lastReportedProgress) {
                        $this->updateJob($jobId, [
                            'progress' => min(95, $newProgress),
                            'progress_meta' => [
                                'phase' => 'scan',
                                'total_files' => $safeTotalFiles,
                                'completed_files' => $index,
                                'current_file_index' => $index + 1,
                                'current_file_name' => $currentSourceName,
                                'assets_found' => count($manifest['assets']) + $assetCount,
                                'detected_authors' => count($this->uniqueValues(array_merge($allAuthorTags, $authorsInFile))),
                                'elapsed_seconds' => (int) floor(microtime(true) - $scanStartedAt),
                            ],
                        ]);
                        $lastReportedProgress = $newProgress;
                    }
                    $counter++;

                    $currentExtractedSize += $f->getSize();
                    $realPath = $f->getRealPath();
                    if (!is_string($realPath) || $realPath === '') {
                        continue;
                    }

                    $name = $f->getFilename();
                    $ext = strtolower($f->getExtension());
                    $rel = $this->normalizeManifestPath($extractRoot, $realPath);

                    $renameCandidatesInFile = array_merge($renameCandidatesInFile, $this->extractAuthorCandidates($rel));

                    if (in_array($ext, $imgExts, true) || in_array($ext, $toolExts, true)) {
                        if ($isZip || $name !== $currentSourceName) {
                            $manifest['assets'][] = [
                                'name' => $name,
                                'rel_path' => $rel,
                                'size' => $f->getSize(),
                                'source_name' => $currentSourceName,
                                'category' => $this->classifyAsset($name),
                            ];
                            $assetCount++;
                        }
                    }

                    if ($name === 'Data' || $ext === 'plist' || $ext === 'archive' || in_array($ext, $toolExts, true)) {
                        $content = @file_get_contents($realPath);
                        if ($content !== false && $content !== '') {
                            $authorsInFile = array_merge($authorsInFile, $this->extractAuthorCandidates($content));
                            $authorsInFile = array_merge($authorsInFile, $this->extractBinaryPlistAuthorCandidates($content));
                        }
                    }
                }

                $totalExtractedSize += $currentExtractedSize;

                if ($totalExtractedSize > 10737418240) {
                    throw new \RuntimeException('Total content exceeds 10GB limit.');
                }

                $authorsInFile = $this->uniqueValues($authorsInFile);
                $renameCandidatesInFile = $this->uniqueValues(array_merge($renameCandidatesInFile, $authorsInFile));
                $allAuthorTags = array_merge($allAuthorTags, $authorsInFile);
                $allRenameCandidates = array_merge($allRenameCandidates, $renameCandidatesInFile);

                $manifest['source_files'][] = [
                    'name' => $currentSourceName,
                    'size' => (int) ($source['size'] ?? 0),
                    'asset_count' => $assetCount,
                    'author_tags' => $authorsInFile,
                ];
            }

            $manifest['detected_authors'] = $this->uniqueValues($allAuthorTags);
            $manifest['rename_candidates'] = $this->uniqueValues($allRenameCandidates);
            $manifest = $this->sanitizeForJson($manifest);

            Log::info("Job {$jobId} scan complete. Found " . count($manifest['assets']) . " assets and " . count($manifest['detected_authors']) . " author tags.");

            $progressMeta = [
                'phase' => 'scan',
                'total_files' => $safeTotalFiles,
                'completed_files' => $safeTotalFiles,
                'current_file_index' => $safeTotalFiles,
                'current_file_name' => $sourceFiles[$totalFiles - 1]['name'] ?? 'Package',
                'assets_found' => count($manifest['assets']),
                'detected_authors' => count($manifest['detected_authors']),
                'elapsed_seconds' => (int) floor(microtime(true) - $scanStartedAt),
            ];

            $this->updateJob($jobId, [
                'status' => 'scanned',
                'manifest' => $manifest,
                'progress' => 100,
                'progress_message' => 'Scan complete. Review detected assets before rebrand.',
                'progress_meta' => $progressMeta,
                'error' => null,
                'error_detail' => null,
                'error_reason_code' => null,
            ]);

            return $this->jsonResponseSafe([
                'status' => 'scanned',
                'manifest' => $manifest,
                'progress_meta' => $progressMeta,
            ]);
        } catch (\Throwable $e) {
            Log::error("Scan failed for job {$jobId}: " . $e->getMessage(), [
                'exception' => $e,
                'current_source_name' => $currentSourceName,
            ]);

            $failure = $this->buildStudioFailurePayload('scan', $e, [
                'current_file_name' => $currentSourceName,
            ]);

            $this->updateJob($jobId, [
                'status' => 'failed',
                'error' => $failure['error'],
                'error_detail' => $failure['detail'],
                'error_reason_code' => $failure['reason_code'],
                'progress_message' => 'Scan failed.',
                'progress_meta' => [
                    'phase' => 'scan',
                    'total_files' => $safeTotalFiles,
                    'completed_files' => 0,
                    'current_file_index' => 1,
                    'current_file_name' => $failure['current_file_name'] ?? $currentSourceName,
                    'elapsed_seconds' => (int) floor(microtime(true) - $scanStartedAt),
                    'reason_code' => $failure['reason_code'],
                ],
            ]);

            return $this->jsonResponseSafe($failure, 500);
        }
    }

    /**
     * Preview Asset
     */
    public function previewAsset(Request $request, $jobId)
    {
        $path = $request->query('path');

        if (!$path) return response()->json(['error' => 'No path provided'], 400);

        $fullPath = $this->resolveJobAssetPath($jobId, $path);

        if (!$fullPath) {
            Log::warning("Preview failed: File not found for job {$jobId} path {$path}");
            return response()->json(['error' => 'File not found'], 404);
        }

        $lastModifiedTs = @filemtime($fullPath) ?: time();
        $lastModified = gmdate('D, d M Y H:i:s', $lastModifiedTs) . ' GMT';
        $etag = '"' . sha1($fullPath . '|' . $lastModifiedTs . '|' . (@filesize($fullPath) ?: 0)) . '"';

        if ($request->header('If-None-Match') === $etag) {
            return response('', 304, [
                'Cache-Control' => 'private, max-age=600',
                'ETag' => $etag,
                'Last-Modified' => $lastModified,
            ]);
        }

        $headers = [
            'Content-Type' => File::mimeType($fullPath) ?: 'application/octet-stream',
            'Cache-Control' => 'private, max-age=600',
            'ETag' => $etag,
            'Last-Modified' => $lastModified,
        ];

        return response()->file($fullPath, $headers);
    }

    public function downloadAsset(Request $request, $jobId, $path)
    {
        $fullPath = $this->resolveJobAssetPath($jobId, $path);
        if (!$fullPath) {
            return response()->json(['error' => 'File not found'], 404);
        }

        return response()->download($fullPath);
    }

    public function downloadOutput(Request $request, $jobId, $index)
    {
        $job = $this->getJob($jobId);
        if (!$job) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        $outputs = $job->outputs ?: [];
        $output = $outputs[(int) $index] ?? null;
        if (!is_array($output)) {
            return response()->json(['error' => 'Output not found'], 404);
        }

        $fullPath = $this->resolveJobManagedPath($jobId, $output['path'] ?? null);
        if (!$fullPath) {
            Log::warning("Output download failed: File not found for job {$jobId} output {$index}");
            return response()->json(['error' => 'File not found'], 404);
        }

        $this->queueJobCleanupIfRequested($request, $jobId);

        return response()->download($fullPath, $output['name'] ?? basename($fullPath));
    }

    public function downloadJob(Request $request, $jobId)
    {
        $job = $this->getJob($jobId);
        if (!$job) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        $download = $this->resolveJobDownloadTarget($job);
        if (!$download) {
            Log::warning("Job download failed: No downloadable output found for job {$jobId}");
            return response()->json(['error' => 'File not found'], 404);
        }

        $this->queueJobCleanupIfRequested($request, $jobId);

        $customName = $request->query('filename');
        if ($customName && !str_ends_with(strtolower($customName), '.zip')) {
            $customName .= '.zip';
        }
        $name = $customName ?: $download['name'];

        return response()->download($download['path'], $name);
    }

    public function rebrand(Request $request, $jobId)
    {
        @set_time_limit(600); // Increase timeout for repackaging
        @ignore_user_abort(true);
        $job = $this->getJob($jobId);
        if (!$job) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        if (!$request->boolean('_background')) {
            $files = is_array($job->files ?? null) ? $job->files : [];
            $activeProgressMeta = is_array($job->progress_meta ?? null) ? $job->progress_meta : [];
            if (($job->status ?? null) === 'processing' && ($activeProgressMeta['phase'] ?? null) === 'rebrand') {
                return $this->jsonResponseSafe([
                    'status' => 'processing',
                    'job_id' => $jobId,
                    'progress' => (int) ($job->progress ?? 2),
                    'progress_message' => $job->progress_message ?? 'Repackaging is already running...',
                    'progress_meta' => $activeProgressMeta,
                    'drive_storage' => $job->drive_storage ?? [],
                ], 202);
            }

            $initialMeta = [
                'phase' => 'rebrand',
                'total_files' => max(count($files), 1),
                'completed_files' => 0,
                'current_file_index' => 1,
                'current_file_name' => $files[0]['name'] ?? 'Package',
                'elapsed_seconds' => 0,
                'action' => 'Preparing assets',
            ];

            $this->updateJob($jobId, [
                'status' => 'processing',
                'progress' => 2,
                'progress_message' => 'Preparing rebranding engine...',
                'progress_meta' => $initialMeta,
                'error' => null,
            ]);

            $backgroundRequest = $request->duplicate(
                $request->query->all(),
                $request->request->all(),
                $request->attributes->all(),
                $request->cookies->all(),
                $request->files->all(),
                $request->server->all()
            );
            $backgroundRequest->request->set('_background', '1');

            $userId = Auth::id();
            app()->terminating(function () use ($backgroundRequest, $jobId, $userId, $files): void {
                $lockHandle = null;
                try {
                    $lockHandle = $this->acquireJobProcessLock($jobId, 'rebrand');
                    if ($userId) {
                        Auth::onceUsingId($userId);
                    }

                    $this->rebrand($backgroundRequest, $jobId);
                } catch (\RuntimeException $e) {
                    if ($e->getMessage() === 'Job lock is already held.') {
                        Log::info("Rebrand already running for job {$jobId}; duplicate background trigger ignored.");
                        return;
                    }

                    throw $e;
                } catch (\Throwable $e) {
                    Log::error("Rebrand failed for job {$jobId}: " . $e->getMessage(), [
                        'exception' => $e,
                    ]);

                    $currentJob = $this->getJob($jobId);
                    $progressMeta = is_array($currentJob?->progress_meta ?? null) ? $currentJob->progress_meta : [];
                    $failure = $this->buildStudioFailurePayload('rebrand', $e, [
                        'current_file_name' => $progressMeta['current_file_name'] ?? ($files[0]['name'] ?? 'Package'),
                    ]);

                    $this->updateJob($jobId, [
                        'status' => 'failed',
                        'error' => $failure['error'],
                        'error_detail' => $failure['detail'],
                        'error_reason_code' => $failure['reason_code'],
                        'progress_message' => 'Repackaging failed.',
                        'progress_meta' => [
                            'phase' => 'rebrand',
                            'total_files' => max(count($files), 1),
                            'completed_files' => 0,
                            'current_file_index' => 1,
                            'current_file_name' => $failure['current_file_name'] ?? ($files[0]['name'] ?? 'Package'),
                            'elapsed_seconds' => 0,
                            'action' => 'Failed',
                            'reason_code' => $failure['reason_code'],
                        ],
                    ]);
                } finally {
                    $this->releaseJobProcessLock($lockHandle);
                }
            });

            try {
                if (app()->bound('session')) {
                    app('session')->save();
                }
            } catch (\Throwable) {
                // Ignore session flush failures and continue with async processing.
            }

            if (function_exists('session_write_close')) {
                @session_write_close();
            }

            return response()->json([
                'status' => 'processing',
                'job_id' => $jobId,
                'progress' => 2,
                'progress_message' => 'Preparing rebranding engine...',
                'progress_meta' => $initialMeta,
                'drive_storage' => $job->drive_storage ?? [],
            ], 202);
        }

        $rebrandStartedAt = microtime(true);

        $this->updateJob($jobId, [
            'status' => 'processing',
            'progress' => 2,
            'progress_message' => 'Preparing rebranding engine...',
            'progress_meta' => [
                'phase' => 'rebrand',
                'total_files' => max(count($job->files ?: []), 1),
                'completed_files' => 0,
                'current_file_index' => 1,
                'current_file_name' => $job->files[0]['name'] ?? 'Package',
                'elapsed_seconds' => 0,
                'action' => 'Preparing assets',
            ],
        ]);

        $userDir = $this->storagePath . '/users/' . Auth::id();
        if (!file_exists($userDir)) @mkdir($userDir, 0755, true);

        $storeNameInput = trim((string) $request->input('store_name', ''));
        $authorNameInput = trim((string) $request->input('author_name', ''));
        $finalZipNameInput = trim((string) $request->input('final_zip_name', ''));

        if (!$storeNameInput || !$authorNameInput) {
            if (file_exists($userDir . '/prefs.json')) {
                $prefs = json_decode(file_get_contents($userDir . '/prefs.json'), true);
                if (!$storeNameInput && !empty($prefs['store_name'])) $storeNameInput = $prefs['store_name'];
                if (!$authorNameInput && !empty($prefs['author_name'])) $authorNameInput = $prefs['author_name'];
                if (!$finalZipNameInput && !empty($prefs['final_zip_name'])) $finalZipNameInput = $prefs['final_zip_name'];
            } else {
                /*
                $lastJob = StudioJob::where('user_id', auth()->id())
                    ->whereNotNull('store_name')
                    ->where('store_name', '!=', '')
                    ->latest()
                    ->first();
                    
                if ($lastJob) {
                    if (!$storeNameInput) $storeNameInput = $lastJob->store_name;
                    if (!$authorNameInput) $authorNameInput = $lastJob->author_name;
                    if (!$finalZipNameInput) $finalZipNameInput = $lastJob->final_zip_name;
                }
                */
            }
        }

        // Save new preferences if provided
        if ($storeNameInput && $authorNameInput) {
            file_put_contents($userDir . '/prefs.json', json_encode([
                'store_name'     => $storeNameInput,
                'author_name'    => $authorNameInput,
                'final_zip_name' => $finalZipNameInput ?: $storeNameInput,
            ]));
        }

        $storeName = $storeNameInput ?: 'My Store';
        $authorName = $authorNameInput ?: $storeName;
        $finalZipName = $finalZipNameInput ?: $storeName;
        $driveToken = $this->resolveGoogleDriveAccessToken($request->input('drive_token'));
        $driveStorage = is_array($job->drive_storage ?? null) ? $job->drive_storage : [];
        
        $authorReplacements = $this->buildAuthorReplacementMap(
            $job->manifest['rename_candidates'] ?? ($job->manifest['detected_authors'] ?? []),
            $authorName
        );
        
        $signatureFile = $request->file('signature_file');
        $authorPictureFile = $request->file('author_pic_file');

        $signaturePath = $signatureFile ? $signatureFile->getRealPath() : null;
        $authorPicturePath = $authorPictureFile ? $authorPictureFile->getRealPath() : null;

        $confDir = $this->storagePath . '/' . $jobId . '/config';

        if ($signaturePath) {
            @copy($signaturePath, $userDir . '/SignaturePicture.png');
        } else {
            if (file_exists($confDir . '/SignaturePicture.png')) {
                $signaturePath = $confDir . '/SignaturePicture.png';
            } else if (file_exists($userDir . '/SignaturePicture.png')) {
                $signaturePath = $userDir . '/SignaturePicture.png';
            }
        }

        if ($authorPicturePath) {
            @copy($authorPicturePath, $userDir . '/AuthorPicture.png');
        } else {
            if (file_exists($confDir . '/AuthorPicture.png')) {
                $authorPicturePath = $confDir . '/AuthorPicture.png';
            } else if (file_exists($userDir . '/AuthorPicture.png')) {
                $authorPicturePath = $userDir . '/AuthorPicture.png';
            }
        }
        
        $outputDir = $this->storagePath . '/' . $jobId . '/output';
        if (!file_exists($outputDir)) mkdir($outputDir, 0755, true);

        $rebrandedFiles = [];
        $total = count($job->files ?: []);
        $safeTotal = max($total, 1);
        
        foreach ($job->files as $index => $source) {
            Log::info("Rebranding file " . ($index+1) . " of $total: " . $source['name']);
            $currentProgress = 5 + (int)(($index / ($total ?: 1)) * 85);
            $this->updateJob($jobId, [
                'progress' => $currentProgress,
                'progress_message' => "Rebranding " . ($index+1) . "/$total: " . $source['name'],
                'progress_meta' => [
                    'phase' => 'rebrand',
                    'total_files' => $safeTotal,
                    'completed_files' => $index,
                    'current_file_index' => $index + 1,
                    'current_file_name' => $source['name'],
                    'elapsed_seconds' => (int) floor(microtime(true) - $rebrandStartedAt),
                    'action' => 'Rewriting metadata',
                ],
            ]);
            
            $sourceStem = pathinfo($source['name'], PATHINFO_FILENAME);
            $sourceExtractPath = $this->storagePath . '/' . $jobId . '/work/' . $sourceStem;
            
            // Critical Check: Ensure source exists before processing
            if (!File::exists($sourceExtractPath)) {
                Log::error("Missing source for rebranding: " . $sourceExtractPath);
                continue; // Skip this file if missing but don't crash others
            }

            $workDir = $this->storagePath . '/' . $jobId . '/temp_' . $index;
            if (File::exists($workDir)) {
                File::deleteDirectory($workDir);
            }

            if (!File::copyDirectory($sourceExtractPath, $workDir)) {
                throw new \RuntimeException("Failed to prepare a temporary workspace for {$source['name']}");
            }

            if (!File::exists($workDir)) {
                throw new \RuntimeException("Temporary workspace was not created for {$source['name']}");
            }

            try {
                $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($workDir, \FilesystemIterator::SKIP_DOTS));
                $totalWorkFiles = iterator_count($iterator);
                $iterator->rewind();
            } catch (\Exception $e) {
                Log::error("Failed to iterate rebrand directory for job {$jobId}: " . $e->getMessage());
                continue;
            }

            $lastRebrandProgress = -1;

            $counter = 0;
            foreach ($iterator as $f) {
                if ($f->isDir()) continue;

                $filePath = $f->getPathname();
                if (!is_string($filePath) || $filePath === '') {
                    continue;
                }
                
                if ($this->shouldRewriteMetadataFile($f)) {
                    $this->rewriteMetadataFile($filePath, $authorReplacements);
                }

                // Sub-progress during rebranding
                $subProgress = ($counter / ($totalWorkFiles ?: 1)) * (85 / ($total ?: 1));
                $newProgress = 5 + (int)(($index / ($total ?: 1)) * 85 + $subProgress);
                
                if ($newProgress > $lastRebrandProgress) {
                    $this->updateJob($jobId, [
                        'progress' => min(94, $newProgress),
                        'progress_meta' => [
                            'phase' => 'rebrand',
                            'total_files' => $safeTotal,
                            'completed_files' => $index,
                            'current_file_index' => $index + 1,
                            'current_file_name' => $source['name'],
                            'elapsed_seconds' => (int) floor(microtime(true) - $rebrandStartedAt),
                            'action' => $this->shouldRewriteMetadataFile($f)
                                ? 'Rewriting metadata'
                                : 'Inspecting packaged files',
                        ],
                    ]);
                    $lastRebrandProgress = $newProgress;
                }
                $counter++;
            }

            $this->updateJob($jobId, [
                'progress_meta' => [
                    'phase' => 'rebrand',
                    'total_files' => $safeTotal,
                    'completed_files' => $index,
                    'current_file_index' => $index + 1,
                    'current_file_name' => $source['name'],
                    'elapsed_seconds' => (int) floor(microtime(true) - $rebrandStartedAt),
                    'action' => 'Replacing identity assets',
                ],
            ]);
            $this->replaceBrandImagesInDirectory($workDir, $authorPicturePath, $signaturePath);

            $this->updateJob($jobId, [
                'progress_meta' => [
                    'phase' => 'rebrand',
                    'total_files' => $safeTotal,
                    'completed_files' => $index,
                    'current_file_index' => $index + 1,
                    'current_file_name' => $source['name'],
                    'elapsed_seconds' => (int) floor(microtime(true) - $rebrandStartedAt),
                    'action' => 'Renaming author paths',
                ],
            ]);
            $this->renameAuthorTaggedPaths($workDir, $authorReplacements);

            $newName = $this->buildOutputFilename($source['name'], $storeName);
            $outputPath = $outputDir . '/' . $newName;

            $this->updateJob($jobId, [
                'progress_meta' => [
                    'phase' => 'rebrand',
                    'total_files' => $safeTotal,
                    'completed_files' => $index,
                    'current_file_index' => $index + 1,
                    'current_file_name' => $source['name'],
                    'elapsed_seconds' => (int) floor(microtime(true) - $rebrandStartedAt),
                    'action' => 'Packaging output',
                ],
            ]);
            $this->zipDirectory($workDir, $outputPath);
            
            $rebrandedFiles[] = [
                'name' => $newName,
                'path' => $outputPath,
                'size' => filesize($outputPath)
            ];
            
            File::deleteDirectory($workDir);
        }

        $this->updateJob($jobId, [
            'progress' => 95,
            'progress_message' => "Zipping final bundle...",
            'progress_meta' => [
                'phase' => 'rebrand',
                'total_files' => $safeTotal,
                'completed_files' => $safeTotal,
                'current_file_index' => $safeTotal,
                'current_file_name' => $rebrandedFiles[count($rebrandedFiles) - 1]['name'] ?? ($job->files[$total - 1]['name'] ?? 'Package'),
                'elapsed_seconds' => (int) floor(microtime(true) - $rebrandStartedAt),
                'action' => 'Zipping final bundle',
            ],
        ]);

        $bundle = count($rebrandedFiles) > 0
            ? $this->buildOutputBundle($jobId, $rebrandedFiles, $finalZipName . '.zip')
            : null;

        if ($driveToken !== '' && count($rebrandedFiles) > 0) {
            $this->updateJob($jobId, [
                'progress' => 97,
                'progress_message' => 'Syncing outputs to Google Drive...',
                'progress_meta' => [
                    'phase' => 'rebrand',
                    'total_files' => $safeTotal,
                    'completed_files' => $safeTotal,
                    'current_file_index' => $safeTotal,
                    'current_file_name' => $rebrandedFiles[count($rebrandedFiles) - 1]['name'] ?? ($job->files[$total - 1]['name'] ?? 'Package'),
                    'elapsed_seconds' => (int) floor(microtime(true) - $rebrandStartedAt),
                    'action' => 'Syncing to Google Drive',
                ],
            ]);

            try {
                $driveSync = $this->syncManagedFilesToGoogleDrive($jobId, $rebrandedFiles, $driveToken, $driveStorage);
                $rebrandedFiles = $driveSync['files'];
                $driveStorage = $driveSync['drive_storage'];
                $driveStorage['outputs_synced'] = count($rebrandedFiles);

                if (is_array($bundle)) {
                    $bundleSync = $this->syncManagedFilesToGoogleDrive($jobId, [$bundle], $driveToken, $driveStorage);
                    $bundle = $bundleSync['files'][0] ?? $bundle;
                    $driveStorage = $bundleSync['drive_storage'];
                    $driveStorage['bundle_synced'] = isset($bundle['drive_file_id']);
                }
            } catch (\Throwable $e) {
                Log::warning("Output Drive sync failed for job {$jobId}: " . $e->getMessage());
                $driveStorage = $this->buildDriveStorageErrorState($driveStorage, $e->getMessage());
            }
        } elseif ($driveStorage !== []) {
            $driveStorage['status'] = $driveStorage['status'] ?? 'synced';
        }

        // Keep extracted work files until explicit cleanup so scanned previews
        // and step-back review continue to work after completion.
        if (File::exists($this->storagePath . '/' . $jobId . '/input')) {
            File::deleteDirectory($this->storagePath . '/' . $jobId . '/input');
        }

        $this->updateJob($jobId, [
            'status' => 'completed',
            'outputs' => $rebrandedFiles,
            'bundle' => $bundle,
            'progress' => 100,
            'progress_message' => 'Rebrand complete. Files are ready to download.',
            'progress_meta' => [
                'phase' => 'rebrand',
                'total_files' => $safeTotal,
                'completed_files' => $safeTotal,
                'current_file_index' => $safeTotal,
                'current_file_name' => $rebrandedFiles[count($rebrandedFiles) - 1]['name'] ?? ($job->files[$total - 1]['name'] ?? 'Package'),
                'elapsed_seconds' => (int) floor(microtime(true) - $rebrandStartedAt),
                'action' => 'Complete',
            ],
            'store_name' => $storeName,
            'final_zip_name' => $finalZipName,
            'drive_storage' => $driveStorage,
        ]);

        return response()->json([
            'status' => 'completed',
            'outputs' => $rebrandedFiles,
            'bundle' => $bundle,
            'progress_meta' => [
                'phase' => 'rebrand',
                'total_files' => $safeTotal,
                'completed_files' => $safeTotal,
                'current_file_index' => $safeTotal,
                'current_file_name' => $rebrandedFiles[count($rebrandedFiles) - 1]['name'] ?? ($job->files[$total - 1]['name'] ?? 'Package'),
                'elapsed_seconds' => (int) floor(microtime(true) - $rebrandStartedAt),
                'action' => 'Complete',
            ],
            'store_name' => $storeName,
            'final_zip_name' => $finalZipName,
            'drive_storage' => $driveStorage,
        ]);
    }

    public function renameOutput(Request $request, $jobId)
    {
        $job = $this->getJob($jobId);
        if (!$job) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        $oldName = trim((string) $request->input('old_name'));
        $newName = trim((string) $request->input('new_name'));

        if (!$oldName || !$newName) {
            return response()->json(['error' => 'old_name and new_name are required'], 400);
        }

        $outputs = $job->outputs ?? [];
        $outputDir = $this->storagePath . '/' . $jobId . '/output';
        $driveStorage = is_array($job->drive_storage ?? null) ? $job->drive_storage : [];
        $driveToken = $this->resolveGoogleDriveAccessToken($request->input('drive_token'));

        $foundIndex = -1;
        foreach ($outputs as $index => $out) {
            if ($out['name'] === $oldName) {
                $foundIndex = $index;
                break;
            }
        }

        if ($foundIndex === -1) {
            return response()->json(['error' => 'Output file not found'], 404);
        }

        $oldPath = $outputDir . '/' . $oldName;
        $newPath = $outputDir . '/' . $newName;

        if (!File::exists($oldPath)) {
            return response()->json(['error' => 'Physical file not found on disk'], 404);
        }

        if (File::exists($newPath) && $oldName !== $newName) {
            return response()->json(['error' => 'A file with the new name already exists'], 400);
        }

        if ($oldName !== $newName) {
            $oldDriveFileId = $outputs[$foundIndex]['drive_file_id'] ?? null;
            $oldBundleDriveFileId = is_array($job->bundle) ? ($job->bundle['drive_file_id'] ?? null) : null;
            rename($oldPath, $newPath);
            $outputs[$foundIndex] = [
                'name' => $newName,
                'path' => $newPath,
                'size' => filesize($newPath)
            ];

            // Update the bundle with the new names
            if ($job->bundle && isset($job->bundle['path']) && File::exists($job->bundle['path'])) {
                File::delete($job->bundle['path']);
            }
            
            $bundle = $this->buildOutputBundle($jobId, $outputs, $job->final_zip_name . '.zip');

            if ($driveToken !== '' && $driveStorage !== []) {
                try {
                    $outputSync = $this->syncManagedFilesToGoogleDrive(
                        $jobId,
                        [$outputs[$foundIndex]],
                        $driveToken,
                        $driveStorage,
                        [0 => array_filter([(string) $oldDriveFileId])]
                    );
                    $outputs[$foundIndex] = $outputSync['files'][0] ?? $outputs[$foundIndex];
                    $driveStorage = $outputSync['drive_storage'];

                    if (is_array($bundle)) {
                        $bundleSync = $this->syncManagedFilesToGoogleDrive(
                            $jobId,
                            [$bundle],
                            $driveToken,
                            $driveStorage,
                            [0 => array_filter([(string) $oldBundleDriveFileId])]
                        );
                        $bundle = $bundleSync['files'][0] ?? $bundle;
                        $driveStorage = $bundleSync['drive_storage'];
                    }
                } catch (\Throwable $e) {
                    Log::warning("Drive rename sync failed for job {$jobId}: " . $e->getMessage());
                    $driveStorage = $this->buildDriveStorageErrorState($driveStorage, $e->getMessage());
                }
            } elseif ($driveStorage !== []) {
                $driveStorage['status'] = 'out_of_sync';
                $driveStorage['error'] = 'Output names changed locally. Reconnect Google Drive and rename again to resync.';
            }

            $this->updateJob($jobId, [
                'outputs' => $outputs,
                'bundle' => $bundle,
                'drive_storage' => $driveStorage,
            ]);
        }

        $freshJob = $this->getJob($jobId);

        return response()->json([
            'status' => 'renamed',
            'outputs' => $freshJob->outputs ?? $outputs,
            'bundle' => $freshJob->bundle ?? ($job->bundle ?? null),
            'drive_storage' => $freshJob->drive_storage ?? $driveStorage,
        ]);
    }

    public function getStatus($jobId)
    {
        $job = $this->getJob($jobId);
        return $this->jsonResponseSafe($job ?: ['error' => 'Job not found'], $job ? 200 : 404);
    }

    public function cleanup($jobId)
    {
        $this->deleteJobData($jobId);
        return response()->json(['status' => 'success']);
    }



    // --- Helpers ---

    private function hasOwnerManagedGoogleDriveStorage(): bool
    {
        $google = config('services.google');

        return (bool) ($google['owner_managed'] ?? false)
            && trim((string) ($google['client_id'] ?? '')) !== ''
            && trim((string) ($google['client_secret'] ?? '')) !== ''
            && trim((string) ($google['refresh_token'] ?? '')) !== '';
    }

    private function resolveGoogleDriveAccessToken(mixed $requestToken = null): string
    {
        if ($this->hasOwnerManagedGoogleDriveStorage()) {
            try {
                return $this->getOwnerManagedGoogleDriveAccessToken();
            } catch (\Throwable $e) {
                Log::warning('Owner-managed Google Drive token fetch failed: ' . $e->getMessage());
            }
        }

        return trim((string) ($requestToken ?? ''));
    }

    private function getOwnerManagedGoogleDriveAccessToken(): string
    {
        if ($this->ownerDriveAccessToken && is_int($this->ownerDriveAccessTokenExpiresAt) && $this->ownerDriveAccessTokenExpiresAt > (time() + 60)) {
            return $this->ownerDriveAccessToken;
        }

        if (!$this->hasOwnerManagedGoogleDriveStorage()) {
            throw new \RuntimeException('Owner-managed Google Drive credentials are not configured.');
        }

        $google = config('services.google');
        $response = Http::asForm()
            ->timeout(60)
            ->post('https://oauth2.googleapis.com/token', [
                'client_id' => $google['client_id'],
                'client_secret' => $google['client_secret'],
                'refresh_token' => $google['refresh_token'],
                'grant_type' => 'refresh_token',
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Google OAuth token refresh failed: ' . $response->body());
        }

        $accessToken = trim((string) $response->json('access_token'));
        if ($accessToken === '') {
            throw new \RuntimeException('Google OAuth token refresh returned an empty access token.');
        }

        $expiresIn = (int) ($response->json('expires_in') ?? 3600);
        $this->ownerDriveAccessToken = $accessToken;
        $this->ownerDriveAccessTokenExpiresAt = time() + max(300, $expiresIn);

        return $this->ownerDriveAccessToken;
    }

    private function fetchGoogleDriveStorageQuota(string $token): array
    {
        $response = Http::timeout(60)
            ->withToken($token)
            ->get('https://www.googleapis.com/drive/v3/about', [
                'fields' => 'storageQuota',
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Google Drive quota lookup failed: ' . $response->body());
        }

        $storageQuota = $response->json('storageQuota');
        if (!is_array($storageQuota)) {
            throw new \RuntimeException('Google Drive quota response is invalid.');
        }

        $limitBytes = (int) ($storageQuota['limit'] ?? 0);
        $usedBytes = (int) ($storageQuota['usage'] ?? 0);
        $remainingBytes = max(0, $limitBytes - $usedBytes);
        $percent = $limitBytes > 0 ? ($usedBytes / $limitBytes) * 100 : 0;

        $status = 'normal';
        if ($percent >= 100) {
            $status = 'full';
        } elseif ($percent >= 80) {
            $status = 'warning';
        }

        return [
            'limit_bytes' => $limitBytes,
            'used_bytes' => $usedBytes,
            'remaining_bytes' => $remainingBytes,
            'percent' => $percent,
            'status' => $status,
        ];
    }

    private function ensureGoogleDriveRootFolder(string $token): array
    {
        return Cache::remember('google_drive_owner_root_folder', now()->addDay(), function () use ($token) {
            $appName = trim((string) config('app.name', 'THOR REBRAND TOOL'));
            $rootFolderName = $appName !== '' ? $appName . ' Storage' : 'THOR REBRAND TOOL Storage';
            $rootFolderId = $this->findOrCreateGoogleDriveFolder($token, $rootFolderName);

            return [
                'root_folder_id' => $rootFolderId,
                'root_folder_name' => $rootFolderName,
                'root_folder_url' => $this->buildGoogleDriveFolderUrl($rootFolderId),
            ];
        });
    }

    private function normalizeGoogleDriveQuotaError(string $message): string
    {
        $haystack = strtolower($message);

        if (str_contains($haystack, 'accessnotconfigured') || str_contains($haystack, 'service_disabled') || str_contains($haystack, 'google drive api has not been used')) {
            return 'Google Drive API is disabled in this Google Cloud project. Enable drive.googleapis.com, wait a minute, then refresh.';
        }

        if (str_contains($haystack, 'invalid_grant')) {
            return 'The owner Google Drive refresh token is invalid or expired. Generate a new refresh token and update the .env file.';
        }

        if (str_contains($haystack, 'insufficient') && str_contains($haystack, 'scope')) {
            return 'The owner Google Drive token is missing the required Drive scopes. Regenerate it with drive.file and drive.metadata.readonly.';
        }

        return 'Google Drive quota could not be loaded right now. Check the Drive API and owner OAuth credentials, then refresh.';
    }

    private function syncManagedFilesToGoogleDrive(
        string $jobId,
        array $files,
        string $token,
        array $driveStorage = [],
        array $deleteFileIdsByIndex = []
    ): array {
        if (trim($token) === '') {
            throw new \InvalidArgumentException('A Google Drive access token is required for storage sync.');
        }

        $folder = $this->ensureGoogleDriveJobFolder($token, $jobId, $driveStorage);
        $driveStorage = $this->buildDriveStorageState($driveStorage, $folder);
        $syncedFiles = [];

        foreach ($files as $index => $file) {
            if (!is_array($file)) {
                continue;
            }

            $resolvedPath = $this->resolveJobManagedPath($jobId, $file['path'] ?? null);
            if (!$resolvedPath || !File::exists($resolvedPath)) {
                $syncedFiles[] = $file;
                continue;
            }

            $uploaded = $this->uploadLocalFileToGoogleDrive(
                $token,
                $resolvedPath,
                $file['name'] ?? basename($resolvedPath),
                $folder['job_folder_id'],
                $deleteFileIdsByIndex[$index] ?? []
            );

            $syncedFiles[] = array_merge($file, $this->buildDriveFileMetadata($uploaded));
        }

        $driveStorage['enabled'] = true;
        $driveStorage['provider'] = 'google_drive';
        $driveStorage['status'] = 'synced';
        $driveStorage['error'] = null;
        $driveStorage['last_synced_at'] = now()->toIso8601String();

        return [
            'files' => $syncedFiles,
            'drive_storage' => $driveStorage,
        ];
    }

    private function buildDriveStorageState(array $existing, array $folder): array
    {
        return array_merge($existing, [
            'enabled' => true,
            'provider' => 'google_drive',
            'root_folder_id' => $folder['root_folder_id'],
            'root_folder_url' => $folder['root_folder_url'],
            'job_folder_id' => $folder['job_folder_id'],
            'job_folder_url' => $folder['job_folder_url'],
        ]);
    }

    private function shouldDeferInputDriveSync(array $files): bool
    {
        $totalBytes = 0;

        foreach ($files as $file) {
            if (!is_array($file)) {
                continue;
            }

            $totalBytes += max(0, (int) ($file['size'] ?? 0));
        }

        return $totalBytes >= 536870912; // 512MB+
    }

    private function buildDeferredInputDriveStorage(string $jobId, string $token, array $files, array $existing = []): array
    {
        $folder = $this->ensureGoogleDriveJobFolder($token, $jobId, $existing);
        $totalBytes = 0;

        foreach ($files as $file) {
            if (!is_array($file)) {
                continue;
            }

            $totalBytes += max(0, (int) ($file['size'] ?? 0));
        }

        return $this->buildDriveStorageState(array_merge($existing, [
            'enabled' => true,
            'provider' => 'google_drive',
            'status' => 'pending',
            'error' => null,
            'inputs_synced' => (int) ($existing['inputs_synced'] ?? 0),
            'deferred_inputs' => count($files),
            'deferred_input_bytes' => $totalBytes,
            'notice' => 'Large input upload stayed local during processing to avoid Google Drive timeout. Outputs still sync to Google Drive.',
        ]), $folder);
    }

    private function buildDriveStorageErrorState(array $existing, string $message): array
    {
        $existing['enabled'] = $existing['enabled'] ?? true;
        $existing['provider'] = 'google_drive';
        $existing['status'] = 'error';
        $existing['error'] = $message;
        $existing['last_synced_at'] = now()->toIso8601String();

        return $existing;
    }

    private function ensureGoogleDriveJobFolder(string $token, string $jobId, array $driveStorage = []): array
    {
        $jobFolderName = 'Job ' . $jobId;

        $rootFolderId = isset($driveStorage['root_folder_id']) && is_string($driveStorage['root_folder_id'])
            ? trim($driveStorage['root_folder_id'])
            : '';
        $jobFolderId = isset($driveStorage['job_folder_id']) && is_string($driveStorage['job_folder_id'])
            ? trim($driveStorage['job_folder_id'])
            : '';

        if ($rootFolderId === '') {
            $rootFolder = $this->ensureGoogleDriveRootFolder($token);
            $rootFolderId = $rootFolder['root_folder_id'];
            $rootFolderUrl = $rootFolder['root_folder_url'];
        } else {
            $rootFolderUrl = $this->buildGoogleDriveFolderUrl($rootFolderId);
        }

        if ($jobFolderId === '') {
            $jobFolderId = $this->findOrCreateGoogleDriveFolder($token, $jobFolderName, $rootFolderId);
        }

        return [
            'root_folder_id' => $rootFolderId,
            'root_folder_url' => $rootFolderUrl,
            'job_folder_id' => $jobFolderId,
            'job_folder_url' => $this->buildGoogleDriveFolderUrl($jobFolderId),
        ];
    }

    private function findOrCreateGoogleDriveFolder(string $token, string $name, ?string $parentId = null): string
    {
        $queryParts = [
            "mimeType = 'application/vnd.google-apps.folder'",
            "name = '" . $this->escapeGoogleDriveQueryValue($name) . "'",
            'trashed = false',
        ];

        if ($parentId) {
            $queryParts[] = "'" . $this->escapeGoogleDriveQueryValue($parentId) . "' in parents";
        }

        $response = Http::timeout(60)
            ->withToken($token)
            ->get('https://www.googleapis.com/drive/v3/files', [
                'q' => implode(' and ', $queryParts),
                'fields' => 'files(id,name)',
                'pageSize' => 1,
                'supportsAllDrives' => 'true',
                'includeItemsFromAllDrives' => 'true',
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Google Drive folder lookup failed: ' . $response->body());
        }

        $existingId = $response->json('files.0.id');
        if (is_string($existingId) && trim($existingId) !== '') {
            return $existingId;
        }

        $payload = ['name' => $name, 'mimeType' => 'application/vnd.google-apps.folder'];
        if ($parentId) {
            $payload['parents'] = [$parentId];
        }

        $create = Http::timeout(60)
            ->withToken($token)
            ->post('https://www.googleapis.com/drive/v3/files?fields=id,name', $payload);

        if (!$create->successful()) {
            throw new \RuntimeException('Google Drive folder creation failed: ' . $create->body());
        }

        $folderId = $create->json('id');
        if (!is_string($folderId) || trim($folderId) === '') {
            throw new \RuntimeException('Google Drive returned an empty folder ID.');
        }

        return $folderId;
    }

    private function uploadLocalFileToGoogleDrive(
        string $token,
        string $localPath,
        string $name,
        string $parentId,
        array $deleteFileIds = []
    ): array {
        foreach (array_filter(array_map('strval', $deleteFileIds)) as $fileId) {
            $this->deleteGoogleDriveFile($token, $fileId);
        }

        $this->deleteGoogleDriveFilesByName($token, $name, $parentId);

        $mimeType = File::mimeType($localPath) ?: 'application/octet-stream';
        $size = filesize($localPath);
        if ($size === false) {
            throw new \RuntimeException("Failed to read file size for {$localPath}.");
        }

        $metadata = [
            'name' => $name,
            'parents' => [$parentId],
        ];

        $initResponse = Http::timeout(120)
            ->withToken($token)
            ->withHeaders([
                'Content-Type' => 'application/json; charset=UTF-8',
                'X-Upload-Content-Type' => $mimeType,
                'X-Upload-Content-Length' => (string) $size,
            ])
            ->withBody(json_encode($metadata, JSON_THROW_ON_ERROR), 'application/json; charset=UTF-8')
            ->send('POST', 'https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable&fields=id,name,size,webViewLink,webContentLink');

        if (!$initResponse->successful()) {
            throw new \RuntimeException('Google Drive resumable upload start failed: ' . $initResponse->body());
        }

        $uploadUrl = $initResponse->header('Location');
        if (!is_string($uploadUrl) || trim($uploadUrl) === '') {
            throw new \RuntimeException('Google Drive did not return a resumable upload URL.');
        }

        $stream = fopen($localPath, 'rb');
        if ($stream === false) {
            throw new \RuntimeException("Failed to open {$localPath} for Google Drive upload.");
        }

        try {
            $uploadResponse = Http::timeout(1800)
                ->withToken($token)
                ->withHeaders([
                    'Content-Length' => (string) $size,
                    'Content-Type' => $mimeType,
                ])
                ->send('PUT', $uploadUrl, ['body' => $stream]);
        } finally {
            fclose($stream);
        }

        if (!$uploadResponse->successful()) {
            throw new \RuntimeException('Google Drive file upload failed: ' . $uploadResponse->body());
        }

        $payload = $uploadResponse->json();
        $fileId = $payload['id'] ?? null;
        if (!is_string($fileId) || trim($fileId) === '') {
            throw new \RuntimeException('Google Drive returned an empty file ID after upload.');
        }

        return [
            'id' => $fileId,
            'name' => $payload['name'] ?? $name,
            'size' => isset($payload['size']) ? (int) $payload['size'] : $size,
            'webViewLink' => $payload['webViewLink'] ?? $this->buildGoogleDriveFileUrl($fileId),
            'webContentLink' => $payload['webContentLink'] ?? null,
        ];
    }

    private function deleteGoogleDriveFilesByName(string $token, string $name, string $parentId): void
    {
        $response = Http::timeout(60)
            ->withToken($token)
            ->get('https://www.googleapis.com/drive/v3/files', [
                'q' => implode(' and ', [
                    "name = '" . $this->escapeGoogleDriveQueryValue($name) . "'",
                    "'" . $this->escapeGoogleDriveQueryValue($parentId) . "' in parents",
                    'trashed = false',
                ]),
                'fields' => 'files(id)',
                'pageSize' => 100,
                'supportsAllDrives' => 'true',
                'includeItemsFromAllDrives' => 'true',
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Google Drive duplicate cleanup lookup failed: ' . $response->body());
        }

        foreach ($response->json('files', []) as $file) {
            if (!is_array($file) || empty($file['id'])) {
                continue;
            }

            $this->deleteGoogleDriveFile($token, (string) $file['id']);
        }
    }

    private function deleteGoogleDriveFile(string $token, string $fileId): void
    {
        if (trim($fileId) === '') {
            return;
        }

        $response = Http::timeout(60)
            ->withToken($token)
            ->delete("https://www.googleapis.com/drive/v3/files/{$fileId}");

        if ($response->status() !== 204 && !$response->successful() && $response->status() !== 404) {
            throw new \RuntimeException('Google Drive file delete failed: ' . $response->body());
        }
    }

    private function buildDriveFileMetadata(array $file): array
    {
        $fileId = (string) ($file['id'] ?? '');

        return [
            'drive_file_id' => $fileId,
            'drive_url' => $file['webViewLink'] ?? $this->buildGoogleDriveFileUrl($fileId),
            'drive_download_url' => $file['webContentLink'] ?? null,
            'storage_provider' => 'google_drive',
        ];
    }

    private function buildGoogleDriveFolderUrl(string $folderId): string
    {
        return 'https://drive.google.com/drive/folders/' . rawurlencode($folderId);
    }

    private function buildGoogleDriveFileUrl(string $fileId): string
    {
        return 'https://drive.google.com/file/d/' . rawurlencode($fileId) . '/view';
    }

    private function makeUniqueLocalPath(string $directory, string $filename): string
    {
        $candidate = $directory . DIRECTORY_SEPARATOR . $filename;
        if (!File::exists($candidate)) {
            return $candidate;
        }

        $name = pathinfo($filename, PATHINFO_FILENAME);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $suffix = 1;

        do {
            $next = $name . ' (' . $suffix . ')';
            if ($extension !== '') {
                $next .= '.' . $extension;
            }

            $candidate = $directory . DIRECTORY_SEPARATOR . $next;
            $suffix++;
        } while (File::exists($candidate));

        return $candidate;
    }

    private function escapeGoogleDriveQueryValue(string $value): string
    {
        return str_replace(['\\', '\''], ['\\\\', '\\\''], $value);
    }

    private function classifyAsset($name) {
        $name = strtolower($name);
        if (str_contains($name, 'signature')) return 'signature';
        if (
            str_contains($name, 'authorpicture') ||
            str_contains($name, 'author_picture') ||
            str_contains($name, 'author-pic') ||
            str_contains($name, 'authorpic') ||
            str_contains($name, 'profilepicture') ||
            str_contains($name, 'profile_picture') ||
            str_contains($name, 'avatar') ||
            (str_contains($name, 'author') && (str_contains($name, 'picture') || str_contains($name, 'pic') || str_contains($name, 'profile')))
        ) return 'author_picture';
        if (str_contains($name, 'logo')) return 'logo';
        return 'thumbnail';
    }

    private function normalizeManifestPath(string $rootPath, string $fullPath): string
    {
        $rootPath = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rootPath), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $fullPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fullPath);

        if (str_starts_with($fullPath, $rootPath)) {
            $fullPath = substr($fullPath, strlen($rootPath));
        }

        return str_replace(DIRECTORY_SEPARATOR, '/', ltrim($fullPath, DIRECTORY_SEPARATOR));
    }

    private function resolveJobAssetPath(string $jobId, string $path): ?string
    {
        $jobRoot = $this->storagePath . DIRECTORY_SEPARATOR . $jobId . DIRECTORY_SEPARATOR . 'work';
        
        $cleanPath = str_replace(["\0", '../', './', '..\\', '.\\'], '', $path);
        $cleanPath = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $cleanPath), DIRECTORY_SEPARATOR);
        
        if ($cleanPath === '') return null;

        $fullPath = $jobRoot . DIRECTORY_SEPARATOR . $cleanPath;
        if (!file_exists($fullPath)) {
            Log::warning("File does not exist: {$fullPath}");
            return null;
        }

        // Basic security check: ensure the resulting path is still inside the jobRoot
        $fullPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fullPath);
        $jobRoot = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $jobRoot);
        
        if (!str_starts_with($fullPath, $jobRoot)) {
            Log::warning("Security block: {$fullPath} is not inside {$jobRoot}");
            return null;
        }

        return $fullPath;
    }

    private function resolveJobManagedPath(string $jobId, ?string $path): ?string
    {
        if (!is_string($path) || trim($path) === '') {
            return null;
        }

        $jobRoot = realpath($this->storagePath . DIRECTORY_SEPARATOR . $jobId);
        if ($jobRoot === false) {
            return null;
        }

        $resolvedPath = realpath($path);
        if ($resolvedPath === false) {
            return null;
        }

        $jobRootPrefix = rtrim($jobRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if ($resolvedPath !== $jobRoot && !str_starts_with($resolvedPath, $jobRootPrefix)) {
            return null;
        }

        return $resolvedPath;
    }

    private function resolveJobDownloadTarget(object $job): ?array
    {
        $jobId = (string) $job->job_id;
        $bundle = $job->bundle;
        if (is_array($bundle)) {
            $bundlePath = $this->resolveJobManagedPath($jobId, $bundle['path'] ?? null);
            if ($bundlePath) {
                return [
                    'name' => $bundle['name'] ?? basename($bundlePath),
                    'path' => $bundlePath,
                ];
            }
        }

        $outputs = array_values(array_filter($job->outputs ?: [], 'is_array'));
        if (count($outputs) === 1) {
            $outputPath = $this->resolveJobManagedPath($jobId, $outputs[0]['path'] ?? null);
            if ($outputPath) {
                return [
                    'name' => $outputs[0]['name'] ?? basename($outputPath),
                    'path' => $outputPath,
                ];
            }
        }

        if (count($outputs) > 1) {
            $bundleName = is_array($bundle) && !empty($bundle['name']) ? (string) $bundle['name'] : 'rebranded_pack.zip';
            $generatedBundle = $this->buildOutputBundle($jobId, $outputs, $bundleName);
            if ($generatedBundle) {
                $this->updateJob($jobId, ['bundle' => $generatedBundle]);

                return [
                    'name' => $generatedBundle['name'],
                    'path' => $generatedBundle['path'],
                ];
            }
        }

        return null;
    }

    private function buildOutputBundle(string $jobId, array $outputs, string $bundleName): ?array
    {
        $bundleName = trim($bundleName) !== '' ? trim($bundleName) : 'rebranded_pack.zip';
        if (!str_ends_with(strtolower($bundleName), '.zip')) {
            $bundleName .= '.zip';
        }

        $bundlePath = $this->storagePath . DIRECTORY_SEPARATOR . $jobId . DIRECTORY_SEPARATOR . $bundleName;
        $zip = new ZipArchive;
        if ($zip->open($bundlePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return null;
        }

        $filesAdded = 0;
        foreach ($outputs as $output) {
            $outputPath = $this->resolveJobManagedPath($jobId, $output['path'] ?? null);
            if (!$outputPath) {
                continue;
            }

            $zip->addFile($outputPath, $output['name'] ?? basename($outputPath));
            $filesAdded++;
        }

        $zip->close();

        if ($filesAdded === 0 || !file_exists($bundlePath)) {
            if (file_exists($bundlePath)) {
                @unlink($bundlePath);
            }

            return null;
        }

        return [
            'name' => basename($bundlePath),
            'path' => $bundlePath,
            'size' => filesize($bundlePath),
        ];
    }

    private function queueJobCleanupIfRequested(Request $request, string $jobId): void
    {
        if (!$request->boolean('cleanup')) {
            return;
        }

        app()->terminating(function () use ($jobId) {
            $this->deleteJobData($jobId);
        });
    }

    private function deleteJobData(string $jobId): void
    {
        $this->jobCleanup->deleteJobData($jobId);
    }

    private function buildAuthorReplacementMap(array $authors, string $newAuthor): array
    {
        $newAuthor = trim($newAuthor);
        if ($newAuthor === '') {
            return [];
        }

        $oldAuthors = $this->uniqueValues($authors);
        usort($oldAuthors, fn ($left, $right) => strlen((string) $right) <=> strlen((string) $left));

        $replacements = [];
        foreach ($oldAuthors as $oldAuthor) {
            $oldAuthor = trim((string) $oldAuthor);
            if ($oldAuthor === '' || strtolower($oldAuthor) === strtolower($newAuthor)) {
                continue;
            }

            $replacements[$oldAuthor] = $newAuthor;
        }

        return $replacements;
    }

    private function shouldRewriteMetadataFile(\SplFileInfo $file): bool
    {
        $name = strtolower($file->getFilename());
        $ext = strtolower($file->getExtension());

        return $name === 'data' || in_array($ext, ['plist', 'archive', 'json', 'txt', 'xml', 'md', 'rtf'], true);
    }

    private function rewriteMetadataFile(string $filePath, array $replacements): void
    {
        if ($replacements === [] || !file_exists($filePath)) {
            return;
        }

        $content = @file_get_contents($filePath);
        if ($content === false) {
            return;
        }

        if ($this->looksLikeBinaryPlist($content) && $this->rewriteBinaryPlistWithPython($filePath, $replacements)) {
            return;
        }

        $updatedContent = $this->replaceAuthorStrings($content, $replacements);
        if ($updatedContent !== $content) {
            $parentDir = dirname($filePath);
            if (!is_dir($parentDir)) {
                throw new \RuntimeException('The temporary package folder disappeared while rewriting ' . basename($filePath));
            }

            file_put_contents($filePath, $updatedContent);
        }
    }

    private function replaceAuthorStrings(string $content, array $replacements): string
    {
        foreach ($replacements as $oldAuthor => $newAuthor) {
            $content = str_ireplace($oldAuthor, $newAuthor, $content);
        }

        return $content;
    }

    private function rewriteBinaryPlistWithPython(string $filePath, array $replacements): bool
    {
        $payload = json_encode($replacements, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($payload) || $payload === '') {
            return false;
        }

        $script = <<<'PY'
import json
import plistlib
import sys

path = sys.argv[1]
replacements = json.loads(sys.argv[2])
normalized = {str(key).lower(): str(value) for key, value in replacements.items()}

with open(path, "rb") as handle:
    payload = plistlib.load(handle)

def walk(node):
    if isinstance(node, dict):
        for key, value in list(node.items()):
            node[key] = walk(value)
        return node
    if isinstance(node, list):
        for index, value in enumerate(node):
            node[index] = walk(value)
        return node
    if isinstance(node, str):
        return normalized.get(node.lower(), node)
    return node

payload = walk(payload)

with open(path, "wb") as handle:
    plistlib.dump(payload, handle, fmt=plistlib.FMT_BINARY, sort_keys=False)
PY;

        $commands = [
            ['python', '-c', $script, $filePath, $payload],
            ['py', '-3', '-c', $script, $filePath, $payload],
        ];

        foreach ($commands as $command) {
            $process = new Process($command, base_path(), null, null, 20);
            $process->run();

            if ($process->isSuccessful()) {
                return true;
            }
        }

        Log::warning('Binary plist rewrite skipped because Python execution failed for ' . $filePath);
        return false;
    }

    private function replaceBrandImagesInDirectory(string $workDir, ?string $authorPicturePath, ?string $signaturePath): void
    {
        if (!$authorPicturePath && !$signaturePath) {
            return;
        }

        foreach (File::allFiles($workDir) as $file) {
            $ext = strtolower($file->getExtension());

            if (in_array($ext, ['brush', 'brushset'], true)) {
                $this->replaceImagesInZipArchive($file->getRealPath(), $authorPicturePath, $signaturePath);
            }

            if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
                continue;
            }

            $category = $this->classifyAsset($file->getFilename());
            if ($category === 'author_picture' && $authorPicturePath) {
                $this->replaceImageFile($authorPicturePath, $file->getRealPath());
            }

            if ($category === 'signature' && $signaturePath) {
                $this->replaceImageFile($signaturePath, $file->getRealPath());
            }
        }
    }

    private function replaceImagesInZipArchive(string $zipPath, ?string $authorPicturePath, ?string $signaturePath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return;
        }

        $modified = false;
        $filesToReplace = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
                continue;
            }

            $category = $this->classifyAsset(basename($filename));
            if ($category === 'author_picture' && $authorPicturePath) {
                $filesToReplace[$filename] = $authorPicturePath;
            } elseif ($category === 'signature' && $signaturePath) {
                $filesToReplace[$filename] = $signaturePath;
            }
        }

        foreach ($filesToReplace as $zipFilename => $localPath) {
            $zip->addFile($localPath, $zipFilename);
            $modified = true;
        }

        if ($modified) {
            $zip->close();
        } else {
            $zip->close();
        }
    }

    private function replaceImageFile(string $sourcePath, string $targetPath): bool
    {
        $targetExt = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
        if ($targetExt === 'png' || $targetExt === '') {
            return (bool) File::copy($sourcePath, $targetPath);
        }

        if (!function_exists('imagecreatefromstring')) {
            Log::warning("Image conversion skipped for {$targetPath}: GD is not available.");
            return false;
        }

        $sourceContent = @file_get_contents($sourcePath);
        if ($sourceContent === false) {
            return false;
        }

        $image = @imagecreatefromstring($sourceContent);
        if ($image === false) {
            return false;
        }

        $saved = match ($targetExt) {
            'jpg', 'jpeg' => imagejpeg($image, $targetPath, 90),
            'webp' => function_exists('imagewebp') ? imagewebp($image, $targetPath, 90) : false,
            default => false,
        };

        imagedestroy($image);
        return (bool) $saved;
    }

    private function renameAuthorTaggedPaths(string $workDir, array $replacements): void
    {
        if ($replacements === []) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($workDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $currentName = $item->getFilename();
            $updatedName = $this->replaceAuthorTokensInPathSegment($currentName, $replacements);
            if ($updatedName === '' || $updatedName === $currentName) {
                continue;
            }

            $targetPath = $item->getPath() . DIRECTORY_SEPARATOR . $updatedName;
            if (file_exists($targetPath)) {
                continue;
            }

            @rename($item->getPathname(), $targetPath);
        }
    }

    private function replaceAuthorTokensInPathSegment(string $value, array $replacements): string
    {
        foreach ($replacements as $oldAuthor => $newAuthor) {
            $pattern = $this->buildAuthorPathPattern($oldAuthor);
            if ($pattern === null) {
                continue;
            }

            $value = preg_replace($pattern, $newAuthor, $value) ?? $value;
        }

        return $value;
    }

    private function buildAuthorPathPattern(string $author): ?string
    {
        $parts = array_values(array_filter(preg_split('/[\s._-]+/', trim($author)) ?: []));
        if ($parts === []) {
            return null;
        }

        return '/' . implode('[\s._-]+', array_map(fn ($part) => preg_quote($part, '/'), $parts)) . '/i';
    }

    private function extractAuthorCandidates(string $text): array
    {
        if ($text === '') {
            return [];
        }

        $candidates = [];
        $searchText = preg_replace('/[\r\n\t\0]+/', ' ', $text) ?? $text;
        $keywordPattern = '(?:author(?:name)?|made\s*by|madeby|created\s*by|createdby|creator|copyright)';

        $patterns = [
            '/\b' . $keywordPattern . '\b\s*[:=\-]?\s*["\']?(.{2,40}?)(?=\s+(?:' . $keywordPattern . ')\b|$|[\/\\\\]|\.brushset\b|\.brush\b|\.procreate\b|\.swatches\b|\.zip\b|[,;\]\)])/i',
            '/\bby[\s_-]+(.{2,40}?)(?=$|[\/\\\\]|\.brushset\b|\.brush\b|\.procreate\b|\.swatches\b|\.zip\b|[,;\]\)]|\s{2,})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $searchText, $matches)) {
                foreach ($matches[1] as $match) {
                    $candidate = $this->normalizeAuthorCandidate($match);
                    if ($candidate !== null) {
                        $candidates[] = $candidate;
                    }
                }
            }
        }

        if (preg_match_all('/[\x20-\x7E]{3,80}/', $searchText, $matches)) {
            $expectValue = false;
            foreach ($matches[0] as $match) {
                $trimmed = trim($match);
                $collapsedKey = strtolower(preg_replace('/[^a-z]/i', '', $trimmed) ?? '');

                if (in_array($collapsedKey, ['author', 'authorname', 'madeby', 'createdby', 'creator', 'copyright'], true)) {
                    $expectValue = true;
                    continue;
                }

                if ($expectValue) {
                    $candidate = $this->normalizeAuthorCandidate($trimmed);
                    if ($candidate !== null) {
                        $candidates[] = $candidate;
                    }
                    $expectValue = false;
                }

                foreach ($patterns as $pattern) {
                    if (preg_match_all($pattern, $trimmed, $stringMatches)) {
                        foreach ($stringMatches[1] as $stringMatch) {
                            $candidate = $this->normalizeAuthorCandidate($stringMatch);
                            if ($candidate !== null) {
                                $candidates[] = $candidate;
                            }
                        }
                    }
                }
            }
        }

        return $this->uniqueValues($candidates);
    }

    private function extractBinaryPlistAuthorCandidates(string $content): array
    {
        if (!$this->looksLikeBinaryPlist($content)) {
            return [];
        }

        try {
            $parsed = $this->parseBinaryPlist($content);
        } catch (\Throwable $e) {
            Log::debug('Binary plist parse failed during author detection: ' . $e->getMessage());
            return [];
        }

        $objects = is_array($parsed) && isset($parsed['$objects']) && is_array($parsed['$objects'])
            ? $parsed['$objects']
            : null;

        return $this->collectBinaryPlistAuthorCandidates($parsed, $objects);
    }

    private function looksLikeBinaryPlist(string $content): bool
    {
        return str_starts_with($content, 'bplist');
    }

    private function collectBinaryPlistAuthorCandidates(mixed $node, ?array $objects = null, int $depth = 0): array
    {
        if ($depth > 24) {
            return [];
        }

        $candidates = [];
        if (!is_array($node)) {
            return $candidates;
        }

        foreach ($node as $key => $value) {
            if (is_string($key) && preg_match('/^(authorName|author|madeBy|createdBy|creator|copyright)$/i', $key)) {
                foreach ($this->flattenBinaryPlistValueToStrings($value, $objects, $depth + 1) as $stringValue) {
                    $candidate = $this->normalizeAuthorCandidate($stringValue);
                    if ($candidate !== null) {
                        $candidates[] = $candidate;
                    }
                }
            }

            $candidates = array_merge($candidates, $this->collectBinaryPlistAuthorCandidates($value, $objects, $depth + 1));
        }

        return $this->uniqueValues($candidates);
    }

    private function flattenBinaryPlistValueToStrings(mixed $value, ?array $objects = null, int $depth = 0): array
    {
        if ($depth > 24) {
            return [];
        }

        if (is_string($value)) {
            return [$value];
        }

        if (!is_array($value)) {
            return [];
        }

        if (array_key_exists('__uid', $value) && is_array($objects)) {
            $uid = $value['__uid'];
            if (is_int($uid) && array_key_exists($uid, $objects)) {
                return $this->flattenBinaryPlistValueToStrings($objects[$uid], $objects, $depth + 1);
            }

            return [];
        }

        $strings = [];
        foreach ($value as $nested) {
            $strings = array_merge($strings, $this->flattenBinaryPlistValueToStrings($nested, $objects, $depth + 1));
        }

        return $this->uniqueValues($strings);
    }

    private function parseBinaryPlist(string $content): mixed
    {
        if (strlen($content) < 40 || !$this->looksLikeBinaryPlist($content)) {
            throw new \RuntimeException('Invalid binary plist payload.');
        }

        $trailer = substr($content, -32);
        $offsetSize = ord($trailer[6]);
        $objectRefSize = ord($trailer[7]);
        $objectCount = $this->binaryPlistUnsignedInt(substr($trailer, 8, 8));
        $topObject = $this->binaryPlistUnsignedInt(substr($trailer, 16, 8));
        $offsetTableOffset = $this->binaryPlistUnsignedInt(substr($trailer, 24, 8));

        $offsets = [];
        for ($i = 0; $i < $objectCount; $i++) {
            $offsetPosition = $offsetTableOffset + ($i * $offsetSize);
            $offsets[$i] = $this->binaryPlistUnsignedInt(substr($content, $offsetPosition, $offsetSize));
        }

        $cache = [];
        $building = [];

        $parseObject = function (int $objectIndex) use (&$parseObject, &$cache, &$building, $content, $offsets, $objectRefSize) {
            if (array_key_exists($objectIndex, $cache)) {
                return $cache[$objectIndex];
            }

            if (isset($building[$objectIndex])) {
                return ['__plist_ref' => $objectIndex];
            }

            if (!array_key_exists($objectIndex, $offsets)) {
                throw new \RuntimeException("Binary plist object index {$objectIndex} is missing.");
            }

            $building[$objectIndex] = true;
            $offset = $offsets[$objectIndex];
            $marker = ord($content[$offset]);
            $type = $marker >> 4;
            $info = $marker & 0x0F;

            switch ($type) {
                case 0x0:
                    $value = match ($info) {
                        0x0 => null,
                        0x8 => false,
                        0x9 => true,
                        default => null,
                    };
                    break;

                case 0x1:
                    $byteCount = 1 << $info;
                    $value = $this->binaryPlistUnsignedInt(substr($content, $offset + 1, $byteCount));
                    break;

                case 0x2:
                    $byteCount = 1 << $info;
                    $raw = substr($content, $offset + 1, $byteCount);
                    $value = match ($byteCount) {
                        4 => unpack('G', $raw)[1],
                        8 => unpack('E', $raw)[1],
                        default => null,
                    };
                    break;

                case 0x3:
                    $raw = substr($content, $offset + 1, 8);
                    $seconds = unpack('E', $raw)[1];
                    $value = 978307200 + $seconds;
                    break;

                case 0x5:
                    [$length, $dataOffset] = $this->binaryPlistReadLength($content, $offset, $info);
                    $value = substr($content, $dataOffset, $length);
                    break;

                case 0x6:
                    [$length, $dataOffset] = $this->binaryPlistReadLength($content, $offset, $info);
                    $raw = substr($content, $dataOffset, $length * 2);
                    $converted = @iconv('UTF-16BE', 'UTF-8//IGNORE', $raw);
                    $value = $converted === false ? '' : $converted;
                    break;

                case 0x8:
                    $byteCount = $info + 1;
                    $value = ['__uid' => $this->binaryPlistUnsignedInt(substr($content, $offset + 1, $byteCount))];
                    break;

                case 0xA:
                    [$length, $dataOffset] = $this->binaryPlistReadLength($content, $offset, $info);
                    $value = [];
                    for ($i = 0; $i < $length; $i++) {
                        $refOffset = $dataOffset + ($i * $objectRefSize);
                        $refIndex = $this->binaryPlistUnsignedInt(substr($content, $refOffset, $objectRefSize));
                        $value[] = $parseObject($refIndex);
                    }
                    break;

                case 0xD:
                    [$length, $dataOffset] = $this->binaryPlistReadLength($content, $offset, $info);
                    $value = [];
                    $keysOffset = $dataOffset;
                    $valuesOffset = $dataOffset + ($length * $objectRefSize);

                    for ($i = 0; $i < $length; $i++) {
                        $keyIndex = $this->binaryPlistUnsignedInt(substr($content, $keysOffset + ($i * $objectRefSize), $objectRefSize));
                        $valueIndex = $this->binaryPlistUnsignedInt(substr($content, $valuesOffset + ($i * $objectRefSize), $objectRefSize));
                        $key = $parseObject($keyIndex);
                        $decodedValue = $parseObject($valueIndex);

                        if (is_string($key)) {
                            $value[$key] = $decodedValue;
                        } else {
                            $value[] = ['key' => $key, 'value' => $decodedValue];
                        }
                    }
                    break;

                default:
                    $value = null;
                    break;
            }

            unset($building[$objectIndex]);
            $cache[$objectIndex] = $value;

            return $value;
        };

        return $parseObject((int) $topObject);
    }

    private function binaryPlistReadLength(string $content, int $offset, int $info): array
    {
        if ($info < 0x0F) {
            return [$info, $offset + 1];
        }

        $lengthMarker = ord($content[$offset + 1]);
        $lengthType = $lengthMarker >> 4;
        $lengthInfo = $lengthMarker & 0x0F;

        if ($lengthType !== 0x1) {
            throw new \RuntimeException('Binary plist length marker is invalid.');
        }

        $byteCount = 1 << $lengthInfo;
        $length = $this->binaryPlistUnsignedInt(substr($content, $offset + 2, $byteCount));

        return [$length, $offset + 2 + $byteCount];
    }

    private function binaryPlistUnsignedInt(string $bytes): int
    {
        $value = 0;
        $length = strlen($bytes);

        for ($i = 0; $i < $length; $i++) {
            $value = ($value << 8) | ord($bytes[$i]);
        }

        return $value;
    }

    private function normalizeAuthorCandidate(string $candidate): ?string
    {
        $candidate = $this->sanitizeUtf8String($candidate);
        $candidate = trim($candidate, " \t\n\r\0\x0B\"'`.,:;|[](){}<>");
        $candidate = preg_replace('/\.(brushset|brush|procreate|swatches|zip|plist|png|jpe?g|webp)$/i', '', $candidate) ?? $candidate;
        $candidate = preg_replace('/[_]+/', ' ', $candidate) ?? $candidate;
        $candidate = preg_replace('/\s+/', ' ', $candidate) ?? $candidate;
        $candidate = trim($candidate, " -_");

        if ($candidate === '' || strlen($candidate) < 3 || strlen($candidate) > 40) {
            return null;
        }

        if (preg_match('/^(?:author|authorpicture|signature|signaturepicture|thumbnail|quicklook|shape|grain|data|copyright|creator|madeby|createdby|brushset|brush|procreate|swatches|png|jpg|jpeg|webp)$/i', $candidate)) {
            return null;
        }

        if (preg_match('/^(?:com|http|https|apple)$/i', $candidate) || str_contains($candidate, '://') || str_contains($candidate, '\\')) {
            return null;
        }

        if (preg_match('/^[0-9 ._-]+$/', $candidate)) {
            return null;
        }

        return $candidate;
    }

    private function uniqueValues(array $values): array
    {
        $unique = [];
        $seen = [];

        foreach ($values as $value) {
            $sanitizedValue = trim($this->sanitizeUtf8String((string) $value));
            $normalized = strtolower($sanitizedValue);
            if ($normalized === '' || isset($seen[$normalized])) {
                continue;
            }

            $seen[$normalized] = true;
            $unique[] = $sanitizedValue;
        }

        return $unique;
    }

    private function sanitizeUtf8String(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $value = str_replace("\0", '', $value);

        if (preg_match('//u', $value)) {
            return $value;
        }

        $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
        if ($converted !== false && $converted !== '') {
            return str_replace("\0", '', $converted);
        }

        $encoding = function_exists('mb_detect_encoding')
            ? mb_detect_encoding($value, ['UTF-8', 'Windows-1252', 'ISO-8859-1', 'ASCII'], true)
            : false;

        if ($encoding && function_exists('mb_convert_encoding')) {
            $converted = @mb_convert_encoding($value, 'UTF-8', $encoding);
            if (is_string($converted) && $converted !== '') {
                return str_replace("\0", '', $converted);
            }
        }

        return preg_replace('/[^\x20-\x7E]/', '', $value) ?? '';
    }

    private function sanitizeForJson(mixed $value): mixed
    {
        if (is_array($value)) {
            $sanitized = [];
            foreach ($value as $key => $item) {
                $safeKey = is_string($key) ? $this->sanitizeUtf8String($key) : $key;
                $sanitized[$safeKey] = $this->sanitizeForJson($item);
            }

            return $sanitized;
        }

        if (is_object($value)) {
            return $this->sanitizeForJson((array) $value);
        }

        if (is_string($value)) {
            return $this->sanitizeUtf8String($value);
        }

        return $value;
    }

    private function jsonResponseSafe(mixed $payload, int $status = 200)
    {
        return response()->json(
            $this->sanitizeForJson($payload),
            $status,
            [],
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
    }

    private function acquireJobProcessLock(string $jobId, string $process): mixed
    {
        $lockPath = $this->storagePath . '/' . $jobId . '/.' . preg_replace('/[^a-z0-9_-]+/i', '_', $process) . '.lock';
        $lockDir = dirname($lockPath);
        if (!is_dir($lockDir)) {
            @mkdir($lockDir, 0755, true);
        }

        $handle = @fopen($lockPath, 'c+');
        if ($handle === false) {
            throw new \RuntimeException('Unable to create a process lock file.');
        }

        if (!@flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            throw new \RuntimeException('Job lock is already held.');
        }

        ftruncate($handle, 0);
        fwrite($handle, json_encode([
            'process' => $process,
            'locked_at' => now()->toIso8601String(),
            'pid' => function_exists('getmypid') ? getmypid() : null,
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));

        return $handle;
    }

    private function releaseJobProcessLock(mixed $handle): void
    {
        if (!is_resource($handle)) {
            return;
        }

        @flock($handle, LOCK_UN);
        @fclose($handle);
    }

    private function buildStudioFailurePayload(string $phase, \Throwable $e, array $context = []): array
    {
        $rawMessage = trim($this->sanitizeUtf8String($e->getMessage()));
        $currentFileName = trim($this->sanitizeUtf8String((string) ($context['current_file_name'] ?? '')));
        $currentFileExtension = $currentFileName !== '' ? strtolower(pathinfo($currentFileName, PATHINFO_EXTENSION)) : '';

        $reasonCode = 'processing_error';
        $detail = $phase === 'scan'
            ? 'Branding scan failed.'
            : 'Repackaging failed.';

        if (str_contains($rawMessage, 'Malformed UTF-8')) {
            $reasonCode = 'invalid_metadata_encoding';
            $detail = 'A file inside this package contains metadata or text that is not valid UTF-8.';
        } elseif (str_contains(strtolower($rawMessage), 'could not be extracted') || str_contains(strtolower($rawMessage), 'zip')) {
            $reasonCode = 'archive_extract_failed';
            $detail = 'The uploaded archive could not be unpacked. The file may be corrupted or use an unsupported format.';
        } elseif (str_contains(strtolower($rawMessage), 'temporary package folder disappeared') || str_contains(strtolower($rawMessage), 'failed to open stream: no such file or directory')) {
            $reasonCode = 'workspace_missing';
            $detail = 'The temporary workspace disappeared while repackaging this file.';
        } elseif (str_contains(strtolower($rawMessage), 'source file is missing') || str_contains(strtolower($rawMessage), 'file not found')) {
            $reasonCode = 'missing_source_file';
            $detail = 'A required source file was missing while processing the package.';
        } elseif (str_contains(strtolower($rawMessage), '10gb limit')) {
            $reasonCode = 'content_limit_exceeded';
            $detail = 'The expanded package content exceeds the 10 GB processing limit.';
        } elseif (str_contains(strtolower($rawMessage), 'memory')) {
            $reasonCode = 'memory_limit';
            $detail = 'The server ran out of memory while processing this package.';
        }

        if ($currentFileName !== '') {
            $detail .= " Last file checked: {$currentFileName}.";
        }

        return $this->sanitizeForJson([
            'error' => $detail,
            'detail' => $detail,
            'phase' => $phase,
            'reason_code' => $reasonCode,
            'current_file_name' => $currentFileName ?: null,
            'current_file_extension' => $currentFileExtension ?: null,
            'technical_detail' => $rawMessage !== '' ? $rawMessage : null,
        ]);
    }

    private function buildOutputFilename($original, $storeName) {
        $ext = pathinfo($original, PATHINFO_EXTENSION);
        $name = pathinfo($original, PATHINFO_FILENAME);
        $clean = preg_replace('/\s+by\s+.*$/i', '', $name);
        $clean = preg_replace('/\s*[-\|]\s*[a-zA-Z].*$/i', '', $clean);
        return trim($clean) . " by " . $storeName . "." . $ext;
    }

    private function zipDirectory($dir, $zipPath) {
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir), \RecursiveIteratorIterator::LEAVES_ONLY);
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relPath = substr($filePath, strlen($dir) + 1);
                $zip->addFile($filePath, $relPath);
            }
        }
        $zip->close();
    }

    private function getJob($jobId) {
        $path = $this->storagePath . '/' . $jobId . '/job.json';
        if (!file_exists($path)) return null;
        $data = json_decode((string) file_get_contents($path), true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
        return $data ? (object)$data : null;
    }

    private function updateJob($jobId, $data) {
        $path = $this->storagePath . '/' . $jobId . '/job.json';
        if (!file_exists(dirname($path))) @mkdir(dirname($path), 0755, true);

        // Atomic write with retry
        for ($i = 0; $i < 3; $i++) {
            try {
                $now = now();
                $job = (array)($this->getJob($jobId) ?? []);
                $data['created_at'] = $job['created_at'] ?? $data['created_at'] ?? $now->toIso8601String();
                $data['updated_at'] = $now->toIso8601String();
                $data['last_activity_at'] = $data['last_activity_at'] ?? $now->toIso8601String();
                $data['expires_at'] = $data['expires_at'] ?? $now->copy()->addHours(max(1, (int) config('studio.job_retention_hours', 24)))->toIso8601String();
                $job = array_merge($job, $data);
                $job = $this->sanitizeForJson($job);
                $json = json_encode($job, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                if ($json === false) throw new \Exception("JSON encode failed");
                
                $tempPath = $path . '.tmp';
                if (file_put_contents($tempPath, $json, LOCK_EX) !== false) {
                    if (rename($tempPath, $path)) {
                        return (object)$job;
                    }
                }
            } catch (\Exception $e) {
                usleep(10000); // Wait 10ms
            }
        }
        
        return (object)(array_merge((array)($this->getJob($jobId) ?? []), $data));
    }

    private function maybePurgeExpiredJobs(): void
    {
        $cacheKey = 'studio_jobs_cleanup:last_run';

        if (!Cache::add($cacheKey, time(), now()->addMinutes(15))) {
            return;
        }

        try {
            $this->jobCleanup->purgeExpiredJobs();
        } catch (\Throwable $e) {
            Cache::forget($cacheKey);
            Log::warning('Opportunistic studio cleanup failed: ' . $e->getMessage());
        }
    }
}
