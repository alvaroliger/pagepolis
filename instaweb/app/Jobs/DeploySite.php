<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\NginxService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeploySite implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private Project $project) {}

    public function handle(NginxService $nginx): void
    {
        try {
            $nginx->deploySite(
                $this->project->id,
                $this->project->html    ?? '',
                $this->project->css     ?? '',
                $this->project->js      ?? '',
                $this->project->seo_meta ?? []
            );

            $this->project->update([
                'status'       => 'published',
                'published_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('DeploySite failed: ' . $e->getMessage());
            $this->fail($e);
        }
    }
}
