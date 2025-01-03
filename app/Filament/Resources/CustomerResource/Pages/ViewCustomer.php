<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Support\Htmlable;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    public function getTitle(): string|Htmlable
    {
        if (filled(static::$title)) {
            return static::$title;
        }

        return __('models.customers.links.view', [
            'label' => $this->getRecordTitle(),
        ]);
    }

    public static function getNavigationLabel(): string
    {
        return 'Data '.__('models.transactions.title');
    }

    public static function getNavigationIcon(): string|Htmlable|null
    {
        return 'heroicon-o-shopping-cart';
    }

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }
}
