<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    protected $fillable = [
        'name',
        'category',
        'thumbnail',
        'html',
        'css',
        'js',
        'tags',
        'is_premium',
        'is_active',
        'uses_count',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_premium' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Detecta si la plantilla incluye el hero 3D (canvas WebGL de hero3d.js)
     * para poder distinguir "Simple" de "3D" en la galería sin cargar el JS.
     */
    public function getHas3dAttribute(): bool
    {
        return str_contains($this->html ?? '', 'data-hero3d');
    }
}
