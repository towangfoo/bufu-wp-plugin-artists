<?php
/**
 * Script to migrate album data from old site to new WP site
 */

require_once 'DBTable.php';
require_once 'Filter.php';
require_once 'State.php';
require_once 'WPApi.php';

// load settings
$settingsStr = file_get_contents(dirname(__FILE__) . '/settings.json');
$settings = json_decode($settingsStr, true);

// build config
$config  = [
	'source' => [
		'mysql' => [
			'hostname' => $settings['source']['mysql']['hostname'],
			'username' => $settings['source']['mysql']['username'],
			'password' => $settings['source']['mysql']['password'],
			'db'       => $settings['source']['mysql']['db']
		],
		'table_name' => [
			'albums' => $settings['migrate_albums']['source']['table_names']['albums'],
			'tracks' => $settings['migrate_albums']['source']['table_names']['tracks'],
		]
	],
	'target' => [
		'wpapi' => [
			'url'      => $settings['migrate_albums']['target']['url'],
			'endpoint' => $settings['migrate_albums']['target']['endpoint'],

			// requires Basic-Auth plugin to be active
			// which should not be the case on a production setup!
			// Use for development/ setup purpose only
			'authorization' => [
				'username' => $settings['target']['wpapi_auth']['username'],
				'password' => $settings['target']['wpapi_auth']['password'],
			]
		]
	],
	'skipExisting' => $settings['migrate_albums']['skip_existing'],
];

$startedAt = new \DateTime();

// get state of last run and mappings, start from there
$state = new State();
$stateInfo = $state->loadState();

// init state fields required for artists
if (!array_key_exists('albums', $stateInfo)) {
	$stateInfo['albums'] = [];
	$stateInfo['__state']['albums'] = [
		'lastrun' => null,
		'count'   => null,
	];
}


// define album properties
$album = new stdClass();
$album->id = null;
$album->artistid = null;
$album->albumtitel = null;
$album->jahr = null;
$album->besetzung = null;
$album->label = null;
$album->shoplink = null;

// define track properties
$track = new stdClass();
$track->id = null;
$track->albumid = null;
$track->titel = null;
$track->titelnr = null;
$track->text = '';


$albumTable = new DBTable($config['source']['table_name']['albums'], $config['source']['mysql']);
$albumTable->setHydrateObject($album);

$trackTable = new DBTable($config['source']['table_name']['tracks'], $config['source']['mysql']);
$trackTable->setHydrateObject($track);

$wpApi = new WPApi($config['target']['wpapi']);

$filter = new Filter();

// get album id mapping from state
$idMapping = $stateInfo['albums'];

// get artist mapping
$artistIdMapping = $stateInfo['artists'];

$albumRrows = $albumTable->getRows();
foreach ($albumRrows as $row) {
	/** @var $row stdClass */

	// if the id is known from mapping, update the post instead
	$wpPostId = null;
	if (array_key_exists($row->id, $idMapping)) {
		if ($config['skipExisting']) {
			echo "s";
			continue;
		}

		$wpPostId = $idMapping[$row->id];
	}

	// get track list for the album
	$tracks = $trackTable->getRows("SELECT * FROM %TABLE% WHERE albumid = {$row->id} ORDER BY titelnr ASC");

	$tracklist = [];
	$lyrics    = [];
	foreach ($tracks as $t) {
		$item = [
			'id'    => $t->id,
			'title' => $t->titel,
			'text'  => $t->text,
		];

		$tracklist[] = $item;
		if (!empty($item['text'])) {
			$lyrics[] = $item;
		}
	}

	// create Content: musicians, track list, and lyrics
	$theContent = $filter->createTracklistHtml($tracklist)
		. "\n" .  $filter->createLineupHtml($row->besetzung)
		. "\n" .  $filter->createAccordionHtmlForLyrics($lyrics);

	// create post data for album
	$postParams = [
		'status' => 'publish',
		'title'   => trim($row->albumtitel),
		'content' => $theContent,
		// custom meta/rest fields
		'_bufu_artist_albumRelease' => trim($row->jahr),
		'_bufu_artist_albumLabel'   => trim($row->label),
		'_bufu_artist_shopUrl'      => str_replace('http://', 'https://', trim($row->shoplink)),
	];

	if (array_key_exists($row->artistid, $artistIdMapping)) {
		$postParams['_bufu_artist_selectArtist'] = $artistIdMapping[$row->artistid];
	}

	$response = $wpApi->savePost($postParams, $wpPostId);

	if (array_key_exists('id', $response) && array_key_exists('type', $response) && $response['type'] === $config['target']['wpapi']['endpoint']) {
		// save post id to state mapping
		$idMapping[$row->id] = intval($response['id'], 10);
		echo '.';
	}
	else {
		if (array_key_exists('code', $response)) {
			// save errors?
			var_dump($response, $row);
			exit();
		}

		echo 'E';
	}
}

// save state and mappings of this run
$now     = new \DateTime();
$diffSec = $now->getTimestamp() - $startedAt->getTimestamp();
$count   = count($albumRrows);

$stateInfo['albums'] = $idMapping;
$stateInfo['__state']['albums']['lastrun'] = "started: " . $startedAt->format(DATE_ISO8601) . " (took {$diffSec} sec)";
$stateInfo['__state']['albums']['count']   = $count;
$state->saveState($stateInfo);

echo PHP_EOL . "Done! Processed {$count} albums, took ${diffSec} seconds." . PHP_EOL;