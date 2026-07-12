# 🤖 PagePolis — Backlog de auto-mejora

Roadmap vivo del agente de auto-mejora. La app Laravel está en **`pagepolis/`** (subcarpeta del repo).
Regla: coge **1** ítem no bloqueado de mayor valor, impleméntalo en una **rama nueva**, deja los tests
verdes (`cd pagepolis && php artisan test`), abre **PR**. **No desplegar, no tocar `main`.**

Leyenda: 🟢 listo · 🟡 decisión de Álvaro · 🔵 grande · ✅ hecho

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
- ✅ **La galería de plantillas ahora muestra el hero 3D en vivo** — `TemplateController::index`
  solo seleccionaba `html`/`css` (nunca `js`), así que el motor 3D nunca se ejecutaba ni en las
  miniaturas ni en el modal de vista previa: las 6 plantillas con hero 3D se veían idénticas a
  las demás en el paso donde el cliente decide. Añadido `js` al select y al `<script>` inyectado
  en `TemplatePreview` y en el modal (`resources/js/Pages/Templates/Index.tsx`) — ahora el
  efecto 3D es visible y demostrable antes de elegir plantilla. 244/244 tests verdes, build OK.
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
- 🟢 Añadir columna `type`/`is_3d` a `templates` (migración + seeder) y una insignia "3D" +
  filtro en la galería (`Templates/Index.tsx`) — ahora que el hero 3D ya es visible en la
  vista previa (ver "Hecho" 2026-07-12), falta modelar la distinción explícitamente en vez
  de depender de que el HTML contenga el `<canvas>` por convención. Es la base real para el
  selector "Simple | 3D" del wizard de creación (`/crear`, que hoy no toca la tabla de
  plantillas en absoluto).
- 🟢 Vincular el wizard de IA (`/crear`) con las plantillas: hoy son dos flujos totalmente
  separados (IA genera desde cero vs. plantilla estática). Un paso "Simple | 3D" antes de
  generar, que pase el tipo elegido a `GenerateWebsiteJob`/`AnthropicService`, sería el
  primer end-to-end real del track de plantillas 3D.
- 🟢 Con hasta 6 plantillas renderizando WebGL a la vez en la galería (`/plantillas`), vigilar
  el nº de contextos WebGL simultáneos si se añaden más plantillas 3D — el `IntersectionObserver`
  de `hero3d.js` ya pausa las que no son visibles, pero conviene revisar en un móvil de gama
  baja real.
- 🟢 Arreglar mutación de Carbon en `SendWeeklyReports::buildStats()` (`$weekAgo->subDay()` muta el objeto, sesga la comparación semanal 8 días vs 7 días).
