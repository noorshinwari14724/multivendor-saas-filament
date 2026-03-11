<?php

namespace App\Filament\Resources\ActivityLogResource\Pages;

use App\Filament\Resources\ActivityLogResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Activity'),
            
            'vendors' => Tab::make('Vendors')
                ->modifyQueryUsing(fn ($query) => $query->where('log_name', 'vendor'))
                ->badge(ActivityLogResource::getModel()::where('log_name', 'vendor')->count()),
            
            'users' => Tab::make('Users')
                ->modifyQueryUsing(fn ($query) => $query->where('log_name', 'user'))
                ->badge(ActivityLogResource::getModel()::where('log_name', 'user')->count()),
            
            'subscriptions' => Tab::make('Subscriptions')
                ->modifyQueryUsing(fn ($query) => $query->where('log_name', 'subscription'))
                ->badge(ActivityLogResource::getModel()::where('log_name', 'subscription')->count()),
            
            'payments' => Tab::make('Payments')
                ->modifyQueryUsing(fn ($query) => $query->where('log_name', 'payment'))
                ->badge(ActivityLogResource::getModel()::where('log_name', 'payment')->count()),
            
            'plans' => Tab::make('Plans')
                ->modifyQueryUsing(fn ($query) => $query->where('log_name', 'plan'))
                ->badge(ActivityLogResource::getModel()::where('log_name', 'plan')->count()),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'all';
    }
}
