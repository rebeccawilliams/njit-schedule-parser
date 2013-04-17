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

		$courses = Course::all();

		foreach($courses as $cou) :
			// Make a request
			// CHOICE:Submit Course
			$request = $this->client()->post(null, null, [
				'CHOICE' => 'Submit Course',
				'COUR' => $cou->course,
				'SUBJ' => $cou->department,
				'SEMESTER' => $this->semester()
			])->send();
			$body = $request->getBody(true);
			$dom = $this->dom($body);

			$count = 0;
			$section_row_data = $dom->find('table', 0)->find('tr');
			foreach($section_row_data as $section_row) :
				// Skip the header row
				$count += 1;
				if ($count < 4) continue;
				
				// See if it's the last
				if ($count == count($section_row_data)) continue;

				$section = trim($section_row->find('td', 0)->plaintext);

				// Location
				$location = trim($section_row->find('td', 3)->plaintext);
				strip_nb($location);
				strip_double_space($location);

				// Instructor
				$instructor = trim($section_row->find('td', 4)->plaintext);
				strip_nb($instructor);
				strip_double_space($instructor);

				// Comments
				$comments = trim($section_row->find('td', 5)->plaintext);
				strip_nb($comments);
				strip_double_space($comments);

				$credits = (int) $section_row->find('td', 8)->plaintext;

				if ($this->is_multi_day($section_row))
				{
					// Multiple days
					$time_cell = $section_row->find('td', 2);
					$day_cell = $section_row->find('td', 1);

					$time_exploded = explode('<br>', strtolower($time_cell->innertext));
					$day_exploded = explode('<br>', strtolower($day_cell->innertext));

					if (count($time_exploded) !== count($day_exploded))
						return($this->error('Time count != day count'));

					// Strip the location for the double class
					$location = $section_row->find('td', 3)->innertext;
					$location = explode('<BR>', strtoupper($location), 2);
					$location = trim(strip_tags($location[0]));
					strip_nb($location);
					strip_double_space($location);

					for($i = 0; $i < count($time_exploded); $i++) {
						$time = strip_tags($time_exploded[$i]);
						$time = str_replace(' ', '', $time);

						list($start_time, $end_time) = explode('-', $time, 2);
						strip_nb($start_time);
						strip_nb($end_time);

						$day = strip_tags($day_exploded[$i]);
						strip_nb($day);
						$day = strtoupper(str_replace(' ', '', $day));

						// Just one day
						$s = new Section;
						$s->department = $cou->department;
						$s->course = $cou->course;
						$s->section = $section;

						$s->days = $day;
						$s->start_time = date('H:i:s', strtotime($start_time));
						$s->end_time = date('H:i:s', strtotime($end_time));
						
						$s->room = $location;
						$s->room = $location;
						$s->instructor = $instructor;
						$s->comments = $comments;
						$s->credits = $credits;
						$s->save();

						$this->comment($section.' added.');
					}

				}
				else
				{
					// Single Day
					$time_cell = $section_row->find('td', 2);
					$day_cell = $section_row->find('td', 1);

					$time = strip_tags($time_cell->plaintext);
					$time = str_replace(' ', '', $time);
					
					$day = strip_tags($day_cell->plaintext);
					$day = str_replace(' ', '', $day);
					strip_nb($day);
					list($start_time, $end_time) = explode('-', $time, 2);
					strip_nb($start_time);
					strip_nb($end_time);

					// Just one day
					$s = new Section;
					$s->department = $cou->department;
					$s->course = $cou->course;
					$s->section = $section;

					$s->days = str_replace(' ', '', $day);
					$s->start_time = date('H:i:s', strtotime($start_time));
					$s->end_time = date('H:i:s', strtotime($end_time));
					
					
					$s->room = $location;
					$s->instructor = $instructor;
					$s->comments = $comments;
					$s->credits = $credits;
					$s->save();

					$this->comment($section.' added.');
				}

				
			endforeach;
			$this->info('Course complete.');
			sleep(1);

		endforeach;

		$this->info('Import Complete.');
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


	public function format_times($row)
	{
		$time_cell = $row->find('td', 2);
		$time_html = strtolower($time_cell->innertext);

		if (strpos($time_html, '<br>') !== FALSE)
		{
			// Multi-day setup
		}
		else
		{
			// Just one day
		}

		$days_cell = $row->find('td', 1);

		return ['time', 'days'];
	}

	/**
	 * @return boolean
	 */
	public function is_multi_day($row)
	{
		$time_html = strtolower($row->find('td', 2)->innertext);

		if (strpos($time_html, '<br>') !== FALSE)
			return TRUE;
		else
			return FALSE;
	}
}

function strip_nb(&$s) {
	$s = str_replace('&nbsp;', '', $s);
}

function strip_double_space(&$s) {
	$s = str_replace('   ', '  ', $s);
	$s = str_replace('  ', ' ', $s);
}