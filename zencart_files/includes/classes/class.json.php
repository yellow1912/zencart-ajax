<?php
/**
 * Json Class.
 *
 * @package classes
 * @copyright Copyright 2003-2006 RubikIntegration Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: class.json.php $
 */
class Json{
	var $json = array();
	
	function reset(){
		$this->json = array();
	}
	
	function set($data){
		$this->json = array_merge_recursive($this->json, $data);
	}
	
	function get($key = null){
		if(!empty($key))
			return $this->_get($this->json, $key);
		else 
			return $this->json;
	}
	
	function _get($data, $key){
		if(is_array($key)){
			$sub_key = array_shift($key);
			
			if(isset($data[$sub_key])){
				if(count($key) > 0)
					return $this->_get($data[$sub_key], $key);
				else 
					return  $data[$sub_key];
			}
			else 
				return $data;
		}
		else {
			if(isset($data[$key]))
				return  $data[$key];
			else 
				return null;
		}
	}
	
	function exist($key){
		return isset($this->json[$key]);
	}
	
	function add($key, $content){
		$this->json[$key] .= $content;
	}
	
	// This function is to replace Zen's messageStack. Basically we return the messages to the client, the JS will deal with them.
	function addMessage($message, $type='error'){
		$this->add('message', "<div class='$type'>$message</div>");
	}
	
	function getJson(){
		return json_encode($this->json);	
	}
	
	function setJsonAndExit($data){
		$this->set($data);
		echo $this->getJson();
		$this->_exit();
	}

	function getJsonAndExit(){
		echo $this->getJson();
		$this->_exit();
	}
	
	function _exit(){
		session_write_close();
  	exit();
	}
}

// add support for php version < 5.2
if (!function_exists('json_encode'))
	include(DIR_WS_CLASSES.'class.services_json.php');