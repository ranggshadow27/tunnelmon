<?php

namespace App\Filament\Resources\PingResultResource\Pages;

use App\Filament\Resources\PingResultResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Actions\ActionGroup;
use Illuminate\Support\Carbon;

class ManagePingResults extends ManageRecords
{
    protected static string $resource = PingResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),

            ActionGroup::make([
                ExportAction::make('csv')
                    ->icon('phosphor-file-csv-duotone')
                    ->label("Export to CSV")
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withFilename('CBOSS Ticket Export_' . date('ymd'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::CSV)
                            ->withColumns([
                                Column::make('message')
                                    ->heading('Message'),

                                Column::make('created_at')
                                    ->heading('Timestamp'),
                            ])
                    ]),

                ExportAction::make('xlsx')
                    ->icon('phosphor-file-xls-duotone')
                    ->label("Export to XLSX")
                    ->exports([
                        ExcelExport::make('ping_result')
                            ->fromTable()
                            ->withFilename('Ping Result Data Export_' . date('ymd'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                            ->withColumns([
                                Column::make('message')
                                    ->heading('Message'),

                                Column::make('created_at')
                                    ->heading('Timestamp'),
                            ])
                    ]),
            ])
                ->icon('heroicon-m-arrow-down-tray')
                ->label("Export Data")
                ->tooltip("Export Data"),
        ];
    }
}
