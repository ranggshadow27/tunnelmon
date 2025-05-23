<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PingResultResource\Pages;
use App\Filament\Resources\PingResultResource\RelationManagers;
use App\Models\DeviceDetail;
use App\Models\PingResult;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PingResultResource extends Resource
{
    protected static ?string $model = PingResult::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('ip_address')
                    ->label("IP Address")
                    ->required()
                    ->maxLength(50),

                Forms\Components\TextInput::make('status')
                    ->required()
                    ->numeric(),

                Forms\Components\TextInput::make('packet_loss')
                    ->required()
                    ->numeric(),

                Forms\Components\Textarea::make('message')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('deviceDetail.device_name')
                    ->label("Device Name")
                    ->searchable(),

                Tables\Columns\TextColumn::make('deviceDetail.device_type')
                    ->label("Device Type")
                    ->badge()
                    ->color("secondary")
                    ->searchable(),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label("IP Address")
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label("Status")
                    ->formatStateUsing(fn($state) => $state === 0 ? "Down" : "Up")
                    ->badge()
                    ->color(fn($state) => $state === 0 ? "danger" : "success")
                    ->sortable(),

                Tables\Columns\TextColumn::make('packet_loss')
                    ->label("Messages")
                    ->formatStateUsing(fn($state) => "Packet Loss : " . $state . " %")
                    ->description(fn(PingResult $record): string => $record->message)
                    ->sortable(),

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
                Tables\Filters\SelectFilter::make('status')
                    ->label("Status")
                    ->native(false)
                    ->options(fn() => PingResult::query()->pluck('status', 'status')),

                Tables\Filters\SelectFilter::make('device_name')
                    ->label('Device Name')
                    ->preload()
                    ->searchable()
                    ->native(false)
                    ->options(
                        DeviceDetail::select('device_name')
                            ->distinct()
                            ->pluck('device_name')
                            ->mapWithKeys(function ($name) {
                                return [$name => $name];
                            })
                            ->toArray()
                    )
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereIn('ip_address', DeviceDetail::where('device_name', $data['value'])
                                ->pluck('ip_address')
                                ->toArray());
                        }
                    }),

                Tables\Filters\SelectFilter::make('device_type')
                    ->label('Device Type')
                    ->preload()
                    ->searchable()
                    ->native(false)
                    ->options(
                        DeviceDetail::select('device_type')
                            ->distinct()
                            ->pluck('device_type')
                            ->mapWithKeys(function ($name) {
                                return [$name => $name];
                            })
                            ->toArray()
                    )
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereIn('ip_address', DeviceDetail::where('device_type', $data['value'])
                                ->pluck('ip_address')
                                ->toArray());
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ManagePingResults::route('/'),
        ];
    }
}
