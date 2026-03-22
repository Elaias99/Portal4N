<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracking público 4N</title>

    @viteReactRefresh
    @vite('resources/js/react/tracking/main.jsx')
</head>
<body>
    <div
        id="react-root"
        data-initial-tracking="{{ $tracking }}"
    ></div>
</body>
</html>