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

    /*
    |--------------------------------------------------------------------------
    | Dominios: coste, fondo de reserva y política de renovación
    |--------------------------------------------------------------------------
    | El registrador cobra el dominio ANUAL por adelantado, pero al cliente se le
    | puede cobrar mensual. Para no descuadrarnos, en cada cobro apartamos una
    | porción prorrateada del coste anual del dominio en un "fondo de reserva":
    | cuando llega la renovación se paga DESDE ese fondo, no de la caja general.
    |
    | La porción que apartamos por ciclo lleva un margen de seguridad (>1) para
    | cubrir subidas de precio / cambio de divisa del registrador: así, sumadas
    | las porciones de un año, SIEMPRE hay más de lo que cuesta renovar.
    |
    | Todo en CÉNTIMOS (igual que Stripe/Cashier). Precios reales configurables por
    | env; el precio EXACTO de compra/renovación se consulta en vivo al registrador
    | antes de pagar (esto es solo la base de acumulación, no un coste asumido).
    */
    'domain' => [
        // Coste anual estimado del dominio en el registrador (base de acumulación).
        'annual_cost_cents' => (int) env('DOMAIN_ANNUAL_COST_CENTS', 1200), // 12,00 €/año

        // Margen de seguridad sobre la porción prorrateada (1.25 = +25% de colchón).
        // Con margen 1.25 y cobro mensual, en 12 meses acumulas 1,25× el coste anual.
        'reserve_margin' => (float) env('DOMAIN_RESERVE_MARGIN', 1.25),

        // Nº de días antes de la expiración en que se intenta la renovación.
        'renew_before_days' => (int) env('DOMAIN_RENEW_BEFORE_DAYS', 30),

        // Qué hacer con el dominio si el cliente cancela antes de fin de ciclo.
        // 'suspend' = se suspende la web pero el dominio se conserva hasta expirar;
        // 'offer_transfer' = se le ofrece quedárselo pagando la diferencia.
        'on_cancel' => env('DOMAIN_ON_CANCEL', 'suspend'),
    ],

];
