# 🤖 PagePolis — Backlog de auto-mejora

Roadmap vivo del agente de auto-mejora. La app Laravel está en **`pagepolis/`** (subcarpeta del repo).
Regla: coge **1** ítem no bloqueado de mayor valor, impleméntalo en una **rama nueva**, deja los tests
verdes (`cd pagepolis && php artisan test`), abre **PR**. **No desplegar, no tocar `main`.**

Leyenda: 🟢 listo · 🟠 ya hay PR(s) abiertos sin revisar (no reimplementar) · 🟡 decisión de Álvaro ·
🔵 grande · ✅ hecho

## ⚠️ Antes de empezar: comprueba el trabajo en curso (este archivo en `master` va con retraso)

`master` no se ha fusionado desde 2026-07-03 — hay **~40 PRs abiertos sin revisar** (aprox. #44-#83)
y más ramas sin PR. `git branch -r` justo después de clonar **solo muestra `origin/master`**; hace
falta `git fetch origin` (o `git ls-remote --heads origin`) para ver las ramas reales, y la lista de
PRs abiertos (`list_pull_requests` sobre `alvaroliger/pagepolis`) para saber qué ya está resuelto —
este mismo fichero en `master` no lo refleja porque ninguna PR se ha fusionado todavía. Varias
ejecuciones seguidas han reimplementado la misma idea por no hacer esta comprobación primero.

Ítems de esta lista que **ya tienen PR abierto** (no los repitas — si quieres mejorarlos, revisa el PR
existente en vez de abrir uno nuevo):
- Variantes de geometría/paleta del hero 3D → PRs #46, #57, #79.
- Rendimiento del hero 3D en gama baja (menos figuras / desactivar en `hardwareConcurrency` bajo) →
  PR #45 (+ varias ramas duplicadas sin PR: `perf/hero3d-low-end-*`).
- Elegir "Clásica / 3D interactiva" en el wizard de creación → PR #49.
- Galería de plantillas: insignia/filtro Simple↔3D → PRs #44, #51 (+ variantes: #67, #71, #75, #78).
- Vista previa de plantilla no mostraba el efecto 3D real → PR #50 (arreglado, pendiente de fusionar).
- Nav móvil del panel autenticado (hamburguesa) → PR #47.
- Modales sin `role=dialog`/foco atrapado (Plantillas, Dashboard, Editor) → PR #48.
- `confirm()` nativos en borrado irreversible (papelera, eliminar cuenta) → PR #52.
- Gráfico de Analítica sin equivalente de teclado → PR #53.
- Formularios de login/registro/recuperación con problemas de a11y/autofill → PR #58.
- Chips de filtro de categoría en la galería sin estado accesible → PR #59.
- Overlay de carga en la preview del editor mientras la IA genera → PR #60.
- Enlace "Ver web" en el editor para proyectos publicados → PR #61.
- Aviso cuando falla el guardado del editor en vez de fallar en silencio → PR #62.
- Buscador en Mensajes/Dashboard (leads y proyectos) → PRs #63, #64.
- Páginas de error 404/500 con marca propia → PR #65.
- Guardado del editor roto al vaciar HTML/CSS/JS/nombre → PR #66.
- Deshacer el último cambio de la IA en el editor → PR #69.
- Checkout traducido a los 6 idiomas con precio por idioma → PR #56.
- Tamaño de vista activo del editor (PC/Tablet/Móvil) sin estado accesible → PR #55.
- Emoji decorativo del buzón de mensajes sin `aria-hidden` → PR #54.
- Error 500 al abrir "Suscripción" sin ser suscriptor → PR #70.
- Feedback de progreso del wizard durante la generación con IA (1-3 min) → PR #72.
- Error de confirmación de contraseña sin feedback en Registro → PR #74.
- Error real al fallar cambio de contraseña / borrado de cuenta en Perfil → PR #73.
- Estados vacíos con CTA en Mensajes/Analítica → PR #80.
- Contador de usos de IA del editor accesible por teclado/táctil → PR #81.
- Despublicar un proyecto sin borrarlo → PR #82.
- Marcado manual de leídos/no leídos en `/mensajes` → PR #83.
- ✅ **Layout responsive del editor en móvil/tablet** — resuelto en esta misma sesión (ver más abajo).

Si Álvaro vuelve, lo más valioso que puede hacer alguien con acceso de repo es revisar/fusionar/cerrar
esos PRs antes de que seguir generando ramas produzca más duplicados.

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
- ✅ **Layout responsive del editor en móvil/tablet** — el split de 3 columnas (chat IA / vista
  previa / código, `w-72` + `flex-1` + `w-96`) no tenía ningún breakpoint: en un móvil real las tres
  columnas se apretujaban o desbordaban y el editor era inutilizable. Por debajo de `lg` (1024px)
  ahora se ve un panel a pantalla completa a la vez, con una barra de pestañas ("Chat IA" / "Vista
  previa" / "Código", esta última solo si el modo avanzado está activo) para cambiar entre ellos;
  en `lg` y superior el layout de escritorio no cambia. Cabecera superior con `flex-wrap` para no
  desbordar en pantallas estrechas. `resources/js/Pages/Editor/Index.tsx`.
- 🟢 Duplicar/clonar un proyecto existente como borrador nuevo (probar otra plantilla sin perder
  el original) — sin PR abierto todavía.
- 🟢 Analítica: filtro de rango de fechas + exportar a CSV (ya existe CSV de leads; falta en
  `AnalyticsController`) — sin PR abierto todavía.

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
- 🟠 Variantes del hero 3D (2-3 geometrías/paletas distintas) para que no todas las webs
  "tech" se vean idénticas — ver `buildIcosahedron()` en `hero3d.js` como base. PRs abiertos:
  #46, #57, #79 (revisar y quedarse con la mejor versión, cerrar el resto).
- 🟠 Auditar rendimiento del hero 3D en móviles de gama baja (FPS, batería) y bajar el
  nº de figuras o desactivarlo automáticamente si `navigator.hardwareConcurrency` es bajo.
  PR abierto: #45 (`perf/hero3d-low-end-throttle`).
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
- 🟠 Revisar accesibilidad/responsive de las páginas nuevas — buena parte ya cubierta por PRs
  abiertos (#47 nav móvil, #48 modales, #52 confirm() nativos, #53 gráfico de analítica, #54 emoji
  decorativo, #55 viewport del editor, #58 formularios de auth, #59 chips de categoría, #81 tooltip
  de uso de IA); revisar qué queda suelto antes de abrir uno nuevo.
- ✅ **Prompt caching de Anthropic** — el bloque `system` va ahora con `cache_control:
  ephemeral` en `AnthropicService::requestText` (lecturas de caché a ~10% del precio;
  ahorra en reintentos, ráfagas de ediciones del mismo usuario y usuarios concurrentes).
  `readStream` convierte los tokens cacheados a equivalentes de tarifa normal
  (escritura ×1,25, lectura ×0,10) para que `AiBudgetGuard` siga contando bien.

## Hecho recientemente (2026-07-20)
- **Layout responsive del editor en móvil/tablet** — antes de implementar, comprobé `git fetch
  origin` + `git branch -r` (123 ramas remotas) y `list_pull_requests` (39 PRs abiertos, #44-#83,
  ninguno fusionado desde el 2026-07-03). Mis dos primeros intentos de esta sesión (guardia de
  rendimiento del hero 3D en gama baja, variantes de geometría del hero 3D) resultaron ser
  duplicados casi exactos de PRs ya abiertos (#45 y #46/#57/#79 respectivamente), así que los
  descarté sin hacer push. Añadido el aviso de arriba con la lista completa de qué ya tiene PR,
  para que la próxima ejecución no repita el mismo trabajo.

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
