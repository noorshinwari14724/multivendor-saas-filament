<?php

namespace App\Filament\Resources\SubscriptionResource\Pages;

use App\Filament\Resources\SubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

class ListSubscriptions extends ListRecords
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Subscriptions'),
            
            'active' => Tab::make('Active')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'active'))
                ->badge(SubscriptionResource::getModel()::where('status', 'active')->count())
                ->badgeColor('success'),
            
            'trialing' => Tab::make('Trialing')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'trialing'))
                ->badge(SubscriptionResource::getModel()::where('status', 'trialing')->count())
                ->badgeColor('info'),
            
            'past_due' => Tab::make('Past Due')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'past_due'))
                ->badge(SubscriptionResource::getModel()::where('status', 'past_due')->count())
                ->badgeColor('warning'),
            
            'canceled' => Tab::make('Canceled')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'canceled'))
                ->badge(SubscriptionResource::getModel()::where('status', 'canceled')->count())
                ->badgeColor('danger'),
            
            'expiring_soon' => Tab::make('Expiring Soon')
                ->modifyQueryUsing(fn ($query) => $query->expiringSoon(7))
                ->badge(SubscriptionResource::getModel()::expiringSoon(7)->count())
                ->badgeColor('warning'),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'all';
    }
}
