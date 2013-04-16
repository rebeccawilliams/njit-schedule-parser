<?php

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class PullDepartments extends ScheduleCommand {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'schedule:departments';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Pull and Sync the departments';

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
		set_time_limit(500);

		// Clear the departments table
		DB::table('departments')->truncate();

		// Start to pull them in
		$client = $this->client();
		$departments = $client->post(null, null, [
			'SEMESTER' => $this->semester(),
			'CHOICE' => $this->semester()
		])->send();
		$body = $departments->getBody(true);
		$dom = $this->dom($body);

		$dep_listing = $dom->find('select', 0)->childNodes();
		$count = 0;

		foreach($dep_listing as $dep) :
			$d = new Department;
			$d->name = trim($dep->plaintext);
			$d->save();

			$count += 1;

		endforeach;
		
		$this->info(number_format($count) . ' Departments registered.');
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return [];

		return array(
			array('example', InputArgument::REQUIRED, 'An example argument.'),
		);
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