<?php

/**
 * The other media optimization page functionality of the plugin.
 *
 * @link       https://cheetaho.com
 * @since      1.5
 * @package    CheetahO
 * @subpackage CheetahO/admin
 * @author     CheetahO <support@cheetaho.com>
 */
class CheetahO_Other_Media {

    // Pagination .
    protected $items_per_page = 20;
    protected $current_page = 1;
    protected $total_items = 0;
    protected $order = 'desc';
    protected $order_by = 'id';
    protected $search =  false;

    /**
     * The version of this plugin.
     *
     * @since    1.5
     * @access   private
     * @var      string $version The current version of this plugin.
     */

    private $version;

    /**
     * For main cheetaho data object
     *
     * @var object $cheetaho
     */
    private $cheetaho;

    /**
     * Settings data.
     *
     * @since    1.5
     * @access   private
     * @var      array $cheetaho_settings
     */
    private $cheetaho_settings;

    /**
     * @since    1.5
     * @var CheetahO_Optimizer
     */
    private $cheetaho_optimizer;

    /**
     * Image meta db table instance.
     *
     * @since    1.5
     * @access   private
     */
    private $image_meta;

    /**
     * Initialize the class and set its properties.
     *
     * @since   1.5
     * @param   string $plugin_name The name of this plugin.
     * @param   string $version The version of this plugin.
     * @param   array  $settings
     */
    public function __construct( $cheetaho ) {
        $this->version           = $cheetaho->get_version();
        $this->cheetaho_settings = $cheetaho->get_cheetaho_settings();
        $this->cheetaho          = $cheetaho;
        $this->current_page = isset($_GET['paged']) ? (int)$_GET['paged'] : 1;
        $this->cheetaho_optimizer = new CheetahO_Optimizer( $this->cheetaho );
        $this->image_meta        = new CheetahO_Image_Metadata( new CheetahO_DB() );

        $cheetaho->get_loader()->add_action( 'admin_menu', $this, 'register_other_media_page' );
        $cheetaho->get_loader()->add_action('wp_ajax_cheetaho_image_action', $this, 'process_action');
        $cheetaho->get_loader()->add_action('admin_enqueue_scripts', $this, 'enqueue_other_media_scripts');
    }

    /*
     * register new page
     */
    function register_other_media_page() {
        add_media_page(
            'CheetahO Bulk Process',
            'CheetahO Other Media',
            'edit_others_posts',
            'cheetaho-other-media',
            array( $this, 'render_cheetaho_other_media_page' )
        );
    }

    /**
     * Render settings page html
     * @since    1.5
     * @return mixed
     */
    function render_cheetaho_other_media_page() {

        global  $total_image, $post_per_page, $paged;
        $post_per_page = $this->items_per_page;
        $paged = $this->current_page;

        $images = $this->cheetaho->get_loader()->modules->cheetaho_folder->get_custom_images();
        $this->total_items = $total_image;
        $nonce = wp_create_nonce('cheetaho_custom_action');
        $this->process_url_action();

        $exclude_file_patterns = CheetahO_Helpers::convert_exclude_file_patterns_to_array($this->cheetaho_settings);

        include CHEETAHO_PLUGIN_ROOT . 'admin/views/other_media.php';
    }

