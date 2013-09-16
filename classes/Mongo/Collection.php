<?php defined('SYSPATH') or die();

class Mongo_Collection
{
	protected $_db;
	protected $_collection;
	protected $_model;

	public static function factory($collection, $config='default')
	{
		return new Mongo_Collection($collection, $config);
	}
	
	public function __construct($collection, $config='default')
	{
		$this->_db = Mongo_Connection::instance($config)->db();
		$this->_model = strtolower($collection);
		$this->_collection = new MongoCollection($this->_db, strtolower($collection));
	}
	
	public function find($query=array(),$fields=array(),$limit=NULL,$skip=NULL)
	{
		$cursor = $this->_collection->find($query,$fields);

		if (!is_null($limit))
			$cursor = $cursor->limit($limit);

		if (!is_null($skip))
			$cursor = $cursor->skip($skip);

		$result = array();
		
		foreach($cursor as $doc)
		{
			$result[] = Model::factory($this->_model)->values($doc);
		}
		
		return $result;
	}
	
	public function find_one($query, $fields=array())
	{
		$doc = $this->_collection->findOne($query, $fields);
		
		//echo Debug::Vars($doc); die();
		
		if( ! $doc )
			return FALSE;
		
		return Model::factory($this->_model)->values($doc);
	}
	
	public function self()
	{
		return $this->_collection;
	}
}
