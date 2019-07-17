#!/usr/bin/php
<?php

require_once(dirname(__FILE__) . '/../constant.php');

define('PLUGINID', 'com.synology.TheTVDB');
define('API_URL', 'https://www.thetvdb.com/api/');
define('BANNER_URL', 'https://www.thetvdb.com/banners/');

$DEFAULT_TYPE = 'tvshow_episode';
$DEFAULT_LANG = 'chs';

$SUPPORTED_TYPE = array('tvshow', 'tvshow_episode');
$SUPPORTED_PROPERTIES = array('title');

require_once(dirname(__FILE__) . '/../search.inc.php');

function ConvertToAPILang($lang)
{
	static $map = array(
		'chs' => 'zh', 'cht' => 'zh', 'csy' => 'cs', 'dan' => 'da',
		'enu' => 'en', 'fre' => 'fr', 'ger' => 'de', 'hun' => 'hu',
		'ita' => 'it', 'jpn' => 'ja', 'krn' => 'ko', 'nld' => 'nl',
		'nor' => 'no', 'plk' => 'pl', 'ptb' => 'pt', 'ptg' => 'pt',
		'rus' => 'ru', 'spn' => 'es', 'sve' => 'sv', 'trk' => 'tr',
		'tha' => 'th'
	);

	$ret = isset($map[$lang]) ? $map[$lang] : NULL;
	return $ret;
}


// tvshow_episode 单集
// tvshow 总

/**
 * @brief get metadata for multiple movies
 * @param $query_data [in] a array contains multiple movie item
 * @param $season [in] season number
 * @param $episode [in] episode number
 * @param $lang [in] a language
 * @param $type [in] tvshow, tvshow_episode
 * @return [out] a result array
 */
