# 🤖 PagePolis — Backlog de auto-mejora

Roadmap vivo del agente de auto-mejora. La app Laravel está en **`pagepolis/`** (subcarpeta del repo).
Regla: coge **1** ítem no bloqueado de mayor valor, impleméntalo en una **rama nueva**, deja los tests
verdes (`cd pagepolis && php artisan test`), abre **PR**. **No desplegar, no tocar `main`.**

Leyenda: 🟢 listo · 🟡 decisión de Álvaro · 🔵 grande · ✅ hecho

## ⚠️ Nota operativa (2026-07-21): comprobar PRs abiertas antes de picar un ítem
Hay **decenas de PRs abiertas sin fusionar** (varias sesiones han estado picando el
backlog en paralelo sin verse entre sí): al menos 5 PRs distintas implementando la misma
insignia/filtro "3D" en la galería de plantillas, 3 implementando variantes de geometría
del hero 3D, y 2 implementando la guardia de rendimiento en gama baja. `git branch -r` en
un clon recién hecho de esta sesión **solo trae `origin/master`** — no basta para ver el
trabajo en curso, y da una falsa sensación de que el backlog está "limpio".
Antes de picar un ítem:
1. `git fetch origin <rama>` no sirve si no sabes el nombre — usa la API de GitHub
   (`list_pull_requests` con `state=open`, paginando hasta el final) para ver TODAS las
   PRs abiertas, no solo las ramas que ya conoces.
2. Compara por **significado**, no solo por nombre de rama: ideas iguales han tenido
   nombres de rama totalmente distintos (`perf/hero3d-low-end-throttle` vs
   `feature/hero3d-low-end-device-guard` vs `feature/hero3d-low-end-device-perf`, las tres
   la misma mejora). Si dudas, mira el diff real (`pull_request_read` método `get_diff`).
3. Si el ítem que ibas a coger ya tiene una PR abierta razonable, NO abras otra: pivota a
   un ítem sin cubrir. Con tantas PRs abiertas, es más valioso encontrar un hueco genuino
   que añadir una 6ª variante de lo mismo.

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
- ✅ Ampliar `resources/js/i18n/locales/*.json` más allá de es/en: pt, fr, de, it ya están
  completos (landing/checkout/FAQ traducidos en los 6 idiomas, `i18n/index.ts` detecta
  idioma guardado/navegador con fallback a es).
- ✅ **Traducir la navegación del panel autenticado** — `AuthenticatedLayout.tsx` (nav +
  menú de avatar) estaba en español fijo pese a que la landing ya soporta 6 idiomas; ahora
  usa `useTranslation` (`authNav.*`) y muestra `<LanguageSelector />` en todas las páginas
  autenticadas, no solo antes de iniciar sesión.
- 🟢 **Extender el mismo patrón i18n al resto de páginas autenticadas** (Dashboard,
  Editor, Perfil, Plantillas, Analítica, Mensajes) — de momento solo el "chrome" fijo
  (`AuthenticatedLayout`) está traducido; el contenido propio de cada página sigue en
  español fijo. Es un ítem grande (🔵 candidato) si se hace de una vez; mejor trocearlo
  página a página en PRs separadas.
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

## Hecho recientemente (2026-07-21)
- Navegación del panel de cliente traducida a los 6 idiomas (`AuthenticatedLayout.tsx` +
  claves `authNav.*` en `i18n/locales/*.json`) y selector de idioma añadido junto al
  avatar — hasta ahora un cliente que usaba la app en inglés/francés/etc. veía toda la
  navegación en español fijo nada más iniciar sesión, sin forma de cambiarlo desde dentro
  de la app. Verificado con `php artisan test` (244/244 verdes) y `npm run build`.

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
- 🟢 Traducir `Dashboard/Index.tsx` (título, estados vacíos, tarjetas de proyecto, modal
  de papelera) a los 6 idiomas — siguiente página más visitada tras el login, ahora que
  el chrome de `AuthenticatedLayout` ya está traducido.
- 🟢 Traducir `Pages/Editor/Index.tsx` (etiquetas de pestañas HTML/CSS/JS, botones de
  guardar/publicar, mensajes de estado) — puede convivir con el trabajo de accesibilidad
  del editor que ya haya en marcha, son cambios independientes (solo texto vs. solo aria).
- 🟢 Auditar si `resources/js/Pages/Profile/Edit.tsx` y `Pages/Analytics/Index.tsx`
  también tienen texto fijo en español pese a que ya reciben `useTranslation` en otras
  páginas del mismo árbol — mismo patrón que el resto de ítems i18n de esta lista.
