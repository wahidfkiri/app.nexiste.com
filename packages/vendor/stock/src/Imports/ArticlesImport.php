<?php

namespace Vendor\Stock\Imports;

use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;
use Vendor\Stock\Services\StockService;

class ArticlesImport implements OnEachRow, WithHeadingRow
{
    public function __construct(protected ?StockService $service = null)
    {
        $this->service = $this->service ?: app(StockService::class);
    }

    public function onRow(Row $row): void
    {
        $data = $row->toArray();

        $this->service->createArticle([
            'sku' => $data['sku'] ?? null,
            'name' => $data['nom'] ?? ($data['name'] ?? null),
            'unit' => $data['unite'] ?? ($data['unit'] ?? trans('stock::stock.common.unit_piece')),
            'purchase_price' => (float) ($data['prix_achat'] ?? 0),
            'sale_price' => (float) ($data['prix_vente'] ?? ($data['sale_price'] ?? 0)),
            'opening_stock' => (float) ($data['stock'] ?? 0),
            'min_stock' => (float) ($data['stock_minimum'] ?? 0),
            'status' => in_array(($data['statut'] ?? 'active'), ['active', 'inactive'], true) ? ($data['statut'] ?? 'active') : 'active',
        ]);
    }
}
