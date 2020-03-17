#!/usr/bin/php
<?php

require_once(dirname(__FILE__) . '/../util_themoviedb.php');
require_once(dirname(__FILE__) . '/../search.inc.php');
require_once(dirname(__FILE__) . '/../syno_file_assets/douban.php');

$SUPPORTED_TYPE = array('movie');
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

function Process($input, $lang, $type, $limit, $search_properties, $allowguess, $id)
{
	$t1 = microtime(true);
    $RET = ProcessDouban($input, $lang, $type, $limit, $search_properties, $allowguess, $id);
	$t2 = microtime(true);
	//error_log(print_r( $_SERVER, true), 3, "/var/packages/VideoStation/target/plugins/syno_themoviedb/my-errors.log");
	//error_log((($t2-$t1)*1000).'ms', 3, "/var/packages/VideoStation/target/plugins/syno_themoviedb/my-errors.log");
	return $RET;
}

PluginRun('Process');
