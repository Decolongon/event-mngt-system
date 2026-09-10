<?php

namespace Database\Seeders;

use App\Enums\EventPermissionEnum;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $organizer = Role::firstOrCreate(['name' => 'organizer', 'guard_name' => 'web']);
        $attendee = Role::firstOrCreate(['name' => 'attendee', 'guard_name' => 'web']);

        $create = Permission::firstOrCreate(['name' => EventPermissionEnum::CREATE_EVENTS->value, 'guard_name' => 'web']);
        $view = Permission::firstOrCreate(['name' => EventPermissionEnum::VIEW_EVENTS->value, 'guard_name' => 'web']);
        $update = Permission::firstOrCreate(['name' => EventPermissionEnum::UPDATE_EVENTS->value, 'guard_name' => 'web']);
        $delete = Permission::firstOrCreate(['name' => EventPermissionEnum::DELETE_EVENTS->value, 'guard_name' => 'web']);
        $updateStatus = Permission::firstOrCreate(['name' => EventPermissionEnum::UPDATE_STATUS_EVENTS->value, 'guard_name' => 'web']);

        $organizer->syncPermissions([$create, $view, $update, $delete, $updateStatus]);
        $attendee->syncPermissions([$view]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user1 = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'organizer@gmail.com',
            'password' => bcrypt('12345678'),
        ]);

        $user1->assignRole($organizer);

        $user2 = User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'attendee@gmail.com',
            'password' => bcrypt('12345678'),
        ]);

        $user2->assignRole($attendee);
    }
}
