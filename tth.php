<?php

/**
 * @author Alexey Kupershtokh <alexey.kupershtokh@gmail.com>
 * @url http://kupershtokh.blogspot.com/2007/12/on-phpclub.html
 */
class TTH {
  private static $BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
  private static $tiger_hash = null;
  private static $tiger_mhash = null;
  
   /**
   * Generates TigerTree Head in breadthfirst format
   *
   * @param string $filename
   * @param integer $maxlevel
   * @return binary tree
   */
	
	/*
		First 24 bytes is binary encoded TTH.
	*/
  
  public static function getTigerTree($filename, $maxlevel) {
    $fp = fopen($filename, "rb");
    $top = 1;
    $tree = NULL;
    if($fp) {
      $i = 1;
      $hashes = array();
      while(!feof($fp)) {
        $buf = fread($fp, 1024);
		$step = 1;
        if ($buf || ($i == 1)) {
          $hashes[$i] = self::tiger("\0".$buf);
			if (isset($tree[$step])) {
				$tree[$step] = $tree[$step].$hashes[$i];
			} else {
				$tree[$step] = $hashes[$i];
			}
          $j = 1;
          while($i % ($j * 2) == 0) {
			$step = $step + 1;
            $hashes[$i] = self::tiger("\1".$hashes[$i - $j].$hashes[$i]);
			/*Building tree*/
            if ($top < $step) $top = $step;
            if ($top - $step <= $maxlevel) {
				if (isset($hashes[$i])) {
					if (isset($tree[$step])) {
						$tree[$step] = $tree[$step].$hashes[$i];
					} else {
						$tree[$step] = $hashes[$i];
					}
				}
            }elseif (isset($tree[$step])){
                unset($tree[$step]);
            }
			/*end*/
            unset($hashes[$i - $j]);
            $j = round($j * 2);
          }
          $i++;
        }
      }
      $k = 1;
      while($i > $k) {
        $k = round($k * 2);
      }
      for(; $i <= $k && $i > 2; $i++) {
          $j = 1;
		  $step = 2;
          while($i % ($j * 2) == 0) {
            if(isset($hashes[$i])) {
              $hashes[$i] = self::tiger("\1".$hashes[$i - $j].$hashes[$i]);
            } elseif(isset($hashes[$i - $j])) {
              $hashes[$i] = $hashes[$i - $j];
            }
			/*Building tree*/
            if ($top < $step) $top = $step;
            if ($top - $step <= $maxlevel) {
				if (isset($hashes[$i])) {
					if (isset($tree[$step])) {
						$tree[$step] = $tree[$step].$hashes[$i];
					} else {
						$tree[$step] = $hashes[$i];
					}
				}
            }elseif (isset($tree[$step])){
                unset($tree[$step]);
            }
			/*end*/
            unset($hashes[$i - $j]);
			$step = $step + 1;
            $j = round($j * 2);
          }
      }
      fclose($fp);
	  
	  $fulltree = NULL;
	  
	  for($level = 0; $level < $maxlevel; $level++){
		if (!isset($tree[$top - $level])) break;
		$fulltree = $fulltree.$tree[$top - $level];
	  }
	  
	  return $fulltree;
    }
  } 
  
  /**
   * Generates DC-compatible TTH of a file.
   *
   * @param string $filename
   * @return string
   */
/*
  public static function getTTH($filename) {
    $fp = fopen($filename, "rb");
    if($fp) {
      $i = 1;
      $hashes = array();
      while(!feof($fp)) {
        $buf = fread($fp, 1024);
        if ($buf || ($i == 1)) {
          $hashes[$i] = self::tiger("\0".$buf);
          $j = 1;
          while($i % ($j * 2) == 0) {
            $hashes[$i] = self::tiger("\1".$hashes[$i - $j].$hashes[$i]);
            unset($hashes[$i - $j]);
            $j = round($j * 2);
          }
          $i++;
        }
      }
      $k = 1;
      while($i > $k) {
        $k = round($k * 2);
      }
      for(; $i <= $k; $i++) {
          $j = 1;
          while($i % ($j * 2) == 0) {
            if(isset($hashes[$i])) {
              $hashes[$i] = self::tiger("\1".$hashes[$i - $j].$hashes[$i]);
            } elseif(isset($hashes[$i - $j])) {
              $hashes[$i] = $hashes[$i - $j];
            }
            unset($hashes[$i - $j]);
            $j = round($j * 2);
          }
      }
      fclose($fp);

      return self::base32encode($hashes[$i-1]);
    }
  }
*/


  /**
   * Generates a DC-compatible tiger hash (not TTH).
   * Automatically chooses between hash() and mhash().
   *
   * @param string $string
   * @return string
   */
  private static function tiger($string) {
    if (is_null(self::$tiger_hash)) {
       self::$tiger_hash = function_exists("hash_algos") && in_array("tiger192,3", hash_algos());
    }
    if (self::$tiger_hash) {
      return self::tigerfix(hash("tiger192,3", $string, 1));
    }

    if (is_null(self::$tiger_mhash)) {
      self::$tiger_mhash = function_exists("mhash");
    }
    if(self::$tiger_mhash) {
      return self::tigerfix(mhash(MHASH_TIGER, $string));
    }

    trigger_error(E_USER_ERROR, "Neither tiger hash function is available.");
  }

  /**
   * Repairs tiger hash for compatibility with DC.
   *
   * @url http://www.php.net/manual/en/ref.mhash.php#55737
   * @param string $binary_hash
   * @return string
   */ 
  private static function tigerfix($binary_hash) {
      $my_split = str_split($binary_hash,8);
      $my_tiger ="";
      foreach($my_split as $key => $value) {
         $my_split[$key] = strrev($value);
         $my_tiger .= $my_split[$key];
      }
     return $my_tiger;
  }

  /**
   * Just a base32encode function :)
   *
   * @url http://www.php.net/manual/en/function.sha1-file.php#61741
   * @param string $input
   * @return string
   */
  public static function base32encode($input) {
    $output = '';
    $position = 0;
    $storedData = 0;
    $storedBitCount = 0;
    $index = 0;
    while ($index < strlen($input)) {
      $storedData <<= 8;
      $storedData += ord($input[$index]);
      $storedBitCount += 8;
      $index += 1;
      //take as much data as possible out of storedData
      while ($storedBitCount >= 5) {
        $storedBitCount -= 5;
        $output .= self::$BASE32_ALPHABET[$storedData >> $storedBitCount];
        $storedData &= ((1 << $storedBitCount) - 1);
      }
    } //while
    //deal with leftover data
    if ($storedBitCount > 0) {
      $storedData <<= (5-$storedBitCount);
      $output .= self::$BASE32_ALPHABET[$storedData];
    }
    return $output;
  }
}

?>