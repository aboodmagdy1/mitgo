<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomNotificationsTable extends Migration {

	public function up()
	{
		Schema::create('custom_notifications', function(Blueprint $table) {
			$table->id('id');
			$table->timestamps();
			$table->json('title');
			$table->json('body');
			$table->json('metadata');
			$table->boolean('is_read')->default(false);
			$table->morphs('notifiable');
		});
	}

	public function down()
	{
		Schema::drop('custom_notifications');
	}
}