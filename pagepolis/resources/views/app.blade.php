<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Pagepolis') }}</title>

        {{-- SEO base (la landing es la página clave para posicionar en Google) --}}
        <meta name="description" content="Crea tu página web profesional con inteligencia artificial en minutos. Describe tu negocio y Pagepolis genera una web completa, con tienda online y dominio propio. Publica gratis.">
        <meta name="keywords" content="crear página web, web con inteligencia artificial, hacer una web, página web gratis, tienda online, crear web negocio, generador de webs IA">
        <meta name="robots" content="index, follow">
        <meta name="theme-color" content="#7c3aed">
        <link rel="canonical" href="{{ url()->current() }}">
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/logo-icono.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/logo-icono.svg">

        {{-- Open Graph / Twitter (al compartir en redes y WhatsApp) --}}
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="Pagepolis">
        <meta property="og:title" content="Pagepolis — Crea tu web profesional con IA en minutos">
        <meta property="og:description" content="Describe tu negocio y la IA genera una web completa con tienda online. Publica gratis en pagepolis.com o con tu propio dominio.">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ config('app.url') }}/og-image.png">
        <meta property="og:locale" content="es_ES">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="Pagepolis — Crea tu web profesional con IA en minutos">
        <meta name="twitter:description" content="Describe tu negocio y la IA genera una web completa con tienda online. Publica gratis o con tu dominio.">
        <meta name="twitter:image" content="{{ config('app.url') }}/og-image.png">

        {{-- Datos estructurados (JSON-LD) para que Google entienda qué es Pagepolis --}}
        <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@graph'   => [
                [
                    '@type' => 'Organization',
                    '@id'   => config('app.url') . '/#organization',
                    'name'  => 'Pagepolis',
                    'url'   => config('app.url'),
                    'logo'  => config('app.url') . '/og-image.png',
                ],
                [
                    '@type'      => 'WebSite',
                    '@id'        => config('app.url') . '/#website',
                    'name'       => 'Pagepolis',
                    'url'        => config('app.url'),
                    'inLanguage' => 'es',
                    'publisher'  => ['@id' => config('app.url') . '/#organization'],
                ],
                [
                    '@type'               => 'SoftwareApplication',
                    'name'                => 'Pagepolis',
                    'applicationCategory' => 'BusinessApplication',
                    'operatingSystem'     => 'Web',
                    'url'                 => config('app.url'),
                    'description'         => 'Creador de páginas web con inteligencia artificial: describe tu negocio y genera una web completa con tienda online.',
                    'offers'              => [
                        '@type'         => 'Offer',
                        'price'         => '0',
                        'priceCurrency' => 'EUR',
                        'description'   => 'Plan gratuito para publicar en pagepolis.com',
                    ],
                ],
                [
                    // Refleja el FAQ (es) de la landing → habilita el rich snippet desplegable en Google.
                    '@type'      => 'FAQPage',
                    '@id'        => config('app.url') . '/#faq',
                    'mainEntity' => collect([
                        ['¿Necesito saber programar?', 'No. Describes lo que quieres en español y la IA genera todo. Si quieres retocar el código, tienes un editor completo, pero no es obligatorio.'],
                        ['¿Cuánto tarda en generar mi web?', 'Entre 5 y 15 segundos. La IA genera HTML, CSS y JavaScript completo y profesional.'],
                        ['¿Qué pasa con mi dominio si cancelo?', 'Tienes 30 días de periodo de prueba tras cancelar. Tu web sigue activa. Si no reactivas, el dominio se libera y los datos se eliminan automáticamente.'],
                        ['¿Puedo usar mi propio dominio?', 'Sí. En el plan Pro incluimos la compra y configuración automática de un dominio .com. También puedes usar un subdominio gratuito en pagepolis.com.'],
                        ['¿Puedo exportar mi web?', 'Sí. El código HTML/CSS/JS generado es tuyo. Puedes copiarlo y alojarlo donde quieras.'],
                        ['¿Cómo funciona el periodo de prueba?', '7 días completamente gratis. Sin tarjeta de crédito. Acceso completo a todas las funciones.'],
                    ])->map(fn ($qa) => [
                        '@type'          => 'Question',
                        'name'           => $qa[0],
                        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa[1]],
                    ])->all(),
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/Pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
