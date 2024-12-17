<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Transaction;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;

class CreateTransaction extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static string $resource = TransactionResource::class;

    protected static string $view = 'filament.resources.transaction-resource.pages.create-transaction';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Hidden::make('user_id')
                            ->default(auth()->id())
                            ->required(),
                        Forms\Components\DatePicker::make('purchase_date')
                            ->label(__('models.transactions.fields.purchase_date'))
                            ->default(now())
                            ->maxDate(now()->addDay())
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->prefixIcon('heroicon-m-calendar-days')
                            ->required(),
                        Forms\Components\Select::make('customer_id')
                            ->label(__('models.customers.title'))
                            ->options(Customer::where('user_id', auth()->id())->pluck('name', 'id'))
                            ->prefixIcon('heroicon-m-user-circle')
                            ->searchable()
                            ->required(),
                    ])->columns(2),
                Forms\Components\Section::make()
                    ->schema([
                        TableRepeater::make('items')
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->headers([
                                Header::make('product')
                                    ->label(__('models.transactions.fields.product'))
                                    ->markAsRequired(),
                                Header::make('price')
                                    ->label(__('models.transactions.fields.price')),
                                Header::make('quantity')
                                    ->label(__('models.transactions.fields.quantity'))
                                    ->width('100px')
                                    ->markAsRequired(),
                                Header::make('subtotal')
                                    ->label(__('models.transactions.fields.subtotal')),
                                Header::make('discount')
                                    ->label(__('models.transactions.fields.discount'))
                                    ->width('150px'),
                                Header::make('subtotal_after_discount')
                                    ->label(__('models.transactions.fields.subtotal_after_discount')),
                                Header::make('total_capital')
                                    ->label(__('models.transactions.fields.total_capital')),
                                Header::make('profit')
                                    ->label(__('models.transactions.fields.profit')),
                            ])
                            ->schema([
                                // product select
                                Forms\Components\Select::make('product_id')
                                    ->label(__('models.transactions.fields.product'))
                                    ->options(Product::where('user_id', auth()->id())->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->live(debounce: 1000)
                                    ->afterStateUpdated(function (Set $set, Get $get, ?int $state) {
                                        $product = Product::find($state);
                                        $quantity = $get('quantity');
                                        $discount = $get('discount');

                                        $this->updateData($product, $quantity, $discount, $set);
                                    })
                                    ->required(),

                                // price display
                                Forms\Components\Placeholder::make("price_display")
                                    ->label(__('models.transactions.fields.price'))
                                    ->hiddenLabel()
                                    ->content(function (Get $get): string {
                                        $price = $get('price');
                                        return __("Rp. " . number_format($price, 0, ',', '.'));
                                    }),

                                // price hidden
                                Forms\Components\Hidden::make('price')
                                    ->required(),

                                // quantity input
                                Forms\Components\TextInput::make('quantity')
                                    ->label(__('models.transactions.fields.quantity'))
                                    ->minValue(1)
                                    ->default(1)
                                    ->live(debounce: 1000)
                                    ->integer()
                                    ->afterStateUpdated(function (Set $set, Get $get, ?int $state) {
                                        $product = Product::find($get('product_id'));
                                        $quantity = $state;
                                        $discount = $get('discount');

                                        $this->updateData($product, $quantity, $discount, $set);
                                    })
                                    ->disabled(fn(Get $get) => !$get('product_id'))
                                    ->required(),

                                // subtotal display
                                Forms\Components\Placeholder::make("subtotal_display")
                                    ->label(__('models.transactions.fields.subtotal'))
                                    ->hiddenLabel()
                                    ->content(function (Get $get) {
                                        $subtotal = $get('subtotal');
                                        return __("Rp. " . number_format($subtotal, 2, ',', '.'));
                                    }),

                                // subtotal hideen
                                Forms\Components\Hidden::make('subtotal')
                                    ->required(),

                                // discount input
                                Forms\Components\TextInput::make('discount')
                                    ->label(__('models.transactions.fields.discount'))
                                    ->live(debounce: 1000)
                                    ->minValue(0)
                                    ->default(0)
                                    ->integer()
                                    ->afterStateUpdated(function (Set $set, Get $get, ?int $state) {
                                        $product = Product::find($get('product_id'));
                                        $quantity = $get('quantity');
                                        $discount = $state;

                                        $this->updateData($product, $quantity, $discount, $set);
                                    })
                                    ->disabled(fn(Get $get) => !$get('product_id')),

                                // subtotal after discount display
                                Forms\Components\Placeholder::make("subtotal_after_discount_display")
                                    ->label(__('models.transactions.fields.subtotal_after_discount'))
                                    ->hiddenLabel()
                                    ->content(function (Get $get) {
                                        $subtotalAfterDiscount = $get('subtotal_after_discount');
                                        return __("Rp. " . number_format($subtotalAfterDiscount, 2, ',', '.'));
                                    }),

                                // subtotal after discount hidden
                                Forms\Components\Hidden::make('subtotal_after_discount')
                                    ->required(),

                                // capital hidden
                                Forms\Components\Hidden::make('capital')
                                    ->required(),

                                // total capital display
                                Forms\Components\Placeholder::make("total_capital_display")
                                    ->label(__('models.transactions.fields.total_capital'))
                                    ->hiddenLabel()
                                    ->content(function (Get $get) {
                                        $profitPerItem = $get('total_capital');
                                        return __("Rp. " . number_format($profitPerItem, 2, ',', '.'));
                                    }),

                                // total capital hidden
                                Forms\Components\Hidden::make('total_capital')
                                    ->required(),

                                // profit per item hidden
                                Forms\Components\Hidden::make('profit_per_item')
                                    ->required(),

                                // profit display
                                Forms\Components\Placeholder::make("profit_display")
                                    ->label(__('models.transactions.fields.profit'))
                                    ->hiddenLabel()
                                    ->content(function (Get $get) {
                                        $profit = $get('profit');
                                        return __("Rp. " . number_format($profit, 2, ',', '.'));
                                    }),

                                // profit hidden
                                Forms\Components\Hidden::make('profit')
                                    ->required(),
                            ]),
                    ]),
            ])->statePath('data');
    }

    public function updateData($product, $quantity, $discount, Set $set)
    {
        $price = $product?->selling_price ?? 0;
        $subtotal = $price * $quantity;
        $subtotalAfterDiscount = $subtotal - $discount;
        $capital = $product?->purchase_price ?? 0;
        $totalCapital = $product?->purchase_price * $quantity;
        $profitPerItem = $price - $capital;
        $profit = $subtotalAfterDiscount - $totalCapital;

        $set('price', $price);
        $set('subtotal', $subtotal);
        $set('subtotal_after_discount', $subtotalAfterDiscount);
        $set('capital', $capital);
        $set('total_capital', $totalCapital);
        $set('profit_per_item', $profitPerItem);
        $set('profit', $profit);
    }

    public function save()
    {
        try {
            $transactionCode = Str::random(2) . rand(10, 99) . Str::random(2) . rand(10, 99);
            $transactions = $this->form->getState();

            foreach ($transactions['items'] as $transaction) {
                Transaction::create([
                    'transaction_code' => $transactionCode,
                    "purchase_date" => $transactions['purchase_date'],
                    "quantity" => $transaction['quantity'],
                    "price" => $transaction['price'],
                    "discount" => $transaction['discount'],
                    "subtotal" => $transaction['subtotal'],
                    "subtotal_after_discount" => $transaction['subtotal_after_discount'],
                    "capital" => $transaction['capital'],
                    "total_capital" => $transaction['total_capital'],
                    "profit_per_item" => $transaction['profit_per_item'],
                    "profit" => $transaction['profit'],
                    "user_id" => $transactions['user_id'],
                    "customer_id" => $transactions['customer_id'],
                    "product_id" => $transaction['product_id'],
                ]);
            }

            Notification::make()
                ->success()
                ->title(__('models.transactions.title') . ' berhasil di ' . ('models.common.create'))
                ->send();

            return redirect('/admin');
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('models.common.save'))
                ->submit('save'),
            Action::make('cancel')
                ->label(__('models.common.cancel'))
                ->url($this->getResource()::getUrl('index'))
                ->color('gray'),
        ];
    }

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    public function getTitle(): string | Htmlable
    {
        if (filled(static::$title)) {
            return static::$title;
        }

        return __('models.common.create') . ' ' . __('models.transactions.title');
    }
}