<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Users'),
            
            'active' => Tab::make('Active')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'active'))
                ->badge(UserResource::getModel()::where('status', 'active')->count())
                ->badgeColor('success'),
            
            'suspended' => Tab::make('Suspended')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'suspended'))
                ->badge(UserResource::getModel()::where('status', 'suspended')->count())
                ->badgeColor('danger'),
            
            'unverified' => Tab::make('Unverified')
                ->modifyQueryUsing(fn ($query) => $query->whereNull('email_verified_at'))
                ->badge(UserResource::getModel()::whereNull('email_verified_at')->count())
                ->badgeColor('warning'),
            
            'recent' => Tab::make('Recently Active')
                ->modifyQueryUsing(fn ($query) => $query->recentlyActive(7))
                ->badge(UserResource::getModel()::recentlyActive(7)->count())
                ->badgeColor('info'),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'all';
    }
}
