<?php

namespace App\Filament\Resources\DeviceDetailResource\Pages;

use App\Filament\Resources\DeviceDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageDeviceDetails extends ManageRecords
{
    protected static string $resource = DeviceDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
