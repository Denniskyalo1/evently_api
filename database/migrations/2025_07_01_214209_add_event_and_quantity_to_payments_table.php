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
        Schema::table('payments', function (Blueprint $table) {
        $table->unsignedBigInteger('event_id')->after('user_id');
        $table->integer('ticket_quantity')->after('event_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
        $table->dropColumn('event_id');
        $table->dropColumn('ticket_quantity');
        });
    }
};
