<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->index('user_id', 'links_user_id_index');
            $table->index('created_at', 'links_created_at_index');

            $table->index('status', 'links_status_index');
            $table->index('expires_at', 'links_expires_at_index');
            $table->index(['user_id', 'created_at'], 'links_user_id_created_at_index');
            $table->index(['user_id', 'status'], 'links_user_id_status_index');
            $table->index(['user_id', 'status', 'expires_at'], 'links_user_id_status_expires_at_index');
        });

        Schema::table('visits', function (Blueprint $table) {
            $table->index('link_id', 'visits_link_id_index');
            $table->index('created_at', 'visits_created_at_index');
            $table->index(['link_id', 'created_at'], 'visits_link_id_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropIndex('visits_link_id_created_at_index');
            $table->dropIndex('visits_link_id_index');
            $table->dropIndex('visits_created_at_index');
        });

        Schema::table('links', function (Blueprint $table) {
            $table->dropIndex('links_user_id_index');
            $table->dropIndex('links_created_at_index');

            $table->dropIndex('links_status_index');
            $table->dropIndex('links_expires_at_index');
            $table->dropIndex('links_user_id_created_at_index');
            $table->dropIndex('links_user_id_status_index');
            $table->dropIndex('links_user_id_status_expires_at_index');
        });
    }
};