<?php 

namespace App\Settings;

use Illuminate\Support\Facades\Storage;
use Spatie\LaravelSettings\Settings;
use Illuminate\Support\Facades\Cache;

class ContentSettings extends Settings
{
    public string $about_us_ar;
    public string $about_us_en;
    public string $privacy_ar;
    public string $privacy_en;
    public static function group(): string
    {
                return 'content';
    }

    public function save(): self
    {
        parent::save();
        Cache::forget('all_settings');
        Cache::forget('setting_content_about_us_ar');
        Cache::forget('setting_content_about_us_en');
        Cache::forget('setting_content_privacy_ar');
        Cache::forget('setting_content_privacy_en');
        return $this;
    }       


}
