<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.initial_radius_km', 2.0);
        $this->migrator->add('general.radius_increment_km', 1.0);
        $this->migrator->add('general.drivers_per_wave', 5);
    }
};
