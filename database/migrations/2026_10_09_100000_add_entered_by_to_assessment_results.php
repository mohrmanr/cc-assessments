<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_results', function (Blueprint $table) {
            $table->foreignId('entered_by_user_id')->nullable()->after('primary_clinician_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('assessment_results', function (Blueprint $table) {
            $table->dropConstrainedForeignId('entered_by_user_id');
        });
    }
};