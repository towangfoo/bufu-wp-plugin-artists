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
	 * Get the cover image form a product details page.
	 * @param string $url
	 * @return string|null
	 */
	public function getCoverImageURLFromProductPage($url)
	{
		$html = @file_get_contents($url);
		if ($html === false) {
			return null;
		}
		/**
		 * @var array $http_response_header materializes out of thin air
		 */
		$status_line = $http_response_header[0];
		$match = [];
		preg_match('{HTTP\/\S*\s(\d{3})}', $status_line, $match);

		// check HTTP response code to be OK
		if ($match[1] !== "200") {
			return null;
		}

		$document = new DOMDocument();
		@$document->loadHTML($html); // not interested in `malformed document` warnings

		// get the <p> containing the small image and lightbox link
		$classname = "product-image-zoom";
		$finder = new DomXPath($document);
		$imageContainer = $finder->query("//*[contains(@class, '{$classname}')]")->item(0);

		$imageUrl = null;

		if (isset($imageContainer->childNodes) && is_array($imageContainer->childNodes)) {
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
}