<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('two_factor_attempts')->default(0)->after('two_factor_expires_at');
            $table->timestamp('two_factor_lock_expires_at')->nullable()->after('two_factor_attempts');
            $table->integer('two_factor_lock_count')->default(0)->after('two_factor_lock_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_attempts',
                'two_factor_lock_expires_at',
                'two_factor_lock_count',
            ]);
        });
    }
};
