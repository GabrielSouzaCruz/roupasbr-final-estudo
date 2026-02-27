<?php

return [
    'backup' => [
        'name' => 'ecommerce-roupasbr',
        'source' => [
            'files' => [
                'include' => [
                    base_path(),
                ],
                'exclude' => [
                    base_path('vendor'),
                    base_path('node_modules'),
                    storage_path('app/backup'),
                ],
            ],
            'databases' => [
                'mysql',
            ],
        ],
        'destination' => [
            'disks' => ['s3'],
        ],
        'monitor_backups' => [
            [
                'name' => 'production',
                'disks' => ['s3'],
                'health_checks' => [
                    \Spatie\Backup\Tasks\HealthChecks\MaximumAgeInDays::class => 1,
                    \Spatie\Backup\Tasks\HealthChecks\MaximumStorageInMegabytes::class => 5000,
                ],
            ],
        ],
        'cleanup' => [
            'strategy' => \Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy::class,
            'default_strategy' => [
                'keep_all_backups_for_days' => 7,
                'keep_daily_backups_for_days' => 30,
                'keep_weekly_backups_for_weeks' => 8,
                'keep_monthly_backups_for_months' => 12,
                'keep_yearly_backups_for_years' => 2,
                'delete_oldest_backups_when_using_more_megabytes_than' => 50000,
            ],
        ],
        'notifications' => [
            'notifications' => [
                \Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification::class => ['mail'],
                \Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification::class => ['mail'],
                \Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification::class => ['mail'],
                \Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification::class => ['mail'],
                \Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification::class => ['mail'],
                \Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification::class => ['mail'],
            ],
            'mail' => [
                'to' => 'admin@seudominio.com.br',
            ],
        ],
    ],
];
