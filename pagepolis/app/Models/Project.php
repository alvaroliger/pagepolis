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
        'previous_html',
        'previous_css',
        'previous_js',
        'ai_history',
        'ai_status',
        'ai_progress',
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

    public function leads()
    {
        return $this->hasMany(\App\Models\Lead::class);
    }

    public function getPreviewUrlAttribute(): string
    {
        if ($this->domain?->status === 'active') {
            return 'https://' . $this->domain->domain;
        }

        return route('projects.preview', $this->id);
    }

    public function canUndoAiChange(): bool
    {
        return $this->previous_html !== null;
    }

    /**
     * Deshace el último cambio de la IA (chat/instant), restaurando el snapshot
     * guardado justo antes de aplicarlo. Deshacer es de un solo nivel: al
     * restaurar se limpia el snapshot para que no se pueda deshacer dos veces.
     */
    public function undoAiChange(): void
    {
        $this->update([
            'html'          => $this->previous_html,
            'css'           => $this->previous_css,
            'js'            => $this->previous_js,
            'previous_html' => null,
            'previous_css'  => null,
            'previous_js'   => null,
        ]);
    }

    /**
     * Si el proyecto está publicado en un dominio propio/subdominio ACTIVO, vuelve a
     * desplegar los archivos estáticos para que la web en internet refleje los
     * últimos cambios (editar "después" de publicar). Los sitios en /s/{slug}
     * (type 'path') se sirven en vivo desde la BD, no necesitan re-despliegue.
     */
    public function deployToLiveDomain(): void
    {
        $domain = $this->domain()->first();
        if ($domain && $domain->status === 'active' && in_array($domain->type, ['custom', 'subdomain'], true)) {
            \App\Jobs\DeploySite::dispatch($this);
        }
    }
}
