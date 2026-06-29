<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer contraseña</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 40px 20px;
        }
        .container {
            max-width: 520px;
            margin: 0 auto;
            background: #ffffff;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #1a1a2e;
            font-size: 24px;
            margin: 0;
            font-weight: 700;
        }
        .header p {
            color: #6c757d;
            margin: 8px 0 0;
            font-size: 15px;
        }
        .divider {
            height: 2px;
            background: #e9ecef;
            margin: 25px 0;
            border: none;
        }
        .btn {
            display: inline-block;
            padding: 14px 32px;
            background: #4F46E5;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #4338CA;
        }
        .footer {
            margin-top: 30px;
            font-size: 13px;
            color: #a0aec0;
            text-align: center;
            border-top: 1px solid #edf2f7;
            padding-top: 25px;
        }
        .info {
            background: #f8fafc;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            color: #475569;
            margin: 20px 0;
        }
        .info strong {
            color: #1e293b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Restablecer contraseña</h1>
            <p>Recibimos una solicitud para cambiar tu contraseña</p>
        </div>

        <hr class="divider">

        <p style="color: #334155; font-size: 16px; line-height: 1.6;">
            Hola, <strong>{{ $nombre ?? 'usuario' }}</strong>
        </p>

        <p style="color: #475569; font-size: 15px; line-height: 1.6;">
            Haz clic en el botón para crear una nueva contraseña. Este enlace expirará en 
            <strong>{{ $expiraEn ?? 15 }} minutos</strong>.
        </p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $resetUrl }}" class="btn">
                Restablecer contraseña
            </a>
        </div>

        <div class="info">
            ⚠️ Si no solicitaste este cambio, <strong>ignora este correo</strong>.
            Tu contraseña seguirá siendo la misma.
        </div>

        <div class="footer">
            <p>Este es un mensaje automático, no respondas a este correo.</p>
            <p style="margin-top: 4px; font-size: 12px; color: #cbd5e1;">
                © {{ date('Y') }} TuAplicación. Todos los derechos reservados.
            </p>
        </div>
    </div>
</body>
</html>