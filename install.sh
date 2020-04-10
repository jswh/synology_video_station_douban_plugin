#!/bin/bash
PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:~/bin
export PATH

clear;

# VAR   ******************************************************************
vVersion='v2020409';
vPlugins='/var/packages/VideoStation/target/plugins';
vUI='/var/packages/VideoStation/target';
vAction=$1;
vWorker=$2
pack='https://github.com/jswh/synology_video_station_douban_plugin/archive/master.tar.gz'
dist='synology_video_station_douban_plugin-master'
# Logo  ******************************************************************
CopyrightLogo="
                         DS Video Douban Patch $vVersion
                                    by atroy @2019
                         http://9hut.com All Rights Reserved

==========================================================================";
echo "$CopyrightLogo";

# Function List *******************************************************************************
function install()
{
    cd /tmp/;

    # backup
    if [ ! -f "$vPlugins/syno_themoviedb/search.php.orig" ]; then
        mv $vPlugins/syno_themoviedb/search.php $vPlugins/syno_themoviedb/search.php.orig
    fi;
    if [ ! -f "$vPlugins/syno_synovideodb/search.php.orig" ]; then
        mv $vPlugins/syno_synovideodb/search.php $vPlugins/syno_synovideodb/search.php.orig
    fi;
    if [ ! -f "$vPlugins/syno_thetvdb/search.php.orig" ]; then
        mv $vPlugins/syno_thetvdb/search.php $vPlugins/syno_thetvdb/search.php.orig
    fi;
    if [ ! -f "$vUI/ui/videostation2.js.orig" ]; then
        mv $vUI/ui/videostation2.js $vUI/ui/videostation2.js.orig
    fi;
    if [ ! -f "$vPlugins/syno_file_assets/episode.inc.php.orig" ]; then
        mv $vPlugins/syno_file_assets/episode.inc.php $vPlugins/syno_file_assets/episode.inc.php.orig
    fi;

    wget --no-check-certificate $pack -O video_station_douban_patch.tar.gz;
    tar -zxvf video_station_douban_patch.tar.gz

        cd $dist

    \cp -rfa ./syno_themoviedb $vPlugins/;
    \cp -rfa ./syno_synovideodb $vPlugins/;
    \cp -rfa ./syno_thetvdb $vPlugins/;
    \cp -rfa ./syno_file_assets $vPlugins/;
    \cp -rfa ./ui $vUI/;

    chmod 0755 $vPlugins/syno_themoviedb/search.php $vPlugins/syno_synovideodb/search.php $vPlugins/syno_thetvdb/search.php $vUI/ui/videostation2.js $vPlugins/syno_file_assets/episode.inc.php

    chown VideoStation:VideoStation $vPlugins/syno_themoviedb/search.php $vPlugins/syno_synovideodb/search.php $vPlugins/syno_thetvdb/search.php $vUI/ui/videostation2.js $vPlugins/syno_file_assets/episode.inc.php

    sed "s/CF_WORKER_URL/$2/g" $vPlugins/syno_file_assets/douban.tmp.php > $vPlugins/syno_file_assets/douban.php

    cd -

    echo '==========================================================================';
    echo "Congratulations, DS Video Douban Patch $vVersion install/upgrade completed.";
    echo '==========================================================================';
}

function uninstall()
{
    mv -f $vPlugins/syno_themoviedb/search.php.orig $vPlugins/syno_themoviedb/search.php
    mv -f $vPlugins/syno_synovideodb/search.php.orig $vPlugins/syno_synovideodb/search.php
    mv -f $vPlugins/syno_thetvdb/search.php.orig /$vPlugins/syno_thetvdb/search.php
    mv -f $vPlugins/syno_file_assets/episode.inc.php.orig /$vPlugins/syno_file_assets/episode.inc.php
    mv -f $vUI/ui/videostation2.js.orig $vUI/ui/videostation2.js

    rm $vPlugins/syno_file_assets/douban.php

    rm -rf /tmp/$dist;

    echo 'Congratulations, DS Video Douban Patch uninstall completed.';
    echo '==========================================================================';
}

# SHELL     ******************************************************************
if [ "$vAction" == 'install' ]; then
    if [ ! -f "$vPlugins/syno_themoviedb/search.php.orig" ]; then
        install;
    else
        echo 'Sorry, you have already installed DS Video Douban Patch.';
        echo '==========================================================================';
        exit 1;
    fi;
elif [ "$vAction" == 'uninstall' ]; then
    if [ ! -f "$vPlugins/syno_themoviedb/search.php.orig" ]; then
        echo 'Sorry, you have not installed DS Video Douban Patch yet.';
        echo '==========================================================================';
        exit 1;
    else
        uninstall;
    fi;
elif [ "$vAction" == 'upgrade' ]; then
    install;
else
    echo 'Sorry, Failed to install DS Video Douban Patch.';
    echo '==========================================================================';
    exit 1
fi;
