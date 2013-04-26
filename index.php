<?php
require_once("tth.php");
require_once("mime.php");
require_once("findinfo.php");
require_once("dimexml.php");
require_once("guid.php");
require("filedownload.php");

define('NAME', 'MegaMagDrive');					//Name of this script
define('VERSION', 'v.0.8.1');					//Version of this script.
define('SHA_STORE', 'sha.txt');					//Actual storage of the recent magnet sources.
define('INFO_STORE', 'fileinfo/');
define('DIRECTORY', '');						//If nothing is given, the default folder will be scanned.
define('MD5', true);							//Append an MD5 hash to the magnets?
define('MD4', true);							//Append an MD4 hash to the magnets?
define('TIGER', true);							//Append a bitprint by enabling tiger-tree hashing?
define('ENLARGE_HOVER', false);					//Shall the links be enlarged on mouse hover?
define('ED2K', true);							//Shall ED2K links be generated?
define('EXPIRE', 72);							//How many hours shall a host be cached as a magnet source?
$base32_mapping = array(						//Regular base-32 to proper base-32 conversion array.
	'0' => 'A', '1' => 'B', '2' => 'C', '3' => 'D', '4' => 'E', '5' => 'F', '6' => 'G', '7' => 'H',
	'8' => 'I', '9' => 'J', 'a' => 'K', 'b' => 'L', 'c' => 'M', 'd' => 'N', 'e' => 'O', 'f' => 'P',
	'g' => 'Q', 'h' => 'R', 'i' => 'S', 'j' => 'T', 'k' => 'U', 'l' => 'V', 'm' => 'W', 'n' => 'X',
	'o' => 'Y', 'p' => 'Z', 'q' => '2', 'r' => '3', 's' => '4', 't' => '5', 'u' => '6', 'v' => '7'
);
$browsers = array(
	'opera',
	'MSIE',
	'gecko',
	'windows',
	'firefox',
	'infopath',
	'mozilla'
);
function remove_junk($item) {
	$ignore_list = array(
		'.',
		'..',
		'index.php',
		'dimexml.php',
		'guid.php',
		'dime.php',
		'mime.php',
		'tth.php',
		'findinfo.php',
		'filedownload.php',
		'magnet-icon-14w-14h.gif',
		'Thumbs.db',
		SHA_STORE,
		'ed2k.jpeg',
		'cgi-bin',
		'error_log',
		'.htaccess'
	);
	return (!in_array($item, $ignore_list) && strpos($item, '.pureftpd-upload') !== 0);
}
function give_error() {
	global $server_name;
	header('HTTP/1.1 404 Not Found');
	header('Server: '.$server_name, true);
	header('Remote-IP: '.$_SERVER['REMOTE_ADDR']);
	header('X-Nick: '.NAME);
	header('Connection: close');
}
function save_data() {
	global $remote_host;
	check();
	$file1 = fopen(SHA_STORE, 'rb');
	flock($file1, LOCK_EX);
	$data = explode("\n", fread($file1, filesize(SHA_STORE)));
	flock($file1, LOCK_UN);
	fclose($file1);
	if (!empty($data)) {
		foreach ($data as $line) {
			$search = array();
			$hits = $sources_ = $sha = '';
			$line = trim($line);
			if (strpos($line, '|') !== false) {
				list($sha, $sources_, $hits) = explode('|', $line);
				if (!empty($sha)) {
					if (!empty($sources_)) {
						$each = explode('-', $sources_);
						foreach ($each as $line_line) {
							list(,$time) = explode('*', $line_line);
							if ((time() - $time) <= (3600 * EXPIRE)) {	$search[] = $line_line;	}
						}
						if (!empty($search)) {	$file[$sha] = implode('-', $search);	}
					}
					if (!empty($hits)) {	$file2[$sha] = $hits;	}
					else {	$file2[$sha] = 0;	}
				}
			}
		}
	}
	if (isset($file[strtoupper($_GET['sha1'])])) {
		if (strpos($file[strtoupper($_GET['sha1'])], $_SERVER['REMOTE_ADDR']) === false) {
			$imp = array(
				$remote_host.'*'.time(),
				$file[strtoupper($_GET['sha1'])]
			);
			$file[strtoupper($_GET['sha1'])] = implode('-', $imp);
			
		}
	}
	else {	$file[strtoupper($_GET['sha1'])] = $remote_host.'*'.time();	}
	if (isset($file2[strtoupper($_GET['sha1'])])){ $file2[strtoupper($_GET['sha1'])] += 1; }else{ $file2[strtoupper($_GET['sha1'])] = 1;};
	$file1 = fopen(SHA_STORE, 'r+');
	flock($file1, LOCK_EX);
	ftruncate($file1, 0);
	foreach ($file2 as $sha=>$hits) {
		if (isset($file[$sha]))  fwrite($file1, $sha.'|'.$file[$sha].'|'.$hits."\r\n");
		else fwrite($file1, $sha.'||'.$hits."\r\n");}
	flock($file1, LOCK_UN);
	fclose($file1);
}
function check() {
	if (file_exists(SHA_STORE) === false) {
		$file2 = fopen(SHA_STORE, 'w');
		fclose($file2);
	}
}

