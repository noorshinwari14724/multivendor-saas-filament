<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            
            Actions\Action::make('markPaid')
                ->label('Mark as Paid')
                ->icon('heroicon-m-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => in_array($this->record->status, ['pending', 'processing', 'failed']))
                ->action(function () {
                    $this->record->markAsPaid();
                }),

            Actions\Action::make('refund')
                ->label('Refund')
                ->icon('heroicon-m-arrow-uturn-left')
                ->color('warning')
                ->form([
                    \Filament\Forms\Components\TextInput::make('amount')
                        ->numeric()
                        ->prefix('$')
                        ->required()
                        ->default(fn () => $this->record->getRemainingRefundableAmount())
                        ->maxValue(fn () => $this->record->getRemainingRefundableAmount()),
                ])
                ->visible(fn () => $this->record->canBeRefunded())
                ->action(function (array $data) {
                    $this->record->refund($data['amount']);
                }),
        ];
    }

    protected function afterSave(): void
    {
        \App\Models\ActivityLog::log(
            "Payment {$this->record->payment_number} was updated",
            'payment',
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
