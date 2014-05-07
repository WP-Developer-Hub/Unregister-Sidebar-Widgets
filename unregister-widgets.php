<?php
/*
Plugin Name: Unregister Sidebar Widgets
Plugin URI: https://github.com/ShinichiNishikawa/Unregister-Sidebar-Widgets
Description: You can choose and unregister/disable/hide widgets which you don't need, both defaults and added by plugins.
Author: Shinichi Nishikawa
Version: 0.1
Author URI: http://nskw-style.com

License:
 Released under the GPL license
  http://www.gnu.org/copyleft/gpl.html
  Copyright 2013 Shinichi Nishikawa (email : shinichi.nishikawa@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Retrieve all the registered widgets.
function uw_get_all_widgets() {
	
	global $wp_registered_widgets;
		
	$widgets_objs = array();
	
	foreach ( $wp_registered_widgets as $w ) {
		$w_obj          = $w['callback'][0];
		$widgets_objs[] = get_class( $w_obj );
	}
	
	return $widgets_objs;
	
}

// add menu page
add_action( 'admin_menu', 'uw_add_menu' );
function uw_add_menu() {
	add_theme_page(
		'Unregister Widgets',
		'Unregister Widgets',
		'activate_plugins',
		'unregister_widget',
		'uw_form'
	);
}

// form
function uw_form() {	
	$registered_widgets = uw_get_all_widgets();
	?>
	<div class="wrap">
	<h2>Unregister Widgets</h2>
	<form id="uw-display-form" method="post" action="">
	
	<h3>Choose widgets to unregister</h3>
	
	<table class="form-table">
	<tr valign="top">
	<th scope="row">Check the Widgets You don't need.</th>
	<td>
	<?php
	global $wp_widget_factory;
	$uw_wid_obj = $wp_widget_factory->widgets['WP_Widget_Meta'];
	
	$saved_widgets = get_option( 'unregister_widgets' );
	$num = 0;
	foreach ( $registered_widgets as $w ) {		
		$uw_wid_obj = $wp_widget_factory->widgets[$w];
		$w_name = $uw_wid_obj->name;
		$w_desc_arr = $uw_wid_obj->widget_options;
		$w_desc = $w_desc_arr['description'];
		?>
		<label for="<?php echo $w; ?>" style="padding-bottom:30px;">
			<input 
				type="checkbox" 
				name="uw_widgets_<?php echo $num; ?>" 
				id="<?php echo $w; ?>" 
				value="<?php echo $w; ?>"
				<?php if ( in_array( $w, $saved_widgets) ) { ?>
					checked="checked"
				<?php } ?>
				/> 
			<?php echo $w_name; ?>(<?php echo $w_desc; ?>) <br />
		</label>
		<?php
		$num++;
	} // end foreach.
	?>
	</td>
	</tr>
	</table>
	<?php wp_nonce_field( 'uw-display-form', 'unregister_widget' ); ?>
	<p class="submit"><input id="submit" class="button button-primary" type="submit" value="Save" name="uw-submit"></p>
	</form>
	</div>
	<?php
}

// save
add_action( 'admin_init', 'uw_save_form' );
function uw_save_form() {

	if ( isset( $_POST['uw-submit'] ) && $_POST['uw-submit'] && check_admin_referer( 'uw-display-form', 'unregister_widget' ) ) {
		
		$posted = $_POST;
		$dont_save = array( 'unregister_widget', '_wp_http_referer', 'uw-submit' );

		foreach ( $dont_save as $dn ) {
			if ( isset( $posted[$dn] ) ) {
				unset($posted[$dn]);
			}
		}
		
		$posted_objs = array_values( $posted );
		$updated = update_option( 'unregister_widgets', $posted_objs );
		
		if ( $updated ) {
			add_action( 'admin_notices', 'uw_admin_notice' );
		}
		
	}
	
}

// admin notice
function uw_admin_notice() {
	?>
	<div class="updated">
		<ul>
			<li>Saved! The widgets you chose has been hided :)</li>
		</ul>
	</div>
	<?php
}

// unregister the widgets user chose.
add_action( 'widgets_init', 'uw_unregister_chosen_widgets' );
function uw_unregister_chosen_widgets() {
	
	$users_choice = get_option( 'unregister_widgets' );
	
	foreach ( $users_choice as $w ) {
		unregister_widget($w);
	}
	
}