function grab_sources($sha) {
	check();
	if (filesize(SHA_STORE) == 0) return 0;
	$sha = strtoupper($sha);
	$file1 = fopen(SHA_STORE, 'rb');
	flock($file1, LOCK_EX);
	$data = explode("\n", fread($file1, filesize(SHA_STORE)));
	flock($file1, LOCK_UN);
	fclose($file1);
	//print_r($data);
	if (!empty($data)) {
		
		foreach ($data as $line) {
			if (!empty($line)){
				list($sha_, $ips, $hits) = explode('|', $line);
				if ($sha_ == $sha) {
					if (!empty($ips)) {	return substr_count($ips, '*');	}
					else {	return '0';	}
				}
			}
		}
		return 0;
	}
	else {
		return 0;
	}
}
function grab_hits($sha) {
	check();
	if (filesize(SHA_STORE) == 0) return 0;
	$sha = strtoupper($sha);
	$file1 = fopen(SHA_STORE, 'rb');
	flock($file1, LOCK_EX);
	$data = explode("\n", fread($file1, filesize(SHA_STORE)));
	flock($file1, LOCK_UN);
	fclose($file1);
	if (!empty($data)) {
		foreach ($data as $line) {
			if (!empty($line)){
				list($sha_, $ips, $hits) = explode('|', $line);
				if ($sha_ == $sha) {
					$hits = trim($hits);
					if (ctype_digit($hits)) {	return $hits;	}
					else {	return 0;	}
				}
			}
		}
		return 0;
	}
	else {
		return 0;
	}
}

/*
function getTTH($sha1){
	$ttbffilepath = findinfo(INFO_STORE, 'sha1', $_GET['sha1'], 'ttbf');
	$ttbfdata = fopen($ttbffilepath, 'rb');
	fseek($ttbfdata, 0);
	$tthash = TTH::base32encode(fread($ttbfdata, 24));
	fclose($ttbfdata);
	return $tthash;
}
*/

function sha1hf($filepath)
{
	return strtoupper(convert_hex_to_base32(sha1_file($filepath)));
}

function findtarget($directory, $hash, $hashname, $hashfunction)
{
	$locnfile = findinfo(INFO_STORE, $hashname, $hash, 'locn');
	if (isset($locnfile) && !empty($locnfile) && is_file($locnfile))
	{
		$locations = file($locnfile);
		if (isset($locnfile) && !empty($locnfile) && is_file($locnfile))
		{
			foreach ($locations as $file)
			{
				$filepath = dirname(__FILE__).trim($file);
				if (is_file($filepath) && $hash == $hashfunction($filepath))
				{
					return $filepath;
				}
			}	
		}
	}
	

	foreach ($directory as $file) 
	{
		$filepath = $directory_path.'/'.$file;
		if (is_file($filepath) && $hash == $hashfunction($filepath))
		{
			return $filepath;
		}
	}
	
	return NULL;
}

