<?php

namespace Vendor\Client\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Vendor\Client\Models\Client;

class ClientsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $clients;

    public function __construct()
    {
        $this->clients = Client::byTenant(auth()->user()->tenant_id)->get();
    }

    public function collection()
    {
        return $this->clients;
    }

    public function headings(): array
    {
        return trans('client::clients.export.headings');
    }

    public function map($client): array
    {
        return [
            $client->id,
            $client->company_name,
            $client->contact_name,
            $client->email,
            $client->phone,
            $client->type_label,
            $client->status_label,
            $client->source_label,
            $client->city,
            $client->postal_code,
            $client->country,
            $client->siret,
            $client->vat_number,
            $client->revenue,
            $client->created_at->format('d/m/Y'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
