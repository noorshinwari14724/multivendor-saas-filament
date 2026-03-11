<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Payments'),
            
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'pending'))
                ->badge(PaymentResource::getModel()::where('status', 'pending')->count())
                ->badgeColor('warning'),
            
            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'completed'))
                ->badge(PaymentResource::getModel()::where('status', 'completed')->count())
                ->badgeColor('success'),
            
            'failed' => Tab::make('Failed')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'failed'))
                ->badge(PaymentResource::getModel()::where('status', 'failed')->count())
                ->badgeColor('danger'),
            
            'refunded' => Tab::make('Refunded')
                ->modifyQueryUsing(fn ($query) => $query->whereIn('status', ['refunded', 'partially_refunded']))
                ->badge(PaymentResource::getModel()::whereIn('status', ['refunded', 'partially_refunded'])->count())
                ->badgeColor('secondary'),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'all';
    }
}
