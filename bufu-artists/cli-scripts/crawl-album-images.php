<?php
/**
 * Script to crawls for album images on the shop and add them as post thumbnails
 */

require_once 'State.php';
require_once 'WPApi.php';
require_once 'Crawler.php';

// load settings
$settingsStr = file_get_contents(dirname(__FILE__) . '/settings.json');
$settings = json_decode($settingsStr, true);

// build config
$config  = [
	'source' => [
		'wpapi' => [
			'url'      => $settings['crawl_album_images']['source']['url'],
			'endpoint' => $settings['crawl_album_images']['source']['endpoint'],

			// requires Basic-Auth plugin to be active
			// which should not be the case on a production setup!
			// Use for development/ setup purpose only
			'authorization' => [
				'username' => $settings['target']['wpapi_auth']['username'],
				'password' => $settings['target']['wpapi_auth']['password'],
			]
		]
	],
	'skipExisting' => $settings['crawl_album_images']['skip_existing'],
	'labelPrefix'  => $settings['crawl_album_images']['label_prefix'],
	'labelSuffix'  => $settings['crawl_album_images']['label_suffix'],
	'exitOnError'  => true, // for debugging
];

$startedAt = new \DateTime();

// get state of last run and mappings, start from there
$state = new State();
$stateInfo = $state->loadState();

// init state fields required
if (!array_key_exists('albums', $stateInfo)) {
	$stateInfo['albums'] = [];
}
if (!array_key_exists('__state', $stateInfo)) {
	$stateInfo['__state'] = [];
}
if (!array_key_exists('crawl_album_images', $stateInfo['__state'])) {
	$stateInfo['__state']['crawl_album_images'] = [
		'lastrun' => null,
		'count'   => null,
	];
}

$wpApi   = new WPApi($config['source']['wpapi']);
$crawler = new Crawler();

// get album id mapping from state
$idMapping = $stateInfo['albums'];

// get artist mapping
$artistIdMapping = $stateInfo['artists'];

$albumCount = 0;
$errorCount = 0;
$skippedCount = 0;

foreach ($idMapping as $albumId) {

	// get album, incl. shop URL
	$albumData = $wpApi->getPost($albumId);

	if (!(array_key_exists('id', $albumData) && array_key_exists('type', $albumData) && $albumData['type'] === $config['source']['wpapi']['endpoint'] && array_key_exists('_bufu_artist_shopUrl', $albumData))) {
		echo 'E';

		if ($config['exitOnError']) {
			// save errors?
			var_dump($albumData);
			exit();
		}

		$errorCount++;
		continue;
	}

	if ($config['skipExisting'] && !empty($albumData['featured_media'])) {
		echo 's';

		$skippedCount++;
		continue;
	}

	// get the URL to the shop page
	$shopUrl = $albumData['_bufu_artist_shopUrl'];
	if (empty($shopUrl)) {
		// not found
		echo '-';
		continue;
	}

	// scrape shop page HTML for image URL
	$imageUrl = $crawler->getCoverImageURLFromProductPage($shopUrl);
	if ($imageUrl === null) {
		// not found
		echo '-';
		$albumCount++;
		continue;
	}

	// create media item
	$attachmentData = $wpApi->createMediaItem($imageUrl);
	if (!(array_key_exists('id', $attachmentData) && array_key_exists('type', $attachmentData) && $attachmentData['type'] === 'attachment')) {
		echo 'A';

		if ($config['exitOnError']) {
			// save errors?
			var_dump($albumData);
			exit();
		}

		$errorCount++;
		continue;
	}

	// set attachment properties
	$albumTitle = $albumData['title'];
	if (is_array($albumTitle)) {
		$albumTitle = (array_key_exists('rendered', $albumTitle)) ? $albumTitle['rendered'] : current($albumTitle);
	}
	$title = $config['labelPrefix'] . $albumTitle . $config['labelSuffix'];

	$attachmentFields = [
		'title'   => $title,
		'caption' => $title,
	];

	// update attachment
	$updateResponse = $wpApi->savePost($attachmentFields, $attachmentData['id'], 'media');
	if (!(array_key_exists('id', $updateResponse) && array_key_exists('type', $updateResponse) && $updateResponse['type'] === 'attachment')) {
		echo 'U';

		if ($config['exitOnError']) {
			// save errors?
			var_dump($updateResponse);
			exit();
		}

		$errorCount++;
		continue;
	}

	// add attachment to album as feature image
	$albumFields = [
		'featured_media' => $attachmentData['id'],
	];

	// update album
	$updateResponse2 = $wpApi->savePost($albumFields, $albumId);
	if (!(array_key_exists('id', $updateResponse2) && array_key_exists('type', $updateResponse2) && $updateResponse2['type'] === $config['source']['wpapi']['endpoint'])) {

		echo 'X';

		if ($config['exitOnError']) {
			// save errors?
			var_dump($updateResponse2);
			exit();
		}

		$errorCount++;
		continue;
	}

	// we apparently did it!
	echo '.';
	$albumCount++;
}

// save state and mappings of this run
$now     = new \DateTime();
$diffSec = $now->getTimestamp() - $startedAt->getTimestamp();
$numAlbums = count($idMapping);

$stateInfo['__state']['crawl_album_images']['lastrun'] = "started: " . $startedAt->format(DATE_ISO8601) . " (took {$diffSec} sec)";
$stateInfo['__state']['crawl_album_images']['count']   = [$numAlbums, $albumCount, $errorCount, $skippedCount];
$state->saveState($stateInfo);

echo PHP_EOL . "Done! Processed {$albumCount} albums (of {$numAlbums} total), encountered {$errorCount} errors, skipped {$skippedCount}, took ${diffSec} seconds." . PHP_EOL;