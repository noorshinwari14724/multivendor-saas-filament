<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::installDefaults();
        
        $this->command->info('Default settings installed successfully!');
    }
}
