<?php
$Ajax->start();
if(isset($_REQUEST['javascript_enabled'])){
	if($_REQUEST['javascript_enabled'] == 1)
		$_SESSION['javascript_enabled'] = true;
	elseif($_REQUEST['javascript_enabled'] == 0)
		$_SESSION['javascript_enabled'] = false;
}