<!-- resources/views/under_construction.blade.php -->

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página en Construcción</title>
    <style>
        body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f5f5f5;
            color: #333;
            font-family: 'Montserrat', sans-serif;
        }

        img {
            max-width: 80%;
            height: auto;
        }

        h1 {
            font-size: 36px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    {{-- <h1>Página Web en construcción</h1> --}}
    <img src="{{ asset('images/Espera.png') }}" alt="Página en Construcción">
</body>
</html>
