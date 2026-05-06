<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aceptar invitación — SProjects</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f3f4f6; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card { background: white; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,.08); width: 100%; max-width: 440px; overflow: hidden; }
        .card-header { background: #7c3aed; padding: 28px 32px; }
        .card-header .label { color: #ede9fe; font-size: 12px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; }
        .card-header h1 { color: white; font-size: 22px; font-weight: 700; margin-top: 6px; }
        .card-body { padding: 28px 32px; }
        .invite-info { background: #f5f3ff; border: 1px solid #e9d5ff; border-radius: 10px; padding: 14px 16px; margin-bottom: 24px; font-size: 14px; color: #374151; line-height: 1.6; }
        .invite-info strong { color: #7c3aed; }
        label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 5px; }
        input { width: 100%; padding: 10px 13px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; color: #111827; outline: none; transition: border-color .15s, box-shadow .15s; }
        input:focus { border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,.12); }
        input[readonly] { background: #f9fafb; color: #6b7280; cursor: not-allowed; }
        .field { margin-bottom: 18px; }
        button[type=submit] { width: 100%; background: #7c3aed; color: white; border: none; padding: 12px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background .15s; margin-top: 4px; }
        button[type=submit]:hover { background: #6d28d9; }
        .error { color: #dc2626; font-size: 12px; margin-top: 4px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <p class="label">SProjects</p>
            <h1>Crear cuenta</h1>
        </div>
        <div class="card-body">
            <div class="invite-info">
                Has sido invitado al proyecto <strong>{{ $invitation->project->name }}</strong>
                como <strong>{{ ucfirst($invitation->role) }}</strong>
                por <strong>{{ $invitation->invitedBy->name }}</strong>.
            </div>

            @if ($errors->any())
                <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 14px;margin-bottom:18px;font-size:13px;color:#dc2626;">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('invitation.register', $invitation->token) }}">
                @csrf
                <div class="field">
                    <label>Correo electrónico</label>
                    <input type="email" value="{{ $invitation->email }}" readonly/>
                </div>
                <div class="field">
                    <label>Nombre completo</label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="Tu nombre" required autofocus/>
                </div>
                <div class="field">
                    <label>Contraseña</label>
                    <input type="password" name="password" placeholder="Mínimo 8 caracteres" required/>
                </div>
                <div class="field">
                    <label>Confirmar contraseña</label>
                    <input type="password" name="password_confirmation" placeholder="Repite la contraseña" required/>
                </div>
                <button type="submit">Registrarme y unirme →</button>
            </form>
        </div>
    </div>
</body>
</html>
