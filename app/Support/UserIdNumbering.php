<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserIdNumbering
{
    public const OFFSET = 1000;

    /**
     * Ensure user primary keys sit in the four-digit range (1001+).
     * Empty databases get a sequence baseline of 1000 so the next insert is 1001.
     * Existing low ids are shifted by OFFSET and every users-table foreign key follows.
     */
    public static function ensureFourDigitRange(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $count = (int) DB::table('users')->count();

        if ($count === 0) {
            self::setUsersSequence(self::OFFSET);

            return;
        }

        $maxId = (int) DB::table('users')->max('id');

        if ($maxId >= self::OFFSET) {
            self::setUsersSequence($maxId);

            return;
        }

        self::shiftBy(self::OFFSET);
    }

    /**
     * Reverse a prior +OFFSET bump when every user id is still in the bumped range.
     */
    public static function reverseFourDigitRange(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $count = (int) DB::table('users')->count();

        if ($count === 0) {
            return;
        }

        $minId = (int) DB::table('users')->min('id');

        if ($minId < self::OFFSET) {
            return;
        }

        self::shiftBy(-self::OFFSET);
    }

    private static function shiftBy(int $delta): void
    {
        $driver = DB::getDriverName();

        $apply = function () use ($delta, $driver): void {
            // SQLite: foreign_keys=OFF is ignored inside a transaction. Defer checks
            // until commit so parent and child ids can move together.
            if ($driver === 'sqlite') {
                DB::statement('PRAGMA defer_foreign_keys = ON');
            }

            DB::table('users')->update(['id' => DB::raw("id + ({$delta})")]);

            foreach (self::foreignKeyTargets() as [$table, $column]) {
                if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                    continue;
                }

                DB::table($table)
                    ->whereNotNull($column)
                    ->update([$column => DB::raw("{$column} + ({$delta})")]);
            }

            $maxId = (int) DB::table('users')->max('id');
            self::setUsersSequence($maxId);
        };

        if ($driver === 'sqlite') {
            if (DB::transactionLevel() > 0) {
                $apply();
            } else {
                DB::transaction($apply);
            }

            return;
        }

        Schema::disableForeignKeyConstraints();

        try {
            $apply();
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private static function foreignKeyTargets(): array
    {
        return [
            ['sessions', 'user_id'],
            ['user_roles', 'user_id'],
            ['course_accesses', 'user_id'],
            ['course_purchases', 'user_id'],
            ['course_progress', 'user_id'],
            ['course_quiz_submissions', 'user_id'],
            ['clinician_treatment_track', 'user_id'],
            ['screening_submissions', 'user_id'],
            ['screening_submissions', 'reviewed_by'],
            ['screening_submissions', 'deleted_by'],
            ['participants', 'user_id'],
            ['participants', 'primary_clinician_id'],
            ['assessment_results', 'primary_clinician_id'],
            ['assessment_results', 'entered_by_user_id'],
            ['treatment_recommendations', 'confirmed_by'],
            ['message_threads', 'clinician_id'],
            ['messages', 'sender_id'],
        ];
    }

    private static function ensureSqliteUsersSequenceExists(): void
    {
        $hasSequence = DB::select("SELECT 1 AS ok FROM sqlite_master WHERE type = 'table' AND name = 'sqlite_sequence' LIMIT 1");

        if ($hasSequence !== [] && DB::table('sqlite_sequence')->where('name', 'users')->exists()) {
            return;
        }

        // sqlite_sequence is internal and cannot be CREATE TABLE'd; a real insert creates it.
        $seedEmail = '__user_id_sequence_seed__@internal.invalid';

        DB::table('users')->insert([
            'name' => 'Sequence Seed',
            'email' => $seedEmail,
            'password' => 'x',
            'role' => 'participant',
            'is_active' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->where('email', $seedEmail)->delete();
    }

    private static function setUsersSequence(int $seq): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            self::ensureSqliteUsersSequenceExists();

            DB::table('sqlite_sequence')->where('name', 'users')->update(['seq' => $seq]);

            return;
        }

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE users AUTO_INCREMENT = '.((int) $seq + 1));
        }
    }
}