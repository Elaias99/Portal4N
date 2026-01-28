<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Etiquetas Grandes</title>
<style>
    body { font-family: Arial, sans-serif; background:#f5f7fa; margin:0; }
    .wrap { max-width:720px; margin:120px auto; padding:0 24px; }
    .box { background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:40px; }
    h1 { margin:0 0 8px 0; font-size:28px; }
    p { margin:0 0 24px 0; color:#64748b; }
    input[type=file]{ width:100%; font-size:15px; margin-bottom:20px; }
    button{ width:100%; height:52px; border:none; border-radius:10px; background:#2563eb; color:#fff; font-size:16px; cursor:pointer; }
    button:hover{ background:#1e40af; }
    .error{ margin-bottom:16px; padding:12px 14px; background:#fee2e2; color:#991b1b; border-radius:10px; }
</style>
</head>
<body>
<div class="wrap">
    <div class="box">
        <h1>Etiquetas 10×15</h1>
        <p>Sube el Excel y descarga el archivo ZPL listo para imprimir.</p>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" enctype="multipart/form-data">
            @csrf
            <input type="file" name="file" required>
            <button type="submit">Generar archivo ZPL</button>
        </form>
    </div>
</div>
</body>
</html>
