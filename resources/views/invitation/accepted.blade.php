<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Bienvenido! — SProjects</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f3f4f6; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card { background: white; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,.08); width: 100%; max-width: 420px; padding: 40px 32px; text-align: center; }
        .icon { width: 56px; height: 56px; background: #d1fae5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 26px; }
        h1 { font-size: 22px; font-weight: 700; color: #111827; margin-bottom: 10px; }
        p { font-size: 14px; color: #6b7280; line-height: 1.6; margin-bottom: 6px; }
        .project { font-weight: 600; color: #7c3aed; }
        a.btn { display: inline-block; margin-top: 24px; background: #7c3aed; color: white; padding: 11px 28px; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none; }
        a.btn:hover { background: #6d28d9; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">✓</div>
        <h1>¡Te has unido al proyecto!</h1>
        <p>Ya eres miembro de <span class="project">{{ $project->name }}</span>.</p>
        @if ($existing)
            <p>Inicia sesión para empezar a trabajar.</p>
        @endif
        <a href="/app" class="btn">Ir a SProjects →</a>
    </div>
</body>
</html>
