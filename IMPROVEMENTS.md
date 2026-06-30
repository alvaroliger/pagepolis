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

## Ideas futuras
- 🟢 Tests para WhatsApp webhook (flow de IA vía WA, firma de Meta, rate limit por usuario).
- 🟢 Rate limiting en `DomainController::check()` para evitar abuso de la API del registrador.
- 🟢 Tests para `BillingController::portal()` happy path (requiere mock de Stripe/Cashier).

## Hecho recientemente (2026-06-30)
- Suite de tests del checkout de facturación (5 tests); bug fix: `catch (\Throwable)` en BillingController para que errores de Stripe SDK no escapen el manejador; infraestructura de tests reparada (APP_KEY, AnthropicService, BillingWebhookTest). PR #15.

## Hecho recientemente (2026-06-29)
- Captura de leads completa (6 tests, suite 96 verde). FAQPage JSON-LD. Estudios de producto/mercado.
