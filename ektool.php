<?php
/**
* Plugin Name: [EK] EK ToOls
* Plugin URI: http://www.wdev.pro/
* Description: Ajoute juste ce qu'il me faut !
* Version: 1.2
* Author: Erwan Kuznik
* Author URI: http://www.wdev.pro/
* 
*	1.0  : 29/04/2021 - ajout d'éléments dans le header
*	1.1  : 04/05/2021 - copie des pages et posts
*	1.2  : 16/05/2021 - ajout de l'icone The Beautifull Walk pour le menu social dans le thème GridLove
*	1.3  : 14/11/2021 - Regéneration des miniatures
* 
**/

/***********************************************
 ****																				****
 ****								SETTINGS								****
 ****																				****
 ***********************************************/
$ektools_v = "1.3";
$cleprivee = "maCléPrivée";

// ajout de CSS et JS en front... à utiliser ultérieurement
function ektools_enqueue_scripts() 
{
	global $ektools_v;
	wp_register_style('ektools_css',  plugin_dir_url( __FILE__ ) . '/ektools.css', array(), $ektools_v );
	wp_enqueue_style('ektools_css');

	//wp_enqueue_script('ektools_js', plugin_dir_url( __FILE__ ).'/ektools.js', array('jquery'), $ektools_v, true );
	//wp_add_inline_script('ektools_js', "ektools_init();");
}


function ektools_enqueue_admin() 
{
	wp_register_style( 'ektools_css_back', plugin_dir_url( __FILE__ ) . '/ektools_back.css', false, $ektools_v );
	wp_enqueue_style( 'ektools_css_back' );
}