function GetMetadata($query_data, $season, $episode, $lang, $type)
{
	global $DATA_TEMPLATE;
	
	//error_log(print_r( array('query_data' => $query_data, 'season'=>$season, 'episode' => $episode, 'lang'=>$lang, 'type'=>$type), true), 3, "/var/packages/VideoStation/target/plugins/syno_thetvdb/my-errors.log");
	
	//Foreach query result
	$result = array();
	foreach($query_data as $item) {
		//If languages are different, skip it
		if (0 != strcmp($item['lang'], $lang)) {
			continue;
		}

        //Copy template
		$data = $DATA_TEMPLATE;

		// retry 3
		$retryCount = 3;
		do {
			$movie_data = json_decode( HTTPGETRequest('https://api.9hut.cn/douban.php?type=tv&season='.$season.'&episode='.$episode.'&movid=' . $item['id']) );
		} while (!$movie_data->title && 0 < $retryCount--);
		//error_log(print_r( array('GetMetadata', 'https://api.9hut.cn/douban.php?type=tv&season='.$season.'&episode='.$episode.'&movid=' . $item['id']), true), 3, "/var/packages/VideoStation/target/plugins/syno_thetvdb/my-errors.log");

		if (!$movie_data) {
			continue;
		}
		
		$data['path'] = NULL;
		$data['title'] = $movie_data->title;
		$data['tagline'] = implode(',', $movie_data->aka);
		$data['original_available'] = $movie_data->original_available;
		$data['summary'] = $movie_data->summary;
		$data['season'] = (int)$movie_data->season == 0 ? 1 : (int)$movie_data->season;
		//$data['episode'] = (int)$movie_data->episode;
		$data['episode'] = (int)$episode;
		$data['certificate'] = array();
		$data['extra'] = NULL;
		
		$data['extra'][PLUGINID]['list'] = array();
		
		$data['extra'][PLUGINID] = array('reference' => array());
		$data['extra'][PLUGINID]['reference']['thetvdb'] = (string)$movie_data->id;
		if (isset($movie_data->imdb_id)) {
			$data['extra'][PLUGINID]['reference']['imdb'] = (string)$movie_data->imdb_id;
		}
		if ((float)$movie_data->rating->average) {
			$data['extra'][PLUGINID]['rating'] = array('thetvdb' => (float)$movie_data->rating->average);
		}
		/*
		if (isset($movie_data->images)) {
			$data['extra'][PLUGINID]['poster'] = array((string)$movie_data->images->large);
		}
		if (isset($movie_data->backdrop_path)) {
			$data['extra'][PLUGINID]['backdrop'] = array((string)$movie_data->backdrop_path);
		}
		*/
		
		// writer
		if( isset($movie_data->writers) ){
			foreach ($movie_data->writers as $item) {
				if (!in_array($item->name, $data['writer'])) {
					array_push($data['writer'], $item->name);
				}
			}
		}
		
		// director
		if( isset($movie_data->directors) ){
			foreach ($movie_data->directors as $item) {
				if (!in_array($item->name, $data['director'])) {
					array_push($data['director'], $item->name);
				}
			}
		}
		
		// actor
		if( isset($movie_data->actors) ){
			foreach ($movie_data->actors as $item) {
				if (!in_array($item->name, $data['actor'])) {
					array_push($data['actor'], $item->name);
				}
			}
		}
		
		// genre
		if( isset($movie_data->genres) ){
			foreach ($movie_data->genres as $item) {
				if (!in_array($item, $data['genre'])) {
					array_push($data['genre'], $item);
				}
			}
		}
		
		// editor
		//array_push($data['editor'], array());
		
		//array_values
		switch ($type) {
			case 'tvshow':
				//$data = GetTVShowInfo($series_data, $actors, $data);
				
				if (isset($movie_data->images)) {
					$data['extra'][PLUGINID]['poster'] = array((string)$movie_data->images->large);
				}
				if (isset($movie_data->backdrop_path)) {
					$data['extra'][PLUGINID]['backdrop'] = array((string)$movie_data->backdrop_path);
				}
				
				$data['extra'][PLUGINID]['list'] = array();
				$data['id'] = (string)$movie_data->id;
				$data['lang'] = ConvertToAPILang($lang);
				$data['type'] = $type;
				//$data['limit'] = 1;
				//
				//for($i=1; $i<=(int)$data['episode']; $i++){
					$epitem[] = array(
						'actor'	=>	$data['actor'],
						'certificate'	=> NULL,
						'director'	=> $data['director'],
						'editor'	=> array(),
						'episode'	=> $episode,
						'extra'	=> array(
							PLUGINID => array(
								'reference'	=> array(
									'synovideodb' => (string)$movie_data->id
								)
							)
						),
						'genre'	=>	$data['genre'],
						'original_available'	=> $data['original_available'],
						'season'	=> $data['season'],
						'summary'	=> $data['summary'],
						'tagline'	=> $data['tagline'],
						'writer'	=> $data['writer']
					);					
				//}
				$list[] = array(
					'season' => $data['season'],
					'episode' => array_values($epitem)
				);
				$data['extra'][PLUGINID]['list'] = $list;
				break;
			case 'tvshow_episode':
				//$data = GetEpisodeInfo($series_data, $actors, $season, $episode, $data);
				$data['extra'][PLUGINID]['tvshow'] = array();
				$data['id'] = (string)$movie_data->id;
				$data['lang'] = ConvertToAPILang($lang);
				$data['type'] = $type;
				//$data['limit'] = 1;
				//
				$data['extra'][PLUGINID]['tvshow'] = array(
					'actor'	=>	$data['actor'],
					'certificate'	=> NULL,
					'director'	=> $data['director'],
					'editor'	=> array(),
					'extra'	=> array(
						PLUGINID => array(
							'rating'	=> array(
								'synovideodb' => (float)$movie_data->rating->average
							),
							'reference'	=> array(
								'synovideodb' => (string)$movie_data->id
							)
						)
					),
					'genre'	=>	$data['genre'],
					'original_available'	=> $data['original_available'],
					'summary'	=> $data['summary'],
					'title'	=> $data['title'],
					'writer'	=> $data['writer']
				);
				if (isset($movie_data->images)) {
					$data['extra'][PLUGINID]['tvshow']['extra'][PLUGINID]['poster'] = array((string)$movie_data->images->large);
				}
				if (isset($movie_data->backdrop_path)) {
					$data['extra'][PLUGINID]['tvshow']['extra'][PLUGINID]['backdrop'] = array((string)$movie_data->backdrop_path);
				}
				break;
		}
		//error_log(print_r( array('type' => $type, 'data'=>$data), true), 3, "/var/packages/VideoStation/target/plugins/syno_thetvdb/my-errors.log");

		//Append to result
		$result[] = $data;
	}
	//error_log(print_r( array('xXXx' => 'GetMetadata', 'result'=>$result), true), 3, "/var/packages/VideoStation/target/plugins/syno_thetvdb/my-errors.log");
	return $result;
}

