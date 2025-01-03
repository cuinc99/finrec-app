<?php

namespace App\Filament\Resources\TransactionResource\Widgets;

use App\Filament\Resources\TransactionResource\Pages\ListTransactions;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TransactionStats extends BaseWidget
{
    use InteractsWithPageTable;

    protected static ?string $pollingInterval = null;

    protected function getTablePage(): string
    {
        return ListTransactions::class;
    }

    protected function getStats(): array
    {
        return [
            Stat::make(
                label: __('models.transactions.title'),
                value: number_format((clone $this->getPageTableQuery())->count()))
                ->icon('heroicon-m-shopping-cart')
                ->extraAttributes([
                    'class' => 'bg-transaction',
                ]),
            Stat::make(
                label: __('models.common.sold'),
                value: number_format((clone $this->getPageTableQuery())->sum('quantity')))
                ->icon('heroicon-o-chart-bar')
                ->extraAttributes([
                    'class' => 'bg-sold',
                ]),
            Stat::make(
                label: __('models.transactions.fields.total_sales'),
                value: __('Rp. '.number_format((clone $this->getPageTableQuery())->sum('subtotal_after_discount'), 0, ',', '.')))
                ->icon('heroicon-o-currency-dollar')
                ->extraAttributes([
                    'class' => 'bg-sales',
                ]),
            // Stat::make(
            //     label: __('models.transactions.fields.profit'),
            //     value: __("Rp. " . number_format((clone $this->getPageTableQuery())->sum('profit'), 0, ',', '.')))
            //     ->color('success')
            //     ->icon('heroicon-o-presentation-chart-line')
            //     ->extraAttributes([
            //         'class' => 'bg-profit',
            //     ]),
        ];
    }
}
