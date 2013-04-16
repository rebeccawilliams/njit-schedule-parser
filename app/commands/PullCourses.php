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
		// Clear all courses
		DB::table('courses')->truncate();

		$departments = Department::all();
		foreach($departments as $dep) :
			// Make a request
			$request = $this->client()->post(null, null, [
				'CHOICE' => 'Add By Subject',
				'SUBJ' => $dep->name,
				'SEMESTER' => $this->semester()
			])->send();
			$body = $request->getBody(true);
			$dom = $this->dom($body);

			foreach($dom->find('select', 0)->childNodes() as $course) :
				$plain = trim($course->plaintext);
				$plain = str_replace('   ', '  ', $plain);
				$plain = str_replace('  ', ' ', $plain);
				
				list($department, $course, $name) = explode(' ', $plain, 3);

				$this->comment('Adding '.$plain);

				$c = new Course;
				$c->department = trim($department);
				$c->course = trim($course);
				$c->course_name = trim($name);
				$c->save();
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