#!/usr/bin/php
<?php

require_once(dirname(__FILE__) . '/../util_themoviedb.php');
require_once(dirname(__FILE__) . '/../search.inc.php');
require_once(dirname(__FILE__) . '/douban.php');

$SUPPORTED_TYPE = array('movie', 'tvshow', 'tvshow_episode');
$SUPPORTED_PROPERTIES = array('title');


//=========================================================
// douban begin
//=========================================================
function ProcessDouban($input, $lang, $type, $limit, $search_properties, $allowguess, $id)
{
	$title 	= $input['title'];
	$year 	= ParseYear($input['original_availa ble']);
	$lang 	= ConvertToAPILang($lang);
	if (!$lang) {
		return array();
	}

	//$query_data = json_decode( HTTPGETReques t('http: //api.9hut.cn/douban.php?q=' . $title ), true );
	$query_data = getRequest('https://m.douban.com/search/?query=' . $title . '&type=movie');
	$detailPath = array();
	preg_match_all('/\/movie\/subject\/[0-9]+/', $query_data, $detailPath);

	//Get metadata
	return GetMetadataDouban($detailPath[0], $lang);
}
//=========================================================
// douban end
//=========================================================


//=========================================================
// tmdb begin
//=========================================================
function GetMovieInfoTMDB($movie_data, $data)
{
	$data['title']				 	= $movie_data->title;
	$data['original_title']			= $movie_data->original_title;
	$data['tagline'] 				= $movie_data->tagline;
	$data['original_available'] 	= $movie_data->release_date;
	$data['summary'] 				= $movie_data->overview;

	foreach ($movie_data->genres as $item) {
		if (!in_array($item->name, $data['genre'])) {
			array_push($data['genre'], $item->name);
		}
	}

	//extra
	$data['extra'] = array();
	$data['extra'][PLUGINID] = array('reference' => array());
	$data['extra'][PLUGINID]['reference']['themoviedb'] = $movie_data->id;
	if (isset($movie_data->imdb_id)) {
		$data['extra'][PLUGINID]['reference']['imdb'] = $movie_data->imdb_id;
	}
	if ((float) $movie_data->vote_average) {
		$data['extra'][PLUGINID]['rating'] = array('themoviedb' => (float) $movie_data->vote_average);
	}
	if (isset($movie_data->poster_path)) {
		$data['extra'][PLUGINID]['poster'] = array(BANNER_URL . $movie_data->poster_path);
	}
	if (isset($movie_data->backdrop_path)) {
		$data['extra'][PLUGINID]['backdrop'] = array(BACKDROUP_URL . $movie_data->backdrop_path);
	}
	if (isset($movie_data->belongs_to_collection)) {
		$data['extra'][PLUGINID]['collection_id'] = array('themoviedb' => $movie_data->belongs_to_collection->id);
	}

	return $data;
}

function GetCastInfoTMDB($cast_data, $data)
{
	// actor
	foreach ($cast_data->cast as $item) {
		if (!in_array($item->name, $data['actor'])) {
			array_push($data['actor'], $item->name);
		}
	}

	// director & writer
	foreach ($cast_data->crew as $item) {
		if (strcasecmp($item->department, 'Directing') == 0) {
			if (!in_array($item->name, $data['director'])) {
				array_push($data['director'], $item->name);
			}
		}
		if (strcasecmp($item->department, 'Writing') == 0) {
			if (!in_array($item->name, $data['writer'])) {
				array_push($data['writer'], $item->name);
			}
		}
	}

	return $data;
}

function GetCertificateInfoTMDB($releases_data, $data)
{
	$certificate = array();
	foreach ($releases_data->countries as $item) {
		if ('' === $item->certification) {
			continue;
		}
		$name = strcasecmp($item->iso_3166_1, 'us') == 0 ? 'USA' : $item->iso_3166_1;
		$certificate[$name] = $item->certification;
	}
	$data['certificate'] = $certificate;
	return $data;
}

/**
 * @brief get metadata for multiple movies
 * @param $query_data [in] a array contains multiple movie item
 * @param $lang [in] a language
 * @return [out] a result array
 */
function GetMetadataTMDB($query_data, $lang)
{
	global $DATA_TEMPLATE;

	//Foreach query result
	$result = array();
	foreach ($query_data as $item) {
		//If languages are different, skip it
		if (0 != strcmp($item['lang'], $lang)) {
			continue;
		}

		//Copy template
		$data = $DATA_TEMPLATE;

		//Get movie
		$movie_data = GetRawdata("movie", array('id' => $item['id'], 'lang' => $item['lang']), DEFAULT_EXPIRED_TIME);
		if (!$movie_data) {
			continue;
		}
		$data = GetMovieInfoTMDB($movie_data, $data);

		//Get cast
		$cast_data = GetRawdata("cast", array('id' => $item['id']), DEFAULT_EXPIRED_TIME);
		if ($cast_data) {
			$data = GetCastInfoTMDB($cast_data, $data);
		}

		//Get certificates
		$releases_data = GetRawdata("releases", array('id' => $item['id']), DEFAULT_EXPIRED_TIME);
		if ($releases_data) {
			$data = GetCertificateInfoTMDB($releases_data, $data);
		}

		//Append to result
		$result[] = $data;
	}

	return $result;
}

function ProcessTMDB($input, $lang, $type, $limit, $search_properties, $allowguess, $id)
{
	$title 	= $input['title'];
	$year 	= ParseYear($input['original_available']);
	$lang 	= ConvertToAPILang($lang);
	if (!$lang) {
		return array();
	}

	if (0 < $id) {
		// if haved id, output metadata directly.
		return GetMetadataTMDB(array(array('id' => $id, 'lang' => $lang)), $lang);
	}

	//Search
	$query_data = array();
	$titles = GetGuessingList($title, $allowguess);
	foreach ($titles as $checkTitle) {
		if (empty($checkTitle)) {
			continue;
		}
		$query_data = Query($checkTitle, $year, $lang, $limit, 0, 0);
		if (0 < count($query_data)) {
			break;
		}
	}

	//Get metadata
	return GetMetadataTMDB($query_data, $lang);
}

//=========================================================
// tmdb end
//=========================================================

function Process($input, $lang, $type, $limit, $search_properties, $allowguess, $id)
{
	$t1 = microtime(true);
	if ('enu' == $lang) {
		$RET = ProcessTMDB($input, $lang, $type, $limit, $search_properties, $allowguess, $id);
	} else {
		$RET = ProcessDouban($input, $lang, $type, $limit, $search_properties, $allowguess, $id);
	}
	$t2 = microtime(true);
	//error_log(print_r( $_SERVER, true), 3, "/var/packages/VideoStation/target/plugins/syno_themoviedb/my-errors.log");
	//error_log((($t2-$t1)*1000).'ms', 3, "/var/packages/VideoStation/target/plugins/syno_themoviedb/my-errors.log");
	return $RET;
}

PluginRun('Process');
