<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DriverWithdrawRequestResource\Pages;
use App\Models\DriverWithdrawRequest;
use App\Models\Driver;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class DriverWithdrawRequestResource extends Resource
{
    protected static ?string $model = DriverWithdrawRequest::class;
    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return 'المالية';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('driver_id')
                    ->label('السائق')
                    ->relationship('driver.user', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('amount')
                    ->label('المبلغ')
                    ->numeric()
                    ->required()
                    ->step(0.01)
                    ->minValue(0.01)
                    ->prefix('SAR'),
                Forms\Components\Toggle::make('is_approved')
                    ->label('موافق عليه')
                    ->default(false),
                Forms\Components\Textarea::make('notes')
                    ->label('الملاحظات')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
                TextColumn::make('driver.user.name')
                    ->label('اسم السائق')
                    ->searchable()
                    ->sortable()
                    ->url(fn (DriverWithdrawRequest $record): string => 
                        route('filament.admin.resources.drivers.view', ['record' => $record->driver_id])
                    )
                    ->color('primary')
                    ->weight('medium'),
                TextColumn::make('driver.user.phone')
                    ->label('هاتف السائق')
                    ->searchable(),
                TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('SAR')
                    ->sortable(),
                BadgeColumn::make('is_approved')
                    ->label('الحالة')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'مكتمل' : 'قيد الانتظار')
                    ->colors([
                        'warning' => false,
                        'success' => true,
                    ]),
                TextColumn::make('notes')
                    ->label('الملاحظات')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('is_approved')
                    ->label('الحالة')
                    ->options([
                        0 => 'قيد الانتظار',
                        1 => 'مكتمل',
                    ]),
            ])
            ->actions([
                Action::make('mark_completed')
                    ->label('تحديد كمكتمل')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (DriverWithdrawRequest $record): bool => !$record->is_approved)
                    ->form([
                        Forms\Components\Textarea::make('completion_notes')
                            ->label('ملاحظات الإكمال')
                            ->placeholder('أضف أي ملاحظات حول إكمال هذا الطلب...')
                            ->rows(3),
                    ])
                    ->action(function (DriverWithdrawRequest $record, array $data): void {
                        // Update the request as completed
                        $record->update([
                            'is_approved' => true,
                            'notes' => ($record->notes ? $record->notes . "\n\n" : '') . 
                                      'ملاحظات الإدارة: ' . ($data['completion_notes'] ?? 'لا توجد ملاحظات إضافية'),
                        ]);

                        Notification::make()
                            ->title('تم تحديد الطلب كمكتمل')
                            ->body('تم إكمال طلب السحب بنجاح')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('تحديد كمكتمل')
                    ->modalDescription('تحديد طلب السحب هذا كمكتمل. هذا للحفظ والتوثيق فقط.')
                    ->modalSubmitActionLabel('تحديد كمكتمل'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDriverWithdrawRequests::route('/'),
            'create' => Pages\CreateDriverWithdrawRequest::route('/create'),
            'view' => Pages\ViewDriverWithdrawRequest::route('/{record}/view'),
            'edit' => Pages\EditDriverWithdrawRequest::route('/{record}/edit'),
        ];
    }

    public static function getLabel(): string
    {
        return 'طلب السحب';
    }

    public static function getPluralLabel(): string
    {
        return 'طلبات السحب';
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_approved', false)->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }
}
