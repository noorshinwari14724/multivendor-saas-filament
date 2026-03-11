<?php

namespace App\Filament\Resources\SubscriptionResource\Pages;

use App\Filament\Resources\SubscriptionResource;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewSubscription extends ViewRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            
            Actions\Action::make('cancel')
                ->label('Cancel')
                ->icon('heroicon-m-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->is_active && !$this->record->is_canceled)
                ->action(function () {
                    $this->record->cancel();
                }),

            Actions\Action::make('resume')
                ->label('Resume')
                ->icon('heroicon-m-play')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->cancel_at_period_end)
                ->action(function () {
                    $this->record->resume();
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Subscription Overview')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('vendor.name')
                                    ->label('Vendor')
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->weight('font-medium')
                                    ->icon('heroicon-m-building-storefront'),
                                
                                TextEntry::make('plan.name')
                                    ->label('Plan')
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->badge()
                                    ->color('primary'),
                                
                                TextEntry::make('status')
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'active', 'trialing' => 'success',
                                        'past_due', 'incomplete' => 'warning',
                                        default => 'danger',
                                    }),
                            ]),
                    ]),

                Section::make('Billing Information')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('formatted_amount')
                                    ->label('Amount')
                                    ->state(fn ($record) => $record->formatted_amount)
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->weight('font-bold')
                                    ->color('success'),
                                
                                TextEntry::make('billing_cycle')
                                    ->label('Billing Cycle')
                                    ->badge()
                                    ->color('gray'),
                                
                                TextEntry::make('quantity')
                                    ->label('Quantity')
                                    ->numeric(),
                                
                                IconEntry::make('is_trial')
                                    ->label('Trial')
                                    ->boolean(),
                            ]),
                    ]),

                Section::make('Period Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('starts_at')
                                    ->label('Start Date')
                                    ->dateTime('F j, Y, g:i a'),
                                
                                TextEntry::make('current_period_start')
                                    ->label('Current Period Start')
                                    ->dateTime('F j, Y, g:i a'),
                                
                                TextEntry::make('current_period_end')
                                    ->label('Current Period End / Renewal')
                                    ->dateTime('F j, Y, g:i a')
                                    ->color(fn ($state, $record) => $record->days_until_renewal <= 7 ? 'warning' : null),
                                
                                TextEntry::make('trial_ends_at')
                                    ->label('Trial Ends')
                                    ->dateTime('F j, Y, g:i a')
                                    ->placeholder('Not on trial'),
                                
                                TextEntry::make('ends_at')
                                    ->label('Subscription Ends')
                                    ->dateTime('F j, Y, g:i a')
                                    ->placeholder('Ongoing'),
                                
                                TextEntry::make('canceled_at')
                                    ->label('Canceled At')
                                    ->dateTime('F j, Y, g:i a')
                                    ->placeholder('Not canceled'),
                            ]),
                    ]),

                Section::make('Cancellation Status')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                IconEntry::make('cancel_at_period_end')
                                    ->label('Cancel at Period End')
                                    ->boolean(),
                                
                                TextEntry::make('cancel_at')
                                    ->label('Will Cancel At')
                                    ->dateTime('F j, Y, g:i a')
                                    ->placeholder('Not scheduled'),
                            ]),
                    ])
                    ->visible(fn ($record) => $record->cancel_at_period_end || $record->cancel_at),

                Section::make('Stripe Integration')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('stripe_id')
                                    ->label('Subscription ID')
                                    ->placeholder('Not connected')
                                    ->copyable(),
                                
                                TextEntry::make('stripe_status')
                                    ->label('Stripe Status')
                                    ->placeholder('N/A'),
                                
                                TextEntry::make('stripe_price')
                                    ->label('Price ID')
                                    ->placeholder('Not connected')
                                    ->copyable(),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Subscriber Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label('Subscribed By')
                                    ->icon('heroicon-m-user'),
                                
                                TextEntry::make('user.email')
                                    ->label('Email')
                                    ->icon('heroicon-m-envelope'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Payment History')
                    ->schema([
                        TextEntry::make('payments_count')
                            ->label('Total Payments')
                            ->state(fn ($record) => $record->payments()->count())
                            ->icon('heroicon-m-credit-card'),
                        
                        TextEntry::make('total_paid')
                            ->label('Total Paid')
                            ->state(fn ($record) => $record->payments()->where('status', 'completed')->sum('total_amount'))
                            ->money('USD')
                            ->icon('heroicon-m-currency-dollar'),
                    ])
                    ->collapsible(),
            ]);
    }
}
