<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class PullSchedule extends Command {
	private $base = 'http://www.njit.edu/registrar/cgi-bin/schedule_builder.cgi';
	private $semester = 'Fall';

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'schedule:pull';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Pull and Sync the schedule';

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

		$client = $this->client();
		$departments = $client->post(null, null, [
			'SEMESTER' => $this->semester,
			'CHOICE' => $this->semester
		])->send();

		var_dump($departments->getBody(true));
		exit;
	}

	private function client()
	{
		return new Guzzle\Http\Client($this->base);
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