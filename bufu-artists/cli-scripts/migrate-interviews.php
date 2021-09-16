<?php
/**
 * Script to migrate interview data from old site to new WP site
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
			'data'      => $settings['migrate_interviews']['source']['table_names']['data'],
			'relations' => $settings['migrate_interviews']['source']['table_names']['relations'],
		]
	],
	'target' => [
		'wpapi' => [
			'url'      => $settings['migrate_interviews']['target']['url'],
			'endpoint' => $settings['migrate_interviews']['target']['endpoint'],

			// requires Basic-Auth plugin to be active
			// which should not be the case on a production setup!
			// Use for development/ setup purpose only
			'authorization' => [
				'username' => $settings['target']['wpapi_auth']['username'],
				'password' => $settings['target']['wpapi_auth']['password'],
			]
		]
	],
	'skipExisting' => $settings['migrate_interviews']['skip_existing'],
];

$startedAt = new \DateTime();

// get state of last run and mappings, start from there
$state = new State();
$stateInfo = $state->loadState();

// init state fields required for artists
if (!array_key_exists('interviews', $stateInfo)) {
	$stateInfo['interviews'] = [];
	$stateInfo['__state']['interviews'] = [
		'lastrun' => null,
		'count'   => null,
	];
}


// define properties
$interview = new stdClass();
$interview->id = null;
$interview->artistid = null;
$interview->titel = null;
$interview->text = null;
$interview->quelle = null;

$relationObj = new stdClass();
$relationObj->artistid = null;
$relationObj->interviewsid = null;

$dataTable = new DBTable($config['source']['table_name']['data'], $config['source']['mysql']);
$dataTable->setHydrateObject($interview);

$relationTable = new DBTable($config['source']['table_name']['relations'], $config['source']['mysql']);
$relationTable->setHydrateObject($relationObj);

$wpApi = new WPApi($config['target']['wpapi']);

$filter = new Filter();

// get album id mapping from state
$idMapping = $stateInfo['interviews'];

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
	$artistIdRows = $relationTable->getRows("SELECT * FROM %TABLE% WHERE interviewsid = {$row->id} LIMIT 1");
	$artistId = current($artistIdRows)->artistid;

	// filter/ format content
	$theContent = $filter->convertMarkdown($row->text);
	$theContent = $filter->createParagraphs($theContent);

	// create post data
	$postParams = [
		'status'  => 'publish',
		'title'   => trim($row->titel),
		'content' => $theContent,
		// custom meta/rest fields
		'_bufu_artist_interview_source' => trim($row->quelle)
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

$stateInfo['interviews'] = $idMapping;
$stateInfo['__state']['interviews']['lastrun'] = "started: " . $startedAt->format(DATE_ISO8601) . " (took {$diffSec} sec)";
$stateInfo['__state']['interviews']['count']   = $count;
$state->saveState($stateInfo);

echo PHP_EOL . "Done! Processed {$count} interviews, took ${diffSec} seconds." . PHP_EOL;