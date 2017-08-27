<?php

namespace FinZip;

class Localities extends Resource {
	
	protected function _getData() {
		
		$row = $this->_readLine();
		if($row) {
			
			$item = array(
				'struct' => mb_substr($row,0,5),
				'updated' => mb_substr($row,5,8),
				'zip' => mb_substr($row,13,5),
				'locality' => mb_substr($row,18,30),
				'locality_swe' => mb_substr($row,48,30),
				'locality_short' => mb_substr($row,78,12),
				'locality_short_swe' => mb_substr($row,90,12),
				'created' => mb_substr($row,102,8),
				'type' => mb_substr($row,110,1), //1,2 = normal, 3 = postilokero, 4 = corporate zip, 7=koontipostinumero, 8=erikoispostinumero
				'govt_area_code' => mb_substr($row,111,5),
				'govt_area_name' => mb_substr($row,116,30),
				'govt_area_name_swe' => mb_substr($row,146,30),
				'municipality_code' => mb_substr($row,176,3),
				'municipality_name' => mb_substr($row,179,20),
				'municipality_name_swe' => mb_substr($row,199,20),
				'municipality_language' => mb_substr($row,219,1) //1 = Finnish, 2, 3 = Bilangual, 4 = Swedish
			);
			
			$item = $this->_trimArray($item);
			
			foreach(array('updated','created') as $i) {
				if($item[$i]) {
					$item[$i] = $this->_convertDate($item[$i]);
				}
			}
			
			return $item;
		}
		else {
			return false;
		}
	}
}
