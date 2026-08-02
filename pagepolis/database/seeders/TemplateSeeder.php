<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;

/**
 * Plantillas de inicio: webs COMPLETAS y vendibles (multi-sección, varias con
 * tienda), montadas desde archivos en database/templates/. Comparten un motor
 * (engine.js + hero3d.js) y un CSS base (base.css) que las hace interactivas,
 * con motion design 3D, y amoldables por la IA (misma convención de ids/clases
 * que el generador).
 *
 * Idempotente (updateOrCreate por nombre): se puede ejecutar en cada deploy.
 */
class TemplateSeeder extends Seeder
{
    /**
     * @return array<int,array{key:string,name:string,category:string,tags:array<int,string>,premium?:bool}>
     */
    private function manifest(): array
    {
        return [
            // ── Plantillas 3D premium (modernas, oscuras, hero WebGL) — al frente ──
            ['key' => 'nebula', 'name' => 'Nebula — IA / SaaS 3D',        'category' => 'SaaS',        'tags' => ['ia', 'saas', '3d', 'moderna'],        'premium' => true],
            ['key' => 'prisma', 'name' => 'Prisma — Estudio creativo 3D', 'category' => 'Portfolio',   'tags' => ['agencia', 'portfolio', '3d', 'diseño'], 'premium' => true],
            ['key' => 'vault',  'name' => 'Vault — Fintech / Web3 3D',    'category' => 'Fintech',     'tags' => ['fintech', 'cripto', 'web3', '3d'],      'premium' => true],
            ['key' => 'pulse',  'name' => 'Pulse — Gimnasio / Fitness 3D','category' => 'Fitness',     'tags' => ['gimnasio', 'fitness', '3d', 'moderna'], 'premium' => true],
            ['key' => 'ember',  'name' => 'Ember — Restaurante 3D',       'category' => 'Restaurante', 'tags' => ['restaurante', 'carta', '3d', 'premium'],'premium' => true],
            ['key' => 'aura',   'name' => 'Aura — Belleza / Estética 3D', 'category' => 'Belleza',     'tags' => ['belleza', 'estética', 'spa', '3d'],     'premium' => true],
            ['key' => 'vista',  'name' => 'Vista — Inmobiliaria 3D',      'category' => 'Inmobiliaria','tags' => ['inmobiliaria', 'propiedades', '3d'],    'premium' => true],

            ['key' => 'restaurante', 'name' => 'Restaurante con pedidos', 'category' => 'Restaurante', 'tags' => ['restaurante', 'carta', 'pedidos', 'tienda']],
            ['key' => 'tienda',      'name' => 'Tienda online',          'category' => 'E-commerce',  'tags' => ['tienda', 'productos', 'carrito']],
            ['key' => 'servicios',   'name' => 'Empresa de servicios',   'category' => 'Servicios',   'tags' => ['servicios', 'empresa', 'agencia']],
            ['key' => 'clinica',     'name' => 'Clínica / Salud',        'category' => 'Salud',       'tags' => ['clínica', 'salud', 'citas']],
            ['key' => 'gimnasio',    'name' => 'Gimnasio / Fitness',     'category' => 'Fitness',     'tags' => ['gimnasio', 'fitness', 'cuotas']],
            ['key' => 'belleza',     'name' => 'Peluquería y estética',  'category' => 'Belleza',     'tags' => ['belleza', 'peluquería', 'reservas', 'tienda']],
            ['key' => 'inmobiliaria','name' => 'Inmobiliaria',           'category' => 'Inmobiliaria','tags' => ['inmobiliaria', 'propiedades', 'venta']],
            ['key' => 'abogados',    'name' => 'Bufete de abogados',     'category' => 'Servicios',   'tags' => ['abogados', 'legal', 'profesional']],
            ['key' => 'fotografo',   'name' => 'Fotógrafo / Portfolio',  'category' => 'Portfolio',   'tags' => ['fotografía', 'portfolio', 'galería']],
            ['key' => 'cafeteria',   'name' => 'Cafetería con tienda',   'category' => 'Restaurante', 'tags' => ['cafetería', 'café', 'pedidos', 'tienda']],
            ['key' => 'saas',        'name' => 'App / SaaS',             'category' => 'SaaS',        'tags' => ['saas', 'startup', 'software']],
            ['key' => 'coach',       'name' => 'Coach / Formación',      'category' => 'Servicios',   'tags' => ['coach', 'formación', 'cursos']],

            // ── Recuperadas de producción (2026-07-30): eran las únicas 7 realmente
            // distintas de las 20 que había en el servidor real — el resto eran una
            // misma plantilla genérica de relleno duplicada 13 veces con el nombre
            // cambiado. Son autocontenidas (su propio CSS/JS, sistema anterior a
            // base.css/engine.js): 'standalone' => true evita mezclarlas con el motor
            // compartido, que rompería su diseño original.
            ['key' => 'prod-landing-saas',        'name' => 'Landing SaaS',         'category' => 'SaaS',        'tags' => ['oscuro', 'moderno', 'startup'],       'standalone' => true],
            ['key' => 'prod-portfolio-creativo',  'name' => 'Portfolio Creativo',    'category' => 'Portfolio',   'tags' => ['minimalista', 'creativo', 'diseñador'],'standalone' => true],
            ['key' => 'prod-restaurante-elegante','name' => 'Restaurante Elegante',  'category' => 'Restaurante', 'tags' => ['gastronomía', 'elegante', 'reservas'], 'standalone' => true],
            ['key' => 'prod-agencia-marketing',   'name' => 'Agencia de Marketing',  'category' => 'Agencia',     'tags' => ['colorida', 'stats', 'marketing'],      'standalone' => true],
            ['key' => 'prod-app-movil',           'name' => 'App Móvil',             'category' => 'App',         'tags' => ['app', 'móvil', 'startup', 'producto'], 'standalone' => true],
            ['key' => 'prod-gimnasio-fitness',    'name' => 'Gimnasio Fitness',      'category' => 'Fitness',     'tags' => ['gym', 'fitness', 'energía', 'deporte'],'standalone' => true],
            ['key' => 'prod-blog-personal',       'name' => 'Blog Personal',         'category' => 'Blog',        'tags' => ['blog', 'escritura', 'personal'],       'standalone' => true],

            // ── Reemplazo de las 13 duplicadas genéricas de producción (2026-07-30):
            // diseño y copy reales y distintos por sector, no relleno.
            ['key' => 'cv-resume',            'name' => 'CV / Portfolio personal',   'category' => 'Portfolio',   'tags' => ['currículum', 'personal', 'freelance']],
            ['key' => 'boda',                 'name' => 'Invitación de boda',        'category' => 'Boda',        'tags' => ['boda', 'invitación', 'pareja']],
            ['key' => 'tienda-tech',          'name' => 'Tienda de tecnología',      'category' => 'E-commerce',  'tags' => ['tienda', 'tecnología', 'gadgets']],
            ['key' => 'inmobiliaria-listados','name' => 'Inmobiliaria con listados', 'category' => 'Inmobiliaria','tags' => ['inmobiliaria', 'listados', 'venta']],
            ['key' => 'clinica-dental',       'name' => 'Clínica dental',            'category' => 'Salud',       'tags' => ['salud', 'dental', 'citas']],
            ['key' => 'consultoria-legal',    'name' => 'Consultoría legal',         'category' => 'Servicios',   'tags' => ['legal', 'consultoría', 'abogada']],
            ['key' => 'startup-tech',         'name' => 'Startup tecnológica',       'category' => 'SaaS',        'tags' => ['startup', 'tech', 'producto']],
            ['key' => 'fotografo-bodas',      'name' => 'Fotógrafo de bodas',        'category' => 'Portfolio',   'tags' => ['fotografía', 'bodas', 'portfolio']],
            ['key' => 'musico-artista',       'name' => 'Músico / Banda',            'category' => 'Música',      'tags' => ['música', 'artista', 'conciertos']],
            ['key' => 'ong-fundacion',        'name' => 'ONG / Fundación',           'category' => 'ONG',         'tags' => ['ong', 'fundación', 'donaciones']],
            ['key' => 'moda-boutique',        'name' => 'Boutique de moda',          'category' => 'E-commerce',  'tags' => ['moda', 'boutique', 'ropa']],
            ['key' => 'conferencia',          'name' => 'Evento / Conferencia',      'category' => 'Evento',      'tags' => ['evento', 'conferencia', 'congreso']],
            ['key' => 'barberia',             'name' => 'Barbería',                  'category' => 'Belleza',     'tags' => ['peluquería', 'barbería', 'estilo']],
        ];
    }

