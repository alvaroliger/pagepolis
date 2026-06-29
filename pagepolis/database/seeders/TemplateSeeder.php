<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;

/**
 * Plantillas de inicio: webs COMPLETAS y vendibles (multi-sección, varias con
 * tienda), montadas desde archivos en database/templates/. Comparten un motor
 * (engine.js) y un CSS base (base.css) que las hace interactivas y amoldables por
 * la IA (misma convención de ids/clases que el generador).
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
        ];
    }

    public function run(): void
    {
        $dir    = database_path('templates');
        $base   = @file_get_contents("{$dir}/base.css") ?: '';
        $engine = @file_get_contents("{$dir}/engine.js") ?: '';

        $names = [];
        foreach ($this->manifest() as $t) {
            $html = @file_get_contents("{$dir}/{$t['key']}.html");
            $css  = @file_get_contents("{$dir}/{$t['key']}.css");

            // Si aún no existen los archivos de la plantilla, se omite (no rompe el deploy).
            if ($html === false || $css === false) {
                continue;
            }

            Template::updateOrCreate(
                ['name' => $t['name']],
                [
                    'category'   => $t['category'],
                    'tags'       => $t['tags'],
                    'html'       => $html,
                    'css'        => $base . "\n\n" . $css,
                    'js'         => $engine,
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
