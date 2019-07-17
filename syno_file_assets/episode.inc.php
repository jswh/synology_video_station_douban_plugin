<?php
/**
 * @brief guess episode info from $checkTitle
 * @param title [in] default title value
 * @param year [in] default year value
 * @param season [in] default season value
 * @param checkTitle [in] $checkTitle is usually a sort of
 *  				 file name. we try to parse episode info
 *  				 from it. Sometimes we can't get enough
 *  				 information, use default value instead.
 * @return [out] array('title' => , 'season' => , 'episode' => )
 */
function GuessEpisodeInfo($title, $year, $season, $checkTitle)
{
	
	//remove year fix wrong episode by atroy @201903170112
	//$checkTitle = str_replace($year, "", $checkTitle);
	$checkTitle = preg_replace(array("/$year/", "/ [0-9]{4}/"), array("", ""), $checkTitle);
	
	
	$episode_regexps = array(
		'/(?P<show>.*?)(Season ?(?P<season>[0-9]+))(.*?)Episode ?(?P<ep>[0-9]+)([- ]?Episode ?(?P<secondEp>[0-9]+))?/ui',				// Season 3 XXXX Episode 04
		'/(?P<show>.*?)(Season ?(?P<season>[0-9]+))[\._ ]*Episode ?(?P<ep>[0-9]+)([- ]?Episode ?(?P<secondEp>[0-9]+))?/ui',				// Season 3 Episode 04
		'/(?P<show>.*?)Season ?(?P<season>[0-9]+)/ui',																					// Season 03
		'/(?P<show>.*?)([sS](?P<season>[0-9]+))[\._ ]*[eE][pP]?(?P<ep>[0-9]+)([- ]?[Ee+][pP]?(?P<secondEp>[0-9]+))?/ui',				// S03E04-E05
		'/(?P<show>.*?)[sS](?P<season>[0-9]{2})[\._\- ]+(?P<ep>[0-9]+)/ui',																// S03-03
		'/(?P<show>.*?)([^0-9]|^)(?P<season>[0-9]{1,2})[Xx](?P<ep>[0-9]+)(-[0-9]+[Xx](?P<secondEp>[0-9]+))?/ui',						// 3x03
		'/(?P<show>.*?)[eE][pP]?(?P<ep>[0-9]+)([- ]?[Ee+]?[pP]?(?P<secondEp>[0-9]+))?[\._ ]*([sS](?P<season>[0-9]+))/ui',				// E04-E05S03
		'/(?P<show>.*?)[\._ ]*[eE][pP]?(?P<ep>[0-9]+)([- ]?[Ee+][pP]?(?P<secondEp>[0-9]+))?/ui',										// E04-E05
		'/(?P<show>.*?)[\._ ]*[eE][pP] ?(?P<ep>[0-9]+)([- ]?[Ee+][pP] ?(?P<secondEp>[0-9]+))?/ui',										// E04-E05
		'/(?P<show>.*?)[\._ ]*Episode ?(?P<ep>[0-9]+)([- ]?Episode ?(?P<secondEp>[0-9]+))?/ui',											// Episode 04
		'/(?P<show>.*?)(\x{7b2c}\s?(?P<season>\d+)\s?\x{5b63})(.*?)(\x{7b2c}\s?(?P<ep>\d+)([- ]?(?P<secondEp>\d+))?\s?[\x{8a71}|\x{56de}|\x{96c6}])/ui',	// 第 3 季 XXXX 第 04 話|回|集
		'/(?P<show>.*?)(\x{7b2c}\s?(?P<season>\d+)\s?\x{5b63})/ui',																		// 第 3 季
		'/(?P<show>.*?)(\x{7b2c}\s?(?P<ep>\d+)([- ]?(?P<secondEp>\d+))?\s?[\x{8a71}|\x{56de}|\x{96c6}])/ui',							// 第4話|回|集
//		'/(.*?)[^0-9a-z](?P<season>[0-9]{1,2})(?P<ep>[0-9]{2})([\.\-][0-9]+(?P<secondEp>[0-9]{2})([ \-_\.]|$)[\.\-]?)?([^0-9a-z%]|$)/ui'	// .602.
	);

	$just_episode_regexs = array(
		'/(?P<show>.*?)[\._ ]*[eE][pP]?(?P<ep>[0-9]+)([- ]?[Ee+][pP]?(?P<secondEp>[0-9]+))?/ui',				// E04-E05
		'/(?P<show>.*?)[\._ ]*[eE][pP] ?(?P<ep>[0-9]+)([- ]?[Ee+][pP] ?(?P<secondEp>[0-9]+))?/ui',				// E04-E05
		'/(?P<show>.*?)[\._ ]*Episode ?(?P<ep>[0-9]+)([- ]?Episode ?(?P<secondEp>[0-9]+))?/ui',					// Episode 04
		'/(?P<show>.*?)(\x{7b2c}\s?(?P<ep>\d+)([- ]?(?P<secondEp>\d+))?\s?[\x{8a71}|\x{56de}|\x{96c6}])/ui',	// 第4話|回|集
		'/(?P<ep>[0-9]{1,3})[\. -_]of[\. -_]+[0-9]{1,3}/ui',													// 01 of 08
		'/^(?P<ep>[0-9]{1,3})[^0-9]/ui',																		// 01 - Foo
		'/e[a-z]*[ \.\-_]*(?P<ep>[0-9]{2,3})([^0-9c-uw-z%]|$)/ui',												// Blah Blah ep234
		'/.*?[ \.\-_](?P<ep>[0-9]{2,3})[^0-9c-uw-z%]+/ui',														// Blah - 04 - Blah
		'/.*?[ \.\-_](?P<ep>[0-9]{2,3})$/ui',																	// Blah - 04
		//'/.*?[^0-9x](?P<ep>[0-9]{2,3})$/ui',																	// Blah707
		'/(?P<show>.*?)[\._ ]*[eE][pP]?(?P<ep>[0-9]+)([- ]?[Ee+][pP]?(?P<secondEp>[0-9]+))?/ui',				// XXXX.14
		'/^(?P<ep>[0-9]+)$/ui'																					// 01
	);

	$episode = NULL;

	//use $episode_regexps pattern to parse season and episode
	foreach ($episode_regexps as $regex) {
		if (0 == preg_match($regex, $checkTitle, $matches)) {
			continue;
		}

		if (isSet($matches['show']) && !empty($matches['show'])) {
			list($title) = CleanName($matches['show']);
		}
		if (isSet($matches['season']) && !empty($matches['season'])) {
			$season = $matches['season'];
		}
		if (isSet($matches['ep']) && !empty($matches['ep'])) {
			$episode = $matches['ep'];
		}
		break;
	}
//error_log(print_r( array('xXXx' => 'episode1', 'episode' => $episode), true), 3, "/var/packages/VideoStation/target/plugins/syno_thetvdb/my-errors.log");

	//if failed, use $just_episode_regexs pattern to parse episode only
	if (!$episode) {
		//clean title first
		if ($checkTitle !== $title) {
			$checkTitle = str_replace($title, '', $checkTitle);
		}
		//error_log(print_r( array('xXXx' => 'matches2', 'matches' => $matches), true), 3, "/var/packages/VideoStation/target/plugins/syno_thetvdb/my-errors.log");
		foreach ($just_episode_regexs as $regex) {
			if (0 == preg_match($regex, $checkTitle, $matches)) {
				continue;
			}
			if (empty($title) && isSet($matches['show']) && !empty($matches['show'])) {
				list($title) = CleanName($matches['show']);
			}
			if (isSet($matches['ep']) && !empty($matches['ep'])) {
				$episode = $matches['ep'];
			}
			//error_log(print_r( array('xXXx' => 'matches22', 'matches' => $matches), true), 3, "/var/packages/VideoStation/target/plugins/syno_thetvdb/my-errors.log");
			break;
		}
	}
//error_log(print_r( array('xXXx' => 'episode2', 'episode' => $episode), true), 3, "/var/packages/VideoStation/target/plugins/syno_thetvdb/my-errors.log");

	//error_log(print_r( array('xXXx' => 'GuessEpisodeInfo', 'title' => $title, 'year' => $year, 'season' => $season, 'checkTitle' => $checkTitle), true), 3, "/var/packages/VideoStation/target/plugins/syno_thetvdb/my-errors.log");
	//error_log(print_r( array('xXXx' => 'GuessEpisodeInfoResult', 'title' => $title, 'season' => $season, 'episode' => $episode), true), 3, "/var/packages/VideoStation/target/plugins/syno_thetvdb/my-errors.log");
	return array($title, $season, $episode);
}

