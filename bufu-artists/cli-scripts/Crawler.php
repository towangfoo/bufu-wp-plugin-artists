<?php

/**
 * Created by PhpStorm.
 * User: towangfoo
 * Date: 28.11.20
 * Time: 20:48
 *
 * This crawls the Magento 1.9.x shop with the bufu theme for stuff.
 */
class Crawler
{
	/**
	 * @var string
	 */
	private $host;

	/**
	 * @var array
	 */
	private $skipUrlsPatterns;

	/**
	 * Get the cover image form a product details page.
	 * @param string $url
	 * @return string|null
	 */
	public function getCoverImageURLFromProductPage($url)
	{
		$html = @file_get_contents($url);

		// check html response to be OK
		if ($html === false) {
			return null;
		}

		$document = new DOMDocument();
		@$document->loadHTML($html); // not interested in `malformed document` warnings

		// get the <p> containing the small image and lightbox link
		$classname = "product-image-zoom";
		$finder = new DomXPath($document);
		$imageContainer = $finder->query("//*[contains(@class, '{$classname}')]")->item(0);

		$imageUrl = null;

		if (isset($imageContainer->childNodes)) {
			foreach ($imageContainer->childNodes as $c) {
				/** @var $c DOMElement */
				if ($c->nodeName === 'a') {
					$imageUrl = $c->getAttribute('href');
					break;
				}
			}
		}

		return $imageUrl;
	}

	/**
	 * Extract all URLs referenced in a HTML document.
	 * @param $host
	 * @param bool $recurse
	 * @param int $passes
	 * @return array|null
	 */
	public function extractUrls($host, $recurse = true, $passes = 3)
	{
		$this->host = trim($host, ' /');

		$urls = $this->_extractUrls($host);
		$urls = array_unique($urls);

		if ($recurse) {
			for ($i = 1; $i <= $passes; $i++) {
				$countBefore = count($urls);

				$newList = [];
				foreach ($urls as $u) {
					$newList = array_merge($newList, $this->_extractUrls($u));
				}

				// merge results from pass into main list
				$urls = array_merge($urls, $newList);
				$urls = array_unique($urls);

				// did we get something new?
				if (count($urls) === $countBefore) {
					echo PHP_EOL . "stop after {$i} passes";
					break;
				}
			}
		}

		sort($urls);

		return $urls;
	}

	/**
	 * Set patterns of URLs to no include in the results
	 * @param array $patterns
	 */
	public function setUrlExcludePatters(array $patterns)
	{
		$this->skipUrlsPatterns = $patterns;
	}

	/**
	 * @param $url
	 * @return array
	 */
	private function _extractUrls($url)
	{
		if (strpos($url, $this->host) !== 0) {
			return []; // do not follow external urls
		}

		$html = @file_get_contents($url);

		// check html response to be OK
		if ($html === false) {
			return [];
		}

		$document = new DOMDocument();
		@$document->loadHTML($html); // not interested in `malformed document` warnings

		$anchors = $document->getElementsByTagName('a');

		$urls = [];
		foreach ($anchors as $a) {
			/** @var $a DOMElement */
			$href = $a->getAttribute('href');

			// prepend relative URLs
			if (substr($href, 0, 8) !== 'https://' && substr($href, 0, 7) !== 'http://') {
				if (substr($href, 0, 1) === '/') {
					$href = $this->host . $href;
				}
				else  {
					$href = $this->host . '/' . $href;
				}
			}

			$ignore = false;
			foreach ($this->skipUrlsPatterns as $p) {
				if (preg_match($p, $href) === 1) {
					$ignore = true;
					break;
				}
			}

			if (!$ignore) {
				$urls[] = $href;
			}
		}

		return $urls;
	}
}