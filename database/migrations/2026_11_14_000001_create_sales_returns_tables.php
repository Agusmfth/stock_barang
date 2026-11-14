<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_no')->nullable()->unique();
            $table->foreignId('stock_transaction_id')->constrained('stock_transactions')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('returned_at');
            $table->text('reason');
            $table->decimal('refund_total', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('sales_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_return_id')->constrained('sales_returns')->cascadeOnDelete();
            $table->foreignId('stock_transaction_item_id')->constrained('stock_transaction_items')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->unsignedInteger('qty');
            $table->decimal('price', 15, 2);
            $table->decimal('cost_price', 15, 2);
            $table->timestamps();
            $table->unique(['sales_return_id', 'stock_transaction_item_id'], 'sales_return_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_return_items');
        Schema::dropIfExists('sales_returns');
    }
};
