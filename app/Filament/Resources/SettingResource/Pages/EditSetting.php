<?php

namespace App\Filament\Resources\SettingResource\Pages;

use App\Filament\Resources\SettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSetting extends EditRecord
{
    protected static string $resource = SettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Handle different value types
        $data['value'] = match ($data['type']) {
            'boolean' => $data['value_boolean'] ?? '0',
            'integer' => (string) ($data['value_integer'] ?? 0),
            'float' => (string) ($data['value_float'] ?? 0),
            'json' => $data['value_json'] ?? '{}',
            'array' => json_encode($data['value_array'] ?? []),
            default => $data['value'] ?? '',
        };

        // Remove temporary fields
        unset(
            $data['value_boolean'],
            $data['value_integer'],
            $data['value_float'],
            $data['value_json'],
            $data['value_array']
        );

        return $data;
    }

    protected function afterSave(): void
    {
        // Clear cache after updating
        \App\Models\Setting::clearCache();

        \App\Models\ActivityLog::log(
            "Setting '{$this->record->group}.{$this->record->key}' was updated",
            'setting',
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
