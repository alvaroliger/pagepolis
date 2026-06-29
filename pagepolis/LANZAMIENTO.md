# 🚀 Pagepolis — Runbook de lanzamiento

Estado: **código terminado, 29/29 tests verdes, build OK.** Lo que queda son pasos
manuales de infraestructura. Cuando decidas lanzar, sigue este orden de arriba a abajo.

> ⚠️ El servidor `207.180.193.214` aloja OTROS proyectos. Todo lo de aquí toca SOLO
> `/var/www/pagepolis` y `/var/www/pagepolis-sites`. No reconfigures nginx global.

---

## 0. Antes de tocar el servidor (en local)
- [ ] `cd pagepolis && php artisan test` → 29 en verde
- [ ] `npm run build` → sin errores
- [ ] Datos legales rellenados (Terms/Privacy/PurchaseDomain) — **lo hace Claude con tus datos**

## 1. DNS  (tú tienes acceso ✅)
- [ ] Registro **A**: `pagepolis.com` → `207.180.193.214`
- [ ] Registro **A**: `www.pagepolis.com` → `207.180.193.214`
- [ ] (Si usas Cloudflare) proxy en **DNS only** (nube gris) hasta tener SSL en el server, luego puedes activarlo.
- [ ] Verifica: `dig +short pagepolis.com` devuelve la IP.

## 2. `.env` de producción  (`/var/www/pagepolis/.env`)
```env
APP_ENV=production
APP_DEBUG=false                 # CRÍTICO: en true expone la API key
APP_URL=https://pagepolis.com
SESSION_SECURE_COOKIE=true      # tras tener HTTPS
LOG_LEVEL=warning

ANTHROPIC_API_KEY=sk-ant-...    # tu clave real
ANTHROPIC_MODEL=claude-opus-4-8
ANTHROPIC_MODEL_FREE=claude-sonnet-4-6
AI_MONTHLY_BUDGET_USD=50        # tope; nunca gasta más sin que lo subas
AI_DAILY_BUDGET_USD=

# Stripe (claves listas ✅) — usa LIVE en producción
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_PRICE_MONTHLY=price_...
STRIPE_PRICE_YEARLY=price_...

# Email real (necesario para verificación de cuenta)
MAIL_MAILER=resend
RESEND_API_KEY=re_...

SERVER_PUBLIC_IP=207.180.193.214
```

## 3. Stripe  (claves listas ✅)
- [ ] Productos creados con precio mensual y anual → pega los `price_...` en el `.env`.
- [ ] Webhook en https://dashboard.stripe.com/webhooks apuntando a:
      **`https://pagepolis.com/stripe/webhook`**  ← (ruta de Cashier, NO `/webhook/stripe`)
- [ ] Eventos mínimos: `customer.subscription.*`, `invoice.*`, `checkout.session.completed`.
- [ ] Copia el *Signing secret* → `STRIPE_WEBHOOK_SECRET`.

## 4. Deploy
```bash
python scripts/deploy.py     # push a GitHub + SSH + pull + composer + build + migrate + seed + cache + reload nginx
```

## 5. SSL  (después de que el DNS resuelva)
```bash
certbot --nginx -d pagepolis.com -d www.pagepolis.com --non-interactive --agree-tos -m hola@pagepolis.com
```

## 6. Worker de colas + cron  (en el servidor, una vez)
> ⚠️ **AHORA ES IMPRESCINDIBLE:** la generación de webs con IA corre en segundo plano
> (job `GenerateWebsiteJob`). Sin un worker activo, las webs se quedan "Generando…" para
> siempre. El worker es obligatorio para que funcione el producto, no solo los emails.
Worker (systemd recomendado) — `/etc/systemd/system/pagepolis-queue.service`:
```ini
[Unit]
Description=Pagepolis queue worker
After=network.target
[Service]
User=www-data
Restart=always
WorkingDirectory=/var/www/pagepolis
ExecStart=/usr/bin/php artisan queue:work --tries=3 --sleep=3
[Install]
WantedBy=multi-user.target
```
```bash
systemctl enable --now pagepolis-queue
```
Cron (programador de Laravel) — `crontab -e`:
```cron
* * * * * cd /var/www/pagepolis && php artisan schedule:run >> /dev/null 2>&1
```

## 7. Permisos nginx para www-data (dominios de usuarios)
`NginxService` ejecuta `sudo nginx -t && sudo systemctl reload nginx`. En `visudo`:
```
www-data ALL=(root) NOPASSWD: /usr/sbin/nginx -t, /bin/systemctl reload nginx
```

## 8. Verificación post-lanzamiento (humo)
- [ ] https://pagepolis.com carga con candado (SSL OK)
- [ ] Registro de cuenta → llega email de verificación (Resend)
- [ ] Generar una web con IA funciona; `/admin` muestra el gasto de IA
- [ ] Publicar gratis → `pagepolis.com/s/{slug}` con el sello "Hecho con Pagepolis"
- [ ] Suscripción de prueba en Stripe → webhook recibido (Stripe dashboard, sin 4xx)
- [ ] `pagepolis.com/sitemap.xml` y `/robots.txt` responden

## 9. Copias de seguridad (no bloquea, pero hazlo pronto)
- [ ] Cron de backup del SQLite `/var/www/pagepolis/database/database.sqlite`

---
### Pendiente NO bloqueante (cuando puedas)
- **Imagen para compartir:** sube `public/og-image.png` (1200×630) con el logo/claim de Pagepolis. El SEO ya la referencia (OG/Twitter/JSON-LD); sin ella, al compartir el enlace no sale imagen.
- **Timeout de generación:** la IA tarda 1-3 min pero corre en segundo plano, así que NO necesita subir timeouts de nginx/php-fpm para generar. (El sondeo `/ai/estado` es instantáneo).
- `ANTHROPIC_MAX_TOKENS` (opcional, def. 22000): techo de tokens por fase de generación.
- Cloudflare + Dinahosting para dominios de usuarios de pago (`CLOUDFLARE_*`, `DINAHOSTING_*`)
- Google Search Console + enviar sitemap
- 2FA (Laravel Fortify), Sentry, vídeo demo de la landing
