#!/usr/bin/php
<?php

require_once(dirname(__FILE__) . '/../search.inc.php');
require_once(dirname(__FILE__) . '/dbmgr/synomovie.php');
require_once(dirname(__FILE__) . '/dbmgr/synotvshow.php');
require_once(dirname(__FILE__) . '/dbmgr/atmovie.php');

define('PLUGINID', 			'com.synology.Synovideodb');

$DEFAULT_TYPE = 'movie';
$DEFAULT_LANG = 'enu';

$SUPPORTED_TYPE = array('movie', 'tvshow', 'tvshow_episode');
$SUPPORTED_PROPERTIES = array('title');

function ConvertToAPILang($lang)
{
	static $map = array(
		'enu' => 'enu',
		'cht' => 'cht'
	);

	$ret = isset($map[$lang]) ? $map[$lang] : NULL;
	return $ret;
}

function RecognizePrefix($id) {
	$prefix = substr($id, 0, 3);
	switch ($prefix) {
	case 'im_':
		return 'imdb';
	case 'at_':
		return 'atmovie';
	}
}

function Process($input, $lang, $type, $limit, $search_properties, $allowguess)
{
	global $DATA_TEMPLATE;
	error_log(print_r( $input, true), 3, "/var/packages/VideoStation/target/plugins/syno_themoviedb/my-errors.log");
	if( isset($input['doubandb']) )
		return array();
	//Init
	$title 	= $input['title'];
	$year 	= ParseYear($input['original_available']);
	$lang 	= ConvertToAPILang($lang);
	$season  = $input['season'];
	$episode = $input['episode'];
	if (!$lang) {
		return array();
	}

	//year
	if (isset($input['extra']) && count($input['extra']) > 0) {
		$pluginid = array_shift($input['extra']);
		if (!empty($pluginid['tvshow']['original_available'])) {
			$year = ParseYear($pluginid['tvshow']['original_available']);
		}
	}

	//Set
	$cache_dir = GetPluginDataDirectory(PLUGINID);

	if ("movie" == $type) {
		//Get videodb
		$videodb = new Synomovie();
		$videodb->Init(PLUGINID, $cache_dir);

		//Search
		$query_data = array();
		$titles = GetGuessingList($title, $allowguess);
		foreach ($titles as $query) {
			if (empty($query)) {
				continue;
			}
			if ($year) {
				$query = "{$query} {$year}";
			}
			$query_data = $videodb->Query($query, $lang, $limit);
			if (0 < count($query_data)) {
				break;
			}
		}

		//Get metadata
		if (count($query_data) > 0) {

			//If id comes from atmovie, we should get metadata from atmovie website directly
			$dbname = RecognizePrefix($query_data[0]['id']);
			if ('atmovie' == $dbname) {
				$videodb = new Atmovie();
				$videodb->Init(PLUGINID, $cache_dir);
			}

			return $videodb->GetMovieMetadata($query_data, $lang, $DATA_TEMPLATE);
		}

		//Get metadata
		return $videodb->GetMovieMetadata($query_data, $lang, $DATA_TEMPLATE);

	} else {
		//Get videodb
		$videodb = new Synotvshow();
		$videodb->Init(PLUGINID, $cache_dir);

		//Search
		$query_data = array();
		$titles = GetGuessingList($title, $allowguess);
		foreach ($titles as $query) {
			if (empty($query)) {
				continue;
			}
			if ($year) {
				$query = "{$query} {$year}";
			}
			$query_data = $videodb->Query($query, $lang, $limit);
			if (0 < count($query_data)) {
				break;
			}
		}

		//Get metadata
		return $videodb->GetTvshowMetadata($query_data, $lang, $season, $episode, $type, $DATA_TEMPLATE);
	}
}

PluginRun('Process');

?>
