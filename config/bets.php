<?php

return [

    'types' => [

        /*
        |--------------------------------------------------------------------------
        | 三連単
        |--------------------------------------------------------------------------
        | 固定流しは formation で表現する。
        | 専用モードはマルチ系のみ。
        */
        'sanrentan' => [
            'label' => '三連単',

            'modes' => [

                'box' => [
                    'label'   => 'ボックス',
                    'view'    => 'bet.build.sanrentan.box',
                    'builder' => \App\Services\Bet\Builders\SanrentanBoxBuilder::class,
                ],

                'formation' => [
                    'label'   => 'フォーメーション',
                    'view'    => 'bet.build.sanrentan.formation',
                    'builder' => \App\Services\Bet\Builders\SanrentanFormationBuilder::class,
                ],

                // 1頭軸マルチ
                'nagashi_1axis' => [
                    'label'   => '1頭軸流し（マルチ）',
                    'view'    => 'bet.build.sanrentan.nagashi_1axis',
                    'builder' => \App\Services\Bet\Builders\SanrentanNagashi1AxisMultiBuilder::class,
                ],

                // 2頭軸マルチ
                'nagashi_2axis' => [
                    'label'   => '2頭軸流し（マルチ）',
                    'view'    => 'bet.build.sanrentan.nagashi_2axis',
                    'builder' => \App\Services\Bet\Builders\SanrentanNagashi2AxisMultiBuilder::class,
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 三連複（順不同・昇順固定）
        |--------------------------------------------------------------------------
        */
        'sanrenpuku' => [
            'label' => '三連複',

            'modes' => [

                'box' => [
                    'label'   => 'ボックス',
                    'view'    => 'bet.build.sanrenpuku.box',
                    'builder' => \App\Services\Bet\Builders\SanrenpukuBoxBuilder::class,
                ],

                'formation' => [
                    'label'   => 'フォーメーション',
                    'view'    => 'bet.build.sanrenpuku.formation',
                    'builder' => \App\Services\Bet\Builders\SanrenpukuFormationBuilder::class,
                ],

                'nagashi_1axis' => [
                    'label'   => '1頭軸流し',
                    'view'    => 'bet.build.sanrenpuku.nagashi_1axis',
                    'builder' => \App\Services\Bet\Builders\SanrenpukuNagashi1AxisBuilder::class,
                ],

                'nagashi_2axis' => [
                    'label'   => '2頭軸流し',
                    'view'    => 'bet.build.sanrenpuku.nagashi_2axis',
                    'builder' => \App\Services\Bet\Builders\SanrenpukuNagashi2AxisBuilder::class,
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 馬連（順不同・昇順固定）
        |--------------------------------------------------------------------------
        */
        'umaren' => [
            'label' => '馬連',

            'modes' => [

                'box' => [
                    'label'   => 'ボックス',
                    'view'    => 'bet.build.umaren.box',
                    'builder' => \App\Services\Bet\Builders\UmarenBoxBuilder::class,
                ],

                'nagashi_1axis' => [
                    'label'   => '1頭軸流し',
                    'view'    => 'bet.build.umaren.nagashi_1axis',
                    'builder' => \App\Services\Bet\Builders\UmarenNagashi1AxisBuilder::class,
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 馬単（順序あり）
        |--------------------------------------------------------------------------
        */
        'umatan' => [
            'label' => '馬単',

            'modes' => [

                'box' => [
                    'label'   => 'ボックス',
                    'view'    => 'bet.build.umatan.box',
                    'builder' => \App\Services\Bet\Builders\UmatanBoxBuilder::class,
                ],

                'nagashi_1axis_multi' => [
                    'label'   => '1頭軸流し（マルチ）',
                    'view'    => 'bet.build.umatan.nagashi_1axis_multi',
                    'builder' => \App\Services\Bet\Builders\UmatanNagashi1AxisMultiBuilder::class,
                ],

                'formation' => [
                    'label'   => 'フォーメーション',
                    'view'    => 'bet.build.umatan.formation',
                    'builder' => \App\Services\Bet\Builders\UmatanFormationBuilder::class,
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | ワイド（順不同）
        |--------------------------------------------------------------------------
        */
        'wide' => [
            'label' => 'ワイド',

            'modes' => [

                'box' => [
                    'label'   => 'ボックス',
                    'view'    => 'bet.build.wide.box',
                    'builder' => \App\Services\Bet\Builders\WideBoxBuilder::class,
                ],

                'nagashi_1axis' => [
                    'label'   => '1頭軸流し',
                    'view'    => 'bet.build.wide.nagashi_1axis',
                    'builder' => \App\Services\Bet\Builders\WideNagashi1AxisBuilder::class,
                ],

                'formation' => [
                    'label'   => 'フォーメーション',
                    'view'    => 'bet.build.wide.formation',
                    'builder' => \App\Services\Bet\Builders\WideFormationBuilder::class,
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 枠連（順不同）
        |--------------------------------------------------------------------------
        */
        'wakuren' => [
            'label' => '枠連',

            'modes' => [

                //'box' => [
                //    'label'   => 'ボックス',
                //    'view'    => 'bet.build.wakuren.box',
                //    'builder' => \App\Services\Bet\Builders\WakurenBoxBuilder::class,
                //],

                'nagashi_1axis' => [
                    'label'   => '1頭軸流し',
                    'view'    => 'bet.build.wakuren.nagashi_1axis',
                    'builder' => \App\Services\Bet\Builders\WakurenNagashi1AxisBuilder::class,
                ],

                'formation' => [
                    'label'   => 'フォーメーション',
                    'view'    => 'bet.build.wakuren.formation',
                    'builder' => \App\Services\Bet\Builders\WakurenFormationBuilder::class,
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 単勝
        |--------------------------------------------------------------------------
        */
        'tansho' => [
            'label' => '単勝',

            'modes' => [
                'single' => [
                    'label'   => '単勝',
                    'view'    => 'bet.build.tansho.single',
                    'builder' => \App\Services\Bet\Builders\TanshoSingleBuilder::class,
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 複勝
        |--------------------------------------------------------------------------
        */
        'fukusho' => [
            'label' => '複勝',

            'modes' => [
                'single' => [
                    'label'   => '複勝',
                    'view'    => 'bet.build.fukusho.single',
                    'builder' => \App\Services\Bet\Builders\FukushoSingleBuilder::class,
                ],
            ],
        ],
    ],
];
