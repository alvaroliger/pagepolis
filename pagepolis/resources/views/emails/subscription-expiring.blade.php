<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tu suscripción está por expirar — Pagepolis</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: #0f0f0f; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; color: #e5e5e5; }
  .wrapper { max-width: 560px; margin: 0 auto; padding: 32px 16px; }
  .logo { font-size: 24px; font-weight: 900; color: #a78bfa; letter-spacing: -0.5px; }
  .header { padding: 32px 0 24px; border-bottom: 1px solid #1f1f1f; }
  .greeting { font-size: 22px; font-weight: 800; color: #fff; margin-top: 24px; }
  .subtitle { color: #9ca3af; font-size: 15px; margin-top: 10px; line-height: 1.6; }
  .alert-card { background: #2a1215; border: 1px solid #7f1d1d; border-radius: 16px; padding: 24px; margin: 24px 0; text-align: center; }
  .alert-number { font-size: 56px; font-weight: 900; color: #fca5a5; line-height: 1; }
  .alert-label { color: #f87171; font-size: 14px; margin-top: 6px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; }
  .body-text { font-size: 15px; color: #d1d5db; line-height: 1.7; margin: 16px 0; }
  .cta-wrap { margin: 28px 0; text-align: center; }
  .cta-btn { display: inline-block; background: #7c3aed; color: #fff; text-decoration: none; font-weight: 700; font-size: 15px; padding: 14px 32px; border-radius: 12px; }
  .footer { border-top: 1px solid #1f1f1f; padding-top: 24px; margin-top: 32px; font-size: 12px; color: #4b5563; text-align: center; line-height: 1.8; }
  .footer a { color: #6b7280; text-decoration: none; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <div class="logo">pagepolis</div>
  </div>

  <p class="greeting">Hola {{ $user->name }}, no pierdas tus webs.</p>
  <p class="subtitle">Tu suscripción ha finalizado y tu periodo de gracia está terminando.</p>

  <div class="alert-card">
    <div class="alert-number">{{ $daysLeft }}</div>
    <div class="alert-label">días para reactivar</div>
  </div>

  <p class="body-text">
    Pasado ese plazo, tus proyectos y dominios se eliminarán de forma permanente y no
    podremos recuperarlos. Reactiva tu suscripción ahora para mantener todo intacto y
    que tus webs sigan online.
  </p>

  <div class="cta-wrap">
    <a href="{{ config('app.url') }}/facturacion/portal" class="cta-btn">Reactivar mi suscripción &rarr;</a>
  </div>

  <p class="body-text" style="font-size:13px;color:#9ca3af;">
    ¿Prefieres descargar una copia de tus webs antes? Entra en
    <a href="{{ config('app.url') }}/dashboard" style="color:#a78bfa;text-decoration:none;">tu panel</a>
    y usa el botón de descarga (ZIP) en cada proyecto.
  </p>

  <div class="footer">
    <p>Pagepolis</p>
    <p style="margin-top:8px">
      <a href="{{ config('app.url') }}/perfil">Ajustes</a> &middot;
      <a href="{{ config('app.url') }}/facturacion/portal">Suscripción</a>
    </p>
  </div>
</div>
</body>
</html>
