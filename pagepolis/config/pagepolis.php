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

    /*
    |--------------------------------------------------------------------------
    | Correo de soporte
    |--------------------------------------------------------------------------
    | Dirección a la que los usuarios escriben para pedir ayuda. Por defecto es
    | hola@pagepolis.com (la misma que MAIL_FROM_ADDRESS, bandeja real que ya se
    | usa). El botón "Escríbenos" de la landing y las páginas legales usaban
    | antes soporte@pagepolis.com, un buzón que nunca llegó a crearse: los
    | correos se perdían. Si algún día creas un buzón dedicado, ponlo en
    | SUPPORT_EMAIL en el .env.
    */
    'support_email' => env('SUPPORT_EMAIL', 'hola@pagepolis.com'),

];
