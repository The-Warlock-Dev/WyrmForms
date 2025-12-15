<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Form Builder Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the routes for the form builder admin interface
    |
    */
    'routes' => [
        'prefix' => 'admin/forms',
        'middleware' => ['web', 'auth'],
        'name_prefix' => 'forms.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Public Form Routes
    |--------------------------------------------------------------------------
    |
    | Configure the public-facing form routes
    |
    */
    'public_routes' => [
        'prefix' => 'forms',
        'middleware' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Configuration
    |--------------------------------------------------------------------------
    |
    | Default email settings for form submissions
    |
    */
    'email' => [
        'enabled' => true,
        'from_name' => env('APP_NAME', 'Laravel Form Builder'),
        'from_address' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
        'queue' => false, // Set to true to queue emails
    ],

    /*
    |--------------------------------------------------------------------------
    | Statistics Configuration
    |--------------------------------------------------------------------------
    |
    | Configure statistics and analytics settings
    |
    */
    'statistics' => [
        'enabled' => true,
        'days_to_show' => 30, // Number of days to show in charts
    ],

    /*
    |--------------------------------------------------------------------------
    | Form Field Types
    |--------------------------------------------------------------------------
    |
    | Available field types for forms
    |
    */
    'field_types' => [
        'text',
        'email',
        'number',
        'textarea',
        'select',
        'checkbox',
        'radio',
        'date',
        'url',
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Default validation rules for different field types
    |
    */
    'validation' => [
        'max_fields' => 50, // Maximum fields per form
        'max_options' => 20, // Maximum options for select/radio/checkbox
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how submissions are stored
    |
    */
    'storage' => [
        'log_ip_addresses' => true,
        'prune_submissions_after_days' => null, // null = never prune, or set number of days
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Configuration
    |--------------------------------------------------------------------------
    |
    | Customize the UI appearance
    |
    */
    'ui' => [
        'use_cdn' => true, // Use CDN for Tailwind/Alpine or false to use your own
        'primary_color' => '#3b82f6', // Blue
    ],
];
