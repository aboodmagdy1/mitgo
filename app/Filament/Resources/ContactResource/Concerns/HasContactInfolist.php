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
                Section::make(__('Contact Information'))
                    ->description(__('Details about the person who contacted us'))
                    ->icon('heroicon-o-user')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('name')
                                    ->label(__('Full Name'))
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color('primary')
                                    ->icon('heroicon-o-user')
                                    ->iconPosition(IconPosition::Before)
                                    ->copyable()
                                    ->copyMessage(__('Name copied to clipboard')),

                                TextEntry::make('email')
                                    ->label(__('Email'))
                                    ->color('info')
                                    ->icon('heroicon-o-envelope')
                                    ->iconPosition(IconPosition::Before)
                                    ->copyable()
                                    ->copyMessage(__('Email copied to clipboard'))
                                    ->url(fn ($record) => 'mailto:' . $record->email)
                                    ->openUrlInNewTab(),
                            ]),
                    ])
                    ->collapsible(),

                Section::make(__('Message Content'))
                    ->description(__('The message content sent by the contact'))
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                TextEntry::make('message')
                                    ->label(__('Message'))
                                    ->columnSpanFull()
                                    ->placeholder(__('No message content provided'))
                                    ->formatStateUsing(fn ($state) => $state ? nl2br(e($state)) : null)
                                    ->html()
                                    ->extraAttributes([
                                        'style' => 'word-wrap: break-word; word-break: break-word; white-space: pre-wrap; max-width: 100%; overflow-wrap: break-word;',
                                        'class' => 'whitespace-pre-wrap break-words max-w-full'
                                    ]),
                            ]),
                    ])
                    ->collapsible(),

                Section::make(__('Message Metadata'))
                    ->description(__('Additional information about this contact message'))
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('type')
                                    ->label(__('Message Type'))
                                    ->formatStateUsing(fn ($state) => $state == 0 ? __('Suggestion') : __('Problem'))
                                    ->color(fn ($state) => $state == 0 ? 'success' : 'danger')
                                    ->icon(fn ($state) => $state == 0 ? 'heroicon-o-light-bulb' : 'heroicon-o-exclamation-triangle')
                                    ->iconPosition(IconPosition::Before),

                                TextEntry::make('created_at')
                                    ->label(__('Received At'))
                                    ->dateTime('F j, Y \a\t g:i A')
                                    ->icon('heroicon-o-clock')
                                    ->iconPosition(IconPosition::Before)
                                    ->color('gray'),

                                TextEntry::make('updated_at')
                                    ->label(__('Last Updated'))
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
