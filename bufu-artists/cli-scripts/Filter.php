<?php

/**
 * Created by PhpStorm.
 * User: towangfoo
 * Date: 11.09.20
 * Time: 15:19
 */
class Filter
{
	/**
	 * @param string $text
	 * @return string
	 */
	public function createParagraphs($text)
	{
		$openP  = "<p>";
		$closeP = "</p>";

		$parts = preg_split("/\r?\n\r?\n/", $text);
		return $openP . join($closeP . $openP, $parts) . $closeP;
	}

	/**
	 * @param int $timestamp
	 * @return \DateTime
	 */
	public function getDateTimeFromTimestamp($timestamp)
	{
		if (!is_int($timestamp)) {
			$timestamp = intval($timestamp, 10);
		}

		$dt = DateTime::createFromFormat("U", $timestamp);
		$dt->setTimezone(new DateTimeZone('Europe/Berlin'));

		return $dt;
	}

	/**
	 * @param int $timestamp
	 * @param DateTimeZone|null $dtz
	 * @return string
	 */
	public function getFormattedDateFromTimestamp($timestamp, DateTimeZone $dtz = null)
	{
		$dt = $this->getDateTimeFromTimestamp($timestamp);

		if ($dtz instanceof DateTimeZone) {
			$dt->setTimezone($dtz);
		}

		return $dt->format("Y-m-d\TH:i:s");
	}

	/**
	 * @param string $ipt
	 * @return string
	 */
	public function convertMarkdown($ipt)
	{
		$ipt = str_replace(['[fett]', '[/fett]'], ['<b>', '</b>'], $ipt);

		return $ipt;
	}
}