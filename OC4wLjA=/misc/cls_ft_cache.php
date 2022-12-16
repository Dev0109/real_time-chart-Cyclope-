<?php
////////////////////////////////////////////////////////////
//
// $RCSfile: cls_ft_cache.php,v $
//
////////////////////////////////////////////////////////////
//
// Created: $Name:  $  Last modified: $Date: 2003/10/22 12:00:46 $
// Revised by: $Author: tynu $ 
//
// Copyright (c) 2003  Reea Srl (http://www.reea.net <office@reea.net>) 
// 
// All rigths reserved . Please see licence file. 
//
////////////////////////////////////////////////////////////
class ft_cache extends ft 
{

var $cache_folder; //hold the path to the cached files folder
var $max_runtime;  //if the processing of the templates in the assignation/parsing process
                   //time is bigger than this value the final page will be cached.Else no.We don't
                   //wan to make the cache processing time bigger than template processing
                   //(including all your queries and extra work) else the caching of pages will have 
                   //no mean.
                   //Default to 2 secconds I thing is enough?
var $start;        //store the microtime when class is created
var $end;          //when the print method is called the job of template engine it's done.
                   //It's time to take the decission (based on[($end-$start)<$max_runtime]) if the curent page 
                   //nedds or not caching.
var $average;       //store the diff between $end and $start.In fact is the duration required but your script
                   //to run,starting from the moment of the class instantiation and the call of ft_print().
                   //The value is used to determine if the curent conext require a cache or not,reported to 
                   //$max_runtime.You can use this value to choose the best value for $max_runtime. 
                   //WARNING! This value is not set if already exist a cache for the context, so you must clear the
                   //cache files or touch() the "cache.info" file to force the cache reactualization.
var $require_cache; //if FALSE the class will not attempt to cache the page for the current context.
                   //This is used when we don't wan to cache a specific page for a unique context like
                   //login pages,thanks pages as response to some user data post,etc.
                   //Default TRUE
var $cache_name;   //the curent context file name.Is builded based on the current page url($PHP_SELF) and context ($_GET/$_POST) values
                   //In this way we get (relative) short page names and still keep unigue in the context.For example if we have a page for 
                   //a product presentation.The page will always be the same but we have a different product_id and the content is changed
                   //for each product.Now for each product presented in that page we need an addtional cached page.In this way we get A LOT of
                   //cached pages BUT keep in mind:OLNY pages that require a big processing time (over $max_runtime) are cached.
var $files_stamps; //hold the last modified timestamps of all files registered as templates by define() function.
var $cache;       //Will be set to TRUE if the curent context is served from cache.You must test this after define() was called
                   //and print the content by calling the ft_print('_CACHE_'),or get the cached content using the fetch() function from _CACHE_ tag and
                   //do whatever you wan with it.
var $init_called;   
                
/**
 * @return ft_cache object
 * @param root_dir = NULL string
 * @param cache_dir = NULL string
 * @desc Class constructor
 */
function ft_cache($root_dir=NULL,$cache_dir=NULL)
{
    $this->start=microtime();
    if($root_dir)
    {
        $this->set_root($root_dir);
    }
    if($cache_dir)
    {
        $this->set_cache_folder($cache_dir);
    }
    
    $this->templates=array();
    $this->tags=array();
    $this->blocks=array();
    $this->strip=true;
    $this->require_cache=true;
    $this->max_runtime=2;
    $this->init_called=false;
}

/**
 * @return boolean
 * @desc Cache init routine is here.Must be called before the define() method.
 */
function init()
{
    if(!$this->require_cache)
    {
        //we don't need the next lines
        return true;
    }
    if(!$this->cache_folder)
    {
        //see if we have a CACHE_FOLDER constant defined and use that.
        //else we create that relative to the current detected path
        if(defined('CACHE_FOLDER'))
        {
            $ft_folder=CACHE_FOLDER;
        }
        //still no value ?
        if(!$ft_folder)
        {
            $ft_folder=realpath('.').'/ft_cache';
        }
        
        if(FALSE === $this->set_cache_folder($ft_folder))
        {
            return false;
        }
    }
    //create the "cache.info" file if not exist.
    if(!file_exists($this->cache_folder.'cache.info'))
    {
        touch($this->cache_folder.'cache.info');
        @chmod($this->cache_folder.'cache.info',0664);
    }
    $this->_set_cache_name();
    $this->init_called=true;
    return true;
}

/**
 * @return boolean
 * @param cache_dir string
 * @desc Manualy set the your cache folder.First look after CACHE_FOLDER constant.
         If is not defined then use the $cache_dir parameter and define the constant.
         Else the $cache_dir is rewrited from there.
         Also create the cache folder if does not exist.
 */
function set_cache_folder($cache_dir)
{
    if(!$this->require_cache)
    {
        //we don't need the next lines
        return true;
    }
    if(!$cache_dir)
    {
        user_error('ft_cache::set_cache_folder()-Expected value for $cache_dir parameter.This must point to the folder where cached files will be stored.');
        return true;
    }
    //we can't rewrite the CACHE_FOLDER constant,so this function must be called before
    //to set that.If already defined then use that
    if(defined('CACHE_FOLDER'))
    {
        $cache_dir=CACHE_FOLDER;
    }
    if(!is_dir($cache_dir))
    {
        if(FALSE === mkdir($cache_dir))
        {
            user_error('ft_cache::set_cache_folder()-Unable to create ft_cache folder.');
            return false;
        }
        @chmod($cache_dir,0774);
    }
    if('/' != substr($cache_dir,strlen($cache_dir)-1,1))
    {
        $cache_dir.='/';
    }
    //define the constant so other instances of ft_cache found'it
    if(!defined('CACHE_FOLDER'))
    {
        @define('CACHE_FOLDER',$cache_dir);
    }
    $this->cache_folder=$cache_dir;
    return true;
}
/**
 * @return boolean
 * @param mixed_templates array()
 * @desc Specify and load all required template files.
         Also look if a cached file exist for the curent context
         and if so read he's content and create the _CACHE_ tag.
         The $this->cache flag is set, so you can know the page was readed
         from cache.
         You can ft_print('_CACHE_') content,or get'it with fetch('_CACHE_') and
         do whatever you wan with it. 
 */
function define($mixed_template)
{
    if(!$this->init_called)
    {
        user_error("ft_cache::define()-Expected to call init() method first.",E_USER_ERROR);
        return false;
    }
    if('array' != gettype($mixed_template))
    {
        user_error("ft_cache::define()-Expected array of templates(array('name' => 'file')).",E_USER_ERROR);
        return false;
    }

    $was_required=$this->require_cache;
    
    //ok.here we must test if the curent context have a cache.
    //If yes,the stamp from the first line of it told us if is in sync
    //with the templates from $mixed_template so we know if the cache file
    //must be rebuilded.
    //BUT we do this only if required_cache is true.
    if(!file_exists($this->cache_folder.$this->cache_name.'.html') && $this->require_cache)
    {
        //file does not exist, so we consider that a cache file
        //was not required the last time the class has been run in this context
        $this->require_cache=false;
    }
    if(!$this->require_cache)
    {
        //normal way
        $this->_define($mixed_template);
        //if we set the require_cache to false in the previuos "if",
        //then set it back in order to be able to create a cache file(if required)
        //when the ft_print() is called.
        $this->require_cache=$was_required;
        return true;
    }
    //get the file
    $this->files_stamps=array();
    $cache_file=$this->cache_folder.$this->cache_name.'.html';
    $c_content=$this->_get_file($cache_file);
    if(!$c_content)
    {
        //an error message is already on the screen.
        return false;
    }
    //here is a way to force all cache files to be rebuilded.
    //this is very useful when you wan to force the cache recreation
    //when some changes are made in database and you wan to show that change on the frontend.
    //All you have to do is touch() the file "cache.info" from the CACHE_FOLDER.
    //This will mark the end lifetime of
    //all cached files, so are rebuilded.
    if(FALSE === ($stamp=@filemtime($this->cache_folder.'cache.info')))
    {
        user_error("ft_cache::define()-Failed to get timestamp for template file $tfile.Check please if the file exist.",E_USER_ERROR);
        return false;
    }
    if((int)$stamp > (int)$this->files_stamps[0])
    {
        //all caches must rebuilded so 
        //go in normal way.
        @unlink($cache_file);
        $this->_define($mixed_template);
        $this->require_cache=$was_required;
        return true;
    }
    //check the stamps from cache
    if(1 !== @preg_match("/^<!-- ft_cache stamp ".$this->cache_name."\[([0-9\|]+)\] -->/s",$c_content,$stamp_info))
    {
        //no stamp.Our cache is wrong/altered.
        //go in normal way, and will be reacreated if required
        @unlink($cache_file);
        $this->_define($mixed_template);
        $this->require_cache=$was_required;
        return true;
    }
    //now from template files
    $stamp_info=explode('|',$stamp_info[1]);
    $i=0;
    foreach ($mixed_template as $t_name => $t_file)
    {
        if(FALSE === ($stamp=@filemtime($this->root.$t_file)))
        {
            user_error("ft_cache::define()-Failed to get timestamp for template file $tfile.Check please if the file exist.",E_USER_ERROR);
            return false;
        }
        if($stamp != $stamp_info[$i])
        {
            //the cache must be rebuild
            //go in normal way...
            @unlink($cache_file);
            $this->_define($mixed_template);
            $this->require_cache=$was_required;
            return true;
        }
        $i++;
    }
    $this->average=$this->_micro_diff(microtime(),$this->start);
    $this->cache=true;
    $this->tags['{_CACHE_}']=$c_content;
    return true;
}
/**
 * @return string
 * @param tag_name string
 * @desc return the content of the specified $tag_name(parse or not)
         You must keep track of what is parsed and what is not....
 */
function fetch($tag_name,$force_caching=FALSE)
{
    if($force_caching)
    {
        $this->ft_print($tag_name,true);
    }
    return $this->tags["\{$tag_name}"];
}

/**
 * @return boolean
 * @param tag_name string
 * @desc Print out the given tag and create the cache file if required.
         This is done by calculating the time required by your script to run
         between class initialization and call to this method.
         If the given $tag_name it's called _CACHE_ then just print he's output.
 */
function ft_print($tag_name,$skip_output=FALSE)
{
    if(!$this->require_cache || $tag_name == '_CACHE_')
    {
        echo $this->fetch($tag_name);
        return true;
    }
    $this->end=microtime();
    //now check if a cache for this context make sense
    $this->average=$this->_micro_diff($this->end,$this->start);
    if(($this->_micro_diff($this->end,$this->start))<=$this->max_runtime)
    {
        //no.delete the (suposed) file,echo and exit.
        @unlink($this->cache_folder.$this->cache_name);
        if(!$skip_output)
        {
            echo $this->fetch($tag_name);
        }
        return true;
    }
    //it's bigger so a cache file my be useful.
    //first get the timestamps of template files used
    //in this context.This will be used later to see if
    //the html content of templates has been changed,and
    //rebuild the cache file to reflect the changes.
    //The timestamps will be in the first line of each html cached file.
    $stamp_line='<!-- ft_cache stamp '.$this->cache_name.'['.join($this->files_stamps,'|')."] -->\n";
    if(!$this->_write_file($this->cache_folder.$this->cache_name.'.html',$stamp_line.$this->fetch($tag_name)))
    {
        return false;
    }
    chmod($this->cache_folder.$this->cache_name.'.html',0664);
    if(!$skip_output)
    {
        echo $this->_get_file($this->cache_folder.$this->cache_name.'.html');
    }
    $this->cached=true;
    return true;
}

/**
 * @return boolean
 * @param mixed_template array()
 * @desc Orignal method from "ft" which load the templates content
 */
function _define($mixed_template)
{
    $this->files_stamps=array();
    foreach($mixed_template as $t_id => $t_file)
    {
        $this->templates[$t_id]=$this->_get_file($this->root.$t_file);
    }
    return true;
}

/**
 * @return boolean
 * @desc Create the context name(cache file name) from 
         the Get/Post and current url as and md5 hash.
 */
function _set_cache_name()
{
    global $_GET,$_POST,$PHP_SELF;
    if(!$this->require_cache)
    {

        //we don't need the next lines
        return true;
    }
    $context=$PHP_SELF.join($_GET,'&').join($_POST,'&');
    $this->cache_name=md5($context);
    return true;
}

/**
 * @return float
 * @param max_value string
 * @param min_value sting
 * @desc From php manual.Thanks to he's author.Take to 
         microtime() strings and calculate the diff between 
         them.
 */
function _micro_diff($max_value,$min_value)
{
    list($a_micro, $a_int)=explode(' ',$max_value);
    list($b_micro, $b_int)=explode(' ',$min_value);
    if ($a_int>$b_int) 
    {
        return ($a_int-$b_int)+($a_micro-$b_micro);
    }
    elseif ($a_int==$b_int)
    {
        if ($a_micro>$b_micro)
        {
           return ($a_int-$b_int)+($a_micro-$b_micro);
        }
        elseif ($a_micro<$b_micro)
        {
           return ($b_int-$a_int)+($b_micro-$a_micro);
        }
        else
        {
           return 0;
        }
    }
    else
    { // $a_int<$b_int
        return ($b_int-$a_int)+($b_micro-$a_micro);
    }
}

/**
 * @return string
 * @param file_path string Full path to a text file.
 * @desc Open a given file and return he's content as string.
 * @scope Private
 */
function _get_file($file_path)
{
    if(FALSE === ($f_hwnd=@fopen($file_path,'r')))
    {
        user_error("ft_cache::_get_file()-Failed to open template file: $file_path",E_USER_ERROR);
        return false;
    }
    if(-1 === ($f_content=@fread($f_hwnd,@filesize($file_path))))
    {
        user_error("ft_cache::_get_file()-Failed to read template file: $file_path",E_USER_ERROR);
        return false;
    }
    fclose($f_hwnd);
    
    if(FALSE === ($stamp=@filemtime($file_path)))
    {
        user_error("ft_cache::_get_file()-Failed to get timestamp of template file: $file_path",E_USER_ERROR);
        return false;
    }
    $this->files_stamps[].=$stamp;        
    return $f_content;
}

/**
 * @return boolean
 * @param file_path string
 * @param data string
 * @desc Write data to a file.If file exist is truncated,if no 
         is created.   
 */
function _write_file($file_path,$data)
{
    if(FALSE === ($f_hwnd=@fopen($file_path,'wb')))
    {
        user_error("ft_cache::_write_file()-Failed to open(for write) cache file: $file_path",E_USER_ERROR);
        return false;
    }
    if(-1 === @fwrite($f_hwnd,$data))
    {
        user_error("ft_cache::_write_file()-Failed to write data in cache file: $file_path",E_USER_ERROR);
        return false;
    }
    fclose($f_hwnd);
    return true;
}
}//end class ft_cache
?>