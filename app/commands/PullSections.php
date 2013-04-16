<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class PullSections extends ScheduleCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'schedule:sections';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Pull in the individual sections.';

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
		
		// Clear all courses
		DB::table('sections')->truncate();
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
		return [];
		return array(
			array('example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null),
		);
	}

}