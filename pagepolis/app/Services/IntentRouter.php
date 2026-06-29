<?php

namespace App\Services;

use App\Models\Project;

/**
 * Router de intenciones LOCAL (sin IA, sin tokens). Interpreta lo que el usuario
 * pide en el chat y, si la tarea está "programada" (aunque la pida de mil formas
 * distintas), aplica el cambio al instante de forma determinista. Si no la
 * reconoce, devuelve null y el flujo cae en la IA (gasto mínimo).
 *
 * Aprovecha que las plantillas y las webs generadas usan los MISMOS tokens CSS
 * (--bg, --brand, --text, --radius, --font-*) y la misma convención de clases.
 */
class IntentRouter
{
    /**
     * @return array{html:string,css:string,js:string,description:string,changed:array<int,string>}|null
     */
    public function handle(Project $project, string $message): ?array
    {
        $orig = trim($message);
        $t    = $this->norm($message);
        if ($t === '') {
            return null;
        }

        foreach (['whatsapp', 'orderEmail', 'businessName', 'font', 'hideSection', 'rounded', 'color'] as $intent) {
            $result = $this->{$intent}($project, $t, $orig);
            if ($result !== null) {
                return $result + ['html' => $project->html ?? '', 'css' => $project->css ?? '', 'js' => $project->js ?? ''];
            }
        }

        // No hay cambio aplicable, pero quizá el usuario saluda, pide ayuda o pide
        // algo a medias: respondemos GRATIS (pregunta de vuelta) en vez de mandarlo
        // a la IA y arriesgar un cambio equivocado.
        $reply = $this->helpOrClarify($t, $orig);
        if ($reply !== null) {
            return [
                'html'        => $project->html ?? '',
                'css'         => $project->css ?? '',
                'js'          => $project->js ?? '',
                'description' => $reply,
                'changed'     => [],   // respuesta de chat, no cambia la web
            ];
        }

        return null;
    }

    /**
     * Respuestas GRATIS a "errores" típicos al dirigirse a la IA: saludos, pedir
     * ayuda, o peticiones incompletas (sin color, sin nombre, sin número…).
     * Pregunta de vuelta en lugar de adivinar. Devuelve el texto o null.
     */
    private function helpOrClarify(string $t, string $orig): ?string
    {
        // Saludo / ayuda / no sabe qué pedir.
        if ($this->has($t, ['hola', 'buenas', 'buenos dias', 'buenas tardes', 'hey', 'holaa', 'ayuda', 'help', 'socorro', 'que puedes hacer', 'que sabes hacer', 'que puedo hacer', 'que puedo pedir', 'no se que', 'no se como', 'que pongo', 'que hago', 'opciones', 'sugerencias', 'como funciona', 'como va esto', 'que pongo aqui'])) {
            return $this->capabilities();
        }

        $verb       = $this->has($t, ['pon', 'ponlo', 'ponla', 'ponme', 'cambia', 'cambiar', 'cambiame', 'haz', 'hazlo', 'hazme', 'quiero', 'quisiera', 'dale', 'usa', 'aplica', 'modifica', 'mete', 'quita', 'que sea']);
        $noColorOps = !$this->has($t, ['quita', 'elimina', 'borra', 'degradado', 'gradiente', 'imagen', 'imagenes', 'foto', 'video', 'transparente', 'sombra', 'difumin']);

        // Fondo sin decir color.
        if ($verb && $noColorOps && $this->has($t, ['fondo', 'background']) && Color::detect($t) === null) {
            return '¿De qué color quieres el fondo? Dime un color, por ejemplo: azul, verde, rojo, negro o gris.';
        }
        // Color principal / botones sin color.
        if ($verb && $noColorOps && $this->has($t, ['principal', 'primario', 'marca', 'boton', 'botones']) && Color::detect($t) === null) {
            return '¿Qué color quieres para los botones / el color principal? Por ejemplo: azul, rojo o verde.';
        }
        // Color del texto sin color.
        if ($verb && $noColorOps && $this->has($t, ['color del texto', 'color de la letra', 'texto de color', 'letra de color']) && Color::detect($t) === null) {
            return '¿De qué color quieres el texto? Por ejemplo: negro, gris o blanco.';
        }
        // Solo un color, sin decir dónde.
        if (Color::detect($t) !== null && str_word_count($t) <= 3 && !$this->has($t, ['fondo', 'texto', 'boton', 'botones', 'marca', 'letra', 'principal'])) {
            return '¿Dónde quieres ese color: en el fondo, en los botones o en el texto? Por ejemplo: «pon el fondo azul».';
        }
        // Cambiar el nombre sin decir cuál.
        if ($this->has($t, ['cambia el nombre', 'cambiar el nombre', 'el nombre a', 'renombra', 'se llama', 'ponle de nombre', 'nombre del negocio', 'que se llame']) && $this->extractName($orig) === null) {
            return '¿Qué nombre quieres ponerle? Escríbelo así: «cambia el nombre a Mi Negocio».';
        }
        // Tipografía sin una fuente reconocida.
        if ($verb && $this->has($t, ['fuente', 'tipografia', 'tipo de letra']) && !$this->mentionsKnownFont($t)) {
            return '¿Qué tipografía quieres? Puedo poner: Poppins, Montserrat, Roboto, Inter, Lato, Oswald o Playfair.';
        }
        // WhatsApp sin número.
        if ($this->has($t, ['whatsapp', 'wasap', 'watsap', 'guasap', 'whatsap', 'whats app']) && !preg_match('/\d{6,}/', preg_replace('/[\s().\-]/', '', $orig))) {
            return '¿Cuál es tu número de WhatsApp para los pedidos? Escríbelo con prefijo, por ejemplo: +34 600 123 456.';
        }

        return null;
    }

