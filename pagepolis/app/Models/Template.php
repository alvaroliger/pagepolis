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
}
