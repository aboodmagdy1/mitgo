<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;
use Illuminate\Support\Facades\Cache;

class GeneralSettings extends Settings
{
    public int $driver_acceptance_time;
    public int $search_wave_time;
    public int $search_wave_count;
    public float $initial_radius_km;
    public float $radius_increment_km;
    public int $drivers_per_wave;
    public int $free_waiting_time;
    public int $commission_rate;
    public string $emergency_phone;

    public static function group(): string
    {
        return 'general';
    }


    public function save(): self
    {
        parent::save();
        Cache::forget('all_settings');
        Cache::forget('setting_general_driver_acceptance_time');
        Cache::forget('setting_general_search_wave_time');
        Cache::forget('setting_general_search_wave_count');
        Cache::forget('setting_general_initial_radius_km');
        Cache::forget('setting_general_radius_increment_km');
        Cache::forget('setting_general_drivers_per_wave');
        Cache::forget('setting_general_free_waiting_time');
        Cache::forget('setting_general_commission_rate');
        Cache::forget('setting_general_emergency_phone');
        return $this;

    }
}