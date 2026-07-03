<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnthropicService
{
    private string $apiKey;
    private string $paidModel;
    private string $freeModel;
    private int $maxTokens;
    private ?string $activeModel = null;
    private ?string $hero3dEngineCache = null;

    public function __construct(private AiBudgetGuard $budget)
    {
        $this->apiKey    = config('services.anthropic.key');
        $this->paidModel = config('services.anthropic.model', 'claude-opus-4-8');
        $this->freeModel = config('services.anthropic.model_free', 'claude-sonnet-4-6');
        $this->maxTokens = (int) config('services.anthropic.max_tokens', 16000);
    }

    /**
     * Selecciona el modelo según el tier del usuario: los de pago usan el mejor
     * modelo (máxima calidad); los gratis uno más económico (protege el margen).
     */
    public function forUser(?User $user): static
    {
        $this->activeModel = ($user && $user->isSubscribed()) ? $this->paidModel : $this->freeModel;

        return $this;
    }

    private function model(): string
    {
        return $this->activeModel ?? $this->freeModel;
    }

    /**
     * Construye el "content" de un mensaje de usuario. Si hay imágenes adjuntas
     * (p. ej. la carta de un restaurante o un logo), las añade como bloques de
     * visión para que la IA lea su contenido real. Las imágenes van ANTES del texto
     * (recomendación de Anthropic para mejor comprensión).
     *
     * @param array<int,array{media_type:string,data:string}> $images
     * @return string|array<int,array<string,mixed>>
     */
    private function userContent(string $text, array $images = []): string|array
    {
        $imageBlocks = [];
        foreach ($images as $img) {
            if (empty($img['data']) || empty($img['media_type'])) {
                continue;
            }
            $imageBlocks[] = [
                'type'   => 'image',
                'source' => [
                    'type'       => 'base64',
                    'media_type' => $img['media_type'],
                    'data'       => $img['data'],
                ],
            ];
        }

        if (empty($imageBlocks)) {
            return $text;
        }

        $note = ' (El usuario ha adjuntado ' . count($imageBlocks)
            . ' imagen(es): pueden ser su carta o menú, su logo, o fotos del negocio. Extrae de ellas el contenido REAL —nombres de platos, precios, textos, datos— e inclúyelo en la web. Si hay un logo o una foto, úsalos como referencia de estilo, colores y contenido.)';

        $imageBlocks[] = ['type' => 'text', 'text' => $text . $note];

        return $imageBlocks;
    }

    /**
     * Genera un SITIO WEB COMPLETO desde cero: multi-sección, profesional y, cuando
     * el negocio lo requiere, con tienda (carrito + checkout por WhatsApp/email).
     *
     * @param array<int,array{media_type:string,data:string}> $images
     */
    public function generateWebsite(string $userPrompt, array $templateContext = [], ?callable $onProgress = null, array $images = []): array
    {
        // ── FASE 1: estructura (HTML) + estilos (CSS) co-diseñados ──────────────
        // Un sitio complejo no cabe entero (HTML+CSS+JS) en una sola respuesta sin
        // truncarse, así que se genera en dos fases sobre una CONVENCIÓN fija de
        // ids/clases para que el JS de la fase 2 case con el HTML de la fase 1.
        if ($onProgress) {
            $onProgress('Diseñando la estructura y los estilos…');
        }

        $messagesA = [];
        if (!empty($templateContext)) {
            $messagesA[] = ['role' => 'user',      'content' => 'Usa esta plantilla solo como inspiración estética, pero amplíala hasta un sitio completo: ' . json_encode($templateContext)];
            $messagesA[] = ['role' => 'assistant', 'content' => 'Entendido. La usaré como base estética y construiré un sitio completo y rico.'];
        }
        $messagesA[] = ['role' => 'user', 'content' => $this->userContent("Crea la web (HTML + CSS) para este encargo:\n\n{$userPrompt}", $images)];

        $blocksA = $this->parseBlocks($this->requestText($this->htmlCssSystemPrompt(), $messagesA, $this->maxTokens));
        if (empty($blocksA['html'])) {
            $blocksA = $this->parseBlocks($this->requestText($this->htmlCssSystemPrompt(), $messagesA, $this->maxTokens));
        }
        if (empty($blocksA['html'])) {
            throw new \Exception('La IA no pudo generar la web. Inténtalo de nuevo con una descripción un poco más concreta.');
        }

        $html = $blocksA['html'];
        $css  = $blocksA['css'] ?? '';
        $desc = $blocksA['desc'] ?? 'He creado tu web completa.';

        // ── FASE 2: JavaScript (interactividad + tienda) sobre el HTML real ─────
        if ($onProgress) {
            $onProgress('Programando la interactividad y la tienda…');
        }

        $messagesB = [[
            'role'    => 'user',
            'content' => "Encargo original: {$userPrompt}\n\nEste es el HTML de la web (clases e ids ya definidos):\n\n{$html}\n\nEscribe el JavaScript completo.",
        ]];

        $blocksB = $this->parseBlocks($this->requestText($this->jsSystemPrompt(), $messagesB, $this->maxTokens));
        $js      = $this->withHero3dEngine($blocksB['js'] ?? '', $html);

        return [
            'html'        => $html,
            'css'         => $css,
            'js'          => $js,
            'description' => $desc,
        ];
    }

    /**
     * Si el HTML incluye el canvas del hero 3D, adjunta el motor WebGL propio
     * (hero3d.js, sin dependencias) tal cual está en disco: es más fiable que
     * pedirle a la IA que reescriba WebGL en cada generación, y no añade coste
     * de tokens al prompt. Idempotente: no lo duplica si ya está presente.
     */
    private function withHero3dEngine(string $js, string $html): string
    {
        if (!str_contains($html, 'hero3d-canvas') && !str_contains($html, 'data-hero3d')) {
            return $js;
        }
        if (str_contains($js, 'PAGEPOLIS-HERO3D-ENGINE')) {
            return $js;
        }

        return rtrim($js) . "\n\n" . $this->hero3dEngine();
    }

    private function hero3dEngine(): string
    {
        if ($this->hero3dEngineCache === null) {
            $this->hero3dEngineCache = @file_get_contents(database_path('templates/hero3d.js')) ?: '';
        }

        return $this->hero3dEngineCache;
    }

    /**
     * Aplica un cambio quirúrgico a una web existente, conservando todo lo demás
     * (incluido el sistema de diseño, las secciones y la lógica de la tienda).
     */
    public function updateWebsite(
        string $instruction,
        string $currentHtml,
        string $currentCss,
        string $currentJs,
        array  $chatHistory = [],
        array  $images = []
    ): array {
        $system = <<<'SYSTEM'
Eres un desarrollador web senior que aplica cambios PRECISOS y QUIRÚRGICOS a una web existente, sin romper nada.

REGLAS CRÍTICAS:
1. Modifica SOLO lo que el usuario pide. Conserva intactos el sistema de diseño (variables CSS), TODAS las secciones, las imágenes, la interactividad (menú móvil, scroll suave, acordeón, animaciones) y, si existe, TODA la lógica de la tienda/carrito.
2. NO regeneres la web desde cero. NO elimines funcionalidad ni secciones existentes salvo que se pida explícitamente.
3. Si añades una sección o producto, intégralo con el mismo estilo, variables y convenciones que el resto.
4. Mantén el código válido y sin errores en consola.
5. CAMBIOS DE COLOR / FONDO / TEMA: aplícalos de forma COHERENTE en TODA la web, no en un solo sitio.
   - Si piden cambiar el FONDO, cambia el fondo de la página y de TODAS las secciones de fondo (hero, body, secciones principales y franjas), no solo una. NUNCA dejes el header o el footer de un color y el cuerpo de otro sin querer.
   - Ajusta SIEMPRE el color del texto y de los elementos para que haya buen contraste y todo se lea perfectamente sobre el nuevo fondo (texto claro sobre fondo oscuro y viceversa).
   - Cambia el valor en las VARIABLES CSS de :root que correspondan (sea cual sea su nombre) para que el cambio se propague; si hay colores fijos en las secciones, cámbialos también.
   - Mantén los botones y acentos con buen contraste y un diseño armónico. El resultado debe verse profesional, nunca a medias.
6. HERO 3D: si el usuario pide un fondo 3D / animado / "como una agencia premium" en el hero, añade <canvas class="hero3d-canvas" data-hero3d aria-hidden="true"> dentro del hero (con position:relative;overflow:hidden en el hero; el canvas en position:absolute;inset:0;z-index:0;pointer-events:none; el contenido de texto/CTAs en position:relative;z-index:1). NO escribas JS para ese canvas: se inicializa solo con un motor que se añade aparte tras tu respuesta. Si el HTML ya tenía ese canvas, consérvalo intacto salvo que pidan quitarlo. Para inclinación 3D en tarjetas, la clase es "tilt-3d" (el JS ya la gestiona si ya estaba implementada; si la añades nueva, implementa el listener de mousemove/mouseleave que fija --tilt-x/--tilt-y como el resto del motor).

FORMATO DE SALIDA (OBLIGATORIO): devuelve el resultado EXACTAMENTE en este formato de bloques, sin texto adicional fuera de ellos. Devuelve SIEMPRE el HTML, CSS y JS COMPLETOS y actualizados (no fragmentos):
[[[HTML]]]
(todo el HTML del body, actualizado)
[[[CSS]]]
(todo el CSS, actualizado)
[[[JS]]]
(todo el JS, actualizado)
[[[DESC]]]
(1 frase en español coloquial explicando qué cambiaste, p. ej. "He cambiado el color principal a azul")
[[[CHANGED]]]
(lista separada por comas de lo que tocaste: html, css y/o js)
SYSTEM;

        $messages = [];
        foreach ($chatHistory as $entry) {
            $messages[] = ['role' => $entry['role'], 'content' => $entry['content']];
        }

        $messages[] = [
            'role'    => 'user',
            'content' => $this->userContent("Esta es mi web actual:\n\n[[[HTML]]]\n{$currentHtml}\n\n[[[CSS]]]\n{$currentCss}\n\n[[[JS]]]\n{$currentJs}\n\n--- CAMBIO SOLICITADO ---\n{$instruction}", $images),
        ];

        $text   = $this->requestText($system, $messages, $this->maxTokens);
        $blocks = $this->parseBlocks($text);

        if (empty($blocks['html'])) {
            $text   = $this->requestText($system, $messages, $this->maxTokens);
            $blocks = $this->parseBlocks($text);
        }

        if (empty($blocks['html'])) {
            throw new \Exception('No pude aplicar el cambio. Inténtalo de nuevo o reformula la instrucción.');
        }

        $changed = [];
        if (!empty($blocks['changed'])) {
            $changed = array_values(array_filter(array_map('trim', explode(',', strtolower($blocks['changed'])))));
        }

        $finalHtml = $blocks['html'] ?? $currentHtml;

        return [
            'html'        => $finalHtml,
            'css'         => $blocks['css'] ?? $currentCss,
            'js'          => $this->withHero3dEngine($blocks['js'] ?? $currentJs, $finalHtml),
            'description' => $blocks['desc'] ?? 'He aplicado el cambio.',
            'changed'     => $changed ?: ['html', 'css', 'js'],
        ];
    }

    /**
     * Convención COMPARTIDA de ids/clases entre la fase HTML/CSS y la fase JS, para
     * que el JavaScript (generado por separado) case exactamente con el HTML.
     */
    private function uiConvention(): string
    {
        return <<<'CONV'
CONVENCIÓN OBLIGATORIA de ids/clases (el JS depende de ella; respétala al pie de la letra):
- Header: <header id="site-header">. Botón hamburguesa: <button id="menu-toggle">. Navegación: <nav id="main-nav">. (El JS alterna la clase "open").
- Animación de aparición: añade la clase "reveal" a las secciones/tarjetas que deban aparecer al hacer scroll.
- FAQ: cada pregunta es <div class="faq-item"> con un <button class="faq-q"> y un <div class="faq-a"> (respuesta). El JS alterna "open" en .faq-item.
- Galería (si hay): cada imagen ampliable con clase "gallery-img".
- Testimonios en carrusel (si hay): contenedor <div class="testi-slider"> con pista <div class="testi-track"> y <div class="testi-slide">, y botones <button class="testi-prev"> / <button class="testi-next">.
- Formulario de contacto: <form id="contact-form"> con campos name="nombre", name="email", name="mensaje", y un <div id="form-success" hidden> para el mensaje de éxito.
- Botón "volver arriba": <button id="to-top">.
- Hero 3D (opcional; ver más abajo cuándo usarlo): <canvas class="hero3d-canvas" data-hero3d aria-hidden="true"> dentro del hero. Se inicializa solo (motor WebGL propio añadido automáticamente tras generar el JS); en el HTML/CSS solo colocas el canvas, NO escribas JS para él.
- Inclinación 3D: añade la clase "tilt-3d" a una tarjeta (feature-card, price-card, product...) para que se incline suavemente hacia el cursor.
- TIENDA (si el negocio vende productos):
  · Cada producto: <article class="product" data-id="..." data-name="..." data-price="29.90" data-img="URL"> con un <button class="add-to-cart">Añadir</button>. (data-price en número con punto decimal).
  · Botón flotante del carrito: <button id="cart-toggle"> con un <span id="cart-count">0</span>.
  · Panel lateral: <aside id="cart-drawer"> con la lista <div id="cart-items">, el total <span id="cart-total">, botón cerrar <button id="cart-close"> y un fondo <div id="cart-overlay">. (El JS alterna "open").
  · Checkout: <button id="checkout-whatsapp"> y <button id="checkout-email">.
CONV;
    }

    /**
     * FASE 1 — Prompt del sistema para generar HTML + CSS (estructura y estilos).
     * El JS se genera después; aquí solo se dejan los "ganchos" de la convención.
     */
    private function htmlCssSystemPrompt(): string
    {
        return str_replace('__CONV__', $this->uiConvention(), <<<'SYSTEM'
Eres un equipo senior de agencia (dirección de arte + UX + front-end) de nivel mundial. Creas SITIOS WEB COMPLETOS, ricos y orientados a la conversión. NUNCA páginas simples de una sola sección: cada web parece hecha por una agencia premium y está lista para captar clientes y vender.

En ESTA fase generas SOLO el HTML y el CSS (el JavaScript se añade en una fase posterior). Por eso NO incluyas ninguna etiqueta <script> ni código JS: solo deja en el HTML los elementos y clases de la convención de abajo para que el JS los controle después.

═══ FORMATO DE SALIDA (OBLIGATORIO) ═══
Devuelve EXACTAMENTE estos bloques, sin texto fuera de ellos:
[[[HTML]]]
(SOLO el contenido del <body>: sin <html>, <head>, <body> ni <script>)
[[[CSS]]]
(TODO el CSS; se inyectará en una etiqueta <style>)
[[[DESC]]]
(1-2 frases en español explicando qué has creado; si incluiste tienda, recuérdale que edite su WhatsApp/email para recibir pedidos)

═══ EFICIENCIA DE TOKENS ═══
- Iconos: define cada icono UNA vez en un sprite oculto al inicio del HTML: <svg style="display:none"><symbol id="ic-x" viewBox="0 0 24 24">…</symbol>…</svg> y reutilízalos con <svg><use href="#ic-x"></use></svg>. NUNCA repitas un path SVG.
- Máximo 12 imágenes. Marcado conciso, sin <div> innecesarios.
- COMPLETA SIEMPRE el bloque CSS: es preferible una web algo más corta pero con todo el CSS, que un HTML enorme sin estilos.

═══ ALCANCE Y PROFUNDIDAD ═══
Sitio COMPLETO de 7 a 9 secciones reales, con contenido específico y creíble (jamás Lorem Ipsum: inventa nombres, copy, precios y datos realistas en español). Adapta las secciones al negocio, incluyendo SIEMPRE:
1. Header fijo con logo, navegación por anclas (#) y botón CTA. Menú hamburguesa en móvil.
2. Hero potente: titular con propuesta de valor, subtítulo, 1-2 CTAs y elemento visual.
3. Barra de confianza (logos, valoraciones o métricas con números grandes).
4. Servicios/características en tarjetas con iconos SVG.
5. "Sobre nosotros" / historia con imagen.
6. Prueba social: 3+ testimonios con nombre, rol y avatar.
7. Sección destacada según el negocio: galería con lightbox, tabla de precios, proceso por pasos o estadísticas.
8. FAQ con acordeón.
9. Banda CTA de cierre.
10. Contacto: formulario (nombre, email, mensaje) + datos reales (dirección, teléfono, horario).
11. Footer multi-columna con enlaces, redes (iconos SVG) y copyright.

═══ TIENDA / VENTA DE PRODUCTOS ═══
Si el negocio vende productos o el usuario lo pide, incluye un catálogo de 6+ productos y TODA la interfaz de la tienda siguiendo la convención (tarjetas .product con data-*, botón #cart-toggle con #cart-count, panel #cart-drawer, checkout #checkout-whatsapp y #checkout-email). El comportamiento lo añadirá el JS; tú deja el HTML y el CSS (incluido el estilo del panel del carrito, oculto por defecto y visible con la clase "open").

═══ NIVEL VISUAL: AGENCIA PREMIUM CON PROFUNDIDAD 3D ═══
El listón es una agencia de diseño de primer nivel (referencia: motionsites.ai, Linear, Vercel, Stripe): tipografía grande y segura, mucho aire, capas con profundidad y movimiento con propósito, nunca plano ni genérico.
- Hero 3D: para negocios donde encaja una estética tech/premium (SaaS, startups, agencias, consultoras, estudios, inmobiliarias de alto standing, abogados, coaches/formación) añade en el hero un <canvas class="hero3d-canvas" data-hero3d aria-hidden="true"> (ver convención). Dale al hero: position:relative;overflow:hidden. Al canvas: position:absolute;inset:0;width:100%;height:100%;z-index:0;pointer-events:none. Y al contenido de texto/CTAs del hero: position:relative;z-index:1 (para que quede por encima). Para negocios donde no encaja (restaurantes, tiendas físicas, belleza, gimnasios...) NO lo incluyas: usa en su lugar una imagen/foto potente como siempre.
- Fondo del hero con profundidad incluso sin 3D: aplica al .hero un degradado radial sutil con el color de marca (2-3 radial-gradient() en capas usando color-mix(in srgb, var(--brand) X%, transparent) sobre --bg), para que tenga presencia aunque el canvas tarde en cargar.
- Tarjetas con inclinación 3D: añade la clase "tilt-3d" a 3-6 tarjetas clave (features, precios, o productos destacados) para profundidad interactiva al pasar el ratón (el JS ya lo gestiona; tú solo pones la clase).
- Micro-interacciones: sombras suaves en capas, transiciones con curvas cubic-bezier (nunca linear), hover states con elevación (translateY + shadow), nunca animaciones bruscas.

__CONV__

═══ SISTEMA DE DISEÑO ═══
- OBLIGATORIO: define en :root EXACTAMENTE estas variables (con estos nombres) y úsalas en todo el CSS, para que la web sea editable sin código después:
  --bg (fondo), --surface (tarjetas/superficies), --text (texto), --muted (texto secundario), --border, --brand (color principal/botones), --brand-2 (variante más oscura del principal), --on-brand (texto sobre el color principal, blanco o negro según contraste), --brand-soft (versión translúcida del principal para iconos), --radius (redondeo), --font-head (titulares) y --font-body (texto). Importa las fuentes con @import al principio.
- Paleta coherente con el sector, escala tipográfica, espaciados, sombras y transiciones, todo a partir de esas variables.
- Tipografía con Google Fonts vía @import (una familia para titulares, otra para texto).
- Estética moderna: jerarquía clara, aire, micro-interacciones, :hover y :focus-visible, sombras suaves, secciones diferenciadas.
- Mobile-first y 100% responsive (breakpoints 980px, 768px, 480px). El menú colapsa a hamburguesa.
- Respeta @media (prefers-reduced-motion: reduce).
- RESILIENCIA SIN JS: el contenido debe verse aunque el JS aún no haya cargado. Para las animaciones de aparición usa: .reveal{opacity:1} por defecto, y oculta SOLO cuando el body tenga la clase "js-ready": body.js-ready .reveal{opacity:0;transform:translateY(24px)} y body.js-ready .reveal.visible{opacity:1;transform:none;transition:.6s}. El panel del carrito (#cart-drawer) y el lightbox sí pueden estar ocultos por defecto.

═══ IMÁGENES ═══
- Fotos: https://picsum.photos/seed/PALABRA/ANCHO/ALTO con PALABRA descriptiva y única por imagen. Son fotos reales y estables.
- Iconos y decoración: SVG en línea. Avatares: picsum con seeds distintos. NUNCA emojis como iconos.
- Toda <img> con loading="lazy" y alt descriptivo.

═══ REGLAS DURAS ═══
- Solo HTML y CSS puros. PROHIBIDO Tailwind, Bootstrap, jQuery, React o cualquier CDN/librería (excepto Google Fonts vía @import e imágenes de picsum). Sin <script>.
- HTML semántico (header, nav, main, section, article, footer), un único <h1>, aria-labels donde aporten, buen contraste. Sin enlaces rotos (anclas reales o "#").
SYSTEM);
    }

    /**
     * FASE 2 — Prompt del sistema para generar el JavaScript sobre el HTML ya creado.
     */
    private function jsSystemPrompt(): string
    {
        return str_replace('__CONV__', $this->uiConvention(), <<<'SYSTEM'
Eres un desarrollador front-end senior. Te paso el HTML de una web ya construida y debes escribir TODO su JavaScript en vanilla JS (sin librerías, sin <script>, sin CDNs). Debe funcionar tal cual al inyectarse al final del <body>, SIN errores en consola.

═══ FORMATO DE SALIDA (OBLIGATORIO) ═══
Devuelve EXACTAMENTE este único bloque, sin texto fuera de él:
[[[JS]]]
(todo el JavaScript)

═══ QUÉ IMPLEMENTAR (según la convención; comprueba SIEMPRE que el elemento existe antes de usarlo) ═══
- PRIMERA línea: document.body.classList.add('js-ready');
- Menú móvil: #menu-toggle alterna la clase "open" en #main-nav (y se cierra al pulsar un enlace).
- Scroll suave en los enlaces a[href^="#"].
- Sombra/estado del header al hacer scroll: añade la clase "scrolled" a #site-header cuando scrollY>10.
- Animaciones de aparición: IntersectionObserver que añade "visible" a cada ".reveal" al entrar en viewport.
- Acordeón FAQ: al pulsar .faq-q alterna "open" en su .faq-item (cierra los demás).
- Carrusel de testimonios (.testi-slider) si existe: .testi-prev/.testi-next desplazan .testi-track; con autoplay suave.
- Lightbox: al pulsar una .gallery-img, ábrela en un overlay a pantalla completa creado por JS (cierra al hacer clic o con Escape).
- Formulario #contact-form: valida nombre/email/mensaje, evita el envío real (preventDefault), muestra #form-success y resetea.
- Botón #to-top: aparece al bajar y hace scroll suave arriba.
- Inclinación 3D (solo si hay elementos .tilt-3d): en mousemove sobre cada tarjeta, calcula la posición del cursor relativa al centro (-0.5 a 0.5 en ambos ejes) y fija las variables CSS --tilt-x/--tilt-y (grados, rango ±8-10deg, eje invertido según corresponda para que se incline "hacia" el cursor); en mouseleave, resetea ambas a 0deg. Solo si matchMedia('(hover:hover)') y no hay prefers-reduced-motion.
- Si ves un <canvas class="hero3d-canvas"> o con data-hero3d: IGNÓRALO POR COMPLETO, no escribas nada de JS para él (ni 2D ni WebGL). Se inicializa solo con un motor aparte que se añade automáticamente después de tu código.

═══ TIENDA (solo si en el HTML hay elementos .product) ═══
- Al PRINCIPIO define, fáciles de editar:
  const WHATSAPP_NUMERO = '34600000000'; // solo dígitos con prefijo de país, sin '+'
  const EMAIL_PEDIDOS  = 'pedidos@negocio.com';
  Si en el "Encargo original" el usuario indicó un teléfono/WhatsApp o un email, ÚSALOS aquí (formatea el teléfono como dígitos con prefijo, sin '+').
- Lee los productos del DOM: document.querySelectorAll('.product') y sus data-id/data-name/data-price/data-img. NO inventes un array aparte: usa los del HTML.
- Carrito: .add-to-cart añade el producto de su .product; persiste en localStorage (clave 'pp_cart'); actualiza #cart-count.
- Panel #cart-drawer: #cart-toggle lo abre (clase "open"), #cart-close y #cart-overlay lo cierran. Pinta los artículos en #cart-items con cantidad (+/−) y botón eliminar; recalcula y muestra #cart-total (formato "12,90 €").
- Checkout #checkout-whatsapp: abre https://wa.me/${WHATSAPP_NUMERO}?text=... con el detalle (producto x cantidad, subtotales y TOTAL) usando encodeURIComponent. #checkout-email: abre un mailto:${EMAIL_PEDIDOS} con asunto y cuerpo equivalentes. Si el carrito está vacío, avisa y no continúes.

__CONV__
SYSTEM);
    }

    /**
     * Genera metadatos SEO para una web existente.
     */
    public function generateSeoMeta(string $html, string $businessHint = ''): array
    {
        $system = <<<SYSTEM
Eres un experto en SEO. Analiza el contenido HTML de una web y genera metadatos SEO optimizados.
Devuelve ÚNICAMENTE JSON válido con esta estructura:
{
  "title": "...título SEO de máximo 60 caracteres...",
  "description": "...meta description de máximo 155 caracteres, con llamada a la acción...",
  "keywords": "...5-8 keywords separadas por comas...",
  "og_title": "...título para redes sociales...",
  "og_description": "...descripción para redes sociales...",
  "schema": {...objeto JSON-LD LocalBusiness o WebSite según el contenido...}
}
SYSTEM;

        $content  = $businessHint ? "Pista del negocio: {$businessHint}\n\n" : '';
        $content .= "HTML: " . substr($html, 0, 3000);

        // El SEO es una tarea ligera: usa siempre el modelo económico.
        $this->activeModel = $this->freeModel;

        $text   = $this->requestText($system, [['role' => 'user', 'content' => $content]], 1024);
        $parsed = $this->parseJson($text);

        if ($parsed === null) {
            throw new \Exception('La IA no pudo generar el SEO. Inténtalo de nuevo.');
        }

        return $parsed;
    }

    /**
     * Hace la llamada a la API en STREAMING (con guardián de presupuesto, registro
     * de gasto y reintento ante saturación) y devuelve el TEXTO crudo acumulado.
     *
     * El streaming es imprescindible para sitios grandes: la conexión recibe el
     * texto por trozos, evitando los timeouts de lectura (y los gateway timeouts
     * de nginx/php-fpm en producción) que cortarían una generación larga.
     */
    private function requestText(string $system, array $messages, int $maxTokens, int $attempt = 1): string
    {
        // Guardián de presupuesto: si se ha alcanzado el tope, no se hacen más
        // llamadas (nunca se gasta de más sin permiso explícito).
        if (!$this->budget->isWithinBudget()) {
            Log::warning('IA pausada: presupuesto alcanzado', [
                'motivo'       => $this->budget->blockedReason(),
                'gasto_hoy'    => $this->budget->todayUsd(),
                'gasto_mes'    => $this->budget->monthToDateUsd(),
                'tope_diario'  => $this->budget->dailyBudgetUsd(),
                'tope_mensual' => $this->budget->monthlyBudgetUsd(),
            ]);
            throw new \Exception('El servicio de IA está en pausa temporal. Vuelve a intentarlo más tarde o escribe a ' . config('pagepolis.support_email') . '.');
        }

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->withOptions(['stream' => true, 'read_timeout' => 120])
              ->timeout(600)
              ->post('https://api.anthropic.com/v1/messages', [
                  'model'      => $this->model(),
                  'max_tokens' => $maxTokens,
                  // El prompt de sistema es largo y estático (no cambia entre
                  // llamadas): marcarlo con cache_control lo cachea 5 min en la
                  // API (lecturas a ~10% del precio). Ahorra en reintentos, en
                  // ráfagas de ediciones de un mismo usuario y entre usuarios
                  // concurrentes, sin cambiar en nada la respuesta.
                  'system'     => [[
                      'type'          => 'text',
                      'text'          => $system,
                      'cache_control' => ['type' => 'ephemeral'],
                  ]],
                  'messages'   => $messages,
                  'stream'     => true,
              ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            if ($attempt === 1) {
                sleep(2);
                return $this->requestText($system, $messages, $maxTokens, 2);
            }
            throw new \Exception('La IA tardó demasiado en responder. Inténtalo de nuevo en un momento.');
        }

        if ($response->failed()) {
            $status = $response->status();

            // Reintentar una vez si el servicio está saturado.
            if ($status === 529 && $attempt === 1) {
                sleep(3);
                return $this->requestText($system, $messages, $maxTokens, 2);
            }

            throw new \Exception(match (true) {
                $status === 401 => 'La configuración de la IA no es válida. Escribe a ' . config('pagepolis.support_email') . '.',
                $status === 429 => 'La IA está recibiendo demasiadas peticiones. Espera un momento e inténtalo de nuevo.',
                $status === 529 => 'La IA está saturada ahora mismo. Inténtalo en unos minutos.',
                $status >= 500  => 'El servicio de IA no está disponible temporalmente. Inténtalo en unos minutos.',
                default         => 'No se pudo conectar con la IA. Inténtalo de nuevo.',
            });
        }

        [$text, $inputTokens, $outputTokens] = $this->readStream($response);

        // Registrar el consumo real para que el guardián de presupuesto lo cuente.
        if ($inputTokens > 0 || $outputTokens > 0) {
            $this->budget->record($this->model(), $inputTokens, $outputTokens);
        }

        return $text;
    }

    /**
     * Lee la respuesta SSE de Anthropic acumulando el texto y los tokens usados.
     *
     * @return array{0:string,1:int,2:int} [texto, input_tokens, output_tokens]
     */
    private function readStream(\Illuminate\Http\Client\Response $response): array
    {
        $body = $response->toPsrResponse()->getBody();

        $buffer = '';
        $text   = '';
        $inputTokens = 0;
        $outputTokens = 0;

        while (!$body->eof()) {
            $buffer .= $body->read(8192);

            while (($pos = strpos($buffer, "\n")) !== false) {
                $line   = trim(substr($buffer, 0, $pos));
                $buffer = substr($buffer, $pos + 1);

                if ($line === '' || ! str_starts_with($line, 'data:')) {
                    continue;
                }

                $payload = trim(substr($line, 5));
                if ($payload === '' || $payload === '[DONE]') {
                    continue;
                }

                $evt = json_decode($payload, true);
                if (! is_array($evt)) {
                    continue;
                }

                switch ($evt['type'] ?? '') {
                    case 'message_start':
                        // Con prompt caching, la entrada se reparte en tres cifras
                        // con precios distintos: normal (×1), escritura de caché
                        // (×1,25) y lectura de caché (×0,10). Se convierten aquí a
                        // "tokens equivalentes" a tarifa normal para que el guardián
                        // de presupuesto (que tarifica plano) siga contando bien.
                        $usage       = $evt['message']['usage'] ?? [];
                        $inputTokens = (int) ($usage['input_tokens'] ?? 0)
                            + (int) ceil((int) ($usage['cache_creation_input_tokens'] ?? 0) * 1.25)
                            + (int) ceil((int) ($usage['cache_read_input_tokens'] ?? 0) * 0.10);
                        break;
                    case 'content_block_delta':
                        $text .= $evt['delta']['text'] ?? '';
                        break;
                    case 'message_delta':
                        $outputTokens = (int) ($evt['usage']['output_tokens'] ?? $outputTokens);
                        break;
                    case 'error':
                        throw new \Exception('La IA devolvió un error durante la generación. Inténtalo de nuevo.');
                }
            }
        }

        return [$text, $inputTokens, $outputTokens];
    }

    /**
     * Parsea la respuesta por bloques [[[HTML]]] / [[[CSS]]] / [[[JS]]] / [[[DESC]]]
     * / [[[CHANGED]]]. Mucho más robusto que JSON para HTML/CSS/JS grandes (evita
     * los problemas de escapado que rompían las generaciones largas).
     *
     * @return array<string,string> claves: html, css, js, desc, changed
     */
    private function parseBlocks(string $text): array
    {
        // Quita posibles vallas de código markdown que el modelo añada por fuera.
        $text = trim($text);

        $pattern = '/\[\[\[(HTML|CSS|JS|DESC|CHANGED)\]\]\]/i';
        if (!preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $blocks = [];
        $count  = count($matches[0]);
        for ($i = 0; $i < $count; $i++) {
            $name  = strtolower($matches[1][$i][0]);
            $start = $matches[0][$i][1] + strlen($matches[0][$i][0]);
            $end   = ($i + 1 < $count) ? $matches[0][$i + 1][1] : strlen($text);
            $value = trim(substr($text, $start, $end - $start));
            // Limpia vallas markdown al inicio/fin de un bloque (```html ... ```).
            $value = preg_replace('/^```[a-zA-Z]*\s*/', '', $value);
            $value = preg_replace('/\s*```$/', '', $value);
            $blocks[$name] = trim($value);
        }

        return $blocks;
    }

    /**
     * Parsea una respuesta JSON (usado por el SEO). Limpia vallas markdown y
     * extrae el primer objeto JSON. Devuelve null si no es válido.
     */
    private function parseJson(string $text): ?array
    {
        $text = preg_replace('/^```(?:json)?\s*/m', '', $text);
        $text = preg_replace('/\s*```$/m', '', $text);
        if (preg_match('/\{[\s\S]*\}/m', $text, $matches)) {
            $text = $matches[0];
        }

        $parsed = json_decode(trim($text), true);

        return is_array($parsed) ? $parsed : null;
    }
}
