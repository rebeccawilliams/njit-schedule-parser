<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class PullDepartments extends Command {
	private $base = 'http://www.njit.edu/registrar/cgi-bin/schedule_builder.cgi';
	private $semester = 'Fall';

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
			'SEMESTER' => $this->semester,
			'CHOICE' => $this->semester
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

	private function client()
	{
		return new Guzzle\Http\Client($this->base);
	}

	private function dom($str, $lowercase=true, $forceTagsClosed=true, $target_charset = DEFAULT_TARGET_CHARSET, $stripRN=true, $defaultBRText=DEFAULT_BR_TEXT, $defaultSpanText=DEFAULT_SPAN_TEXT)
	{
		$dom = new \DOM\simple_html_dom(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);
		if (empty($str) OR strlen($str) > MAX_FILE_SIZE)
		{
			$dom->clear();
			return false;
		}

		$dom->load($str, $lowercase, $stripRN);
		return $dom;
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