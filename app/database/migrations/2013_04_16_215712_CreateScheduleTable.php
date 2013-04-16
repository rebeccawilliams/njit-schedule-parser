<?php

use Illuminate\Database\Migrations\Migration;

class CreateScheduleTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('courses', function($table)
		{
			//$table->increments('id');
			$table->string('department');
			$table->string('course');
			$table->string('course_name');

			$table->timestamps();
		});

		Schema::create('sections', function($table)
		{
			// ACCT 117
			$table->string('course');
			$table->integer('section');
			$table->string('days');
			$table->string('times');
			$table->string('room');
			$table->string('instructor');
			$table->string('comments');
			$table->integer('credits');

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
		Schema::drop('courses');
		Schema::drop('sections');
	}

}