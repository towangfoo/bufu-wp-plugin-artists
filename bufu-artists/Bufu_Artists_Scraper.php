<?php

require_once 'Bufu_Artists.php';

/**
 * Scrape data from HTML documents.
 */
class Bufu_Artists_Scraper
{
	/**
	 * @var string
	 */
	private static $varDir = 'var';

	/**
	 * @var string
	 */
	private static $imageDir = 'product-images';

	/**
	 * @var string
	 */
	private static $productsDataFile = 'products.json';

	/**
	 * @param array $urls
	 */
	public function loadProducts(array $urls)
	{
		$data = [];
		foreach ($urls as $url) {
			$response = $this->scrapeProductData($url);

			if ($response['success'] === true) {
				$data[] = [
					'url'  => $url,
					'data' => $response['product']
				];
			}
			else {
				$data[] = [
					'url'   => $url,
					'data'  => null,
					'error' => (isset($response['message'])) ? $response['message'] : '',
				];
			}
		}

		$this->writeToFile(self::$productsDataFile, $data);
	}

	// -----------------------------------------------------------------------------------------------------------------

	private function scrapeProductData($url)
	{
		$document = $this->loadDocument($url);
		if (!$document instanceof DOMDocument) {
			return [
				'success' => false,
				'message' => 'Could not load document',
			];
		}

		// scrape sku and type
		$sku  = $this->scrapeForProductSku($document);
		$type = $this->scrapeForProductType($document);

		// scrape name and artist
		$name   = $this->scrapeForProductName($document);
		$artist = $this->scrapeForProductArtist($document);

		// scrape prices and vat
		$prices = $this->scrapeForProductPrices($document);
		$vat = $this->scrapeForProductVAT($document);

		// scrape image
		$imageUrl = $this->scrapeForProductImageUrl($document);

		// copy image to local
		$imagePath = $this->copyImage($imageUrl, $sku);

		return [
			'success' => true,
			'product' => [
				'sku'    => $sku,
				'type'   => $type,
				'name'   => $name,
				'artist' => $artist,
				'image_remote'  => $imageUrl,
				'image_local'   => $imagePath,
				'regular_price' => $prices[0],
				'special_price' => $prices[1],
				'vat_percent'   => $vat,
			],
		];
	}

	/**
	 * @param string $url
	 * @return DOMDocument|null
	 */
	private function loadDocument($url)
	{
		$html = @file_get_contents($url);

		// check html response to be OK
		if ($html === false) {
			return null;
		}

		$document = new DOMDocument();
		@$document->loadHTML($html); // not interested in `malformed document` warnings

		return $document;
	}

	/**
	 * @param $filename
	 * @param array $data
	 * @return bool|int
	 */
	private function writeToFile($filename, array $data)
	{
		$pwd = dirname(__FILE__);

		$path = $pwd . DIRECTORY_SEPARATOR . self::$varDir . DIRECTORY_SEPARATOR . $filename;
		if (!is_writable(dirname($path))) {
			return false;
		}

		$encoded = json_encode($data);
		if (json_last_error() !== JSON_ERROR_NONE) {
			return false;
		}

		return (false !== file_put_contents($path, $encoded));
	}

	/**
	 * @param $imageUrl
	 * @param null $filename
	 * @return bool|string
	 */
	private function copyImage($imageUrl, $filename = null)
	{
		$fileContent = file_get_contents($imageUrl);
		$basename = basename($imageUrl);

		if ($filename) {
			$ext = substr($basename, strrpos($basename, '.'));
			$path = self::$varDir . DIRECTORY_SEPARATOR . self::$imageDir . DIRECTORY_SEPARATOR . $filename . $ext;
		}
		else {
			$path = self::$varDir . DIRECTORY_SEPARATOR . self::$imageDir . DIRECTORY_SEPARATOR . $basename;
		}

		$pwd = dirname(__FILE__);

		$fullPath = $pwd . DIRECTORY_SEPARATOR . $path;

		if (!is_dir(dirname($fullPath))) {
			mkdir(dirname($fullPath), 0777, true);
		}

		if (false === file_put_contents($fullPath, $fileContent)) {
			return false;
		}

		return $path;
	}

