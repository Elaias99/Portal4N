


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liquidación de Remuneraciones</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #000;
            margin: 20px;
        }
        .container {
            width: 100%;
            margin: 0 auto;
            padding: 20px;
        }
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .header-container {
            width: 100%;
            border: 1px solid black;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-sizing: border-box;
        }
        .header-container .title {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 15px;
        }
        .header-container table {
            width: 100%;
        }
        .header-container table td {
            padding: 5px 0;
            vertical-align: top;
        }
        .content-container {
            border: 1px solid black;
            padding: 15px;
            border-radius: 5px;
            box-sizing: border-box;
            margin-bottom: 20px;
        }
        .content-container div {
            margin-bottom: 10px;
        }
        .signature {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .signature .line {
            width: 40%;
            border-top: 1px solid black;
            text-align: center;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="<?php echo e(asset('images/logo.png')); ?>" alt="Logo de la Empresa" style="max-height: 100px;">
        </div>
        <div class="header-container">
            <div class="title">
                LIQUIDACIÓN DE REMUNERACIONES<br>
                <?php echo e(\Carbon\Carbon::now()->translatedFormat('F - Y')); ?>

            </div>
            
            <table>
                <tr>
                    <td><strong>Empleador:</strong> 4 NORTES LOGÍSTICA SPA</td>
                    <td><strong>RUT:</strong> 77.346.078-7</td>
                </tr>
                <tr>
                    <td><strong>Trabajador:</strong> <?php echo e(strtoupper($trabajador->ApellidoMaterno)); ?> <?php echo e(strtoupper($trabajador->ApellidoPaterno)); ?>, <?php echo e(strtoupper($trabajador->Nombre)); ?> <?php echo e(strtoupper($trabajador->SegundoNombre)); ?></td>
                    <td><strong>RUT:</strong> <?php echo e($trabajador->Rut); ?></td>
                </tr>
                <tr>
                    <td><strong>Cargo:</strong> <?php echo e($trabajador->cargo->Nombre); ?></td>
                    <td></td>
                </tr>
            </table>
        </div>

        <div class="content-container">
            <div>
                <label>Salario Bruto:</label>
                <span><?php echo e('$' . number_format($trabajador->salario_bruto, 0, ',', '.')); ?></span>
            </div>
            <div>
                <label>AFP:</label>
                <span><?php echo e($trabajador->afp->Nombre); ?></span>
            </div>
            <div>
                <label>Asignación Familiar:</label>
                <span><?php echo e('$' . number_format($trabajador->calcularAsignacionFamiliar(), 0, ',', '.')); ?></span>
            </div>
            <?php if($cotizacion): ?>
                <div>
                    <label>Cotización Obligatoria:</label>
                    <span><?php echo e('$' . number_format($cotizacion['cotizacion'], 0, ',', '.')); ?></span>
                </div>
                <div>
                    <label>SIS:</label>
                    <span><?php echo e('$' . number_format($cotizacion['sis'], 0, ',', '.')); ?></span>
                </div>
                <div>
                    <label>Total Descuento:</label>
                    <span><?php echo e('$' . number_format($cotizacion['total'], 0, ',', '.')); ?></span>
                </div>
            <?php else: ?>
                <p style="color: red;"><strong>Cotización:</strong> No disponible</p>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/empleados/exportpdf.blade.php ENDPATH**/ ?>