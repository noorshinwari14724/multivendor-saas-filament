<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            
            Actions\Action::make('impersonate')
                ->label('Login As')
                ->icon('heroicon-m-arrow-right-on-rectangle')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn () => !$this->record->is_super_admin)
                ->action(function () {
                    // Implement impersonation logic
                    session()->put('impersonate', $this->record->id);
                    return redirect()->route('home');
                }),
        ];
    }

    protected function afterSave(): void
    {
        \App\Models\ActivityLog::log(
            "User '{$this->record->name}' was updated",
            'user',
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
