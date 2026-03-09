<?php

namespace App\Filament\Resources\ContactResource\Concerns;

use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;

trait HasContactInfolist
{
    public function contactInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('معلومات الاتصال')
                    ->description('تفاصيل عن الشخص الذي اتصل بنا')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('الاسم الكامل')
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color('primary')
                                    ->icon('heroicon-o-user')
                                    ->iconPosition(IconPosition::Before)
                                    ->copyable()
                                    ->copyMessage('تم نسخ الاسم إلى الحافظة'),

                                TextEntry::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->color('info')
                                    ->icon('heroicon-o-envelope')
                                    ->iconPosition(IconPosition::Before)
                                    ->copyable()
                                    ->copyMessage('تم نسخ البريد الإلكتروني إلى الحافظة')
                                    ->url(fn ($record) => 'mailto:' . $record->email)
                                    ->openUrlInNewTab(),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('محتوى الرسالة')
                    ->description('محتوى الرسالة المرسلة من قبل الاتصال')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                TextEntry::make('message')
                                    ->label('الرسالة')
                                    ->columnSpanFull()
                                    ->placeholder('لم يتم تقديم محتوى الرسالة')
                                    ->formatStateUsing(fn ($state) => $state ? nl2br(e($state)) : null)
                                    ->html()
                                    ->extraAttributes([
                                        'style' => 'word-wrap: break-word; word-break: break-word; white-space: pre-wrap; max-width: 100%; overflow-wrap: break-word;',
                                        'class' => 'whitespace-pre-wrap break-words max-w-full'
                                    ]),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('بيانات الرسالة')
                    ->description('معلومات إضافية عن رسالة الاتصال هذه')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('type')
                                    ->label('نوع الرسالة')
                                    ->formatStateUsing(fn ($state) => $state == 0 ? 'اقتراح' : 'مشكلة')
                                    ->color(fn ($state) => $state == 0 ? 'success' : 'danger')
                                    ->icon(fn ($state) => $state == 0 ? 'heroicon-o-light-bulb' : 'heroicon-o-exclamation-triangle')
                                    ->iconPosition(IconPosition::Before),

                                TextEntry::make('created_at')
                                    ->label('تاريخ الاستلام')
                                    ->dateTime('F j, Y \a\t g:i A')
                                    ->icon('heroicon-o-clock')
                                    ->iconPosition(IconPosition::Before)
                                    ->color('gray'),

                                TextEntry::make('updated_at')
                                    ->label('آخر تحديث')
                                    ->dateTime('F j, Y \a\t g:i A')
                                    ->icon('heroicon-o-pencil')
                                    ->iconPosition(IconPosition::Before)
                                    ->color('gray')
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
