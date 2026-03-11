<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        
        // Calculate total amount
        $data['total_amount'] = ($data['amount'] ?? 0) + ($data['tax_amount'] ?? 0) - ($data['discount_amount'] ?? 0);
        
        return $data;
    }

    protected function afterCreate(): void
    {
        \App\Models\ActivityLog::log(
            "Payment {$this->record->payment_number} was created",
            'payment',
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
