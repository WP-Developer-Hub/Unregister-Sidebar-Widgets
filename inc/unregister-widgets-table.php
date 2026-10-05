<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!class_exists('UW_Widgets_List_Table')) {
    if (!class_exists('WP_List_Table')) {
        require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
    }

    class UW_Widgets_List_Table extends WP_List_Table {
        private $widgets;
        private $already_unregistered;

        public function __construct($widgets, $unregistered) {
            parent::__construct(array(
                'singular' => __('widget', 'Unregister-Sidebar-Widgets'),
                'plural' => __('widgets', 'Unregister-Sidebar-Widgets'),
                'ajax' => false
            ));
            $this->widgets = $widgets;
            $this->already_unregistered = array_keys($unregistered);
        }

        function bulk_actions($which = '') {
            submit_button(__('Save Widgets', 'Unregister-Sidebar-Widgets'), 'button action', 'uw-submit', false);
        }

        function get_columns() {
            return array(
                'cb' => '<input type="checkbox">',
                'name' => __('Widget Name', 'Unregister-Sidebar-Widgets'),
                'desc' => __('Description', 'Unregister-Sidebar-Widgets'),
            );
        }

        function column_cb($item) {
            $checked = in_array($item['slug'], $this->already_unregistered) ? 'checked' : '';
            return sprintf(
                '<input type="checkbox" name="uw_widgets[]" value="%s" %s />',
                esc_attr($item['slug']),
                $checked
            );
        }

        function prepare_items() {
            $columns = $this->get_columns();
            $hidden = [];
            $sortable = [];

            $this->_column_headers = [$columns, $hidden, $sortable];

            // Transform data for table rows
            $data = [];
            foreach ($this->widgets as $slug => $widget) {
                $data[] = array(
                    'slug' => $slug,
                    'name' => $widget['name'],
                    'desc' => $widget['desc']
                );
            }
            $this->items = $data;
        }

        function column_default($item, $column_name) {
            switch ($column_name) {
                case 'name':
                case 'desc':
                    return esc_html($item[$column_name]);
                default:
                    return '';
            }
        }
    }
}
