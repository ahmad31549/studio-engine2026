<?php

return [
    'job_retention_hours' => max(1, (int) env('STUDIO_JOB_RETENTION_HOURS', 2)),
    'local_working_limit_mb' => max(1024, (int) env('STUDIO_LOCAL_WORKING_LIMIT_MB', 8192)),
    'delete_drive_folders_on_cleanup' => filter_var(env('STUDIO_DELETE_DRIVE_FOLDERS_ON_CLEANUP', false), FILTER_VALIDATE_BOOL),
];
