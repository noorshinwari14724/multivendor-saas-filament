<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Subscription Management';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Plan Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Starter, Professional, Enterprise')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', \Illuminate\Support\Str::slug($state))),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->disabledOn('edit'),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->placeholder('Brief description of this plan')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Pricing')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->minValue(0)
                            ->step(0.01),

                        Forms\Components\Select::make('currency')
                            ->options([
                                'USD' => 'USD - US Dollar',
                                'EUR' => 'EUR - Euro',
                                'GBP' => 'GBP - British Pound',
                                'JPY' => 'JPY - Japanese Yen',
                                'CAD' => 'CAD - Canadian Dollar',
                                'AUD' => 'AUD - Australian Dollar',
                            ])
                            ->default('USD')
                            ->required(),

                        Forms\Components\Select::make('billing_cycle')
                            ->options([
                                'monthly' => 'Monthly',
                                'yearly' => 'Yearly',
                                'lifetime' => 'Lifetime',
                            ])
                            ->default('monthly')
                            ->required(),

                        Forms\Components\TextInput::make('trial_days')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->suffix('days')
                            ->helperText('Number of trial days (0 for no trial)'),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Plan Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive plans are not available for purchase'),

                        Forms\Components\Toggle::make('is_featured')
                            ->label('Featured')
                            ->helperText('Featured plans are highlighted on the pricing page'),

                        Forms\Components\Toggle::make('is_default')
                            ->label('Default Plan')
                            ->helperText('New vendors will be assigned this plan by default'),

                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Order in which plans are displayed'),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Resource Limits')
                    ->description('Set limits for resources available in this plan. Use -1 for unlimited.')
                    ->schema([
                        Forms\Components\TextInput::make('max_vendors')
                            ->numeric()
                            ->default(1)
                            ->minValue(-1)
                            ->helperText('Max vendors per account (-1 for unlimited)'),

                        Forms\Components\TextInput::make('max_users_per_vendor')
                            ->label('Max Team Members')
                            ->numeric()
                            ->default(5)
                            ->minValue(-1)
                            ->helperText('Max team members per vendor (-1 for unlimited)'),

                        Forms\Components\TextInput::make('max_products')
                            ->numeric()
                            ->default(100)
                            ->minValue(-1)
                            ->helperText('Max products per vendor (-1 for unlimited)'),

                        Forms\Components\TextInput::make('max_storage_mb')
                            ->label('Storage Limit (MB)')
                            ->numeric()
                            ->default(1024)
                            ->minValue(-1)
                            ->suffix('MB')
                            ->helperText('Storage limit in MB (-1 for unlimited)'),

                        Forms\Components\TextInput::make('max_api_calls_per_day')
                            ->label('API Calls per Day')
                            ->numeric()
                            ->default(1000)
                            ->minValue(-1)
                            ->helperText('Daily API call limit (-1 for unlimited)'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Features & Add-ons')
                    ->schema([
                        Forms\Components\Toggle::make('has_custom_domain')
                            ->label('Custom Domain Support'),

                        Forms\Components\Toggle::make('has_priority_support')
                            ->label('Priority Support'),

                        Forms\Components\Toggle::make('has_advanced_analytics')
                            ->label('Advanced Analytics'),

                        Forms\Components\Toggle::make('has_white_label')
                            ->label('White Label Option'),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Plan Features List')
                    ->description('Add features that will be displayed on the pricing page')
                    ->schema([
                        Forms\Components\Repeater::make('features')
                            ->schema([
                                Forms\Components\TextInput::make('icon')
                                    ->placeholder('heroicon-o-check')
                                    ->helperText('Heroicon name'),
                                
                                Forms\Components\TextInput::make('label')
                                    ->required()
                                    ->placeholder('Feature name'),
                                
                                Forms\Components\TextInput::make('value')
                                    ->placeholder('Feature value or description'),
                            ])
                            ->columns(3)
                            ->addActionLabel('Add Feature')
                            ->reorderable()
                            ->collapsible(),
                    ]),

                Forms\Components\Section::make('Stripe Integration')
                    ->description('Connect this plan to Stripe for automated billing')
                    ->schema([
                        Forms\Components\TextInput::make('stripe_product_id')
                            ->label('Stripe Product ID')
                            ->placeholder('prod_xxxxxxxxxx')
                            ->helperText('The Stripe Product ID for this plan'),

                        Forms\Components\TextInput::make('stripe_price_id')
                            ->label('Stripe Price ID')
                            ->placeholder('price_xxxxxxxxxx')
                            ->helperText('The Stripe Price ID for this plan'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Metadata')
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
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
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('font-medium'),

                Tables\Columns\TextColumn::make('price')
                    ->money(fn ($record) => $record->currency)
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('billing_cycle')
                    ->colors([
                        'primary' => 'monthly',
                        'success' => 'yearly',
                        'warning' => 'lifetime',
                    ]),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),

                Tables\Columns\TextColumn::make('trial_days')
                    ->numeric()
                    ->suffix(' days')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('active_subscriptions_count')
                    ->label('Active Subs')
                    ->counts('activeSubscriptions')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('billing_cycle')
                    ->options([
                        'monthly' => 'Monthly',
                        'yearly' => 'Yearly',
                        'lifetime' => 'Lifetime',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured Status'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('setDefault')
                        ->label('Set as Default')
                        ->icon('heroicon-m-star')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->visible(fn (Plan $record) => !$record->is_default)
                        ->action(function (Plan $record) {
                            $record->setAsDefault();
                        }),

                    Tables\Actions\Action::make('duplicate')
                        ->label('Duplicate')
                        ->icon('heroicon-m-document-duplicate')
                        ->color('gray')
                        ->action(function (Plan $record) {

                            $newPlan = $record->replicate([
                                'active_subscriptions_count',
                                'subscriptions_count',
                            ]);

                            $newPlan->name = $record->name . ' (Copy)';
                            $newPlan->slug = $record->slug . '-copy';
                            $newPlan->is_default = false;
                            $newPlan->stripe_price_id = null;
                            $newPlan->stripe_product_id = null;

                            $newPlan->save();
                        }),

                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-m-check')
                        ->color('success')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            foreach ($records as $record) {
                                $record->activate();
                            }
                        }),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-m-x-mark')
                        ->color('warning')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            foreach ($records as $record) {
                                $record->deactivate();
                            }
                        }),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Add relations here if needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'view' => Pages\ViewPlan::route('/{record}'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Price' => $record->formatted_price,
            'Billing' => ucfirst($record->billing_cycle),
        ];
    }
}
