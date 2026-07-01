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
- ✅ Tests del webhook WhatsApp (15 tests, cubre firma HMAC, comandos, límite diario, IA).
- 🟢 Revisar accesibilidad/responsive de las páginas nuevas.
- 🟢 Unificar límite diario WhatsApp con AiRateLimit (20 vs 30/100 por tier).

## Hecho recientemente (2026-07-01)
- Fix off-by-1 en cuenta de cambios restantes vía WhatsApp (`WhatsAppController`, línea 198).
- Suite de tests WhatsApp (15 tests, 111/111 verde) + phpunit.xml con APP_KEY/ANTHROPIC/STRIPE.
- Fix dos tests pre-existentes: AiBudgetGuardTest (fecha cruzando mes) y BillingWebhookTest (status()).

## Hecho anteriormente (2026-06-29)
- Captura de leads completa (6 tests, suite 96 verde). FAQPage JSON-LD. Estudios de producto/mercado.
