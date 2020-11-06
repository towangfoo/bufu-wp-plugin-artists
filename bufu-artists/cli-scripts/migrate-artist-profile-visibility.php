<?php
/**
 * Script to migrate artist profile visibility from old site to new WP site
 */

require_once 'DBTable.php';
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
			'url'      => $settings['migrate_artist_profile_visibility']['target']['url'],
			'endpoint' => $settings['migrate_artist_profile_visibility']['target']['endpoint'],

			// requires Basic-Auth plugin to be active
			// which should not be the case on a production setup!
			// Use for development/ setup purpose only
			'authorization' => [
				'username' => $settings['target']['wpapi_auth']['username'],
				'password' => $settings['target']['wpapi_auth']['password'],
			]
		]
	],
];

$startedAt = new \DateTime();

// get state of last run and mappings, start from there
$state = new State();
$stateInfo = $state->loadState();

// init state fields required for artists
if (!array_key_exists('artists', $stateInfo)) {
	$stateInfo['artists'] = [];
}
if (!array_key_exists('artist_profile_visibility', $stateInfo)) {
	$stateInfo['__state']['artist_profile_visibility'] = [
		'lastrun' => null,
		'count'   => null,
	];
}


// define item properties
$artist = new stdClass();
$artist->id = null;
$artist->showinprofile = null;

$table = new DBTable($config['source']['table_name'], $config['source']['mysql']);
$table->setHydrateObject($artist);

$wpApi = new WPApi($config['target']['wpapi']);

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

	if ( !$wpPostId ) {
		echo 's';
		continue;
	}

	// post data
	$postParams = [
		'_bufu_artist_profileVisible' => ($row->showinprofile === "1") ? "yes" : "no",
	];

	$response = $wpApi->savePost($postParams, $wpPostId);

	if (array_key_exists('id', $response) && array_key_exists('type', $response) && $response['type'] === $config['target']['wpapi']['endpoint']) {
		echo '.';
	}
	else {
		if (array_key_exists('code', $response)) {
			if ($response['code'] === 'rest_post_invalid_id') {
				echo '-';
				continue;
			}
			else {
				// save errors?
//				var_dump($response, $row);
//				exit();
			}
		}

		echo 'E';
	}
}

// save state and mappings of this run
$now     = new \DateTime();
$diffSec = $now->getTimestamp() - $startedAt->getTimestamp();
$count   = count($rows);

$stateInfo['__state']['artist_profile_visibility']['lastrun'] = "started: " . $startedAt->format(DATE_ISO8601) . " (took {$diffSec} sec)";
$stateInfo['__state']['artist_profile_visibility']['count']   = $count;
$state->saveState($stateInfo);

echo PHP_EOL . "Done! Processed {$count} artists, took ${diffSec} seconds." . PHP_EOL;