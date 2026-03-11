<?php

namespace App\Filament\Resources\VendorResource\Pages;

use App\Filament\Resources\VendorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

class ListVendors extends ListRecords
{
    protected static string $resource = VendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Vendors'),
            
            'pending' => Tab::make('Pending Approval')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'pending'))
                ->badge(VendorResource::getModel()::where('status', 'pending')->count())
                ->badgeColor('warning'),
            
            'approved' => Tab::make('Approved')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'approved'))
                ->badge(VendorResource::getModel()::where('status', 'approved')->count())
                ->badgeColor('success'),
            
            'suspended' => Tab::make('Suspended')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'suspended'))
                ->badge(VendorResource::getModel()::where('status', 'suspended')->count())
                ->badgeColor('danger'),
            
            'trial' => Tab::make('On Trial')
                ->modifyQueryUsing(fn ($query) => $query->where('is_trial', true))
                ->badge(VendorResource::getModel()::where('is_trial', true)->count())
                ->badgeColor('info'),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'all';
    }
}
