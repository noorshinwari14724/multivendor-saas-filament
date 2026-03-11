<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        // Assign default role if none selected
        if ($this->record->roles()->count() === 0) {
            $this->record->assignRole('user');
        }

        \App\Models\ActivityLog::log(
            "User '{$this->record->name}' was created",
            'user',
            'created',
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
