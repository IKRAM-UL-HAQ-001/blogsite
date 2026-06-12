<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\MarketAsset;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
        AdminUserSeeder::class,
        ]);
        
        $adminRole = Role::query()->updateOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Admin', 'description' => 'Full administrative access.']
        );

        Role::query()->updateOrCreate(
            ['name' => 'editor'],
            ['display_name' => 'Editor', 'description' => 'Can access editorial workflows.']
        );

        $userRole = Role::query()->updateOrCreate(
            ['name' => 'user'],
            ['display_name' => 'User', 'description' => 'Default authenticated user.']
        );

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $user->roles()->sync([$adminRole->id, $userRole->id]);

        $marketAssetCount = MarketAsset::seedDefaults();
        $this->command?->info("Seeded {$marketAssetCount} market assets.");

        collect([
            'Forex',
            'Stocks',
            'Gold',
            'Crypto',
            'Oil',
            'Central Banks',
            'Economic Data',
            'Geopolitics',
        ])->each(function (string $name): void {
            Category::query()->updateOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($name)],
                ['name' => $name]
            );
        });
    }
}
