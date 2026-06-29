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
  .greeting { font-size: 22px; font-weight: 800; color: #fff; margin-top: 24px; }
  .subtitle { color: #9ca3af; font-size: 15px; margin-top: 10px; line-height: 1.6; }
  .steps { margin: 24px 0; }
  .step { display: flex; align-items: flex-start; gap: 14px; margin-bottom: 16px; }
  .step-num { flex-shrink: 0; width: 28px; height: 28px; border-radius: 50%; background: #7c3aed; color: #fff; font-size: 13px; font-weight: 800; display: flex; align-items: center; justify-content: center; }
  .step-text { font-size: 15px; color: #d1d5db; line-height: 1.5; padding-top: 4px; }
  .step-text strong { color: #fff; }
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

  <p class="greeting">Hola {{ $user->name }}, bienvenido 👋</p>
  <p class="subtitle">Tu cuenta está lista. En menos de un minuto puedes tener una web profesional publicada y recibiendo clientes.</p>

  <div class="steps">
    <div class="step">
      <div class="step-num">1</div>
      <div class="step-text"><strong>Descríbenos tu negocio</strong> — nombre, sector y lo que ofreces.</div>
    </div>
    <div class="step">
      <div class="step-num">2</div>
      <div class="step-text"><strong>La IA genera tu web</strong> — diseño profesional, textos y SEO local listos.</div>
    </div>
    <div class="step">
      <div class="step-num">3</div>
      <div class="step-text"><strong>Publica y comparte</strong> — tu web queda online en segundos con URL propia.</div>
    </div>
  </div>

  <div class="cta-wrap">
    <a href="{{ config('app.url') }}/crear" class="cta-btn">Crear mi primera web &rarr;</a>
  </div>

  <p style="font-size:13px;color:#6b7280;text-align:center;">
    ¿Alguna duda? Responde a este correo y te ayudamos.
  </p>

  <div class="footer">
    <p>Pagepolis &mdash; webs profesionales con IA</p>
    <p style="margin-top:8px">
      <a href="{{ config('app.url') }}/dashboard">Mi panel</a> &middot;
      <a href="{{ config('app.url') }}/perfil">Ajustes</a>
    </p>
  </div>
</div>
</body>
</html>
