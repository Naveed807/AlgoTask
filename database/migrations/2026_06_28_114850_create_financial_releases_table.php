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
        Schema::create('financial_releases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained()->cascadeOnDelete();
            $table->date('first_release_date')->nullable();
            $table->date('second_release_date')->nullable();
            $table->date('final_release_date')->nullable();
            $table->decimal('first_amount',12,2)->nullable();
            $table->decimal('second_amount',12,2)->nullable();
            $table->decimal('final_amount',12,2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_releases');
    }
};
