<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Alias for user personal channel
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});


Broadcast::channel('conversations.{conversation_id}', function ($user, $conversation_id) {
    return $user->conversations()->where('id', $conversation_id)->exists();
});

// Driver personal channel for receiving trip requests
Broadcast::channel('driver.{driverId}', function ($user, $driverId) {
    return $user->isDriver() && (int) $user->driver->id === (int) $driverId;
});

// Trip channel - shared by client and assigned driver
Broadcast::channel('trip.{tripId}', function ($user, $tripId) {
    $trip = \App\Models\Trip::find($tripId);
    
    if (!$trip) {
        return false;
    }
    
    // Client (trip owner) can subscribe
    if ($user->id === $trip->user_id) {
        return true;
    }
    
    // Assigned driver can subscribe
    if ($user->isDriver() && $trip->driver_id && $user->driver->id === $trip->driver_id) {
        return true;
    }
    
    return false;
});