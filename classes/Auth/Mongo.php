<?php defined('SYSPATH') or die();

class Auth_Mongo extends Auth
{
	protected $_mongo;
	
    protected function _login($username, $password, $remember)
    {
    	// Get the Mongo connection
		$coll = Collection::factory('User', $this->_config['mongo_config']);
		
		// Do username/password check here	
		if (is_string($password))
		{
			// Create a hashed password
			$password = $this->hash(trim($password));
		}
		
		// Create JS search function
		$js = "function() {
		    return this.username == '{$username}' || this.email == '{$username}';
		}";
		
		//echo $js; die();
		
		// Find matches for this username
		$user = $coll->find_one(array('$where' => $js));
				
		if( $user and is_a($user, 'Model_User') and $user->password === $password )
		{
			// Finish the login
			$this->complete_login($user);

			return TRUE;		
		}
		
		// Login Failed
		return FALSE;
		
    }
 
    public function password($username)
    {
        // Return the password for the username
        $coll = Collection::factory('user', $this->_config['mongo_config']);
        
		// Create JS search function
		$js = "function() {
		    return this.username == '{$username}' || this.email == '{$username}';
		}";
		
		// Find matches for this username
		$user = $coll->find_one(array('$where' => $js));
        
        // Return the password
        return $user->password;
        
    }
 
    public function check_password($password)
    {
		$user = $this->get_user();

		if ( ! $user)
			return FALSE;

		return ($this->hash($password) === $user->password);
    }

	// NOTE.. DRIVE DOES NOT SUPPORT ROLES AT THIS POINT 
    public function logged_in($role = NULL)
    {
        // Check to see if the user is logged in, and if $role is set, has all roles
        $user = $this->get_user();
        
        if( ! $user )
        	return FALSE;
        	
        //if( $user instanceof Model_User AND $user->loaded() )
        //{
        	//if( ! $roles )
        		return TRUE;
        //}
        
        return TRUE;
    }
 
    public function get_user($default = NULL)
    {
        // Get the logged in user, or return the $default if a user is not found
        return parent::get_user($default);
    }
    
    // Override authentication class to not save session
    protected function complete_login($user)
    {
        // Regenerate session_id
        $this->_session->regenerate();
     
        // Store username in session
        $this->_session->set($this->_config['session_key'], $user->id());
     
        return TRUE;
    }
}