    private function capabilities(): string
    {
        return '¡Hola! Dime con tus palabras qué quieres cambiar y lo hago al momento. Por ejemplo: '
            . '«pon el fondo azul», «cambia el color principal a verde», «cambia la tipografía a Poppins», '
            . '«mi WhatsApp es +34 600 123 456», «cambia el nombre a Bar Pepe» o «quita la sección de testimonios». '
            . 'También puedes adjuntar una foto (tu carta o tu logo) con el clip 📎. '
            . 'Para crear algo nuevo o pedir cosas más complejas, descríbelo y la IA se encarga.';
    }

    private function mentionsKnownFont(string $t): bool
    {
        foreach (array_keys($this->fontMap()) as $key) {
            if (str_contains($t, $key)) {
                return true;
            }
        }
        return false;
    }

    /* ─────────────────────────── Intenciones ─────────────────────────── */

    private function color(Project $p, string $t, string $orig): ?array
    {
        $hex = Color::detect($t);
        if (!$hex) {
            return null;
        }

        $css = $p->css ?? '';

        // ¿Qué parte? (fondo / texto / marca-botones)
        $isBg    = $this->has($t, ['fondo', 'background', 'de fondo']);
        $isText  = $this->has($t, ['texto', 'letra', 'letras', 'tipografia del texto']);
        $isBrand = $this->has($t, ['marca', 'principal', 'primario', 'boton', 'botones', 'acento', 'enlace', 'enlaces', 'link', 'links']);
        $isAll   = $this->has($t, ['todo', 'general', 'tema', 'toda la web', 'colores', 'la pagina', 'el sitio', 'la web entera']);
        // Verbo de cambio: confirma la intención cuando no se nombra la parte.
        $verb    = $this->has($t, ['pon', 'ponlo', 'ponla', 'cambia', 'cambiar', 'haz', 'hazlo', 'quiero', 'quisiera', 'dale', 'usa', 'aplica', 'color']);

        if ($isBg) {
            $newCss = $this->changeBackground($css, $hex);
            return $newCss === null ? null   // inseguro -> lo hace la IA (coherente)
                : ['css' => $newCss, 'description' => 'He cambiado el color de fondo. ✨', 'changed' => ['css']];
        }

        $o     = $this->readOverrides($css);
        $vars  = $o['vars'];
        $rules = $o['rules'];

        if ($isText && !$isBrand) {
            if (!str_contains($css, '--text')) {
                return null;   // sin token estándar de texto -> lo hace la IA
            }
            $vars['--text'] = $hex;
            $desc = 'He cambiado el color del texto.';
        } elseif ($isBrand || $isAll || $verb) {
            if (!str_contains($css, '--brand')) {
                return null;   // sin token estándar de marca -> lo hace la IA
            }
            $vars['--brand']      = $hex;
            $vars['--brand-2']    = Color::darken($hex, 0.12);
            $vars['--on-brand']   = Color::contrast($hex);
            $vars['--brand-soft'] = $this->soft($hex);
            $desc = 'He cambiado el color principal de la web.';
        } else {
            return null; // hay un color pero no queda claro qué cambiar -> IA
        }

        return ['css' => $this->writeOverrides($css, $vars, $rules), 'description' => $desc . ' ✨', 'changed' => ['css']];
    }