function Query($query, $year, $lang, $limit, $season, $episode)
{
	$result = array();

	//Get search result
	//$search_data = GetRawdata('search', array('query' => $query, 'lang' => $lang));
	//Search
	//error_log(print_r( array('query' => $query, 'year'=>$year, 'lang' => $lang, 'limit'=>$limit), true), 3, "/var/packages/VideoStation/target/plugins/syno_thetvdb/my-errors.log");
	$search_data = json_decode( HTTPGETRequest('https://api.9hut.cn/douban.php?type=tv&season='.$season.'&episode='.$episode.'&q='.$query) );
	//error_log(print_r( array('Query', 'https://api.9hut.cn/douban.php?type=tv&season='.$season.'&episode='.$episode.'&q=' . $query), true), 3, "/var/packages/VideoStation/target/plugins/syno_thetvdb/my-errors.log");
	if (!$search_data) {
		return $result;
	}

	//Get all items
	foreach($search_data->data as $item) {
		$data = array();
		$data['id'] 	= (string)$item->id;
		$data['lang'] 	= (string)$item->lang;
		$data['diff']   = $item->diff;
		$result[] = $data;
	}

	//If no result
	if (!count($result)) {
		return $result;
	}

	//Get the first $limit items
	$result = array_slice($result, 0, $limit);
	
	//error_log(print_r( array('xXXx' => 'Query', 'result'=>$result), true), 3, "/var/packages/VideoStation/target/plugins/syno_thetvdb/my-errors.log");
	return $result;
}


function Process($input, $lang, $type, $limit, $search_properties, $allowguess, $id)
{
	//error_log(print_r( array('xXXx' => 'Process', 'input' => $input, 'lang'=>$lang, 'type' => $type, 'limit'=>$limit, 'search_properties'=>$search_properties, 'allowguess'=>$allowguess, 'id'=>$id), true), 3, "/var/packages/VideoStation/target/plugins/syno_thetvdb/my-errors.log");
	//error_log(print_r( array('xXXx' => 'input', 'titles' => $input), true), 3, "/var/packages/VideoStation/target/plugins/syno_thetvdb/my-errors.log");
	$result = array();

	$title 	 = $input['title'];
	$year 	 = ParseYear($input['original_available']);
	$lang 	 = ConvertToAPILang($lang);
	$season  = $input['season'];
	$episode = $input['episode'];
	if (!$lang) {
		return array();
	}

	if (0 < $id) {
		// if haved id, output metadata directly.
		return GetMetadata(array(array('id' => $id, 'lang' => $lang)), $season, $episode, $lang, $type);
	}

	//year
	if (isset($input['extra']) && count($input['extra']) > 0) {
		$pluginid = array_shift($input['extra']);
		if (!empty($pluginid['tvshow']['original_available'])) {
			$year = ParseYear($pluginid['tvshow']['original_available']);
		}
	}

	//Search
	$query_data = array();
	//$titles = GetGuessingList($title, $allowguess);
	$titles = GetGuessingList($title, false);
	
	foreach ($titles as $checkTitle) {
		if (empty($checkTitle)) {
			continue;
		}
		$query_data = Query($checkTitle, $year, $lang, $limit, $season, $episode);
		if (0 < count($query_data)) {
			break;
		}
	}

	//Get metadata
	return GetMetadata($query_data, $season, $episode, $lang, $type);
}

PluginRun('Process');
?>
