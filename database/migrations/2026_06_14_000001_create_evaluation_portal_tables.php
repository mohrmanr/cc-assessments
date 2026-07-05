<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_tracks', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('reassessment_schedule')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('instruments', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('version', 32);
            $table->string('domain', 64);
            $table->json('items')->nullable();
            $table->json('scoring_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('clinician_treatment_track', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('treatment_track_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'treatment_track_id']);
        });

        Schema::create('screening_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('first_name');
            $table->string('last_name');
            $table->json('responses')->nullable();
            $table->string('outcome', 32)->nullable();
            $table->boolean('safety_flag')->default(false);
            $table->text('safety_notes')->nullable();
            $table->text('auto_decision_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['email', 'outcome']);
        });

        Schema::create('account_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('screening_submission_id')->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('treatment_track_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('primary_clinician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('enrolled_at')->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamps();
        });

        Schema::create('assessment_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instrument_id')->constrained()->restrictOnDelete();
            $table->string('administration_type', 32);
            $table->decimal('total_score', 8, 2)->nullable();
            $table->json('subscale_scores')->nullable();
            $table->json('item_responses');
            $table->foreignId('treatment_track_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('primary_clinician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('threshold_met')->default(false);
            $table->json('threshold_flags')->nullable();
            $table->timestamp('administered_at');
            $table->timestamps();

            $table->index(['participant_id', 'instrument_id', 'administered_at']);
        });

        Schema::create('treatment_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_result_id')->constrained()->cascadeOnDelete();
            $table->string('status', 32)->default('pending');
            $table->foreignId('recommended_track_id')->nullable()->constrained('treatment_tracks')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('message_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinician_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['participant_id', 'clinician_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_thread_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 16)->default('user');
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('message_threads');
        Schema::dropIfExists('treatment_recommendations');
        Schema::dropIfExists('assessment_results');
        Schema::dropIfExists('participants');
        Schema::dropIfExists('account_invitations');
        Schema::dropIfExists('screening_submissions');
        Schema::dropIfExists('clinician_treatment_track');
        Schema::dropIfExists('instruments');
        Schema::dropIfExists('treatment_tracks');
    }
};
