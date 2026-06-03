<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\EditorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PublishController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Landing pública
Route::get('/', fn() => Inertia::render('Landing'))->name('home');

// SEO
Route::get('/sitemap.xml', [SitemapController::class, 'index']);
Route::get('/robots.txt',  [SitemapController::class, 'robots']);

// Tier gratuito: sitios hospedados en pagepolis.com/s/slug
Route::get('/s/{slug}', [SiteController::class, 'show'])->where('slug', '[a-z0-9\-]+');

// Tracking de visitas (pixel transparente)
Route::get('/t/{projectId}', [TrackingController::class, 'track'])->where('projectId', '[0-9]+');

// Plantillas públicas
Route::get('/plantillas', [TemplateController::class, 'index'])->name('templates.index');

// Webhook Stripe (sin CSRF)
Route::post('/webhook/stripe', [BillingController::class, 'webhook'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('stripe.webhook');

// Rutas autenticadas
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Proyectos
    Route::post('/proyectos', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/proyectos/{project}/preview', [ProjectController::class, 'preview'])->name('projects.preview');
    Route::delete('/proyectos/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    // Editor
    Route::get('/editor/{project}', [EditorController::class, 'index'])->name('editor.index');
    Route::post('/editor/{project}/guardar', [EditorController::class, 'save'])->name('editor.save');

    // IA
    Route::post('/ai/generar', [AiController::class, 'generate'])
        ->middleware('ai.ratelimit')
        ->name('ai.generate');
    Route::post('/ai/actualizar', [AiController::class, 'update'])
        ->middleware('ai.ratelimit')
        ->name('ai.update');
    Route::post('/ai/seo', [AiController::class, 'seo'])
        ->name('ai.seo');

    // Publicación
    Route::get('/publicar', [PublishController::class, 'index'])->name('publish.index');
    Route::get('/publicar/exito', [PublishController::class, 'success'])->name('publish.success');
    Route::post('/publicar/gratis', [PublishController::class, 'publishFree'])->name('publish.free');

    // Dominios
    Route::post('/dominios/verificar', [DomainController::class, 'check'])->name('domains.check');
    Route::post('/dominios/reservar', [DomainController::class, 'reserve'])->name('domains.reserve');

    // Facturación
    Route::post('/facturacion/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::get('/facturacion/portal', [BillingController::class, 'portal'])->name('billing.portal');

    // Perfil
    Route::get('/perfil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/perfil', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/perfil', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Rutas de administrador
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin.index');
    Route::post('/usuarios/{user}/suspender', [AdminController::class, 'suspendUser'])->name('admin.suspend');
    Route::post('/usuarios/{user}/gracia', [AdminController::class, 'extendGrace'])->name('admin.grace');
    Route::post('/usuarios/{user}/reactivar', [AdminController::class, 'reactivateUser'])->name('admin.reactivate');
});

require __DIR__.'/auth.php';