function GetEpisodeInfo($path)
{
	$standalone_episode_regexs = array(
		'/(.*?)( \(([0-9]+)\))? - ([0-9]+)+x([0-9]+)(-[0-9]+[Xx]([0-9]+))?( - (.*))?/ui',		// Newzbin style, no _UNPACK_
		'/(.*?)( \(([0-9]+)\))?[Ss]([0-9]+)+[Ee]([0-9]+)(-[0-9]+[Xx]([0-9]+))?( - (.*))?/ui'	// standard s00e00
	);
	$season_regex = '/.*?(season|s)[\s-]*(?P<season>[0-9]+)$/ui';									// folder for a season
	$date_regexps = array(
		'/(?P<year>[0-9]{4})[^0-9a-zA-Z]+(?P<month>[0-9]{2})[^0-9a-zA-Z]+(?P<day>[0-9]{2})([^0-9]|$)/ui',		// 2009-02-10
		'/(?P<month>[0-9]{2})[^0-9a-zA-Z]+(?P<day>[0-9]{2})[^0-9a-zA-Z(]+(?P<year>[0-9]{4})([^0-9a-zA-Z]|$)/ui',// 02-10-2009
	);
	$whacx_regex = array('/([hHx][\.]?264)[^0-9]/ui', '/[^[0-9](720[pP])/ui', '/[^[0-9](1080[pP])/ui', '/[^[0-9](480[pP])/ui');
	$title_tagline_regex = '/^(?P<show>.*?)\s*-\s*(?P<tagline>.*?)$/ui';

	//sync with other rules
	$ends_with_episode = array(
		'/(Season ?(?P<season>[0-9]+))(.*?)Episode ?(?P<ep>[0-9]+)([- ]?Episode ?(?P<secondEp>[0-9]+))?$/ui',							// Season 3 XXXX Episode 04
		'/(Season ?(?P<season>[0-9]+))[\._ ]*Episode ?(?P<ep>[0-9]+)([- ]?Episode ?(?P<secondEp>[0-9]+))?$/ui',							// Season 3 Episode 04
		'/Season ?(?P<season>[0-9]+)$/ui',																								// Season 03
		'/([sS](?P<season>[0-9]+))[\._ ]*[eE][pP]?(?P<ep>[0-9]+)([- ]?[Ee+][pP]?(?P<secondEp>[0-9]+))?$/ui',							// S03E04-E05
		'/[sS](?P<season>[0-9]{2})[\._\- ]+(?P<ep>[0-9]+)$/ui',																			// S03-03
		'/(?P<season>[0-9]{1,2})[Xx](?P<ep>[0-9]+)(-[0-9]+[Xx](?P<secondEp>[0-9]+))?$/ui',												// 3x03
		'/[eE][pP]?(?P<ep>[0-9]+)([- ]?[Ee+][pP]?(?P<secondEp>[0-9]+))?$/ui',															// E04-E05
		'/[eE][pP] ?(?P<ep>[0-9]+)([- ]?[Ee+][pP] ?(?P<secondEp>[0-9]+))?$/ui',															// E04-E05
		'/Episode ?(?P<ep>[0-9]+)([- ]?Episode ?(?P<secondEp>[0-9]+))?$/ui',															// Episode 04
		'/(\x{7b2c}\s?(?P<season>\d+)\s?\x{5b63})(.*?)(\x{7b2c}\s?(?P<ep>\d+)([- ]?(?P<secondEp>\d+))?\s?[\x{8a71}|\x{56de}|\x{96c6}])$/ui',	// 第 3 季 XXXX 第 04 話|回|集
		'/(\x{7b2c}\s?(?P<season>\d+)\s?\x{5b63})$/ui',																					// 第 3 季
		'/(\x{7b2c}\s?(?P<ep>\d+)([- ]?(?P<secondEp>\d+))?\s?[\x{8a71}|\x{56de}|\x{96c6}])$/ui',										// 第4話|回|集
		'/[\._ ]+(?P<ep>[0-9]{2,3})$/ui'																								// 707
	);

	$pathinfo = pathinfo($path);
	$filename = $pathinfo['filename'];

	//Split path into each component and arrage items backwards.
	$path_component = array_reverse(explode('/', $path));

	//Path depth exclude share level (/volume1/video/) and the empty root element
	$path_depth = count($path_component) - 3;

	$title = NULL;
	$tagline = NULL;
	$year = NULL;
	$season = NULL;
	$episode = NULL;

	list($cleanName, $cleanYear) = CleanName($filename);

	if (2 <= $path_depth) {
		//Get season from parent directory name. ex: '/volume1/video/tvshow/Revenge/Season 1/S01.E02.rmvb')
		if (3 <= $path_depth && 0 < preg_match($season_regex, $path_component[1], $matches)) {
			$season = $matches['season'];
			list($title, $year) = CleanName($path_component[2]);
		//ex: '/volume1/video/tvshow/Revenge/S01.E02.rmvb' => 'Revenge'
		} else {
			list($title, $year) = CleanName($path_component[1]);
		}
	}

	//If title and year is not compatible to above situations, use filename as title and year directly.
	if (!$title) {
		$title = $cleanName;
	}
	if (!$year) {
		$year = $cleanYear;
	}

	//strip out episode info from title
	foreach ($ends_with_episode as $regex) {
		$title = preg_replace($regex, '',  $title);
	}

	//clean filename
	$filename = CleanGarbage($filename, $year, '/([\.\- _\(\)\[\]+]+)/ui');
	foreach ($whacx_regex as $regex) {
		$filename = preg_replace($regex, ' ', $filename);
	}

	//use cleanName to guess title, season, episode
	list($title, $season, $episode) = GuessEpisodeInfo($title, $year, $season, $cleanName);
	if (empty($title) || empty($season) || empty($episode) || 1000 < (int)$episode ) {
		//use filename to guess episode
		$tmp_title = NULL;
		$tmp_season = NULL;
		$tmp_episode = NULL;
		list($tmp_title, $tmp_season, $tmp_episode) = GuessEpisodeInfo($title, $year, $season, $filename);
		$title = empty($title)? $tmp_title: $title;
		$season = empty($season)? $tmp_season: $season;
		$episode = empty($episode)? $tmp_episode: $episode;
	}

	//parse date when episode empty
	if (!$episode) {
		foreach ($date_regexps as $regex) {
			if (0 == preg_match($regex, $filename, $matches)) {
				continue;
			}
			$year = $matches['year'] . '-' . $matches['month'] . '-' . $matches['day'];
			break;
		}
	}

	//no season, no episode, use the cleaned filename as title */
	if (!$season && !$episode) {
		$title = NULL;
		$checkFilename = CleanGarbage($pathinfo['filename'], $year, '/([\. _\(\)+]+)/ui');
		if (0 !== preg_match($title_tagline_regex, $checkFilename, $matches)) {
			if (isSet($matches['show']) && !empty($matches['show'])) {
				list($title) = CleanName($matches['show']);
			}
			if (isSet($matches['tagline']) && !empty($matches['tagline'])) {
				list($tagline) = CleanName($matches['tagline']);
			}
		}
		if (empty($title)) {
			$title = $cleanName;
		}
	}

	// to avoid empty title or title only has spaces
	if(empty($title) || strlen(trim($title)) == 0){
		// ex1: $path_depth >= 3 but parent.parent.name is empty after cleaning & stripping
		// ex2: $path_depth >= 2 but parent.name is empty after cleaning & stripping
		// ex3: $path_depth < 2 but filename is empty after cleaning & removing re
		$title = $pathinfo['basename'];
	}
	//error_log(print_r( array('xXXx' => 'GetEpisodeInfo', 'path' => $path), true), 3, "/var/packages/VideoStation/target/plugins/syno_thetvdb/my-errors.log");
	//error_log(print_r( array('xXXx' => 'GetEpisodeInfoResult', 'title' => $title, 'tagline' => $tagline, 'year' => $year, 'season' => $season, 'episode' => $episode), true), 3, "/var/packages/VideoStation/target/plugins/syno_thetvdb/my-errors.log");
	return array($title, $tagline, $year, $season, $episode);
}
?>
