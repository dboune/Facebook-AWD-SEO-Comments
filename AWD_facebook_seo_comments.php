<?php
/*
Plugin Name: Facebook AWD Seo Comments
Plugin URI: http://www.ahwebdev.fr
Description: This plugin merge Facebook Comments with native Wordpress Comments System and more... Need Facebook AWD All in One plugin.
Version: 1.5.4
Author: AHWEBDEV
Author URI: http://www.ahwebdev.fr
License: AHWEBDEV
Text Domain: AWD_facebook_seo_comments
Last modification: 02/12/2012
*/

/**
 *
 * @author alexhermann
 *
 */
add_action('plugins_loaded', 'initial_seo_comments');
function initial_seo_comments()
{
	global $AWD_facebook;
	if(is_object($AWD_facebook)){
		$model_path = $AWD_facebook->get_plugins_model_path();
		require_once($model_path);
		require_once(dirname(__FILE__).'/inc/classes/class.AWD_facebook_seo_comments.php');
		$AWD_facebook_seo_comments = new AWD_facebook_seo_comments(__FILE__,$AWD_facebook);
	}
}
?>