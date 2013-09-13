<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class PullCourses extends ScheduleCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'schedule:courses';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Pull in the courses from the departments we have.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		// Try and retrieve all the departments
		ignore_user_abort(TRUE);
		
		// Clear all courses
		DB::table('courses')->truncate();

		$departments = Department::all();
		foreach($departments as $dep) :
			list($department_id, $department_name) = explode(Config::get('schedule.delim'), $dep->name, 2);
			$department_id = trim($department_id);
			$department_name = trim($department_name);
			$url = sprintf(Config::get('schedule.department'), Config::get('schedule.semester'), $department_id);

			$request = $this->client()->get($url, null)->send();
			$body = $request->getBody(true);
			$dom = $this->dom($body);

			foreach($dom->find('.courseName a') as $course) :
				$plain = trim($course->plaintext);
				$plain = str_replace('   ', '  ', $plain);
				$plain = str_replace('  ', ' ', $plain);

				list($department, $course, $name) = explode(' ', $plain, 3);

				$c = new Course;
				$c->department = trim($department_id);
				$c->course = trim($course);
				$c->course_name = str_replace('(', ' (',
					trim(
						str_replace('- ', '', $name)
					)
				);
				$c->save();

				$this->comment('Adding '.$c->course_name);
			endforeach;
		endforeach;

		$this->info('Done importing courses.');
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return [];
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null),
		);
	}
}