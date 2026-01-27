<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Generador de Etiquetas Zebra</title>

<style>
:root {
    --bg: #f5f7fa;
    --surface: #ffffff;
    --text: #0f172a;
    --muted: #64748b;
    --primary: #2563eb;
    --border: #e5e7eb;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    background: var(--bg);
    color: var(--text);
}

.wrapper {
    max-width: 720px;
    margin: 120px auto;
    padding: 0 24px;
}

header {
    margin-bottom: 48px;
}

header h1 {
    margin: 0 0 8px 0;
    font-size: 32px;
    font-weight: 600;
    letter-spacing: -0.02em;
}

header p {
    margin: 0;
    font-size: 16px;
    color: var(--muted);
}

.surface {
    background: var(--surface);
    border-radius: 16px;
    padding: 40px;
    border: 1px solid var(--border);
}

.field {
    margin-bottom: 28px;
}

label {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
    color: var(--muted);
}

input[type="file"] {
    width: 100%;
    font-size: 15px;
}

button {
    width: 100%;
    height: 52px;
    border-radius: 10px;
    border: none;
    background: var(--primary);
    color: white;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
}

button:hover {
    background: #1e40af;
}

.helper {
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid var(--border);
    font-size: 14px;
    color: var(--muted);
}

.helper strong {
    color: var(--text);
    font-weight: 500;
}

.helper ul {
    margin: 12px 0 0 18px;
    padding: 0;
}

.helper li {
    margin-bottom: 6px;
}

.footer {
    margin-top: 32px;
    text-align: center;
    font-size: 14px;
}

.footer a {
    color: var(--primary);
    text-decoration: none;
}

.footer a:hover {
    text-decoration: underline;
}

.error {
    margin-bottom: 24px;
    padding: 14px 16px;
    background: #fee2e2;
    color: #991b1b;
    border-radius: 10px;
    font-size: 14px;
}
</style>
</head>

<body>

<div class="wrapper">

    <header>
        <h1>Generador de Etiquetas Zebra</h1>
        <p>Sube un archivo Excel y genera el archivo ZPL listo para imprimir.</p>
    </header>

    <div class="surface">

        @if ($errors->any())
            <div class="error">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" enctype="multipart/form-data">
            @csrf

            <div class="field">
                <label>Archivo Excel</label>
                <input type="file" name="file" required>
            </div>

            <button type="submit">
                Generar archivo ZPL
            </button>
        </form>

        <div class="helper">
            <strong>Después de descargar el archivo ZPL</strong>
            <ul>
                <li>Abrir Zebra Setup Utilities</li>
                <li>Usar “Open Communication With Printer”</li>
                <li>Seleccionar el archivo .zpl</li>
                <li>Enviar con “Send to Printer”</li>
            </ul>
        </div>

    </div>

    <div class="footer">
        ¿Necesitas un formato de ejemplo?
        <a href="{{ url('/labels/excel/template') }}">Descargar plantilla Excel</a>
    </div>

</div>

</body>
</html>
