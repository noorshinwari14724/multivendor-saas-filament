<?php

namespace App\Filament\Resources\VendorResource\Pages;

use App\Filament\Resources\VendorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVendor extends CreateRecord
{
    protected static string $resource = VendorResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default trial if enabled
        if (\App\Models\Setting::get('enable_trial', true, 'features')) {
            $trialDays = \App\Models\Setting::get('trial_days', 14, 'features');
            $data['is_trial'] = true;
            $data['trial_ends_at'] = now()->addDays($trialDays);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Log the creation
        \App\Models\ActivityLog::log(
            "Vendor '{$this->record->name}' was created",
            'vendor',
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
