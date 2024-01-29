<?php

return [
    'cipher' => 'AES-256-CBC',
    'key' => 'f9be1aecfe511a173f4d36a26ba1dbdd',
    'timezone' => 'America/Sao_Paulo',
    'providers' => [
        'TymonJWTAuthProvidersJWTAuthServiceProvider',
    ],
    'aliases' => [
        'JWTAuth' => 'TymonJWTAuthFacadesJWTAuth',
        'JWTFactory' => 'TymonJWTAuthFacadesJWTFactory',
    ],
];
