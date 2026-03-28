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
        Schema::table('mp3_files', function (Blueprint $table) {
            $table->enum('visibility', ['public', 'private'])->default('public')->after('mime_type');
            $table->string('password')->nullable()->after('visibility');
            $table->foreignId('user_id')->nullable()->constrained()->after('id')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mp3_files', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['visibility', 'password', 'user_id']);
        });
    }
};
