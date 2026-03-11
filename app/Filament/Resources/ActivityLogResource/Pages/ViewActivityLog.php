<?php

namespace App\Filament\Resources\ActivityLogResource\Pages;

use App\Filament\Resources\ActivityLogResource;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewActivityLog extends ViewRecord
{
    protected static string $resource = ActivityLogResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Activity Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('description')
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->weight('font-medium')
                                    ->columnSpanFull(),
                                
                                TextEntry::make('log_name')
                                    ->badge()
                                    ->color('primary'),
                                
                                TextEntry::make('event')
                                    ->badge()
                                    ->color('gray')
                                    ->placeholder('No event'),
                            ]),
                    ]),

                Section::make('Actor Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label('Performed By')
                                    ->placeholder('System')
                                    ->icon('heroicon-m-user'),
                                
                                TextEntry::make('vendor.name')
                                    ->label('Related Vendor')
                                    ->placeholder('N/A')
                                    ->icon('heroicon-m-building-storefront'),
                            ]),
                    ]),

                Section::make('Request Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('ip_address')
                                    ->label('IP Address')
                                    ->fontFamily('mono')
                                    ->icon('heroicon-m-globe-alt'),
                                
                                TextEntry::make('url')
                                    ->label('URL')
                                    ->url(fn ($state) => $state, true)
                                    ->placeholder('N/A'),
                                
                                TextEntry::make('method')
                                    ->label('HTTP Method')
                                    ->badge()
                                    ->color('gray')
                                    ->placeholder('N/A'),
                                
                                TextEntry::make('user_agent')
                                    ->label('User Agent')
                                    ->placeholder('N/A')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Properties')
                    ->schema([
                        KeyValueEntry::make('properties')
                            ->label('')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => !empty($record->properties))
                    ->collapsible(),

                Section::make('Subject Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('subject_type')
                                    ->label('Subject Type')
                                    ->placeholder('N/A'),
                                
                                TextEntry::make('subject_id')
                                    ->label('Subject ID')
                                    ->placeholder('N/A'),
                            ]),
                    ])
                    ->visible(fn ($record) => $record->subject_type !== null)
                    ->collapsible()
                    ->collapsed(),

                Section::make('Timestamps')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->dateTime('F j, Y, g:i:s a'),
                                
                                TextEntry::make('updated_at')
                                    ->dateTime('F j, Y, g:i:s a'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
