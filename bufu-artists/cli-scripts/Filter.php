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
		$parts = preg_split("/\r?\n\r?\n/", $text);
		return "<p>" . join("</p><p>", $parts) . "</p>";
	}
}