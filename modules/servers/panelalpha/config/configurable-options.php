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
        'options' => ['1']
    ],
    'enable_redis_cache' => [
        'name' => 'enable_redis_cache|Enabled Redis Cache',
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