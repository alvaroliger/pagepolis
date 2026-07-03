# 🤖 PagePolis — Backlog de auto-mejora

Roadmap vivo del agente de auto-mejora. La app Laravel está en **`pagepolis/`** (subcarpeta del repo).
Regla: coge **1** ítem no bloqueado de mayor valor, impleméntalo en una **rama nueva**, deja los tests
verdes (`cd pagepolis && php artisan test`), abre **PR**. **No desplegar, no tocar `main`.**

Leyenda: 🟢 listo · 🟡 decisión de Álvaro · 🔵 grande · ✅ hecho

## Ingresos / conversión (lo que hace que el cliente pague)
- ✅ **Captura de leads** en las webs generadas (endpoint + email al dueño + bandeja `/mensajes`).
- ✅ **Vender la captura de leads** en landing/precios — feature card + línea en pricing + FAQ, en los 6 idiomas (PR #40).
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

## Hecho recientemente (2026-06-29)
- Captura de leads completa (6 tests, suite 96 verde). FAQPage JSON-LD. Estudios de producto/mercado.
