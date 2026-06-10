<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Límites anti-abuso del tier gratuito
    |--------------------------------------------------------------------------
    | Topes de cantidad para usuarios sin suscripción. Evitan que bots o
    | usuarios malintencionados saturen el hosting creando o publicando webs
    | sin control. Los usuarios de pago y los administradores no tienen tope.
    */
    'limits' => [
        'free_max_projects'  => (int) env('FREE_MAX_PROJECTS', 10),
        'free_max_published' => (int) env('FREE_MAX_PUBLISHED', 3),
    ],

];
