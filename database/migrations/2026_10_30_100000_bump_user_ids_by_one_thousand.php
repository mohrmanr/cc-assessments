<?php

use App\Support\UserIdNumbering;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        UserIdNumbering::ensureFourDigitRange();
    }

    public function down(): void
    {
        UserIdNumbering::reverseFourDigitRange();
    }
};