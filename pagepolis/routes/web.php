<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\EditorController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PublishController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\WhatsAppController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Landing pública
Route::get('/', [LandingController::class, 'index'])->name('home');

// SEO
Route::get('/sitemap.xml', [SitemapController::class, 'index']);
Route::get('/robots.txt',  [SitemapController::class, 'robots']);

// Tier gratuito: sitios hospedados en pagepolis.com/s/slug
Route::get('/s/{slug}', [SiteController::class, 'show'])
    ->where('slug', '[a-z0-9\-]+')
    ->middleware('throttle:300,1');

// Captura de leads: el formulario de contacto de la web publicada postea aquí.
// Público (lo llama la web del cliente) → sin CSRF, con throttle anti-spam.
Route::post('/s/{slug}/lead', [LeadController::class, 'store'])
    ->where('slug', '[a-z0-9\-]+')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->middleware('throttle:20,1')
    ->name('leads.store');

// Tracking de visitas (pixel transparente)
Route::get('/t/{projectId}', [TrackingController::class, 'track'])
    ->where('projectId', '[0-9]+')
    ->middleware('throttle:600,1');

// Plantillas públicas
Route::get('/plantillas', [TemplateController::class, 'index'])->name('templates.index');

// Páginas legales
Route::get('/terminos',   fn() => Inertia::render('Legal/Terms'))->name('legal.terms');
Route::get('/privacidad', fn() => Inertia::render('Legal/Privacy'))->name('legal.privacy');

// Webhook WhatsApp Business (sin CSRF)
Route::get('/webhook/whatsapp',  [WhatsAppController::class, 'verify'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::post('/webhook/whatsapp', [WhatsAppController::class, 'webhook'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->middleware('throttle:100,1');

// El webhook de Stripe lo sirve Cashier en POST /stripe/webhook (cashier.webhook).
// La lógica propia está en App\Listeners\HandleStripeWebhook.

// Rutas autenticadas
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Asistente de creación con IA ("a prueba de abuelos")
    Route::get('/crear', [ProjectController::class, 'create'])->name('create.wizard');
    Route::post('/crear-con-ia', [ProjectController::class, 'createWithAi'])
        ->middleware('ai.ratelimit')
        ->name('projects.create-ai');

    // Proyectos
    Route::post('/proyectos', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/proyectos/{project}/preview', [ProjectController::class, 'preview'])->name('projects.preview');
    Route::get('/proyectos/{project}/zip', [ProjectController::class, 'exportZip'])->name('projects.zip');
    Route::delete('/proyectos/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    Route::post('/proyectos/{id}/restaurar', [ProjectController::class, 'restore'])->name('projects.restore');
    Route::delete('/proyectos/{id}/eliminar-definitivo', [ProjectController::class, 'forceDestroy'])->name('projects.force-destroy');

    // Analítica de visitas
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    // Bandeja de mensajes (leads capturados de las webs publicadas)
    Route::get('/mensajes', [LeadController::class, 'index'])->name('leads.index');
    Route::get('/mensajes/exportar', [LeadController::class, 'export'])->name('leads.export');

    // Editor
    Route::get('/editor/{project}', [EditorController::class, 'index'])->name('editor.index');
    Route::post('/editor/{project}/guardar', [EditorController::class, 'save'])->name('editor.save');

    // IA
    Route::post('/ai/generar', [AiController::class, 'generate'])
        ->middleware('ai.ratelimit')
        ->name('ai.generate');
    // Sin ai.ratelimit: el controlador solo cuenta cuota si cae en la IA (los
    // cambios resueltos en local por IntentRouter son gratis e ilimitados).
    Route::post('/ai/actualizar', [AiController::class, 'update'])->name('ai.update');
    // Deshacer no llama a la IA (solo restaura un snapshot local): sin rate-limit.
    Route::post('/ai/deshacer', [AiController::class, 'undo'])->name('ai.undo');
    Route::post('/ai/seo', [AiController::class, 'seo'])
        ->middleware('ai.ratelimit')
        ->name('ai.seo');
    // Sondeo del estado de generación (sin rate-limit: no consume cuota de IA).
    Route::get('/ai/estado/{project}', [AiController::class, 'status'])->name('ai.status');

    // Publicación
    Route::get('/publicar', [PublishController::class, 'index'])->name('publish.index');
    Route::get('/publicar/exito', [PublishController::class, 'success'])->name('publish.success');
    Route::post('/publicar/gratis', [PublishController::class, 'publishFree'])->name('publish.free');
    Route::post('/publicar/despublicar', [PublishController::class, 'unpublish'])->name('publish.unpublish');

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
    // Exportación de datos (RGPD)
    Route::get('/perfil/exportar', [ProfileController::class, 'exportData'])->name('profile.export');
});

// Rutas de administrador
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin.index');
    Route::post('/usuarios/{user}/suspender', [AdminController::class, 'suspendUser'])->name('admin.suspend');
    Route::post('/usuarios/{user}/gracia', [AdminController::class, 'extendGrace'])->name('admin.grace');
    Route::post('/usuarios/{user}/reactivar', [AdminController::class, 'reactivateUser'])->name('admin.reactivate');
});

require __DIR__.'/auth.php';
