<?php

namespace Vendor\Stock\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Vendor\Automation\Contracts\AutomationContextEvent;
use Vendor\Stock\Models\Article;

class LowStockThresholdReached implements AutomationContextEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Article $article,
        public array $meta = []
    ) {
    }

    public function automationSourceEvent(): string
    {
        return 'stock_low_threshold_reached';
    }

    public function automationTenantId(): int
    {
        return (int) $this->article->tenant_id;
    }

    public function automationUserId(): ?int
    {
        return $this->article->user_id ? (int) $this->article->user_id : null;
    }

    public function automationSourceType(): ?string
    {
        return $this->article::class;
    }

    public function automationSourceId(): int|string|null
    {
        return $this->article->getKey();
    }

    public function automationSource(): mixed
    {
        return $this->article;
    }

    public function automationContext(): array
    {
        $this->article->loadMissing('supplier');

        return [
            'article' => [
                'id' => (int) $this->article->id,
                'name' => (string) $this->article->name,
                'sku' => (string) ($this->article->sku ?? ''),
                'unit' => (string) ($this->article->unit ?? ''),
                'status' => (string) ($this->article->status ?? ''),
                'current_stock' => (float) $this->article->current_stock,
                'min_stock' => (float) $this->article->min_stock,
                'supplier_id' => $this->article->supplier_id ? (int) $this->article->supplier_id : null,
                'supplier_name' => (string) optional($this->article->supplier)->name,
            ],
            'meta' => $this->meta,
        ];
    }
}
