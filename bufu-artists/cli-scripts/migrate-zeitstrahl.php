<?php
/**
 * Script to migrate chronicle posts data from old site to new WP site
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
			'zeitstrahl' => $settings['migrate_zeitstrahl']['source']['table_names']['zeitstrahl'],
		],
	],
	'target' => [
		'wpapi' => [
			'url'      => $settings['migrate_zeitstrahl']['target']['url'],
			'endpoint' => $settings['migrate_zeitstrahl']['target']['endpoint'],

			// requires Basic-Auth plugin to be active
			// which should not be the case on a production setup!
			// Use for development/ setup purpose only
			'authorization' => [
				'username' => $settings['target']['wpapi_auth']['username'],
				'password' => $settings['target']['wpapi_auth']['password'],
			]
		]
	],
	'skipExisting' => $settings['migrate_zeitstrahl']['skip_existing'],
	'parentPageId' => $settings['migrate_zeitstrahl']['parent_page_id'],
];

$startedAt = new \DateTime();

// get state of last run and mappings, start from there
$state = new State();
$stateInfo = $state->loadState();

// init state fields required for news
if (!array_key_exists('zeitstrahl', $stateInfo)) {
	$stateInfo['zeitstrahl'] = [];
	$stateInfo['__state']['zeitstrahl'] = [
		'lastrun' => null,
		'count'   => null,
	];
}

// define item properties
$post = new stdClass();
$post->id = null;
$post->headline = null;
$post->text = null;
$post->jahr = null;

$wpApi = new WPApi($config['target']['wpapi']);

$filter = new Filter();

// get posts id mapping from state
$idMapping = $stateInfo['zeitstrahl'];

// get one entry only per year
$query = "SELECT * from %TABLE% AS t1 where id = (SELECT MAX(id) from %TABLE% AS t2 WHERE t1.jahr = t2.jahr AND t2.headline != '') ORDER BY t1.jahr ASC";


$tableZeitstrahl = new DBTable($config['source']['table_name']['zeitstrahl'], $config['source']['mysql']);
$tableZeitstrahl->setHydrateObject($post);
$rows1 = $tableZeitstrahl->getRows($query);

foreach ($rows1 as $row) {
	/** @var $row stdClass */

	// if the id is known from mapping, update the post instead
	$wpPostId = null;
	// keep backwards compat for existing mapping
	if (array_key_exists($row->id, $idMapping)) {
		$wpPostId = $idMapping[$row->id];
	}
	if ( $wpPostId && $config['skipExisting'] ) {
		echo "s";
		continue;
	}

	$title = $row->jahr . ': ' . $row->headline;

	// prepare content
	$content = $row->text;
	$content = $filter->createParagraphs($content);
	$content = $filter->convertMarkdown($content);

	// create post data
	$postParams = [
		'status'   => 'publish',
		'title'    => $title,
		'content'  => $content,
		'parent'   => ($config['parentPageId']) ? $config['parentPageId'] : null,
	];

	$response = $wpApi->savePost($postParams, $wpPostId);

	if (array_key_exists('id', $response) && array_key_exists('type', $response) && $response['type'] === 'page') {
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

if (array_key_exists('parentPageId', $config) && is_int($config['parentPageId'])) {
	$wpApi->savePost( ['_bufu_artist_pageShowChildren' => '1'], $config['parentPageId'] );
}


// save state and mappings of this run
$now     = new \DateTime();
$diffSec = $now->getTimestamp() - $startedAt->getTimestamp();
$count   = count($rows1);

$stateInfo['zeitstrahl'] = $idMapping;
$stateInfo['__state']['zeitstrahl']['lastrun'] = "started: " . $startedAt->format(DATE_ISO8601) . " (took {$diffSec} sec)";
$stateInfo['__state']['zeitstrahl']['count']   = $count;
$state->saveState($stateInfo);

echo PHP_EOL . "Done! Processed {$count} pages, took ${diffSec} seconds." . PHP_EOL;