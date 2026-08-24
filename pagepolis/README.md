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

**En Windows, `composer dev` no sirve:** el visor de logs (`php artisan pail`) necesita la
extensión `pcntl`, que no existe en PHP para Windows, y como el script usa
`concurrently --kill-others`, ese fallo mata también al servidor/cola/vite aunque
arrancaran bien. Además `php artisan serve` es de un solo hilo en Windows —
con la galería de plantillas (218 miniaturas) cualquier otra pestaña se queda con
"conexión rechazada". Usa `scripts\dev-local.ps1` (en la raíz del repo, un nivel
por encima de esta carpeta): levanta servidor (FrankenPHP, concurrente de verdad),
cola y vite en 3 ventanas separadas, sin pail.

Tests: `php artisan test`. Build de producción: `npm run build`.

## Miniaturas de la galería de plantillas

La galería (`/plantillas`) NO renderiza las plantillas: enseña una imagen WebP
pre-generada por cada una. Antes montaba un iframe por tarjeta y con 218
plantillas activas eso eran 299 documentos, 38 MB de red y 12,5 s de CPU en el
hilo principal del navegador (los iframes del mismo origen comparten hilo, así
que no hay forma de repartir ese coste en el cliente).

```bash
php artisan serve                                   # la app tiene que responder
php artisan templates:thumbnails --tabs=6           # solo las nuevas o cambiadas
php artisan templates:thumbnails --tabs=6 --force   # todas
```

Abre las plantillas en Chrome headless (varias pestañas en paralelo, una por
núcleo) contra `/plantillas/{id}/render` y guarda el recorte del hero en
`public/thumbs/`. Necesita Chrome instalado; si no está en una ruta habitual,
defínelo con `CHROME_PATH`.

El nombre del archivo lleva una huella del html+css+js **y de la versión del
motor**, así que basta con volver a lanzarlo (sin `--force`) tras cambiar
plantillas o `database/templates/*.css`: regenera solo lo que haga falta. Las
imágenes se versionan en git, de modo que el servidor de producción no necesita
Chrome.

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
