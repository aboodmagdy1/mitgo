<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('content.about_us_ar','arabic about us');
        $this->migrator->add('content.about_us_en','english about us');
        $this->migrator->add('content.privacy_ar','arabic privacy');
        $this->migrator->add('content.privacy_en','english privacy');
        }
};
