<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Página de Inicio de Sesión</title>

    <!-- Import Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;500&display=swap" rel="stylesheet">

    <!-- Import FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- Estilo personalizado -->
    <?php echo app('Illuminate\Foundation\Vite')('resources/css/login.css'); ?>

</head>
<body>
    <div class="container">
        <div class="image-section">
            <img src="<?php echo e(asset('images/logo1.png')); ?>" alt="Logo de 4N Logística" class="logo">
        </div>
        <div class="text-section">
            <header>
                <h1>Inicio de Sesión</h1>
            </header>
            <main>
                <form class="login-form" method="POST" action="<?php echo e(route('login')); ?>">
                    <?php echo csrf_field(); ?> <!-- Laravel CSRF Token -->
                    <script>console.log("Formulario cargado correctamente");</script>

                    <!-- Campo de correo electrónico -->
                    <div class="input-group">
                        
                        <input type="email" id="email" name="email" placeholder="Correo" value="<?php echo e(old('email')); ?>" required>
                        <script>console.log("Campo de correo electrónico renderizado");</script>
                        <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="invalid-feedback" role="alert">
                                <strong><?php echo e($message); ?></strong>
                            </span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- Campo de contraseña -->
                    <div class="input-group">
                        
                        <input type="password" id="password" name="password" placeholder="Contraseña" required>
                        <script>console.log("Campo de contraseña renderizado");</script>
                        <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="invalid-feedback" role="alert">
                                <strong><?php echo e($message); ?></strong>
                            </span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- Botón de Login -->
                    <button type="submit" class="button" onclick="console.log('Botón de login presionado')">Login</button>
                </form>
            </main>
        </div>
    </div>

    <script>
        // Agregar clase 'loaded' para iniciar la animación
        window.onload = function() {
            console.log("Página completamente cargada");
            document.body.classList.add('loaded');
        }
    </script>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/auth/login.blade.php ENDPATH**/ ?>