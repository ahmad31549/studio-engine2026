<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
// use App\Models\StudioJob; // Removed to use file-based storage
use Symfony\Component\Process\Process;
use ZipArchive;

class StudioController extends Controller
{
    private $storagePath;
    private $limitMB = 20480; // 20GB Working Limit

    public function __construct()
    {
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
        $usedBytes = $this->getUsedStorageBytes();
        $limitBytes = $this->limitMB * 1024 * 1024;
        $remainingBytes = max(0, $limitBytes - $usedBytes);
        $percent = ($usedBytes / $limitBytes) * 100;
        
        $status = 'normal';
        if ($percent >= 100) $status = 'full';
        elseif ($percent >= 80) $status = 'warning';

        return response()->json([
            'total_mb' => $this->limitMB,
            'used_bytes' => $usedBytes,
            'remaining_bytes' => $remainingBytes,
            'percent' => round($percent, 2),
            'status' => $status
        ]);
    }

    /**
     * Handle direct file uploads
     */
    public function upload(Request $request)
    {
        if (!$this->checkStorageLimit()) {
            return response()->json(['error' => 'Storage is full. Please click Clear Memory before starting a new task.'], 403);
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

        $this->updateJob($jobId, [
            'job_id' => $jobId,
            'user_id' => Auth::id(),
            'status' => 'uploaded',
            'files' => $savedFiles,
            'progress' => 0
        ]);

        $this->purgeOldJobs();

        return response()->json([
            'job_id' => $jobId,
            'status' => 'uploaded'
        ]);
    }

    public function uploadChunk(Request $request)
    {
        Log::info("UploadChunk Start: " . $request->input('file_name') . " (Chunk " . $request->input('chunk_index') . ")");
        if (!$this->checkStorageLimit()) {
            return response()->json(['error' => 'Storage is full. Please click Clear Memory before starting a new task.'], 403);
        }

        $jobId = $request->input('job_id');
        $fileName = $request->input('file_name');
        $chunkIndex = (int) $request->input('chunk_index');
        $totalChunks = (int) $request->input('total_chunks');
        $file = $request->file('chunk');

        if (!$jobId || !$fileName || !$file) {
            return response()->json(['error' => 'Missing data'], 400);
        }

        $jobDir = $this->storagePath . '/' . $jobId . '/input';
        if (!file_exists($jobDir)) mkdir($jobDir, 0755, true);

        $tempPath = $jobDir . '/' . $fileName . '.part';
        
        // Append chunk to the file
        $out = fopen($tempPath, $chunkIndex === 0 ? 'wb' : 'ab');
        fwrite($out, file_get_contents($file->getRealPath()));
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

        $this->updateJob($jobId, [
            'job_id' => $jobId,
            'user_id' => Auth::id(),
            'status' => 'uploaded',
            'files' => $savedFiles,
            'progress' => 0
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

        return response()->json(['status' => 'uploaded', 'job_id' => $jobId]);
    }

    /**
     * Purge jobs older than 1 hour
     */
    private function purgeOldJobs()
    {
        $folders = File::directories($this->storagePath);
        $oneHourAgo = time() - 3600;

        foreach ($folders as $folder) {
            if (filemtime($folder) < $oneHourAgo) {
                File::deleteDirectory($folder);
                // No need to delete from DB anymore, file is in the folder being deleted
                // $jobId = basename($folder);
                // StudioJob::where('job_id', $jobId)->delete();
            }
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
        $apiKey = env('GOOGLE_DRIVE_API_KEY');
        if (!$apiKey && !$token) {
            throw new \Exception("Google Drive API is not configured (Missing API Key or Token).");
        }

        try {
            $client = Http::timeout(300);
            if ($token) {
                $client->withToken($token);
                $metaUrl = "https://www.googleapis.com/drive/v3/files/{$fileId}";
                $contentUrl = "https://www.googleapis.com/drive/v3/files/{$fileId}?alt=media";
            } else {
                $metaUrl = "https://www.googleapis.com/drive/v3/files/{$fileId}?key={$apiKey}";
                $contentUrl = "https://www.googleapis.com/drive/v3/files/{$fileId}?alt=media&key={$apiKey}";
            }

            // First get Metadata
            $metaResponse = $client->get($metaUrl);
            if (!$metaResponse->successful()) {
                throw new \Exception("Failed to fetch file metadata from Google API: " . $metaResponse->body());
            }
            $meta = $metaResponse->json();
            $filename = $meta['name'] ?? "google_drive_file.zip";
            
            $filePath = $jobDir . '/' . $filename;
            
            // Stream content to sink
            $response = $client->withOptions([
                'sink' => $filePath
            ])->get($contentUrl);

            if (!$response->successful()) {
                throw new \Exception("Google Drive API download failed. Status: " . $response->status());
            }

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
            throw $e;
        }
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
        if (!$job) return response()->json(['error' => 'Job not found'], 404);

        $extractRoot = $this->storagePath . '/' . $jobId . '/work';
        Log::info("Scan starting. Extraction root: {$extractRoot}");
        if (file_exists($extractRoot)) {
            File::deleteDirectory($extractRoot);
        }
        mkdir($extractRoot, 0755, true);

        $this->updateJob($jobId, [
            'status' => 'scanning',
            'progress' => 2,
            'progress_message' => 'Initializing intelligent scan...'
        ]);

        $manifest = ['assets' => [], 'source_files' => [], 'detected_authors' => [], 'rename_candidates' => []];
        $allAuthorTags = [];
        $allRenameCandidates = [];
        $totalExtractedSize = 0;
        $toolExts = ['brushset', 'brush', 'procreate', 'swatches', 'usdz'];
        $imgExts = ['png', 'jpg', 'jpeg', 'webp'];

        $sourceFiles = $job->files ?: [];
        $totalFiles = count($sourceFiles);

        foreach ($sourceFiles as $index => $source) {
            $currentProgress = 5 + (int)(($index / ($totalFiles ?: 1)) * 90);
            $this->updateJob($jobId, [
                'progress' => $currentProgress,
                'progress_message' => "Scanning " . ($index + 1) . "/$totalFiles: " . $source['name']
            ]);

            $subDir = $extractRoot . '/' . pathinfo($source['name'], PATHINFO_FILENAME);
            if (!file_exists($subDir)) mkdir($subDir, 0755, true);

            $isZip = false;
            $zip = new ZipArchive;
            if ($zip->open($source['path']) === TRUE) {
                $zip->extractTo($subDir);
                $zip->close();
                $isZip = true;
            } else {
                // If not a zip, copy the file itself to subDir so it can be "found" by allFiles
                copy($source['path'], $subDir . '/' . $source['name']);
            }

            if (!File::exists($subDir)) {
                Log::warning("Scanning subDir missing for job {$jobId}: {$subDir}");
                continue;
            }

            try {
                $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($subDir, \FilesystemIterator::SKIP_DOTS));
                $totalExtracted = iterator_count($iterator);
                $iterator->rewind(); 
            } catch (\Exception $e) {
                Log::error("Failed to iterate scan directory for job {$jobId}: " . $e->getMessage());
                continue;
            }
            
            $currentExtractedSize = 0;
            $lastReportedProgress = -1;
            $authorsInFile = [];
            $renameCandidatesInFile = $this->extractAuthorCandidates($source['name']);
            $renameCandidatesInFile = array_merge($renameCandidatesInFile, $this->extractAuthorCandidates(pathinfo($source['name'], PATHINFO_FILENAME)));
            $assetCount = 0;

            $counter = 0;
            foreach ($iterator as $f) {
                if ($f->isDir()) continue;
                
                // Sub-progress within this source file
                $subProgress = ($counter / ($totalExtracted ?: 1)) * (90 / ($totalFiles ?: 1));
                $newProgress = 5 + (int)(($index / ($totalFiles ?: 1)) * 90 + $subProgress);
                
                if ($newProgress > $lastReportedProgress) {
                    $this->updateJob($jobId, ['progress' => min(95, $newProgress)]);
                    $lastReportedProgress = $newProgress;
                }
                $counter++;

                $currentExtractedSize += $f->getSize();
                $realPath = $f->getRealPath();
                $name = $f->getFilename();
                $ext = strtolower($f->getExtension());
                $rel = $this->normalizeManifestPath($extractRoot, $realPath);

                $renameCandidatesInFile = array_merge($renameCandidatesInFile, $this->extractAuthorCandidates($rel));

                if (in_array($ext, $imgExts) || in_array($ext, $toolExts)) {
                    if ($isZip || $name !== $source['name']) {
                        $manifest['assets'][] = [
                            'name' => $name,
                            'rel_path' => $rel,
                            'size' => $f->getSize(),
                            'source_name' => $source['name'],
                            'category' => $this->classifyAsset($name)
                        ];
                        $assetCount++;
                    }
                }

                // DEEP SCAN for Author Metadata
                if ($name === 'Data' || $ext === 'plist' || $ext === 'archive' || in_array($ext, $toolExts)) {
                    $content = @file_get_contents($realPath);
                    if ($content) {
                        $authorsInFile = array_merge($authorsInFile, $this->extractAuthorCandidates($content));
                        $authorsInFile = array_merge($authorsInFile, $this->extractBinaryPlistAuthorCandidates($content));
                    }
                }
            }

            $totalExtractedSize += $currentExtractedSize;

            if ($totalExtractedSize > 10737418240) { // 10GB Total Limit
                return response()->json(['error' => 'Total content exceeds 10GB limit.'], 413);
            }

            $authorsInFile = $this->uniqueValues($authorsInFile);
            $renameCandidatesInFile = $this->uniqueValues(array_merge($renameCandidatesInFile, $authorsInFile));
            $allAuthorTags = array_merge($allAuthorTags, $authorsInFile);
            $allRenameCandidates = array_merge($allRenameCandidates, $renameCandidatesInFile);

            $manifest['source_files'][] = [
                'name' => $source['name'],
                'size' => $source['size'],
                'asset_count' => $assetCount,
                'author_tags' => $this->uniqueValues($authorsInFile)
            ];
        }

        $manifest['detected_authors'] = $this->uniqueValues($allAuthorTags);
        $manifest['rename_candidates'] = $this->uniqueValues($allRenameCandidates);
        
        Log::info("Job {$jobId} scan complete. Found " . count($manifest['assets']) . " assets and " . count($manifest['detected_authors']) . " author tags.");

        $this->updateJob($jobId, [
            'status' => 'scanned',
            'manifest' => $manifest,
            'progress' => 100
        ]);

        return response()->json(['status' => 'scanned', 'manifest' => $manifest]);
    }

    /**
     * Preview Asset
     */
    public function previewAsset(Request $request, $jobId)
    {
        $path = $request->query('path');
        Log::info("Preview request for job {$jobId}, path: {$path}");
        
        if (!$path) return response()->json(['error' => 'No path provided'], 400);

        $fullPath = $this->resolveJobAssetPath($jobId, $path);
        Log::info("Resolved path: " . ($fullPath ?: 'NULL'));
        
        if (!$fullPath) {
            Log::warning("Preview failed: File not found for job {$jobId} path {$path}");
            return response()->json(['error' => 'File not found'], 404);
        }

        $headers = [
            'Content-Type' => File::mimeType($fullPath) ?: 'application/octet-stream',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
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
        $job = $this->getJob($jobId);
        if (!$job) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        $this->updateJob($jobId, [
            'status' => 'processing',
            'progress' => 2,
            'progress_message' => 'Preparing rebranding engine...'
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
        
        foreach ($job->files as $index => $source) {
            Log::info("Rebranding file " . ($index+1) . " of $total: " . $source['name']);
            $currentProgress = 5 + (int)(($index / ($total ?: 1)) * 85);
            $this->updateJob($jobId, [
                'progress' => $currentProgress,
                'progress_message' => "Rebranding " . ($index+1) . "/$total: " . $source['name']
            ]);
            
            $sourceStem = pathinfo($source['name'], PATHINFO_FILENAME);
            $sourceExtractPath = $this->storagePath . '/' . $jobId . '/work/' . $sourceStem;
            
            // Critical Check: Ensure source exists before processing
            if (!File::exists($sourceExtractPath)) {
                Log::error("Missing source for rebranding: " . $sourceExtractPath);
                continue; // Skip this file if missing but don't crash others
            }

            $workDir = $this->storagePath . '/' . $jobId . '/temp_' . $index;
            File::copyDirectory($sourceExtractPath, $workDir);

            if (!File::exists($workDir)) {
                Log::error("Repackaging workDir missing for job {$jobId}: {$workDir}");
                continue;
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
                
                if ($this->shouldRewriteMetadataFile($f)) {
                    $this->rewriteMetadataFile($f->getRealPath(), $authorReplacements);
                }

                // Sub-progress during rebranding
                $subProgress = ($counter / ($totalWorkFiles ?: 1)) * (85 / ($total ?: 1));
                $newProgress = 5 + (int)(($index / ($total ?: 1)) * 85 + $subProgress);
                
                if ($newProgress > $lastRebrandProgress) {
                    $this->updateJob($jobId, ['progress' => min(94, $newProgress)]);
                    $lastRebrandProgress = $newProgress;
                }
                $counter++;
            }

            $this->replaceBrandImagesInDirectory($workDir, $authorPicturePath, $signaturePath);
            $this->renameAuthorTaggedPaths($workDir, $authorReplacements);

            $newName = $this->buildOutputFilename($source['name'], $storeName);
            $outputPath = $outputDir . '/' . $newName;
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
            'progress_message' => "Zipping final bundle..."
        ]);

        $bundle = count($rebrandedFiles) > 0
            ? $this->buildOutputBundle($jobId, $rebrandedFiles, $finalZipName . '.zip')
            : null;

        // Final Cleanup Level 1: Delete original uploads and large extracts
        // We only keep the '/output' folder which has the rebranded zips
        if (File::exists($this->storagePath . '/' . $jobId . '/work')) {
            File::deleteDirectory($this->storagePath . '/' . $jobId . '/work');
        }
        if (File::exists($this->storagePath . '/' . $jobId . '/input')) {
            File::deleteDirectory($this->storagePath . '/' . $jobId . '/input');
        }

        $this->updateJob($jobId, [
            'status' => 'completed',
            'outputs' => $rebrandedFiles,
            'bundle' => $bundle,
            'progress' => 100,
            'store_name' => $storeName,
            'final_zip_name' => $finalZipName,
        ]);

        return response()->json([
            'status' => 'completed',
            'outputs' => $rebrandedFiles,
            'bundle' => $bundle,
            'store_name' => $storeName,
            'final_zip_name' => $finalZipName,
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

            $this->updateJob($jobId, [
                'outputs' => $outputs,
                'bundle' => $bundle
            ]);
        }

        return response()->json([
            'status' => 'renamed',
            'outputs' => $job->outputs,
            'bundle' => $job->bundle
        ]);
    }

    public function getStatus($jobId)
    {
        $job = $this->getJob($jobId);
        return response()->json($job ?: ['error' => 'Job not found'], $job ? 200 : 404);
    }

    public function cleanup($jobId)
    {
        $this->deleteJobData($jobId);
        return response()->json(['status' => 'success']);
    }

    public function maintenanceCleanup(Request $request)
    {
        // Deep clean: delete all folders and records
        $folders = File::directories($this->storagePath);
        foreach ($folders as $folder) {
            if (basename($folder) !== 'users') {
                File::deleteDirectory($folder);
            }
        }
        
        // Log cleanup
        try {
            \App\Models\StorageLog::create([
                'user_id' => Auth::id(),
                'event_type' => 'cleanup',
                'message' => 'User cleared working storage memory.',
                'size_delta' => 0 // Would need to measure before and after for true delta
            ]);
        } catch (\Exception $e) {}

        return response()->json(['status' => 'success', 'message' => 'All temporary storage cleared.']);
    }


    // --- Helpers ---

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
        $jobPath = $this->storagePath . DIRECTORY_SEPARATOR . $jobId;
        if (File::exists($jobPath)) {
            File::deleteDirectory($jobPath);
        }

        // No record to delete, directory already gone
        // StudioJob::where('job_id', $jobId)->delete();
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
            $normalized = strtolower(trim((string) $value));
            if ($normalized === '' || isset($seen[$normalized])) {
                continue;
            }

            $seen[$normalized] = true;
            $unique[] = trim((string) $value);
        }

        return $unique;
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
        $data = json_decode(file_get_contents($path), true);
        return $data ? (object)$data : null;
    }

    private function updateJob($jobId, $data) {
        $path = $this->storagePath . '/' . $jobId . '/job.json';
        if (!file_exists(dirname($path))) @mkdir(dirname($path), 0755, true);

        // Atomic write with retry
        for ($i = 0; $i < 3; $i++) {
            try {
                $job = (array)($this->getJob($jobId) ?? []);
                $job = array_merge($job, $data);
                $json = json_encode($job, JSON_PRETTY_PRINT);
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
}
