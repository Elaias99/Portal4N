<?php

namespace App\Imports;

use App\Services\Ventas\DocumentoFinancieroPostImportService;
use App\Services\Ventas\DocumentoFinancieroRowStoreService;
use App\Support\Ventas\DocumentoFinancieroImportState;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class DocumentosImport implements ToModel, WithHeadingRow, SkipsOnError
{
    use SkipsErrors;

    public array $errores = [];
    public array $importados = [];
    public array $duplicados = [];
    public array $sinCobranza = [];
    public array $notasCredito = [];

    protected ?int $empresaId;
    protected DocumentoFinancieroImportState $state;

    public function __construct(
        ?int $empresaId = null,
        protected ?DocumentoFinancieroRowStoreService $rowStoreService = null,
        protected ?DocumentoFinancieroPostImportService $postImportService = null,
    ) {
        $this->empresaId = $empresaId;
        $this->state = new DocumentoFinancieroImportState();

        $this->rowStoreService ??= app(DocumentoFinancieroRowStoreService::class);
        $this->postImportService ??= app(DocumentoFinancieroPostImportService::class);

        $this->state->syncToImport($this);
    }

    public function model(array $row)
    {
        $documento = $this->rowStoreService->handle(
            row: $row,
            empresaId: $this->empresaId,
            state: $this->state,
        );

        $this->state->syncToImport($this);

        return $documento;
    }

    public function onError(Throwable $e)
    {
        $this->state->agregarError($e->getMessage());
        $this->state->syncToImport($this);
    }

    public function afterImport(): void
    {
        $this->postImportService->execute($this->state);
        $this->state->syncToImport($this);
    }
}