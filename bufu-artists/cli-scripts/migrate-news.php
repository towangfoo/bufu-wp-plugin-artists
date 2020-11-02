<?php
/**
 * Script to migrate news posts data from old site to new WP site
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
		'table_name' => [
			'meldung' => 'data_meldung',
			'planung' => 'data_planung',
		],
	],
	'target' => [
		'wpapi' => [
			'url'      => 'https://bufu-verlag-wp.test/wp-json/wp/v2',
			'endpoint' => 'posts',

			// requires Basic-Auth plugin to be active
			// which should not be the case on a production setup!
			// Use for development/ setup purpose only
			'authorization' => [
				'username' => 'admin',
				'password' => 'password',
			]
		],
		'categories' => [
			'meldung' => 1,
			'planung' => 5,
		]
	]
];

$startedAt = new \DateTime();

// get state of last run and mappings, start from there
$state = new State();
$stateInfo = $state->loadState();

// init state fields required for news
if (!array_key_exists('posts', $stateInfo)) {
	$stateInfo['posts'] = [];
	$stateInfo['__state']['posts'] = [
		'lastrun' => null,
		'count'   => null,
	];
}

// define item properties
$post = new stdClass();
$post->id = null;
$post->headline = null;
$post->text = null;
$post->added = null;

$wpApi = new WPApi($config['target']['wpapi']);

$filter = new Filter();

// get posts id mapping from state
$idMapping = $stateInfo['posts'];


// meldung
$tableMeldung = new DBTable($config['source']['table_name']['meldung'], $config['source']['mysql']);
$tableMeldung->setHydrateObject($post);
$rows1 = $tableMeldung->getRows();
$mappingPrefix = 'meldung_';
$categoryId = $config['target']['categories']['meldung'];
foreach ($rows1 as $row) {
	/** @var $row stdClass */

	// if the id is known from mapping, update the post instead
	$wpPostId = null;
	// keep backwards compat for existing mapping
	if (array_key_exists($row->id, $idMapping)) {
		$wpPostId = $idMapping[$row->id];
	}
	else if (array_key_exists($mappingPrefix.$row->id, $idMapping)) {
		$wpPostId = $idMapping[$mappingPrefix.$row->id];
	}

	// create post data
	$postParams = [
		'status'   => 'publish',
		'title'    => $row->headline,
		'content'  => $filter->createParagraphs($row->text),
		'date'     => $filter->getFormattedDateFromTimestamp($row->added),
		'date_gmt' => $filter->getFormattedDateFromTimestamp($row->added, new DateTimeZone("UTC")),
		'categories' => [ $categoryId ]
	];

	$response = $wpApi->savePost($postParams, $wpPostId);

	if (array_key_exists('id', $response) && array_key_exists('type', $response) && $response['type'] === 'post') {
		// save post id to state mapping
		$idMapping[$mappingPrefix.$row->id] = intval($response['id'], 10);
		echo '.';
	}
	else {
		if (array_key_exists('code', $response)) {
			// save errors?
			var_dump($postParams, $response, $row);
			exit();
		}

		echo 'E';
	}
}
// planung
$tablePlanung = new DBTable($config['source']['table_name']['planung'], $config['source']['mysql']);
$tablePlanung->setHydrateObject($post);
$rows2 = $tablePlanung->getRows();
$mappingPrefix = 'planung_';
$categoryId = $config['target']['categories']['planung'];
foreach ($rows2 as $row) {
	/** @var $row stdClass */

	// if the id is known from mapping, update the post instead
	$wpPostId = null;
	if (array_key_exists($mappingPrefix.$row->id, $idMapping)) {
		$wpPostId = $idMapping[$mappingPrefix.$row->id];
	}

	// create post data
	$postParams = [
		'status'   => 'publish',
		'title'    => $row->headline,
		'content'  => $filter->createParagraphs($row->text),
		'date'     => $filter->getFormattedDateFromTimestamp($row->added),
		'date_gmt' => $filter->getFormattedDateFromTimestamp($row->added, new DateTimeZone("UTC")),
		'categories' => [ $categoryId ]
	];

	$response = $wpApi->savePost($postParams, $wpPostId);

	if (array_key_exists('id', $response) && array_key_exists('type', $response) && $response['type'] === 'post') {
		// save post id to state mapping
		$idMapping[$mappingPrefix.$row->id] = intval($response['id'], 10);
		echo '.';
	}
	else {
		if (array_key_exists('code', $response)) {
			// save errors?
			var_dump($postParams, $response, $row);
			exit();
		}

		echo 'E';
	}
}

// save state and mappings of this run
$now     = new \DateTime();
$diffSec = $now->getTimestamp() - $startedAt->getTimestamp();
$count   = count($rows1) + count($rows2);

$stateInfo['posts'] = $idMapping;
$stateInfo['__state']['posts']['lastrun'] = "started: " . $startedAt->format(DATE_ISO8601) . " (took {$diffSec} sec)";
$stateInfo['__state']['posts']['count']   = $count;
$state->saveState($stateInfo);

echo PHP_EOL . "Done! Processed {$count} posts, took ${diffSec} seconds." . PHP_EOL;