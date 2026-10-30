<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\UserIdNumbering;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserIdNumberingTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_database_assigns_user_ids_above_one_thousand(): void
    {
        $user = User::query()->create([
            'name' => 'Four Digit',
            'email' => 'four.digit@example.test',
            'password' => Hash::make('password'),
            'role' => 'participant',
        ]);

        $this->assertGreaterThanOrEqual(1001, $user->id);

        $next = User::query()->create([
            'name' => 'Next User',
            'email' => 'next.user@example.test',
            'password' => Hash::make('password'),
            'role' => 'participant',
        ]);

        $this->assertSame($user->id + 1, $next->id);
        $this->assertGreaterThan(1000, $next->id);
    }

    public function test_existing_low_ids_are_bumped_with_foreign_keys(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('participants')->delete();
        DB::table('user_roles')->delete();
        DB::table('users')->delete();
        Schema::enableForeignKeyConstraints();

        DB::table('sqlite_sequence')->where('name', 'users')->update(['seq' => 0]);

        $userId = DB::table('users')->insertGetId([
            'name' => 'Low Id',
            'email' => 'low.id@example.test',
            'password' => Hash::make('password'),
            'role' => 'participant',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertLessThan(1000, $userId);

        DB::table('user_roles')->insert([
            'user_id' => $userId,
            'role' => 'participant',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('participants')->insert([
            'user_id' => $userId,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        UserIdNumbering::ensureFourDigitRange();

        $bumped = $userId + UserIdNumbering::OFFSET;

        $this->assertSame($bumped, (int) DB::table('users')->where('email', 'low.id@example.test')->value('id'));
        $this->assertSame($bumped, (int) DB::table('user_roles')->where('role', 'participant')->value('user_id'));
        $this->assertSame($bumped, (int) DB::table('participants')->where('status', 'active')->value('user_id'));

        $created = User::query()->create([
            'name' => 'After Bump',
            'email' => 'after.bump@example.test',
            'password' => Hash::make('password'),
            'role' => 'participant',
        ]);

        $this->assertGreaterThan($bumped, $created->id);
        $this->assertGreaterThan(1000, $created->id);
    }
}