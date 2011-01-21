<?php

if (!defined('IS_ADMIN_FLAG')) {
 die('Illegal Access');
} 

$autoLoadConfig[0][] = array('autoType'=>'class',
                                'loadFile'=>'class.json.php');
$autoLoadConfig[0][] = array('autoType'=>'class',
                                'loadFile'=>'class.ajax.php');
$autoLoadConfig[0][] = array('autoType'=>'classInstantiate',
                           		'className'=>'Json',
                            	'objectName'=>'Json',
                            	'checkInstantiated'=>true);                   	                                
$autoLoadConfig[130][] = array('autoType'=>'classInstantiate',
                           		'className'=>'Ajax',
                            	'objectName'=>'Ajax',
                            	'checkInstantiated'=>true);                   	
$autoLoadConfig[130][] = array('autoType'=>'init_script',
                                'loadFile'=> 'init_ajax_config.php');