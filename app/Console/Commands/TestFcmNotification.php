<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\TestFcmNotification;
use Illuminate\Console\Command;

class TestFcmNotification extends Command
{
    protected $signature = 'fcm:test 
                            {user_id : The user ID to send test notification to}
                            {--title= : Notification title}
                            {--body= : Notification body}';

    protected $description = 'Send a test FCM notification to a user';

    public function handle(): int
    {
        $user = User::find($this->argument('user_id'));
        if (!$user) {
            $this->error('User not found.');
            return self::FAILURE;
        }

        $tokens = $user->getFCMTokens();
        if (empty($tokens)) {
            $this->error('User has no FCM tokens. Login from the mobile app first to register a token.');
            return self::FAILURE;
        }

        $this->info("User: {$user->name} (ID: {$user->id})");
        $this->info("FCM tokens: " . count($tokens));

        $title = $this->option('title') ?? 'Test Notification';
        $body = $this->option('body') ?? 'FCM is working! ' . now()->format('H:i:s');

        try {
            $user->notify(new TestFcmNotification($title, $body));
            $this->info('Notification sent successfully. Check the device.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