function give_sources($file) {
	global $server_name, $directory, $directory_path;
	foreach ($file[strtoupper($_GET['sha1'])] as $so) {
		list($host, $time) = explode('*', $so);
		$time2 = gmdate('Y-m-d', $time);
		$fullhosts_all[] = 'http://'.$host.'/uri-res/N2R?urn:sha1:'.strtoupper($_GET['sha1']).' '.$time2.'T00:00Z';
		$hosts_all[] = $host;
	}
	$hash['sha1'] = $_GET['sha1'];
	//$hash['tiger'] = getTTH($_GET['sha1']);
	//header('HTTP/1.1 206 OK');
	
	header('Server: '.$server_name);
	header('Remote-IP: '.$_SERVER['REMOTE_ADDR']);
	header('Retry-After: 10');
	//header('Connection: Keep-Alive'); //Default
	//header('Accept-Ranges: bytes');
	
	header('X-PerHost: 2');
	header('X-Nick: '.NAME);

	$targetfile = NULL;
	
	if (isset($_GET['t']))	//Only redirect
	{
		if ($_GET['t']=='r')
		{
			header('HTTP/1.1 301 OK');
			
			
			foreach ($fullhosts_all as $so)
			{
				header("Location: ".$so , false);
			}
			header("Location: http://".$_SERVER["HTTP_HOST"].'/'.rawurlencode($file), false);
			//header("Location: http://cache.freebase.be/".$hash['sha1'], false);
			echo implode('\n', $fullhosts_all);
		}
		elseif ($_GET['t']=='l') //Only redirect
		{
			header('HTTP/1.1 300 OK');
			foreach ($fullhosts_all as $so)
			{
				header("Location: ".$so , false);
			}
			header("Location: http://".$_SERVER["HTTP_HOST"].'/'.rawurlencode($file), false);
			echo "http://".$_SERVER["HTTP_HOST"].'/'.rawurlencode($file);
			echo implode('\n', $fullhosts_all);
		}
	}
	else //Find the file
	{
		$targetfile = findtarget($directory, strtoupper($hash['sha1']),"sha1", "sha1hf");
		//It need to send before shareaza get URN headers and mark this connection as P2P.
		if (isset($targetfile) && !empty($targetfile) && is_file($targetfile))
			header('Content-Disposition: attachment; filename="' . rawurlencode(basename($targetfile)) . '"');
	}
	
	
	//header('X-Content-URN: urn:sha1:'.strtoupper($_GET['sha1']), false);
	//header('X-Content-URN: urn:tree:tiger:'.strtoupper($hash['tiger']), false);
	
	$urnsfile = findinfo(INFO_STORE, 'sha1', strtoupper($_GET['sha1']), 'urns');
	if (isset($urnsfile) && !empty($urnsfile) && is_file($urnsfile))
	{
		$urns = file($urnsfile);
		foreach ($urns as $urn) 
		{
			$urn = trim($urn);
			if (!empty($urn)){
				header('X-Content-URN: urn:'.$urn, false);
				list($urnname, $param1) = explode(':', $urn, 2);
				switch ($urnname) 
				{
					case 'sha1':
						$hash['sha1'] = $param1;
						break;
					case 'md5':
						$hash['md5'] = $param1;
						break;
					case 'md4':
						$hash['md4'] = $param1;
						break;
					case 'ed2k':
						$hash['ed2k'] = $param1;
						break;
					case 'tree':
						list($param1, $param2) = explode(':', $param1, 2);
						if ($param1 == 'tiger')
						{
							$hash['tiger'] = $param2;
						}
						break;
					case 'bitprint':
						$hash['bit'] = $param1;
				}
			}
		}
	}
	if (isset($hash['tiger'])) header('X-Thex-URI: /gnutella/thex/v1/?urn:tree:tiger/:'.strtoupper($hash['tiger']).'&ed2k=0&depth=9; '.strtoupper($hash['tiger']));
	$ttbffile = findinfo(INFO_STORE, 'sha1', $_GET['sha1'], 'ttbf');
	if (!empty($ttbffile) && is_file(INFO_STORE.$ttbffile)) header('X-TigerTree-Path: /'.INFO_STORE.rawurlencode(basename($ttbffile)));
	header('X-SourceProvider: true');
	header('X-Alt: '.implode(',', $hosts_all));
	header('Alt-Location: '.implode(', ', $fullhosts_all));

	if (isset($targetfile) && !empty($targetfile) && is_file($targetfile))
	{	
		header("X-Available-Ranges: bytes 0-".filesize($targetfile));
		set_time_limit(60);
		@downloadFile($targetfile , mime_content_type($targetfile));
	}
	else
	{
		header("HTTP/1.1 503 Bisy");
		header("X-Available-Ranges: bytes 0-0");
	}
}

