<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Informe semanal — Pagepolis</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: #0f0f0f; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; color: #e5e5e5; }
  .wrapper { max-width: 560px; margin: 0 auto; padding: 32px 16px; }
  .logo { font-size: 24px; font-weight: 900; color: #a78bfa; letter-spacing: -0.5px; }
  .header { padding: 32px 0 24px; border-bottom: 1px solid #1f1f1f; }
  .greeting { font-size: 22px; font-weight: 800; color: #fff; margin-top: 24px; }
  .subtitle { color: #6b7280; font-size: 14px; margin-top: 6px; }
  .total-card { background: #1e1b4b; border: 1px solid #3730a3; border-radius: 16px; padding: 24px; margin: 24px 0; text-align: center; }
  .total-number { font-size: 56px; font-weight: 900; color: #fff; line-height: 1; }
  .total-label { color: #a78bfa; font-size: 14px; margin-top: 6px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; }
  .trend { font-size: 13px; margin-top: 8px; }
  .trend-up { color: #34d399; }
  .trend-down { color: #f87171; }
  .trend-flat { color: #6b7280; }
  .section-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #6b7280; margin: 28px 0 12px; }
  .project-card { background: #161616; border: 1px solid #1f1f1f; border-radius: 12px; padding: 16px; margin-bottom: 10px; }
  .project-row { display: flex; justify-content: space-between; align-items: center; }
  .project-name { font-weight: 700; color: #fff; font-size: 15px; }
  .project-url { font-size: 11px; color: #6b7280; font-family: monospace; margin-top: 2px; }
  .project-visits { font-size: 22px; font-weight: 800; color: #a78bfa; text-align: right; }
  .project-visits-label { font-size: 11px; color: #6b7280; text-align: right; }
  .bar-bg { background: #1f1f1f; border-radius: 4px; height: 6px; margin-top: 10px; overflow: hidden; }
  .bar-fill { background: #7c3aed; border-radius: 4px; height: 100%; }
  .tip-box { background: #111827; border: 1px solid #1f2937; border-radius: 12px; padding: 16px 20px; margin: 24px 0; }
  .tip-label { font-size: 11px; font-weight: 700; color: #60a5fa; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 6px; }
  .tip-text { font-size: 14px; color: #d1d5db; line-height: 1.6; }
  .cta-wrap { margin: 28px 0; text-align: center; }
  .cta-btn { display: inline-block; background: #7c3aed; color: #fff; text-decoration: none; font-weight: 700; font-size: 15px; padding: 14px 32px; border-radius: 12px; }
  .cta-secondary { display: inline-block; color: #a78bfa; text-decoration: none; font-size: 13px; margin-left: 16px; }
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

  <p class="greeting">Hola {{ $user->name }}, tu web esta semana est&aacute; trabajando.</p>
  <p class="subtitle">Informe del {{ now()->subDays(7)->format('d M') }} al {{ now()->format('d M Y') }}</p>

  <div class="total-card">
    <div class="total-number">{{ number_format($stats['total_week']) }}</div>
    <div class="total-label">visitas esta semana</div>
    @php
      $prev = $stats['total_prev_week'] ?? 0;
      $diff = $stats['total_week'] - $prev;
      $pct  = $prev > 0 ? round(abs($diff) / $prev * 100) : 0;
    @endphp
    @if($diff > 0)
      <div class="trend trend-up">&#8593; {{ $pct }}% m&aacute;s que la semana anterior</div>
    @elseif($diff < 0)
      <div class="trend trend-down">&#8595; {{ $pct }}% menos que la semana anterior</div>
    @else
      <div class="trend trend-flat">Igual que la semana anterior</div>
    @endif
  </div>

  @if(count($stats["projects"]) > 0)
  <div class="section-title">Tus proyectos publicados</div>
  @php $maxV = max(array_column($stats["projects"], "week_visits") ?: [1]); @endphp
  @foreach($stats["projects"] as $p)
  <div class="project-card">
    <div class="project-row">
      <div>
        <div class="project-name">{{ $p["name"] }}</div>
        <div class="project-url">{{ $p["url"] }}</div>
      </div>
      <div>
        <div class="project-visits">{{ $p["week_visits"] }}</div>
        <div class="project-visits-label">visitas</div>
      </div>
    </div>
    <div class="bar-bg">
      <div class="bar-fill" style="width:{{ $maxV > 0 ? round($p["week_visits"] / $maxV * 100) : 0 }}%"></div>
    </div>
  </div>
  @endforeach
  @endif

  @if(!empty($stats["tip"]))
  <div class="tip-box">
    <div class="tip-label">Consejo de esta semana</div>
    <div class="tip-text">{{ $stats["tip"] }}</div>
  </div>
  @endif

  <div class="cta-wrap">
    <a href="{{ config("app.url") }}/dashboard" class="cta-btn">Ver mis proyectos &rarr;</a>
    <a href="{{ config("app.url") }}/plantillas" class="cta-secondary">Crear nueva web</a>
  </div>

  <div class="footer">
    <p>Pagepolis &middot; <em>The web that you get</em></p>
    <p style="margin-top:8px">
      <a href="{{ config("app.url") }}/perfil">Ajustes</a> &middot;
      <a href="{{ config("app.url") }}/facturacion/portal">Suscripci&oacute;n</a>
    </p>
  </div>
</div>
</body>
</html>