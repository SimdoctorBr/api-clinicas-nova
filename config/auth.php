<?php

return [
    'defaults' => [
        'guard' => 'api',
        'passwords' => 'users',
    ],
    'guards' => [
        'api' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ],      
        'clinicas' => [
            'driver' => 'jwt',
            'provider' => 'users_clinica',
        ],
        'clinicas_pacientes' => [
            'driver' => 'jwt',
            'provider' => 'users_clinica_pacientes',
        ],
          'interno_api' => [
            'driver' => 'jwt',
            'provider' => 'users_api_interno',
        ],
    ],
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => \App\Models\User::class
        ],
        'users_clinica' => [
            'driver' => 'eloquent',
            'model' => \App\Models\Clinicas\User::class
        ],
        'users_clinica_pacientes' => [
            'driver' => 'eloquent',
            'model' => \App\Models\Clinicas\Paciente::class
        ],
        'users_api_interno' => [
            'driver' => 'eloquent',
            'model' => \App\Models\Gerenciamento\UsersInternoApiSimdoctor::class
        ]
    ]
];
