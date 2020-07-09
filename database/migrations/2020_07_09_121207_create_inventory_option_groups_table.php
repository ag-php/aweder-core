<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryOptionGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('inventory_option_groups')) {
            Schema::create('inventory_option_groups', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
                $table->foreignId('inventory_id')->index('inventory_id_option_group')->constrained()->onDelete('RESTRICT');
                $table->string('name');
                $table->integer('title');
                $table->boolean('required')->default(0);
                $table->dateTime('deleted_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inventory_option_groups');
    }
}
