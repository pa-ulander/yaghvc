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
        Schema::table('profile_views', function (Blueprint $table) {
            // Add repository column
            $table->string('repository')->nullable()->after('username');

            // Drop the unique constraint on username only
            $table->dropUnique(['username']);

            // Add composite unique constraint for username + repository combination
            $table->unique(['username', 'repository'], 'username_repository_unique');

            // Add index for better performance when querying by username only
            $table->index('username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profile_views', function (Blueprint $table) {
            // Drop the composite unique constraint
            $table->dropUnique('username_repository_unique');

            // Drop the username index
            $table->dropIndex(['username']);

            // Re-add the original unique constraint on username
            $table->unique('username');

            // Drop the repository column
            $table->dropColumn('repository');
        });
    }
};
