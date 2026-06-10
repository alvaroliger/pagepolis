<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Project extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'template_id',
        'html',
        'css',
        'js',
        'ai_history',
        'seo_meta',
        'status',
        'published_at',
    ];

    protected $casts = [
        'ai_history'   => 'array',
        'seo_meta'     => 'array',
        'published_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            if (empty($project->slug)) {
                // En minúsculas: la ruta pública /s/{slug} solo acepta [a-z0-9-].
                $base = Str::slug($project->name);
                $project->slug = ($base !== '' ? $base : 'web') . '-' . Str::lower(Str::random(6));
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    public function domain()
    {
        return $this->hasOne(Domain::class);
    }

    public function pageViews()
    {
        return $this->hasMany(\App\Models\PageView::class);
    }

    public function getPreviewUrlAttribute(): string
    {
        if ($this->domain?->status === 'active') {
            return 'https://' . $this->domain->domain;
        }

        return route('projects.preview', $this->id);
    }
}
