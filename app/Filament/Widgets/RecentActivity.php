<?php

namespace App\Filament\Widgets;

use App\Models\ActivityLog;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentActivity extends BaseWidget
{
    protected static ?string $heading = 'Recent Activity';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ActivityLog::query()
                    ->with(['user', 'vendor'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->limit(60)
                    ->wrap(),

                Tables\Columns\BadgeColumn::make('log_name')
                    ->colors([
                        'primary' => 'vendor',
                        'success' => 'user',
                        'warning' => 'subscription',
                        'danger' => 'payment',
                        'info' => 'plan',
                        'secondary' => 'default',
                    ]),

                Tables\Columns\TextColumn::make('event')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('user.name')
                    ->placeholder('System')
                    ->icon('heroicon-m-user'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => route('filament.admin.resources.activity-logs.view', $record)),
            ])
            ->paginated(false);
    }
}
