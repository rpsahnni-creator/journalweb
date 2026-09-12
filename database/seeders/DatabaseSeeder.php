<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ProductionSeeder::class);

        if (app()->environment('production')) {
            return;
        }

        $this->call([
            AdminUserSeeder::class,
            JournalContentSeeder::class,
        ]);
    }
}
