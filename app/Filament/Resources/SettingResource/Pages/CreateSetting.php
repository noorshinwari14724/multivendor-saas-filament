<?php

namespace App\Filament\Resources\SettingResource\Pages;

use App\Filament\Resources\SettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSetting extends CreateRecord
{
    protected static string $resource = SettingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
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

    protected function afterCreate(): void
    {
        // Clear cache after creating
        \App\Models\Setting::clearCache();

        \App\Models\ActivityLog::log(
            "Setting '{$this->record->group}.{$this->record->key}' was created",
            'setting',
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