    /**
     * Cambia el color de fondo de forma SEGURA en cualquier web:
     * - Plantillas (motor base): usa las variables/secciones conocidas.
     * - Otras webs: detecta la variable de fondo del sitio (aunque se llame
     *   --cream, --bg2…) y la cambia, ajustando el texto al contraste. Si hay
     *   conflicto (una variable hace de fondo Y de texto), devuelve null para que
     *   lo resuelva la IA y no quede una web a medias.
     */
    private function changeBackground(string $css, string $hex): ?string
    {
        if ($this->isTemplateEngine($css)) {
            $o = $this->readOverrides($css);
            $this->applyBackground($o['vars'], $hex);
            $rules = $this->mergeRule($o['rules'], 'pp-bg', $this->bgRules());
            return $this->writeOverrides($css, $o['vars'], $rules);
        }

        $colors   = $this->rootColorVars($css);
        if (empty($colors)) {
            return null;
        }
        $bgVars   = [];
        $textVars = [];
        foreach ($colors as $name => $val) {
            $b = $this->usedAs($css, $name, 'bg');
            $f = $this->usedAs($css, $name, 'fg');
            if ($b && $f) {
                return null;            // conflicto de rol -> IA
            }
            if ($b) {
                $bgVars[] = $name;
            } elseif ($f) {
                $textVars[] = $name;
            }
        }
        if (empty($bgVars)) {
            return null;                 // no hay variable de fondo clara -> IA
        }

        $o        = $this->readOverrides($css);
        $vars     = $o['vars'];
        $contrast = Color::contrast($hex);
        foreach ($bgVars as $n) {
            $vars[$n] = $hex;
        }
        foreach ($textVars as $n) {
            $vars[$n] = $contrast;
        }
        $rules = $this->mergeRule($o['rules'], 'pp-bg', 'body{background:' . $hex . ';color:' . $contrast . '}');
        return $this->writeOverrides($css, $vars, $rules);
    }

    /** @return array<string,string> variables de :root cuyo valor es un color hex */
    private function rootColorVars(string $css): array
    {
        $vars = [];
        if (preg_match_all('/:root\s*\{([^}]*)\}/s', $css, $blocks)) {
            foreach ($blocks[1] as $b) {
                foreach (explode(';', $b) as $d) {
                    if (!str_contains($d, ':')) {
                        continue;
                    }
                    [$k, $v] = explode(':', $d, 2);
                    $k = trim($k);
                    $v = trim($v);
                    if (str_starts_with($k, '--') && preg_match('/^#[0-9a-f]{3,8}$/i', $v)) {
                        $vars[$k] = $v;
                    }
                }
            }
        }
        return $vars;
    }

    /** ¿La variable se usa como fondo ('bg') o como color de texto ('fg')? */
    private function usedAs(string $css, string $name, string $role): bool
    {
        $q = preg_quote($name, '/');
        if ($role === 'bg') {
            return (bool) preg_match('/background(?:-color)?\s*:\s*[^;}]*var\(\s*' . $q . '\s*\)/i', $css);
        }
        return (bool) preg_match('/(?<![\w-])color\s*:\s*[^;}]*var\(\s*' . $q . '\s*\)/i', $css);
    }

    /** ¿La web usa el motor base de las plantillas (clases .hero, .story…)? */
    private function isTemplateEngine(string $css): bool
    {
        return str_contains($css, 'Base compartida de las plantillas');
    }

    /** Reglas para que el cambio de fondo se vea en hero y secciones de acento. */
    private function bgRules(): string
    {
        return '.hero{background:var(--bg)!important}'
            . '.story,.gallery-sec,.ventajas,.proceso,.tienda-sec,.servicios-band,.stats,.faq-sec,.contact-sec{background:var(--bg)!important}';
    }

    private function applyBackground(array &$vars, string $hex): void
    {
        $vars['--bg'] = $hex;
        $dark = Color::contrast($hex) === '#ffffff';
        $vars['--text']      = $dark ? '#f3f4f6' : '#1a1a1a';
        $vars['--muted']     = $dark ? '#9aa0b0' : '#6b7280';
        $vars['--surface']   = $dark ? Color::lighten($hex, 0.08) : '#ffffff';
        $vars['--border']    = $dark ? Color::lighten($hex, 0.14) : '#e6e6ee';
        $vars['--header-bg'] = $dark ? $this->rgba($hex, 0.92) : 'rgba(255,255,255,.92)';
    }

