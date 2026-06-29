<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;background:#f3f4f6;font-family:system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;color:#111827">
  <div style="max-width:560px;margin:0 auto;padding:24px">
    <div style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="background:linear-gradient(135deg,#7c3aed,#d946ef);padding:22px 28px;color:#fff">
        <div style="font-size:13px;opacity:.9;font-weight:600">Pagepolis · {{ $project->name }}</div>
        <div style="font-size:20px;font-weight:800;margin-top:4px">📩 Tienes un nuevo mensaje</div>
      </div>
      <div style="padding:26px 28px">
        <p style="margin:0 0 18px;color:#4b5563">Alguien ha contactado desde tu web. Estos son sus datos:</p>

        @if($lead->name)
          <p style="margin:0 0 10px"><strong>Nombre:</strong> {{ $lead->name }}</p>
        @endif
        @if($lead->email)
          <p style="margin:0 0 10px"><strong>Email:</strong> <a href="mailto:{{ $lead->email }}" style="color:#7c3aed">{{ $lead->email }}</a></p>
        @endif
        @if($lead->phone)
          <p style="margin:0 0 10px"><strong>Teléfono:</strong> {{ $lead->phone }}</p>
        @endif
        @if($lead->message)
          <div style="margin:14px 0;padding:14px 16px;background:#f9fafb;border-left:3px solid #7c3aed;border-radius:8px;white-space:pre-wrap;color:#374151">{{ $lead->message }}</div>
        @endif

        <p style="margin:22px 0 0;font-size:13px;color:#6b7280">
          Responde directamente a este correo para contestar al cliente@if($lead->email) ({{ $lead->email }})@endif.
        </p>
        <p style="margin:18px 0 0">
          <a href="{{ config('app.url') }}/mensajes" style="display:inline-block;background:#7c3aed;color:#fff;text-decoration:none;font-weight:700;padding:11px 20px;border-radius:10px">Ver todos tus mensajes</a>
        </p>
      </div>
    </div>
    <p style="text-align:center;color:#9ca3af;font-size:12px;margin:16px 0 0">Recibes esto porque tienes una web publicada en Pagepolis.</p>
  </div>
</body>
</html>