function give_file() {
	check();
	$data = file(SHA_STORE);
	if (!empty($data)) {
		foreach ($data as $line) {
			$line = trim($line);
			if (strpos($line, '|') !== false) {
				list($sha, $sources) = explode('|', $line);
				if (!empty($sources)) {	$file[$sha] = explode('-', $sources);	}
			}
		}
		if (isset($file[strtoupper($_GET['sha1'])])) {	give_sources($file);	}
		else {	give_error();	}
	}
}

function convert_hex_to_base32($original_hexadecimal) {
	$result = $base32_full_string = '';
	global $base32_mapping;
    for ($pos = 0; $pos < strlen($original_hexadecimal); $pos += 10) {
        $base32_chunk = base_convert(substr($original_hexadecimal, $pos, 10), 16, 32);
		while (8 - strlen($base32_chunk) > 0) {	$base32_chunk = '0'.$base32_chunk;	}
		$base32_full_string .= $base32_chunk;
    }
    for ($character_index = 0; $character_index < strlen($base32_full_string); $character_index++) {	$result .= $base32_mapping[$base32_full_string[$character_index]];	}
    return $result;
}

/*
function ed2k_md4_chunks($data) {
	$total_splits = strlen($data) / 9728000;	$md4_chunks = '';
	if ($total_splits >= 1) {
		for ($pos = 0; $pos <= $total_splits; $pos++) {
			$md4_chunks .= hash('md4', substr($data, ($pos * 9728000), 9728000));
		}	
		return $md4_chunks;
	}else {
		return hash('md4', $data);
	}
}
*/

function ed2k_hash($data) {
	$total_splits = strlen($data) / 9728000;	$md4_chunks = '';
	if ($total_splits >= 1) {	for ($pos = 0; $pos <= $total_splits; $pos++) {	$md4_chunks .= hash('md4', substr($data, ($pos * 9728000), 9728000));	}	}
	else {	$md4_chunks = $data;	}
	return hash('md4', $md4_chunks);
}
function shorten_size($size) {
	$unit = 'Bytes';
	if ($size > 1024) {
		$unit = 'KB';	$size /= 1024;
		if ($size > 1024) {
			$unit = 'MB';	$size = round($size, 3) / 1024;
			if ($size > 1024) {
				$unit = 'GB';	$size = round($size, 3) / 1024;
			}
		}
	}
	return round($size, 3).' ('.$unit.')';
}