    private function rounded(Project $p, string $t, string $orig): ?array
    {
        if (!$this->has($t, ['redonde', 'esquina', 'bordes', 'cuadrad', 'rectos'])) {
            return null;
        }
        $radius = null;
        if ($this->has($t, ['cuadrad', 'rectos', 'sin redonde'])) {
            $radius = '2px';
        } elseif ($this->has($t, ['muy redonde', 'mas redonde', 'redondead', 'redondo', 'redondos'])) {
            $radius = $this->has($t, ['muy', 'mucho']) ? '28px' : '20px';
        } elseif ($this->has($t, ['menos redonde'])) {
            $radius = '6px';
        }
        if ($radius === null) {
            return null;
        }
        $css = $p->css ?? '';
        if (!str_contains($css, '--radius')) {
            return null;   // la web no usa el token -> que lo haga la IA
        }
        $o = $this->readOverrides($css);
        $o['vars']['--radius'] = $radius;
        return ['css' => $this->writeOverrides($css, $o['vars'], $o['rules']), 'description' => 'He ajustado el redondeo de los bordes. ✨', 'changed' => ['css']];
    }

    private function font(Project $p, string $t, string $orig): ?array
    {
        if (!$this->has($t, ['fuente', 'tipografia', 'tipo de letra', 'font', 'la letra'])) {
            return null;
        }
        $picked = null;
        foreach ($this->fontMap() as $key => $family) {
            if (str_contains($t, $key)) { $picked = $family; break; }
        }
        if (!$picked) {
            return null;
        }
        $css  = $p->css ?? '';
        if (!str_contains($css, '--font-body') && !str_contains($css, '--font-head')) {
            return null;   // la web no usa tokens de fuente -> que lo haga la IA
        }
        $importFamily = str_replace(' ', '+', $picked) . ':wght@400;500;600;700;800';
        $css  = $this->writeFontImport($css, "https://fonts.googleapis.com/css2?family={$importFamily}&display=swap");
        $o    = $this->readOverrides($css);
        $serif = in_array($picked, ['Playfair Display', 'Merriweather', 'Cormorant Garamond', 'Bitter'], true);
        $stack = "'{$picked}'," . ($serif ? 'serif' : 'system-ui,sans-serif');
        $o['vars']['--font-head'] = $stack;
        $o['vars']['--font-body'] = $stack;
        return ['css' => $this->writeOverrides($css, $o['vars'], $o['rules']), 'description' => "He cambiado la tipografía a {$picked}. ✨", 'changed' => ['css']];
    }

    /** @return array<string,string> palabra clave (normalizada) => familia de fuente */
    private function fontMap(): array
    {
        return [
            'poppins' => 'Poppins', 'montserrat' => 'Montserrat', 'roboto' => 'Roboto',
            'lato' => 'Lato', 'inter' => 'Inter', 'raleway' => 'Raleway', 'oswald' => 'Oswald',
            'playfair' => 'Playfair Display', 'merriweather' => 'Merriweather', 'nunito' => 'Nunito',
            'open sans' => 'Open Sans', 'opensans' => 'Open Sans', 'sora' => 'Sora', 'rubik' => 'Rubik',
            'work sans' => 'Work Sans', 'manrope' => 'Manrope', 'jost' => 'Jost', 'bitter' => 'Bitter',
            'space grotesk' => 'Space Grotesk', 'archivo' => 'Archivo', 'cormorant' => 'Cormorant Garamond',
        ];
    }

    private function whatsapp(Project $p, string $t, string $orig): ?array
    {
        if (!$this->has($t, ['whatsapp', 'wasap', 'whats app', 'whatsap', 'numero de pedido', 'numero para pedido', 'telefono de pedido'])) {
            return null;
        }
        if (!preg_match('/(\+?\d[\d\s().\-]{6,}\d)/', $orig, $m)) {
            return null;
        }
        $digits = preg_replace('/\D/', '', $m[1]);
        if (strlen($digits) < 8) {
            return null;
        }
        if (strlen($digits) === 9) {
            $digits = '34' . $digits;   // España por defecto si dan 9 dígitos
        }
        $js = $p->js ?? '';
        $new = preg_replace("/(WHATSAPP_NUMERO\s*=\s*')[^']*(')/", '${1}' . $digits . '${2}', $js, 1, $count);
        if (!$count) {
            return null;
        }
        return ['js' => $new, 'description' => "He puesto tu WhatsApp de pedidos: +{$digits}. ✨", 'changed' => ['js']];
    }

