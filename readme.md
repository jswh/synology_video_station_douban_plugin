终于我还是弃坑了。在我写了半天TMDB得东西之后，发现Video Station本来就是用的TMDB得接口啊（摔！自带得搜索不能用得原因是TMDB接口api地址被墙了。但是我调试api得时候发现了一个不正规得地址z4vrpkijmodhwsxzc.stoplight-proxy.io。遵循如下步骤修改就可以使用自带搜索了。

ssh 登录群晖
执行命令 cd /var/packages/VideoStation/target/plugins
编辑 util_themoviedb.php 文件
修改其中的api.themoviedb.org 为 z4vrpkijmodhwsxzc.stoplight-proxy.io
好了，没事了，大家散了吧。

# 以下内容已经弃坑


修改自帖子 http://www.gebi1.com/thread-261344-1-9.html?_dsign=39316681 的代码

原贴的信息应该是来自豆瓣的api，目前已经关闭，部分数据可用是博主自己代理了一层，然后缓存了老数据。

修改内容是直接抓取了网页信息。并且设置为电影和电视类型都可用。

安装：
1. ssh 登录群晖系统
2. 执行`wget https://raw.githubusercontent.com/jswh/synology_video_station_douban_plugin/master/install.sh`
3. 执行`sudo bash install.sh uninstall` （第一次安装可以跳过这个步骤）
4. 执行`sudo bash install.sh install 'http://quiet-cake-f23b.jswh-cf-workers.workers.dev'`

卸载
1. ssh 登录群晖系统
2. 执行`wget https://raw.githubusercontent.com/jswh/synology_video_station_douban_plugin/master/install.sh`
3. 执行`sudo bash install.sh uninstall`


#### 使用自己的cf-worker
刮削器目前使用cf-worker搭建的代理来访问豆瓣来防止豆瓣屏蔽。cf-worker免费版本每天有10W的访问量，目前我公开worker已经基本饱和，建议使用自己的cf-worker。
步骤如下
1. 注册cloudflare https://dash.cloudflare.com/sign-up
2. 登录后选择Workers ![批注 2020-04-09 114934.jpg](https://i.loli.net/2020/04/09/w8r62KjcpP4S5Tt.jpg)
3. 点击create worker ![批注 2020-04-09 115054.jpg](https://i.loli.net/2020/04/09/KsI9qxpJhf8BciQ.jpg)
4. 在左侧编辑框中黏贴代理代码.代码在[cf-worker.js](https://github.com/jswh/synology_video_station_douban_plugin/blob/master/cf-worker.js).然后点击save and deploy ![批注 2020-04-09 115254.jpg](https://i.loli.net/2020/04/09/SMl2sQg1wfImHKx.jpg)
5. 预览界面上有你的worker代理地址，安装的时候替换即可 ![批注 2020-04-09 115714.jpg](https://i.loli.net/2020/04/09/evsglLICjf6dXE5.jpg)

所有权利归老哥所有

+++++++++
2020-02-23 更新 使用 cloudflare workers 代理服务器间接访问豆瓣
