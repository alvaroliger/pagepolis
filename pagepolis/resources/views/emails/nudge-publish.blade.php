<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>¿Has publicado ya tu web? — Pagepolis</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: #0f0f0f; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; color: #e5e5e5; }
  .wrapper { max-width: 560px; margin: 0 auto; padding: 32px 16px; }
  .logo { font-size: 24px; font-weight: 900; color: #a78bfa; letter-spacing: -0.5px; }
  .header { padding: 32px 0 24px; border-bottom: 1px solid #1f1f1f; }
  .greeting { font-size: 24px; font-weight: 800; color: #fff; margin-top: 28px; line-height: 1.35; }
  .body-text { color: #9ca3af; font-size: 15px; margin-top: 14px; line-height: 1.7; }
  .highlight-box { background: #1e1b4b; border: 1px solid #3730a3; border-radius: 16px; padding: 22px 24px; margin: 28px 0; }
  .highlight-title { font-size: 14px; font-weight: 700; color: #a78bfa; margin-bottom: 10px; }
  .highlight-list { list-style: none; }
  .highlight-list li { font-size: 14px; color: #c4b5fd; padding: 5px 0; }
  .highlight-list li::before { content: "→ "; }
  .cta-wrap { margin: 28px 0; text-align: center; }
  .cta-btn { display: inline-block; background: #7c3aed; color: #fff; text-decoration: none; font-weight: 700; font-size: 16px; padding: 16px 40px; border-radius: 12px; }
  .cta-secondary { display: block; color: #a78bfa; text-decoration: none; font-size: 13px; margin-top: 12px; text-align: center; }
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

  <p class="greeting">Hola {{ $user->name }},<br>tu web te está esperando.</p>

  <p class="body-text">
    Hace un par de días te registraste en Pagepolis, pero aún no has publicado tu web.
    No te preocupes — publicar solo lleva 2 minutos.
  </p>

  <div class="highlight-box">
    <div class="highlight-title">¿Por qué publicar ahora?</div>
    <ul class="highlight-list">
      <li>Tus clientes te encontrarán en Google y redes sociales</li>
      <li>Recibirás sus mensajes directamente en tu correo</li>
      <li>Puedes editar y mejorar la web en cualquier momento</li>
    </ul>
  </div>

  <p class="body-text">
    Si ya tienes un proyecto creado, solo falta publicarlo desde el editor.
    Si todavía no has creado ninguno, en 2 minutos lo tienes listo con la IA.
  </p>

  <div class="cta-wrap">
    <a href="{{ config('app.url') }}/dashboard" class="cta-btn">Publicar mi web &rarr;</a>
    <a href="{{ config('app.url') }}/plantillas" class="cta-secondary">Empezar desde cero</a>
  </div>

  <div class="footer">
    <p>Pagepolis &middot; <em>The web that you get</em></p>
    <p style="margin-top:8px">
      <a href="{{ config('app.url') }}/dashboard">Mi cuenta</a> &middot;
      <a href="{{ config('app.url') }}/perfil">Ajustes</a>
    </p>
  </div>
</div>
</body>
</html>
