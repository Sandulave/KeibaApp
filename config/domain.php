<?php

use App\Enums\BetType;

$betTypeLabels = [];
foreach (BetType::all() as $betType) {
    $betTypeLabels[$betType->value] = $betType->label();
}

return [
    'roles' => [
        'admin' => ['admin', 'kannrisyato'],
        'viewer_fallback' => 'user',
        'groups' => [
            'admin' => ['admin', 'kannrisyato'],
            'stats_access' => ['user', 'admin', 'kannrisyato'],
        ],
    ],

    'audience_roles' => [
        'streamer' => 'streamer',
        'viewer' => 'viewer',
    ],

    'bet' => [
        'default_horse_count' => 18,
        'frame_max' => 8,
        'point_count_max' => 1_000,
        'type_labels' => $betTypeLabels,
        'amount' => [
            'min' => 100,
            'max' => 1_000_000,
            'step' => 100,
        ],
        'payout' => [
            'min' => 100,
            'step' => 10,
        ],
        'popularity' => [
            'min' => 1,
        ],
    ],

    'stats' => [
        'adjustment_max' => 1_000_000,
    ],
];
