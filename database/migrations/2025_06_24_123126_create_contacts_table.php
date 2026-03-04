<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactsTable extends Migration {

	public function up()
	{
		Schema::create('contacts', function(Blueprint $table) {
			$table->id('id');
			$table->timestamps();
			$table->string('name');
			$table->string('email');
			$table->longText('message');
			$table->tinyInteger('type')->default(1);
			$table->boolean('is_read')->default(false);
		});
	}

	public function down()
	{
		Schema::drop('contacts');
	}
}