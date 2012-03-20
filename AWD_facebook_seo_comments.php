<?php
/*
Plugin Name: Facebook AWD Seo Comments
Plugin URI: http://www.ahwebdev.fr
Description: This plugin merge Facebook Comments with native Wordpress Comments System. Need Facebook AWD All in One plugin v0.9.7.7 to work.
Version: 1.1
Author: AHWEBDEV
Author URI: http://www.ahwebdev.fr
License: Copywrite AHWEBDEV
Text Domain: AWD_facebook_seo_comments
Last modification: 15/07/2011
*/

add_action('plugins_loaded', 'initial');
function initial()
{
/*	global $AWD_facebook;
	$model_path = $AWD_facebook->get_plugins_model_path();
	
	require_once($model_path);
	require_once(dirname(__FILE__).'/inc/classes/class.AWD_facebook_seo_comments.php');
	
	//instance
	$AWD_facebook_seo_comments = new AWD_facebook_seo_comments($AWD_facebook);*/
}
?>