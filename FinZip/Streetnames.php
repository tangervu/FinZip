<?php
namespace FinZip;

require_once dirname(__FILE__) . '/Resource.php';

class Streetnames extends Resource {
	
	protected function _readLine() {
		
		$row = utf8_encode(fread($this->handle,257));
		
		mb_internal_encoding('UTF-8');
		
		if(trim($row) == '') {
			$this->line = null;
		}
		
		else {
			//NOTE this appeared slightly different than defined in documentation
			$item = array(
				'struct' => mb_substr($row,0,5),
				'updated' => mb_substr($row,5,8),
				'zip' => mb_substr($row,13,5),
				'locality' => mb_substr($row,18,30),
				'locality_swe' => mb_substr($row,48,30),
				'locality_short' => mb_substr($row,78,12),
				'locality_short_swe' => mb_substr($row,90,12),
				'street' => mb_substr($row,102,30),
				'street_swe' => mb_substr($row,132,30),
				'streetnumber_type' => mb_substr($row,186,1), // 1 = odd, 2 = even
				'streetnumber_min' => mb_substr($row,187,5),
				'streetnumber_min_char' => mb_substr($row,192,1),
				'streetnumber_max' => mb_substr($row,200,5),
				'streetnumber_max_char' => mb_substr($row,205,1),
				'municipality_code' => mb_substr($row,213,3),
				'municipality_name' => mb_substr($row,216,20),
				'municipality_name_swe' => mb_substr($row,236,20)
			);
			
			foreach($item as $key => $val) {
				$val = trim($val);
				if($val == '') {
					$val = null;
				}
				$item[$key] = $val;
			}
			
			if($item['updated']) {
				$str = $item['updated'];
				$item['updated'] = mb_substr($str,0,4) . '-' . mb_substr($str,4,2) . '-' . mb_substr($str,6,2);
			}
			
			$this->line = $item;
		}
	}
}
