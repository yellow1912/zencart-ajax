<?php
/**
 * @package general
 * @copyright Copyright 2008-2009 RubikIntegration.com
 * @copyright Copyright 2003-2005 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: index.php 2942 2006-02-02 04:41:23Z drbyte $
 */

if(isset($_GET['ajax_action']) && !empty($_GET['ajax_action'])){
	
	$rb_ajax_action = str_ireplace(array('/','\\','.','@','#','&','*'), '',$_GET['ajax_action']);
	
	require('includes/application_top.php');
	
	$Ajax->setReturnBlocks('*');
	
	$language_page_directory = DIR_WS_LANGUAGES . $_SESSION['language'] . '/';
	
	// get the language file
	if ( file_exists("{$language_page_directory}/ajax/{$template_dir}/{$rb_ajax_action}.php") ) 
		require("{$language_page_directory}/ajax/{$template_dir}/{$rb_ajax_action}.php");
	elseif( file_exists("{$language_page_directory}/ajax/{$rb_ajax_action}.php") ) 
		require("{$language_page_directory}/ajax/{$rb_ajax_action}.php");
		
	// get the approriate module file
	if ( file_exists(DIR_WS_MODULES . 'ajax/' . $template_dir . '/' . "{$rb_ajax_action}.php") ) 
  	require(DIR_WS_MODULES . 'ajax/' . $template_dir . '/' . "{$rb_ajax_action}.php");
	elseif (file_exists(DIR_WS_MODULES . 'ajax/' . "{$rb_ajax_action}.php")) 
  	require(DIR_WS_MODULES . 'ajax/' . "{$rb_ajax_action}.php");
	
	// get the template file
	//require($template->get_template_dir("{$rb_ajax_action}.php",DIR_WS_TEMPLATE, $current_page_base,'ajax'). "/{$rb_ajax_action}.php");
	require(DIR_FS_ADMIN.DIR_WS_INCLUDES."templates/template_default/ajax/{$rb_ajax_action}.php");
	
	$Ajax->end();
	require(DIR_WS_INCLUDES . 'application_bottom.php');
}
