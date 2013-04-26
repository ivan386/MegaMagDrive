<?php
	function findinfo($directoryl, $hashtypel, $hashl, $filetypel){
		$htandh = $hashtypel.'-'.$hashl;
		if (false !== ($directorylistl = @scandir($directoryl))) {
			foreach ($directorylistl as $filel) {
				$targetl = $directoryl.'/'.$filel;
				if (is_file($targetl)){
					$nameextlist = explode(".", $filel);
					if ($nameextlist[1] == $filetypel) {
						$nameextlist[0] = explode("_", $nameextlist[0]);
						foreach ($nameextlist[0] as $typeandhash) {
							if ($typeandhash == $htandh){
								return $targetl;
							}
						}
					}
				}
			}
		}
		return false;
	}
?>