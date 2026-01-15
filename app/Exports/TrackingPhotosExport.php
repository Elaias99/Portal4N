<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TrackingPhotosExport implements FromArray, WithHeadings
{
    protected string $tracking;
    protected string $state;
    protected array $photos;

    public function __construct(string $tracking, string $state, array $photos)
    {
        $this->tracking = $tracking;
        $this->state = $state;
        $this->photos = $photos;
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->photos as $url) {
            $rows[] = [
                'tracking' => $this->tracking,
                'estado'   => $this->state,
                'url'      => $url,
            ];
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['Tracking', 'Estado', 'URL'];
    }
}
