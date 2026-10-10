<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CAT System Global Configuration
    |--------------------------------------------------------------------------
    */

    'app_name' => env('CAT_APP_NAME', 'Computer Assisted Test Engine'),
    'version'  => '2.0.0',

    'anti_cheat' => [
        'enabled'                  => env('CAT_ENABLE_ANTI_CHEAT', true),
        'max_violations'           => (int) env('CAT_MAX_VIOLATIONS', 3),
        'auto_submit_on_limit'     => env('CAT_AUTO_SUBMIT_ON_VIOLATION', true),
        'enforce_fullscreen'       => true,
        'prevent_copy_paste'       => true,
        'prevent_context_menu'     => true,
        'heartbeat_interval_sec'   => 15, // candidate presence heartbeat
    ],

    'exam_defaults' => [
        'duration_minutes'         => 60,
        'pass_percentage'          => 60.0,
        'shuffle_questions'        => true,
        'shuffle_options'          => true,
        'allow_review'             => true,
        'show_result_instant'      => true,
        'negative_marking'         => false,
        'negative_marks_value'     => 0.25,
    ],

    'question_types' => [
        'single_choice'   => 'Multiple Choice (Single Answer)',
        'multiple_choice' => 'Multiple Choice (Multiple Answers)',
        'true_false'      => 'True or False',
        'short_answer'    => 'Short Answer / Fill in Blank',
    ],

    'difficulty_levels' => [
        'easy'   => 'Easy',
        'medium' => 'Medium',
        'hard'   => 'Hard',
    ],

    'bulk_import' => [
        'max_rows' => 5000,
        'allowed_mimes' => ['csv', 'txt'],
    ],

    'offline_mode' => [
        'use_local_assets' => env('CAT_OFFLINE_ASSETS', true),
        'vendor_paths' => [
            'bootstrap_css'    => 'vendor/bootstrap/css/bootstrap.min.css',
            'bootstrap_js'     => 'vendor/bootstrap/js/bootstrap.bundle.min.js',
            'bootstrap_icons'  => 'vendor/bootstrap-icons/bootstrap-icons.min.css',
            'chartjs'          => 'vendor/chartjs/chart.umd.min.js',
            'sweetalert'       => 'vendor/sweetalert2/sweetalert2.all.min.js',
        ],
    ],
];
