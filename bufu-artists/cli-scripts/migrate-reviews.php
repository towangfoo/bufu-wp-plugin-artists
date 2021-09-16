<?php
/**
 * Script to migrate review data from old site to new WP site
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
			'data'      => $settings['migrate_reviews']['source']['table_names']['data'],
			'relations' => $settings['migrate_reviews']['source']['table_names']['relations'],
		]
	],
	'target' => [
		'wpapi' => [
			'url'      => $settings['migrate_reviews']['target']['url'],
			'endpoint' => $settings['migrate_reviews']['target']['endpoint'],

			// requires Basic-Auth plugin to be active
			// which should not be the case on a production setup!
			// Use for development/ setup purpose only
			'authorization' => [
				'username' => $settings['target']['wpapi_auth']['username'],
				'password' => $settings['target']['wpapi_auth']['password'],
			]
		]
	],
	'skipExisting' => $settings['migrate_reviews']['skip_existing'],
];

$startedAt = new \DateTime();

// get state of last run and mappings, start from there
$state = new State();
$stateInfo = $state->loadState();

// init state fields required for artists
if (!array_key_exists('reviews', $stateInfo)) {
	$stateInfo['reviews'] = [];
	$stateInfo['__state']['reviews'] = [
		'lastrun' => null,
		'count'   => null,
	];
}


// define properties
$review = new stdClass();
$review->id = null;
$review->typ = null;
$review->artistid = null;
$review->headline = null;
$review->text = null;
$review->zeitung = null;
$review->autor = null;
$review->datum = null;

$relationObj = new stdClass();
$relationObj->artistid = null;
$relationObj->rezensionenid = null;

$dataTable = new DBTable($config['source']['table_name']['data'], $config['source']['mysql']);
$dataTable->setHydrateObject($review);

$relationTable = new DBTable($config['source']['table_name']['relations'], $config['source']['mysql']);
$relationTable->setHydrateObject($relationObj);

$wpApi = new WPApi($config['target']['wpapi']);

$filter = new Filter();

// get album id mapping from state
$idMapping = $stateInfo['reviews'];

// get artist mapping
$artistIdMapping = $stateInfo['artists'];

$rows = $dataTable->getRows();
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

	// get artist id from the help table
	$artistIdRows = $relationTable->getRows("SELECT * FROM %TABLE% WHERE rezensionenid = {$row->id} LIMIT 1");
	$artistId = current($artistIdRows)->artistid;

	// filter/ format content
	$theContent = $filter->convertMarkdown($row->text);
	$theContent = $filter->createParagraphs($theContent);

	// create post data
	$postParams = [
		'status'  => 'publish',
		'title'   => trim($row->headline),
		'content' => $theContent,
		'date'    => $filter->getFormattedDateFromTimestamp($row->datum),
		// custom meta/rest fields
		'_bufu_artist_review_type'   => trim($row->typ),
		'_bufu_artist_review_source' => trim($row->zeitung),
		'_bufu_artist_review_author' => trim($row->autor),
	];

	if (array_key_exists($artistId, $artistIdMapping)) {
		$postParams['_bufu_artist_selectArtist'] = $artistIdMapping[$artistId];
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
$count   = count($rows);

$stateInfo['reviews'] = $idMapping;
$stateInfo['__state']['reviews']['lastrun'] = "started: " . $startedAt->format(DATE_ISO8601) . " (took {$diffSec} sec)";
$stateInfo['__state']['reviews']['count']   = $count;
$state->saveState($stateInfo);

echo PHP_EOL . "Done! Processed {$count} reviews, took ${diffSec} seconds." . PHP_EOL;