    private function orderEmail(Project $p, string $t, string $orig): ?array
    {
        if (!$this->has($t, ['email', 'correo', 'e-mail', 'mail'])) {
            return null;
        }
        if (!$this->has($t, ['pedido', 'pedidos', 'compras', 'tienda'])) {
            return null;
        }
        if (!preg_match('/([^\s@]+@[^\s@]+\.[^\s@]+)/', $orig, $m)) {
            return null;
        }
        $js = $p->js ?? '';
        $new = preg_replace("/(EMAIL_PEDIDOS\s*=\s*')[^']*(')/", '${1}' . $m[1] . '${2}', $js, 1, $count);
        if (!$count) {
            return null;
        }
        return ['js' => $new, 'description' => "He puesto el email de pedidos: {$m[1]}. ✨", 'changed' => ['js']];
    }

    private function businessName(Project $p, string $t, string $orig): ?array
    {
        if (!$this->has($t, ['nombre del negocio', 'cambia el nombre', 'cambiar el nombre', 'renombra', 'se llama', 'el nombre a', 'nombre por', 'titulo del sitio', 'cambia el titulo', 'ponle de nombre', 'que se llame'])) {
            return null;
        }
        $newName = $this->extractName($orig);
        if ($newName === null) {
            return null;
        }
        $html = $p->html ?? '';
        // El nombre suele estar en el logo: <... class="logo">NOMBRE</...>
        if (!preg_match('/class="logo"[^>]*>(.*?)<\/(?:a|div|span|h1|p)>/s', $html, $lm)) {
            return null;
        }
        $current = trim($lm[1]);
        if ($current === '' || strip_tags($current) === '') {
            return null;
        }
        // Reemplaza TODAS las apariciones exactas del nombre actual (logo, footer, copyright).
        $newHtml = str_replace($current, $this->esc($newName), $html);
        return ['html' => $newHtml, 'description' => "He cambiado el nombre a «{$newName}». ✨", 'changed' => ['html']];
    }

    /** Extrae el nombre nuevo de la frase (patrones precisos). null si no hay. */
    private function extractName(string $orig): ?string
    {
        $patterns = [
            '/(?:se\s+llame?|ll[aá]mal[ao]|nombre\s+es)\s+["“]?(.+?)["”]?$/iu',
            '/nombre\s+(?:a|por|de)\s+["“]?(.+?)["”]?$/iu',
            '/(?:renombra\w*|t[ií]tulo)\b.*?\b(?:a|por|es)\s+["“]?(.+?)["”]?$/iu',
            '/\b(?:a|por)\s+["“]?(.{2,60})["”]?$/iu',
        ];
        foreach ($patterns as $re) {
            if (preg_match($re, $orig, $m)) {
                $name = trim($m[1], " \t\"“”'.");
                return $name !== '' ? $name : null;
            }
        }
        return null;
    }

    private function hideSection(Project $p, string $t, string $orig): ?array
    {
        if (!$this->has($t, ['quita', 'quitar', 'elimina', 'eliminar', 'borra', 'borrar', 'oculta', 'ocultar', 'no quiero', 'fuera', 'sin la seccion', 'quitame'])) {
            return null;
        }
        // mapa: palabras del usuario -> marcador que identifica la <section>
        $map = [
            'testi-slider'  => ['testimonio', 'opiniones', 'opinion', 'resena', 'resenas', 'valoracion', 'valoraciones', 'reseña'],
            'gallery-grid'  => ['galeria', 'fotos', 'imagenes', 'portfolio', 'trabajos'],
            'price-grid'    => ['precio', 'precios', 'tarifa', 'tarifas', 'planes', 'cuotas'],
            'faq-item'      => ['faq', 'preguntas', 'dudas', 'frecuentes'],
            'contact-form'  => ['contacto', 'formulario', 'contactar'],
            'stats-grid'    => ['estadistica', 'estadisticas', 'numeros', 'cifras', 'metricas'],
            'feature-grid'  => ['servicios', 'caracteristicas', 'features'],
            'story-grid'    => ['sobre nosotros', 'sobre mi', 'historia', 'quienes somos', 'nosotros'],
            'products'      => ['tienda', 'productos', 'catalogo', 'carta'],
        ];
        $marker = null;
        foreach ($map as $mk => $words) {
            if ($this->has($t, $words)) { $marker = $mk; break; }
        }
        if (!$marker) {
            return null;
        }
        // Seguridad anti-borrado erróneo: los apartados ambiguos (precios, servicios,
        // productos, contacto, nosotros…) solo se quitan si el usuario dice claramente
        // "sección/apartado/bloque". Los inequívocos (testimonios, galería, FAQ) no.
        $unambiguous = ['testi-slider', 'gallery-grid', 'faq-item'];
        $sectionCue  = $this->has($t, ['seccion', 'apartado', 'bloque', 'parte', 'zona', 'el modulo']);
        if (!in_array($marker, $unambiguous, true) && !$sectionCue) {
            return null;
        }
        $html = $p->html ?? '';
        $removed = false;
        $newHtml = preg_replace_callback('/<section\b[^>]*>.*?<\/section>/s', function ($m) use ($marker, &$removed) {
            if (!$removed && str_contains($m[0], $marker)) {
                $removed = true;
                return '';
            }
            return $m[0];
        }, $html);
        if (!$removed) {
            return null;
        }
        return ['html' => $newHtml, 'description' => 'He quitado esa sección de la web. ✨', 'changed' => ['html']];
    }

