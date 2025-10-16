<?php

/**
 * available types:
 *  - dropdown - 1
 *  - radio - 2
 *  - boolean - 3
 *  - quantity - 4
 */
return [
    'server_location' => [
        'name' => 'server_location|Location',
        'type' => 1,
        'options' => [
            '1|Location 1',
            '2|Location 2',
        ]
    ],
    'sites' => [
        'name' => 'sites|Number of Sites',
        'type' => 4,
        'options' => ['Site'],
    ],
    'geo_affinity' => [
        'name' => 'geo_affinity|Location',
        'type' => 1,
        'options' => [
            'ams|Amsterdam, NL',
            'bur|Los Angeles, CA',
            'dca|Ashburn, VA',
            'dfw|Dallas, TX',
        ]
    ],
    'space_quota' => [
        'name' => 'space_quota|Space Quota',
        'type' => 4,
        'options' => ['1 MB']
    ],
    'php_workers' => [
        'name' => 'php_workers|PHP Workers',
        'type' => 4,
        'options' => ['1 PHP Worker'],
    ],
    'php_memory_limit' => [
        'name' => 'php_memory_limit|PHP Memory Limit',
        'type' => 1,
        'options' => [
            '512|512 MB',
            '1024|1024 MB',
            '1536|1536 MB',
            '2048|2048 MB',
        ],
    ],
    'burst_up_php_workers' => [
        'name' => 'burst_up_php_workers|Burst Up PHP Workers',
        'type' => 3,
        'options' => ['Enable'],
    ],
    'default_ssh_access_enabled' => [
        'name' => 'default_ssh_access_enabled|Enabled SSH Access',
        'type' => 3,
        'options' => ['Enable'],
    ],
    'disk_space_limit' => [
        'name' => 'disk_space_limit|Disk Space Limit',
        'type' => 4,
        'options' => ['1 MB'],
    ],
    'memory_limit' => [
        'name' => 'memory_limit|Memory Limit',
        'type' => 4,
        'options' => ['1 MB']
    ],
    'cpu_limit' => [
        'name' => 'cpu_limit|CPU Limit',
        'type' => 4,
        'options' => ['number|Number']
    ],
    'device_read_bps' => [
        'name' => 'device_read_bps|Read Rate Limit (Bps)',
        'type' => 4,
        'options' => ['number|Number']
    ],
    'device_write_bps' => [
        'name' => 'device_write_bps|Write Rate Limit (Bps)',
        'type' => 4,
        'options' => ['number|Number']
    ],
    'bandwidth_limit' => [
        'name' => 'bandwidth_limit|Bandwidth Limit (MB Per Month)',
        'type' => 4,
        'options' => ['number|Number']
    ],
    'mysql_databases_limit' => [
        'name' => 'mysql_databases_limit|MySQL Databases Limit',
        'type' => 4,
        'options' => ['number|Number']
    ],
    'ftp_accounts_limit' => [
        'name' => 'ftp_accounts_limit|FTP Accounts Limit',
        'type' => 4,
        'options' => ['number|Number']
    ],
    'sftp_accounts_limit' => [
        'name' => 'sftp_accounts_limit|SFTP Accounts Limit',
        'type' => 4,
        'options' => ['number|Number']
    ],
    'addon_domains_limit' => [
        'name' => 'addon_domains_limit|Addon Domains Limit',
        'type' => 4,
        'options' => ['number|Number']
    ],
    'subdomains_limit' => [
        'name' => 'subdomains_limit|Subdomains Limit',
        'type' => 4,
        'options' => ['number|Number']
    ],
    'inodes_limit' => [
        'name' => 'inodes_limit|Inodes Limit',
        'type' => 4,
        'options' => ['number|Number']
    ],
    'php_fpm_pool_settings' => [
        'name' => 'php_fpm_pool_settings|PHP-FPM Pool Settings',
    ],
    'lsphp_settings' => [
        'name' => 'lsphp_settings|LiteSpeed Settings',
        'type' => 4,
        'options' => ['number|Number']
    ],
    'enable_redis_cache' => [
        'name' => 'enable_redis_cache|Enabled Redis Cache',
        'type' => 3,
        'options' => ['Enable']
    ],
    'dedicated_ipv4' => [
        'name' => 'dedicated_ipv4|Dedicated IPv4',
        'type' => 3,
        'options' => ['Enable']
    ],
    'dedicated_ipv6' => [
        'name' => 'dedicated_ipv6|Dedicated IPv6',
        'type' => 3,
        'options' => ['Enable']
    ],
    'whm_package' => [
        'name' => 'whm_package|WHM Package',
        'type' => 1,
        'options' => [
            'basic|Premium',
            'premium|Premium',
        ]
    ],
    'package' => [
        'name' => 'package|Package',
        'type' => 1,
        'options' => [
            'basic|Premium',
            'premium|Premium',
        ]
    ],
    'hosting_plan' => [
        'name' => 'hosting_plan|Hosting Plan',
        'type' => 1,
        'options' => [
            'basic|Premium',
            'premium|Premium',
        ]
    ],
];