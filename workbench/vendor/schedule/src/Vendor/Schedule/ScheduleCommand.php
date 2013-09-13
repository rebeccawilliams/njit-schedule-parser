<?php
namespace Vendor\Schedule;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Support\Facades\Config;

class ScheduleCommand extends Command {
	protected function client($u = NULL)
	{
		if ($u == NULL)
			$u = $this->base();

		return new \Guzzle\Http\Client($u);
	}

	protected function dom($str, $lowercase=true, $forceTagsClosed=true, $target_charset = DEFAULT_TARGET_CHARSET, $stripRN=true, $defaultBRText=DEFAULT_BR_TEXT, $defaultSpanText=DEFAULT_SPAN_TEXT)
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

	public function base() {
		return Config::get('schedule.base');
	}

	public function semester()
	{
		return Config::get('schedule.semester');
	}
}