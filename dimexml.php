<?php
	require_once("dime.php");
	function writedimexml($xmlfilename, $size, $bfsize, $depth, $guid){
		/* Begin of DIME */
		$bftypeid = 'http://open-content.net/spec/thex/breadthfirst';
			
		/* Begin of XML Block */
		$xmldata = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<!DOCTYPE hashtree SYSTEM \"http://open-content.net/spec/thex/thex.dtd\">
<hashtree>
	<file size=\"$size\" segmentsize=\"1024\"/>
	<digest algorithm=\"http://open-content.net/spec/digest/tiger\" outputsize=\"24\"/>
	<serializedtree depth=\"$depth\" type=\"$bftypeid\" uri=\"$guid\"/>
</hashtree>";
		$dimedata  = ToDIME(1 , '', 0, 'text/xml', 8, $xmldata, strlen($xmldata));
		/* End of XML Block */
				
		/* Begin of Tiger Tree Block */
		$dimedata .= ToDIME(2 , $guid, strlen($guid), $bftypeid, strlen($bftypeid)  , NULL /* HACK: Data  write after*/, $bfsize);
		$dimepartfile = fopen($xmlfilename, "w");
		fwrite($dimepartfile, $dimedata);
		fclose($dimepartfile);
	}
?>