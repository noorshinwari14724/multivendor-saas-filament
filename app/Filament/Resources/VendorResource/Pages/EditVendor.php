<?php

namespace App\Filament\Resources\VendorResource\Pages;

use App\Filament\Resources\VendorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVendor extends EditRecord
{
    protected static string $resource = VendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            
            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-m-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->isPending())
                ->action(function () {
                    $this->record->approve(auth()->user());
                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                }),

            Actions\Action::make('suspend')
                ->label('Suspend')
                ->icon('heroicon-m-no-symbol')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->isApproved())
                ->action(function () {
                    $this->record->suspend(auth()->user());
                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                }),
        ];
    }

    protected function afterSave(): void
    {
        // Log the update
        \App\Models\ActivityLog::log(
            "Vendor '{$this->record->name}' was updated",
            'vendor',
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
