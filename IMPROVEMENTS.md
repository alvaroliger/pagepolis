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
- ✅ **`Publish/Index.tsx` (el paso de pago) traducido y con precios por idioma** — la
  página donde el cliente realmente paga (`/publicar`, paso "Elige tu plan") estaba
  100% en español fijo, sin ni una llamada a `useTranslation`, con `9,99€`/`14,99€`/
  `119,88€` hardcodeados — un cliente que navegaba toda la web en inglés/francés/alemán/
  portugués/italiano llegaba al momento de pagar y todo volvía de golpe al español, con
  precios en euros aunque el resto de la web ya le mostraba su moneda (p. ej. `$10.99`
  en la Landing en inglés). Ahora usa el mismo bloque `t('currency', {returnObjects:true})`
  que ya usa `Landing.tsx` (mismos precios `pro_monthly`/`pro_yearly`/`pro_yearly_total`
  por idioma, sin inventar tasas de cambio) y un namespace `publish.*` nuevo en los 6
  locales. Cubierto por `tests/Unit/LocaleParityTest.php` (verifica que las claves de
  `publish` coincidan exactamente entre los 6 idiomas — evita que una futura edición
  añada una clave en un idioma y se olvide de los otros 5, cayendo en el fallback
  español/en clave cruda).
- 🟡 El precio mostrado en `pt` (moneda BRL) y `en` (USD) son cifras de mercado fijadas a
  mano en los locales, no una conversión real de la tarifa de Stripe (que sigue siendo un
  único `Price` en EUR vía `STRIPE_PRICE_MONTHLY`/`STRIPE_PRICE_YEARLY`). Cobrar de verdad
  en la moneda local requeriría Stripe Prices multi-moneda — decisión de negocio de Álvaro,
  no un cambio de interfaz.
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

## ⚠️ Coordinación (2026-07-13): backlog de PRs sin revisar muy saturado
A fecha de hoy hay **13 PRs abiertas sin revisar** (#44 a #56), varias de ellas
implementando el MISMO ítem por triplicado porque distintas sesiones no vieron el trabajo
de las demás (el clon de esta sesión solo tenía `origin/master` en `git branch -r` hasta
hacer `git fetch origin` — sin ese fetch, las ramas de otras sesiones son invisibles).
**Antes de picar un ítem del backlog, haced siempre `git fetch origin && git branch -r`**
(no solo mirar el `git branch -r` del clon inicial) y revisad los PRs abiertos del repo,
no solo `git log` de `master`. PRs actuales y qué cubren, para no repetir:
- #44, #51: insignia + filtro "Simple | 3D" en la galería de plantillas (mismo ítem, 2 PRs).
- #45: throttle del hero 3D en gama baja.
- #46: variantes de geometría/color del hero 3D.
- #47: navegación móvil del panel autenticado (hamburguesa).
- #48: modales accesibles (focus trap, `role="dialog"`, Escape) en Plantillas/Dashboard/Editor.
- #49: elección "Clásica | 3D" en el wizard de creación con IA.
- #50: fix de que la vista previa de plantillas no ejecutaba el `js` (hero 3D en blanco).
- #52: sustituye `confirm()` nativo por modales propios en borrado irreversible.
- #53, #54, #55: `aria-*` sueltos (gráfico de Analítica, emoji de Mensajes, toggle de viewport del Editor).
- (esta sesión, rama `feat/publish-checkout-i18n`): `Publish/Index.tsx` traducido a los 6
  idiomas + precios por idioma — no toca `Templates/Index.tsx` ni `TemplateController`, sin
  conflicto con las anteriores.
Antes de que Álvaro las revise/mergee, tened en cuenta que hay conflictos de fondo (varias tocan `Templates/Index.tsx`
a la vez: #44, #48, #50, #51 se van a pisar entre sí) antes de seguir apilando más PRs sobre
la galería de plantillas — probablemente compensa que Álvaro elija una versión de cada
familia y cierre el resto manualmente en vez de que una sesión intente fusionarlas sola.

## Ideas nuevas
- 🟢 Tests para `pagepolis:weekly-reports` (mismo patrón; cero cobertura para el comando de informes semanales).
- 🟢 Arreglar mutación de Carbon en `SendWeeklyReports::buildStats()` (`$weekAgo->subDay()` muta el objeto, sesga la comparación semanal 8 días vs 7 días).
- 🟢 Los mensajes de error que vienen directamente del backend en `Publish/Index.tsx`
  (`e.response?.data?.message`) siguen sin traducir — son las respuestas de validación de
  Laravel, en español siempre. Si se quiere un checkout 100% coherente por idioma, falta
  i18n también en las respuestas de error del backend (`ProjectController`, `BillingController`),
  no solo en el frontend.
