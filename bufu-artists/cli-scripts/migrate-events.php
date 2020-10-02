<?php
/**
 * Script to migrate concerts and events data from old site to new WP site
 */

require_once 'DBTable.php';
require_once 'Filter.php';
require_once 'State.php';
require_once 'WPApi.php';

// config
$config  = [
	'source' => [
		'mysql' => [
			'hostname' => 'verlag_db',
			'username' => 'root',
			'password' => 'rootpassword',
			'db'       => 'verlag_source_test'
		],
		'table_name' => 'data_konzerte',
	],
	'target' => [
		'wpapi' => [
			'url'      => 'https://bufu-verlag-wp.test/wp-json/tribe/events/v1',
			'endpoint' => 'events',

			// requires Basic-Auth plugin to be active
			// which should not be the case on a production setup!
			// Use for development/ setup purpose only
			'authorization' => [
				'username' => 'admin',
				'password' => 'password',
			]
		]
	],
	'startFrom' => '- 1 year', // modify for production!
	'skipExisting' => false,
];

$startedAt = new \DateTime();

// get state of last run and mappings, start from there
$state = new State();
$stateInfo = $state->loadState();

// init state fields required for news
if (!array_key_exists('events', $stateInfo)) {
	$stateInfo['events'] = [];
	$stateInfo['__state']['events'] = [
		'lastrun' => null,
		'count'   => null,
	];
}
if (!array_key_exists('artists', $stateInfo)) {
	$stateInfo['artists'] = [];
}

// define item properties
$post = new stdClass();
$post->id = null;
$post->artist_id = null;
$post->datum = null;
$post->tipp = null;
$post->plz = null;
$post->ort = null;
$post->location = null;
$post->titel = null;
$post->beschreibung = null;
$post->ticketlink = null;
$post->orderlink = null;

$table = new DBTable($config['source']['table_name'], $config['source']['mysql']);
$table->setHydrateObject($post);

$wpApi = new WPApi($config['target']['wpapi']);

$filter = new Filter();

// where to start importing events
$from = new DateTime();
$from->modify($config['startFrom']);

// get id mapping from state
$idMapping = $stateInfo['events'];

$errors = [];
$rows = $table->getRowsNewerThan($from, 'datum');
foreach ($rows as $row) {
	/** @var $row stdClass */

	// if the id is known from mapping, skip to next
	$wpPostId = null;
	if (array_key_exists($row->id, $idMapping)) {
		if ($config['skipExisting']) {
			echo "s";
			continue;
		}
		$wpPostId = $idMapping[$row->id];
	}

	$date = $filter->getDateTimeFromTimestamp($row->datum);

	// create post data
	$postParams = [
		'status'      => 'publish',
		'title'       => $row->titel,
		'start_date'  => $date->format("Y-m-d H:i:s"),
		'end_date'    => $date->modify("+ 4 hours")->format("Y-m-d H:i:s"),
		'website'     => ((int) $row->orderlink === 1 && $row->ticketlink !== 'http://') ? $row->ticketlink : '',
		'venue'       => [
			'venue' => $row->location,
			'city'  => $row->ort,
			'zip'   => $row->plz
		],
		'featured' => ((int) $row->tipp === 1) ? true : false,
		'meta' => [
			'bufu_artist_selectArtist' => (array_key_exists($row->artist_id, $stateInfo['artists'])) ? $stateInfo['artists'][$row->artist_id] : '',
		],
	];

	$response = $wpApi->savePost($postParams, $wpPostId);

	if (array_key_exists('id', $response)) {
		// save post id to state mapping
		$idMapping[$row->id] = intval($response['id'], 10);
		echo '.';
	}
	else {
		if (array_key_exists('code', $response)) {
			$errors[] = $response;
//			var_dump($postParams, $response, $row);
//			exit();
		}

		echo 'E';
	}
}

// save state and mappings of this run
$now     = new \DateTime();
$diffSec = $now->getTimestamp() - $startedAt->getTimestamp();
$count   = count($rows);

$stateInfo['events'] = $idMapping;
$stateInfo['__state']['events']['lastrun'] = "started: " . $startedAt->format(DATE_ISO8601) . " (took {$diffSec} sec)";
$stateInfo['__state']['events']['count']   = $count;
$state->saveState($stateInfo);

echo PHP_EOL . "Done! Processed {$count} events, took ${diffSec} seconds." . PHP_EOL;

$nuMErrors = count($errors);
if ($nuMErrors > 0) {
	echo "{$nuMErrors} Errors!" . PHP_EOL;
	var_dump($errors);
}