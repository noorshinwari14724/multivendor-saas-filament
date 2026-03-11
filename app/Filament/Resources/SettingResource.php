<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'key';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Setting Information')
                    ->schema([
                        Forms\Components\Select::make('group')
                            ->options(Setting::getGroups())
                            ->required()
                            ->default('general')
                            ->searchable()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('group')
                                    ->required()
                                    ->unique('settings', 'group'),
                            ])
                            ->createOptionUsing(function (array $data): string {
                                return $data['group'];
                            }),

                        Forms\Components\TextInput::make('key')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('e.g., site_name, max_login_attempts'),

                        Forms\Components\Textarea::make('description')
                            ->rows(2)
                            ->placeholder('Explain what this setting does...'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Value Configuration')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->options([
                                'string' => 'String (Text)',
                                'integer' => 'Integer (Number)',
                                'float' => 'Float (Decimal)',
                                'boolean' => 'Boolean (Yes/No)',
                                'json' => 'JSON',
                                'array' => 'Array',
                            ])
                            ->required()
                            ->default('string')
                            ->live()
                            ->helperText('Determines how the value will be stored and displayed'),

                        // String value
                        Forms\Components\TextInput::make('value')
                            ->required()
                            ->maxLength(65535)
                            ->visible(fn (Forms\Get $get) => $get('type') === 'string')
                            ->formatStateUsing(function ($state) {
                                return is_string($state) ? $state : json_encode($state);
                            }),

                        // Integer value
                        Forms\Components\TextInput::make('value_integer')
                            ->label('Value')
                            ->required()
                            ->numeric()
                            ->integer()
                            ->visible(fn (Forms\Get $get) => $get('type') === 'integer')
                            ->dehydrateStateUsing(fn ($state) => (string) $state)
                            ->formatStateUsing(fn ($state) => (int) $state),

                        // Float value
                        Forms\Components\TextInput::make('value_float')
                            ->label('Value')
                            ->required()
                            ->numeric()
                            ->step(0.01)
                            ->visible(fn (Forms\Get $get) => $get('type') === 'float')
                            ->dehydrateStateUsing(fn ($state) => (string) $state)
                            ->formatStateUsing(fn ($state) => (float) $state),

                        // Boolean value
                        Forms\Components\Toggle::make('value_boolean')
                            ->label('Value')
                            ->required()
                            ->visible(fn (Forms\Get $get) => $get('type') === 'boolean')
                            ->dehydrateStateUsing(fn ($state) => $state ? '1' : '0')
                            ->formatStateUsing(fn ($state) => $state === '1' || $state === true),

                        // JSON value
                        Forms\Components\Textarea::make('value_json')
                            ->label('Value (JSON)')
                            ->required()
                            ->rows(5)
                            ->visible(fn (Forms\Get $get) => $get('type') === 'json')
                            ->helperText('Enter valid JSON')
                            ->dehydrateStateUsing(function ($state) {
                                return is_string($state) ? $state : json_encode($state);
                            })
                            ->formatStateUsing(function ($state) {
                                if (is_string($state)) {
                                    $decoded = json_decode($state, true);
                                    return json_encode($decoded, JSON_PRETTY_PRINT);
                                }
                                return json_encode($state, JSON_PRETTY_PRINT);
                            }),

                        // Array value
                        Forms\Components\KeyValue::make('value_array')
                            ->label('Value (Key-Value Pairs)')
                            ->required()
                            ->visible(fn (Forms\Get $get) => $get('type') === 'array')
                            ->dehydrateStateUsing(fn ($state) => json_encode($state))
                            ->formatStateUsing(function ($state) {
                                if (is_string($state)) {
                                    return json_decode($state, true) ?? [];
                                }
                                return $state;
                            }),
                    ]),

                Forms\Components\Section::make('Visibility')
                    ->schema([
                        Forms\Components\Toggle::make('is_public')
                            ->label('Public Setting')
                            ->helperText('Public settings can be accessed via API without authentication')
                            ->default(false),
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('group')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('key')
                    ->searchable()
                    ->sortable()
                    ->weight('font-medium')
                    ->copyable(),

                Tables\Columns\TextColumn::make('value')
                    ->searchable()
                    ->limit(50)
                    ->formatStateUsing(function ($state, $record) {
                        return match ($record->type) {
                            'boolean' => $record->castValue() ? 'Yes' : 'No',
                            'json', 'array' => json_encode($record->castValue()),
                            default => $state,
                        };
                    }),

                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'string',
                        'success' => 'integer',
                        'warning' => 'float',
                        'info' => 'boolean',
                        'danger' => 'json',
                        'secondary' => 'array',
                    ])
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_public')
                    ->label('Public')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('description')
                    ->limit(40)
                    ->toggleable()
                    ->placeholder('No description'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('group')
            ->groups([
                'group',
                'type',
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->options(Setting::getGroups())
                    ->searchable()
                    ->multiple(),

                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'string' => 'String',
                        'integer' => 'Integer',
                        'float' => 'Float',
                        'boolean' => 'Boolean',
                        'json' => 'JSON',
                        'array' => 'Array',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_public')
                    ->label('Public Only'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('quickEdit')
                        ->label('Quick Edit')
                        ->icon('heroicon-m-pencil-square')
                        ->color('warning')
                        ->form(function ($record) {
                            return match ($record->type) {
                                'boolean' => [
                                    Forms\Components\Toggle::make('value')
                                        ->label($record->key)
                                        ->default($record->castValue()),
                                ],
                                'integer' => [
                                    Forms\Components\TextInput::make('value')
                                        ->label($record->key)
                                        ->numeric()
                                        ->integer()
                                        ->default($record->castValue()),
                                ],
                                'float' => [
                                    Forms\Components\TextInput::make('value')
                                        ->label($record->key)
                                        ->numeric()
                                        ->step(0.01)
                                        ->default($record->castValue()),
                                ],
                                'json', 'array' => [
                                    Forms\Components\Textarea::make('value')
                                        ->label($record->key)
                                        ->rows(5)
                                        ->default(json_encode($record->castValue(), JSON_PRETTY_PRINT)),
                                ],
                                default => [
                                    Forms\Components\TextInput::make('value')
                                        ->label($record->key)
                                        ->default($record->value),
                                ],
                            };
                        })
                        ->action(function ($record, array $data) {
                            $value = match ($record->type) {
                                'boolean' => $data['value'] ? '1' : '0',
                                'json', 'array' => $data['value'],
                                default => (string) $data['value'],
                            };
                            
                            $record->update(['value' => $value]);
                            
                            // Clear cache
                            Setting::clearCache();
                        }),

                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('changeGroup')
                        ->label('Change Group')
                        ->icon('heroicon-m-folder')
                        ->form([
                            Forms\Components\Select::make('group')
                                ->options(Setting::getGroups())
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            foreach ($records as $record) {
                                $record->update(['group' => $data['group']]);
                            }
                            Setting::clearCache();
                        }),
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
            'index' => Pages\ListSettings::route('/'),
            'create' => Pages\CreateSetting::route('/create'),
            'view' => Pages\ViewSetting::route('/{record}'),
            'edit' => Pages\EditSetting::route('/{record}/edit'),
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return "{$record->group}.{$record->key}";
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Value' => str_limit($record->value, 50),
            'Type' => ucfirst($record->type),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count() ?: null;
    }
}
