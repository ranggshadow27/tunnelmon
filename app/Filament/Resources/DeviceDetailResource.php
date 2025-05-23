<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeviceDetailResource\Pages;
use App\Filament\Resources\DeviceDetailResource\RelationManagers;
use App\Models\DeviceDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DeviceDetailResource extends Resource
{
    protected static ?string $model = DeviceDetail::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('device_name')
                    ->label("Device Name")
                    ->required()
                    ->maxLength(100),
                Forms\Components\Select::make('device_type')
                    ->options([
                        'modem' => 'Modem',
                        'router' => 'Router',
                        'access-point' => 'Access Point',
                        'other' => 'Other',
                    ])
                    ->native(false),
                Forms\Components\TextInput::make('ip_address')
                    ->label("IP Address")
                    ->required()
                    ->maxLength(50),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('device_name')
                    ->label("Device Name")
                    ->copyable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('device_type')
                    ->label("Device Type")
                    ->badge()
                    ->searchable(),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label("IP Address")
                    ->copyable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('device_type')
                    ->label("Device Name")
                    ->native(false)
                    ->options(fn() => DeviceDetail::query()->pluck('device_type', 'device_type')),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label("Add new Device")
                    ->icon('phosphor-plus-circle-duotone'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageDeviceDetails::route('/'),
        ];
    }
}