    public function run(): void
    {
        $dir    = database_path('templates');
        $base   = @file_get_contents("{$dir}/base.css") ?: '';
        $engine = @file_get_contents("{$dir}/engine.js") ?: '';
        $hero3d = @file_get_contents("{$dir}/hero3d.js") ?: '';

        $names = [];
        foreach ($this->manifest() as $t) {
            $html = @file_get_contents("{$dir}/{$t['key']}.html");
            $css  = @file_get_contents("{$dir}/{$t['key']}.css");

            // Si aún no existen los archivos de la plantilla, se omite (no rompe el deploy).
            if ($html === false || $css === false) {
                continue;
            }

            // Las 'standalone' (recuperadas de producción) llevan su CSS/JS propio,
            // sin el motor compartido base.css/engine.js/hero3d.js.
            $standalone = $t['standalone'] ?? false;
            $ownJs = $standalone ? (@file_get_contents("{$dir}/{$t['key']}.js") ?: '') : '';

            Template::updateOrCreate(
                ['name' => $t['name']],
                [
                    'category'   => $t['category'],
                    'tags'       => $t['tags'],
                    'html'       => $html,
                    'css'        => $standalone ? $css : ($base . "\n\n" . $css),
                    'js'         => $standalone ? $ownJs : ($engine . "\n\n" . $hero3d),
                    'is_premium' => $t['premium'] ?? false,
                    'is_active'  => true,
                ]
            );
            $names[] = $t['name'];
        }

        // Elimina plantillas antiguas que ya no forman parte del set (sin romper
        // la FK: primero desvincula los proyectos que las usaban).
        if (!empty($names)) {
            $obsolete = Template::whereNotIn('name', $names)->pluck('id');
            if ($obsolete->isNotEmpty()) {
                \App\Models\Project::whereIn('template_id', $obsolete)->update(['template_id' => null]);
                Template::whereIn('id', $obsolete)->delete();
            }
        }
    }
}
