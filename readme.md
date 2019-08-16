修改自帖子 http://www.gebi1.com/thread-261344-1-9.html?_dsign=39316681 的代码

原贴的信息应该是来自豆瓣的api，目前已经关闭，部分数据可用是博主自己代理了一层，然后缓存了老数据。

修改内容是直接抓取了网页信息。并且设置为电影和电视类型都可用。

使用：
1. ssh 登录群晖系统
2. 执行`wget https://raw.githubusercontent.com/jswh/synology_video_station_douban_plugin/master/install.sh`
3. 执行`sudo bash install.sh uninstall`
4. 执行`sudo bash install.sh install`

所有权利归老哥所有
