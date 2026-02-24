<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvitationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->onDelete('cascade'); // mesa comprada
            $table->foreignId('event_id')->constrained();
            $table->string('code')->unique();        // código de invitación
            $table->string('status')->default('pending'); // pending | used
            $table->foreignId('claimed_by')->nullable()->constrained('users'); // quien lo usó
            $table->timestamp('used_at')->nullable();
            $table->string('guest_phone')->nullable();  // 🔥 número del invitado
            $table->string('token')->unique();           // 🔥 token para el link
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invitations');
    }
}