function ektools_menu() 
{
	add_menu_page('[EK] Tools', 'EK Tools', 'administrator', 'ektools-conf', 'ektools_settings_page', 'dashicons-admin-tools');	
	add_submenu_page('ektools-conf', 'Miniatures', 'Miniatures', 'administrator', 'ektools-miniatures', 'ektools_miniatures' );
	// 1.1
	add_filter('post_row_actions', 'ektools_replicate_link', 10, 2);
	add_filter('page_row_actions', 'ektools_replicate_link', 10, 2);
	add_action('admin_action_ektools_repliquer', 'ektools_repliquer_post');
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
if(is_admin()) 
{
	add_action('wp_ajax_ektregenpix', 'ektools_ajax_regenpix');
}



/***********************************************
****																				****
****									BACK									****
****																				****
***********************************************/
function ektools_miniatures() 
{
	global $cleprivee;

	$args = [
    'post_type'      => 'attachment',
    'post_mime_type' => 'image',
    'post_status'    => 'inherit',
    'posts_per_page' => -1,
	];

	$lstimg = new WP_Query($args);

	echo '<h1>Visuels</h1>';
	echo '<a href="#" onclick="voirvisu(); return false;">Voir les visuels</a> | ';
	echo '<a href="#" onclick="regneall(); return false;">Tout regénérer</a>';
	echo '<table id="lstpixz">';
	foreach($lstimg->posts as $v)
	{
		echo '<tr data-pixid="'.$v->ID.'">';
		echo '	<td data-src="'.$v->guid.'"><img height="40" style="width: auto; display: none;"></td>';
		echo '	<td>'.$v->post_title.'</td>';
		echo '	<td><a href="#" onclick="regenpix('.$v->ID.'); return false;" class="isdash dashicons-image-rotate"></a></td>';
		echo '</tr>';
	}
	
	echo '</table>';
	$ajax_nonce = wp_create_nonce($cleprivee);
	?>
	<script>
	function voirvisu() 
	{
		var $ = jQuery;
		$('td[data-src]').each(function() {
			$(this).find('img').attr('src', $(this).attr('data-src')).css('display', 'block');
		});
	}
	function regneall()
	{
		var $ = jQuery;
		$('#lstpixz').toggleClass('reginall');
		if($('#lstpixz').hasClass('reginall'))
		{regenNext();}
	}
	function regenNext()
	{
		var $ = jQuery;
		pid = $('#lstpixz').find('tr:not(.isdone)').attr('data-pixid');
		regenpix(pid);
	}
	function regenpix(id) 
	{
		var $ = jQuery;
		var dt = {
			'security'	:	"<?php echo $ajax_nonce;?>",
			'action'		:	'ektregenpix',
			'pixid'			:	id
		};
		$.post(ajaxurl, dt, retRegen, "JSON");
	};

	function retRegen(e)
	{
		var $ = jQuery;
		if(e.res==true)
		{
			$('tr[data-pixid='+e.pid+']').addClass('isdone');
			if($('#lstpixz').hasClass('reginall'))
			{regenNext();}
		}
		else
		{
			console.log('erreur !');
		}
		
	}
	</script>
	<?php
	//echo '<pre>'.print_r($lstimg->posts, true).'</pre>';
}

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

function ektools_replicate_link($actions, $post) 
{
	if(current_user_can('edit_posts') && ($post->post_type=='post' || $post->post_type=='page'))
  {
		$actions['duplicate'] = '<a href="'.wp_nonce_url('admin.php?action=ektools_repliquer&post=' . $post->ID, basename(__FILE__), 'duplicate_nonce') . '" title="Dupliquer">Dupliquer</a>';
	}
	return $actions;
}


/***********************************************
****																				****
****									UTILS									****
****																				****
***********************************************/
function ektools_ajax_regenpix()
{
	global $cleprivee;

	check_ajax_referer($cleprivee, 'security');
	$pid = intval($_POST['pixid']);
	if($pid>0)
	{
		$atch = get_post($pid);
		$fl = wp_get_original_image_path($pid);
		wp_create_image_subsizes($fl, $pid);
		$r = ['res'=>true, 'pid'=>$pid];
	}
	else
	{
		$r = ['res'=>false];
	}

	wp_die(json_encode($r));
}

function ektools_repliquer_post()
{
  // honteusement pompé de https://rudrastyh.com/wordpress/duplicate-post.html
	global $wpdb;
	if(empty($_GET['post']) || empty($_GET['action']) || $_GET['action']!='ektools_repliquer')
  {wp_die('Requête incomplète !');}
 
	if ( empty($_GET['duplicate_nonce']) || !wp_verify_nonce($_GET['duplicate_nonce'], basename( __FILE__ )))
  {wp_die('Requête interdite !');}
 
	$post_id = absint($_GET['post']);
	$post = get_post($post_id);
	$current_user = wp_get_current_user();
	$new_post_author = $current_user->ID;

	if (isset( $post ) && $post != null) 
  {
		$args = [
			'comment_status' => $post->comment_status,
			'ping_status'    => $post->ping_status,
			'post_author'    => $new_post_author,
			'post_content'   => $post->post_content,
			'post_excerpt'   => $post->post_excerpt,
			'post_name'      => $post->post_name,
			'post_parent'    => $post->post_parent,
			'post_password'  => $post->post_password,
			'post_status'    => 'draft',
			'post_title'     => $post->post_title,
			'post_type'      => $post->post_type,
			'to_ping'        => $post->to_ping,
			'menu_order'     => $post->menu_order
		];
 
		$new_post_id = wp_insert_post($args);
		$taxonomies = get_object_taxonomies($post->post_type);
		foreach ($taxonomies as $taxonomy) 
    {
			$post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
			wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
		}

		$post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id");
		if (count($post_meta_infos)!=0) 
    {
			$sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
			foreach ($post_meta_infos as $meta_info) 
			{
				$meta_key = $meta_info->meta_key;
				if( $meta_key == '_wp_old_slug' ) continue;
				$meta_value = addslashes($meta_info->meta_value);
				$sql_query_sel[]= "SELECT $new_post_id, '$meta_key', '$meta_value'";
			}
			$sql_query.= implode(" UNION ALL ", $sql_query_sel);
			$wpdb->query($sql_query);
		}
 
 
		wp_redirect(admin_url('post.php?action=edit&post='.$new_post_id));
		exit;
	} 
	else
	{wp_die('Echec de duplication !');}
}


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
add_action('wp_enqueue_scripts', 'ektools_enqueue_scripts');
add_action('admin_enqueue_scripts', 'ektools_enqueue_admin');
add_action('wp_head', 'ektools_add_header');





