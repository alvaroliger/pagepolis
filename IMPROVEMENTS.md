# 🤖 PagePolis — Backlog de auto-mejora

Roadmap vivo del agente de auto-mejora. La app Laravel está en **`pagepolis/`** (subcarpeta del repo).
Regla: coge **1** ítem no bloqueado de mayor valor, impleméntalo en una **rama nueva**, deja los tests
verdes (`cd pagepolis && php artisan test`), abre **PR**. **No desplegar, no tocar `main`.**

Leyenda: 🟢 listo · 🟡 decisión de Álvaro · 🔵 grande · ✅ hecho · ⏳ ya tiene PR(s) abierta(s) sin fusionar

## ⚠️ Aviso de coordinación (2026-07-21)
Hay **48 PRs abiertas** (#44–#91) sin fusionar desde la integración del 2026-07-03 —
ninguna sesión desde entonces las ha mergeado. Varias mejoras están duplicadas 3-4
veces (p.ej. variantes de geometría del hero 3D: #46, #57, #79, y una 4ª rama
`feature/hero3d-shape-variants` sin PR asociada; filtro/badge 3D de la galería de
plantillas: #44, #51, #67, #71, #75, #78, #89). `git branch -r` **no** basta para
detectar esto — el remoto local puede no tener esas ramas fetcheadas. **Antes de
elegir ítem, consulta las PRs abiertas del repo (API/MCP de GitHub, no solo git
log/branch -r)** para no repetir trabajo ya enviado. Este documento marca `✅` solo
lo que está en `master`; lo que ya tiene PR abierta pero sigue sin fusionar no se
marca aquí para no dar una falsa sensación de "hecho".

## 🎯 FOCO DE LA SEMANA (encargo de Álvaro, 2026-07-03 → 2026-07-10, ya vencido)
Álvaro estuvo desconectado esa semana; la ventana ya pasó pero el orden de prioridad
sigue siendo válido salvo mejor criterio:
1. **Interfaz de PagePolis y plantillas con 3D** — ítems de la sección "Diseño / nivel
   visual" (variantes del hero 3D, rendimiento en móviles de gama baja, extenderlo donde
   aporte). El listón sigue siendo motionsites.ai / agencia premium.
2. **Mejorar la web y el servicio para los clientes** — wizard, onboarding, editor,
   captura de leads, i18n/crecimiento: todo lo que haga que un cliente reciba más valor
   esta semana sin necesitar decisiones de Álvaro.
Sigue aplicando el listón de calidad de siempre: nada de churn; si no hay mejora clara,
no abras PR.

## ⚠️ Aviso (2026-07-20): decenas de ramas abiertas sin fusionar
`git branch -r` muestra >100 ramas remotas sin fusionar a `master`, muchas duplicadas
varias veces sobre la MISMA idea (p. ej. ~13 ramas de "hero3d bajo rendimiento en gama
baja", ~10 de "badge/filtro/preview 3D en la galería de plantillas", ~5 de "elección
Simple/3D en el wizard", 3 de "variantes de geometría del hero 3D"). Parece que esta
rutina (u otras sesiones en paralelo) ha corrido muchas más veces de las que se han
revisado/fusionado. **Antes de coger un ítem de "Diseño / nivel visual" o de galería de
plantillas, comprueba primero `git branch -r` + `git log <rama> --oneline` para no
sumar otro duplicado** — con esta cantidad de ramas abiertas es muy probable que ya
exista una para la idea obvia. Álvaro necesita revisar y fusionar (o cerrar) ese
backlog de PRs antes de que siga creciendo; si una sesión detecta que el ítem que iba a
coger ya tiene 2+ ramas abiertas equivalentes, mejor no añadir una tercera y buscar
otro ítem o una mejora de calidad/corrección de bug en su lugar.

## Ingresos / conversión (lo que hace que el cliente pague)
- ✅ **Captura de leads** en las webs generadas (endpoint + email al dueño + bandeja `/mensajes`).
- ✅ **Vender la captura de leads** en landing/precios — feature card + línea en pricing + FAQ, en los 6 idiomas (PR #40).
- ✅ **Prueba social real** — el hero muestra el nº real de webs publicadas (LandingController +
  caché 1h) en vez de una cifra inventada.
- 🔵 **Blog SEO** (motor de tráfico orgánico; cada artículo es un embudo).
- 🟡 Claim "X% más barato / traspasa tu web" — solo cuando exista la cifra/feature (no inventar).
- ✅ Secuencia de email post-registro (bienvenida + nudge de publicación programado).
- ✅ **Fix: el flujo de publicación podía cobrar sin entregar dominio** — el checklist de
  onboarding ("Publica tu web" / "Conecta tu dominio propio") y el banner "Ver planes" del
  Dashboard enlazaban a `/publicar` **sin `project_id`**. Sin proyecto, el botón
  "Continuar →" de `Publish/Index.tsx` saltaba en silencio al paso de pago sin llamar a
  `reserveDomain()`, así que nunca se creaba el `Domain` "pending" que el webhook de
  Stripe (`HandleStripeWebhook::provisionPendingDomain`) necesita para provisionar tras el
  cobro — el cliente podía acabar pagando una suscripción sin que se le comprara ni
  desplegara ningún dominio. Arreglado: los enlaces del Dashboard y los dos avisos de
  cuota de IA agotada del Editor (`Editor/Index.tsx`, que ya sabía el `project.id` — solo
  no lo pasaba en el enlace) ahora incluyen `project_id`, y el botón "Continuar →" de
  `Publish/Index.tsx` queda deshabilitado (con aviso) si no hay proyecto en vez de saltar
  el paso. 3 tests nuevos (`PublishControllerTest`) fijan el contrato del que depende el
  front.

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
- ⏳ Variantes del hero 3D (2-3 geometrías/paletas distintas) — **ya tiene PRs abiertas
  sin fusionar** (#46 `hero3d-variants`, #57 `hero3d-geometry-variants`, #79
  `hero3d-geometry-palette-variants`): no reimplementar hasta que Álvaro fusione una y
  cierre las demás.
- ⏳ Auditar rendimiento del hero 3D en móviles de gama baja (FPS, batería) — **ya tiene
  PRs abiertas** (#45 `perf/hero3d-low-end-throttle`, #90
  `feature/hero3d-low-end-device-guard`): no reimplementar.
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
- ✅ **Arreglado símbolo de moneda duplicado en el ahorro anual del pricing** — la nota
  "-2 meses gratis" bajo cada plan (`pricing.yearly_note_basic/pro`, los 6 idiomas)
  mostraba el símbolo dos veces (p. ej. "€83,88€/año", "$$95.88/year", "R$R$478,80/ano")
  porque el string ya interpolado (`{sym}{total}`) se pasaba a una traducción que volvía
  a anteponer/añadir el símbolo. Quitado el símbolo hardcodeado de las 12 claves
  (`resources/js/i18n/locales/*.json`); ahora coincide con el resto del pricing
  ("€83,88/año"). Verificado: `php artisan test` (244/244), `npm run build` OK.
- 🟡 **Riesgo real detectado (no arreglado en este PR):** el pricing (`Landing.tsx`)
  muestra precios "localizados" per-idioma con monedas distintas (en → `$10.99`,
  pt → `R$59.90`, resto → `€9,99`) pero el cobro real en Stripe es SIEMPRE en EUR
  (`CASHIER_CURRENCY=eur`, un único `STRIPE_PRICE_MONTHLY`/`YEARLY`) sin relación
  alguna con esas cifras y sin ningún aviso al cliente. Un visitante en inglés ve
  "$10.99/mo" pero se le cobra en euros — mismatch de moneda no revelado, riesgo de
  confianza/legal. Necesita decisión de Álvaro sobre el enfoque correcto: (a) añadir
  disclaimer claro tipo "precio de referencia, cobro en EUR" (rápido, no cambia
  Stripe), o (b) montar de verdad precios/objetos Stripe multi-moneda (grande). Hasta
  que se decida, no tocar este número — ver `feat/publish-checkout-i18n` (rama
  abierta), que extiende el mismo patrón a la página de pago sin resolver el fondo.
- 🟢 Precios localizados por región (moneda mostrada según IP/locale, aunque el cobro siga
  en Stripe con la moneda que corresponda) — mejora conversión fuera de España. Ver el
  ítem 🟡 de arriba: probablemente haya que resolver el disclaimer/mismatch primero.
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
- ⏳ Revisar accesibilidad/responsive de las páginas nuevas — **ya cubierto por varias
  PRs abiertas** (#48, #52, #53, #54, #55, #58, #59, #81, #84, #87): revisar el estado
  de esas PRs antes de abrir una más sobre el mismo tema.
- ✅ **Prompt caching de Anthropic** — el bloque `system` va ahora con `cache_control:
  ephemeral` en `AnthropicService::requestText` (lecturas de caché a ~10% del precio;
  ahorra en reintentos, ráfagas de ediciones del mismo usuario y usuarios concurrentes).
  `readStream` convierte los tokens cacheados a equivalentes de tarifa normal
  (escritura ×1,25, lectura ×0,10) para que `AiBudgetGuard` siga contando bien.

## Hecho recientemente (2026-07-12)
- Throttle del hero 3D en dispositivos de gama baja (`isLowEndDevice()` en `hero3d.js` y
  `Hero3D.tsx`): menos figuras, `devicePixelRatio` tope en 1x, sin antialiasing. Suite
  244/244 verde, build OK.

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

## Hecho recientemente (2026-07-12)
- Modales accesibles por teclado en Templates, Dashboard y Editor (`role="dialog"`, foco
  atrapado, Escape para cerrar, foco devuelto al cerrar) + hook reutilizable
  `useModalA11y`. Arreglada también la miniatura de vista previa de plantillas, que no
  era operable con teclado. Verificado con Playwright contra una sesión real logueada
  (apertura por Enter, `aria-modal`/`aria-labelledby` presentes, foco entra y queda
  contenido, Escape cierra y devuelve el foco). Suite 244/244 verde, `npm run build` OK.

## Hecho recientemente (2026-06-30)
- Captura de leads completa (6 tests, suite 96 verde). FAQPage JSON-LD. Estudios de producto/mercado.
- ✅ Tests para `pagepolis:expiry-reminders` (5 tests, cobertura cero → completa para el comando de retención de suscripciones).

## Ideas nuevas
- 🟢 Tests para `pagepolis:weekly-reports` (mismo patrón; cero cobertura para el comando de informes semanales).
- 🟢 Arreglar mutación de Carbon en `SendWeeklyReports::buildStats()` (`$weekAgo->subDay()` muta el objeto, sesga la comparación semanal 8 días vs 7 días).
- 🟡 **Fusionar/cerrar el backlog de 48 PRs abiertas** — decisión de Álvaro, no de una
  sesión de auto-mejora: hay trabajo terminado y verificado (tests + build en verde en
  cada una) acumulando 18 días sin llegar a producción. Cuantas más se acumulen, más
  conflictos de merge y más difícil la integración (ver el aviso de coordinación arriba).
- 🟢 En `DomainController::reserve` / `PublishController`, considerar validar de forma
  más explícita que siempre hay un `project` antes de dejar avanzar el flujo de pago
  (defensa adicional a la ya añadida en `Publish/Index.tsx`) — por ejemplo, que
  `goToCheckout` rechace si no hay un dominio reservado en sesión/backend, no solo el
  front. Relacionado con el fix de `project_id` perdido de esta sesión.
- ✅ Comprobado (esta sesión): los emails transaccionales no enlazan a `/publicar`
  directamente (`nudge-publish.blade.php` enlaza a `/dashboard`), así que no repiten el
  bug de `project_id` perdido arreglado arriba.
