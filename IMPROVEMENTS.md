# 🤖 PagePolis — Backlog de auto-mejora

Roadmap vivo del agente de auto-mejora. La app Laravel está en **`pagepolis/`** (subcarpeta del repo).
Regla: coge **1** ítem no bloqueado de mayor valor, impleméntalo en una **rama nueva**, deja los tests
verdes (`cd pagepolis && php artisan test`), abre **PR**. **No desplegar, no tocar `main`.**

Leyenda: 🟢 listo · 🟡 decisión de Álvaro · 🔵 grande · ✅ hecho

## ⚠️ Nota operativa (2026-07-20): 41 PRs abiertas sin fusionar, con duplicados
Antes de implementar nada, comprueba el estado real con `list_pull_requests` (state=open,
Github MCP) — **no te fíes solo de `git branch -r`**, que tras un clon fresco de este
entorno solo muestra `origin/master` aunque haya decenas de ramas remotas. A fecha de hoy
hay ~41 PRs abiertas (#44-#84) desde el 2026-07-03, ninguna fusionada todavía, con bastante
duplicación entre sí. Grupos ya cubiertos por al menos una PR (no reimplementar, revisar/
fusionar esas primero):
- **Rendimiento del hero 3D en gama baja**: PR #45 (`perf/hero3d-low-end-throttle`).
- **Variantes de geometría/paleta del hero 3D**: PRs #46, #57, #79 (tres implementaciones
  distintas del mismo ítem).
- **Galería de plantillas: filtro/insignia Simple vs 3D**: PRs #44, #51, #67, #78.
- **Vista previa con el efecto 3D real en la galería**: PRs #50, #71, #75.
- **Elección "Clásica" / "3D interactiva" en el wizard**: PR #49.
- Resto de PRs (#47, #48, #52-56, #58-66, #68-70, #72-74, #76, #77, #80-84): un ítem
  distinto cada una (a11y, estados de carga/vacíos/error, dashboard, editor, checkout
  i18n, etc.) — sin duplicados detectados entre ellas a fecha de hoy.

## 🎯 FOCO DE LA SEMANA (encargo de Álvaro, 2026-07-03 → 2026-07-10)
Álvaro está desconectado esta semana. Prioriza en este orden, por encima del sesgo
general a ingresos:
1. **Interfaz de PagePolis y plantillas con 3D** — ítems de la sección "Diseño / nivel
   visual" (variantes del hero 3D, rendimiento en móviles de gama baja, extenderlo donde
   aporte). El listón sigue siendo motionsites.ai / agencia premium.
2. **Mejorar la web y el servicio para los clientes** — wizard, onboarding, editor,
   captura de leads, i18n/crecimiento: todo lo que haga que un cliente reciba más valor
   esta semana sin necesitar decisiones de Álvaro.
Sigue aplicando el listón de calidad de siempre: nada de churn; si no hay mejora clara,
no abras PR.

