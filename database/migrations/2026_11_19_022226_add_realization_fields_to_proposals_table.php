<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->string('realization_status')->default('unsubmitted')->after('status'); // unsubmitted, pending, approved, rejected
            $table->json('realization_items')->nullable()->after('realization_status');
            $table->json('realization_proofs')->nullable()->after('realization_items');
            $table->timestamp('realization_submitted_at')->nullable()->after('realization_proofs');
            $table->text('realization_rejection_reason')->nullable()->after('realization_submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn([
                'realization_status',
                'realization_items',
                'realization_proofs',
                'realization_submitted_at',
                'realization_rejection_reason',
            ]);
        });
    }
};