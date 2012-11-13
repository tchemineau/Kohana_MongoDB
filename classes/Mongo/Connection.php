<?php defined('SYSPATH') or die();

class Mongo_Connection
{
	protected static $_instance;
	protected $_config;
	protected $_conn;
	protected $_db;
	public $_config_name;
		
	public function __construct($config='default')
	{
		$this->_config = Kohana::$config->load('mongodb.'.$config);
		$this->_config_name = $config;
		
		$str = '';
		
		// Build a connection string and connect to the db.
		if($this->_config['username'] and $this->_config['password'])
		{
			$str .= $this->_config['username'].':'.$this->_config['password'].'@';
		}
				
		$str .= $this->_config['host'];
		
		if($this->_config['port'])
		{
			$str .= ':'.$this->_config['port'];
		}
		
		// Set the DB Name
		$this->_db = $this->_config['database'];
		
		$this->_conn = new Mongo('mongodb://'.$str);
	}

	public static function instance($config='default')
	{
		if( ! self::$_instance )
		{
			self::$_instance = new Mongo_Connection($config);
		}
		
		// If config name has changed, update the instance
		if( self::$_instance->_config_name != $config )
		{
			self::$_instance = new Mongo_Connection($config);
		}
		
		return self::$_instance;
	}
	
	public function db()
	{
		return $this->_conn->{$this->_db};
	}
}