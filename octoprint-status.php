#!/usr/bin/php
<?php

/**
 * @param String $url
 * @return String object
 */
function doApiRequest($requestUrl) {

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $requestUrl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	$output = curl_exec($ch);
	if ($output === false) {
		die(curl_error($ch) . PHP_EOL);
	}
	curl_close($ch);
	return $output;
}

/**
 * @see http://docs.octoprint.org/en/master/api/printer.html#retrieve-the-current-printer-state
 * @param String $url
 * @param String $apiKey
 * @return stdClass object
 */
function getPrinterStatus($url, $apiKey) {

	$requestUrl = $url . 'api/printer?history=true&limit=1&apikey=' . $apiKey;
	$response = doApiRequest($requestUrl);
	$jsonResponse = json_decode($response);
	if (null === $jsonResponse) {
		die($response);
	}
	return $jsonResponse;
}

/**
 * @see http://docs.octoprint.org/en/master/api/job.html#retrieve-information-about-the-current-job
 * @param String $url
 * @param String $apiKey
 * @return stdClass object
 */
function getPrinterProgress($url, $apiKey) {

	$requestUrl = $url . 'api/job?apikey=' . $apiKey;
	$response = doApiRequest($requestUrl);
	return json_decode($response);
}


$longOps = array('url:', 'apikey:');
$options = getopt('', $longOps);

if (count($options) < 2) {
	die('usage octoprint-status.php apikey=XXXXX url=YYYY' . PHP_EOL);
}
if (false === function_exists('curl_init')) {
	die('php5-curl is required by this script and not installed');
}


$url = $options['url'];
$apiKey = $options['apikey'];

$progressObject = getPrinterProgress($url, $apiKey);
$printerStatusObject = getPrinterStatus($url, $apiKey);


//var_dump($progressObject);
//var_dump($printerStatusObject);
$temperatures = sprintf('B %d°C E %d°C'
	, $printerStatusObject->temperature->bed->actual
	, $printerStatusObject->temperature->tool0->actual);

if ($printerStatusObject->state->flags->printing === true) {
	echo sprintf('Printing %d%% Time left %d min %s'
		, round($progressObject->progress->completion)
		, round($progressObject->progress->printTimeLeft / 60, 2)
		, $temperatures
	);
} else {
	echo 'Ready ' . $temperatures;
}
