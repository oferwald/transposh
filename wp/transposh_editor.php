<?php

if (!class_exists('WP_List_Table')) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class transposh_editor_table extends WP_List_Table {

    private $filter = "";

    function __construct() {
        //global $status, $page;
        parent::__construct(array(
            'singular' => __('translation', TRANSPOSH_TEXT_DOMAIN), //singular name of the listed records
            'plural' => __('translations', TRANSPOSH_TEXT_DOMAIN), //plural name of the listed records
            'ajax' => true //does this table support ajax?
        ));
    }

    function print_style() {
        echo '<style>';
        echo '.wp-list-table .column-lang { width: 5%; }';
        echo '.wp-list-table .column-source { width: 5%; }';
        echo '</style>';
    }

    function add_screen_options() {
        $option = 'per_page';
        $args = array(
            'label' => __('Translations', TRANSPOSH_TEXT_DOMAIN),
            'default' => 10,
            'option' => 'translations_per_page'
        );
        add_screen_option($option, $args);
    }

    function no_items() {
        _e('No translations found.', TRANSPOSH_TEXT_DOMAIN);
    }

    function item_key($item) {
        return base64_encode($item['timestamp'] . ',' . $item['lang'] . ',' . $item['original']);
    }

    function column_default($item, $column_name) {
        switch ($column_name) {
            case 'original':
            case 'lang':
            case 'translated':
            case 'translated_by':
            case 'source':
            case 'timestamp':
                return $item[$column_name];
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            'original' => array('original', false),
            'lang' => array('lang', false),
            'translated' => array('translated', false),
            'translated_by' => array('translated_by', false),
            'timestamp' => array('timestamp', false)
        );
        return $sortable_columns;
    }

    function get_columns() {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'lang' => __('Language', TRANSPOSH_TEXT_DOMAIN),
            'original' => __('Original string', TRANSPOSH_TEXT_DOMAIN),
            'translated' => __('Translated string', TRANSPOSH_TEXT_DOMAIN),
            'translated_by' => __('Translator', TRANSPOSH_TEXT_DOMAIN),
            'source' => __('Source', TRANSPOSH_TEXT_DOMAIN),
            'timestamp' => __('Date', TRANSPOSH_TEXT_DOMAIN)
        );
        return $columns;
    }

    function column_cb($item) {
        return sprintf(
                '<input type="checkbox" name="keys[]" value="%s" />', $this->item_key($item)
        );
    }

    function get_column_filter($remove = "") {
        $filter = "";
        foreach (["fts", "fl", "lang"] as $curfilt) {
            if ($remove != $curfilt && filter_input(INPUT_GET, $curfilt, FILTER_DEFAULT, FILTER_NULL_ON_FAILURE) !== false) {
                $filter .= "&";
                $filter .= "$curfilt=" . filter_input(INPUT_GET, $curfilt);
            }
        }
        return $filter;
    }

    function column_lang($item) {
        $filter = "";
        if (filter_input(INPUT_GET, 'fl', FILTER_DEFAULT, FILTER_NULL_ON_FAILURE)) {
            $filter = sprintf('<a href="?page=%s&action=%s%s">' . __('Remove filter') . '</a>', filter_input(INPUT_GET, "page"), 'filter-by', $this->get_column_filter('fl'));
        } else {
            $filter = sprintf('<a href="?page=%s&action=%s&fl=%s%s">' . __('Filter') . '</a>', filter_input(INPUT_GET, "page"), 'filter-by', $item['lang'], $this->get_column_filter('fl'));
        }
        $actions = ['filter' => $filter];
        return sprintf('%1$s %2$s', transposh_consts::get_language_name($item['lang']), $this->row_actions($actions));
    }

    function column_original($item) {
        $actions = ['delete' => sprintf('<a href="?page=%s&action=%s&key=%s">' . __('Delete') . '</a>', filter_input(INPUT_GET, "page"), 'delete', $this->item_key($item))];
        return sprintf('%1$s %2$s', htmlspecialchars(htmlspecialchars_decode($item['original'])), $this->row_actions($actions));
    }

    function column_translated($item) {
        if (transposh_consts::is_language_rtl($item['lang'])) {
            return sprintf('<span dir="rtl" style="float:right">%1$s</span>', $item['translated']);
        }

        return $item['translated'];
    }

    function column_translated_by($item) {
        // check if its a user and try to grab his login
        $by = transposh_utils::wordpress_user_by_by($item['translated_by']);
        $filter = "";
        if (filter_input(INPUT_GET, 'ftb', FILTER_DEFAULT, FILTER_NULL_ON_FAILURE)) {
            $filter = sprintf('<a href="?page=%s&action=%s%s">' . __('Remove filter') . '</a>', filter_input(INPUT_GET, "page"), 'filter-by', $this->get_column_filter('ftb'));
        } else {
            $filter = sprintf('<a href="?page=%s&action=%s&ftb=%s%s">' . __('Filter') . '</a>', filter_input(INPUT_GET, "page"), 'filter-by', $item['translated_by'], $this->get_column_filter('ftb'));
        }

        $actions = [
            // 'edit' => sprintf('<a href="?page=%s&action=%s&book=%s">Edit</a>', $_REQUEST['page'], 'edit', 1/*$item['ID']*/),
            'filter' => $filter,
        ];
        return sprintf('%1$s %2$s', $by, $this->row_actions($actions));
    }

    function extra_tablenav($which) {
        //echo "Filter me this!";
        /* 	echo "<label for='bulk-action-selector-" . esc_attr( $which ) . "' class='screen-reader-text'>" . __( 'Select bulk action' ) . "</label>";
          echo "<select name='filter' id='bulk-action-selector-" . esc_attr( $which ) . "'>\n";
          echo "<option value='-1' selected='selected'>" . __( 'Bulk Actions' ) . "</option>\n";

          foreach ( $this->_actions as $name => $title ) {
          $class = 'edit' == $name ? ' class="hide-if-no-js"' : '';

          echo "\t<option value='$name'$class>$title</option>\n";
          }

          echo "</select>\n";

          submit_button( __( 'Filter' ), 'action', false, false, array( 'id' => "dofilter" ) );
          echo "\n"; */
    }

    function get_bulk_actions() {
        $actions = array(
            'delete' => 'Delete'
        );
        return $actions;
    }

    function prepare_items() {
        global $my_transposh_plugin;
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $orderby = (!empty(filter_input(INPUT_GET, 'orderby', FILTER_SANITIZE_SPECIAL_CHARS)) ) ? filter_input(INPUT_GET, 'orderby', FILTER_SANITIZE_SPECIAL_CHARS) : 'timestamp';
        $order = (!empty(filter_input(INPUT_GET, 'order', FILTER_SANITIZE_SPECIAL_CHARS)) ) ? filter_input(INPUT_GET, 'order', FILTER_SANITIZE_SPECIAL_CHARS) : 'desc';
        // FIX CVE-2022-25811
        if (!in_array($orderby, ['timestamp', 'lang', 'original', 'translated', 'translated_by'])) {
            $orderby = "timestamp";
        }
        if (!in_array($order, ['asc', 'desc'])) {
            $order = "desc";
        }

        //$per_page = 5;
        $user = get_current_user_id();
        $screen = get_current_screen();
        $option = $screen->get_option('per_page', 'option');

        $per_page = get_user_meta($user, $option, true);

        if (empty($per_page) || $per_page < 1) {

            $per_page = $screen->get_option('per_page', 'default');
        }


        $current_page = $this->get_pagenum();
        $limit = ($current_page - 1) * $per_page;
        $total_items = $my_transposh_plugin->database->get_filtered_translations_count('', 'null', $this->filter);
        $this->set_pagination_args(array(
            'total_items' => $total_items, //WE have to calculate the total number of items
            'per_page' => $per_page //WE have to determine how many items to show on a page
        ));
        //$this->items = $this->found_data;
        global $my_transposh_plugin;
        $this->items = $my_transposh_plugin->database->get_filtered_translations('', 'null', "$limit, $per_page", $orderby, $order, $this->filter);
    }

    function render_table() {
        echo '</pre><div class="wrap"><h2>' . __('Translations', TRANSPOSH_TEXT_DOMAIN) . '</h2>';
        $this->prepare_items();
        if ($this->filter) {
            $current_url = set_url_scheme('http://' . transposh_utils::get_clean_server_var('HTTP_HOST') . transposh_utils::get_clean_server_var('REQUEST_URI'));
            echo (sprintf("<a href='%s'>%s</a></br>", esc_url(remove_query_arg(['action', 'ftb', 'fts', 'fl', 'paged'], $current_url)), __('Remove all filters')));
        }

        //'filter' => sprintf('<a href="?page=%s&action=%s&ftb=%s">Filter</a>', filter_input(INPUT_GET, "page"), 'filter-by', $item['translated_by']),
        if (filter_input(INPUT_GET, 'fts', FILTER_DEFAULT, FILTER_NULL_ON_FAILURE) !== false) {
            echo (sprintf('<a href="?page=%s&action=%s%s">' . __('Show all translations',TRANSPOSH_TEXT_DOMAIN) . '</a>', filter_input(INPUT_GET, "page"), 'filter-by', $this->get_column_filter('fts')));
        } else {
            echo (sprintf('<a href="?page=%s&action=%s&fts=%s%s">' . __('Show only human translations',TRANSPOSH_TEXT_DOMAIN) . '</a>', filter_input(INPUT_GET, "page"), 'filter-by', "0", $this->get_column_filter('fts')));
        }
        echo '
        <form method="post">
            <input type="hidden" name="page" value="tp_editor">';

        $this->search_box(__('search', TRANSPOSH_TEXT_DOMAIN), 'search_id');
        $this->display();
        echo '</form></div>';
    }

    /**
     * 
     * @global transposh_plugin $my_transposh_plugin 
     */
    function perform_actions() {
        global $my_transposh_plugin;
        // echo "Actioning";
        // echo $this->current_action();
        if ($this->current_action() === 'delete') {
            if (filter_input(INPUT_GET, 'key', FILTER_DEFAULT, FILTER_NULL_ON_FAILURE)) {
                list($timestamp, $lang, $original) = explode(',', base64_decode(filter_input(INPUT_GET, 'key')), 3);
                // echo "($timestamp,$lang,$original)";
                $return = $my_transposh_plugin->database->del_translation_history($original, $lang, $timestamp);
                echo json_encode($return);
                exit();
            }
            $keys = filter_input(INPUT_POST, 'keys', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            if ($keys) {
                foreach ($keys as $key) {
                    tp_logger($key);
                    list($timestamp, $lang, $original) = explode(',', base64_decode($key), 3);
                    $my_transposh_plugin->database->del_translation_history($original, $lang, $timestamp);
                }
            }
        }
        // fts - filter source, fl - filter language, ftb - filter by
        $this->filter = "";
        if ($this->current_action() === 'filter-by') {
            if (filter_input(INPUT_GET, 'fts', FILTER_DEFAULT, FILTER_NULL_ON_FAILURE) !== false) {
                $this->filter .= "source = '" . esc_sql(filter_input(INPUT_GET, 'fts')) . "'";
            }
            if (filter_input(INPUT_GET, 'fl', FILTER_DEFAULT, FILTER_NULL_ON_FAILURE)) {
                if ($this->filter) {
                    $this->filter .= " AND ";
                }
                $this->filter .= "lang = '" . esc_sql(filter_input(INPUT_GET, 'fl')) . "'";
            }
            if (filter_input(INPUT_GET, 'ftb', FILTER_DEFAULT, FILTER_NULL_ON_FAILURE)) {
                if ($this->filter) {
                    $this->filter .= " AND ";
                }
                $this->filter .= "translated_by = '" . esc_sql(filter_input(INPUT_GET, 'ftb')) . "'";
            }

            tp_logger($this->filter);
        }
        $s = htmlspecialchars(filter_input(INPUT_POST, 's', FILTER_DEFAULT, FILTER_NULL_ON_FAILURE));
        if ($s) {
            if ($this->filter) {
                $this->filter .= " AND ";
            }
            $this->filter .= "(translated LIKE '%" . esc_sql($s) . "%' or original LIKE '%" . esc_sql($s) . "%')";
        }

        tp_logger($this->current_action());
    }

}
