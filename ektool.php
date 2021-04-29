<?php
/**
* Plugin Name: [EK] EK ToOls
* Plugin URI: http://www.wdev.pro/
* Description: Ajoute juste ce qu'il me faut !
* Version: 1
* Author: Erwan Kuznik
* Author URI: http://www.wdev.pro/
* 
*	1.0  : 29/04/2021
* 
**/

/***********************************************
 ****																				****
 ****								SETTINGS								****
 ****																				****
 ***********************************************/
$ektools_v = "1";

/*
// ajout de CSS et JS en front... à utiliser ultérieurement
function ektools_enqueue_scripts() 
{
	global $ektools_v;
	wp_register_style('ektools_css',  plugin_dir_url( __FILE__ ) . '/ektools.css', array(), $ektools_v );
	wp_enqueue_style('ektools_css');

	wp_enqueue_script('ektools_js', plugin_dir_url( __FILE__ ).'/ektools.js', array('jquery'), $ektools_v, true );
	wp_add_inline_script('ektools_js', "ektools_init();");
}
*/

function ektools_enqueue_admin() 
{
	wp_register_style( 'ektools_css_back', plugin_dir_url( __FILE__ ) . '/ektools_back.css', false, $ektools_v );
	wp_enqueue_style( 'ektools_css_back' );
}



function ektools_menu() 
{
	add_menu_page('[EK] Tools', 'EK Tools', 'administrator', 'ektools-conf', 'ektools_settings_page', 'dashicons-admin-tools');	
}


/********* PARAMETRES **********/
function ektools_settings()
{
	register_setting('ektools_settings', 'ekt_gkod', array('type'=>'text', 'description'=>'Code analytics'));
}

function ektools_init() 
{
	

	add_action('admin_init', 'ektools_settings' );
}




/***********************************************
****																				****
****									AJAX									****
****																				****
***********************************************/



/***********************************************
****																				****
****									BACK									****
****																				****
***********************************************/
function ektools_settings_page() 
{
	?>
	<h1>Config</h1>
	<form method="post" action="options.php">
	<?php 
		settings_fields( 'ektools_settings' );
		do_settings_sections( 'ektools_settings' );
		?>
		<label for="ekt_gkod_fld">Injecter dans header</em> : </label><br>
		<textarea name="ekt_gkod" id="ekt_gkod_fld" cols="150" rows="12"><?php echo get_option('ekt_gkod');?></textarea><br>
		<br>
		<?php submit_button();?>
		</form>
		<?php
}



/***********************************************
****																				****
****									UTILS									****
****																				****
***********************************************/



/***********************************************
****																				****
****						INSTALLATION !							****
****																				****
***********************************************/



/***********************************************
****																				****
****								TEMPLATES								****
****																				****
***********************************************/
function ektools_add_header()
{
	echo get_option('ekt_gkod');
}
	


/***********************************************
****																				****
****							SHORTCODES !							****
****																				****
***********************************************/



/***********************************************
****																				****
****								ACTION !								****
****																				****
***********************************************/

add_action('init', 'ektools_init', '9');
add_action('admin_menu', 'ektools_menu');
//add_action('wp_enqueue_scripts', 'ektools_enqueue_scripts');
add_action('admin_enqueue_scripts', 'ektools_enqueue_admin');
add_action('wp_head', 'ektools_add_header');





