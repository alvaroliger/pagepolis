<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Cortafuegos: los tests SOLO pueden tocar una base de datos desechable.
     *
     * phpunit.xml fija DB_DATABASE=:memory:, pero esa variable de entorno deja
     * de tener efecto en cuanto existe una configuración cacheada
     * (`php artisan optimize` / `config:cache`): la config viene entonces del
     * archivo generado, que apunta a database/database.sqlite. Con
     * RefreshDatabase eso significa vaciar la base de datos de desarrollo
     * entera —plantillas, usuarios y proyectos— sin ningún aviso.
     *
     * Pasó de verdad. Aquí se detecta antes de ejecutar nada: más vale que
     * fallen los tests a que desaparezcan los datos.
     */
    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        // Va aquí y no en setUp() a propósito: Laravel arranca la aplicación en
        // refreshApplication() y JUSTO DESPUÉS engancha los traits, que es
        // cuando RefreshDatabase lanza el migrate:fresh. Comprobarlo después de
        // parent::setUp() sería tarde: la base ya estaría vacía.
        $database = config('database.connections.' . config('database.default') . '.database');

        if ($database === ':memory:' || $database === '' || $database === null) {
            return;
        }

        // Se admite cualquier ruta que sea claramente de pruebas.
        $file = basename((string) $database);
        if (str_contains($file, 'test')) {
            return;
        }

        DB::disconnect();

        throw new RuntimeException(
            "Los tests apuntan a una base de datos real ({$database}). " .
            'Casi seguro que hay configuración cacheada: ejecuta "php artisan optimize:clear" ' .
            'y vuelve a lanzarlos. Se aborta para no borrar tus datos.'
        );
    }
}
