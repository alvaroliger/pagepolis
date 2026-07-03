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

## Hecho recientemente (2026-07-03)
- ✅ **Tests de restablecimiento de contraseña** (9 tests: envío de enlace, token inválido, token de un solo uso, contraseña corta, confirmación errónea). Sin cobertura previa en ruta de auth crítica.
- ✅ **APP_KEY en phpunit.xml** — toda la suite fallaba con "No application encryption key" sin un .env presente.

## Hecho (2026-06-29)
- Captura de leads completa (6 tests, suite 96 verde). FAQPage JSON-LD. Estudios de producto/mercado.

## Ideas nuevas identificadas
- 🟢 Rate limiting en `POST /reset-password` (actualmente sin throttle; `POST /forgot-password` sí lo tiene).
- 🟢 Rate limiting en `POST /dominios/verificar` y `POST /dominios/reservar` (sin throttle; llaman a API externa).
- 🟢 Tests para `SuspendExpiredSubscriptions` y `SendWeeklyReports` (comandos sin cobertura).
- 🟢 Índice compuesto `(user_id, status)` en `projects` para las consultas de publicación y report semanal.