if (isset($_SERVER)) {
	(isset($_SERVER['SERVER_PORT'])) ? $port = $_SERVER['SERVER_PORT'] : $port = 80;
	(isset($_SERVER['HTTP_USER_AGENT'])) ? $user_agent = $_SERVER['HTTP_USER_AGENT'] : $user_agent = false;
	if (isset($_SERVER['REMOTE_ADDR'])) {
		if (isset($_SERVER['HTTP_LISTEN_IP'])) {
			if (strpos($_SERVER['HTTP_LISTEN_IP'], $_SERVER['REMOTE_ADDR']) === false) {
				if (isset($_SERVER['HTTP_X_NODE'])) {
					$remote_host = $_SERVER['HTTP_X_NODE'];
					if (strpos($remote_host, $_SERVER['REMOTE_ADDR']) === false && isset($_SERVER['HTTP_NODE'])) {
						$remote_host = $_SERVER['HTTP_NODE'];
						if (strpos($remote_host, $_SERVER['REMOTE_ADDR']) === false) {	$remote_host = $_SERVER['REMOTE_ADDR'].':'.$_SERVER['REMOTE_PORT'];	}
					}
					elseif (strpos($remote_host, $_SERVER['REMOTE_ADDR']) === false) {	$remote_host = $_SERVER['REMOTE_ADDR'].':'.$_SERVER['REMOTE_PORT'];	}
				}
				elseif (isset($_SERVER['HTTP_NODE'])) {	$remote_host = $_SERVER['HTTP_NODE'];	}
				else {	$remote_host = $_SERVER['REMOTE_ADDR'].':'.$_SERVER['REMOTE_PORT'];	}
			}
			else {	$remote_host = $_SERVER['HTTP_LISTEN_IP'];	}
		}
		else {	$remote_host = $_SERVER['REMOTE_ADDR'].':'.$_SERVER['REMOTE_PORT'];	}
	}
	else {	die('ERROR: No server supplied remote IP reference.');	}
	(isset($_SERVER['SERVER_NAME'])) ? $server_name = $_SERVER['SERVER_NAME'] : ((isset($_SERVER['SERVER_HOST'])) ? $server_name = $_SERVER['SERVER_HOST'] : die('ERROR: Server name not detected!'));
	(isset($_SERVER['PHP_SELF'])) ? ($url = $server_name.(($port != 80) ? ':'.$port : '').$_SERVER['PHP_SELF']) : die('ERROR: File directory reference not detected!');
}
else {
	die('ERROR: Environment variables missing!');
}
$directory_path = dirname(__FILE__).((DIRECTORY === '') ? '': '/'.DIRECTORY);	//Local Path
$url_path = dirname($url).'/'.DIRECTORY;										//URL Path
$url_path2 = dirname($url).'/';													//Base URL Path
if (!isset($_GET['sha1'])) {
?>
<!DOCTYPE html
PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta name="robots" content="noindex,nofollow,noarchive" />
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<style type="text/css">
			#main {
				margin: 50px auto 5px;
			}
			#footer {
				margin-top: 30px;
			}
			div.center {
				text-align: center;
			}
			span {
				font-family: courier;
			}
			span.error {
				color: rgb(255, 0, 0);
			}
			table#listings {
				margin: auto;
				width: 85%;
				border-width: 3px;
				border-style: dotted;
				border-top-color: rgb(255, 0, 0);
				border-bottom-color: rgb(255, 0, 0);
				border-left-color: rgb(255, 0, 0);
				border-right-color: rgb(255, 0, 0);
				background-color: rgb(255, 0, 0);
				border-collapse: collapse;
				font-family: arial;
			}
			tr#list_top {
				background-color: rgb(180, 180, 180);
			}
			tr#head {
				background-color: rgb(150, 150, 150);
			}
			tr.listing1 {
				background-color: rgb(225, 225, 225);
			}
			tr.listing2 {
				background-color: rgb(240, 240, 240);
			}
			tr {
				text-align: center;
			}
			td.top {
				text-decoration: underline;
				font-size: large;
			}
			td#top_top {
				text-decoration: underline;
				font-size: x-large;
			}
			td {
				padding-left: 25px;
				padding-right: 25px;
			}
			body {
				background-color: rgb(128, 128, 255);
			}
			a:link {
				color: rgb(50, 50, 50);
			}
			a:visited {
				color: rgb(15, 15, 15);
			}
			a:hover {
				color: rgb(120, 120, 120);
			}
			a:active {
				color: rgb(110, 110, 110);
			}
			a.main_table_links:hover {
<?php
	if (ENLARGE_HOVER) {
?>
				font-size: large;
<?php
	}
?>
				background-color: rgb(255, 255, 180);
			}
			a#footer_links:hover {
				font-size: large;
			}
		</style>
		<title><?php echo(NAME); ?></title>
	</head>
	<body>
		<div id="main">
