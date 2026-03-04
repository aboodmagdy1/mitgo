<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Filament\Notifications\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class SendAdminNotfication implements ShouldQueue
{
    use Queueable;
    
    public string $title;
    public string $body;
    public array $actions;
    public string $type;

    /**
     * Create a new job instance.
     */
    public function __construct(string $title, string $body, array $actions = [], string $type = 'database')
    {
        $this->title = $title;
        $this->body = $body;
        $this->actions = $actions;
        $this->type = $type;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
         $admins = User::getAdmins();
        
        if ($this->type == 'database') {
            Notification::make()
                ->title($this->title)
                ->body($this->body)
                ->actions($this->actions)
                ->sendToDatabase($admins);
        }
    }
}
