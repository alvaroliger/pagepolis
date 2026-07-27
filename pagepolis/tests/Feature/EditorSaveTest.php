<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Tests for EditorController — the save endpoint is the most critical
 * action in the app: every paying customer's work goes through it.
 */
class EditorSaveTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['email_verified_at' => now()], $attrs));
    }

    private function project(User $user, array $attrs = []): Project
    {
        return Project::create(array_merge([
            'user_id' => $user->id,
            'name'    => 'Mi web',
            'html'    => '<h1>Original</h1>',
            'css'     => 'h1{color:red}',
            'js'      => '',
            'status'  => 'draft',
        ], $attrs));
    }

    public function test_guest_cannot_access_editor(): void
    {
        $user    = $this->user();
        $project = $this->project($user);

        $this->get("/editor/{$project->id}")->assertRedirect('/login');
    }

    public function test_guest_cannot_save_project(): void
    {
        $user    = $this->user();
        $project = $this->project($user);

        $this->postJson("/editor/{$project->id}/guardar", ['html' => '<h1>New</h1>'])
            ->assertUnauthorized();
    }

    public function test_owner_can_save_all_fields_and_changes_persist(): void
    {
        Queue::fake();
        $user    = $this->user();
        $project = $this->project($user);

        $this->actingAs($user)
            ->postJson("/editor/{$project->id}/guardar", [
                'name' => 'Nuevo nombre',
                'html' => '<h1>Nuevo</h1>',
                'css'  => 'body{background:blue}',
                'js'   => 'console.log("ok")',
            ])
            ->assertOk()
            ->assertExactJson(['success' => true]);

        $fresh = $project->fresh();
        $this->assertSame('Nuevo nombre', $fresh->name);
        $this->assertSame('<h1>Nuevo</h1>', $fresh->html);
        $this->assertSame('body{background:blue}', $fresh->css);
        $this->assertSame('console.log("ok")', $fresh->js);
    }

    public function test_partial_update_only_changes_provided_fields(): void
    {
        Queue::fake();
        $user    = $this->user();
        $project = $this->project($user, [
            'css' => 'h1{color:red}',
            'js'  => 'var x = 1;',
        ]);

        $this->actingAs($user)
            ->postJson("/editor/{$project->id}/guardar", ['html' => '<h1>Solo HTML</h1>'])
            ->assertOk();

        $fresh = $project->fresh();
        $this->assertSame('<h1>Solo HTML</h1>', $fresh->html);
        $this->assertSame('h1{color:red}', $fresh->css);
        $this->assertSame('var x = 1;', $fresh->js);
    }

    public function test_clearing_js_to_empty_string_saves_instead_of_failing(): void
    {
        Queue::fake();
        $user    = $this->user();
        $project = $this->project($user, ['js' => 'var x = 1;']);

        // El middleware global convierte "" en null antes de la validación: sin el
        // fix, esto devolvía 422 y el cliente no podía guardar tras borrar su JS.
        $this->actingAs($user)
            ->postJson("/editor/{$project->id}/guardar", [
                'name' => 'Mi web',
                'html' => '<h1>Original</h1>',
                'css'  => 'h1{color:red}',
                'js'   => '',
            ])
            ->assertOk()
            ->assertExactJson(['success' => true]);

        $this->assertSame('', $project->fresh()->js);
    }

    public function test_clearing_html_and_css_to_empty_string_saves_instead_of_failing(): void
    {
        Queue::fake();
        $user    = $this->user();
        $project = $this->project($user);

        $this->actingAs($user)
            ->postJson("/editor/{$project->id}/guardar", ['html' => '', 'css' => ''])
            ->assertOk();

        $fresh = $project->fresh();
        $this->assertSame('', $fresh->html);
        $this->assertSame('', $fresh->css);
    }

    public function test_emptying_name_keeps_previous_name_instead_of_failing(): void
    {
        Queue::fake();
        $user    = $this->user();
        $project = $this->project($user);

        $this->actingAs($user)
            ->postJson("/editor/{$project->id}/guardar", ['name' => ''])
            ->assertOk()
            ->assertExactJson(['success' => true]);

        $this->assertSame('Mi web', $project->fresh()->name);
    }

    public function test_other_user_cannot_save_project(): void
    {
        Queue::fake();
        $owner   = $this->user();
        $other   = $this->user();
        $project = $this->project($owner);

        $this->actingAs($other)
            ->postJson("/editor/{$project->id}/guardar", ['html' => '<h1>Hacked</h1>'])
            ->assertForbidden();

        $this->assertSame('<h1>Original</h1>', $project->fresh()->html);
    }

    public function test_admin_cannot_save_another_users_project(): void
    {
        Queue::fake();
        $owner   = $this->user();
        $admin   = $this->user(['role' => 'admin']);
        $project = $this->project($owner);

        $this->actingAs($admin)
            ->postJson("/editor/{$project->id}/guardar", ['html' => '<h1>Admin edit</h1>'])
            ->assertForbidden();

        $this->assertSame('<h1>Original</h1>', $project->fresh()->html);
    }

    public function test_name_exceeding_max_length_is_rejected(): void
    {
        Queue::fake();
        $user    = $this->user();
        $project = $this->project($user);

        $this->actingAs($user)
            ->postJson("/editor/{$project->id}/guardar", ['name' => str_repeat('a', 101)])
            ->assertUnprocessable();

        $this->assertSame('Mi web', $project->fresh()->name);
    }

    public function test_oversized_html_is_rejected(): void
    {
        Queue::fake();
        $user    = $this->user();
        $project = $this->project($user);

        // 2 MB + 1 byte exceeds the 2 MB limit
        $this->actingAs($user)
            ->postJson("/editor/{$project->id}/guardar", ['html' => str_repeat('x', 2097153)])
            ->assertUnprocessable();

        $this->assertSame('<h1>Original</h1>', $project->fresh()->html);
    }

    public function test_oversized_css_is_rejected(): void
    {
        Queue::fake();
        $user    = $this->user();
        $project = $this->project($user);

        // 512 KB + 1 byte exceeds the 512 KB CSS limit
        $this->actingAs($user)
            ->postJson("/editor/{$project->id}/guardar", ['css' => str_repeat('x', 524289)])
            ->assertUnprocessable();

        $this->assertSame('h1{color:red}', $project->fresh()->css);
    }

    public function test_oversized_js_is_rejected(): void
    {
        Queue::fake();
        $user    = $this->user();
        $project = $this->project($user);

        // 512 KB + 1 byte exceeds the 512 KB JS limit
        $this->actingAs($user)
            ->postJson("/editor/{$project->id}/guardar", ['js' => str_repeat('x', 524289)])
            ->assertUnprocessable();

        $this->assertSame('', $project->fresh()->js);
    }

    public function test_editor_page_exposes_live_url_for_published_project_with_path_domain(): void
    {
        $user    = $this->user();
        $project = $this->project($user, ['status' => 'published']);

        \App\Models\Domain::create([
            'user_id'    => $user->id,
            'project_id' => $project->id,
            'domain'     => $project->slug,
            'type'       => 'path',
            'status'     => 'active',
        ]);

        $this->actingAs($user)
            ->get("/editor/{$project->id}")
            ->assertInertia(fn ($page) => $page
                ->where('project.status', 'published')
                ->where('project.live_url', config('app.url') . '/s/' . $project->slug)
            );
    }

    public function test_editor_page_has_no_live_url_when_project_has_no_domain(): void
    {
        $user    = $this->user();
        $project = $this->project($user);

        $this->actingAs($user)
            ->get("/editor/{$project->id}")
            ->assertInertia(fn ($page) => $page
                ->where('project.live_url', null)
            );
    }

    public function test_editor_page_forbidden_for_other_user(): void
    {
        $owner   = $this->user();
        $other   = $this->user();
        $project = $this->project($owner);

        $this->actingAs($other)
            ->get("/editor/{$project->id}")
            ->assertForbidden();
    }

    public function test_no_deploy_job_dispatched_for_path_domain(): void
    {
        Queue::fake();
        $user    = $this->user();
        $project = $this->project($user, ['status' => 'published']);

        // path-type domain (free tier) — no DeploySite job should fire
        \App\Models\Domain::create([
            'user_id'    => $user->id,
            'project_id' => $project->id,
            'domain'     => $project->slug,
            'type'       => 'path',
            'status'     => 'active',
        ]);

        $this->actingAs($user)
            ->postJson("/editor/{$project->id}/guardar", ['html' => '<h1>Updated</h1>'])
            ->assertOk();

        Queue::assertNothingPushed();
    }
}
