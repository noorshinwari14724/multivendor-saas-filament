<?php

namespace App\Filament\Resources\SettingResource\Pages;

use App\Filament\Resources\SettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

class ListSettings extends ListRecords
{
    protected static string $resource = SettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            
            Actions\Action::make('installDefaults')
                ->label('Install Default Settings')
                ->icon('heroicon-m-sparkles')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    \App\Models\Setting::installDefaults();
                    $this->notify('success', 'Default settings installed successfully!');
                })
                ->visible(fn () => SettingResource::getModel()::count() === 0),
            
            Actions\Action::make('clearCache')
                ->label('Clear Settings Cache')
                ->icon('heroicon-m-trash')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    \App\Models\Setting::clearCache();
                    $this->notify('success', 'Settings cache cleared!');
                }),
        ];
    }

    public function getTabs(): array
    {
        $groups = \App\Models\Setting::select('group')
            ->distinct()
            ->pluck('group')
            ->toArray();

        $tabs = [
            'all' => Tab::make('All Settings'),
        ];

        foreach ($groups as $group) {
            $tabs[$group] = Tab::make(ucfirst($group))
                ->modifyQueryUsing(fn ($query) => $query->where('group', $group))
                ->badge(SettingResource::getModel()::where('group', $group)->count());
        }

        return $tabs;
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'all';
    }
}
