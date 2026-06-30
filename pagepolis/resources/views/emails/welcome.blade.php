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
  .greeting { font-size: 26px; font-weight: 800; color: #fff; margin-top: 28px; line-height: 1.3; }
  .subtitle { color: #9ca3af; font-size: 16px; margin-top: 10px; line-height: 1.6; }
  .steps { margin: 28px 0; }
  .step { display: flex; align-items: flex-start; gap: 14px; margin-bottom: 16px; }
  .step-number { width: 32px; height: 32px; background: #4c1d95; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; color: #a78bfa; flex-shrink: 0; }
  .step-text strong { display: block; color: #fff; font-weight: 700; font-size: 15px; }
  .step-text span { color: #6b7280; font-size: 13px; margin-top: 2px; display: block; }
  .cta-wrap { margin: 32px 0; text-align: center; }
  .cta-btn { display: inline-block; background: #7c3aed; color: #fff; text-decoration: none; font-weight: 700; font-size: 16px; padding: 16px 40px; border-radius: 12px; }
  .features { background: #111827; border: 1px solid #1f2937; border-radius: 12px; padding: 20px 24px; margin: 24px 0; }
  .features-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #60a5fa; margin-bottom: 12px; }
  .feature-item { font-size: 14px; color: #d1d5db; padding: 4px 0; }
  .feature-item::before { content: "✓ "; color: #34d399; font-weight: 700; }
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

  <p class="greeting">Hola {{ $user->name }},<br>bienvenido a Pagepolis. 🎉</p>
  <p class="subtitle" style="margin-top:12px">
    En menos de 2 minutos puedes tener una web profesional publicada en internet.<br>
    Así de fácil:
  </p>

  <div class="steps">
    <div class="step">
      <div class="step-number">1</div>
      <div class="step-text">
        <strong>Elige una plantilla</strong>
        <span>Tenemos plantillas para negocios locales, restaurantes, clínicas y más.</span>
      </div>
    </div>
    <div class="step">
      <div class="step-number">2</div>
      <div class="step-text">
        <strong>La IA la personaliza para ti</strong>
        <span>Solo cuéntanos de tu negocio — nombre, ciudad, servicios — y la IA genera el contenido.</span>
      </div>
    </div>
    <div class="step">
      <div class="step-number">3</div>
      <div class="step-text">
        <strong>Publica y empieza a recibir clientes</strong>
        <span>Tu web queda en línea al instante. Los clientes pueden contactarte directamente desde ella.</span>
      </div>
    </div>
  </div>

  <div class="cta-wrap">
    <a href="{{ config('app.url') }}/plantillas" class="cta-btn">Crear mi primera web &rarr;</a>
  </div>

  <div class="features">
    <div class="features-title">Incluido en tu cuenta</div>
    <div class="feature-item">Web con dominio gratuito (pagepolis.com/s/tu-web)</div>
    <div class="feature-item">Recibe mensajes de clientes directamente en tu correo</div>
    <div class="feature-item">Editor visual con inteligencia artificial</div>
    <div class="feature-item">SSL, diseño responsive y SEO automático</div>
  </div>

  <p style="font-size:14px;color:#9ca3af;margin-top:24px;line-height:1.6">
    Si tienes cualquier duda, responde a este correo — estamos aquí para ayudarte.
  </p>

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
