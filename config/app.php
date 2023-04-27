<?php

return [
    
    'timezone' => 'America/Sao_Paulo',
    'providers' => [
        'TymonJWTAuthProvidersJWTAuthServiceProvider',
    ],
    'aliases' => [
        'JWTAuth' => 'TymonJWTAuthFacadesJWTAuth',
        'JWTFactory' => 'TymonJWTAuthFacadesJWTFactory',
    ],
];
