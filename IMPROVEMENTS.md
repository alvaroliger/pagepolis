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

## Hecho recientemente (2026-06-30)
- Captura de leads completa (6 tests, suite 96 verde). FAQPage JSON-LD. Estudios de producto/mercado.
- ✅ **Fix `SuspendExpiredSubscriptions` zombie state**: external service calls now happen before clearing `grace_period_ends_at`; if Dinahosting/Nginx fails, the user retains their grace period and the next daily run retries cleanly. DB cleanup wrapped in a transaction. 10 new tests, suite 106 green. Test infrastructure also fixed (APP_KEY, AnthropicService null-safety, BillingWebhookTest `status()`).

## Ideas adicionales
- 🟢 Tests para `SendExpiryReminders` y `SendWeeklyReports` (comandos de billing sin cobertura).
- 🟢 Test para `ProfileController::exportData` (exportación GDPR).
