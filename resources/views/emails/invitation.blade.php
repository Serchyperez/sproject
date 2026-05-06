<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitación al proyecto {{ $invitation->project->name }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:40px 0;">
        <tr>
            <td align="center">
                <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 6px rgba(0,0,0,.08);">
                    {{-- Header --}}
                    <tr>
                        <td style="background:#7c3aed;padding:28px 32px;">
                            <p style="margin:0;color:#ede9fe;font-size:13px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;">SProjects</p>
                            <h1 style="margin:6px 0 0;color:#ffffff;font-size:22px;font-weight:700;">Has sido invitado</h1>
                        </td>
                    </tr>
                    {{-- Body --}}
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px;color:#374151;font-size:15px;line-height:1.6;">
                                <strong>{{ $invitation->invitedBy->name }}</strong> te ha invitado a unirte al proyecto
                                <strong>{{ $invitation->project->name }}</strong> con el rol de
                                <strong>{{ ucfirst($invitation->role) }}</strong>.
                            </p>
                            <p style="margin:0 0 28px;color:#6b7280;font-size:14px;line-height:1.6;">
                                Haz clic en el botón para aceptar la invitación. Si no tienes cuenta, podrás crearla en el proceso.
                            </p>
                            <a href="{{ $acceptUrl }}"
                               style="display:inline-block;background:#7c3aed;color:#ffffff;padding:13px 28px;border-radius:8px;font-size:15px;font-weight:600;text-decoration:none;">
                                Aceptar invitación →
                            </a>
                        </td>
                    </tr>
                    {{-- Footer --}}
                    <tr>
                        <td style="padding:16px 32px 28px;border-top:1px solid #f3f4f6;">
                            <p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.6;">
                                Este enlace expira el {{ $invitation->expires_at->format('d/m/Y') }}.
                                Si no esperabas esta invitación, ignora este mensaje.
                            </p>
                            <p style="margin:8px 0 0;color:#c4b5fd;font-size:11px;">
                                <a href="{{ $acceptUrl }}" style="color:#c4b5fd;">{{ $acceptUrl }}</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
