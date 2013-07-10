<?php defined('SYSPATH') or die();

class Model_Mongo extends Kohana_Model
{
	protected $_model;	
	protected $_collection;
	protected $_loaded = false;
	protected $_id;
	protected $_error;

	public static function factory($model, $id=NULL, $config='default')
	{
		$model_name = 'Model_' . ucfirst($model);
		
		return new $model_name($model, $id, $config);
	}
	
	public function __construct($model, $id=NULL, $config='default')
	{
		$model = strtolower($model);
		
		$this->_model = $model;
	
		$db = Mongo_Connection::instance($config)->db();
		
		$this->_collection = $db->$model;
				
		if( $id != NULL )
		{
			$this->load($id);
		} 
	}
	
	public function id()
	{
		return $this->_id->{'$id'};
	}
	
	public function loaded()
	{
		return $this->_loaded;
	}
	
	public function load($id)
	{
		$response = $this->_collection->findOne(array('_id' => new MongoId($id)));

		if( $response )
		{
			$this->values($response);
			$this->_loaded = true;
		}
				
		return $this;
	}
	
	
	public function values($values)
	{
		if( is_string($values) )
		{
			$this->value = $values;
			return $this;
		}
		
		foreach($values as $key => $value)
		{
			if( substr($key, 0, 1) === '_' and $key != '_id' )
			{
				continue;
			}
			elseif ($key === '_id') 
			{
				$this->$key = $value;
			}
			else 
			{
				$this->$key = json_decode(json_encode($value));
			}
		}
		
		return $this;
	}
	
	public function save($validate = true)
	{
		$obj = $this->get_public_vars();
		
		// Remove reserved fields
		if( $this->_reserved )
		{
			$obj = $this->remove_reserved($obj);
		}

		// Validate the model if validate method has beem defined
		if( $validate === true and method_exists($this,'validate') )
		{
			if( ! $this->validate($obj) )
			{
				return false;
			}
		}
				
		// Add ID if already loaded
		// if( $this->loaded() OR is_a($this->_id, 'MongoId') )
		if( $this->loaded() OR isset($this->_id) )
		{
			$obj['_id'] = $this->_id;
		}
		
		try {
			$this->_collection->save($obj, array('w'=>true));
		}
		catch (MongoException $e) {
			$this->_error = $e->getMessage();
			return false;
		}
					
		$this->_id = $obj['_id'];
		$this->_loaded = true;
		return $this;
	}
	
	public function remove()
	{
	
		try {
		
			$result = $this->_collection->remove(array('_id' => $this->_id), array('justOne' => true));
			
			// Delete the Mongo ID and all public fields
			unset($this->_id);
			
			foreach($this->get_public_vars() as $key => $value )
			{
				unset($this->$key);
			}
			
			$this->_loaded = false;

			return true;
		
		} catch (MongoException $e) {
			$this->_error = $e->getMessage();
			return false;
		}	
		

	}
	
	public function get_public_vars() {
	    $ref = new ReflectionObject($this);
	    $pros = $ref->getProperties(ReflectionProperty::IS_PUBLIC);
	    $result = array();
	    foreach ($pros as $pro) {
	        false && $pro = new ReflectionProperty();
	        $result[$pro->getName()] = $pro->getValue($this);
	    }
	    
	    return $result;
	}
	
	public function remove_reserved($obj)
	{
		foreach($this->_reserved as $key)
		{
			if( isset($obj[$key]) )
			  unset($obj[$key]);
		}
		
		return $obj;
	}
	
	public function last_error()
	{
		return $this->_error;
	}
	
	public function collection()
	{
		return $this->_collection;
	}
	
}
