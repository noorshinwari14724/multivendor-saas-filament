<?php

namespace App\Filament\Resources\VendorResource\Pages;

use App\Filament\Resources\VendorResource;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewVendor extends ViewRecord
{
    protected static string $resource = VendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            
            Actions\Action::make('loginAs')
                ->label('Login as Vendor')
                ->icon('heroicon-m-arrow-right-on-rectangle')
                ->color('gray')
                ->url(fn () => route('admin.vendor.login-as', $this->record))
                ->openUrlInNewTab(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Vendor Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                ImageEntry::make('logo')
                                    ->circular()
                                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&background=random&size=128'),
                                
                                Grid::make(1)
                                    ->schema([
                                        TextEntry::make('name')
                                            ->size(TextEntry\TextEntrySize::Large)
                                            ->weight('font-bold'),
                                        
                                        TextEntry::make('slug')
                                            ->icon('heroicon-m-link')
                                            ->color('gray'),
                                        
                                        TextEntry::make('status')
                                            ->badge()
                                            ->color(fn (string $state): string => match ($state) {
                                                'pending' => 'warning',
                                                'approved' => 'success',
                                                'rejected', 'suspended' => 'danger',
                                                default => 'gray',
                                            }),
                                    ])
                                    ->columnSpan(2),
                            ]),
                        
                        TextEntry::make('description')
                            ->columnSpanFull()
                            ->placeholder('No description provided'),
                    ]),

                Section::make('Contact Details')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('email')
                                    ->icon('heroicon-m-envelope'),
                                
                                TextEntry::make('phone')
                                    ->icon('heroicon-m-phone'),
                                
                                TextEntry::make('website')
                                    ->icon('heroicon-m-globe-alt')
                                    ->url(fn ($state) => $state, true)
                                    ->placeholder('Not set'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Address')
                    ->schema([
                        TextEntry::make('full_address')
                            ->label('Full Address')
                            ->icon('heroicon-m-map-pin')
                            ->placeholder('No address provided'),
                    ])
                    ->collapsible(),

                Section::make('Business Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('business_type')
                                    ->placeholder('Not set'),
                                
                                TextEntry::make('tax_id')
                                    ->label('Tax ID / VAT')
                                    ->placeholder('Not set'),
                                
                                TextEntry::make('registration_number')
                                    ->placeholder('Not set'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Owner & Management')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('owner.name')
                                    ->label('Owner')
                                    ->icon('heroicon-m-user'),
                                
                                TextEntry::make('approved_by')
                                    ->label('Approved By')
                                    ->formatStateUsing(fn ($state, $record) => $record->approver?->name ?? 'N/A')
                                    ->icon('heroicon-m-check-badge'),
                                
                                TextEntry::make('approved_at')
                                    ->dateTime('F j, Y, g:i a')
                                    ->placeholder('Not approved yet'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Subscription & Billing')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                IconEntry::make('is_trial')
                                    ->label('Trial Status')
                                    ->boolean(),
                                
                                TextEntry::make('trial_ends_at')
                                    ->label('Trial Ends')
                                    ->dateTime('F j, Y')
                                    ->placeholder('Not on trial'),
                                
                                TextEntry::make('activeSubscription.plan.name')
                                    ->label('Current Plan')
                                    ->placeholder('No active subscription')
                                    ->formatStateUsing(fn ($state, $record) => $record->activeSubscription?->plan?->name ?? 'No active subscription'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Statistics')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('total_users')
                                    ->label('Total Users')
                                    ->icon('heroicon-m-users')
                                    ->numeric(),
                                
                                TextEntry::make('total_products')
                                    ->label('Products')
                                    ->icon('heroicon-m-cube')
                                    ->numeric(),
                                
                                TextEntry::make('total_orders')
                                    ->label('Orders')
                                    ->icon('heroicon-m-shopping-cart')
                                    ->numeric(),
                                
                                TextEntry::make('total_revenue')
                                    ->label('Revenue')
                                    ->icon('heroicon-m-currency-dollar')
                                    ->money('USD'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Custom Domain')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('custom_domain')
                                    ->placeholder('Not set')
                                    ->icon('heroicon-m-globe-alt'),
                                
                                IconEntry::make('custom_domain_verified')
                                    ->label('Verified')
                                    ->boolean(),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Timestamps')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->dateTime('F j, Y, g:i a'),
                                
                                TextEntry::make('updated_at')
                                    ->dateTime('F j, Y, g:i a'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
