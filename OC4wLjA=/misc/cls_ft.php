<?php
class TemplateParser
{
	private $template_directory = '';
	private $templates = array();
	private $tags = array('{CURRENT_VERSION_FOLDER}' => CURRENT_VERSION_FOLDER);
	private $dynamics = array();

	/**
	 * Constructor. You can set a root folder for your templates 
	 *
	 * @param string $template_dir
	 */
	public function __construct($template_dir = ''){
		if(!empty($template_dir)){
			$this->set_directory($template_dir);
		}
	}
	
	/**
	 * Set template folder for your templates
	 *
	 * @param string $template_dir
	 */
	public function set_directory($template_dir = ''){
		$this->template_directory = $template_dir;
	}
	
	/**
	 * Get the template directory
	 *
	 * @return string
	 */
	public function get_directory(){
		return $this->template_directory;
	}
	
	/**
	 * Define Template files
	 *
	 * @param mixed $template
	 * @param string $filename optional
	 * @return boolean
	 */
	public function define($template, $filename = ''){
		if(is_array($template) && !empty($template)){
			foreach ($template as $key => $filename){
				$this->templates[$key] = $this->read_file($filename);
			}
			return true;
		}
		$this->templates[$template] = $this->read_file($filename);
		return true;
	}
	
	/**
	 * Define blocks inside a template file or another block
	 *
	 * @param string $section
	 * @param string $parent
	 * @return boolean
	 */
	function define_dynamic($section, $parent){
		if(empty($section) || empty($parent)){
			user_error('ft::define_dynamic  - section or parent not defined');
			return false;
		}
		if(!isset($this->templates[$parent])){
			user_error('ft::define_dynamic  - parent not defined use ft::define() first');
			return false;
		}
		$pattern_start = preg_quote('<!-- BEGIN DYNAMIC BLOCK: '.$section.' -->','!');
		$pattern_end = preg_quote('<!-- END DYNAMIC BLOCK: '.$section.' -->','!');
		$sections = preg_match_all('!'.$pattern_start.'(.*)'.$pattern_end.'!is',$this->templates[$parent],$matches);
		if($sections == 0 || $sections === false){
			user_error(__METHOD__.' - Expected section '.$section.' not found. Please define in template file first.');
			return false;
		}elseif ($sections > 1){
			 user_error(__METHOD__.' - Expected section '.$section.' defined more then one time. A section can only be defined once in a template file');
        	return false;
		}
		$this->templates[$section] = $matches[1][0];
		$this->dynamics[$section] = $parent;
	    return true;
	}
	
	/**
	 * Assign tags and values
	 *
	 * @param mixed $tag
	 * @param string $value optional
	 * @return boolean
	 */
	public function assign($tag, $value = ''){
		if(is_array($value)){
			return $this;//skip array values
		}
		
		if(is_array($tag) && !empty($tag)){
			foreach ($tag as $key => $value){
				if(is_array($value)){
					continue;
				}				
				$this->tags['{'.$key.'}'] = $value;
			}
			return true;
		}
		$this->tags['{'.$tag.'}'] = $value;
		return true;	
	}
	
	/**
	 * Parse tags
	 *
	 * @param string $section
	 * @param string $template
	 * @return boolean
	 */
	public function parse($section, $template){
		$append = false;
		if(substr($template,0,1) == '.'){
			$append = true;
			$template = substr($template,1);
		}
		if(!isset($this->templates[$template])){
			user_error(__METHOD__.' - Expected template not defined.');
			return false;
		}
		if(isset($this->dynamics[$template])){
			$pattern_start = preg_quote('<!-- BEGIN DYNAMIC BLOCK: '.$template.' -->','!');
			$pattern_end = preg_quote('<!-- END DYNAMIC BLOCK: '.$template.' -->','!');
			$parent = $this->dynamics[$template];
			$this->templates[$parent] = preg_replace('!'.$pattern_start.'(.*)'.$pattern_end.'!is','{'.$section.'}',$this->templates[$parent]);
		}
		$keys = array_keys($this->tags);
		$values = array_values($this->tags);
		$compiled = str_replace($keys,$values,$this->templates[$template]);
		
		//remove unparsed template tags and sections
		$compiled = preg_replace('/<!-- BEGIN DYNAMIC BLOCK: (.*?) -->(.*?)<!-- END DYNAMIC BLOCK: \1 -->/is','',$compiled);
		$compiled = preg_replace('!{[A-Z0-9_]+}!','',$compiled);
		
		if($append){
			$this->tags['{'.$section.'}'] .= $compiled;
		}else{
			$this->tags['{'.$section.'}'] = $compiled;
		}
		return true;
	}
	
