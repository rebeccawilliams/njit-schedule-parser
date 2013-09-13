<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class SectionTrigger extends ScheduleCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'schedule:trigger';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Trigger async session calls.';

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
		$concur = (bool) $this->option('concurrent');

		$departments = Department::all();
		$count = 0;

		foreach($departments as $dep) :
			$count += 1;
			$this->comment('Triggering '.$dep->name);

			list($department_id, $department_name) = explode(Config::get('schedule.delim'), $dep->name, 2);
			$department_id = trim($department_id);
			$department_name = trim($department_name);
			
			if ($concur) :
				$e = '/usr/bin/env php '.dirname(dirname(__DIR__)).'/artisan schedule:sections '.$department_id.' >> /dev/null &';
				exec($e);
			else :
				$this->call('schedule:sections', ['department' => $department_id]);
				
				sleep(5);
			endif;
		endforeach;

		$this->info('Done triggering!');
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return [
			array('concurrent', InputArgument::OPTIONAL, 'Run concurrent requests (will require A LOT of CPU and DB Load!)', false),
		];
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return [
			array('concurrent', 'c', InputArgument::OPTIONAL, 'Run concurrent requests (will require A LOT of CPU and DB Load!)', false),
		];
	}

}