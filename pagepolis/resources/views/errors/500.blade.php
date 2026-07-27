<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title>Algo ha fallado · Pagepolis</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  html, body { height: 100%; }
  body {
    background: #030712;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    color: #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    padding: 24px;
  }
  .card {
    max-width: 460px;
    width: 100%;
    text-align: center;
  }
  .logo-row {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    margin-bottom: 40px;
  }
  .logo-word {
    font-size: 22px;
    font-weight: 900;
    letter-spacing: -0.02em;
    background: linear-gradient(90deg, #a78bfa, #e879f9, #22d3ee);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
  }
  .code {
    font-size: 96px;
    font-weight: 900;
    line-height: 1;
    letter-spacing: -0.03em;
    background: linear-gradient(135deg, #8b5cf6, #d946ef);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
  }
  h1 {
    margin-top: 16px;
    font-size: 22px;
    font-weight: 800;
    color: #fff;
  }
  p {
    margin-top: 10px;
    font-size: 15px;
    color: #9ca3af;
    line-height: 1.6;
  }
  .actions {
    margin-top: 32px;
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
  }
  .btn {
    display: inline-block;
    text-decoration: none;
    font-weight: 700;
    font-size: 14px;
    padding: 12px 22px;
    border-radius: 10px;
  }
  .btn-primary {
    background: #7c3aed;
    color: #fff;
  }
  .btn-secondary {
    background: transparent;
    color: #d1d5db;
    border: 1px solid #374151;
  }
</style>
</head>
<body>
  <div class="card">
    <a href="/" class="logo-row">
      <svg width="30" height="30" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
        <defs>
          <linearGradient id="pp-grad-500" x1="0" y1="0" x2="36" y2="36" gradientUnits="userSpaceOnUse">
            <stop offset="0%" stop-color="#8b5cf6" />
            <stop offset="50%" stop-color="#d946ef" />
            <stop offset="100%" stop-color="#06b6d4" />
          </linearGradient>
        </defs>
        <rect x="2" y="2" width="32" height="32" rx="9" fill="url(#pp-grad-500)" />
        <rect x="9" y="8" width="14" height="18" rx="2" fill="white" fill-opacity="0.15" />
        <rect x="9" y="8" width="14" height="18" rx="2" stroke="white" stroke-opacity="0.6" stroke-width="1.5" />
        <line x1="12" y1="13" x2="20" y2="13" stroke="white" stroke-opacity="0.8" stroke-width="1.5" stroke-linecap="round" />
        <line x1="12" y1="17" x2="20" y2="17" stroke="white" stroke-opacity="0.5" stroke-width="1.2" stroke-linecap="round" />
        <line x1="12" y1="21" x2="17" y2="21" stroke="white" stroke-opacity="0.5" stroke-width="1.2" stroke-linecap="round" />
      </svg>
      <span class="logo-word">pagepolis</span>
    </a>
    <div class="code">500</div>
    <h1>Algo ha fallado por nuestra parte</h1>
    <p>Ya lo sabemos y estamos en ello. Prueba a recargar la página en unos minutos.</p>
    <div class="actions">
      <a href="/" class="btn btn-primary">Ir al inicio</a>
      <a href="/dashboard" class="btn btn-secondary">Mi panel</a>
    </div>
  </div>
</body>
</html>