<?php
	if (false !== ($directory = @scandir($directory_path))) {
		if (is_array($directory)) {
			if (!empty($directory)) {	$directory = array_filter($directory, 'remove_junk');	}
			if (!empty($directory)) {
				$files_found = count($directory);
?>
			<table id="listings">
				<tr id="head">
					<td id="top_top" colspan="5">
						<?php echo($files_found); ?> File<?php echo((($files_found > 1) ? 's' : '')); ?> Found
					</td>
				</tr>
				<tr id="list_top">
					<td class="top">
						File<?php echo((($files_found > 1) ? 's' : '')); ?>:
					</td>
					<td class="top">
						Magnet Links:
					</td>
					<td class="top">
						Magnet Sources:
					</td>
					<td class="top">
						Magnet Hits:
					</td>
					<td class="top">
						Size:
					</td>
				</tr>
<?php
				$row_color_tracking = 1;
				foreach ($directory as $file) {
					$target = $directory_path.'/'.$file;
					if (is_file($target)){
						$size = filesize($target);
						unset($hash);
						$hash['sha1_16'] = sha1_file($target);
						$hash['sha1'] = convert_hex_to_base32($hash['sha1_16']);
						$urnsfile = findinfo(INFO_STORE, 'sha1', $hash['sha1'], 'urns');
						if (isset($urnsfile) && !empty($urnsfile) && is_file($urnsfile))
						{
							$urns = file($urnsfile);
							foreach ($urns as $urn) 
							{
								$urn = trim($urn);
								list($urnname, $param1) = explode(':', $urn, 2);
								switch ($urnname) 
								{
									case 'sha1':
										$hash['sha1'] = $param1;
										break;
									case 'md5':
										$hash['md5'] = $param1;
										break;
									case 'md4':
										$hash['md4'] = $param1;
										break;
									case 'ed2k':
										$hash['ed2k'] = $param1;
										break;
									case 'tree':
										list($param1, $param2) = explode(':', $param1, 2);
										if ($param1 == 'tiger')
										{
											$hash['tiger'] = $param2;
										}
										break;
									case 'bitprint':
										$hash['bit'] = $param1;
								}
							}
						}
						
						$fullfileinfo = 'sha1-'.$hash['sha1'] ;
						if (MD5 && empty($hash['md5'])) {
							$hash['md5'] = md5_file($target);
							$fullfileinfo = $fullfileinfo.'_md5-'.$hash['md5'] ;
						}
						if (MD4 && empty($hash['md4'])) {
							$hash['md4'] = hash_file('md4', $target);
							$fullfileinfo = $fullfileinfo.'_md4-'.$hash['md4'] ;
						}
						if (ED2K && empty($hash['ed2k'])) {
							$hash['ed2k'] = ed2k_hash(file_get_contents($target, FILE_BINARY)); 
							$fullfileinfo = $fullfileinfo.'_ed2k-'.$hash['ed2k'] ;
						}
						if (TIGER && empty($hash['tiger'])) {
							$ttbffilepath = findinfo(INFO_STORE, 'sha1', $hash['sha1'], 'ttbf');
							
							$ttbfdata = NULL;
							$deph = 9;
							
							if (!file_exists($ttbffilepath)){
								$fulltigertree = TTH::getTigerTree($target, $deph);
								//echo($fulltigertree);
								$tigertreerootbin=substr($fulltigertree, 0, 24);
								$tigertreeroot = TTH::base32encode($tigertreerootbin);
								$fullfileinfo = $fullfileinfo.'_ttr-'.$tigertreeroot ;
								/*Write DIME XML */
								writedimexml(INFO_STORE.$fullfileinfo.'.dxml', $size, strlen($fulltigertree), $deph, guidfrom(bin2hex($tigertreerootbin)));
								/*End DIME XML */
								
								/*Write TigerTree Raw*/
								$ttbfdata = fopen(INFO_STORE.$fullfileinfo.'.ttbf', 'w+b');
								flock($ttbfdata, LOCK_EX);
								fwrite($ttbfdata, $fulltigertree);
								fflush($ttbfdata);
								flock($ttbfdata, LOCK_UN);
								/*End TigerTree Raw*/
								
								unset($fulltigertree);
							}else{
								$ttbfdata = fopen($ttbffilepath, 'rb');
							}
							fseek($ttbfdata, 0);
							$hash['tiger'] = TTH::base32encode(fread($ttbfdata, 24));
							//$hash['tiger'] = tiger(file_get_contents($target, FILE_BINARY));
							fclose($ttbfdata);
						}
						
						if (!empty($hash['tiger']) && !empty($hash['sha1']) && empty($hash['bit']))
						{
							$hash['bit'] = strtolower($hash['sha1']).'.'.strtolower($hash['tiger']);
						}
						
						$locnfile = findinfo(INFO_STORE, 'sha1', $hash['sha1'], 'locn');
						if (!isset($locnfile) || empty($locnfile) || !is_file($locnfile)){
							$locnhnd = fopen(INFO_STORE.$fullfileinfo.'.locn', 'w');
							$location = substr($target,strlen(dirname(__FILE__)));
							fwrite($locnhnd , $location);
							fclose($locnhnd);
						}
						
						if (!isset($urnsfile) || empty($urnsfile) || !is_file($urnsfile)){
							$urnsfhnd = fopen(INFO_STORE.$fullfileinfo.'.urns', 'w');
															fwrite($urnsfhnd, 'sha1:'.$hash['sha1']."\n");
							if (MD5 && !empty($hash['md5'])) fwrite($urnsfhnd, 'md5:'.$hash['md5']."\n");
							if (MD4 && !empty($hash['md4'])) fwrite($urnsfhnd, 'md4:'.$hash['md4']."\n");
							if (ED2K && !empty($hash['ed2k'])) fwrite($urnsfhnd, 'ed2k:'.$hash['ed2k']."\n");
							if (TIGER && !empty($hash['tiger'])) fwrite($urnsfhnd, 'tree:tiger:'.$hash['tiger']."\n");
						}
?>
					<tr class="listing<?php echo($row_color_tracking); ?>">
						<td>
							<a href="http://<?php echo($url.'?sha1='.strtoupper($hash['sha1'])."&xl=".$size."&dn=".rawurlencode($file) /*$url_path.$file*/); ?>" class="main_table_links" target="_blank"><?php echo($file); ?></a> 
						</td>
						<td>
							<a href="magnet:?xt=urn:sha1:<?php
							echo(strtolower($hash['sha1']));
							if (MD5) {	echo('&xt=urn:md5:'.strtolower($hash['md5']));	}
							if (MD4) {	echo('&xt=urn:md4:'.strtolower($hash['md4']));	}
							if (ED2K) {	echo('&xt=urn:ed2k:'.($hash['ed2k']));	}
							if (TIGER) {
								echo('&xt=urn:bitprint:'.$hash['bit']);
								echo('&xt=urn:tree:tiger:'.strtolower($hash['tiger']));
							}
							echo('&xl='.$size);
							echo('&dn='.rawurlencode($file));
							echo('&xs='.rawurlencode('http://'.$url.'?sha1='.strtoupper($hash['sha1'])));
							//echo('&xs='.rawurlencode('http://cache.freebase.be/'.strtoupper($hash['sha1'])));
							//echo('&as='.rawurlencode('http://'.$url_path.$file));

							?>" target="_blank">
								<img src="magnet-icon-14w-14h.gif" alt="magnet-link">
							</a>
<?php
						if (ED2K) {
?>

							<a href="ed2k://|file<?php
							echo('|'.rawurlencode($file));
							echo('|'.$size);
							echo('|'.$hash['ed2k']);
							?>|/" target="_blank">
								<img src="ed2k.jpeg" alt="ed2k-link">
							</a>
<?php
						}
?>
						</td>
						<td><?php echo(grab_sources(strtoupper($hash['sha1']))); ?></td>
						<td><?php echo(grab_hits(strtoupper($hash['sha1']))); ?></td>
						<td><?php echo(shorten_size($size)); ?></td>
					</tr>
<?php
						$row_color_tracking = ($row_color_tracking == 1) ? 2 : 1;
					}
				}
?>
			</table>
<?php
			}
			else {
?>
			<div class="center">
				<span class="error">The directory <?php echo($directory_path); ?> is empty.</span>
			</div>
<?php
			}
		}
		else {
?>
			<div class="center">
				<span class="error">Scanning the <?php echo($directory_path); ?> directory has returned an error.</span>
			</div>
<?php
		}
	}
	else {
?>
			<div class="center">
				<span class="error"><?php echo($directory_path); ?> is not a directory.</span>
			</div>
<?php
	}
