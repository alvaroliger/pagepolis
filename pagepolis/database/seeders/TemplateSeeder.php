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
     * `is3d` marca las plantillas cuyo HTML trae hero/tarjetas con el motor
     * `hero3d.js` (`tilt-3d` / `hero3d-canvas`) — deja a la vista la elección
     * "Simple | 3D" en la galería sin tener que inspeccionar el marcado.
     *
     * @return array<int,array{key:string,name:string,category:string,tags:array<int,string>,is3d:bool,premium?:bool}>
     */
    private function manifest(): array
    {
        return [
            ['key' => 'restaurante', 'name' => 'Restaurante con pedidos', 'category' => 'Restaurante', 'tags' => ['restaurante', 'carta', 'pedidos', 'tienda'], 'is3d' => false],
            ['key' => 'tienda',      'name' => 'Tienda online',          'category' => 'E-commerce',  'tags' => ['tienda', 'productos', 'carrito'], 'is3d' => false],
            ['key' => 'servicios',   'name' => 'Empresa de servicios',   'category' => 'Servicios',   'tags' => ['servicios', 'empresa', 'agencia'], 'is3d' => true],
            ['key' => 'clinica',     'name' => 'Clínica / Salud',        'category' => 'Salud',       'tags' => ['clínica', 'salud', 'citas'], 'is3d' => false],
            ['key' => 'gimnasio',    'name' => 'Gimnasio / Fitness',     'category' => 'Fitness',     'tags' => ['gimnasio', 'fitness', 'cuotas'], 'is3d' => true],
            ['key' => 'belleza',     'name' => 'Peluquería y estética',  'category' => 'Belleza',     'tags' => ['belleza', 'peluquería', 'reservas', 'tienda'], 'is3d' => false],
            ['key' => 'inmobiliaria','name' => 'Inmobiliaria',           'category' => 'Inmobiliaria','tags' => ['inmobiliaria', 'propiedades', 'venta'], 'is3d' => true],
            ['key' => 'abogados',    'name' => 'Bufete de abogados',     'category' => 'Servicios',   'tags' => ['abogados', 'legal', 'profesional'], 'is3d' => true],
            ['key' => 'fotografo',   'name' => 'Fotógrafo / Portfolio',  'category' => 'Portfolio',   'tags' => ['fotografía', 'portfolio', 'galería'], 'is3d' => true],
            ['key' => 'cafeteria',   'name' => 'Cafetería con tienda',   'category' => 'Restaurante', 'tags' => ['cafetería', 'café', 'pedidos', 'tienda'], 'is3d' => false],
            ['key' => 'saas',        'name' => 'App / SaaS',             'category' => 'SaaS',        'tags' => ['saas', 'startup', 'software'], 'is3d' => true],
            ['key' => 'coach',       'name' => 'Coach / Formación',      'category' => 'Servicios',   'tags' => ['coach', 'formación', 'cursos'], 'is3d' => true],
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

            Template::updateOrCreate(
                ['name' => $t['name']],
                [
                    'category'   => $t['category'],
                    'tags'       => $t['tags'],
                    'html'       => $html,
                    'css'        => $base . "\n\n" . $css,
                    'js'         => $engine . "\n\n" . $hero3d,
                    'is_premium' => $t['premium'] ?? false,
                    'is_active'  => true,
                    'is_3d'      => $t['is3d'],
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