## Ingresos / conversión (lo que hace que el cliente pague)
- ✅ **Captura de leads** en las webs generadas (endpoint + email al dueño + bandeja `/mensajes`).
- ✅ **Vender la captura de leads** en landing/precios — feature card + línea en pricing + FAQ, en los 6 idiomas (PR #40).
- ✅ **Prueba social real** — el hero muestra el nº real de webs publicadas (LandingController +
  caché 1h) en vez de una cifra inventada.
- 🔵 **Blog SEO** (motor de tráfico orgánico; cada artículo es un embudo).
- 🟡 Claim "X% más barato / traspasa tu web" — solo cuando exista la cifra/feature (no inventar).
- ✅ Secuencia de email post-registro (bienvenida + nudge de publicación programado).

## Producto
- ✅ Wizard de creación: validación/UX pulida (límite de caracteres, botón deshabilitado si inválido).
- ✅ **Autosave en el editor con debounce** — guarda solo 2,5 s después del último cambio
  (html/css/js/nombre), pospuesto si hay guardado o generación IA en curso
  (`resources/js/Pages/Editor/Index.tsx`).
- ✅ Checklist de onboarding en el dashboard.
- ✅ Code-splitting del editor: CodeMirror extraído a chunk vendor propio (app-*.js ya no lo arrastra).
- ✅ **El fallo al activar un dominio de pago ya no es silencioso** — `PurchaseDomain`
  (registro + Cloudflare + nginx tras el pago) solo registraba el error en el log si algo
  fallaba; el `Domain` se quedaba en `status=pending` para siempre y el proyecto seguía
  como "Borrador" sin ninguna pista de qué había pasado, pese a que el cliente ya había
  pagado. Ahora el job marca `status=failed` en el catch, `DashboardController` expone
  `domain_status`, y `ProjectCard` (Dashboard) muestra un aviso rojo explicando qué pasó
  con enlace a soporte (`mailto:`) en vez de dejar la tarjeta como un borrador cualquiera.
  4 tests nuevos (`PurchaseDomainJobTest`): falla Cloudflare → `failed`, falla nginx →
  `failed`, éxito → sigue marcando `active` y despliega (regresión), y el Dashboard
  expone `domain_status` correctamente.

## Diseño / nivel visual (referencia: motionsites.ai — agencia premium, 3D/motion)
- ✅ Motor hero 3D propio sin dependencias (`database/templates/hero3d.js`, WebGL, figuras
  low-poly con luz e inclinación por ratón/scroll) + clase `.tilt-3d` para tarjetas con
  profundidad. Cableado en `base.css`, `engine.js`, `TemplateSeeder` (plantilla `saas` ya
  lo usa) y en los prompts de `AnthropicService` (la IA añade el `<canvas>`; el motor se
  inyecta desde el backend tras generar el JS — fiable y sin coste extra de tokens).
- ✅ **Extender el hero 3D / `tilt-3d` a más plantillas base** — canvas 3D en servicios,
  abogados, coach, inmobiliaria y fotógrafo (su hero es solo texto, sin foto protagonista)
  + `tilt-3d` en sus tarjetas clave y en el plan destacado de gimnasio. Restaurante,
  tienda, cafetería, belleza y clínica se dejan a propósito sin figuras 3D (heroes con
  foto/producto como protagonista; ya tienen el mesh-gradient sutil de `base.css`).
- 🟡 Variantes del hero 3D (2-3 geometrías/paletas distintas) para que no todas las webs
  "tech" se vean idénticas — **ya hay 3 PRs abiertas para esto (#46, #57, #79)**, revisar/
  fusionar una de ellas en vez de reimplementar.
- 🟡 Auditar rendimiento del hero 3D en móviles de gama baja (FPS, batería) y bajar el
  nº de figuras o desactivarlo automáticamente si `navigator.hardwareConcurrency` es bajo.
  **Ya hay una PR abierta para esto (#45, `perf/hero3d-low-end-throttle`)**, revisar/
  fusionar en vez de reimplementar.
- 🟡 Vídeo/GIF comparativo "antes (plantilla clásica) / después (hero 3D)" para la landing
  y el paso de plantillas del wizard — ayuda a vender el nivel de diseño.
- ✅ **Criterio "agencia premium" en la propia app** — hero 3D WebGL propio portado a React
  (`Components/Hero3D.tsx`, mismo motor que `hero3d.js`, sin three.js), primitivas de
  motion con framer-motion (`Components/Motion.tsx`: `Reveal`, `FadeIn`, `TiltCard`,
  todas con `prefers-reduced-motion`) aplicadas a Landing, GuestLayout,
  AuthenticatedLayout (nav sticky blur + estados activos), Dashboard y wizard.

## Crecimiento global (que se pueda suscribir cualquier persona del planeta)
- 🔵 Ampliar `resources/js/i18n/locales/*.json` más allá de es/en: añadir pt, fr, de, it
  como mínimo (mercados grandes de habla latina/europea) — revisar `LanguageSelector.tsx`
  y `i18n/index.ts` para que la detección/fallback funcione bien.
- 🟢 Precios localizados por región (moneda mostrada según IP/locale, aunque el cobro siga
  en Stripe con la moneda que corresponda) — mejora conversión fuera de España.
- 🟢 SEO técnico multi-idioma: hreflang, sitemap por idioma, metadatos traducidos (usa
  `generateSeoMeta` como referencia de calidad).
- 🟢 Referido/afiliado simple ("invita y gana 1 mes gratis") para crecimiento viral —
  encaja con la captura de leads ya existente.
- 🟡 Claim "X% más barato / traspasa tu web" — solo cuando exista la cifra/feature (no
  inventar). (heredado del backlog anterior)
- 🟢 Prueba social real por idioma/región en landing/pricing (sin inventar testimonios).

## Calidad
- ✅ **Tests AdminController** (12 tests: suspend, extendGrace, reactivate + access control).
- ✅ Cobertura ampliada masivamente: 244 tests (billing/webhooks, admin, WhatsApp, analytics,
  leads/CSV, ciclo de vida de proyectos, password reset, sitemap, suspensión…).
- 🟢 Revisar accesibilidad/responsive de las páginas nuevas.
- ✅ **Prompt caching de Anthropic** — el bloque `system` va ahora con `cache_control:
  ephemeral` en `AnthropicService::requestText` (lecturas de caché a ~10% del precio;
  ahorra en reintentos, ráfagas de ediciones del mismo usuario y usuarios concurrentes).
  `readStream` convierte los tokens cacheados a equivalentes de tarifa normal
  (escritura ×1,25, lectura ×0,10) para que `AiBudgetGuard` siga contando bien.

## Hecho recientemente (2026-07-03, integración a master)
- Integradas a master las ~30 ramas de dos semanas de auto-mejora (una rama por mejora)
  + todo el trabajo de la sesión (3D, motion, prompt caching, autosave). Duplicados de la
  misma mejora descartados con criterio (se quedó la mejor versión de cada familia).
  Arreglados en la integración: webhook WhatsApp fail-closed (tests ahora firman con HMAC),
  test de suspensión adaptado a la semántica segura (admin_suspended_at), y un error de
  sintaxis que venía en una rama. Suite final: 244/244 verde, build OK.

## Hecho recientemente (2026-07-03)
- Motor 3D propio (`hero3d.js`) + `tilt-3d` para un nivel visual de agencia premium
  (referencia motionsites.ai), integrado en plantillas y en la generación por IA.
- Ese mismo lenguaje visual llevado a la propia app: `Hero3D.tsx` (port React del motor,
  cero dependencias 3D) + `Motion.tsx` (framer-motion) en landing, layouts, dashboard y
  wizard; hero 3D extendido a 5 plantillas más (servicios, abogados, coach, inmobiliaria,
  fotógrafo) con `tilt-3d` en tarjetas clave; autosave con debounce en el editor.
  Suite 96/96 verde, `npm run build` OK (framer-motion en chunk propio lazy de ~37 kB gzip).

## Hecho recientemente (2026-06-30)
- Captura de leads completa (6 tests, suite 96 verde). FAQPage JSON-LD. Estudios de producto/mercado.
- ✅ Tests para `pagepolis:expiry-reminders` (5 tests, cobertura cero → completa para el comando de retención de suscripciones).

## Ideas nuevas
- 🟢 Tests para `pagepolis:weekly-reports` (mismo patrón; cero cobertura para el comando de informes semanales).
- 🟢 Arreglar mutación de Carbon en `SendWeeklyReports::buildStats()` (`$weekAgo->subDay()` muta el objeto, sesga la comparación semanal 8 días vs 7 días).
- 🟢 **Mismo fallo silencioso en `DeploySite` para el re-despliegue tras editar** (no en el
  aprovisionamiento inicial, ya arreglado). `App\Jobs\DeploySite::handle()` (usado por
  `Project::deployToLiveDomain()` cuando editas una web ya publicada en dominio propio)
  tiene el mismo patrón: catch → `Log::error` + `$this->fail($e)`, sin tocar el estado.
  Si falla, el proyecto sigue marcado "Publicado" en el Dashboard pero el contenido en
  vivo se queda desactualizado con el cambio que el cliente acaba de hacer, sin ningún
  aviso. Añadir un campo tipo `deploy_failed_at` en `Project` (o reusar `domain_status`)
  y mostrarlo en el Dashboard/Editor.
- 🟢 **Panel de SEO editable en el editor** — `AiController::seo` genera y guarda
  `title`/`description`/`keywords`/`og_*`/`schema` (se inyectan en el `<head>` del sitio
  publicado vía `SiteController`), pero el editor (`Pages/Editor/Index.tsx`) solo muestra
  un botón "SEO activo/Generar SEO"; el cliente nunca ve ni puede corregir lo que la IA
  escribió para su ficha de Google/redes. Con lo que vende el producto ("ayuda al SEO
  local"), publicar metadatos erróneos sin forma de arreglarlos a mano es un hueco real.
  Añadir un panel/modal con esos campos editables, reutilizando el guardado existente
  (`EditorController::save`, añadiendo `seo_meta` a la validación).
