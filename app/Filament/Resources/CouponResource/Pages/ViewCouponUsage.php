<?php

namespace App\Filament\Resources\CouponResource\Pages;

use App\Filament\Resources\CouponResource;
use App\Models\Coupon;
use App\Models\CouponUsage;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;

class ViewCouponUsage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = CouponResource::class;

    protected static string $view = 'filament.resources.coupon-resource.pages.view-coupon-usage';

    public Coupon $record;

    public function getTitle(): string
    {
        return "تتبع استخدام الكوبون: " . $this->record->name;
    }

    public function getBreadcrumb(): string
    {
        return 'تتبع الاستخدام';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CouponUsage::query()
                    ->where('coupon_id', $this->record->id)
                    ->with(['user', 'trip'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('اسم المستخدم')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.phone')
                    ->label('رقم الهاتف')
                    ->searchable(),

                Tables\Columns\TextColumn::make('trip.id')
                    ->label('رقم الرحلة')
                    ->formatStateUsing(fn ($state) => $state ? '#' . $state : '---')
                    ->url(fn ($record) => $record->trip_id 
                        ? route('filament.admin.resources.trips.view', $record->trip_id) 
                        : null
                    )
                    ->color('primary')
                    ->sortable(),

                Tables\Columns\TextColumn::make('discount_amount')
                    ->label('مبلغ الخصم')
                    ->formatStateUsing(fn ($state) => number_format($state, 2) . ' ريال')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('used_at')
                    ->label('تاريخ الاستخدام')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('recent')
                    ->label('الأسبوع الماضي')
                    ->query(fn (Builder $query): Builder => $query->where('used_at', '>=', now()->subWeek())),
                
                Tables\Filters\Filter::make('month')
                    ->label('الشهر الماضي')
                    ->query(fn (Builder $query): Builder => $query->where('used_at', '>=', now()->subMonth())),
            ])
            ->actions([
                // يمكن إضافة المزيد من الإجراءات هنا
            ])
            ->defaultSort('used_at', 'desc');
    }

    public function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('العودة للكوبونات')
                ->url(CouponResource::getUrl('index'))
                ->icon('heroicon-o-arrow-left'),
        ];
    }
}
