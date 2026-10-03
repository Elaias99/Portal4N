<?php

namespace App\Services\Courier\Geo;

use RuntimeException;

/** Mensaje de captura que puede mostrarse sin exponer respuestas ni credenciales de Geo. */
class GeoliceCaptureException extends RuntimeException {}
