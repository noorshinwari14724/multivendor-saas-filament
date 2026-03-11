<?php

namespace App\Filament\Resources\PlanResource\Pages;

use App\Filament\Resources\PlanResource;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewPlan extends ViewRecord
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            
            Actions\Action::make('setDefault')
                ->label('Set as Default')
                ->icon('heroicon-m-star')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn () => !$this->record->is_default)
                ->action(function () {
                    $this->record->setAsDefault();
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Plan Overview')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->weight('font-bold'),
                                
                                TextEntry::make('formatted_price')
                                    ->label('Price')
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->weight('font-bold')
                                    ->color('success'),
                            ]),
                        
                        TextEntry::make('description')
                            ->columnSpanFull()
                            ->placeholder('No description provided'),
                    ]),

                Section::make('Status & Configuration')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                IconEntry::make('is_active')
                                    ->label('Active')
                                    ->boolean(),
                                
                                IconEntry::make('is_featured')
                                    ->label('Featured')
                                    ->boolean(),
                                
                                IconEntry::make('is_default')
                                    ->label('Default')
                                    ->boolean(),
                                
                                TextEntry::make('trial_days')
                                    ->label('Trial')
                                    ->formatStateUsing(fn ($state) => $state > 0 ? $state . ' days' : 'No trial')
                                    ->icon('heroicon-m-clock'),
                            ]),
                    ]),

                Section::make('Resource Limits')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('max_users_per_vendor')
                                    ->label('Team Members')
                                    ->formatStateUsing(fn ($state) => $state == -1 ? 'Unlimited' : $state)
                                    ->icon('heroicon-m-users'),
                                
                                TextEntry::make('max_products')
                                    ->label('Products')
                                    ->formatStateUsing(fn ($state) => $state == -1 ? 'Unlimited' : $state)
                                    ->icon('heroicon-m-cube'),
                                
                                TextEntry::make('max_storage_mb')
                                    ->label('Storage')
                                    ->formatStateUsing(fn ($state) => $state == -1 ? 'Unlimited' : number_format($state) . ' MB')
                                    ->icon('heroicon-m-server'),
                                
                                TextEntry::make('max_api_calls_per_day')
                                    ->label('API Calls/Day')
                                    ->formatStateUsing(fn ($state) => $state == -1 ? 'Unlimited' : number_format($state))
                                    ->icon('heroicon-m-bolt'),
                            ]),
                    ]),

                Section::make('Features Included')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                IconEntry::make('has_custom_domain')
                                    ->label('Custom Domain')
                                    ->boolean(),
                                
                                IconEntry::make('has_priority_support')
                                    ->label('Priority Support')
                                    ->boolean(),
                                
                                IconEntry::make('has_advanced_analytics')
                                    ->label('Advanced Analytics')
                                    ->boolean(),
                                
                                IconEntry::make('has_white_label')
                                    ->label('White Label')
                                    ->boolean(),
                            ]),
                    ]),

                Section::make('Plan Features List')
                    ->schema([
                        RepeatableEntry::make('features')
                            ->schema([
                                TextEntry::make('label')
                                    ->label('Feature')
                                    ->icon(fn ($record) => $record['icon'] ?? 'heroicon-o-check'),
                                
                                TextEntry::make('value')
                                    ->label('Value')
                                    ->placeholder('Included'),
                            ])
                            ->columns(2)
                            ->contained(false),
                    ])
                    ->collapsible(),

                Section::make('Usage Statistics')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('subscriptions_count')
                                    ->label('Total Subscriptions')
                                    ->state(fn ($record) => $record->subscriptions()->count())
                                    ->icon('heroicon-m-credit-card'),

                                TextEntry::make('active_subscriptions_count')
                                    ->label('Active Subscriptions')
                                    ->state(fn ($record) => $record->subscriptions()
                                        ->where('status', 'active')
                                        ->count())
                                    ->icon('heroicon-m-check-circle'),

                                TextEntry::make('total_revenue')
                                    ->label('Total Revenue')
                                    ->state(fn ($record) => $record->payments()->sum('total_amount'))
                                    ->money('USD')
                                    ->icon('heroicon-m-currency-dollar'),
                            ]),
                    ]),

                Section::make('Stripe Integration')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('stripe_product_id')
                                    ->label('Product ID')
                                    ->placeholder('Not connected')
                                    ->copyable(),
                                
                                TextEntry::make('stripe_price_id')
                                    ->label('Price ID')
                                    ->placeholder('Not connected')
                                    ->copyable(),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
