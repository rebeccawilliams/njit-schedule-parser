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
	protected $description = 'Pull in the individual sections for a department.';

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

		$department_id = strtoupper($this->argument('department'));
		
		// Clear all courses in the department
		Section::whereDepartment($department_id)->delete();

		$courses = Course::whereDepartment($department_id)->get();

		foreach($courses as $course) :
			// Make a request
			// CHOICE:Submit Course
			$url = Config::get('schedule.section');
			$url = sprintf($url, Config::get('schedule.semester'), $department_id, $course->course);

			$request = $this->client()->get($url, null)->send();
			$body = $request->getBody(true);
			$dom = $this->dom($body);

			$count = 0;
			$section_row_data = $dom->find('tr.sectionRow');
			foreach($section_row_data as $section_row) :
				$count += 1;

				// Check that status
				$status = trim($section_row->find('td.status span', 0)->innertext);

				if ($status == 'Cancelled') :
					$this->info('Section canceled, skipping.');
					continue;
				endif;

				$sectionText = trim($section_row->find('td.section', 0)->innertext);
				$section = explode('<br />', $sectionText)[0];
				$section = trim($section);

				// Location
				$location = trim($section_row->find('td.room', 0)->plaintext);
				strip_nb($location);
				strip_double_space($location);

				// Instructor
				$instructor = $section_row->find('td.instructor', 0)->plaintext;
				$instructor = trim($instructor);
				strip_nb($instructor);
				strip_double_space($instructor);

				$credits = (int) $section_row->find('td.credits', 0)->plaintext;
				$call = (int) $section_row->find('td.call span', 0)->plaintext;
				// Comments
				$commentsTd = $section_row->find('td', 2);
				$commentsText = $commentsTd->find('span', 1);
				$comments = (! is_null($commentsText)) ? trim($commentsText->plaintext) : '';

				strip_nb($comments);
				strip_double_space($comments);

				$times = strtoupper($commentsTd->find('span', 0)->innertext);

				if ($this->isMultiDayDiff($times))
				{
					$times_exploded = explode('<BR>',
						str_replace('<BR />', '<BR>', $times)
					);

					$location = trim($section_row->find('td.room span', 0)->innertext);
					$location_exploded = explode('<BR>',
						str_replace('<BR />', '<BR>', strtoupper($location))
					);

					if (count($times_exploded) !== count($location_exploded))
						return($this->error('Time count != day count'));

					for($i = 0; $i < count($times_exploded); $i++) {
						list ($days, $hours) = explode(':', $times_exploded[$i], 2);
						$time = str_replace(' ', '', $hours);

						list($start_time, $end_time) = explode('-', $time, 2);
						strip_nb($start_time);
						strip_nb($end_time);
						fixTimeFormat($start_time);
						fixTimeFormat($end_time);

						// Just one day
						$s = new Section;
						$s->department = $course->department;
						$s->course = $course->course;
						$s->section = $section;

						$s->days = trim($days);;
						$s->start_time = date('H:i:s', strtotime($start_time));
						$s->end_time = date('H:i:s', strtotime($end_time));
						
						$s->room = $location_exploded[$i];
						$s->instructor = $instructor;
						$s->comments = $comments;
						$s->credits = $credits;
						$s->call_number = $call;
						$s->save();

						$this->comment($section.' added.');
					}

				}
				else
				{
					// skip empty times, not university offered
					if (empty($times)) continue;

					list ($days, $hours) = explode(':', $times, 2);
					$time = str_replace(' ', '', $hours);

					list($start_time, $end_time) = explode('-', $time, 2);
					strip_nb($start_time);
					strip_nb($end_time);
					fixTimeFormat($start_time);
					fixTimeFormat($end_time);

					// Just one day
					$s = new Section;
					$s->department = $course->department;
					$s->course = $course->course;
					$s->section = $section;

					$s->days = str_replace(' ', '', $days);
					$s->start_time = date('H:i:s', strtotime($start_time));
					$s->end_time = date('H:i:s', strtotime($end_time));
					
					$s->room = $location;
					$s->instructor = $instructor;
					$s->comments = $comments;
					$s->credits = $credits;
					$s->call_number = $call;
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
		return [
			['department', InputArgument::REQUIRED, 'Course Department']
		];
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
	 * Determine if a section has multiple days with different times
	 * 
	 * @return boolean
	 * @param  string
	 */
	public function isMultiDayDiff($times)
	{
		$time_html = strtoupper($times);
		$time_html = str_replace('<BR />', '<BR>', $time_html);

		if (strpos($time_html, '<BR>') !== FALSE)
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

function fixTimeFormat(&$s) {
	$s = str_replace('AM', ' AM', $s);
	$s = str_replace('PM', ' PM', $s);

	list($num, $ampm) = explode(' ', $s, 2);

	if (strlen($num) == 3)
		$num = '0'.$num;
	
	$chunks = str_split($num, 2);
	//Convert array to string.  Each element separated by the given separator.
	$num = implode(':', $chunks);

	$s = $num.' '.$ampm;

	return $s;
}