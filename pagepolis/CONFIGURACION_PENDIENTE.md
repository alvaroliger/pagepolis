# ✅ Pagepolis — Lo que queda por configurar / decidir (no es código)

Índice único de lo que **NO puedo arreglar desde el código** porque depende de ti:
credenciales, servidor, DNS, decisiones de negocio o features grandes. El detalle paso
a paso está en `LANZAMIENTO.md` (runbook) y `PROBLEMAS_PRODUCCION.md` (flujos de dominio/pago).

> Lo que SÍ es código ya está hecho y con tests (90 en verde).

---

## 🔑 1. Credenciales / `.env` de producción
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://pagepolis.com`, `SESSION_SECURE_COOKIE=true`
- [ ] `ANTHROPIC_API_KEY` + topes `AI_MONTHLY_BUDGET_USD` / `AI_DAILY_BUDGET_USD`
- [ ] **Stripe (live):** `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`, `STRIPE_PRICE_MONTHLY`, `STRIPE_PRICE_YEARLY` + webhook a `https://pagepolis.com/stripe/webhook`
- [ ] **Email (Resend):** `MAIL_MAILER=resend`, `RESEND_API_KEY` (obligatorio: la verificación de email lo necesita)
- [ ] **`SUPPORT_EMAIL`** (el buzón/redirección que crees en Dinahosting)
- [ ] **Cloudflare:** `CLOUDFLARE_API_TOKEN`, `CLOUDFLARE_ZONE_ID`
- [ ] **Dinahosting:** `DINAHOSTING_USER`, `DINAHOSTING_PASS` + datos WHOIS reales `OWNER_ADDRESS/CITY/ZIP/COUNTRY/PHONE`
- [ ] `SERVER_PUBLIC_IP=207.180.193.214`

## 🖥️ 2. Servidor (SSH — solo tú)
- [ ] **Worker de colas** activo (systemd): `php artisan queue:work` → **IMPRESCINDIBLE** (generación de IA, dominios y emails son jobs; sin él se quedan colgados)
- [ ] **Cron**: `* * * * * php artisan schedule:run` (avisos, suspensiones, limpieza de subidas)
- [ ] **SSL de pagepolis.com**: `certbot --nginx -d pagepolis.com -d www.pagepolis.com`
- [ ] Permitir a `www-data` recargar nginx sin contraseña (NginxService usa `sudo nginx -t && sudo systemctl reload nginx`)
- [ ] Copias de seguridad del SQLite

## 🌐 3. DNS
- [ ] Apuntar `pagepolis.com` (registro A) → `207.180.193.214`
- [ ] **DECISIÓN PENDIENTE — dominios propios de clientes:** hoy solo funcionan los **subdominios `*.pagepolis.com`**. Para dominios propios (`minegocio.com`) hay que elegir estrategia DNS (recomiendo **API de Dinahosting**) y construirla. Detalle en `PROBLEMAS_PRODUCCION.md` §1. → dime qué opción y lo implemento.
- [ ] **SSL automático para dominios de clientes** (certbot programado) — sin esto van por HTTP.

## ⚖️ 4. Legal (necesito tus datos para rellenarlo yo)
- [ ] Nombre/razón social, NIF, dirección y teléfono → relleno `Terms.tsx`, `Privacy.tsx` y los `OWNER_*`. Hoy tienen placeholders.

## 🎨 5. Marca / assets
- [ ] **`public/og-image.png`** (1200×630): exporta `logoyfirma/og-image.svg` a PNG (las redes no muestran SVG al compartir). El SEO ya lo referencia.
- [ ] Crear el **correo de soporte** en Dinahosting (recomendado: redirección `soporte@pagepolis.com → tu Gmail`).

## 🧩 6. Features grandes pendientes (decisión de negocio)
- [x] **Captura de leads del formulario de contacto — HECHO (2026-06-29).** Las webs publicadas en
  `/s/{slug}` inyectan un script que intercepta cualquier formulario → POST a `/s/{slug}/lead` →
  se guarda el lead (tabla `leads`) y se **avisa al dueño por email** (reply-to = el cliente). El dueño
  ve todo en `/mensajes` (badge de no leídos en la nav). 6 tests + suite (96) en verde.
  **Pendiente para producción:** `php artisan migrate` en el VPS (crea `leads`) al desplegar, y que el
  **worker de colas** esté activo (el email del lead se encola). Resend ya está en el checklist (§1).
- [ ] **Pago real con tarjeta hacia el cliente (Stripe Connect):** hoy las tiendas cobran por **WhatsApp/email** (el dinero va directo al dueño, los datos del comprador le llegan). Para tarjeta online que liquide en la cuenta del cliente → Stripe Connect (desarrollo aparte). Detalle en `PROBLEMAS_PRODUCCION.md` §2.

## ⚠️ 7. Trampas de uso a vigilar (no son bugs, pero confunden al usuario)
- En las **tiendas generadas**, el WhatsApp/email de pedidos es un **placeholder** (`34600000000`). Si el dueño publica sin cambiarlo, los pedidos se pierden. Mitigado: la IA lo avisa y hay un atajo "📱 Mi WhatsApp" en el editor. Conviene un aviso antes de publicar una tienda con el número de ejemplo.
- Las imágenes de las webs son de **picsum.photos** (demo); el dueño debe sustituirlas por las suyas.

---

### Estado del código (hecho)
Generación IA (2 fases, async, tienda), router local gratis + capa de ayuda/aclaración,
12 plantillas, editor mejorado, SEO, correo de soporte configurable, kit de marca, y los
arreglos de dominio (re-despliegue al editar, reserva sin bloquear el embudo, WHOIS a config).
**90 tests en verde.**
