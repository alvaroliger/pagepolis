# 🤖 PagePolis — Backlog de auto-mejora

Roadmap vivo del agente de auto-mejora. La app Laravel está en **`pagepolis/`** (subcarpeta del repo).
Regla: coge **1** ítem no bloqueado de mayor valor, impleméntalo en una **rama nueva**, deja los tests
verdes (`cd pagepolis && php artisan test`), abre **PR**. **No desplegar, no tocar `main`.**

Leyenda: 🟢 listo · 🟡 decisión de Álvaro · 🔵 grande · ✅ hecho

⚠️ **Antes de elegir tarea: revisa las PRs abiertas en GitHub, no solo `git branch -r`.**
A fecha 2026-07-17 hay decenas de ramas remotas y ~10 PRs abiertas (varias sesiones en
paralelo cada hora), muchas duplicando exactamente la misma idea sin saberlo entre sí
(p. ej. media docena de ramas casi idénticas para "insignia 3D en la galería de
plantillas", otras tantas para "rendimiento del hero 3D en gama baja" y para "elección
Simple/3D en el wizard"). `git branch -r` sin un `git fetch origin` previo puede mostrar
solo `origin/master` aunque existan decenas de ramas remotas — no basta para detectar
trabajo en curso. Haz `git fetch origin` y `mcp__github__list_pull_requests` (o
equivalente) con estado `open` antes de tocar código, y si tu idea ya tiene una PR
abierta, no dupliques: elige otra cosa del backlog.

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
- ✅ **Feedback de guardado en "Mi perfil"** — los formularios de datos personales y de
  cambio de contraseña (`Profile/Edit.tsx`) no mostraban ninguna confirmación al guardar
  con éxito (el banner `status` nunca se rellenaba desde el backend); ahora usan
  `recentlySuccessful` de Inertia para mostrar "Cambios guardados." / "Contraseña
  actualizada.". Además, el formulario de "Eliminar cuenta" nunca leía `errors` — si el
  cliente escribía mal su contraseña actual, el botón simplemente volvía a su estado
  normal sin explicar por qué no se había eliminado la cuenta; ahora se muestra el error.
- ✅ Wizard de creación: validación/UX pulida (límite de caracteres, botón deshabilitado si inválido).
- ✅ **Autosave en el editor con debounce** — guarda solo 2,5 s después del último cambio
  (html/css/js/nombre), pospuesto si hay guardado o generación IA en curso
  (`resources/js/Pages/Editor/Index.tsx`).
- ✅ Checklist de onboarding en el dashboard.
- ✅ Code-splitting del editor: CodeMirror extraído a chunk vendor propio (app-*.js ya no lo arrastra).

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
- 🟡 **(PR #67 abierta, no fusionada)** Insignia "3D" + filtro "Todas / Simple / 3D" en
  la galería de plantillas (`Templates/Index.tsx`, `TemplateController`). No reimplementar
  hasta que se fusione o se cierre. Buen follow-up una vez esté en master: la vista previa
  (miniatura y modal) sigue mostrando el HTML/CSS estático sin ejecutar JS, así que el
  hero 3D no se ve animado hasta publicar la web — inyectar el motor `hero3d.js` real en
  el modal de vista previa (solo para plantillas con `<canvas data-hero3d>`) para que el
  cliente vea el efecto en movimiento antes de elegir.
- 🟢 Variantes del hero 3D (2-3 geometrías/paletas distintas) para que no todas las webs
  "tech" se vean idénticas — ver `buildIcosahedron()` en `hero3d.js` como base.
- 🟢 Auditar rendimiento del hero 3D en móviles de gama baja (FPS, batería) y bajar el
  nº de figuras o desactivarlo automáticamente si `navigator.hardwareConcurrency` es bajo.
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
- ✅ **Mensajes de validación/autenticación en español real** — la app fuerza
  `config('app.locale') = 'es'` pero no existía ningún `lang/es/*.php`: Laravel no
  encontraba traducción y devolvía la clave interna sin traducir (p. ej.
  `"validation.required"`, `"auth.failed"`, `"validation.current_password"`) tal cual en
  pantalla, en TODOS los formularios de la app (registro, login, bloqueo por intentos,
  perfil, editor, leads, etc.) — un cliente real veía literalmente claves internas de
  Laravel en vez de un mensaje. Añadidos `lang/es/validation.php`, `lang/es/auth.php` y
  `lang/es/passwords.php` (traducción completa de los archivos que Laravel trae en
  inglés, con `attributes` para los nombres de campo reales de la app). Verificado con
  3 tests nuevos (`SpanishValidationMessagesTest`: registro, login fallido, eliminar
  cuenta con contraseña incorrecta) y a mano en el navegador.
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

## Hecho recientemente (2026-07-17)
- Mensajes de validación/autenticación traducidos al español real (`lang/es/*.php`) —
  antes se veían claves internas de Laravel sin traducir en toda la app. Feedback de
  guardado (`recentlySuccessful`) y error visible al fallar "Eliminar cuenta" en
  `Profile/Edit.tsx`. Suite 248/248 verde, `npm run build` OK, verificado con Playwright.

## Ideas nuevas
- 🟢 Auditar el resto de vistas Blade/notificaciones que usan `__()`/`trans()` fuera de
  validación (p. ej. emails transaccionales, notificaciones de Laravel) para confirmar
  que también salen en español y no en inglés/clave cruda — `lang/es/validation.php`,
  `auth.php` y `passwords.php` ya están cubiertos, pero puede haber más strings del
  framework (p. ej. `Illuminate\Notifications\Notification` por defecto) sin traducir.
- 🟢 Tests para `pagepolis:weekly-reports` (mismo patrón; cero cobertura para el comando de informes semanales).
- 🟢 Arreglar mutación de Carbon en `SendWeeklyReports::buildStats()` (`$weekAgo->subDay()` muta el objeto, sesga la comparación semanal 8 días vs 7 días).
- 🟡 **Ya hay ramas/PRs en curso, no dupliques sin comprobar antes:** elección explícita
  "Simple vs 3D" en el wizard de creación con IA (varias ramas `feature/wizard-*-3d-*` /
  `feature/wizard-*page-type*`) y auditoría de rendimiento del hero 3D en gama baja
  (varias ramas `perf/hero3d-low-end-*` / `feature/hero3d-low-end-*`). Antes de tocar
  cualquiera de estos dos temas, revisa qué PR sigue abierta y sobre esa continúa (o
  revisa por qué ninguna se fusionó todavía) en lugar de abrir una rama más.
