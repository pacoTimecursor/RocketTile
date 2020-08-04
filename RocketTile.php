<?php 
/**
 * 
 * RocketTile
 * 
 * @author Pacoz
 * 
 */


$http = new Swoole\Http\Server("0.0.0.0", 9501);
$rt = new RocketTile();

$http->on('request', function ($request, $response) use ($rt) {

	if($request->server['request_uri'] == '/favicon.ico') {
		$response->status(404);
		$response->end();
		return;
	}

    if(!isset($request->get['action'])){
		$response->status(404);
		$response->end();
		return;
    }

    if($request->get['action'] == 'getConfig' && isset($request->get['config']) ){
    	$config_path = $rt->getConfigPath($request->get['config']);
		if(!$config_path){
			return $response->end('Error: the indicated config is not existed.');
		}
		$config = file_get_contents($config_path);
		$response->header('Access-Control-Allow-Origin', '*');
		$response->end($config);
    }

	if( $request->get['action'] == 'getFt' && isset($request->get['ft']) && isset($request->get['range']) ){
    	$ft_path = $rt->getFtPath($request->get['ft'], $request->get['range']);
		if(!$ft_path){
			return $response->end('Error: the indicated font is not existed.');
		}
		$response->header('Access-Control-Allow-Origin', '*');
		$ft = file_get_contents($ft_path);
		$response->end($ft);
	}

	if($request->get['action'] == 'getTile' ){
		if(!$rt->checkParams($request->get) ){
			$response->end('Error: missing required param.');
		}else if(!$rt->isDBLayer($request->get['map'])){
			$response->end('Error: the map is no existed.');
		}
		
		$db_res = $rt->DBconnect($request->get['map'].'.mbtiles');
		if(!$db_res['res']){
			$response->end('Error: db connection failed.');
		}
	
		foreach($rt->getHeaders() as $h => $v){
			$response->header($h, $v);
		}
		$data = $rt->getTile($db_res['db'], $request->get['z'], $request->get['x'], $request->get['y']);
		$response->end($data);
	}

});

$http->start();



class RocketTile
{
	public $path_root;
	public $path_config;
	public $path_ft;

	public function __construct($path_root = '', $path_config = '', $path_ft = '')
	{
		$this->path_root = $path_root;
		$this->path_config = $path_config;
		$this->path_ft = $path_ft;
	}

	public function getConfigPath($filename){
		if(empty($filename) || !is_string($filename) ) return false;
		$path = !empty($this->path_config) ? $this->path_config . DIRECTORY_SEPARATOR . $filename : $filename;
		return is_file($path) ? $path : false;
	}

	public function getFtPath($ft, $range){
		if(empty($ft) || !is_string($ft) ) return false;
		if(empty($range) || !is_string($range) ) return false;
		$filename = $ft . DIRECTORY_SEPARATOR . $range . '.pbf';
		$path = !empty($this->path_ft) ? $this->path_ft . DIRECTORY_SEPARATOR . $filename : $filename;
		return is_file($path) ? $path : false;
	}

	public function isDBLayer($layer) {
		if(empty($layer) || !is_string($layer) ) return false;
		$path = !empty($this->path_root) ? $this->path_root . DIRECTORY_SEPARATOR . $layer : $layer;
		return is_file($path.'.mbtiles') ? true : false;
	}


	public function getHeaders(){
		return array(
		  'Access-Control-Allow-Origin' => '*',
		  'Content-Encoding' => 'gzip',
		  'Content-type' => 'application/x-protobuf',
		);
	}

	public function checkParams($params){
		$result = true;
		if( !isset($params['map']) || !is_string($params['map']) ){
			$result = false;
		}
		if( !isset($params['z']) || !is_numeric($params['z']) ){
			$result = false;
		}
		if( !isset($params['x']) || !is_numeric($params['x']) ){
			$result = false;
		}
		if( !isset($params['y']) || !is_numeric($params['y']) ){
		 	$result = false;
		}
		return $result;
	}

	public function getTile($db, $z, $x, $y){
		$z = floatval($z);
		$y = floatval($y);
		$y = pow(2, $z) - 1 - $y; // Paco: Does anybody can explain me why should do this -> pow(2, $z) - 1 - $y ?
		$x = floatval($x);

		$result = $db->query('select tile_data as t from tiles where zoom_level=' . $z . ' and tile_column=' . $x . ' and tile_row=' . $y);
		$data = $result->fetchColumn();
		return !empty($data) ? $data : '';
	}

	public function DBconnect($tileset) {
		$result = array(
		  'db' => null,
		  'res' => false,
		  'msg' => '',
		);

		try {
			$result['db'] = new PDO('sqlite:' . $tileset, '', '', array(PDO::ATTR_PERSISTENT => true));
			$result['res'] =  true;
		} catch (Exception $exc) {
			$result['msg'] = $exc->getTraceAsString();
		}

		return $result;
	}

}