?>
		</div>
		<div id="footer" class="center"><a href="http://mymagdrive.grantgalitz.com/" id="footer_links"><?php echo(NAME.' '.VERSION); ?></a><div>
	</body>
</html>
<?php
}
else {
	if ($user_agent !== false) {
		if ($user_agent != 'Mozilla/4.0') {
			foreach ($browsers as $browser_user_agent) {
				if (stripos($user_agent, $browser_user_agent) !== false) {
					give_file();
					exit();
					break;
				}
			}
		}
	}
	/*if (false !== ($directory = @scandir($directory_path))) {
		if (is_array($directory)) {
			if (!empty($directory)) {	$directory = array_filter($directory, 'remove_junk');	}
			if (!empty($directory)) {
				//$sha1_hashes[] = $_GET['sha1'];
				foreach ($directory as $file) {
					if (is_file($file)){
						$sha1_hashes[] = strtoupper(convert_hex_to_base32(sha1_file($directory_path.'/'.$file)));
					}
				}
				if (in_array(strtoupper($_GET['sha1']), $sha1_hashes)) {*/
					save_data();
					give_file();
				/*}
				else {
					give_error();
				}
			}
			else {
				give_error();
			}
		}
		else {
			give_error();
		}
	}
	else {
		give_error();
	}*/
}
?>