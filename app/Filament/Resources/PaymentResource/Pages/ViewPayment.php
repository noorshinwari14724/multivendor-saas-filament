<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            
            Actions\Action::make('markPaid')
                ->label('Mark as Paid')
                ->icon('heroicon-m-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => in_array($this->record->status, ['pending', 'processing', 'failed']))
                ->action(function () {
                    $this->record->markAsPaid();
                }),

            Actions\Action::make('refund')
                ->label('Refund')
                ->icon('heroicon-m-arrow-uturn-left')
                ->color('warning')
                ->form([
                    \Filament\Forms\Components\TextInput::make('amount')
                        ->numeric()
                        ->prefix('$')
                        ->required()
                        ->default(fn () => $this->record->getRemainingRefundableAmount())
                        ->maxValue(fn () => $this->record->getRemainingRefundableAmount()),
                ])
                ->visible(fn () => $this->record->canBeRefunded())
                ->action(function (array $data) {
                    $this->record->refund($data['amount']);
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Payment Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('payment_number')
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->weight('font-bold')
                                    ->copyable(),
                                
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending', 'processing' => 'warning',
                                        'completed' => 'success',
                                        'failed', 'cancelled' => 'danger',
                                        'refunded', 'partially_refunded' => 'secondary',
                                        default => 'gray',
                                    }),
                                
                                TextEntry::make('payment_method')
                                    ->badge()
                                    ->color('gray'),
                            ]),
                        
                        TextEntry::make('description')
                            ->columnSpanFull()
                            ->placeholder('No description provided'),
                    ]),

                Section::make('Amount Breakdown')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('amount')
                                    ->money(fn ($record) => $record->currency)
                                    ->label('Subtotal'),
                                
                                TextEntry::make('tax_amount')
                                    ->money(fn ($record) => $record->currency)
                                    ->label('Tax'),
                                
                                TextEntry::make('discount_amount')
                                    ->money(fn ($record) => $record->currency)
                                    ->label('Discount'),
                                
                                TextEntry::make('total_amount')
                                    ->money(fn ($record) => $record->currency)
                                    ->label('Total')
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->weight('font-bold')
                                    ->color('success'),
                            ]),
                    ]),

                Section::make('Associated Records')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('vendor.name')
                                    ->label('Vendor')
                                    ->icon('heroicon-m-building-storefront'),
                                
                                TextEntry::make('plan.name')
                                    ->label('Plan')
                                    ->icon('heroicon-m-credit-card')
                                    ->placeholder('N/A'),
                                
                                TextEntry::make('subscription.id')
                                    ->label('Subscription')
                                    ->formatStateUsing(fn ($state) => $state ? "#{$state}" : 'N/A')
                                    ->icon('heroicon-m-arrow-path-rounded-square')
                                    ->placeholder('N/A'),
                            ]),
                    ]),

                Section::make('Payment Status Timeline')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('paid_at')
                                    ->label('Paid At')
                                    ->dateTime('F j, Y, g:i a')
                                    ->placeholder('Not paid yet')
                                    ->icon('heroicon-m-check-circle'),
                                
                                TextEntry::make('refunded_at')
                                    ->label('Refunded At')
                                    ->dateTime('F j, Y, g:i a')
                                    ->placeholder('Not refunded')
                                    ->icon('heroicon-m-arrow-uturn-left'),
                                
                                TextEntry::make('refunded_amount')
                                    ->label('Refunded Amount')
                                    ->money(fn ($record) => $record->currency)
                                    ->placeholder('N/A'),
                            ]),
                    ]),

                Section::make('Billing Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('billing_name')
                                    ->label('Name')
                                    ->placeholder('Not provided'),
                                
                                TextEntry::make('billing_email')
                                    ->label('Email')
                                    ->placeholder('Not provided'),
                                
                                TextEntry::make('billing_address')
                                    ->label('Address')
                                    ->placeholder('Not provided'),
                                
                                TextEntry::make('billing_city')
                                    ->label('City')
                                    ->placeholder('Not provided'),
                                
                                TextEntry::make('billing_state')
                                    ->label('State')
                                    ->placeholder('Not provided'),
                                
                                TextEntry::make('billing_country')
                                    ->label('Country')
                                    ->placeholder('Not provided'),
                                
                                TextEntry::make('billing_postal_code')
                                    ->label('Postal Code')
                                    ->placeholder('Not provided'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Stripe Integration')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('stripe_payment_intent_id')
                                    ->label('Payment Intent ID')
                                    ->placeholder('Not connected')
                                    ->copyable(),
                                
                                TextEntry::make('stripe_charge_id')
                                    ->label('Charge ID')
                                    ->placeholder('Not connected')
                                    ->copyable(),
                                
                                TextEntry::make('stripe_invoice_id')
                                    ->label('Invoice ID')
                                    ->placeholder('Not connected')
                                    ->copyable(),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Notes')
                    ->schema([
                        TextEntry::make('notes')
                            ->columnSpanFull()
                            ->placeholder('No notes'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