    /**
     * @since    1.5
     * @param $hook
     */
    public function enqueue_other_media_scripts($hook)
    {
        if ('media_page_cheetaho-other-media' == $hook) {

            wp_enqueue_script(CHEETAHO_PLUGIN_NAME.'-other-media',
                plugin_dir_url(__FILE__).'assets/js/other-media.js',
                array('jquery'), $this->version, false);

            wp_localize_script(
                CHEETAHO_PLUGIN_NAME.'-other-media',
                'cheetaho_other_media_object',
                array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('cheetaho_custom_action')
                )
            );
        }

    }

    /**
     * @since    1.5
     * process actions
     */
    public function process_action()
    {
        $nonce = isset($_POST['_wpnonce']) ? esc_attr($_POST['_wpnonce']) : false;
        $action = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : false;
        $item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : false;

        if ($action) {
            if (!wp_verify_nonce($nonce, 'cheetaho_custom_action')) {
                die('Error. Nonce not verified. Do not call this function directly');
            }

            if ($item_id === false) {
                die('Error executing action');
            }

            switch ($action) {
                case 'optimize':
                    wp_send_json($this->cheetaho_optimizer->optimize_custom_image($item_id));

                    break;
                case 'restore':
                    wp_send_json($this->restore_custom_image($item_id));

                    break;

                case 'refresh':
                    $this->refresh_folders();

                    break;
            }
        }

        wp_send_json_error(__('Failed', 'cheetaho-image-optimizer'));
    }

    /**
     * @since    1.5
     * @param array $args
     * @return mixed
     */
    protected function get_page_url($args = array())
    {
        $defaults = array(
            'orderby' => $this->order_by,
            'order' => $this->order,
            's' => $this->search,
            'paged' => $this->current_page
        );

        $admin_url = admin_url('upload.php');
        $url =  add_query_arg('page','cheetaho-other-media', $admin_url);

        $page_args = array_filter(wp_parse_args($args, $defaults));

        return add_query_arg($page_args, $url);
    }

    /**
     * @since    1.5
     * @return string
     */
    public function getPagination()
    {
        $current = $this->current_page;
        $total = $this->total_items;
        $per_page = $this->items_per_page;

        $pages = round($total / $per_page);

        if ($pages <= 1)
            return ''; // no pages.

        $disable_first = $disable_last = $disable_prev =  $disable_next = false;
        $page_links = array();

        if ( $current == 1 ) {
            $disable_first = true;
            $disable_prev  = true;
        }
        if ( $current == 2 ) {
            $disable_first = true;
        }
        if ( $current == $pages ) {
            $disable_last = true;
            $disable_next = true;
        }
        if ( $current == $pages - 1 ) {
            $disable_last = true;
        }

        $total_pages_before = '<span class="paging-input">';
        $total_pages_after  = '</span></span>';

        $current_url = remove_query_arg( 'paged', $this->get_page_url());

        $output = '<span class="cheetaho-displaying-num">'. sprintf(__('%d Items', 'cheetaho-image-optimizer' ), $total) . '</span>';

        if ( $disable_first ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<a class='first-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                esc_url( $current_url ),
                __( 'First page' ),
                '&laquo;'
            );
        }

        if ( $disable_prev ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<a class='prev-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                esc_url( add_query_arg( 'paged', max( 1, $current - 1 ), $current_url ) ),
                __( 'Previous page' ),
                '&lsaquo;'
            );
        }

        $html_current_page = sprintf(
            "%s<span class='current-page' id='current-page-selector'>%s</span><span class='tablenav-paging-text'>",
            '<label for="current-page-selector" class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
            $current);

        $html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $pages ) );
        $page_links[]     = $total_pages_before . sprintf(
            /* translators: 1: Current page, 2: Total pages. */
                _x( '%1$s of %2$s', 'paging' ),
                $html_current_page,
                $html_total_pages
            ) . $total_pages_after;

        if ( $disable_next ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<a class='next-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                esc_url( add_query_arg( 'paged', min( $pages, $current + 1 ), $current_url ) ),
                __( 'Next page' ),
                '&rsaquo;'
            );
        }

        if ( $disable_last ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<a class='last-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                esc_url( add_query_arg( 'paged', $pages, $current_url ) ),
                __( 'Last page' ),
                '&raquo;'
            );
        }

        $output .= "\n<span class='pagination-links'>" . implode( "\n", $page_links ) . '</span>';


        return $output;
    }

    function restore_custom_image() {
        $meta_id = (int) $_POST['item_id'];
        $image = $this->image_meta->get_image_meta_by_meta_id($meta_id);
        $removed = false;

        if ( ! empty( $image ) ) {
            CheetahO_Backup::restore_single_file( $image );
            $meta = [];
            $meta['status'] = 0;
            $this->image_meta->update_optimized_custom_meta($meta_id, $meta);

            $folder = (new CheetahO_Folders(new CheetahO_DB()))->get_item_by_id($image->folder_id);

            if ($folder != null && $folder->status == 2) {
                $images = new CheetahO_Image_Metadata(new CheetahO_DB());
                $images->remove_folder_item_not_optimized($image->id);
                $removed = true;
            }

            do_action( 'cheetaho_custom_file_reset', $meta_id);
            $exclude_file_patterns = CheetahO_Helpers::convert_exclude_file_patterns_to_array($this->cheetaho_settings);

            include CHEETAHO_PLUGIN_ROOT . 'admin/views/parts/column-custom-optimize-btn.php';

            echo json_encode(
                array(
                    'success'       => true,
                    'status_txt'    => ($removed === true) ? __( 'Removed','cheetaho-image-optimizer' ) : __( 'Not optimized','cheetaho-image-optimizer' ),
                    'html'          => $html,
                    'removed'       => $removed
                )
            );
        }

        wp_die();
    }

    public function process_url_action()
    {
        $nonce = isset($_GET['_wpnonce']) ? esc_attr($_GET['_wpnonce']) : false;
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : false;

        if ($action) {
            if (!wp_verify_nonce($nonce, 'cheetaho_custom_action')) {
                die('Error. Nonce not verified. Do not call this function directly');
            }

            switch ($action) {
                case 'refresh':
                    $this->refresh_folders();

                    break;
            }
        }
    }

    public function refresh_folders()
    {
        $customFolders = $this->cheetaho->get_loader()->modules->cheetaho_folder->get_active_custom_folders();

        if (is_array($customFolders) && !empty($customFolders)) {
            foreach ($customFolders as $folder) {
                $this->cheetaho->get_loader()->modules->cheetaho_folder->save_dir($folder->path);
            }
        }

        wp_redirect( $_SERVER["HTTP_REFERER"], 302 );
        exit;
    }
}
