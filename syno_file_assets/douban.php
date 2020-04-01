<?php
function build_proxy_url($url) {
    return 'https://quiet-cake-f23b.jswh-cf-workers.workers.dev/-----' . $url;
}
function getRequest($url) {
    return HTTPGetRequest(build_proxy_url($url));
}
class DoubanMovie
{
    public $data, $id;
    public $mobile_data;
    public $json;
    public function __construct($id, $data)
    {
        $this->id = $id;
        $this->data = $data;
        $json = $this->pregTagContent('/type="application\/ld\+json">(.*\s*)<\/script>/', '</script>');
        $this->json = @json_decode($json, true);
    }

    public function getTitle()
    {
        if ($this->json) {
            return $this->json['name'];
        }
        return '未知';
    }


    public function getOriginalTitle()
    {
        if ($this->json) {
            return $this->json['name'];
        }
        return '未知';
    }

    public function getTagline()
    {
        $tags = $this->getAkka();
        $tags = array_merge($this->getGenres(), $tags);

        return implode(',', $tags);
    }

    public function getGenres()
    {
        return $this->getJsonValue('genre', []);
    }

    public function getActors()
    {
        return $this->getJsonValue('actor', []);
    }

    public function getDirectors()
    {
        return $this->getJsonValue('director', []);
    }

    public function getWriters()
    {
        return $this->getJsonValue('author', []);
    }

    public function getOriginAvailable()
    {
        if ($this->json) {
            return $this->json['datePublished'];
        }
        return '';
    }

    public function getAkka()
    {
        $data = $this->pregOneValue('/又名:<\/span>(.*)<br\/>/');
        $akka = explode('/', $data);
        array_walk($akka, function ($item) {
            return trim($item);
        });
        return $akka;
    }

    public function getSummary()
    {
        return $this->pregTagContent('/<span property="v:summary" class="">(.*)<\/span>/', '</span>');
    }

    public function getRating()
    {
        $agg = $this->getJsonValue('aggregateRating', null);
        if ($agg) {
            return $agg['ratingValue'];
        }
        return 0;
    }

    public function getPoster()
    {
        $min = $this->getJsonValue('image', '');
        $raw = str_replace('s_ratio_poster', 'l', $min);

        return build_proxy_url(str_replace('webp', 'jpg', $raw));
    }

    protected function getJsonValue($key, $default)
    {
        if ($this->json) {
            return $this->json[$key];
        }
        return $default;
    }

    protected function pregTagContent($pattern, $closeTag) {
        $a = str_replace("\n", "", $this->data);
        $b = [];
        preg_match_all($pattern, $a, $b);
        $b = explode($closeTag, $b[1][0]);

        return $b[0];
    }

    protected function pregOneValue($pattern)
    {
        $result = [];
        $title = null;
        preg_match_all($pattern, $this->data, $result);
        if (count($result) > 1) {
            $title = current($result[1]);
        }

        return $title;
    }
}
function GetMovieInfoDouban($movie_data, $data)
{
    if (!isset($movie_data->aka)) $movie_data->aka = array();
    $data['title']                      = $movie_data->getTitle();
    $data['original_title']             = $movie_data->getOriginalTitle();
    $data['tagline']                    = $movie_data->getTagline();
    $data['original_available']         = $movie_data->getOriginAvailable();
    $data['summary']                    = $movie_data->getSummary();
    $data['id'] = $movie_data->id;

    $data['summary'] = str_replace(' ', '', $data['summary']);
    $data['summary'] = str_replace('<br/>', "\n", $data['summary']);
    //extra
    $data['extra'] = array();
    $data['extra'][PLUGINID] = array('reference' => array());
    $data['extra'][PLUGINID]['reference']['themoviedb'] = $movie_data->id;
    $data['doubandb'] = true;

    if (isset($movie_data->imdb_id)) {
        $data['extra'][PLUGINID]['reference']['imdb'] = $movie_data->imdb_id;
    }
    $data['extra'][PLUGINID]['rating'] = array('themoviedb' => (float) $movie_data->getRating());
    $data['extra'][PLUGINID]['poster'] = array($movie_data->getPoster());
    if (isset($movie_data->backdrop_path)) {
        $data['extra'][PLUGINID]['backdrop'] = array($movie_data->backdrop_path);
    }
    if (isset($movie_data->belongs_to_collection)) {
        $data['extra'][PLUGINID]['collection_id'] = array('themoviedb' => $movie_data->belongs_to_collection->id);
    }

    if (!isset($data['genre'])) {
        $data['genre'] = [];
    }
    // genre
    foreach ($movie_data->getGenres() as $item) {
        if (!in_array($item, $data['genre'])) {
            array_push($data['genre'], $item);
        }
    }

    $keys = ['actor', 'director', 'writer'];
    foreach ($keys as $key) {
        if (!isset($data[$key])) {
            $data[$key] = [];
        }
        $fn = 'get' . ucfirst($key) . 's';
        foreach ($movie_data->{$fn}() as $item) {
            if (!in_array($item['name'], $data[$key])) {
                array_push($data[$key], $item['name']);
            }
        }
    }
    //error_log(print_r( $movie_data, true), 3, "/var/packages/VideoStation/target/plugins/syno_themoviedb/my-errors.log");
    //error_log(print_r( $data, true), 3, "/var/packages/VideoStation/target/plugins/syno_themoviedb/my-errors.log");
    return $data;
}

/**
 * @brief get metadata for multiple movies
 * @param $query_data [in] a array contains multiple movie item
 * @param $lang [in] a language
 * @return [out] a result array
 */
function GetMetadataDouban($query_data, $lang)
{
    global $DATA_TEMPLATE;

    //Foreach query result
    $result = array();
    foreach ($query_data as $item) {
        //Copy template
        $data = $DATA_TEMPLATE;
        //Get movie
        $movie_data = getRequest('https://movie.douban.com' . str_replace('movie/', '', $item)  .  '/');
        //error_log(print_r( $movie_data, true), 3, "/var/packages/VideoStation/target/plugins/syno_themoviedb/my-errors.log");
        if (!$movie_data) {
            continue;
        }
        $movie_data = new DoubanMovie(str_replace('/movie/subject/', '', $item), $movie_data);

        $data = GetMovieInfoDouban($movie_data, $data);
        return;

        //Append to result
        $result[] = $data;
    }

    return $result;
}
function test($title, $lang)
{
    if (!function_exists('HTTPGETRequest')) {
        function HTTPGETRequest($url)
        {
            return file_get_contents($url);
        }
    }
    define('PLUGINID', 'test');
    $query_data = getRequest('https://m.douban.com/search/?query=' . $title . '&type=movie');
    $detailPath = array();
    preg_match_all('/\/movie\/subject\/[0-9]+/', $query_data, $detailPath);

    //Get metadata
    return GetMetadataDouban(array_slice($detailPath[0], 0, 3), $lang);
}
#print_r(test('碟中谍6', 'chs'));
