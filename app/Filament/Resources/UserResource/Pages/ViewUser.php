<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            
            Actions\Action::make('impersonate')
                ->label('Login As User')
                ->icon('heroicon-m-arrow-right-on-rectangle')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn () => !$this->record->is_super_admin)
                ->action(function () {
                    session()->put('impersonate', $this->record->id);
                    return redirect()->route('home');
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Profile')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                ImageEntry::make('avatar')
                                    ->circular()
                                    ->defaultImageUrl(fn ($record) => $record->avatar_url)
                                    ->size(100),
                                
                                Grid::make(1)
                                    ->schema([
                                        TextEntry::make('name')
                                            ->size(TextEntry\TextEntrySize::Large)
                                            ->weight('font-bold'),
                                        
                                        TextEntry::make('email')
                                            ->icon('heroicon-m-envelope'),
                                        
                                        TextEntry::make('status')
                                            ->badge()
                                            ->color(fn (string $state): string => match ($state) {
                                                'active' => 'success',
                                                'inactive' => 'warning',
                                                'suspended' => 'danger',
                                                default => 'gray',
                                            }),
                                    ])
                                    ->columnSpan(2),
                            ]),
                    ]),

                Section::make('Contact Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('phone')
                                    ->icon('heroicon-m-phone')
                                    ->placeholder('Not provided'),
                                
                                TextEntry::make('bio')
                                    ->columnSpanFull()
                                    ->placeholder('No bio provided'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Security & Verification')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                IconEntry::make('email_verified_at')
                                    ->label('Email Verified')
                                    ->boolean()
                                    ->getStateUsing(fn ($record) => $record->email_verified_at !== null),
                                
                                TextEntry::make('email_verified_at')
                                    ->label('Verified At')
                                    ->dateTime('F j, Y, g:i a')
                                    ->placeholder('Not verified'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Roles & Permissions')
                    ->schema([
                        TextEntry::make('roles.name')
                            ->badge()
                            ->color('primary')
                            ->placeholder('No roles assigned'),
                    ])
                    ->collapsible(),

                Section::make('Activity')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('last_login_at')
                                    ->label('Last Login')
                                    ->dateTime('F j, Y, g:i a')
                                    ->placeholder('Never logged in')
                                    ->icon('heroicon-m-clock'),
                                
                                TextEntry::make('last_login_ip')
                                    ->label('Last IP Address')
                                    ->placeholder('N/A')
                                    ->icon('heroicon-m-globe-alt'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Associations')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('ownedVendors_count')
                                    ->label('Owned Vendors')
                                    ->state(fn ($record) => $record->ownedVendors()->count())
                                    ->icon('heroicon-m-building-storefront'),
                                
                                TextEntry::make('vendors_count')
                                    ->label('Member Of')
                                    ->state(fn ($record) => $record->vendors()->count())
                                    ->icon('heroicon-m-users'),
                                
                                TextEntry::make('subscriptions_count')
                                    ->label('Subscriptions')
                                    ->state(fn ($record) => $record->subscriptions()->count())
                                    ->icon('heroicon-m-credit-card'),
                            ]),
                    ])
                    ->collapsible(),

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
