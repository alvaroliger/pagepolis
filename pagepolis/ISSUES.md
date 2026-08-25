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
| B1-5 | 🟠 | **Horizon instalado pero requiere Redis, y `.env` usa `QUEUE/CACHE/SESSION=database` sin `ext-redis`.** El panel `/horizon` no funciona así. Decidir: Redis+Horizon en prod, o quitar Horizon y quedarse en colas `database`. Hoy funciona (database) pero es incoherente. | PENDIENTE |
| B1-6 | 🟡 | Stack de producción: hoy no hay receta reproducible (nginx+php-fpm o FrankenPHP en el VPS). Deploy usa nginx (`scripts/deploy.py`) pero sin OPcache/worker garantizados. | PENDIENTE |

## Bloque 2 — Completar la aplicación (botones muertos, flujos)

| # | Sev | Problema | Estado |
|---|-----|----------|--------|
| B2-1 | 🟡 | **Enlaces en plantillas.** Estructura OK: 264/272 con ≥3 secciones, 272/272 `<nav>`, 271/272 `<footer>` → páginas completas. Anclas rotas (botón que scrollea a la nada): eran 2 (Casa Bella, Agencia) → **ARREGLADAS, 0 en las 271**. Quedan **314 `href="#"` placeholders en 67 plantillas** (footer/redes) — no rompen navegación, pero se ven "sin acabar". | ANCLAS ROTAS: RESUELTO · placeholders: PENDIENTE |
| B2-4 | 🟢 | **Preview de la galería SIEMPRE en vivo con movimiento** (IntersectionObserver + tope 9): cada tarjeta visible muestra la plantilla real moviéndose. Verificado con diff de fotogramas. | RESUELTO |
| B2-2 | 🟢 | Botones de la APP (dashboard/editor/crear/publicar): auditados, **sin handlers muertos** (todos con onClick/submit). Flujos con ruta+controlador reales (auth completa, billing, admin, reset pwd). Falta probar cada uno end-to-end. | PARCIAL |
| B2-3 | 🟡 | Enlaces legales de footer de plantillas (`Privacidad`, `Términos`, `Aviso legal`) van a `#`: deberían apuntar a páginas reales o quitarse. | PENDIENTE |

## Bloque 3 — Catálogo de plantillas

| # | Sev | Problema | Estado |
|---|-----|----------|--------|
| B3-1 | 🟡 | Plantillas casi duplicadas (mismo layout, solo cambia un asset). Auditar y consolidar. | PENDIENTE |
| B3-2 | 🔴 | **Preview en negro / "rechazó la conexión" / sin movimiento**: (a) `X-Frame-Options: DENY` bloqueaba el iframe; (b) sandbox `allow-scripts` (origen opaco) → motor no pintaba. Con `allow-scripts allow-same-origin` la preview pinta Y **anima** (verificado con diff de fotogramas: Ken Burns + WebGL se mueven). ⚠️ Requiere recargar la página para ver el fix. | RESUELTO |
| B3-6 | 🟡 | **Clasificación 2D/3D mal**: 6 plantillas con hero 3D por `<canvas>` (Nova, Atlas, Sentinel, Vidrio, Órbita, Hiperspacio) salían como "clásicas". Heurística de la galería ampliada con `<canvas>` → 132→138 3D, sin falsos positivos. | RESUELTO |
| B3-3 | 🔴 | **Miniaturas de la galería salían como manchas de degradado** (84 plantillas con vídeo): el generador capturaba antes de que cargara el vídeo remoto. ARREGLADO el generador (fuerza carga del vídeo; regenerar con `--tabs=1`). Regenerando las 84. | RESUELTO (generador) + regen en curso |
| B3-4 | 🟠 | **84 plantillas hotlinkean vídeos de `videos.pexels.com`** (CDN de terceros). Aunque la miniatura ya se arregla, en PROD las webs de tus clientes dependen de que Pexels cargue rápido y no bloquee el hotlink. Decisión: auto-hospedar los vídeos o dar `poster` + lazy fiable. | PENDIENTE |
| B3-5 | 🟠 | **CURACIÓN del catálogo (encargo de Álvaro):** analizar cada plantilla por atractivo comercial (público, por qué gustaría, por qué un cliente pasaría de ella), consolidar casi-duplicadas (mismo layout kinetic, distinto vídeo/color), y dejar un catálogo que VENDA. Construir/modificar/eliminar con criterio. NO borrar las que merezcan la pena. | PENDIENTE (bloque grande) |

