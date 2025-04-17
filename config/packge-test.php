<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Command Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can configure defaults for the commands in this package.
    |
    */

    // MakeCrud command settings
    'make_crud' => [
        'generate_repository' => true,
        'api_controller' => false,
        'add_routes' => false,
    ],

    // Model Relations settings
    'model_relations' => [
        'detect_relationships' => true,
    ],
];