    /* ─────────────────────────── Overrides CSS ─────────────────────────── */

    /**
     * Lee el bloque de overrides gestionado: variables (:root) y reglas extra.
     * @return array{vars:array<string,string>,rules:string}
     */
    private function readOverrides(string $css): array
    {
        if (!preg_match('/\/\* PP-OVR-START \*\/(.*?)\/\* PP-OVR-END \*\//s', $css, $m)) {
            return ['vars' => [], 'rules' => ''];
        }
        $block = $m[1];
        $vars  = [];
        $rules = trim($block);
        if (preg_match('/:root\{(.*?)\}/s', $block, $rm)) {
            foreach (explode(';', $rm[1]) as $decl) {
                if (str_contains($decl, ':')) {
                    [$k, $v] = explode(':', $decl, 2);
                    $vars[trim($k)] = trim($v);
                }
            }
            $rules = trim(str_replace($rm[0], '', $block));
        }
        return ['vars' => $vars, 'rules' => $rules];
    }

    private function writeOverrides(string $css, array $vars, string $rules = ''): string
    {
        $css = preg_replace('/\n*\/\* PP-OVR-START \*\/.*?\/\* PP-OVR-END \*\/\n*/s', '', $css);
        if (empty($vars) && trim($rules) === '') {
            return $css;
        }
        $block = "\n\n/* PP-OVR-START */\n";
        if (!empty($vars)) {
            $decls = '';
            foreach ($vars as $k => $v) {
                $decls .= "{$k}:{$v};";
            }
            $block .= ":root{" . $decls . "}\n";
        }
        if (trim($rules) !== '') {
            $block .= trim($rules) . "\n";
        }
        $block .= "/* PP-OVR-END */\n";
        return rtrim($css) . $block;
    }

    /** Inserta/reemplaza un grupo de reglas etiquetado dentro del string de reglas. */
    private function mergeRule(string $rules, string $tag, string $css): string
    {
        $rules = preg_replace('/\/\*' . preg_quote($tag, '/') . '\*\/.*?\/\*\/' . preg_quote($tag, '/') . '\*\//s', '', $rules);
        return trim($rules . "\n/*{$tag}*/" . $css . "/*/{$tag}*/");
    }

    private function writeFontImport(string $css, string $url): string
    {
        $css = preg_replace('/\/\* PP-FONT-START \*\/.*?\/\* PP-FONT-END \*\/\n*/s', '', $css);
        return "/* PP-FONT-START */\n@import url('{$url}');\n/* PP-FONT-END */\n" . $css;
    }

    /* ─────────────────────────── Utilidades ─────────────────────────── */

    private function norm(string $s): string
    {
        $s = mb_strtolower($s, 'UTF-8');
        $s = strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
        return trim(preg_replace('/\s+/', ' ', $s));
    }

    private function has(string $t, array $words): bool
    {
        foreach ($words as $w) {
            if (str_contains($t, $w)) {
                return true;
            }
        }
        return false;
    }

    private function soft(string $hex): string
    {
        [$r, $g, $b] = sscanf(Color::normalizeHex($hex), '#%02x%02x%02x');
        return "rgba({$r},{$g},{$b},.12)";
    }

    private function rgba(string $hex, float $a): string
    {
        [$r, $g, $b] = sscanf(Color::normalizeHex($hex), '#%02x%02x%02x');
        return "rgba({$r},{$g},{$b},{$a})";
    }

    private function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}
