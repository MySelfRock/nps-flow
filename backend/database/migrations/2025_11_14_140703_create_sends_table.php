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
        Schema::create('sends', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('campaign_id');
            $table->uuid('recipient_id');
            $table->string('channel'); // email, whatsapp
            $table->string('status')->default('pending'); // pending, sent, delivered, failed, bounced
            $table->string('provider_message_id')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
            $table->foreign('recipient_id')->references('id')->on('recipients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sends');
    }
};
