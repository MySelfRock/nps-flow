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
        Schema::create('billing_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('plan'); // free, starter, pro, enterprise
            $table->string('status')->default('active'); // active, canceled, past_due, unpaid
            $table->string('stripe_subscription_id')->nullable();
            $table->timestamp('next_billing_date')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('currency', 3)->default('BRL');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_subscriptions');
    }
};
