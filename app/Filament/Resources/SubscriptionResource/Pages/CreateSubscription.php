<?php

namespace App\Filament\Resources\SubscriptionResource\Pages;

use App\Filament\Resources\SubscriptionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSubscription extends CreateRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default dates based on billing cycle
        if (empty($data['current_period_start'])) {
            $data['current_period_start'] = now();
        }

        if (empty($data['current_period_end']) && !empty($data['billing_cycle'])) {
            $data['current_period_end'] = match ($data['billing_cycle']) {
                'monthly' => now()->addMonth(),
                'yearly' => now()->addYear(),
                'lifetime' => null,
                default => now()->addMonth(),
            };
        }

        // Set trial end date if applicable
        if (!empty($data['plan_id'])) {
            $plan = \App\Models\Plan::find($data['plan_id']);
            if ($plan && $plan->trial_days > 0 && empty($data['trial_ends_at'])) {
                $data['trial_ends_at'] = now()->addDays($plan->trial_days);
                $data['status'] = 'trialing';
            }
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        \App\Models\ActivityLog::log(
            "Subscription created for vendor '{$this->record->vendor?->name}'",
            'subscription',
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
