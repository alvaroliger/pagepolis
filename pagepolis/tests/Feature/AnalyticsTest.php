<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['email_verified_at' => now()]);
    }

    private function publishedProject(User $user, string $suffix = 'a'): Project
    {
        return Project::create([
            'user_id' => $user->id,
            'name'    => 'Test Site ' . $suffix,
            'html'    => '<h1>Hello</h1>',
            'css'     => '',
            'js'      => '',
            'status'  => 'published',
        ]);
    }

    private function draftProject(User $user): Project
    {
        return Project::create([
            'user_id' => $user->id,
            'name'    => 'Draft Site',
            'html'    => '<h1>Draft</h1>',
            'css'     => '',
            'js'      => '',
            'status'  => 'draft',
        ]);
    }

    // ── TrackingController ────────────────────────────────────────────────────

    public function test_tracking_pixel_returns_gif_for_published_project(): void
    {
        $user    = $this->user();
        $project = $this->publishedProject($user);

        $response = $this->get("/t/{$project->id}");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/gif');
    }

    public function test_tracking_pixel_returns_gif_for_unknown_project(): void
    {
        $response = $this->get('/t/99999');

        // Should still return a GIF, not a 404 — tracking must never break pages.
        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/gif');
    }

    public function test_tracking_records_view_for_published_project(): void
    {
        $user    = $this->user();
        $project = $this->publishedProject($user);

        $this->assertDatabaseCount('page_views', 0);

        $this->get("/t/{$project->id}");

        $this->assertDatabaseHas('page_views', [
            'project_id' => $project->id,
            'viewed_on'  => now()->toDateString(),
            'count'      => 1,
        ]);
    }

    public function test_tracking_does_not_record_view_for_draft_project(): void
    {
        $user    = $this->user();
        $project = $this->draftProject($user);

        $this->get("/t/{$project->id}");

        $this->assertDatabaseCount('page_views', 0);
    }

    public function test_tracking_does_not_record_view_for_unknown_project(): void
    {
        $this->get('/t/99999');

        $this->assertDatabaseCount('page_views', 0);
    }

    public function test_tracking_increments_count_on_subsequent_hits_same_day(): void
    {
        $user    = $this->user();
        $project = $this->publishedProject($user);

        $this->get("/t/{$project->id}");
        $this->get("/t/{$project->id}");
        $this->get("/t/{$project->id}");

        $this->assertDatabaseHas('page_views', [
            'project_id' => $project->id,
            'viewed_on'  => now()->toDateString(),
            'count'      => 3,
        ]);
        $this->assertDatabaseCount('page_views', 1);
    }

    // ── AnalyticsController ───────────────────────────────────────────────────

    public function test_analytics_requires_authentication(): void
    {
        $this->get('/analytics')->assertRedirect('/login');
    }

    public function test_analytics_page_loads_for_authenticated_user(): void
    {
        $user = $this->user();

        $this->actingAs($user)->get('/analytics')->assertOk();
    }

    public function test_analytics_series_always_contains_30_days(): void
    {
        $user = $this->user();

        $response = $this->actingAs($user)->get('/analytics');

        $series = $response->viewData('page')['props']['series'] ?? null;
        $this->assertIsArray($series);
        $this->assertCount(30, $series);
    }

    public function test_analytics_series_fills_gaps_with_zero(): void
    {
        $user    = $this->user();
        $project = $this->publishedProject($user);
        // Use insert() directly so the raw "Y-m-d" string is stored, matching the
        // format that PageView::record() (upsert) stores in production.
        DB::table('page_views')->insert([
            'project_id' => $project->id,
            'viewed_on'  => now()->toDateString(),
            'count'      => 5,
        ]);

        $response = $this->actingAs($user)->get('/analytics');

        $series = $response->viewData('page')['props']['series'];
        $today  = now()->toDateString();
        $counts = collect($series)->pluck('count', 'date');

        $this->assertSame(5, (int) $counts[$today]);
        // All other 29 days should be zero.
        $this->assertSame(29, collect($counts)->filter(fn ($v) => $v == 0)->count());
    }

    public function test_analytics_totals_are_correct(): void
    {
        $user    = $this->user();
        $project = $this->publishedProject($user);

        // 3 views today, 4 views 20 days ago, 2 views 40 days ago (outside 30-day window).
        DB::table('page_views')->insert([
            ['project_id' => $project->id, 'viewed_on' => now()->toDateString(), 'count' => 3],
            ['project_id' => $project->id, 'viewed_on' => now()->subDays(20)->toDateString(), 'count' => 4],
            ['project_id' => $project->id, 'viewed_on' => now()->subDays(40)->toDateString(), 'count' => 2],
        ]);

        $response = $this->actingAs($user)->get('/analytics');

        $totals = $response->viewData('page')['props']['totals'];
        $this->assertSame(9, $totals['all']);  // 3 + 4 + 2
        $this->assertSame(7, $totals['d30']); // 3 + 4 (40-day-old view excluded)
        $this->assertSame(3, $totals['d7']);  // today only
    }

    public function test_analytics_per_project_breakdown_is_correct(): void
    {
        $user = $this->user();
        $p1   = $this->publishedProject($user, 'x');
        $p2   = $this->publishedProject($user, 'y');

        DB::table('page_views')->insert([
            ['project_id' => $p1->id, 'viewed_on' => now()->toDateString(), 'count' => 10],
            ['project_id' => $p2->id, 'viewed_on' => now()->toDateString(), 'count' => 6],
        ]);

        $response = $this->actingAs($user)->get('/analytics');

        $projects = collect($response->viewData('page')['props']['projects']);
        $this->assertSame(10, $projects->firstWhere('id', $p1->id)['views_total']);
        $this->assertSame(6, $projects->firstWhere('id', $p2->id)['views_total']);
    }

    public function test_analytics_only_shows_own_project_data(): void
    {
        $owner = $this->user();
        $other = $this->user();

        $ownerProject = $this->publishedProject($owner, 'o');
        $otherProject = $this->publishedProject($other, 'p');

        DB::table('page_views')->insert([
            ['project_id' => $ownerProject->id, 'viewed_on' => now()->toDateString(), 'count' => 5],
            ['project_id' => $otherProject->id, 'viewed_on' => now()->toDateString(), 'count' => 99],
        ]);

        $response = $this->actingAs($owner)->get('/analytics');

        $totals = $response->viewData('page')['props']['totals'];
        $this->assertSame(5, $totals['all']);  // only owner's views, not other user's 99

        $projects = $response->viewData('page')['props']['projects'];
        $ids = array_column($projects, 'id');
        $this->assertContains($ownerProject->id, $ids);
        $this->assertNotContains($otherProject->id, $ids);
    }
}
