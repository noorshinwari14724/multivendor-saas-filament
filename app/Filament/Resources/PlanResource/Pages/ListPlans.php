<?php

namespace App\Filament\Resources\PlanResource\Pages;

use App\Filament\Resources\PlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

class ListPlans extends ListRecords
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Plans'),
            
            'active' => Tab::make('Active')
                ->modifyQueryUsing(fn ($query) => $query->where('is_active', true))
                ->badge(PlanResource::getModel()::where('is_active', true)->count())
                ->badgeColor('success'),
            
            'inactive' => Tab::make('Inactive')
                ->modifyQueryUsing(fn ($query) => $query->where('is_active', false))
                ->badge(PlanResource::getModel()::where('is_active', false)->count())
                ->badgeColor('danger'),
            
            'featured' => Tab::make('Featured')
                ->modifyQueryUsing(fn ($query) => $query->where('is_featured', true))
                ->badge(PlanResource::getModel()::where('is_featured', true)->count())
                ->badgeColor('warning'),
            
            'free' => Tab::make('Free Plans')
                ->modifyQueryUsing(fn ($query) => $query->where('price', 0))
                ->badge(PlanResource::getModel()::where('price', 0)->count())
                ->badgeColor('info'),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'all';
    }
}
