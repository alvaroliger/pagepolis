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
        'has_3d',
        'uses_count',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_premium' => 'boolean',
        'is_active' => 'boolean',
        'has_3d' => 'boolean',
    ];

    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}
