<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>4Nortes</title>

    @viteReactRefresh
    @vite('resources/js/react/landing/main.jsx')
</head>
<body>


    <div
        id="react-root"
        data-initial-tracking="{{ $initialTracking ?? '' }}"
    ></div>



</body>
</html>