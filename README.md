<p align="center">
  <img
    src="./public/logos/zotFGi6QzfrFPtZkzL2y8CeX4hLxMh0IP0L9ggTc.png"
    alt="Logo 4N"
    width="240"
  >
</p>

<h1 align="center">Portal4N</h1>

<p align="center">
  Plataforma interna de gestión administrativa y operativa de 4N.
</p>

---

## Descripción

**Portal4N** es una plataforma web desarrollada para centralizar y apoyar distintos procesos administrativos y operativos de la empresa.

El sistema permite gestionar información, documentos, solicitudes, operaciones mensuales y procesos internos desde una sola aplicación, manteniendo control, trazabilidad y acceso según los permisos de cada usuario.

## Módulos principales

Portal4N contempla funcionalidades relacionadas con:

- Gestión de usuarios, roles y permisos.
- Recursos humanos y trabajadores.
- Compras y cobranzas.
- Ventas y cuentas por cobrar.
- Gestión de proveedores.
- Honorarios y documentación financiera.
- Reclamos y seguimiento administrativo.
- Generación de documentos y reportes.
- Importación y exportación de archivos Excel.
- Suscripciones, liquidaciones y prefacturación.
- Procesos administrativos y operativos internos.

## Tecnologías

### Backend

- PHP 8.2
- Laravel 11
- MySQL
- Blade
- Livewire
- Spatie Laravel Permission

### Frontend

- JavaScript
- Vite
- Bootstrap
- Tailwind CSS
- React en interfaces específicas
- Axios
- Leaflet

### Documentos y archivos

- Laravel Excel
- PhpSpreadsheet
- DomPDF
- FPDF
- FPDI

## Requisitos

Para ejecutar el proyecto localmente se necesita:

- PHP 8.2 o superior
- Composer
- Node.js y npm
- MySQL
- Servidor web local o entorno compatible con Laravel

## Instalación local

Clonar el repositorio:

```bash
git clone https://github.com/Elaias99/Portal4N.git
cd Portal4N
```

Instalar las dependencias de PHP:

```bash
composer install
```

Instalar las dependencias del frontend:

```bash
npm install
```

Crear el archivo de configuración local:

```bash
cp .env.example .env
```

En Windows PowerShell también puede utilizarse:

```powershell
Copy-Item .env.example .env
```

Generar la clave de Laravel:

```bash
php artisan key:generate
```

Configurar en `.env` la conexión correspondiente a la base de datos.

Ejecutar las migraciones cuando corresponda:

```bash
php artisan migrate
```

Iniciar la aplicación:

```bash
php artisan serve
```

En otra terminal, iniciar Vite:

```bash
npm run dev
```

## Laravel Brain

El proyecto incorpora Laravel Brain como dependencia de desarrollo para analizar y visualizar su arquitectura.

Para actualizar el análisis:

```bash
php artisan brain:scan
```

Con el servidor local iniciado, el visor se encuentra normalmente en:

```text
http://localhost:8000/_laravel-brain
```

Laravel Brain es una herramienta exclusiva para desarrollo y no debe exponerse en producción.

## Seguridad

Los siguientes elementos no deben almacenarse en el repositorio:

- Archivos `.env`.
- Credenciales o claves privadas.
- Copias de bases de datos.
- Archivos cargados por usuarios.
- Registros de producción.
- Resultados locales generados por Laravel Brain.

La configuración real de cada entorno debe mantenerse fuera del control de versiones.

## Estado del proyecto

Portal4N se encuentra en desarrollo y mejora continua. Sus módulos evolucionan de acuerdo con los requerimientos administrativos y operativos de la empresa.

## Uso

Sistema desarrollado para uso interno de **4N**.