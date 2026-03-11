<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Subscription Management';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payment Information')
                    ->schema([
                        Forms\Components\Select::make('vendor_id')
                            ->relationship('vendor', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('subscription_id')
                            ->relationship('subscription', 'id', fn ($query) => $query->with('vendor'))
                            ->getOptionLabelFromRecordUsing(fn ($record) => "#{$record->id} - {$record->vendor?->name}")
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('plan_id')
                            ->relationship('plan', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('payment_number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn () => Payment::generatePaymentNumber()),

                        Forms\Components\Textarea::make('description')
                            ->rows(2),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Amount Details')
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->minValue(0)
                            ->step(0.01)
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                $amount = (float) $get('amount');
                                $tax = (float) $get('tax_amount');
                                $discount = (float) $get('discount_amount');
                                $set('total_amount', $amount + $tax - $discount);
                            }),

                        Forms\Components\TextInput::make('tax_amount')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->minValue(0)
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                $amount = (float) $get('amount');
                                $tax = (float) $get('tax_amount');
                                $discount = (float) $get('discount_amount');
                                $set('total_amount', $amount + $tax - $discount);
                            }),

                        Forms\Components\TextInput::make('discount_amount')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->minValue(0)
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                $amount = (float) $get('amount');
                                $tax = (float) $get('tax_amount');
                                $discount = (float) $get('discount_amount');
                                $set('total_amount', $amount + $tax - $discount);
                            }),

                        Forms\Components\TextInput::make('total_amount')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->disabled(),

                        Forms\Components\Select::make('currency')
                            ->options([
                                'USD' => 'USD',
                                'EUR' => 'EUR',
                                'GBP' => 'GBP',
                            ])
                            ->default('USD')
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Payment Method')
                    ->schema([
                        Forms\Components\Select::make('payment_method')
                            ->options([
                                'credit_card' => 'Credit Card',
                                'debit_card' => 'Debit Card',
                                'bank_transfer' => 'Bank Transfer',
                                'paypal' => 'PayPal',
                                'stripe' => 'Stripe',
                                'manual' => 'Manual',
                                'other' => 'Other',
                            ])
                            ->default('stripe')
                            ->required(),

                        Forms\Components\TextInput::make('payment_method_details')
                            ->placeholder('Card ending in 4242, etc.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'processing' => 'Processing',
                                'completed' => 'Completed',
                                'failed' => 'Failed',
                                'refunded' => 'Refunded',
                                'partially_refunded' => 'Partially Refunded',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('pending'),

                        Forms\Components\DateTimePicker::make('paid_at')
                            ->label('Paid At'),

                        Forms\Components\DateTimePicker::make('refunded_at')
                            ->label('Refunded At'),

                        Forms\Components\TextInput::make('refunded_amount')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),

                        Forms\Components\Textarea::make('failure_reason')
                            ->rows(2)
                            ->visible(fn (Forms\Get $get) => $get('status') === 'failed'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Billing Information')
                    ->schema([
                        Forms\Components\TextInput::make('billing_name'),
                        Forms\Components\TextInput::make('billing_email')
                            ->email(),
                        Forms\Components\TextInput::make('billing_address'),
                        Forms\Components\TextInput::make('billing_city'),
                        Forms\Components\TextInput::make('billing_state'),
                        Forms\Components\TextInput::make('billing_country'),
                        Forms\Components\TextInput::make('billing_postal_code'),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Stripe Integration')
                    ->schema([
                        Forms\Components\TextInput::make('stripe_payment_intent_id')
                            ->label('Payment Intent ID'),
                        Forms\Components\TextInput::make('stripe_charge_id')
                            ->label('Charge ID'),
                        Forms\Components\TextInput::make('stripe_invoice_id')
                            ->label('Invoice ID'),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\KeyValue::make('metadata')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payment_number')
                    ->searchable()
                    ->sortable()
                    ->weight('font-medium')
                    ->copyable(),

                Tables\Columns\TextColumn::make('vendor.name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('plan.name')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => ['pending', 'processing'],
                        'success' => 'completed',
                        'danger' => ['failed', 'cancelled'],
                        'secondary' => ['refunded', 'partially_refunded'],
                    ])
                    ->icons([
                        'heroicon-m-clock' => ['pending', 'processing'],
                        'heroicon-m-check-circle' => 'completed',
                        'heroicon-m-x-circle' => ['failed', 'cancelled'],
                        'heroicon-m-arrow-uturn-left' => ['refunded', 'partially_refunded'],
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->money(fn ($record) => $record->currency)
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('paid_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        'credit_card' => 'Credit Card',
                        'debit_card' => 'Debit Card',
                        'bank_transfer' => 'Bank Transfer',
                        'paypal' => 'PayPal',
                        'stripe' => 'Stripe',
                        'manual' => 'Manual',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('paid_at')
                    ->form([
                        Forms\Components\DatePicker::make('paid_from'),
                        Forms\Components\DatePicker::make('paid_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['paid_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('paid_at', '>=', $date),
                            )
                            ->when(
                                $data['paid_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('paid_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('markPaid')
                        ->label('Mark as Paid')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Payment $record) => in_array($record->status, ['pending', 'processing', 'failed']))
                        ->action(function (Payment $record) {
                            $record->markAsPaid();
                        }),

                    Tables\Actions\Action::make('refund')
                        ->label('Refund')
                        ->icon('heroicon-m-arrow-uturn-left')
                        ->color('warning')
                        ->form([
                            Forms\Components\TextInput::make('amount')
                                ->numeric()
                                ->prefix('$')
                                ->required()
                                ->default(fn (Payment $record) => $record->getRemainingRefundableAmount())
                                ->maxValue(fn (Payment $record) => $record->getRemainingRefundableAmount()),
                        ])
                        ->visible(fn (Payment $record) => $record->canBeRefunded())
                        ->action(function (Payment $record, array $data) {
                            $record->refund($data['amount']);
                        }),

                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'view' => Pages\ViewPayment::route('/{record}'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
