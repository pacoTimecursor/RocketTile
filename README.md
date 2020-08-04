# RocketTile
It's rewrote and modified with PHP extension Swoole (https://github.com/swoole/swoole-src) base on the TileServer PHP (https://github.com/maptiler/tileserver-php).

# How to use?

1. First at all, it's required installation of docker, and run `docker pull phpswoole/swoole`.

2. Run the swoole image with some params such like this: `docker run -v /path/to/project:/var/www -p 9501:9501 phpswoole/swoole php /var/www/rockettile.php`

3. The folder contains a test tileset, Using your browser to open the `map.html` and you will see a bueatiful city - Barcelona, where lives my friend Marc.

# Thanks to

 - Swoole
 - MapTiler
 
