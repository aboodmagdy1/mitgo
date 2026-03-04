<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration {

	public function up()
	{
		Schema::create('users', function(Blueprint $table) {
			$table->id();
			$table->timestamps();
			$table->string('name')->nullable();
			$table->string('email')->unique()->nullable();
			$table->string('phone')->unique();
			$table->string('password')->nullable();
			$table->foreignId('city_id')->nullable()->constrained('cities')->onDelete('cascade');
			$table->boolean('is_active')->default(true);
			$table->decimal('latest_lat', 10, 8)->nullable();
			$table->decimal('latest_long', 11, 8)->nullable();
			$table->string('address')->nullable();
			$table->integer('active_code')->nullable();
			$table->rememberToken();

		});
	}

	public function down()
	{
		Schema::drop('users');
	}
}