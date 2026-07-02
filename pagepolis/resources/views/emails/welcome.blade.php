<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bienvenido a Pagepolis</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: #0f0f0f; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; color: #e5e5e5; }
  .wrapper { max-width: 560px; margin: 0 auto; padding: 32px 16px; }
  .logo { font-size: 24px; font-weight: 900; color: #a78bfa; letter-spacing: -0.5px; }
  .header { padding: 32px 0 24px; border-bottom: 1px solid #1f1f1f; }
  .greeting { font-size: 26px; font-weight: 800; color: #fff; margin-top: 28px; line-height: 1.25; }
  .body-text { font-size: 15px; color: #9ca3af; line-height: 1.7; margin-top: 16px; }
  .steps { margin: 28px 0; }
  .step { display: flex; gap: 14px; align-items: flex-start; margin-bottom: 16px; }
  .step-num { width: 28px; height: 28px; border-radius: 50%; background: #1e1b4b; border: 1px solid #3730a3; color: #a78bfa; font-size: 12px; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
  .step-content { padding-top: 4px; }
  .step-title { font-size: 14px; font-weight: 700; color: #fff; }
  .step-desc { font-size: 13px; color: #6b7280; margin-top: 2px; }
  .cta-wrap { margin: 32px 0; text-align: center; }
  .cta-btn { display: inline-block; background: #7c3aed; color: #fff; text-decoration: none; font-weight: 700; font-size: 16px; padding: 16px 36px; border-radius: 12px; letter-spacing: -0.2px; }
  .cta-note { font-size: 12px; color: #4b5563; margin-top: 10px; }
  .footer { border-top: 1px solid #1f1f1f; padding-top: 24px; margin-top: 32px; font-size: 12px; color: #4b5563; text-align: center; line-height: 1.8; }
  .footer a { color: #6b7280; text-decoration: none; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <div class="logo">pagepolis</div>
    <div style="font-size:11px;color:#6b7280;font-style:italic">the web that you get</div>
  </div>

  <p class="greeting">Hola {{ $user->name }},<br>tu cuenta est&aacute; lista. ✨</p>
  <p class="body-text">
    Describe tu negocio en una frase y la IA genera tu web completa en menos de 10 segundos.
    Sin c&oacute;digo, sin dise&ntilde;ador, sin complicaciones.
  </p>

  <div class="steps">
    <div class="step">
      <div class="step-num">1</div>
      <div class="step-content">
        <div class="step-title">Elige una plantilla</div>
        <div class="step-desc">Restaurante, portfolio, tienda, cl&iacute;nica, SaaS&hellip; m&aacute;s de 20 dise&ntilde;os.</div>
      </div>
    </div>
    <div class="step">
      <div class="step-num">2</div>
      <div class="step-content">
        <div class="step-title">Describe tu negocio</div>
        <div class="step-desc">La IA genera HTML, CSS y JavaScript profesional al instante.</div>
      </div>
    </div>
    <div class="step">
      <div class="step-num">3</div>
      <div class="step-content">
        <div class="step-title">Publica y listo</div>
        <div class="step-desc">Tu web queda online en segundos. 7 d&iacute;as gratis, sin tarjeta.</div>
      </div>
    </div>
  </div>

  <div class="cta-wrap">
    <a href="{{ config('app.url') }}/plantillas" class="cta-btn">Crear mi primera web &rarr;</a>
    <div class="cta-note">7 d&iacute;as gratis &middot; Sin tarjeta &middot; Cancela cuando quieras</div>
  </div>

  <div class="footer">
    <p>Pagepolis &middot; <em>The web that you get</em></p>
    <p style="margin-top:8px">
      <a href="{{ config('app.url') }}/dashboard">Mis proyectos</a> &middot;
      <a href="{{ config('app.url') }}/perfil">Ajustes</a>
    </p>
  </div>
</div>
</body>
</html>
