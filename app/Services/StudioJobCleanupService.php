<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StudioJobCleanupService
{
    private string $storagePath;
    private ?string $ownerDriveAccessToken = null;
    private ?int $ownerDriveAccessTokenExpiresAt = null;

    public function __construct(?string $storagePath = null)
    {
        $this->storagePath = $storagePath ?: storage_path('app/tasks');
    }

    public function purgeExpiredJobs(): array
    {
        $stats = [
            'retention_hours' => $this->retentionHours(),
            'deleted_jobs' => 0,
            'freed_bytes' => 0,
            'deleted_drive_folders' => 0,
        ];

        if (!File::exists($this->storagePath)) {
            return $stats;
        }

        $now = now();
        $cutoff = $now->copy()->subHours($stats['retention_hours']);

        foreach (File::directories($this->storagePath) as $folder) {
            $jobId = basename($folder);
            $job = $this->readJobData($jobId);

            if (!$this->jobHasExpired($folder, $job, $now, $cutoff)) {
                continue;
            }

            $deleted = $this->deleteJobData($jobId, $job);
            if (!$deleted['deleted']) {
                continue;
            }

            $stats['deleted_jobs']++;
            $stats['freed_bytes'] += $deleted['freed_bytes'];
            if ($deleted['deleted_drive_folder']) {
                $stats['deleted_drive_folders']++;
            }
        }

        return $stats;
    }

    public function deleteJobData(string $jobId, ?array $job = null): array
    {
        $jobPath = $this->storagePath . DIRECTORY_SEPARATOR . $jobId;
        $job = $job ?? $this->readJobData($jobId);

        $freedBytes = File::exists($jobPath) ? $this->directorySize($jobPath) : 0;
        $deletedDriveFolder = false;

        if ($job) {
            $deletedDriveFolder = $this->deleteManagedDriveFolder($jobId, $job);
        }

        $deleted = false;
        if (File::exists($jobPath)) {
            $deleted = File::deleteDirectory($jobPath);
        }

        return [
            'deleted' => $deleted || !File::exists($jobPath),
            'freed_bytes' => $freedBytes,
            'deleted_drive_folder' => $deletedDriveFolder,
        ];
    }

    private function retentionHours(): int
    {
        return max(1, (int) config('studio.job_retention_hours', 24));
    }

    private function jobHasExpired(string $jobPath, ?array $job, Carbon $now, Carbon $cutoff): bool
    {
        $expiresAt = $this->parseTimestamp($job['expires_at'] ?? null);
        if ($expiresAt instanceof Carbon) {
            return $expiresAt->lessThanOrEqualTo($now);
        }

        $lastActivityAt = $this->resolveLastActivityAt($jobPath, $job);
        if ($lastActivityAt instanceof Carbon) {
            return $lastActivityAt->lessThanOrEqualTo($cutoff);
        }

        return false;
    }

    private function resolveLastActivityAt(string $jobPath, ?array $job): ?Carbon
    {
        foreach (['last_activity_at', 'updated_at', 'created_at'] as $field) {
            $parsed = $this->parseTimestamp($job[$field] ?? null);
            if ($parsed instanceof Carbon) {
                return $parsed;
            }
        }

        $jobJsonPath = $jobPath . DIRECTORY_SEPARATOR . 'job.json';
        if (is_file($jobJsonPath)) {
            $timestamp = @filemtime($jobJsonPath);
            if (is_int($timestamp) && $timestamp > 0) {
                return Carbon::createFromTimestamp($timestamp);
            }
        }

        $timestamp = @filemtime($jobPath);
        if (is_int($timestamp) && $timestamp > 0) {
            return Carbon::createFromTimestamp($timestamp);
        }

        return null;
    }

    private function parseTimestamp(mixed $value): ?Carbon
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function readJobData(string $jobId): ?array
    {
        $path = $this->storagePath . DIRECTORY_SEPARATOR . $jobId . DIRECTORY_SEPARATOR . 'job.json';
        if (!is_file($path)) {
            return null;
        }

        $contents = @file_get_contents($path);
        if (!is_string($contents) || $contents === '') {
            return null;
        }

        $decoded = json_decode($contents, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function directorySize(string $path): int
    {
        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    private function deleteManagedDriveFolder(string $jobId, array $job): bool
    {
        if (!$this->hasOwnerManagedGoogleDriveStorage()) {
            return false;
        }

        $driveStorage = $job['drive_storage'] ?? null;
        if (!is_array($driveStorage)) {
            return false;
        }

        $jobFolderId = trim((string) ($driveStorage['job_folder_id'] ?? ''));
        if ($jobFolderId === '') {
            return false;
        }

        try {
            $this->deleteGoogleDriveFile($this->getOwnerManagedGoogleDriveAccessToken(), $jobFolderId);
            return true;
        } catch (\Throwable $e) {
            Log::warning("Failed to delete owner-managed Drive folder for expired job {$jobId}: " . $e->getMessage());
            return false;
        }
    }

    private function hasOwnerManagedGoogleDriveStorage(): bool
    {
        $google = config('services.google');

        return (bool) ($google['owner_managed'] ?? false)
            && trim((string) ($google['client_id'] ?? '')) !== ''
            && trim((string) ($google['client_secret'] ?? '')) !== ''
            && trim((string) ($google['refresh_token'] ?? '')) !== '';
    }

    private function getOwnerManagedGoogleDriveAccessToken(): string
    {
        if ($this->ownerDriveAccessToken && is_int($this->ownerDriveAccessTokenExpiresAt) && $this->ownerDriveAccessTokenExpiresAt > (time() + 60)) {
            return $this->ownerDriveAccessToken;
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

    private function deleteGoogleDriveFile(string $token, string $fileId): void
    {
        $response = Http::timeout(60)
            ->withToken($token)
            ->delete("https://www.googleapis.com/drive/v3/files/{$fileId}");

        if ($response->status() !== 204 && !$response->successful() && $response->status() !== 404) {
            throw new \RuntimeException('Google Drive file delete failed: ' . $response->body());
        }
    }
}
