<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';
    protected static ?string $navigationGroup = 'Subscription Management';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Subscription Details')
                    ->schema([
                        Forms\Components\Select::make('vendor_id')
                            ->relationship('vendor', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('plan_id')
                            ->relationship('plan', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Subscribed By'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Status & Dates')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'trialing' => 'Trialing',
                                'past_due' => 'Past Due',
                                'canceled' => 'Canceled',
                                'unpaid' => 'Unpaid',
                                'incomplete' => 'Incomplete',
                                'incomplete_expired' => 'Incomplete Expired',
                            ])
                            ->required()
                            ->default('active'),

                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Start Date')
                            ->default(now()),

                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('End Date'),

                        Forms\Components\DateTimePicker::make('trial_ends_at')
                            ->label('Trial Ends At'),

                        Forms\Components\DateTimePicker::make('canceled_at')
                            ->label('Canceled At'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Billing Cycle')
                    ->schema([
                        Forms\Components\Select::make('billing_cycle')
                            ->options([
                                'monthly' => 'Monthly',
                                'yearly' => 'Yearly',
                                'lifetime' => 'Lifetime',
                            ])
                            ->required()
                            ->default('monthly'),

                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->minValue(0)
                            ->step(0.01),

                        Forms\Components\Select::make('currency')
                            ->options([
                                'USD' => 'USD',
                                'EUR' => 'EUR',
                                'GBP' => 'GBP',
                            ])
                            ->default('USD'),

                        Forms\Components\TextInput::make('quantity')
                            ->numeric()
                            ->default(1)
                            ->minValue(1),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Current Period')
                    ->schema([
                        Forms\Components\DateTimePicker::make('current_period_start')
                            ->label('Period Start'),

                        Forms\Components\DateTimePicker::make('current_period_end')
                            ->label('Period End'),

                        Forms\Components\Toggle::make('cancel_at_period_end')
                            ->label('Cancel at Period End'),

                        Forms\Components\DateTimePicker::make('cancel_at')
                            ->label('Cancel At'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Stripe Integration')
                    ->schema([
                        Forms\Components\TextInput::make('stripe_id')
                            ->label('Stripe Subscription ID')
                            ->placeholder('sub_xxxxxxxxxx'),

                        Forms\Components\TextInput::make('stripe_status')
                            ->label('Stripe Status'),

                        Forms\Components\TextInput::make('stripe_price')
                            ->label('Stripe Price ID')
                            ->placeholder('price_xxxxxxxxxx'),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Metadata')
                    ->schema([
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
                Tables\Columns\TextColumn::make('vendor.name')
                    ->searchable()
                    ->sortable()
                    ->weight('font-medium'),

                Tables\Columns\TextColumn::make('plan.name')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => ['active', 'trialing'],
                        'warning' => ['past_due', 'incomplete'],
                        'danger' => ['canceled', 'unpaid', 'incomplete_expired'],
                    ])
                    ->icons([
                        'heroicon-m-check-circle' => ['active', 'trialing'],
                        'heroicon-m-exclamation-triangle' => ['past_due', 'incomplete'],
                        'heroicon-m-x-circle' => ['canceled', 'unpaid', 'incomplete_expired'],
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->money(fn ($record) => $record->currency)
                    ->sortable(),

                Tables\Columns\TextColumn::make('billing_cycle')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\IconColumn::make('is_trial')
                    ->label('Trial')
                    ->boolean(),

                Tables\Columns\TextColumn::make('current_period_end')
                    ->label('Renews')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'trialing' => 'Trialing',
                        'past_due' => 'Past Due',
                        'canceled' => 'Canceled',
                        'unpaid' => 'Unpaid',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('plan_id')
                    ->relationship('plan', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('cancel_at_period_end')
                    ->label('Canceling at Period End'),

                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Expiring Soon (7 days)')
                    ->query(fn (Builder $query): Builder => $query->expiringSoon(7)),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('cancel')
                        ->label('Cancel')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalDescription('Are you sure you want to cancel this subscription?')
                        ->visible(fn (Subscription $record) => $record->is_active && !$record->is_canceled)
                        ->action(function (Subscription $record) {
                            $record->cancel();
                        }),

                    Tables\Actions\Action::make('resume')
                        ->label('Resume')
                        ->icon('heroicon-m-play')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Subscription $record) => $record->cancel_at_period_end)
                        ->action(function (Subscription $record) {
                            $record->resume();
                        }),

                    Tables\Actions\Action::make('renew')
                        ->label('Renew')
                        ->icon('heroicon-m-arrow-path')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->visible(fn (Subscription $record) => $record->hasExpired())
                        ->action(function (Subscription $record) {
                            $record->renew();
                        }),

                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('cancel')
                        ->label('Cancel Selected')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            foreach ($records as $record) {
                                if ($record->is_active && !$record->is_canceled) {
                                    $record->cancel();
                                }
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
            // Add relations here
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'view' => Pages\ViewSubscription::route('/{record}'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->vendor?->name . ' - ' . $record->plan?->name;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereIn('status', ['active', 'trialing'])->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
