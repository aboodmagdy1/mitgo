<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;
use BezhanSalleh\FilamentShield\FilamentShield;
use BezhanSalleh\FilamentShield\Commands;
use Filament\Notifications\Livewire\DatabaseNotifications;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use App\Models\Trip;
use App\Observers\TripObserver;
use App\Events\TripCancelled;
use App\Events\TripCompleted;
use App\Events\TripCreated;
use App\Events\TripDriverAccepted;
use App\Events\TripDriverArrived;
use App\Events\TripEnded;
use App\Events\TripNoDriverFound;
use App\Events\TripNoShow;
use App\Events\TripRequestSent;
use App\Events\TripStarted;
use App\Listeners\InitiateDriverSearch;
use App\Listeners\SendChatMessageFcmNotification;
use App\Listeners\LogTripRequestToDb;
use App\Listeners\SendNewTripRequestFcmNotification;
use App\Listeners\SendTripStatusFcmNotification;
use Modules\Chat\Events\MessageSent;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch
                ->locales(['ar']); // also accepts a closure
        });

        Commands\SetupCommand::prohibit($this->app->environment('production'));
        Commands\InstallCommand::prohibit($this->app->environment('production'));
        // Commands\GenerateCommand::prohibit($this->app->environment('production'));
        Commands\PublishCommand::prohibit($this->app->environment('production'));
        // FilamentShield::prohibitDestructiveCommands($this->app->environment('production'));


        
        Gate::guessPolicyNamesUsing(function (string $modelClass) {
            return str_replace('Models', 'Policies', $modelClass) . 'Policy';
        });
        DatabaseNotifications::trigger('vendor.filament.notifications.database-notifications-trigger');
        
        // Register Trip observer for side effects on status changes
        Trip::observe(TripObserver::class);

        // Register trip events
        Event::listen(TripCreated::class, InitiateDriverSearch::class);

        // FCM notifications for trip status changes
        Event::listen(TripDriverAccepted::class, [SendTripStatusFcmNotification::class, 'handleTripDriverAccepted']);
        Event::listen(TripDriverArrived::class, [SendTripStatusFcmNotification::class, 'handleTripDriverArrived']);
        Event::listen(TripStarted::class, [SendTripStatusFcmNotification::class, 'handleTripStarted']);
        Event::listen(TripEnded::class, [SendTripStatusFcmNotification::class, 'handleTripEnded']);
        Event::listen(TripCompleted::class, [SendTripStatusFcmNotification::class, 'handleTripCompleted']);
        Event::listen(TripCancelled::class, [SendTripStatusFcmNotification::class, 'handleTripCancelled']);
        Event::listen(TripNoShow::class, [SendTripStatusFcmNotification::class, 'handleTripNoShow']);
        Event::listen(TripNoDriverFound::class, [SendTripStatusFcmNotification::class, 'handleTripNoDriverFound']);

        // FCM notifications for new trip request (to driver)
        Event::listen(TripRequestSent::class, SendNewTripRequestFcmNotification::class);

        // Log trip request to DB for acceptance/rejection rate reports (async, non-blocking)
        Event::listen(TripRequestSent::class, LogTripRequestToDb::class);

        // FCM notifications for new chat message
        Event::listen(MessageSent::class, SendChatMessageFcmNotification::class);

        // Clear custom settings cache when Spatie settings are updated
        $this->clearCustomSettingsCache();
    }

    /**
     * Clear custom settings cache when Spatie settings are updated
     */
    private function clearCustomSettingsCache(): void
    {
        // Listen for Spatie settings events
        \Event::listen('spatie.settings.saved', function ($settings) {
            // Clear all custom settings cache
            Cache::forget('all_settings');
            Cache::forget('settings');
            
            // Clear specific setting caches based on the settings class
            if ($settings instanceof \App\Settings\GeneralSettings) {
                Cache::forget('setting_general_name_ar');
                Cache::forget('setting_general_name_en');
                Cache::forget('setting_general_email');
                Cache::forget('setting_general_phone');
                Cache::forget('setting_general_logo_ar');
                Cache::forget('setting_general_logo_en');
            } elseif ($settings instanceof \App\Settings\SocialMediaSettings) {
                Cache::forget('setting_social_media_facebook');
                Cache::forget('setting_social_media_twitter');
                Cache::forget('setting_social_media_instagram');
                Cache::forget('setting_social_media_linkedin');
                Cache::forget('setting_social_media_youtube');
            }
        });
    }
}