	/**
	 * @param DOMDocument $document
	 * @return null|string
	 */
	private function scrapeForProductImageUrl(DOMDocument $document)
	{
		// get the <p> containing the small image and lightbox link
		$classname = "product-image-zoom";
		$finder = new DomXPath($document);
		$imageContainer = $finder->query("//*[contains(@class, '{$classname}')]")->item(0);

		$imageUrl = null;

		if (isset($imageContainer->childNodes)) {
			foreach ($imageContainer->childNodes as $c) {
				/** @var $c DOMElement */
				if ($c->nodeName === 'a') {
					$imageUrl = trim($c->getAttribute('href'));
					break;
				}
			}
		}

		return $imageUrl;
	}

	/**
	 * @param DOMDocument $document
	 * @return null|string
	 */
	private function scrapeForProductType(DOMDocument $document)
	{
		// get the .product-shop-essentials box
		$classname = "product-shop-essentials";
		$finder = new DomXPath($document);
		$essentialsEl = $finder->query("//*[contains(@class, '{$classname}')]")->item(0);

		if (isset($essentialsEl->childNodes)) {
			foreach ($essentialsEl->childNodes as $node) {
				if ($node->nodeType !== 1) {
					continue;
				}
				if (strpos($node->nodeValue, 'Medium:') !== false) {
					return trim(str_replace('Medium:', '', $node->nodeValue));
				}
			}
		}

		return null;
	}

	/**
	 * @param DOMDocument $document
	 * @return null|string
	 */
	private function scrapeForProductSku(DOMDocument $document)
	{
		// get the .product-shop-essentials box
		$classname = "product-shop-essentials";
		$finder = new DomXPath($document);
		$essentialsEl = $finder->query("//*[contains(@class, '{$classname}')]")->item(0);

		if (isset($essentialsEl->childNodes)) {
			foreach ($essentialsEl->childNodes as $node) {
				if ($node->nodeType !== 1) {
					continue;
				}
				if (strpos($node->nodeValue, 'Bestellnr.:') !== false) {
					return trim(str_replace('Bestellnr.:', '', $node->nodeValue));
				}
			}
		}

		return null;
	}

	/**
	 * @param DOMDocument $document
	 * @return string
	 */
	private function scrapeForProductName(DOMDocument $document)
	{
		// get the .product-name h3
		$classname = "product-name";
		$finder = new DomXPath($document);
		$h3El = $finder->query("//*[contains(@class, '{$classname}')]")->item(0);

		return trim($h3El->textContent);
	}

	/**
	 * @param DOMDocument $document
	 * @return string
	 */
	private function scrapeForProductArtist(DOMDocument $document)
	{
		// get the .artist p
		$classname = "artist";
		$finder = new DomXPath($document);
		$p = $finder->query("//*[contains(@class, '{$classname}')]")->item(0);

		return trim($p->textContent);
	}

	/**
	 * @param DOMDocument $document
	 * @return array
	 */
	private function scrapeForProductPrices(DOMDocument $document)
	{
		// get the .product-shop-essentials box
		$classname = "product-shop-essentials";
		$finder = new DomXPath($document);
		$essentialsEl = $finder->query("//*[contains(@class, '{$classname}')]")->item(0);

		// get the .price span
		if (isset($essentialsEl->childNodes)) {
			foreach ($essentialsEl->childNodes as $node) {
				if ($node->nodeType !== 1) {
					continue;
				}

				if (strpos($node->nodeValue, '€') !== false) {
					// handle special prices
					$posSpecialPrice = strpos($node->nodeValue, 'Sonderpreis');
					if ($posSpecialPrice !== false) {
						$normal  = substr($node->nodeValue, 0, $posSpecialPrice-1);
						$special = str_replace('Sonderpreis:', '', substr($node->nodeValue, $posSpecialPrice));

						return [trim($normal), trim($special)];
					}
					else {
						return [trim($node->nodeValue), null];
					}
				}
			}
		}

		return [null, null];
	}

	/**
	 * @param DOMDocument $document
	 * @return string|null
	 */
	private function scrapeForProductVAT(DOMDocument $document)
	{
		// get the .product-shop-essentials box
		$classname = "product-shop-essentials";
		$finder = new DomXPath($document);
		$essentialsEl = $finder->query("//*[contains(@class, '{$classname}')]")->item(0);

		// get the .price span
		if (isset($essentialsEl->childNodes)) {
			foreach ($essentialsEl->childNodes as $node) {
				if ($node->nodeType !== 1) {
					continue;
				}

				if (strpos($node->nodeValue, '% MwSt.,') !== false) {
					// scrape VAT rate
					$posPercent = strpos($node->nodeValue, '%');
					$value = substr($node->nodeValue, 0, $posPercent);
					return trim(substr($value, 5));
				}
			}
		}

		return null;
	}
}