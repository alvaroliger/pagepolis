# 🚧 Pagepolis — Problemas reales de negocio/producción

Auditoría de los flujos que el usuario señaló: **dominio propio de pago** y **cobro
del cliente final**. Estado a 2026-06-25.

Leyenda: ✅ arreglado · 🟠 falta (decisión/credenciales/servidor) · 🔴 fallo grave que bloquea el flujo · 🟡 mejora

---

## 1. DOMINIO PROPIO ("paga → publica en su dominio → lo edita antes y después")

### ✅ Arreglado en esta sesión (código)
- **Re-despliegue al editar.** Antes el sitio se desplegaba SOLO al provisionar; cualquier
  edición posterior NO llegaba al dominio. Ahora `Project::deployToLiveDomain()` se llama al
  guardar, al cambiar con IA y al cambiar en local → el dominio refleja siempre lo último.
- **Provisión al reservar.** Antes `reserve()` dejaba el dominio en `pending` y solo lo
  provisionaba el webhook de un checkout; si el usuario ya estaba suscrito, se quedaba colgado
  para siempre. Ahora se dispara `PurchaseDomain` al reservar (el usuario ya está suscrito).
- **Datos WHOIS a configuración** (`OWNER_*` en `.env`). Antes estaban hardcodeados e inválidos
  (`Calle Principal 1`, `+34.600000000`) → el registrador podía rechazar/suspender el dominio.

### 🔴 Bloqueante (los dominios PROPIOS aún no resuelven) — necesita decisión
- **DNS de dominios propios mal dirigido.** `CloudflareService` usa UNA zona (la de
  pagepolis.com). Para **subdominios `x.pagepolis.com` funciona**; para un **dominio propio
  `minegocio.com` NO** (su registro A no puede ir en la zona de pagepolis). Hay que elegir:
  - **Opción A (recomendada):** el dominio se registra en Dinahosting → gestionar su DNS con la
    **API de Dinahosting** (poner el registro A → IP del servidor). Es lo natural porque el
    dominio ya vive ahí.
  - **Opción B:** dar de alta cada dominio como **zona nueva en Cloudflare** y cambiar los
    nameservers en el registrador (más pasos, automatizable).
  > Hasta decidir esto, **solo los subdominios `*.pagepolis.com` funcionan de punta a punta.**

### 🟠 Falta (servidor / credenciales / ops)
- **SSL en dominios propios.** El vhost se crea solo en HTTP (`listen 80`). Falta emitir
  certificado (certbot) cuando el DNS resuelva. Recomendado: comando programado que recorra
  dominios activos sin SSL y ejecute `certbot --nginx -d <dominio>` (reintenta hasta propagar).
  Sin esto: sin candado, avisos del navegador, peor SEO.
- **Credenciales** `CLOUDFLARE_*` y `DINAHOSTING_*` (y `OWNER_*`) en el `.env` de producción.
- El **worker de colas** debe estar activo (PurchaseDomain y DeploySite son jobs).

### 🟡 Mejora UX
- En el editor no se ve el estado del dominio ni un botón "publicar/actualizar" explícito (hoy
  el re-despliegue es automático al guardar, que está bien, pero conviene mostrar la URL y el
  estado: pending/activo/SSL).

---

## 2. COBRO DEL CLIENTE FINAL ("si vende un producto, el dinero va a ÉL; que le lleguen los datos del comprador")

### ℹ️ Cómo funciona HOY (sí funciona, modo informal)
- La tienda generada es **carrito en el navegador → pedido por WhatsApp/email** (`engine.js`).
- **Los datos del comprador SÍ llegan al cliente**: el pedido (productos, cantidades, total y,
  por email, nombre/dirección/teléfono) se envía al **WhatsApp/email del dueño**.
- **El dinero va directo al cliente**: Pagepolis NO está en el medio; el dueño cobra como quiera
  (Bizum, efectivo, transferencia) al cerrar el pedido por WhatsApp.
- Es válido y suficiente para el público objetivo (negocio local/pequeño).

### 🟠 Falta (cobro REAL con tarjeta hacia el cliente) — feature grande
- Para **pago online con tarjeta cuyo dinero llega a la cuenta del cliente** hace falta
  **Stripe Connect**: el cliente conecta/crea su cuenta Stripe (Express), Pagepolis crea los
  Checkout en su nombre, el dinero **liquida en la cuenta del cliente** (Pagepolis puede cobrar
  una comisión por plataforma). Implica: onboarding de Connect, checkout por producto, y
  webhooks que enruten el pedido y el comprador al cliente correcto. **No está construido.**
  Es la vía correcta si quieres "tienda con pago real", pero es un desarrollo aparte.

---

## 3. OTROS HUECOS DE PRODUCTO (relacionados)
- 🟠 **Formulario de contacto sin captura de leads.** Los formularios generados hacen
  `preventDefault` y muestran "gracias" pero **no envían nada**. Para "webs que captan clientes"
  esto es dinero perdido → endpoint que recoja y reenvíe el lead al dueño.
- 🟡 **Imágenes vía picsum.photos** en las webs publicadas: dependencia externa; el usuario debe
  sustituirlas por las suyas.

---

## Prioridad sugerida
1. **Decidir la estrategia DNS de dominios propios** (Opción A: API de Dinahosting) y construirla → sin esto los dominios propios no resuelven. *(o lanzar solo con subdominios `*.pagepolis.com`, que ya funcionan)*
2. **SSL automático** (certbot programado).
3. **Captura de leads** del formulario de contacto.
4. **Stripe Connect** (pago real al cliente) — cuando quieras tienda con tarjeta.
