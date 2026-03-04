<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.driver_acceptance_time',0);
        $this->migrator->add('general.search_wave_time',10);
        $this->migrator->add('general.search_wave_count',10);
        $this->migrator->add('general.free_waiting_time',10);
        $this->migrator->add('general.commission_rate',30);
        $this->migrator->add('general.emergency_phone','0599999999');
    }
};
