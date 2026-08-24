# ISSUES — PagePolis y Soluciones (auditoría de producción)

Rama: `produccion-hardening-2026-08`. Registro vivo de todo lo detectado, con severidad y estado.
Severidad: 🔴 crítico · 🟠 alto · 🟡 medio · 🟢 bajo/mejora.
Estado: `PENDIENTE` · `EN CURSO` · `RESUELTO` · `INFO` (no requiere código, para el informe).

> Este documento es la fuente de verdad de la auditoría. La decisión de pasar a producción
> (dominios/pagos/deploy reales) NO está tomada — es de Álvaro tras revisar esto.

---

## Bloque 1 — Rendimiento y entorno

| # | Sev | Problema | Estado |
|---|-----|----------|--------|
| B1-1 | 🔴 | `php artisan serve` es mono-hilo en Windows: la galería (218 miniaturas) satura el único hilo y cualquier otra petición da "localhost rechazó la conexión". | RESUELTO (FrankenPHP, 24 hilos) |
| B1-2 | 🟠 | OPcache desactivado en el PHP de Windows → recompila Laravel entero en cada petición (2-6 s/petición). | RESUELTO (activado; 0,2-0,5 s con caché caliente) |
| B1-3 | 🟠 | `composer dev` inservible en Windows: `php artisan pail` necesita `pcntl` (inexistente en PHP Windows) y con `--kill-others` mata a server/queue/vite. | RESUELTO (script `scripts/dev-local.ps1` sin pail) |
| B1-4 | 🟡 | Verificar causas de lentitud ADICIONALES: N+1 en el listado, generación de preview bloqueante, assets sin cachear. | EN CURSO |
| B1-5 | 🟡 | No hay caché de aplicación real (Redis/config) para prod; hoy `CACHE_STORE` por defecto. | PENDIENTE |
| B1-6 | 🟡 | Stack de producción: hoy no hay receta reproducible (nginx+php-fpm o FrankenPHP en el VPS). Deploy usa nginx (`scripts/deploy.py`) pero sin OPcache/worker garantizados. | PENDIENTE |

## Bloque 2 — Completar la aplicación (botones muertos, flujos)

| # | Sev | Problema | Estado |
|---|-----|----------|--------|
| B2-1 | 🟠 | Botones/enlaces sin destino en plantillas y vistas de la app ("solo la primera vista está hecha"). | EN CURSO (auditoría) |
| B2-2 | 🟡 | Flujos de producto incompletos por confirmar: recuperación de contraseña, facturación, panel admin. | PENDIENTE |

## Bloque 3 — Catálogo de plantillas

| # | Sev | Problema | Estado |
|---|-----|----------|--------|
| B3-1 | 🟡 | Plantillas casi duplicadas (mismo layout, solo cambia un asset). Auditar y consolidar. | PENDIENTE |
| B3-2 | 🔴 | **Preview en negro / "rechazó la conexión"**: (a) `X-Frame-Options: DENY` global bloqueaba el iframe same-origin de la preview; (b) el sandbox de la galería era `allow-scripts` (origen opaco) → el motor no pintaba. | RESUELTO |
| B3-3 | 🟠 | **84 plantillas hotlinkean vídeos de `videos.pexels.com`** (CDN de terceros). Frágil en prod: Pexels puede bloquear hotlink, va lento, y son las webs de tus clientes las que dependen de ello. Hay que auto-hospedar o dar poster+lazy fiable. | PENDIENTE |

## Bloque 4 — Marketplace (que el cliente venda sus productos)

| # | Sev | Problema | Estado |
|---|-----|----------|--------|
| B4-1 | 🔴 | No existe gestión de catálogo de productos por cliente ni carrito/checkout para el visitante final. | PENDIENTE |
| B4-2 | 🔴 | Pagos a terceros (dinero al cliente, comisión a la plataforma): evaluar Stripe Connect. | PENDIENTE |
| B4-3 | 🟠 | Reembolsos/contracargos del cliente final. | PENDIENTE |

## Bloque 5 — Compra automática de dominios + fondo de reserva

| # | Sev | Problema | Estado |
|---|-----|----------|--------|
| B5-1 | 🔴 | Compra automática de dominio al pagar (API registrador, sandbox). | PENDIENTE |
| B5-2 | 🔴 | Fondo de reserva prorrateado + histórico auditable de movimientos. | PENDIENTE |
| B5-3 | 🟠 | Casos límite: cancelación, dunning, precio premium, pago reversible, doble compra (lock), fallo parcial (saga/reintento). | PENDIENTE |

## Bloque 6 — Transversal

| # | Sev | Problema | Estado |
|---|-----|----------|--------|
| B6-1 | 🔴 | Multi-tenancy: aislamiento real entre clientes (modelo a documentar). | PENDIENTE |
| B6-2 | 🟠 | Seguridad: secretos fuera del código, CSRF/XSS/SQLi, rate limiting checkout, antibots. | EN CURSO |
| B6-3 | 🟠 | Webhooks idempotentes (pagos, registrador). | PENDIENTE |
| B6-4 | 🟡 | Observabilidad: logging/alertas del flujo de dinero, auditoría. | PENDIENTE |
| B6-5 | 🟡 | Backups de BD + recuperación. | PENDIENTE |
| B6-6 | 🟡 | Suspensión de web por impago del servicio (aviso, conservación de contenido). | PENDIENTE |
| B6-7 | 🟢 | Legal/fiscal (solo informe): facturación, IVA marketplace, política de dominio. | INFO |

---

## Hallazgos confirmados (detalle)

### B3-2 — Preview de plantillas en negro / "rechazó la conexión" (RESUELTO)
- **Capa 1 (framing):** `app/Http/Middleware/SecurityHeaders.php` ponía `X-Frame-Options: DENY`
  en TODAS las respuestas. La preview embebe `/plantillas/{id}/render` en un iframe del
  mismo origen; `DENY` prohíbe el framing incluso same-origin → el navegador muestra
  "rechazó la conexión". Cambiado a `SAMEORIGIN` (sigue bloqueando clickjacking cross-origin).
  Verificado en vivo: cabecera pasa a `SAMEORIGIN` y el iframe carga.
- **Capa 2 (render):** la galería usaba `sandbox="allow-scripts"` → origen opaco. El motor
  kinetic/classic no puede pintar la escena ahí (accesos que lanzan en origen opaco, tragados
  por el `try/catch` del motor) → preview NEGRA. El Editor ya usaba `allow-scripts
  allow-same-origin` (correcto); alineada la galería. Verificado con screenshots:
  `allow-scripts` = negro, `allow-scripts allow-same-origin` = escena completa.
- Contenido = plantilla del catálogo (first-party), así que compartir origen es seguro.

### B3-3 — Vídeos de fondo hotlinkeados desde Pexels
- `grep <video src` → 84 ficheros en `database/templates/*.html` con
  `https://videos.pexels.com/...`. Pexels respondió 206 hoy, pero en producción esto es
  un punto único de fallo de terceros para las webs de los clientes. Decisión pendiente:
  auto-hospedar los vídeos (storage propio/CDN) o sustituir por imagen + poster fiable.
