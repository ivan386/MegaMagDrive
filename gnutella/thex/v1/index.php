<?php
require("../../../findinfo.php");
require("../../../dime.php");

function senddime($filettbf, $filedxml)
{
	if (isset($filedxml) && !empty($filedxml) && is_file($filedxml) &&
		isset($filettbf) && !empty($filettbf) && is_file($filettbf))
	{
		$dxmlsize=filesize($filedxml);
		$ttbfsize=filesize($filettbf);
		$ttbfpadding=DIMEPadding($ttbfsize);
		header("Content-Type: application/dime");
		header('Content-Length: '.($dxmlsize + $ttbfsize + strlen($ttbfpadding)));
		readfile($filedxml);
		readfile($filettbf);
		echo $ttbfpadding;
		return true;
	}
}

foreach ($_GET as $key => $value) 
{
	
	$urn = explode(":", $key);
	if ($urn[0] == "urn")
	{
		if ($urn[1] == "tree" && ($urn[2] == "tiger/" || $urn[2] == "tiger"))
		{
			if (
			senddime(	findinfo('../../../fileinfo/', "ttr", $urn[3], "ttbf"),
						findinfo('../../../fileinfo/', "ttr", $urn[3], "dxml")
					)
			) break;
		}
	}
	

}?>