<?php

namespace Tests\Unit;

use App\Services\StudioJobCleanupService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class StudioJobCleanupServiceTest extends TestCase
{
    private string $tempPath;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('studio.job_retention_hours', 24);

        $this->tempPath = storage_path('framework/testing/studio-job-cleanup');
        File::deleteDirectory($this->tempPath);
        File::ensureDirectoryExists($this->tempPath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tempPath);

        parent::tearDown();
    }

    public function test_it_purges_only_expired_jobs(): void
    {
        $expiredPath = $this->tempPath . DIRECTORY_SEPARATOR . 'expired-job';
        $freshPath = $this->tempPath . DIRECTORY_SEPARATOR . 'fresh-job';

        File::ensureDirectoryExists($expiredPath);
        File::ensureDirectoryExists($freshPath);

        file_put_contents($expiredPath . DIRECTORY_SEPARATOR . 'payload.txt', str_repeat('a', 512));
        file_put_contents($freshPath . DIRECTORY_SEPARATOR . 'payload.txt', str_repeat('b', 512));

        file_put_contents($expiredPath . DIRECTORY_SEPARATOR . 'job.json', json_encode([
            'job_id' => 'expired-job',
            'status' => 'completed',
            'created_at' => now()->subHours(30)->toIso8601String(),
            'updated_at' => now()->subHours(30)->toIso8601String(),
            'last_activity_at' => now()->subHours(30)->toIso8601String(),
            'expires_at' => now()->subHours(6)->toIso8601String(),
        ], JSON_PRETTY_PRINT));

        file_put_contents($freshPath . DIRECTORY_SEPARATOR . 'job.json', json_encode([
            'job_id' => 'fresh-job',
            'status' => 'completed',
            'created_at' => now()->subHours(2)->toIso8601String(),
            'updated_at' => now()->subHours(2)->toIso8601String(),
            'last_activity_at' => now()->subHours(2)->toIso8601String(),
            'expires_at' => now()->addHours(22)->toIso8601String(),
        ], JSON_PRETTY_PRINT));

        $service = new StudioJobCleanupService($this->tempPath);
        $stats = $service->purgeExpiredJobs();

        $this->assertSame(1, $stats['deleted_jobs']);
        $this->assertGreaterThan(0, $stats['freed_bytes']);
        $this->assertFalse(File::exists($expiredPath));
        $this->assertTrue(File::exists($freshPath));
    }
}
