<?php
/***********************************************************************
* @Author: MedeeaWeb Works											   *
***********************************************************************/
class lang
{
	var $dbu;
	
	function lang()
	{
	}

	/****************************************************************
	* function add(&$ld)                                            *
	****************************************************************/
	function add(&$ld)
	{
		if(!isset($ld['word']) || empty($ld['word'])){
			return false;
		}
		//try and sanitize it
		$ld['word'] = filter_var($ld['word'],FILTER_SANITIZE_STRING);
		$lang = new LanguageParser();
		$ld['word'] = $lang->lookup($ld['word']);		
		return true;
	}

}//end class