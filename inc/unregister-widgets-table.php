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
            parent::__construct([
                'singular' => 'widget',
                'plural'   => 'widgets',
                'ajax'     => false
            ]);
            $this->widgets = $widgets;
            $this->already_unregistered = array_keys($unregistered);
        }

        function get_columns() {
            return [
                'cb' => '<input type="checkbox">',
                'name' => __('Widget Name', 'unregister_sidebar_widget'),
                'desc' => __('Description', 'unregister_sidebar_widget'),
            ];
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
                $data[] = [
                    'slug' => $slug,
                    'name' => $widget['name'],
                    'desc' => $widget['desc']
                ];
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
