<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageView extends Model
{
    public $timestamps = false;

    protected $fillable = ['project_id', 'viewed_on', 'count'];

    protected $casts = ['viewed_on' => 'date'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public static function record(int $projectId): void
    {
        static::upsert(
            [['project_id' => $projectId, 'viewed_on' => now()->toDateString(), 'count' => 1]],
            ['project_id', 'viewed_on'],
            ['count' => \Illuminate\Support\Facades\DB::raw('count + 1')]
        );
    }
}
