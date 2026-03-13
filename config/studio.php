<?php

return [
    'job_retention_hours' => max(1, (int) env('STUDIO_JOB_RETENTION_HOURS', 24)),
    'delete_drive_folders_on_cleanup' => filter_var(env('STUDIO_DELETE_DRIVE_FOLDERS_ON_CLEANUP', false), FILTER_VALIDATE_BOOL),
];
