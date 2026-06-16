<?php

namespace Vendor\Stock\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Vendor\Stock\Models\Article;

class ArticlesExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Article::query()
            ->withCurrentStock()
            ->get(['stock_articles.id', 'sku', 'name', 'unit', 'purchase_price', 'sale_price', 'min_stock', 'status'])
            ->map(function (Article $article) {
                return [
                    'id' => $article->id,
                    'sku' => $article->sku,
                    'name' => $article->name,
                    'unit' => $article->unit,
                    'purchase_price' => $article->purchase_price,
                    'sale_price' => $article->sale_price,
                    'current_stock' => $article->current_stock,
                    'min_stock' => $article->min_stock,
                    'status' => $article->status,
                ];
            });
    }

    public function headings(): array
    {
        return trans('stock::stock.exports.articles');
    }
}