## Bloque 4 — Marketplace (que el cliente venda sus productos)

| # | Sev | Problema | Estado |
|---|-----|----------|--------|
| B4-1 | 🔴 | No existe gestión de catálogo de productos por cliente ni carrito/checkout para el visitante final. | PENDIENTE |
| B4-2 | 🔴 | Pagos a terceros (dinero al cliente, comisión a la plataforma): evaluar Stripe Connect. | PENDIENTE |
| B4-3 | 🟠 | Reembolsos/contracargos del cliente final. | PENDIENTE |

## Bloque 5 — Compra automática de dominios + fondo de reserva

| # | Sev | Problema | Estado |
|---|-----|----------|--------|
| B5-1 | 🔴 | Compra automática de dominio al pagar. YA EXISTE andamiaje real: job `PurchaseDomain` (Dinahosting + Cloudflare A-record + Nginx vhost + `expires_at`+1año) disparado por el webhook de Stripe. Falta: flag sandbox→real, lock atómico anti-doble-compra, consulta de precio en vivo, idempotencia del reintento. | PARCIAL |
| B5-2 | 🔴 | **Fondo de reserva prorrateado + histórico auditable.** Núcleo construido y probado: `DomainReserveFund` + tabla `domain_reserve_movements` (append-only, idempotente) + 7 tests (margen, idempotencia, auditoría, casos límite). Falta: llamar al `accrue()` desde el webhook `invoice.payment_succeeded` y el comando de renovación. | NÚCLEO RESUELTO |
| B5-3 | 🟠 | Casos límite: cancelación (config `domain.on_cancel`), dunning, precio premium (débito con precio real ya soportado), pago reversible (acumular solo en pago confirmado), doble compra (lock), fallo parcial (saga/reintento con idempotencia por `reference`). | PARCIAL |

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

### B5-2 — Fondo de reserva de dominios (NÚCLEO RESUELTO)
- **La pregunta de Álvaro** ("¿cómo aparto una parte menor de lo que me paga el cliente, en
  las mismas fechas, para que la renovación anual nunca falle?") queda resuelta así:
  - En cada cobro CONFIRMADO se aparta una porción prorrateada del coste anual del dominio,
    con margen de seguridad configurable (`domain.reserve_margin`, por defecto 1.25).
  - **Invariante probada:** porción mensual = `ceil(coste_anual × margen / 12)`; sumadas 12
    ⇒ ≥ coste anual + colchón. Y esa porción (p. ej. 1,25 €/mes) es siempre menor que la cuota
    que paga el cliente, así que nunca se come el margen de la plataforma.
  - **Histórico auditable append-only** (`domain_reserve_movements`): cada entrada/salida con
    `balance_after`, idempotente por `reference` (webhook repetido no cuenta doble).
  - La renovación debita el fondo con el **precio real** consultado al registrador (soporta
    subidas de precio); `canCover()` avisa antes de renovar si no llega (dominio premium).
- Ficheros: `app/Services/DomainReserveFund.php`, `app/Models/DomainReserveMovement.php`,
  migración `..._create_domain_reserve_movements_table`, `config/pagepolis.php` (bloque `domain`),
  `tests/Feature/DomainReserveFundTest.php` (7 tests verdes).
- **Falta para cerrarlo:** llamar `accrue()` desde el webhook `invoice.payment_succeeded` de
  Cashier (idempotente por invoice id) y un comando `domains:renew` que recorra los que expiran
  en `renew_before_days`, consulte precio real, debite el fondo y renueve; con alerta si no cubre.

### B3-3 — Vídeos de fondo hotlinkeados desde Pexels
- `grep <video src` → 84 ficheros en `database/templates/*.html` con
  `https://videos.pexels.com/...`. Pexels respondió 206 hoy, pero en producción esto es
  un punto único de fallo de terceros para las webs de los clientes. Decisión pendiente:
  auto-hospedar los vídeos (storage propio/CDN) o sustituir por imagen + poster fiable.
