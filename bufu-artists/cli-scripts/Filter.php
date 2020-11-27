<?php

/**
 * Filters to convert data and text into formats used on the new site.
 */
class Filter
{
	/**
	 * @param string $text
	 * @param bool $includeBlockMarker
	 * @return string
	 */
	public function createParagraphs($text, $includeBlockMarker = true)
	{
		$openP  = ($includeBlockMarker) ? "<!-- wp:paragraph -->\n<p>"     : "<p>";
		$closeP = ($includeBlockMarker) ? "</p>\n<!-- /wp:paragraph -->\n" : "</p>\n";

		$parts = preg_split("/(\r?\n)+\r?\n/", $text);
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

	/**
	 * Create HTML markup for track list within an album.
	 * The list of tracks has to be passed in.
	 * @param array $lyrics
	 * @return string
	 */
	public function createAccordionHtmlForLyrics(array $lyrics)
	{
		if (count($lyrics) < 1) {
			return '';
		}

		// title
		$html  = "<!-- wp:heading -->\n<h2>Liedtexte</h2>\n<!-- /wp:heading -->\n";

		// open accordion
		$html .= "<!-- wp:html -->\n";
		$html .= "<div id=\"album-lyrics\">\n";

		foreach ($lyrics as $track) {
			$trackId = $track['id'];
			$title   = $track['title'];
			$text    = nl2br($this->createParagraphs($this->convertMarkdown($track['text']), false));

			// remove linebreaks directly after </p>
			$text = str_replace(["</p><br>", "</p><br />"], "</p>", $text);

			// open card
			$html .= "<div class=\"card\">";
			// header
			$html .= "<div class=\"card-header\" id=\"track{$trackId}\"><h5 class=\"mb-0\">";
			$html .= "<button class=\"btn btn-link\" data-toggle=\"collapse\" data-target=\"#t{$trackId}\" aria-expanded=\"true\" aria-controls=\"t{$trackId}\">{$title}</button>";
			$html .= "</h5></div>\n";
			// content
			$html .= "<div id=\"t{$trackId}\" class=\"collapse\" aria-labelledby=\"track{$trackId}\" data-parent=\"#album-lyrics\">";
			$html .= "<div class=\"card-body\">{$text}</div>";
			$html .= "</div>";
			// end card
			$html .= "</div>\n";
		}

		// end accordion
		$html .= "</div>\n";
		$html .= "<!-- /wp:html -->\n";

		return $html;
	}

	/**
	 * @param array $tracks
	 * @return string
	 */
	public function createTracklistHtml(array $tracks)
	{
		if (count($tracks) < 1) {
			return '';
		}

		// title
		$html  = "<!-- wp:heading -->\n<h2>Titelliste</h2>\n<!-- /wp:heading -->\n";

		// open list
		$html .= "<!-- wp:list {\"ordered\":true} -->\n<ol>";

		// render track
		foreach ($tracks as $track) {
			$title = trim($track['title']);

			$html .= "<li>";

			if (!empty($track['text'])) {
				// link to lyrics item
				$html .= "<a href=\"#track{$track['id']}\">{$title}</a>";
			}
			else {
				$html .= $title;
			}

			$html .= "</li>";
		}

		// end list
		$html .= "</ol>\n<!-- /wp:list -->\n";

		return $html;
	}

	/**
	 * @param string $lineupString
	 * @return string
	 */
	public function createLineupHtml($lineupString)
	{
		if (empty($lineupString)) {
			return '';
		}

		// split and trim into lineup array
		$lineup = [];
		$lines = explode("] [", $lineupString);
		foreach ($lines as $l) {
			$l = trim($l, " []");
			$matches = [];
			preg_match('/^##NAME (?P<name>.*?) ##JOB (?P<job>.*?)$/', $l, $matches);
			$lineup[] = [
				'name' => $matches['name'],
				'job'  => $matches['job'],
			];
		}

		// title
		$html  = "<!-- wp:heading -->\n<h2>Besetzung</h2>\n<!-- /wp:heading -->\n";

		// begin list
		$html .= "<!-- wp:list -->\n<ul>";

		foreach ($lineup as $row) {
			$html .= "<li><strong>{$row['name']}</strong>: {$row['job']}</li>";
		}

		// end list
		$html .= "</ul>\n<!-- /wp:list -->\n";

		return $html;
	}
}