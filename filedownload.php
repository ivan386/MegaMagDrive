<?php
function downloadFile($filename, $mimetype='application/octet-stream') {
	if (!file_exists($filename)) die('Файл не найден');
	if (empty($mimetype)) $mimetype='application/octet-stream';
	$from=$to=0; $cr=NULL;
	$fullsize = filesize($filename);
	$blocksize = $fullsize;
	

	if (isset($_SERVER['HTTP_RANGE'])) 
	{
		$range=substr($_SERVER['HTTP_RANGE'], strpos($_SERVER['HTTP_RANGE'], '=')+1);
		$from=strtok($range, '-');
		$to=strtok('/');
		if (isset($to) && !empty($to)) 
		{
			$blocksize = $to - $from + 1;
			if ($blocksize + $from > $fullsize) {
				$blocksize = $fullsize - $from;
			}
		}
		else 
		{
			$blocksize = $fullsize - $from;
		}
		
		header('HTTP/1.1 206 Partial Content');
		$cr='Content-Range: bytes ' . $from . '-' . ($from+$blocksize-1). '/' . $fullsize;
	}
	else	header('HTTP/1.1 200 Ok');

	$etag=md5($filename);
	$etag=substr($etag, 0, 8) . '-' . substr($etag, 8, 7) . '-' . substr($etag, 15, 8);
	header('ETag: "' . $etag . '"');

	header('Accept-Ranges: bytes');
	header('Content-Length: ' . $blocksize);
	if ($cr) header($cr);

	//header('Connection: close');
	header('Content-Type: ' . $mimetype);
	header('Last-Modified: ' . gmdate('r', filemtime($filename)));
	$f=fopen($filename, 'rb');
	//header('Content-Disposition: attachment; filename="' . basename($filename) . '";');
	if ($from) fseek($f, $from, SEEK_SET);

	$downloaded=0;
	while(!feof($f) and !connection_status() and ($downloaded<$blocksize)) {
		$miniblock=512000;
		if ($miniblock > $blocksize - $downloaded){
			$miniblock = $blocksize - $downloaded;
		}
		echo fread($f, $miniblock);
		$downloaded+=$miniblock;
		flush();
	}
	fclose($f);
}
?>