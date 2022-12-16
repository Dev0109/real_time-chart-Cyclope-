<?php

function &database($params = array()){
	static $instances = array();
	
	$driver = 'mysql';
	if(!count($params)){
		//if there are no params sent we try to get the default constants and use those.
		$constants2properites = array('DB_HOSTNAME' => 'hostname',
									  'DB_USERNAME' => 'username',
									  'DB_PASSWORD' => 'password',
									  'DB_DATABASE' => 'database',
									  'DB_DRIVER'   => 'driver',
									  'DB_PREFIX' => 'tbl_prefix',
									  'DB_EXIT_ON_ERROR' => 'exit_on_error',
									  'DB_ALWAYS_CREATE_NEW' => 'create_new');
		foreach ($constants2properites as $constant => $property){
			if(defined($constant)){
				$params[$property] = constant($constant);
			}
		}
		$driver = $params['driver'] ? $params['driver'] : $driver;
	}
	
	$instance_key = $params['hostname'].'.'.$params['username'].'.'.$params['database'].'.'.$driver;
	
	if(isset($instances[$instance_key]) && (!defined('DB_ALWAYS_CREATE_NEW') || !isset($param['create_new']))){
		return $instances[$instance_key];
	}
	//no existing instance exists so we require the files and run with it
	if(!class_exists("database_driver")){
		require(INSTALLPATH.'database/database_driver.php');
	}
	if(!class_exists($driver.'_driver')){
		require(INSTALLPATH.'database/driver/'.$driver.'/'.$driver.'_driver.php');
	}
	$class = $driver.'_driver';
	$instances[$instance_key] = new $class($params);
	return $instances[$instance_key];
}