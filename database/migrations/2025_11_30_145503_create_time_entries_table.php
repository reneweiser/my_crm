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
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->date('date');
            $table->decimal('hours', 8, 2);
            $table->boolean('billable')->default(true);
            $table->boolean('invoiced')->default(false);
            $table->unsignedBigInteger('invoice_id')->nullable(); // Will add foreign key in Sprint 4
            $table->timestamps();

            $table->index('project_id');
            $table->index('user_id');
            $table->index('date');
            $table->index(['billable', 'invoiced']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
