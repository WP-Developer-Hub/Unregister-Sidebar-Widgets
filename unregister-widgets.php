<?php
/*
Plugin Name: Unregister Sidebar Widgets
Plugin URI: https://github.com/ShinichiNishikawa/Unregister-Sidebar-Widgets
Description: You can choose and unregister/disable/hide widgets which you don't need, both defaults and added by plugins.
Author: Shinichi Nishikawa & matained DJABhipHop
Requires PHP: 7.2
Requires at least: 6.0
License: GPL2 or later
Version: 3.3.0
Author URI: http://nskw-style.com
Text Domain: Unregister-Sidebar-Widgets
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
if(!defined('ABSPATH')){
    exit;
}

require_once plugin_dir_path(__FILE__) . 'inc/unregister-widgets-table.php';

if (!class_exists('unregister_sidebar_widgets')){
    class unregister_sidebar_widgets {
        public $classes = [];
        public $classes_re = [];
        public $classes_un = [];

        function __construct(){
            add_action('admin_menu', [$this, 'menu']);
            add_action('admin_init', [$this, 'save']);
            add_action('widgets_init', [$this, 'unregister'], 15);
        }

        // add menu
        public function menu(){
            add_theme_page(
                __('Unregister Widgets', 'Unregister-Sidebar-Widgets'),
                __('Unregister Widgets', 'Unregister-Sidebar-Widgets'),
                'activate_plugins',
                'unregister_widget',
                array($this, 'form')
           );
        }

        // get array of UNregistered widgets
        // from the DB.
        public function get_unregistered(){
            $from_db = get_option('unregid_classes');

            if ($from_db) {
                $this->classes_un = $from_db;
            } else {
                $this->classes_un = [];
            }
        }

        // make associative array of registered widgets
        // from $wp_registered_widgets global variable.
        public function get_registered(){
            global $wp_registered_widgets;

            foreach ($wp_registered_widgets as $rw){
                $obj = $rw['callback'][0];
                $class = get_class($obj);

                $this->classes_re[$class] = $this->class_to_namedesc($class);
            }
        }

        // return name & desc array by given class name.
        // it's possible only for registered widgets.
        public function class_to_namedesc($class){
            global $wp_widget_factory;

            $desc_missing_label = __('No description available.', 'Unregister-Sidebar-Widgets');

            // Check if the class exists in the widget factory.
            if (isset($wp_widget_factory->widgets[$class])){
                $obj = $wp_widget_factory->widgets[$class];

                // Get name and description, with fallbacks.
                return array(
                    'name' => $obj->widget_options['name'] ?? $class,
                    'desc' => $obj->widget_options['description'] ?? $desc_missing_label,
                );
            }

            // Return default values if class is not found.
            return array(
                'name' => $class,
                'desc' => $desc_missing_label,
            );
        }

        // save the key[class]=>[name=>name, desc=>desc] array
        public function save(){
            $submit = isset($_POST['uw-submit']) ? sanitize_text_field(wp_unslash($_POST['uw-submit'])) : '';

            if (isset($_POST['uw-submit']) && $submit && check_admin_referer('uw-display-form', 'unregister_widget')){

                $dont_save = array('unregister_widget', '_wp_http_referer', 'uw-submit');

                foreach ($dont_save as $dn){
                    if (isset($posted[$dn])){
                        unset($posted[$dn]);
                    }
                }

                if (isset($_POST['uw_widgets'])){
                    $unregid_classes = [];
                    $uw_widgets = array_map('sanitize_text_field', wp_unslash($_POST['uw_widgets']));

                    foreach ($uw_widgets as $class_name){
                        $unregid_classes[$class_name] = $this->class_to_namedesc($class_name);
                    }
                }

                $updated = update_option('unregid_classes', $unregid_classes);

                if ($updated){
                    add_action('admin_notices', array($this, 'notice'));
                }

                if (isset($_SERVER['REQUEST_URI'])) {
                    wp_redirect(esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])));
                }
            }
        }

        // admin notice
        public function notice() {
            ?>
            <div class="updated">
                <ul>
                    <li><?php echo esc_html_e('Saved! The widgets you chose have been hidden. :)', 'Unregister-Sidebar-Widgets'); ?> <a href="<?php echo esc_url(admin_url('widgets.php')); ?>"><?php esc_html_e('Widgets Page', 'Unregister-Sidebar-Widgets'); ?></a></li>
                </ul>
            </div>
            <?php
        }

        // unregister actually
        public function unregister() {
            $this->get_unregistered();
            $unregid = array_keys($this->classes_un);

            foreach ($unregid as $un) {
                unregister_widget($un);
            }
        }

        // display the form
        public function form() {
            $this->get_registered();
            $this->get_unregistered();

            $all_wids = array_merge($this->classes_re, $this->classes_un);

            $table = new UW_Widgets_List_Table($all_wids, $this->classes_un);
            $table->prepare_items();

            ?>
            <div class="wrap">
                <h1><?php esc_html_e('Unregister Widgets', 'Unregister-Sidebar-Widgets'); ?></h1>
                <span><?php esc_html_e('Choose the widgets you want to unregister', 'Unregister-Sidebar-Widgets'); ?></span>
                <form method="post" action="">
                    <?php
                    $table->display();
                    wp_nonce_field('uw-display-form', 'unregister_widget');
                    ?>
                </form>
            </div>
            <?php
        }

    }
    new unregister_sidebar_widgets();
}
