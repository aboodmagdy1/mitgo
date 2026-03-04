<?php

namespace App\Filament\Resources\ContactResource\Pages;

use App\Filament\Resources\ContactResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;

class ViewContact extends ViewRecord
{
    protected static string $resource = ContactResource::class;

    public function getTitle(): string
    {
        return __('Contact Message Details');
    }

    public function getHeading(): string
    {
        return __('Contact from :name', ['name' => $this->record->name]);
    }

    public function getSubheading(): string
    {
        return __('Received on :date', ['date' => $this->record->created_at->format('F j, Y \a\t g:i A')]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('is_read')
                ->label(__('Mark as Read'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn($record) => $record->is_read == false)
                ->action(function () {
                    $this->record->update(['is_read' => true]);
                    $this->record->save();
                    \Filament\Notifications\Notification::make()
                        ->title(__('Contact marked as read'))
                        ->success()
                        ->send();
                }),

            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading(__('Delete Contact Message'))
                ->modalDescription(__('Are you sure you want to delete this contact message? This action cannot be undone.'))
                ->modalSubmitActionLabel(__('Delete'))
                ->modalCancelActionLabel(__('Cancel')),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Contact Information Section
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
                                    ->url(fn($record) => 'mailto:' . $record->email)
                                    ->openUrlInNewTab(),
                            ]),
                    ])
                    ->collapsible(),

                // Message Content Section
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

                // Media Attachments Section (if any)
              
                // Metadata Section
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