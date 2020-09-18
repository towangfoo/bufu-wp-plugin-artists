<?php
/**
 * Script to migrate artist data from old site to new WP site
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
		'table_name' => 'data_artist',
	],
	'target' => [
		'wpapi' => [
			'url'      => 'https://bufu-verlag-wp.test/wp-json/wp/v2',
			'endpoint' => 'bufu_artist',

			// requires Basic-Auth plugin to be active
			// which should not be the case on a production setup!
			// Use for development/ setup purpose only
			'authorization' => [
				'username' => 'admin',
				'password' => 'password',
			]
		]
	]
];

$startedAt = new \DateTime();

// get state of last run and mappings, start from there
$state = new State();
$stateInfo = $state->loadState();


// define item properties
$artist = new stdClass();
$artist->id = null;
$artist->artistname = null;
$artist->sortierung = null;
$artist->profiltext = null;
$artist->homepage = null;
$artist->shoplinks = null;

$table = new DBTable($config['source']['table_name'], $config['source']['mysql']);
$table->setHydrateObject($artist);

$wpApi = new WPApi($config['target']['wpapi']);

$filter = new Filter();

// get artist id mapping from state
$idMapping = $stateInfo['artists'];

$rows = $table->getRows();
foreach ($rows as $row) {
	/** @var $row stdClass */

	// if the id is known from mapping, update the post instead
	$wpPostId = null;
	if (array_key_exists($row->id, $idMapping)) {
		$wpPostId = $idMapping[$row->id];
	}

	// create post data
	$postParams = [
		'status' => 'publish',
		'title'   => $row->artistname,
		'content' => $filter->createParagraphs($row->profiltext),
		// custom meta/rest fields
		'bufu_artist_sortBy'  => $row->sortierung,
		'bufu_artist_website' => $row->homepage,
	];

	$response = $wpApi->savePost($postParams, $wpPostId);

	var_dump($response);

	if (array_key_exists('id', $response) && array_key_exists('type', $response) && $response['type'] === $config['target']['wpapi']['endpoint']) {
		// save post id to state mapping
		$idMapping[$row->id] = intval($response['id'], 10);
		echo '.';
	}
	else {
		echo 'E';
	}
}

// save state and mappings of this run
$now     = new \DateTime();
$diffSec = $startedAt->diff($now, true)->s;

$count = count($rows);

$stateInfo['artists'] = $idMapping;
$stateInfo['__state']['artists']['lastrun'] = "started: " . $startedAt->format(DATE_ISO8601) . " (took {$diffSec} sec)";
$stateInfo['__state']['artists']['count']   = $count;
$state->saveState($stateInfo);

echo PHP_EOL . "Done! Processed {$count} artists, took ${diffSec} seconds." . PHP_EOL;