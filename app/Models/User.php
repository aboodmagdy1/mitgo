<?php

namespace App\Models;

use App\Enums\TripStatus;
use Bavix\Wallet\Traits\HasWallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Modules\Chat\Models\Conversation;
use Spatie\Permission\Traits\HasRoles;
use Filament\Panel;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Spatie\MediaLibrary\InteractsWithMedia;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use App\Models\UserSavedLocation;
use Bavix\Wallet\Interfaces\Wallet;

class User extends Model implements FilamentUser, HasMedia, Authenticatable, AuthorizableContract , Wallet
{
    use HasWallet;

    use HasFactory, Notifiable, HasRoles, InteractsWithMedia, HasApiTokens, AuthenticatableTrait, Authorizable;

    protected $table = 'users';
    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activated_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
    protected $fillable = array('name', 'email', 'phone', 'city_id', 
    'password', 'latest_lat', 'latest_long', 'active_code', 'is_active', 'age', 'gender', 'address', 'lang');

    public function sendActiveCode()
    {
        // $this->active_code = env('APP_ENV') != 'production' ? 1234 : rand(1000,9999);
        $this->active_code = 1234;
        $this->save();
    }
   
    
    public function canAccessPanel(Panel $panel): bool
    {
        // any role excpect client , driver
        if($this->hasRole(['client', 'driver'])) {
            return false;
        }
        return true;
    }

    public function city()
    {
        return $this->belongsTo('App\Models\City');
    }

    public function customNotifications()
    {
        return $this->morphMany('App\Models\CustomNotification', 'notifiable');
    }

   

   

   
    public function fcmTokens()
    {
        return $this->hasMany('App\Models\FcmToken');
    }
    public function getFCMTokens()
    {
        return $this->fcmTokens()->pluck('token')->toArray();
    }
    public function routeNotificationForFcm()
    {
        return $this->getFCMTokens();
    }

   




    public static function getAdmins(){
        return self::whereHas('roles', function($query){
            $query->whereIn('name', ['super_admin', 'admin']);
        })->get();
    }

    public function conversations(){
        return $this->belongsToMany(Conversation::class, 'conversation_user');
    }

    /**
     * Get the driver profile for the user.
     */
    public function driver()
    {
        return $this->hasOne(Driver::class);
    }

    /**
     * Get all trips for the user (as a rider).
     */
    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    /**
     * Get all ratings given by the user.
     */
    public function givenRatings()
    {
        return $this->hasMany(TripRating::class);
    }

    public function cashbackUsages(): HasMany
    {
        return $this->hasMany(CashbackUsage::class);
    }

    /**
     * Check if user is a driver.
     */
    public function isDriver(): bool
    {
        return $this->hasRole('driver');
    }

    /**
     * Check if user is a rider.
     */
    public function isClient(): bool
    {
        return $this->hasRole('client');
    }

    public function savedLocations()
    {
        return $this->hasMany(UserSavedLocation::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/svg+xml', 'image/webp']);
    }

    public static function boot(){
        parent::boot();
        static::deleted(function($user){
            $user->fcmTokens()?->delete();
            $user->driver()?->delete();
            $user->trips()?->delete();
            $user->givenRatings()?->delete();
            $user->savedLocations()?->delete();
        });
    }
    public function hasActiveTrip(): bool
    {
        return $this->trips()->whereIn('status', [TripStatus::SEARCHING, TripStatus::IN_ROUTE_TO_PICKUP, TripStatus::PICKUP_ARRIVED])->exists();
    }

    /**
     * Get locale string from lang column (1=ar, 2=en).
     */
    public function getLocaleAttribute(): string
    {
        return $this->lang == 1 ? 'ar' : 'en';
    }

}