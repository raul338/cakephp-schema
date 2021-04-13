<?php
declare(strict_types=1);

// @codingStandardsIgnoreStart
return [
    'profiles' => [
        [
            'id' => 1,
            'name' => 'admin'
        ],
    ],
    'users' => [
        [
            'id' => 1,
            'profile_id' => 1,
            'name' => 'alice'
        ],
        [
            'id' => 2,
            'profile_id' => 1,
            'name' => 'bob'
        ],
    ],
];
