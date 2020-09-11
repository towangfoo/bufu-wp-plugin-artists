<?php
/**
 * Script to migrate artist data from old site to new WP site
 */

require_once 'DBTable.php';
require_once 'Filter.php';
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

// TODO: get state of last run and mappings, start from there

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

$idMapping = [];
foreach ($table->getRows() as $row) {
	/** @var $row stdClass */

	// TODO: transform
	$postParams = [
		'status' => 'publish',
		'title'   => $row->artistname,
		'content' => $filter->createParagraphs($row->profiltext),
		'meta' => [
			'bufu_artist_sortBy'  => $row->sortierung,
			'bufu_artist_website' => $row->homepage,
		]
	];

	$response = $wpApi->savePost($postParams);

	var_dump($response);
	exit('testing');

	// TODO: create persistent id mapping from old to new
	$idMapping[] = [
		'artist_id' => $row->id,
		'post_id'   => $post->ID,
	];
}

// TODO: save state and mappings of this run
var_dump($idMapping);