	/**
	 * Get a tags' parsed value
	 *
	 * @param string $tag
	 * @return string
	 */
	public function fetch($tag){
		return $this->tags['{'.$tag.'}'];
	}
	
	/**
	 * Print Tags value
	 *
	 * @param string $tag_name
	 */
	public function ft_print($tag_name){
		echo $this->fetch($tag_name);
	}
	
	/**
	 * Clear tag_name
	 *
	 * @param string $tag_name
	 * @return boolean
	 */
	function clear($tag_name){
		unset($this->tags['{'.$tag_name.'}']);
		return true;
	}
	
	/**
	 * Read template files
	 *
	 * @param string $filename
	 * @return boolean
	 */
	private function read_file($filename){
		if(empty($filename)){
			user_error('ft::read_file filename is empty');
			return false;
		}
		if(!is_file($this->template_directory.$filename)){
			user_error('ft::read_file filename does not exist '.$this->template_directory.$filename);
			return false;
		}
		$content = @file_get_contents($this->template_directory.$filename);
		if($content === false){
			user_error(__METHOD__.' filename can not be read');
			return false;
		}
		return $content;
	}
}
//multilanguage
class LanguageParser extends TemplateParser {
	
	static public $langs = array();
	
	public function __construct($template_dir = ''){
		//load language files
		if(!is_array(self::$langs) || empty(self::$langs)){
			include(CURRENT_VERSION_FOLDER.'lang/lang_'.strtolower(LANG).'.php');
			if(!isset($lang) || !is_array($lang)){
				user_error(__METHOD__.' - Language file not defined',E_USER_NOTICE);
			}
			self::$langs = $lang;		
			unset($lang);
		}
		parent::__construct($template_dir);
	}
	
	
	public function fetch($tag){
		return $this->translate($tag);
	}
	
	private function hide($tag){
		$content =  parent::fetch($tag);
		if(empty($content)){
			return $content;
		}
		//get all the hidable keys
		$matches = array();
		$words = preg_match_all('!\[\!H\!\](.*?)\[\!\/H\!\]!is',$content,$matches);
		if($words != 0){
			$tags = reset($matches);
			$content = str_replace($tags,array_fill(0, $words,''),$content);
		}
		return $content;
	}
	
	private function translate($tag){
		$content = $this->hide($tag);
		if(empty($content)){
			return $content;
		}
		//get all the translatable keys
		$matches = array();
		$words = preg_match_all('!\[\!L\!\](.*?)\[\!\/L\!\]!is',$content,$matches);
		if($words != 0){
			$tags = reset($matches);
			$values = array_map(array($this,'value'),end($matches));
			$content = str_replace($tags,$values,$content);
		}
		return $content;
	}
	
	
	public function lookup($word){
		return $this->value($word);
	}
	
	private function value($key){
		$base64_key = base64_encode($key);
		if(!isset(self::$langs[$base64_key])){
			//write the file
			$data = '/* '.$key.' */';
			$data .= "\n"."\$lang['".$base64_key."'] = '".addslashes($key)."';\n";
			if(DEBUG_CONTEXT){
				file_put_contents(CURRENT_VERSION_FOLDER.'lang/lang_'.strtolower(LANG).'.php',$data,FILE_APPEND);
			}
			self::$langs[$base64_key] = $key;
			return $key;	
		}
		return stripslashes(self::$langs[$base64_key]);
	}
}

class ft extends LanguageParser {}