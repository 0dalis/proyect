<?php

return [

    /*
    | Prefijo de todas las bases de datos de empresas.
    | basic   -> {prefix}pool_basic   (Free y Básico comparten)
    | plus    -> {prefix}pool_plus    (solo empresas Plus)
    | premium -> {prefix}tenant_{id}  (BD dedicada)
    */
    'database_prefix' => env('TENANT_DB_PREFIX', 'asist_'),

    'migrations_path' => 'database/migrations/tenant',

    // Checadas repetidas dentro de este lapso se ignoran
    'duplicate_punch_minutes' => 5,

    'geofence' => [
        'min_radius' => 10,
        'max_radius' => 100,
    ],

    'default_trial_days' => 14,

    // El tiempo extra se cuenta en bloques completos (30 min): quedarse 10
    // minutos de más no genera tiempo extra.
    'overtime_block_minutes' => 30,

];
