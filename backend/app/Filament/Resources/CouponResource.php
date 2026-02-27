<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Marketing';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações do Cupom')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->uppercase(),
                        Forms\Components\TextInput::make('description')
                            ->maxLength(255),
                        Forms\Components\Toggle::make('active')
                            ->default(true),
                    ])->columns(3),

                Forms\Components\Section::make('Desconto')
                    ->schema([
                        Forms\Components\Select::make('discount_type')
                            ->options([
                                'percent' => 'Porcentagem (%)',
                                'fixed' => 'Valor Fixo (R$)',
                            ])
                            ->default('percent')
                            ->required(),
                        Forms\Components\TextInput::make('discount_value')
                            ->required()
                            ->numeric()
                            ->prefix(fn (Forms\Get $get) => $get('discount_type') === 'percent' ? '%' : 'R$'),
                        Forms\Components\TextInput::make('max_discount')
                            ->numeric()
                            ->prefix('R$')
                            ->label('Desconto Máximo'),
                        Forms\Components\TextInput::make('min_purchase')
                            ->numeric()
                            ->prefix('R$')
                            ->label('Compra Mínima'),
                    ])->columns(4),

                Forms\Components\Section::make('Limites de Uso')
                    ->schema([
                        Forms\Components\TextInput::make('max_uses')
                            ->numeric()
                            ->label('Usos Totais (0 = ilimitado)'),
                        Forms\Components\TextInput::make('max_uses_per_user')
                            ->numeric()
                            ->label('Usos por Cliente'),
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Validade'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('discount_value')
                    ->formatStateUsing(fn ($record) => 
                        $record->discount_type === 'percent' 
                            ? "{$record->discount_value}%" 
                            : "R$ {$record->discount_value}"
                    ),
                Tables\Columns\TextColumn::make('used')
                    ->label('Usados'),
                Tables\Columns\TextColumn::make('max_uses')
                    ->label('Máx. Usos'),
                Tables\Columns\IconColumn::make('active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime()
                    ->color(fn ($record) => $record->expires_at?->isPast() ? 'danger' : null),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active'),
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
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
