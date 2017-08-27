<?php

namespace FinZip;

class LocalityUpdates extends Resource {
	
	protected function _getData() {
		
		$row = $this->_readLine();
		if($row) {
			
			$item = array(
				'struct' => mb_substr($row,0,4),
				'level' => mb_substr($row,4,1), //1=zip code
				'updated' => mb_substr($row,5,8),
				'period_start' => mb_substr($row,13,8),
				'period_end' => mb_substr($row,21,8),
				'zip_previous' => mb_substr($row,29,5),
				'locality_previous' => mb_substr($row,34,30),
				'locality_swe_previous' => mb_substr($row,64,30),
				'locality_short_previous' => mb_substr($row,94,12),
				'locality_short_swe_previous' => mb_substr($row,106,12),
				'zip_new' => mb_substr($row,249,5),
				'locality_new' => mb_substr($row,254,30),
				'locality_swe_new' => mb_substr($row,284,30),
				'locality_short_new' => mb_substr($row,314,12),
				'locality_short_swe_new' => mb_substr($row,326,12),
				'municipality_code' => mb_substr($row,338,3),
				'municipality_name' => mb_substr($row,341,20),
				'municipality_name_swe' => mb_substr($row,361,20),
				'govt_area_code' => mb_substr($row,381,2),
				'govt_area_name' => mb_substr($row,383,30),
				'govt_area_name_swe' => mb_substr($row,413,30),
				'change_date' => mb_substr($row,443,8),
				'type' => mb_substr($row,451,2) //1 = name changed, 2 = zip dissolve, 3 = new zip, 4 = zip joined, 5 = zip re-establish, 6 = new zip
			);
			
			$item = $this->_trimArray($item);
			
			foreach(array('updated','period_start','period_end','change_date') as $i) {
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
