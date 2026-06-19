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
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('number')->unique();
            $table->string('supplier_name');
            $table->string('supplier_tax_id');
            $table->decimal('net_amount', 15, 2);
            $table->decimal('vat_amount', 15, 2);
            $table->decimal('gross_amount', 15, 2);
            $table->string('currency', 3)->default('UAH');
            $table->string('status')->default('pending')->index();
            $table->date('issue_date');
            $table->date('due_date');
            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
