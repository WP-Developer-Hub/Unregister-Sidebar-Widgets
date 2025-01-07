<?php
/*
Plugin Name: Unregister Sidebar Widgets
Plugin URI: https://github.com/ShinichiNishikawa/Unregister-Sidebar-Widgets
Description: You can choose and unregister/disable/hide widgets which you don't need, both defaults and added by plugins.
Author: Shinichi Nishikawa & DJABhipHop
Requires PHP: 7.2
Requires at least: 6.0
License: GPL2 or later
Version: 3.2.0
Author URI: http://nskw-style.com
Text Domain: unregister_sidebar_widget
Domain Path: /languages

License:
 Released under the GPL license
  http://www.gnu.org/copyleft/gpl.html
  Copyright 2013 Shinichi Nishikawa (email : shinichi.nishikawa@gmail.com)
  Copyright 2025 DJABHipHop (email : djabhiphop-DJABHipHop@yahoo.com)

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

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( ! class_exists( 'unregister_sidebar_widgets' ) ) {
    class unregister_sidebar_widgets {

        public $classes = array();
        public $classes_re = array();
        public $classes_un = array();

        function __construct() {
            add_action( 'admin_menu',   array( $this, 'menu' )       );
            add_action( 'admin_init',   array( $this, 'save' )       );
            add_action( 'widgets_init', array( $this, 'unregister' ), 15 );
        }

        // add menu
        public function menu() {
            add_theme_page(
                __( 'Unregister Widgets', 'unregister_sidebar_widget' ),
                __( 'Unregister Widgets', 'unregister_sidebar_widget' ),
                'activate_plugins',
                'unregister_widget',
                array( $this, 'form' )
            );
        }

        // get array of UNregistered widgets
        // from the DB.
        public function get_unregistered() {
            $from_db = get_option( 'unregid_classes' );
            if ( $from_db ) {
                $this->classes_un = $from_db;
            } else {
                $this->classes_un = array();
            }
        }

        // make associative array of registered widgets
        // from $wp_registered_widgets global variable.
        public function get_registered() {
            global $wp_registered_widgets;

            foreach ( $wp_registered_widgets as $rw ) {
                $obj   = $rw['callback'][0];
                $class = get_class( $obj );

                $this->classes_re[$class] = $this->class_to_namedesc( $class );
            }
        }

        // return name & desc array by given class name.
        // it's possible only for registered widgets.
        public function class_to_namedesc( $class ) {
            global $wp_widget_factory;

            // Check if the class exists in the widget factory.
            if ( isset( $wp_widget_factory->widgets[ $class ] ) ) {
                $obj = $wp_widget_factory->widgets[ $class ];

                // Get name and description, with fallbacks.
                $name = $obj->widget_options['name'] ?? $class; // Replace spaces with underscores in the class name.
                $desc = $obj->widget_options['description'] ?? 'No description available.';

                return [
                    'name' => $name,
                    'desc' => $desc,
                ];
            }

            // Return default values if class is not found.
            return [
                'name' => $class,
                'desc' => 'No description available.',
            ];
        }

        // save the key[class]=>[name=>name, desc=>desc] array
        public function save() {
            if ( isset( $_POST['uw-submit'] ) && $_POST['uw-submit'] && check_admin_referer( 'uw-display-form', 'unregister_widget' ) ) {

                $dont_save = array( 'unregister_widget', '_wp_http_referer', 'uw-submit' );
                
                foreach ( $dont_save as $dn ) {
                    if ( isset( $posted[$dn] ) ) {
                        unset($posted[$dn]);
                    }
                }
                
                if ( isset( $_POST['uw_widgets'] ) ) {
                    $unregid_classes = array();
                    foreach ( $_POST['uw_widgets'] as $class_name ) {
                        $unregid_classes[ $class_name ] = $this->class_to_namedesc( $class_name );
                    }
                }
                
                $updated = update_option( 'unregid_classes', $unregid_classes );
            
                if ( $updated ) {
                    add_action( 'admin_notices', array( $this, 'notice' ) );
                }
            
            }

        }

        // admin notice
        public function notice() {
            ?>
            <div class="updated">
                <ul>
                    <li><?php echo esc_html__( 'Saved! The widgets you chose have been hidden. :)', 'unregister_sidebar_widget' ); ?> <a href="<?php echo admin_url( 'widgets.php' ); ?>"><?php esc_html_e( 'Widgets Page', 'unregister_sidebar_widget' ); ?></a></li>
                </ul>
            </div>
            <?php
        }

        // unregister actually
        public function unregister() {
            $this->get_unregistered();
            $unregid = array_keys( $this->classes_un );
            foreach ( $unregid as $un ) {
                unregister_widget($un);
            }
        }

        // display the form
        public function form() {
            ?>
            <div class="wrap">
                <h1><?php esc_html_e( 'Unregister Widgets', 'unregister_sidebar_widget' ); ?></h1>
                <form id="display-form" method="post" action="">
                    <h2><?php esc_html_e( 'Choose widgets to unregister', 'unregister_sidebar_widget' ); ?></h2>
                    <div class="tablenav top">
                        <div class="alignleft actions bulkactions">
                            <?php submit_button( __( 'Save Widgets', 'unregister_sidebar_widget' ), 'button action', 'uw-submit' ); ?>
                        </div>
                        <br class="clear">
                    </div>
                    <table class="wp-list-table widefat plugins">
                        <thead>
                            <tr>
                                <td id="cb" class="manage-column column-cb check-column">
                                    <input id="cb-select-all-1" type="checkbox">
                                </td>
                                <th scope="col"><?php esc_html_e( 'Widget Name', 'unregister_sidebar_widget' ); ?></th>
                                <th scope="col"><?php esc_html_e( 'Description', 'unregister_sidebar_widget' ); ?></th>
                            </tr>
                        </thead>
                        <tbody id="the-list">
                            <?php
                            $this->get_registered();
                            $this->get_unregistered();

                            $all_wids = array_merge( $this->classes_re, $this->classes_un );
                            $already_unregistered = array_keys( $this->classes_un );
                            foreach ( $all_wids as $key => $val ) {
                                ?>
                                <tr <?php echo in_array( $key, $already_unregistered ) ? 'class="active"' : 'class="inactive"'; ?> data-slug="<?php echo esc_attr( $key ); ?>" data-plugin="<?php echo esc_attr( $key ); ?>" >
                                    <th scope="row" class="check-column">
                                        <input
                                            type="checkbox"
                                            name="uw_widgets[]"
                                            id="<?php echo esc_attr( $key ); ?>"
                                            value="<?php echo esc_attr( $key ); ?>"
                                            <?php checked( in_array( $key, $already_unregistered ) ); ?>
                                        />
                                    </th>
                                    <td>
                                        <label for="<?php echo esc_attr( $key ); ?>">
                                            <?php echo esc_html( $val['name'] ); ?>
                                        </label>
                                    </td>
                                    <td><?php echo esc_html( $val['desc'] ); ?></td>
                                <?php
                                $num++;
                            } // end foreach.
                            ?>
                        </tbody>
                    </table>
                    <?php wp_nonce_field( 'uw-display-form', 'unregister_widget' ); ?>
                    <div class="tablenav bottom">
                        <div class="alignleft actions bulkactions">
                            <?php submit_button( __( 'Save Widgets', 'unregister_sidebar_widget' ), 'button action', 'uw-submit' ); ?>
                        </div>
                        <br class="clear">
                    </div>
            </form>
            </div>
            <?php
        }
    }
    new unregister_sidebar_widgets();
}
