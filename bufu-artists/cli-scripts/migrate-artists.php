<?php
/**
 * Script to migrate artist data from old site to new WP site
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
		'table_name' => $settings['migrate_artists']['source']['table_name'],
	],
	'target' => [
		'wpapi' => [
			'url'      => $settings['migrate_artists']['target']['url'],
			'endpoint' => $settings['migrate_artists']['target']['endpoint'],

			// requires Basic-Auth plugin to be active
			// which should not be the case on a production setup!
			// Use for development/ setup purpose only
			'authorization' => [
				'username' => $settings['target']['wpapi_auth']['username'],
				'password' => $settings['target']['wpapi_auth']['password'],
			]
		]
	],
	'skipExisting' => $settings['migrate_artists']['skip_existing'],
];

$startedAt = new \DateTime();

// get state of last run and mappings, start from there
$state = new State();
$stateInfo = $state->loadState();

// init state fields required for artists
if (!array_key_exists('artists', $stateInfo)) {
	$stateInfo['artists'] = [];
	$stateInfo['__state']['artists'] = [
		'lastrun' => null,
		'count'   => null,
	];
}


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
		if ($config['skipExisting']) {
			echo "s";
			continue;
		}

		$wpPostId = $idMapping[$row->id];
	}

	// create post data
	$postParams = [
		'status' => 'publish',
		'title'   => $row->artistname,
		'content' => $filter->createParagraphs($row->profiltext),
		// custom meta/rest fields
		'_bufu_artist_sortBy'  => $row->sortierung,
		'_bufu_artist_website' => $row->homepage,
	];

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
$count   = count($rows);

$stateInfo['artists'] = $idMapping;
$stateInfo['__state']['artists']['lastrun'] = "started: " . $startedAt->format(DATE_ISO8601) . " (took {$diffSec} sec)";
$stateInfo['__state']['artists']['count']   = $count;
$state->saveState($stateInfo);

echo PHP_EOL . "Done! Processed {$count} artists, took ${diffSec} seconds." . PHP_EOL;