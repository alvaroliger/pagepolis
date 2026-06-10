# Pagepolis

SaaS para crear y publicar páginas web con IA. El usuario describe su negocio, la IA
(Claude) genera una web completa (HTML/CSS/JS), la edita en vivo y la publica — gratis en
`pagepolis.com/s/{slug}` o con dominio propio en los planes de pago.

## Stack

- **Backend:** Laravel (PHP 8.3+), SQLite, Laravel Cashier (Stripe), Spatie Permission
- **Frontend:** Inertia.js + React + TypeScript + Tailwind + Vite, i18n en 6 idiomas
- **IA:** API de Anthropic (Claude) — `app/Services/AnthropicService.php`
- **Infra:** Nginx (vhosts por dominio de usuario), Cloudflare (DNS), Dinahosting (dominios)

## Modelo de negocio

- **Gratis:** publica en `pagepolis.com/s/{slug}` con un sello "Hecho con Pagepolis"
  (backlink + viralidad). Límites: ver `config/pagepolis.php`.
- **Pago (Stripe):** dominio propio o subdominio, sin sello, más generaciones de IA.

## Protección de costes (importante)

La IA usa el mejor modelo posible pero con un **guardián de presupuesto**
(`app/Services/AiBudgetGuard.php`) que corta las llamadas al alcanzar un tope **diario o
mensual** (`AI_MONTHLY_BUDGET_USD` / `AI_DAILY_BUDGET_USD`). Así ni bots ni uso manual
pueden disparar el gasto de la API key. El gasto del mes se ve en `/admin`. Además:
límites de IA por usuario/día, límites de cantidad del tier gratis, y throttling anti-bots
en registro/login.

## Desarrollo local

```bash
composer install
npm install
cp .env.example .env        # rellena las claves
php artisan key:generate
php artisan migrate
php artisan db:seed          # crea admin/demo + plantillas
composer dev                 # servidor + cola + logs + vite (http://localhost:8000)
```

Tests: `php artisan test`. Build de producción: `npm run build`.

## Despliegue (manual, desde git)

El deploy lo lanzas tú con `python scripts/deploy.py` (push a GitHub + SSH al servidor +
`git pull` + composer + `npm run build` + migrate + seed de plantillas + cache + reload nginx).

> ⚠️ El servidor `207.180.193.214` aloja OTROS proyectos. El deploy solo toca
> `/var/www/pagepolis`. Nunca reconfigures nginx de forma global ni rompas otros sitios.

Variables de entorno: ver `.env.example` (todas documentadas). Tareas pendientes para
producción (credenciales, DNS, SSL, colas, cron, legal): ver `PENDIENTE.txt`.

## Programación de tareas

`routes/console.php` define: `pagepolis:suspend-expired` (diario),
`pagepolis:expiry-reminders` (aviso 7 días antes), `pagepolis:weekly-reports` (lunes).
Requiere `php artisan schedule:run` por cron y un worker `php artisan queue:work`.
