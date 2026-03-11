<?php

namespace App\Filament\Resources\SubscriptionResource\Pages;

use App\Filament\Resources\SubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubscription extends EditRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            
            Actions\Action::make('cancel')
                ->label('Cancel Subscription')
                ->icon('heroicon-m-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->is_active && !$this->record->is_canceled)
                ->action(function () {
                    $this->record->cancel();
                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                }),

            Actions\Action::make('resume')
                ->label('Resume Subscription')
                ->icon('heroicon-m-play')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->cancel_at_period_end)
                ->action(function () {
                    $this->record->resume();
                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                }),
        ];
    }

    protected function afterSave(): void
    {
        \App\Models\ActivityLog::log(
            "Subscription for vendor '{$this->record->vendor?->name}' was updated",
            'subscription',
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
