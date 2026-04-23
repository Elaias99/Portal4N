<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\TrackingAlmacenado;
use Smalot\PdfParser\Parser;
use Carbon\Carbon;

class LatamGuideImportController extends Controller
{
    public function index()
    {
        return view('latam-guide-import.index', [
            'preview' => null,
            'rawText' => null,
            'error' => null,
        ]);
    }

    public function previewPdf(Request $request)
    {
        $request->validate([
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        try {
            $pdf = $request->file('pdf');

            $rawText = $this->extractPdfText($pdf->getRealPath());
            $preview = $this->parseLatamPdfText($rawText);

            return view('latam-guide-import.index', [
                'preview' => $preview,
                'rawText' => $rawText,
                'error' => null,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return view('latam-guide-import.index', [
                'preview' => null,
                'rawText' => null,
                'error' => 'No se pudo procesar el PDF: ' . $e->getMessage(),
            ]);
        }
    }

    private function extractPdfText(string $pdfPath): string
    {
        $parser = new Parser();
        $document = $parser->parseFile($pdfPath);
        $text = trim($document->getText());

        if ($text === '') {
            throw new \RuntimeException('El PDF no devolvió texto.');
        }

        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        $text = preg_replace("/\r\n|\r/u", "\n", $text) ?? $text;

        return $text;
    }

    private function parseLatamPdfText(string $text): array
    {
        $lines = preg_split('/\R/u', $text) ?: [];

        $lines = array_values(array_filter(array_map(function ($line) {
            $line = preg_replace('/\s+/u', ' ', trim($line)) ?? trim($line);
            return $line;
        }, $lines), fn ($line) => $line !== ''));

        $normalized = implode(' ', $lines);

        $os = null;
        $prefix = null;
        $code = null;
        $origin = null;
        $destinationCode = null;
        $originCity = null;
        $destinationCity = null;
        $issuedAt = null;
        $fechaProceso = null;
        $destinoFormulario = null;

        if (preg_match('/\b(\d{3})-(\d{8})\b/u', $normalized, $m)) {
            $prefix = $m[1];
            $code = $m[2];
            $os = $prefix . '-' . $code;
        }

        if (preg_match('/\bOrigen\s*([A-Z]{3})\b/iu', $normalized, $m)) {
            $origin = strtoupper($m[1]);
        }

        if (preg_match('/\bDestino\s*([A-Z]{3})\b/iu', $normalized, $m)) {
            $destinationCode = strtoupper($m[1]);
        }

        if (preg_match('/\bSHP\.EmiDateTime\s*(\d{4}-\d{2}-\d{2})(?:\s*(\d{2}:\d{2}))?/iu', $normalized, $m)) {
            $issuedAt = trim(($m[1] ?? '') . ' ' . ($m[2] ?? ''));

            $fechaProceso = Carbon::createFromFormat('Y-m-d', $m[1])
                ->addDay()
                ->toDateString();
        }

        foreach ($lines as $line) {
            if ($originCity === null && preg_match('/^Ciudad\s+(.+)$/iu', $line, $m)) {
                $candidate = $this->cleanValue($m[1]);

                if ($candidate && !preg_match('/^(SHP\.EmiDateTime|Nombre de usuario|Datos del Envio)$/iu', $candidate)) {
                    $originCity = $candidate;
                    continue;
                }
            }

            if ($destinationCity === null && preg_match('/^(.+?)\s+Ciudad$/iu', $line, $m)) {
                $candidate = $this->cleanValue($m[1]);

                if ($candidate && !preg_match('/^(Origen|Destino|Numero de OS)$/iu', $candidate)) {
                    $destinationCity = $candidate;
                    continue;
                }
            }
        }

        if (($originCity === null || $destinationCity === null)
            && preg_match('/Ciudad\s+([A-ZÁÉÍÓÚÑ]+(?:\s+[A-ZÁÉÍÓÚÑ]+)*)\s+([A-ZÁÉÍÓÚÑ]+(?:\s+[A-ZÁÉÍÓÚÑ]+)*)\s+Ciudad\s+SHP\.EmiDateTime/iu', $normalized, $m)) {
            $originCity = $originCity ?? $this->cleanValue($m[1]);
            $destinationCity = $destinationCity ?? $this->cleanValue($m[2]);
        }

        if ($origin && $destinationCity) {
            $destinoFormulario = $origin . ' ' . $destinationCity;
        }

        return [
            'os' => $os,
            'prefijo' => $prefix,
            'codigo_tracking' => $code,
            'origen_codigo' => $origin,
            'destino_codigo' => $destinationCode,
            'ciudad_origen' => $originCity,
            'ciudad_destino' => $destinationCity,
            'fecha_emision_raw' => $issuedAt,
            'fecha_proceso' => $fechaProceso,
            'destino_formulario' => $destinoFormulario,
        ];
    }

    private function cleanValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);

        return $value !== '' ? $value : null;
    }



    public function storePreview(Request $request)
    {
        $validated = $request->validate([
            'prefijo' => ['required', 'regex:/^\d{3}$/'],
            'codigo_tracking' => ['required', 'regex:/^\d{8}$/'],
            'fecha_proceso' => ['required', 'date'],
            'destino' => ['required', 'string', 'max:50'],
        ]);

        $tracking = TrackingAlmacenado::firstOrCreate([
            'prefijo' => $validated['prefijo'],
            'codigo_tracking' => $validated['codigo_tracking'],
            'fecha_proceso' => $validated['fecha_proceso'],
            'destino' => $validated['destino'],
        ]);

        return redirect()
            ->route('latam-guide-import.index')
            ->with(
                $tracking->wasRecentlyCreated ? 'success' : 'warning',
                $tracking->wasRecentlyCreated
                    ? 'Tracking guardado correctamente en el sistema.'
                    : 'Ese tracking ya existía en el sistema.'
            );
    }


    public function inbox()
    {
        return view('latam-guide-import.inbox', [
            'today' => now()->format('d-m-Y'),
            'emails' => $this->availableInboxEmails(),
            'selectedEmailId' => null,
            'preview' => null,
            'error' => null,
        ]);
    }

    public function previewInboxAttachment(string $emailId)
    {
        $email = $this->findMockInboxEmail($emailId);

        abort_unless($email, 404, 'Correo no encontrado.');

        try {
            $rawText = $this->extractPdfText($email['pdf_path']);
            $preview = $this->parseLatamPdfText($rawText);

            return view('latam-guide-import.inbox', [
                'today' => now()->format('d-m-Y'),
                'emails' => $this->availableInboxEmails(),
                'selectedEmailId' => $emailId,
                'preview' => $preview,
                'error' => null,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return view('latam-guide-import.inbox', [
                'today' => now()->format('d-m-Y'),
                'emails' => $this->availableInboxEmails(),
                'selectedEmailId' => $emailId,
                'preview' => null,
                'error' => 'No se pudo procesar el adjunto: ' . $e->getMessage(),
            ]);
        }
    }

    public function storeInboxAttachment(string $emailId)
    {
        $email = $this->findMockInboxEmail($emailId);

        abort_unless($email, 404, 'Correo no encontrado.');

        $rawText = $this->extractPdfText($email['pdf_path']);
        $preview = $this->parseLatamPdfText($rawText);

        if (
            blank($preview['prefijo'] ?? null) ||
            blank($preview['codigo_tracking'] ?? null) ||
            blank($preview['fecha_proceso'] ?? null) ||
            blank($preview['destino_formulario'] ?? null)
        ) {
            return redirect()
                ->route('latam-guide-import.inbox')
                ->with('warning', 'No fue posible obtener todos los datos necesarios desde el adjunto.');
        }

        $tracking = TrackingAlmacenado::firstOrCreate([
            'prefijo' => $preview['prefijo'],
            'codigo_tracking' => $preview['codigo_tracking'],
            'fecha_proceso' => $preview['fecha_proceso'],
            'destino' => $preview['destino_formulario'],
        ]);

        return redirect()
            ->route('latam-guide-import.inbox')
            ->with(
                $tracking->wasRecentlyCreated ? 'success' : 'warning',
                $tracking->wasRecentlyCreated
                    ? 'Tracking guardado correctamente desde la bandeja.'
                    : 'Ese tracking ya existía en el sistema.'
            );
    }

    private function mockInboxEmails(): array
    {
        return [
            [
                'id' => 'mail-001',
                'time' => '00:14',
                'from' => 'noreply2.croamis@latam.com',
                'subject' => 'Prefijo de OS-N?mero/Origen-Destino/Nombre del Cliente Tomador',
                'attachment_name' => 'SSC-Document63753617498852.pdf',
                'pdf_path' => storage_path('app/latam-guides/SSC-Document63753617498852.pdf'),
                'has_attachment' => true,
            ],
            [
                'id' => 'mail-002',
                'time' => '00:16',
                'from' => 'noreply2.croamis@latam.com',
                'subject' => 'Prefijo de OS-N?mero/Origen-Destino/Nombre del Cliente Tomador',
                'attachment_name' => 'SSC-Document63753617498850.pdf',
                'pdf_path' => storage_path('app/latam-guides/SSC-Document63753617498850.pdf'),
                'has_attachment' => true,
            ],
            [
                'id' => 'mail-003',
                'time' => '00:19',
                'from' => 'noreply2.croamis@latam.com',
                'subject' => 'Prefijo de OS-N?mero/Origen-Destino/Nombre del Cliente Tomador',
                'attachment_name' => 'SSC-Document63753617433697.pdf',
                'pdf_path' => storage_path('app/latam-guides/SSC-Document63753617433697.pdf'),
                'has_attachment' => true,
            ],
            [
                'id' => 'mail-004',
                'time' => '00:36',
                'from' => 'noreply2.croamis@latam.com',
                'subject' => 'Prefijo de OS-N?mero/Origen-Destino/Nombre del Cliente Tomador',
                'attachment_name' => 'SSC-Document63753617379596.pdf',
                'pdf_path' => storage_path('app/latam-guides/SSC-Document63753617379596.pdf'),
                'has_attachment' => true,
            ],
        ];
    }

    private function findMockInboxEmail(string $emailId): ?array
    {
        foreach ($this->mockInboxEmails() as $email) {
            if ($email['id'] === $emailId) {
                return $email;
            }
        }

        return null;
    }


    private function availableInboxEmails(): array
    {
        $emails = [];

        foreach ($this->mockInboxEmails() as $email) {
            try {
                $rawText = $this->extractPdfText($email['pdf_path']);
                $preview = $this->parseLatamPdfText($rawText);

                $canCompare =
                    filled($preview['prefijo'] ?? null) &&
                    filled($preview['codigo_tracking'] ?? null) &&
                    filled($preview['fecha_proceso'] ?? null) &&
                    filled($preview['destino_formulario'] ?? null);

                if (!$canCompare) {
                    $emails[] = $email;
                    continue;
                }

                $alreadyStored = TrackingAlmacenado::query()
                    ->where('prefijo', $preview['prefijo'])
                    ->where('codigo_tracking', $preview['codigo_tracking'])
                    ->whereDate('fecha_proceso', $preview['fecha_proceso'])
                    ->where('destino', $preview['destino_formulario'])
                    ->exists();

                if (!$alreadyStored) {
                    $emails[] = $email;
                }
            } catch (\Throwable $e) {
                report($e);
                $emails[] = $email;
            }
        }

        return array_values($emails);
    }



}