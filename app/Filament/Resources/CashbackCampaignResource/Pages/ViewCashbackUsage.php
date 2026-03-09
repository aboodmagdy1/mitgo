<?php

namespace App\Filament\Resources\CashbackCampaignResource\Pages;

use App\Filament\Resources\CashbackCampaignResource;
use App\Models\CashbackCampaign;
use App\Models\CashbackUsage;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;

class ViewCashbackUsage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = CashbackCampaignResource::class;

    protected static string $view = 'filament.resources.cashback-campaign-resource.pages.view-cashback-usage';

    public CashbackCampaign $record;

    public function getTitle(): string
    {
        return __('cashback.usage_page.title', ['name' => $this->record->name]);
    }

    public function getBreadcrumb(): string
    {
        return __('cashback.usage_page.breadcrumb');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CashbackUsage::query()
                    ->where('cashback_campaign_id', $this->record->id)
                    ->with(['user', 'trip'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('cashback.usage_page.table.user_name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.phone')
                    ->label(__('cashback.usage_page.table.user_phone'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('trip.id')
                    ->label(__('cashback.usage_page.table.trip_number'))
                    ->formatStateUsing(fn ($state) => $state ? '#' . $state : '---')
                    ->url(fn ($record) => $record->trip_id
                        ? route('filament.admin.resources.trips.view', $record->trip_id)
                        : null
                    )
                    ->color('primary')
                    ->sortable(),

                Tables\Columns\TextColumn::make('cashback_amount')
                    ->label(__('cashback.usage_page.table.cashback_amount'))
                    ->formatStateUsing(fn ($state) => number_format($state, 2) . ' ' . __('SAR'))
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('awarded_at')
                    ->label(__('cashback.usage_page.table.awarded_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('recent')
                    ->label(__('cashback.filters.recent_week'))
                    ->query(fn (Builder $query): Builder => $query->where('awarded_at', '>=', now()->subWeek())),

                Tables\Filters\Filter::make('month')
                    ->label(__('cashback.filters.recent_month'))
                    ->query(fn (Builder $query): Builder => $query->where('awarded_at', '>=', now()->subMonth())),
            ])
            ->actions([])
            ->defaultSort('awarded_at', 'desc');
    }

    public function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label(__('cashback.usage_page.back_to_campaigns'))
                ->url(CashbackCampaignResource::getUrl('index'))
                ->icon('heroicon-o-arrow-left'),
        ];
    }
}

