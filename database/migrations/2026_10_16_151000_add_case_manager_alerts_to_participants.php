<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table("participants", function (Blueprint $table) {
            $table->string("case_manager_email")->nullable();
            $table->json("therapy_alerts_sent")->nullable();
        });
    }

    public function down(): void
    {
        Schema::table("participants", function (Blueprint $table) {
            $table->dropColumn(["case_manager_email", "therapy_alerts_sent"]);
        });
    }
};
