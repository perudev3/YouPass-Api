<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventBarItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('event_bar_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('event_id');

            $table->string('name');
            $table->string('category'); // bebidas | cocteleria | destilados

            $table->decimal('price', 10, 2);

            $table->string('image')->nullable();

            $table->boolean('available')->default(true);

            $table->timestamps();

            $table->foreign('event_id')
                ->references('id')
                ->on('events')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('event_bar_items');
    }
}
