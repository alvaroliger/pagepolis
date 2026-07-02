# 🤖 PagePolis — Backlog de auto-mejora

Roadmap vivo del agente de auto-mejora. La app Laravel está en **`pagepolis/`** (subcarpeta del repo).
Regla: coge **1** ítem no bloqueado de mayor valor, impleméntalo en una **rama nueva**, deja los tests
verdes (`cd pagepolis && php artisan test`), abre **PR**. **No desplegar, no tocar `main`.**

Leyenda: 🟢 listo · 🟡 decisión de Álvaro · 🔵 grande · ✅ hecho

## Ingresos / conversión (lo que hace que el cliente pague)
- ✅ **Captura de leads** en las webs generadas (endpoint + email al dueño + bandeja `/mensajes`).
- 🟢 **Vender la captura de leads** en landing/precios ("recibe los mensajes de tus clientes en tu
  correo") — ahora es verdad, se puede anunciar. Copy en `resources/js/i18n/locales/*.json`.
- 🟢 **Prueba social** real en landing/pricing (sin inventar testimonios).
- 🔵 **Blog SEO** (motor de tráfico orgánico; cada artículo es un embudo).
- 🟡 Claim "X% más barato / traspasa tu web" — solo cuando exista la cifra/feature (no inventar).
- 🟢 Secuencia de email post-registro empujando a publicar/mejorar.

## Producto
- 🟢 Wizard de creación: pulir validación/UX (`resources/js/Pages/Create/Index.tsx`).
- 🟢 Autosave en el editor con debounce.
- 🟢 Checklist de onboarding en el dashboard (publica → conecta dominio → comparte).
- 🟢 Code-splitting del editor (bundle ~531 kB) para Core Web Vitals.

## Calidad
- 🟢 Más tests de los flujos nuevos (leads ya cubierto: 6 tests).
- 🟢 Revisar accesibilidad/responsive de las páginas nuevas.
- 🟢 **Limpiar imágenes IA en `GenerateWebsiteJob::failed()`** — cuando el job muere por SIGKILL/timeout el `finally` no corre y los uploads (hasta 4×8 MB por llamada) se acumulan en disco sin borrarse. Fix: llamar `cleanupImages()` desde `failed()`.

## Hecho recientemente (2026-07-02)
- JSON-LD schema: `JSON_HEX_TAG | JSON_UNESCAPED_UNICODE` en los 3 call sites (SiteController, ProjectController, NginxService). 2 regression tests. Infra de tests arreglada (APP_KEY, ANTHROPIC_API_KEY, fecha AiBudgetGuard, BillingWebhook status). Suite: 98/98 verde. PR #28.

## Hecho anteriormente (2026-06-29)
- Captura de leads completa (6 tests, suite 96 verde). FAQPage JSON-LD. Estudios de producto/mercado.
