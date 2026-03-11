<?php

namespace App\Filament\Resources\PlanResource\Pages;

use App\Filament\Resources\PlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlan extends EditRecord
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            
            Actions\Action::make('setDefault')
                ->label('Set as Default')
                ->icon('heroicon-m-star')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn () => !$this->record->is_default)
                ->action(function () {
                    $this->record->setAsDefault();
                }),
        ];
    }

    protected function afterSave(): void
    {
        \App\Models\ActivityLog::log(
            "Plan '{$this->record->name}' was updated",
            'plan',
            'updated',
            $this->record,
            auth()->user(),
            $this->record->toArray()
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
