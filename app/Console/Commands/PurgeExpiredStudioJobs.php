<?php

namespace App\Console\Commands;

use App\Services\StudioJobCleanupService;
use Illuminate\Console\Command;

class PurgeExpiredStudioJobs extends Command
{
    protected $signature = 'studio:purge-expired-jobs';

    protected $description = 'Delete expired studio jobs and free retained storage';

    public function handle(StudioJobCleanupService $cleanup): int
    {
        $stats = $cleanup->purgeExpiredJobs();

        $this->info(sprintf(
            'Cleanup complete. Deleted %d jobs, freed %s, removed %d Drive folders. Retention: %d hours.',
            $stats['deleted_jobs'],
            $this->formatBytes((int) $stats['freed_bytes']),
            $stats['deleted_drive_folders'],
            $stats['retention_hours']
        ));

        return self::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $value = $bytes / 1024;

        foreach ($units as $unit) {
            if ($value < 1024 || $unit === 'TB') {
                return number_format($value, $value >= 100 ? 0 : 2) . ' ' . $unit;
            }

            $value /= 1024;
        }

        return number_format($value, 2) . ' TB';
    }
}
