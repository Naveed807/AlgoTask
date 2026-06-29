<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_uuid')->unique();
            $table->string('applicant_name');
            $table->string('applicant_cnic',15)->unique();
            $table->string('district');
            $table->string('tehsil');
            $table->string('partner_name');
            $table->string('bank_name');
            $table->string('branch_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applicant_cases');
    }
};
