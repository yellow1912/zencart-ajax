<?php
/**
 * Ajax Class.
 *
 * @package classes
 * @copyright Copyright 2003-2006 RubikIntegration Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: class.ajax.php $
 */
class Ajax{
  var $status = false;
  var $content = array();
  var $return_blocks = array();
  var $allowed_blocks = array();
  var $messages = array();
  var $block_queue = array();
  var $structure = array();
  var $new_blocks = array();
  var $map = array();
  var $flip_map = array();
  var $hooks = array();
  var $link_map = array();
  var $options = array();

  public function setOption($name, $value){
    $this->options[$name] = $value;
  }
  
  public function getOption($name){
    return isset($this->options[$name]) ? $this->options[$name] : null;
  }
  
  function start(){	
  	// set status
  	if(((isset($_SERVER['HTTP_X_REQUESTED_WITH'])) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') || (isset($_REQUEST['isajaxrequest']) && $_REQUEST['isajaxrequest'] == 1)){
  		$this->status = true;
  		
  		// start buffering
  		ob_start();
  	}
  	$this->restoreMessage();
  }
	
  function loadCssJs(){
  	global $Json, $RI_CJLoader;
  	if($this->status && (!isset($_GET['ajax_skip_cj']) || $_GET['ajax_skip_cj'] != 1)){
  		// load css and js
  		global $zv_onload;
  		$css = $jscript_ = array();
  		$css_inline = $jscript_inline = '';
  		
  		$RI_CJLoader->set(array('ajax' => true, 'load_global' => false, 'load_print' => false));
  		
  		$RI_CJLoader->loadCssJsFiles();
  		
  		$files = $RI_CJLoader->processCssJsFiles();
  	  foreach($files['css'] as $file)
  	  	if($file['include']) {
  	  		ob_start();	
  	  		include($file['src']);
  	  		$css_inline .= ob_get_clean();
  	  
  	  	}
  	  	else $css[] = $file['src'];
  	  	
  	  foreach($files['jscript'] as $file)
  	  	if($file['include']) {
  	  		ob_start();	
  	  		include($file['src']);
  	  		$jscript_inline .= ob_get_clean();
  	  	}
  	  	else $jscript[] = $file['src'];
  	  
  		$Json->set(array('load'=> array('css' => $css, 'css_inline' => utf8_encode($css_inline), 'jscript' => $jscript, 'jscript_inline' => utf8_encode($jscript_inline), 'jscript_onload' => empty($zv_onload) ? '' : $zv_onload)));		
  	}
  }
	
  function end(){
  	global $Json;
  	if($this->status){
  		
  		$this->excuteHook('end');
  		
  		ob_end_clean();
  			
  		$this->proccessMessageStack();
  		
  		$status = $Json->get('status');
  		if(empty($status))
  			$status = isset($this->messages['error']) ? 'error' : 'success';
  		
  		// insert the block structure into database if we have to
  		if(count($this->new_blocks) > 0){
  			global $db;
  			$this->new_blocks['structure']['top'] = array('parent' => 'top', 'id' => 0);
  			// insert new
  			if(is_array($this->new_blocks['new']))
  			foreach($this->new_blocks['new'] as $block){
  				$parent_id = isset($this->flip_map[$this->new_blocks['structure'][$block]['parent']]) ? $this->flip_map[$this->new_blocks['structure'][$block]['parent']] : $this->new_blocks['structure'][$this->new_blocks['structure'][$block]['parent']]['id'];
  				$db->Execute("INSERT IGNORE INTO ".TABLE_AJAX_BLOCKS."(block, parent_id) VALUES ('$block', ".$parent_id.")");
  				$this->new_blocks['structure'][$block]['id'] = mysql_insert_id();
  			}
  			
  			// update
  			if(is_array($this->new_blocks['update']))
  			foreach($this->new_blocks['update'] as $block=>$block_structure){
  				$id = $block_structure['id'];
  				$parent_id = isset($this->flip_map[$this->new_blocks['structure'][$block]['parent']]) ? $this->flip_map[$this->new_blocks['structure'][$block]['parent']] : $this->new_blocks['structure'][$this->new_blocks['structure'][$block]['parent']]['id'];
  				$db->Execute("UPDATE ".TABLE_AJAX_BLOCKS." SET parent_id = $parent_id WHERE id = $id");
  			}
  		}
  		
  		// return only the blocks we need
  		if($this->return_blocks == '*')
  			$content = $this->content;
  		else
  		foreach($this->return_blocks as $return_block)
  			if(isset($this->content[$return_block])){
  				// encode if needed to
  				if(AJAX_CONVERT_TO_UTF8 && strtoupper(CHARSET) != "UTF-8")
  					//$this->content[$return_block] = utf8_encode($this->content[$return_block]);
  						if(function_exists('iconv'))
  						$this->content[$return_block] = iconv(CHARSET, "UTF-8//TRANSLIT", $this->content[$return_block]);
  						else 
  						$this->content[$return_block] = $this->content[$return_block];	
  						
  				$content[$return_block]	= $this->content[$return_block];
  			}
  			
  		if(!$Json->exist('id')){
  			global $current_page_base;
  			$Json->set(array('id' => $current_page_base));	
  		}
  		
  		$Json->set(array('content' => $content, 'status' => $status));
  		
  		if(isset($_REQUEST['iframe'])  && $_REQUEST['iframe'] == 1){
  			echo "<textarea>".$Json->getJson()."</textarea>";
  			exit();
  		}
  		else
  			$Json->getJsonAndExit(array('content' => $content, 'status' => $status));
  	}
  }
  
  function status(){
  	return $this->status;
  }
  
  function proccessMessageStack($reset=true){
  	global $messageStack, $Json;
  	//$messages = array();
  	$messages_array = isset($messageStack->messages) ? $messageStack->messages : $messageStack->errors;
  	foreach($messages_array as $message){
      if(AJAX_CONVERT_TO_UTF8 && strtoupper(CHARSET) != "UTF-8"){
      	//$message['text'] = utf8_encode($message['text']);
      	//$messages[] = iconv(CHARSET, "UTF-8//TRANSLIT", $message['text']);
      	if(function_exists('iconv'))
      	$Json->addMessage(iconv(CHARSET, "UTF-8//TRANSLIT", $message['text']), $message['class']);
      	else 
      	$Json->addMessage($message['text'], $message['class']);
      }
      else 
      	$Json->addMessage($message['text'], $message['class']);
  	}
  	
  	/*if(count($messages) > 0){
  		$messages = array_values($messages);
  		$Json->addMessage($message['text'], $message['class']);
  	}*/
  	if($reset) { $messageStack->reset(); unset($_SESSION['messageToStack']);}
  }
  
//  function resetMessageStack(){
//    global $messageStack;
//    $messageStack->reset();
//  }
  
  function setOptions($options){
  	global $Json;
  	$Json->set($options);
  }
  
  function setReturnBlocks($blocks){
  	global $db;
  	// allow over-ride of blocks needed to be returned
  	if(isset($_REQUEST['ajax_return_blocks']))
  		$blocks = explode(',', zen_db_input($_REQUEST['ajax_return_blocks']));
  	
  	$this->return_blocks = $blocks;
  	
  	// load all?
  	if($this->return_blocks == '*') return;
  	
  	// we will see if the structure is ready
  	
  	if(isset($_SESSION['block_structure']) && isset($_SESSION['block_map']) && isset($_SESSION['block_flip_map'])){
  		$this->structure = $_SESSION['block_structure'];
  		$this->map = $_SESSION['block_map'];
  		$this->flip_map = $_SESSION['block_flip_map'];
  	}
  	else{
  		$db_blocks = $db->Execute("SELECT * FROM ".TABLE_AJAX_BLOCKS);
  		while(!$db_blocks->EOF){
          $this->map[$db_blocks->fields['id']] = $this->structure[$db_blocks->fields['id']]['block'] = $db_blocks->fields['block'];
          $this->flip_map[$db_blocks->fields['block']] = $db_blocks->fields['id'];
          $this->structure[$db_blocks->fields['id']]['parent_id']					= $db_blocks->fields['parent_id'];
          $this->structure[$db_blocks->fields['id']]['path'][] 						= $db_blocks->fields['id'];
          $this->structure[$db_blocks->fields['parent_id']]['children'][] = $db_blocks->fields['id'];;
          $db_blocks->MoveNext();
  		}
  	
  		$this->structure[0]['block'] = '<---top--->';
  		// walk through the array and build sub/cPath and other addtional info needed
  		foreach($this->structure as $key => $value){
  			if(!isset($value['block']))
  				$this->setOption('process_all_blocks',true);
  			// only merge if parent cat is not 0
  			if(isset($this->structure[$key]['parent_id']) && $this->structure[$key]['parent_id'] > 0){
  				if(is_array($this->structure[$this->structure[$key]['parent_id']]['path']) && count($this->structure[$this->structure[$key]['parent_id']]['path'])> 0)
  					$this->structure[$key]['path'] = array_merge($this->structure[$this->structure[$key]['parent_id']]['path'],$this->structure[$key]['path']);
  			}
  		}
  		$_SESSION['block_structure'] = $this->structure;
  		$_SESSION['block_map'] = $this->map;
  		$_SESSION['block_flip_map'] = $this->flip_map;
  	}
  	$diff = array_diff($this->return_blocks, $this->map);				
  	if(count($diff) > 0){
  		$this->setOption('process_all_blocks',true);
  	}
  	else
      foreach($this->structure as $key => $value){
        if(!isset($value['block'])){
        	$this->setOption('process_all_blocks',true);
        	break;
        }
	}
  	if($this->getOption('process_all_blocks')){
  		unset($_SESSION['block_structure']);
  		unset($_SESSION['block_map']);
  		unset($_SESSION['block_flip_map']);
  	}
  	else{
  		// now we have to set allow blocks
  		$allowed_block_ids = array();
  		foreach($blocks as $block){
  			$allowed_block_ids = array_merge($allowed_block_ids, $this->structure[$this->flip_map[$block]]['path']);
  		}
  		$allowed_block_ids = array_unique($allowed_block_ids);
  		
  		foreach ($allowed_block_ids as $block_id){
  			$this->allowed_blocks[] = $this->map[$block_id];
  		}
  		
  		if(count($this->allowed_blocks) < count($this->return_blocks)){
  			$this->setOption('process_all_blocks', true);
  		}
  	}
  }
  
  function startBlock($block_name){		
  	if(!$this->status)
  		return true;
  	
  	elseif(!$this->getOption('process_all_blocks') && !$this->checkAllowedBlock($block_name))
  		return false;
  	
  	if($this->getOption('process_all_blocks') && !isset($this->flip_map[$block_name]))
  		$this->new_blocks['new'][] = $block_name;	
  		
  	$this->block_queue[] = 	$block_name;
  	ob_start();
  	return true;
  }
  
  function endBlock(){
  	if(!$this->status)
  		return false;
  	
  	$block_name = array_pop($this->block_queue);		
  	$this->content[$block_name] = ob_get_clean();
  	
  	if($this->return_blocks == '*') return true;
  	//print_nice($this->map);
  	//print_nice($this->structure);
  	//exit();
  	$b = (empty($this->block_queue)) ? 'top' : end($this->block_queue);
  	if($this->getOption('process_all_blocks')){
  		// insert new blocks
  		//if(isset($this->flip_map[$block_name])){
  			//$this->new_blocks['insert'][$b]['id'] = 0;
  			$this->new_blocks['structure'][$block_name]['parent'] = $b;
  			$this->new_blocks['structure'][$block_name]['id'] = 0;
  			//$this->new_blocks['insert'][$b]['children'][] = $block_name;				
  		//}
  		
  		// checking if the blocks have new parents, thus need updating parent_id
  		if(@in_array($b, $this->new_blocks['new']) && !@in_array($block_name, $this->new_blocks['new'])) {
  			$this->new_blocks['update'][$b]['id'] = 0;
  			$this->new_blocks['update'][$b]['children'][] = $block_name;
  		}
  		
  		// we check if we have to update parent id for some reason
  		if(isset($this->flip_map[$b]) && isset($this->flip_map[$block_name]) && ($this->structure[$this->flip_map[$block_name]]['parent_id'] != $this->flip_map[$b]) ){
  			$this->new_blocks['update'][$b]['id'] = $this->flip_map[$b];
  			$this->new_blocks['update'][$b]['children'][] = $block_name;
  		}
  	}		
  	return true;
  }
  	
  public function redirect($url, $httpResponseCode = '', $redirect_type){  
    $this->excuteHook('redirect');
  	
    global $Json;
  	if($this->status){
  		// TODO: we map if we have to
  		// end mapping
  		$this->proccessMessageStack(false);
  		$Json->reset();
  		/*
  		// here we want to attempt to make sure that it understands we are still in ajax mode
  		// TODO: for seo module we will have to do something here
  		if(strpos($url, 'isajaxrequest') === false){
  			if(strpos($url, '?') !== false)
  				$url = "$url&isajaxrequest=1";
  			else 
  				$url = "$url?isajaxrequest=1";
  		}*/
  		
  		$Json->setJsonAndExit(array('url' => $url, 'status' => 'redirect', 'redirect_type' => $redirect_type, 'content' => ''));
  	}
  	elseif(!$this->getOption('include'))
  		_zen_redirect($url, $httpResponseCode);
  	else 
  		echo '<script language="javascript" type="text/javascript">window.location = "'.$url.'";</script>';
  }
  
  function noAjaxRedirect($page){
  	if(!$this->status && !$this->getOption('include'))
  		zen_redirect($page);
  }
  
  function noJsRedirect($page){
  	if((!isset($_SESSION['javascript_enabled']) || !$_SESSION['javascript_enabled']) && !$this->getOption('include'))	
  		zen_redirect($page);
  }
  
  function checkAllowedBlock($block){
  	if(empty($this->allowed_blocks) || in_array($block, $this->allowed_blocks))
  		return true;
  	return false;
  }
  
  function loadLanguage($current_page_base){
  	global $language_page_directory, $template, $template_dir_select;
  	$directory_array = $template->get_template_part($language_page_directory . $template_dir_select, '/^'.$current_page_base . '/');
  	while(list ($key, $value) = each($directory_array)) {
  			// echo "I AM LOADING: " . $language_page_directory . $template_dir_select . $value . '<br />';
  			require_once($language_page_directory . $template_dir_select . $value);
  	}
  		
  	// load master language file(s) if lang files loaded previously were "overrides" and not masters.
  	if ($template_dir_select != '') {
  			$directory_array = $template->get_template_part($language_page_directory, '/^'.$current_page_base . '/');
  			while(list ($key, $value) = each($directory_array)) {
    			// echo "I AM LOADING MASTER: " . $language_page_directory . $value.'<br />';
    			require_once($language_page_directory . $value);
  			}
  	}
  }
  
  function addMessage($class, $message, $type = 'error'){
  	$this->_addMessage($class, $message, $type, false);
  }
  
  function addSessionMessage($class, $message, $type = 'error'){
  	$this->_addMessage($class, $message, $type, true);
  }
  
  function _addMessage($class, $message, $type = 'error', $session = false){
  	global $messageStack;
  	if($session)
  		$messageStack->_add_session($class, $message, $type);
  	else 
  		$messageStack->_add($class, $message, $type);
  	$this->messages[$type] = array('class' => $class, 'message' => $message, 'session' => $session);
  }
  
  function storeMessage(){
  	global $messageStack;
  	$_SESSION['ajax_session_messages'] = $messageStack;
  }
  
  function restoreMessage(){
  	global $messageStack;
  	if(isset($_SESSION['ajax_session_messages']) && is_object($_SESSION['ajax_session_messages'])){
  	 	$messageStack = $_SESSION['ajax_session_messages'];
  		unset($_SESSION['ajax_session_messages']);
  	}
  }
  
  function recursiveInArray($needle, $haystack) {
      foreach ($haystack as $key => $stalk) {
          if (($key === $needle) || ($needle === $stalk) || (is_array($stalk) && $this->recursiveInArray($needle, $stalk))) {
              return true;
          }
      }
  	return false;
  }
  
  function hook($hook_to, $function_name, $params = array()){
  	$this->hooks[$hook_to][] = array('function' => $function_name, 'parameters' => $params);
  }
  
  function removeHook($hook_to, $function_name = ''){
    if(empty($function_name)) unset($this->hooks[$hook_to]);
    else
    {
      foreach ($this->hooks[$hook_to] as $key => $function)
        if($function['function'] == $function_name) {
          unset($this->hooks[$hook_to][$key]); 
          break;
        }
    }
  }
  
  function excuteHook($hook_to){
  	if(!isset($this->hooks[$hook_to])) return false;
  	foreach($this->hooks[$hook_to] as $hook){
 	  call_user_func_array($hook['function'], $hook['parameters']);
  	}
  }
  
  function map($map=array()){
  	$this->link_map = $map;
  }
}
/*
function print_nice($elem,$max_level=10,$print_nice_stack=array()){
    if(is_array($elem) || is_object($elem)){
        if(in_array(&$elem,$print_nice_stack,true)){
            echo "<font color=red>RECURSION</font>";
            return;
        }
        $print_nice_stack[]=&$elem;
        if($max_level<1){
            echo "<font color=red>nivel maximo alcanzado</font>";
            return;
        }
        $max_level--;
        echo "<table border=1 cellspacing=0 cellpadding=3 width=100%>";
        if(is_array($elem)){
            echo '<tr><td colspan=2 style="background-color:#333333;"><strong><font color=white>ARRAY</font></strong></td></tr>';
        }else{
            echo '<tr><td colspan=2 style="background-color:#333333;"><strong>';
            echo '<font color=white>OBJECT Type: '.get_class($elem).'</font></strong></td></tr>';
        }
        $color=0;
        foreach($elem as $k => $v){
            if($max_level%2){
                $rgb=($color++%2)?"#888888":"#BBBBBB";
            }else{
                $rgb=($color++%2)?"#8888BB":"#BBBBFF";
            }
            echo '<tr><td valign="top" style="width:40px;background-color:'.$rgb.';">';
            echo '<strong>'.$k."</strong></td><td>";
            print_nice($v,$max_level,$print_nice_stack);
            echo "</td></tr>";
        }
        echo "</table>";
        return;
    }
    if($elem === null){
        echo "<font color=green>NULL</font>";
    }elseif($elem === 0){
        echo "0";
    }elseif($elem === true){
        echo "<font color=green>TRUE</font>";
    }elseif($elem === false){
        echo "<font color=green>FALSE</font>";
    }elseif($elem === ""){
        echo "<font color=green>EMPTY STRING</font>";
    }else{
        echo str_replace("\n","<strong><font color=red>*</font></strong><br>\n",$elem);
    }
}*/