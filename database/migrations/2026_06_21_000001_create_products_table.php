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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 10)->unique();
            $table->string('title', 255);
            $table->longText('description');
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('quantity');
            $table->string('stock_status', 20);
            $table->date('date_available');
            $table->timestamps();

            $table->index('date_available');
            $table->index('price');
            $table->index('quantity');
            $table->index('stock_status');
            $table->index('title');
            $table->index(['stock_status', 'date_available']);
            $table->index(['price', 'quantity']);
            $table->fullText(['sku', 'title', 'description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
