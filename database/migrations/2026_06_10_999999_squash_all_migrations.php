<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $created = [];

    public function up(): void
    {
        $this->createCacheTables();
        $this->createJobsTables();
        $this->createUsersTable();
        $this->createMagicLinksTable();
        $this->createPasswordResetTokensTable();
        $this->createTontinesTable();
        $this->createTontineMembersTable();
        $this->createCyclesTable();
        $this->createTransactionsTable();
        $this->createCreditScoresTable();
        $this->createNotificationsLogTable();
        $this->createActivityLogsTable();
        $this->createAuctionBidsTable();
        $this->createSavingsWithdrawalsTable();
        $this->createPostsTable();
        $this->createSessionsTable();
        $this->createPersonalAccessTokensTable();
        $this->createBadgesTables();
        $this->createChatMessagesTable();
        $this->createCycleVetosTable();
        $this->createWebhookLogsTable();
        $this->createFcmTokensTable();
        $this->createTwoFactorSecretsTable();
        $this->createWebhookSignaturesTable();
    }

    public function down(): void
    {
        $tables = [
            'webhook_signatures', 'two_factor_secrets', 'fcm_tokens', 'webhook_logs',
            'cycle_vetos', 'chat_messages', 'user_badges', 'badges',
            'personal_access_tokens', 'sessions', 'posts', 'savings_withdrawals',
            'auction_bids', 'activity_logs', 'notifications_log', 'credit_scores',
            'transactions', 'cycles', 'tontine_members', 'tontines',
            'password_reset_tokens', 'magic_links',
            'users', 'failed_jobs', 'job_batches', 'jobs', 'cache_locks', 'cache',
        ];
        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function createCacheTables(): void
    {
        if (Schema::hasTable('cache')) return;
        $this->created[] = 'cache';
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });
        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });
    }

    private function createJobsTables(): void
    {
        if (Schema::hasTable('jobs')) return;
        $this->created[] = 'jobs';
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    private function createUsersTable(): void
    {
        if (Schema::hasTable('users')) return;
        $this->created[] = 'users';
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('name', 100)->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->string('google_id')->nullable()->unique();
            $table->string('avatar')->nullable();
            $table->string('role', 20)->default('member');
            $table->string('preferred_language', 10)->default('fr');
            $table->boolean('kyc_verified')->default(false);
            $table->string('kyc_document')->nullable();
            $table->string('kyc_document_hash')->nullable()->index();
            $table->string('kyc_status', 20)->default('none');
            $table->string('kyc_rejected_reason')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('onboarding_completed')->default(false);
            $table->string('referral_code', 8)->nullable()->unique();
            $table->foreignId('referred_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_seen_at')->nullable();
            $table->unsignedSmallInteger('payment_streak')->default(0);
            $table->unsignedSmallInteger('max_streak')->default(0);
            $table->json('notification_settings')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function createMagicLinksTable(): void
    {
        if (Schema::hasTable('magic_links')) return;
        $this->created[] = 'magic_links';
        Schema::create('magic_links', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('token', 64)->unique();
            $table->boolean('used')->default(false);
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    private function createPasswordResetTokensTable(): void
    {
        if (Schema::hasTable('password_reset_tokens')) return;
        $this->created[] = 'password_reset_tokens';
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    private function createTontinesTable(): void
    {
        if (Schema::hasTable('tontines')) return;
        $this->created[] = 'tontines';
        Schema::create('tontines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 10)->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('amount');
            $table->string('frequency', 20)->default('monthly');
            $table->string('type', 30)->default('fixed');
            $table->string('status', 20)->default('pending')->index();
            $table->string('visibility', 20)->default('private');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->unsignedInteger('max_members')->default(20);
            $table->unsignedDecimal('penalty_rate', 5, 2)->default(0);
            $table->unsignedInteger('quorum')->default(1);
            $table->string('draw_method', 20)->default('sequential');
            $table->boolean('weighted_draw')->default(false);
            $table->unsignedTinyInteger('veto_threshold')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function createTontineMembersTable(): void
    {
        if (Schema::hasTable('tontine_members')) return;
        $this->created[] = 'tontine_members';
        Schema::create('tontine_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tontine_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('position')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('chat_last_seen_at')->nullable();
            $table->unsignedSmallInteger('start_cycle_number')->default(1);
            $table->timestamps();
            $table->unique(['tontine_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->index(['tontine_id', 'status']);
        });
    }

    private function createCyclesTable(): void
    {
        if (Schema::hasTable('cycles')) return;
        $this->created[] = 'cycles';
        Schema::create('cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tontine_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('cycle_number');
            $table->foreignId('beneficiary_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->index();
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('total_collected')->default(0);
            $table->unsignedBigInteger('bid_amount')->nullable();
            $table->string('draw_hash', 64)->nullable();
            $table->timestamp('drawn_at')->nullable();
            $table->timestamps();
            $table->unique(['tontine_id', 'cycle_number']);
            $table->index('beneficiary_id');
            $table->index(['tontine_id', 'status']);
        });
    }

    private function createTransactionsTable(): void
    {
        if (Schema::hasTable('transactions')) return;
        $this->created[] = 'transactions';
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('amount');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->string('method', 50)->default('wave');
            $table->string('type', 50)->nullable();
            $table->string('external_reference')->nullable()->unique();
            $table->string('status', 20)->default('pending');
            $table->string('failure_reason')->nullable();
            $table->string('receipt_url')->nullable();
            $table->timestamp('paid_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['cycle_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->index(['cycle_id', 'status']);
        });
    }

    private function createCreditScoresTable(): void
    {
        if (Schema::hasTable('credit_scores')) return;
        $this->created[] = 'credit_scores';
        Schema::create('credit_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->unsignedDecimal('score', 4, 2)->default(0);
            $table->unsignedBigInteger('total_contributed')->default(0);
            $table->unsignedInteger('on_time_payments')->default(0);
            $table->unsignedInteger('total_cycles')->default(0);
            $table->unsignedInteger('seniority_months')->default(0);
            $table->string('badge', 20)->default('none');
            $table->timestamp('calculated_at')->useCurrent();
            $table->timestamps();
            $table->index('user_id');
        });
    }

    private function createNotificationsLogTable(): void
    {
        if (Schema::hasTable('notifications_log')) return;
        $this->created[] = 'notifications_log';
        Schema::create('notifications_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 20)->default('email');
            $table->string('event')->nullable();
            $table->string('type')->nullable();
            $table->text('message');
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'event']);
        });
    }

    private function createActivityLogsTable(): void
    {
        if (Schema::hasTable('activity_logs')) return;
        $this->created[] = 'activity_logs';
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('payload')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'action']);
            $table->index(['model_type', 'model_id']);
        });
    }

    private function createAuctionBidsTable(): void
    {
        if (Schema::hasTable('auction_bids')) return;
        $this->created[] = 'auction_bids';
        Schema::create('auction_bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained()->cascadeOnDelete()->index();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedDecimal('bid_rate', 5, 2);
            $table->timestamps();
            $table->unique(['cycle_id', 'user_id']);
        });
    }

    private function createSavingsWithdrawalsTable(): void
    {
        if (Schema::hasTable('savings_withdrawals')) return;
        $this->created[] = 'savings_withdrawals';
        Schema::create('savings_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tontine_id')->constrained()->cascadeOnDelete()->index();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('status', 20)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['tontine_id', 'user_id']);
        });
    }

    private function createPostsTable(): void
    {
        if (Schema::hasTable('posts')) return;
        $this->created[] = 'posts';
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('excerpt')->nullable();
            $table->text('content')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });
    }

    private function createSessionsTable(): void
    {
        if (Schema::hasTable('sessions')) return;
        $this->created[] = 'sessions';
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    private function createPersonalAccessTokensTable(): void
    {
        if (Schema::hasTable('personal_access_tokens')) return;
        $this->created[] = 'personal_access_tokens';
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    private function createBadgesTables(): void
    {
        if (Schema::hasTable('badges')) return;
        $this->created[] = 'badges';
        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('icon', 50)->default('1f3c5');
            $table->string('tier', 20)->default('bronze');
            $table->string('criteria_type', 50);
            $table->unsignedSmallInteger('criteria_value');
            $table->timestamps();
        });
        Schema::create('user_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('badge_id')->constrained()->cascadeOnDelete();
            $table->timestamp('earned_at')->useCurrent();
            $table->timestamps();
            $table->unique(['user_id', 'badge_id']);
        });
    }

    private function createChatMessagesTable(): void
    {
        if (Schema::hasTable('chat_messages')) return;
        $this->created[] = 'chat_messages';
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tontine_id')->constrained()->cascadeOnDelete()->index();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->timestamps();
            $table->index('created_at');
        });
    }

    private function createCycleVetosTable(): void
    {
        if (Schema::hasTable('cycle_vetos')) return;
        $this->created[] = 'cycle_vetos';
        Schema::create('cycle_vetos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained()->cascadeOnDelete()->index();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['cycle_id', 'user_id']);
        });
    }

    private function createWebhookLogsTable(): void
    {
        if (Schema::hasTable('webhook_logs')) return;
        $this->created[] = 'webhook_logs';
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->index();
            $table->string('webhook_hash')->unique();
            $table->json('payload')->nullable();
            $table->string('status', 20)->default('received');
            $table->string('error')->nullable();
            $table->timestamps();
        });
    }

    private function createFcmTokensTable(): void
    {
        if (Schema::hasTable('fcm_tokens')) return;
        $this->created[] = 'fcm_tokens';
        Schema::create('fcm_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->index();
            $table->text('endpoint')->unique();
            $table->text('p256dh');
            $table->text('auth');
            $table->string('user_agent')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    private function createTwoFactorSecretsTable(): void
    {
        if (Schema::hasTable('two_factor_secrets')) return;
        $this->created[] = 'two_factor_secrets';
        Schema::create('two_factor_secrets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('secret');
            $table->json('backup_codes')->nullable();
            $table->timestamp('enabled_at')->nullable()->index();
            $table->timestamps();
        });
    }

    private function createWebhookSignaturesTable(): void
    {
        if (Schema::hasTable('webhook_signatures')) return;
        $this->created[] = 'webhook_signatures';
        Schema::create('webhook_signatures', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('transaction_id');
            $table->string('signature')->unique();
            $table->boolean('is_verified')->default(false)->index();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['provider', 'transaction_id']);
        });
    }
};
