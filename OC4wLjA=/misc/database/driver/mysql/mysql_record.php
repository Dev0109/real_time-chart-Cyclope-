<?php
class mysql_record extends database_record {
	
	protected function _fetch_array(){
		return @mysqli_fetch_array($this->result_id);
	}
	
	protected function _seek($row_number){;
		return @mysqli_data_seek($this->result_id,$row_number);
	}
	
	protected function _num_rows(){
		return @mysqli_num_rows($this->result_id);
	}
	
	protected function _num_fields(){
		return @mysqli_num_fields($this->result_id);
	}
	
}
