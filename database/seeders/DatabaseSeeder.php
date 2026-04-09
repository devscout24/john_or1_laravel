<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            NotificationSeeder::class,
            CompanySettingsSeeder::class,
            DynamicPageSeeder::class,

            LanguageSeeder::class,
            CategorySeeder::class,
            SectionSeeder::class,
            ContentSeeder::class,
            EpisodeSeeder::class,
            PlanSeeder::class,
            CoinPackageSeeder::class,
            RewardSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
