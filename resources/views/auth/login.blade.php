<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0d6efd">

    <!-- iOS support -->
    <link rel="apple-touch-icon" href="/icon-192.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <title>Página de Inicio de Sesión</title>

    <!-- Import Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;500&display=swap" rel="stylesheet">

    <!-- Import FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- Estilo personalizado -->
    @vite('resources/css/login.css')
</head>
<body class="login-body">

    <div class="container">
        <div class="image-section">
            <img src="{{ asset('images/logo1.png') }}" alt="Logo de 4N Logística" class="logo">
        </div>
        <div class="text-section">
            <!-- Logo para móviles -->
            <img src="{{ asset('images/logo.png') }}" alt="Logo de 4N Logística" class="logo-top">

            <header>
                <h1>Inicio de Sesión</h1>
            </header>
            <main>
                <form class="login-form" method="POST" action="{{ route('login') }}">
                    @csrf

                    <!-- Campo de correo electrónico -->
                    <div class="input-group">
                        <input type="email"
                            id="email"
                            name="email"
                            placeholder="Correo"
                            value="{{ old('email') }}"
                            class="@error('email') is-invalid @enderror"
                            required>

                        @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <!-- Campo de contraseña -->
                    <div class="input-group">
                        <input type="password"
                            id="password"
                            name="password"
                            placeholder="Contraseña"
                            class="@error('password') is-invalid @enderror"
                            required>

                        @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <!-- Botón de Login -->
                    <button type="submit" class="button">Login</button>

                    <!-- Alerta general para credenciales inválidas -->
                    @if ($errors->has('email') || $errors->has('password'))
                        <div class="alert alert-danger text-center mt-3" role="alert">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            Las credenciales ingresadas son incorrectas. Verifica tu correo y contraseña.
                        </div>
                    @endif

                </form>
            </main>
        </div>
    </div>

    <script>
        window.onload = function() {
            document.body.classList.add('loaded');
        }
    </script>
</body>
</html>
