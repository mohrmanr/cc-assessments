<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 32);
            $table->timestamps();
            $table->unique(['user_id', 'role']);
        });

        $users = DB::table('users')->select('id', 'role', 'created_at', 'updated_at')->get();
        foreach ($users as $user) {
            if ($user->role === null || $user->role === '') {
                continue;
            }
            DB::table('user_roles')->insert([
                'user_id' => $user->id,
                'role' => $user->role,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]);
        }

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('price_cents')->default(0);
            $table->boolean('requires_payment')->default(true);
            $table->unsignedTinyInteger('pass_percent')->default(75);
            $table->string('video_placeholder')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('course_quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 16);
            $table->string('title');
            $table->json('items');
            $table->timestamps();
            $table->unique(['course_id', 'kind']);
        });

        Schema::create('course_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('source', 32);
            $table->timestamps();
            $table->unique(['user_id', 'course_id']);
        });

        Schema::create('course_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount_cents');
            $table->boolean('is_stub')->default(true);
            $table->timestamp('purchased_at');
            $table->timestamps();
        });

        Schema::create('course_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->timestamp('video_completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'course_id']);
        });

        Schema::create('course_quiz_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_quiz_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 16);
            $table->json('answers');
            $table->decimal('score', 5, 1);
            $table->boolean('passed');
            $table->timestamp('submitted_at');
            $table->timestamps();
            $table->unique(['user_id', 'course_quiz_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_quiz_submissions');
        Schema::dropIfExists('course_progress');
        Schema::dropIfExists('course_purchases');
        Schema::dropIfExists('course_accesses');
        Schema::dropIfExists('course_quizzes');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('user_roles');
    }
};
