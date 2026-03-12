<?php

return [
    'job_retention_hours' => max(1, (int) env('STUDIO_JOB_RETENTION_HOURS', 24)),
];
