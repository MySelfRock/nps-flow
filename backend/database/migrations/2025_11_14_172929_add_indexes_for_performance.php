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
        // Recipients table indexes
        Schema::table('recipients', function (Blueprint $table) {
            $table->index('email'); // For lookups and filtering
            $table->index('status'); // For filtering by status
            $table->index(['campaign_id', 'status']); // Composite for campaign queries
        });

        // Responses table indexes
        Schema::table('responses', function (Blueprint $table) {
            $table->index('score'); // For category filtering (promoters/detractors)
            $table->index('created_at'); // For trending and date filtering
            $table->index(['campaign_id', 'score']); // Composite for campaign NPS calculations
            $table->index(['tenant_id', 'created_at']); // For tenant reports
        });

        // Sends table indexes
        Schema::table('sends', function (Blueprint $table) {
            $table->index('status'); // For filtering by status
            $table->index(['campaign_id', 'status']); // For campaign send tracking
            $table->index('last_attempt_at'); // For retry scheduling
        });

        // Campaigns table indexes
        Schema::table('campaigns', function (Blueprint $table) {
            $table->index(['tenant_id', 'status']); // Composite for tenant queries
            $table->index('status'); // For global status filtering
            $table->index('scheduled_at'); // For scheduler
        });

        // Audit logs table indexes
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['tenant_id', 'created_at']); // For tenant audit log queries
            $table->index('action'); // For action filtering
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipients', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropIndex(['status']);
            $table->dropIndex(['campaign_id', 'status']);
        });

        Schema::table('responses', function (Blueprint $table) {
            $table->dropIndex(['score']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['campaign_id', 'score']);
            $table->dropIndex(['tenant_id', 'created_at']);
        });

        Schema::table('sends', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['campaign_id', 'status']);
            $table->dropIndex(['last_attempt_at']);
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'status']);
            $table->dropIndex(['status']);
            $table->dropIndex(['scheduled_at']);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'created_at']);
            $table->dropIndex(['action']);
        });
    }
};
