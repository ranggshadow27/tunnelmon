<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use App\Filament\Widgets\PingStatusChart;
use App\Models\DeviceDetail;

class DevicePingDashboard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.pages.device-ping-dashboard';

    public $deviceName = 'scada-pulau-geranting'; // Default value

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('deviceName')
                ->label('Device Name')
                ->native(false)
                ->searchable()
                ->preload()
                ->options(
                    DeviceDetail::query()
                        ->pluck('device_name', 'device_name')
                        ->toArray()
                )
                ->default('scada-pulau-geranting')
                ->reactive()
                ->live()
                ->afterStateUpdated(function ($state,) {
                    \Illuminate\Support\Facades\Log::info('Event dispatched: ' . $state);

                    $this->deviceName = $state;
                    $this->dispatch('device-name-updated', ['deviceName' => $state]);
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PingStatusChart::class,
        ];
    }

    public function mount(): void
    {
        $this->form->fill(['deviceName' => $this->deviceName]);
    }
}
