<?php

namespace App\Filament\Resources\SettingResource\Pages;

use App\Filament\Resources\SettingResource;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewSetting extends ViewRecord
{
    protected static string $resource = SettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            
            Actions\Action::make('quickToggle')
                ->label(fn () => $this->record->type === 'boolean' ? 'Toggle Value' : 'Quick Edit')
                ->icon('heroicon-m-bolt')
                ->color('warning')
                ->visible(fn () => $this->record->type === 'boolean')
                ->action(function () {
                    $newValue = $this->record->value === '1' ? '0' : '1';
                    $this->record->update(['value' => $newValue]);
                    \App\Models\Setting::clearCache();
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Setting Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('group')
                                    ->badge()
                                    ->color('primary')
                                    ->size(TextEntry\TextEntrySize::Large),
                                
                                TextEntry::make('key')
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->weight('font-bold')
                                    ->copyable(),
                                
                                TextEntry::make('type')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'string' => 'primary',
                                        'integer' => 'success',
                                        'float' => 'warning',
                                        'boolean' => 'info',
                                        'json' => 'danger',
                                        'array' => 'secondary',
                                        default => 'gray',
                                    }),
                            ]),
                        
                        TextEntry::make('description')
                            ->columnSpanFull()
                            ->placeholder('No description provided'),
                    ]),

                Section::make('Current Value')
                    ->schema([
                        TextEntry::make('display_value')
                            ->label('')
                            ->state(function ($record) {
                                $value = $record->castValue();
                                return match ($record->type) {
                                    'boolean' => $value ? '✅ Enabled (True)' : '❌ Disabled (False)',
                                    'json', 'array' => json_encode($value, JSON_PRETTY_PRINT),
                                    default => (string) $value,
                                };
                            })
                            ->formatStateUsing(function ($state, $record) {
                                if (in_array($record->type, ['json', 'array'])) {
                                    return $state;
                                }
                                return $state;
                            })
                            ->columnSpanFull()
                            ->extraAttributes(function ($record) {
                                if (in_array($record->type, ['json', 'array'])) {
                                    return ['class' => 'font-mono bg-gray-50 p-4 rounded-lg whitespace-pre-wrap'];
                                }
                                return ['class' => 'text-lg'];
                            }),
                    ]),

                Section::make('Raw Data')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('value')
                                    ->label('Stored Value')
                                    ->fontFamily('mono')
                                    ->copyable(),
                                
                                IconEntry::make('is_public')
                                    ->label('Public Access')
                                    ->boolean(),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Usage Information')
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
