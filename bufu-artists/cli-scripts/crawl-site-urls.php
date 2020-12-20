<?php
/**
 * Script to crawls for all site URLs
 */

require_once 'State.php';
require_once 'Crawler.php';


$startedAt = new \DateTime();

// get state of last run and mappings, start from there
$state = new State();
$stateInfo = $state->loadState();

// init state fields required
if (!array_key_exists('__state', $stateInfo)) {
	$stateInfo['__state'] = [];
}
if (!array_key_exists('urls', $stateInfo['__state'])) {
	$stateInfo['__state']['urls'] = [
		'lastrun' => null,
		'count'   => null,
	];
}
$crawler = new Crawler();

$crawler->setUrlExcludePatters([
	'~aktuell\/konzerte\/artist\/[\d]+\/details\/[\d]+?$~', // konzerte von kuenstlern
	'~konsum\.buschfunk\.com~', // konsum
	'~kontakt\/newsletterarchiv\/details~', // alte newsletter
	'~kuenstler/diskografie/[^#]+#[\d]+$~', // fragment links in diskografien
	'~mailto\:~', // of cause
	'~buschfunk\.com\/media\/~' // alle media links
]);

$urls = $crawler->extractUrls('https://verlag.buschfunk.com');

$outfile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'urls.txt';
file_put_contents($outfile, join("\n", $urls));

// save state and mappings of this run
$now     = new \DateTime();
$diffSec = $now->getTimestamp() - $startedAt->getTimestamp();
$numUrls = count($urls);

$stateInfo['__state']['urls']['lastrun'] = "started: " . $startedAt->format(DATE_ISO8601) . " (took {$diffSec} sec)";
$stateInfo['__state']['urls']['count']   = $numUrls;
$state->saveState($stateInfo);

echo PHP_EOL . "Done! Extracted {$numUrls} URLs, took ${diffSec} seconds." . PHP_EOL;