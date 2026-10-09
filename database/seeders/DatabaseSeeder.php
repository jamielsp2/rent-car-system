<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * The starting data every installation needs: currencies, taxes, vehicle
     * catalogs, payment defaults and a few FAQs. Safe to run again.
     *
     * Example data (cars, bookings...) is separate: DemoSeeder.
     */
    public function run(): void
    {
        $this->call(DefaultDataSeeder::class);
    }
}
