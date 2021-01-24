<?php
/**
 * Handle CheetahO image optimization in custom folders .
 *
 * @link       https://cheetaho.com
 * @since      1.4.5
 * @package    CheetahO
 * @subpackage CheetahO/admin
 * @author     CheetahO <support@cheetaho.com>
 */
class CheetahO_Folder
{
    /**
     * The version of this plugin.
     *
     * @since    1.4.5
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;


    /**
     * Settings data.
     *
     * @since    1.4.5
     * @access   private
     * @var      array $cheetaho_settings
     */
    private $cheetaho_settings;

    /**
     * @since    1.4.5
     * @access   private
     * @var string
     */
    private $image_size = 'full';

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.4.5
     */
    public function __construct($cheetaho)
    {
        $this->version = $cheetaho->get_version();
        $this->cheetaho_settings = $cheetaho->get_cheetaho_settings();

        $cheetaho->get_loader()->add_action('admin_enqueue_scripts', $this, 'enqueue_folder_scripts');
        $cheetaho->get_loader()->add_action('wp_ajax_cheetaho_browse_folders', $this, 'browse_folders');
        $cheetaho->get_loader()->add_action('wp_ajax_cheetaho_remove_folders', $this, 'remove_folders');
    }

    /**
     * Get folders list
     */
    function browse_folders()
    {
        $root = $this->get_root_path();
        $dir = (isset($_POST['dir']) && !is_array($_POST['dir'])) ? ltrim(sanitize_text_field(wp_unslash($_POST['dir'])), '/') : null; // Input var ok.
        $postDir = strlen($dir) >= 1 ? path_join($root, $dir) : $root . $dir;
        $postDir = realpath(rawurldecode($postDir));

        // If the final path doesn't contains the root path, bail out.
        if (!$root || false === $postDir || 0 !== strpos($postDir, $root)) {
            wp_send_json_error(__('Unauthorized', 'cheetaho-image-optimizer'));
        }

        $supportedImage = CheetahO_Helpers::$allowed_extensions;

        if (file_exists($postDir) && is_dir($postDir)) {
            $files = scandir($postDir);
            // Exclude hidden files.

            if (!empty($files)) {
                $files = preg_grep('/^([^.])/', $files);
            }

            $return_dir = substr($postDir, strlen($root));

            natcasesort($files);

            if (count($files) !== 0 && !$this->skip_dir($postDir)) {
                $tree = array();

                foreach ($files as $file) {

                    $html_rel = htmlentities(ltrim(path_join($return_dir, $file), '/'));
                    $html_name = htmlentities($file);
                    $ext = preg_replace('/^.*\./', '', $file);

                    $file_path = path_join($postDir, $file);
                    if (!file_exists($file_path) || '.' === $file || '..' === $file) {
                        continue;
                    }

                    if (!is_dir($file_path)){
                        continue;
                    }

                    // Skip unsupported files and files that are already in the media library.
                    if (!is_dir($file_path) && (!in_array($ext, $supportedImage, true) || $this->is_media_library_file($file_path))) {
                        continue;
                    }

                    $skip_path = $this->skip_dir($file_path);

                    $tree[] = array(
                        'title' => $html_name,
                        'key' => $html_rel,
                        'folder' => is_dir($file_path),
                        'lazy' => !$skip_path,
                        'checkbox' => true,
                        'unselectable' => $skip_path, // Skip Uploads folder - Media Files.
                    );
                }

                wp_send_json($tree);
            }
        }

        die();

    }

    /**
     * Check if the image file is media library file
     *
     * @param string $file_path File path.
     *
     * @return bool
     */
    private function is_media_library_file($file_path)
    {
        $upload_dir = wp_upload_dir();
        $upload_path = $upload_dir['path'];

        // Get the base path of file.
        $base_dir = dirname($file_path);

        if ($base_dir === $upload_path) {
            return true;
        }

        return false;
    }

    /**
     * Excludes the Media Upload Directory
     *
     * @param string $path Path.
     *
     * @return bool
     */
    public function skip_dir($path)
    {
        // Admin directory path.
        $admin_dir = $this->get_admin_path();

        // Includes directory path.
        $includes_dir = ABSPATH . WPINC;

        // Upload directory.
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'];

        $skip = false;

        // Skip sites folder for multisite.
        if (false !== strpos($path, $base_dir . '/sites')) {
            $skip = true;
        } elseif (false !== strpos($path, $base_dir)) {
            // If matches the current upload path contains one of the year sub folders of the media library.
            $path_arr = explode('/', str_replace($base_dir . '/', '', $path));

            if (count($path_arr) >= 1
                && is_numeric($path_arr[0]) && $path_arr[0] > 1900 && $path_arr[0] < 2100 // Contains the year sub folder.
                && (1 === count($path_arr) // If there is another sub folder then it's the month sub folder.
                    || (is_numeric($path_arr[1]) && $path_arr[1] > 0 && $path_arr[1] < 13))
            ) {
                $skip = true;
            }
        } elseif ((false !== strpos($path, $admin_dir)) || false !== strpos($path, $includes_dir)) {
            $skip = true;
        }

        if (false !== strpos($path, 'CheetahoBackups')) {
            $skip = true;
        }

        if (false !== strpos($path, 'ShortpixelBackups')) {
            $skip = true;
        }

        // Can be used to skip/include folders matching a specific directory path.
        $skip = apply_filters('cheetaho_skip_folder', $skip, $path);

        return $skip;
    }

    private function get_admin_path()
    {
        // Replace the site base URL with the absolute path to its installation directory.
        $admin_path = rtrim(str_replace(get_bloginfo('url') . '/', ABSPATH, get_admin_url()), '/');

        // Make it filterable, so other plugins can hook into it.
        $admin_path = apply_filters('cheetaho_get_admin_path', $admin_path);

        return $admin_path;
    }

    /**
     * Get root path of the installation.
     *
     * @return string Root path.
     */
    public function get_root_path()
    {
        // If main site.
        if (is_main_site()) {
            $content_path = explode('/', WP_CONTENT_DIR);
            // Get root path and explod.
            $root_path = explode('/', get_home_path());
            // Find the length of the shortest one.
            $end = min(count($content_path), count($root_path));
            $i = 0;
            $common_path = array();
            // Add the component if they are the same in both paths.
            while ($content_path[$i] === $root_path[$i] && $i < $end) {
                $common_path[] = $content_path[$i];
                $i++;
            }

            return implode('/', $common_path);
        }

        $up = wp_upload_dir();

        return $up['basedir'];
    }

    public function enqueue_folder_scripts($hook)
    {
        if ('settings_page_cheetaho-image-optimizer' == $hook) {
            wp_enqueue_script(CHEETAHO_PLUGIN_NAME . '-file-tree',
                plugin_dir_url(__FILE__) . 'assets/js/file-tree.min.js',
                array('jquery'), $this->version, false);

            wp_enqueue_script(CHEETAHO_PLUGIN_NAME . '-settings',
                plugin_dir_url(__FILE__) . 'assets/js/cheetaho-settings.js',
                array('jquery'), $this->version, false);

            wp_localize_script(
                CHEETAHO_PLUGIN_NAME . '-settings',
                'cheetaho_folder_object',
                array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('cheetaho_feedback_uninstall_nonce'),
                    'confirm_msg' => __('Are you sure you want stop monitoring this folder?',
                        'cheetaho-image-optimizer'),
                )
            );
        }
    }

    public function save_dir($dir = null)
    {
        $folder = new CheetahO_Folders(new CheetahO_DB());
        $queryParams = array();
        $queryParams['filter'] = array('path' => $dir);
        $queryParams['filter_format'] = array('%s');
        $queryParams['params'] = array(
            'status' => CheetahO_Folders::STATUS_NOT_OPTIMIZED
        );
        $queryParams['params_format'] = array(
            'status' => '%d'
        );

        $folder->add_or_update_item($queryParams);
        $folder_data = $folder->get_item($dir);

        $this->get_image_list($dir, $folder_data->id);
        $files_count = (new CheetahO_Image_Metadata(new CheetahO_DB()))->get_image_count_meta_by_folder_id($folder_data->id);

        $queryParams = array();
        $queryParams['filter'] = array('path' => $dir);
        $queryParams['filter_format'] = array('%s');
        $queryParams['params'] = array(
            'status' => CheetahO_Folders::STATUS_NOT_OPTIMIZED,
            'file_count' => $files_count
        );
        $queryParams['params_format'] = array(
            'status' => '%d',
            'file_count' => '%d'
        );
        $folder->add_or_update_item($queryParams);
    }

    public function get_active_custom_folders()
    {
        $folder = new CheetahO_Folders(new CheetahO_DB());

        $list = $folder->get_active_items();

        return $list;
    }

    /**
     *
     */
    public function remove_folders()
    {
        if (isset($_POST['id']) && is_numeric($_POST['id']) && (int)$_POST['id'] > 0) {
            $folder = new CheetahO_Folders(new CheetahO_DB());
            $queryParams = array();
            $queryParams['filter'] = array('id' => (int)$_POST['id']);
            $queryParams['filter_format'] = array('%d');
            $queryParams['params'] = array(
                'status' => CheetahO_Folders::STATUS_NOT_REMOVED
            );
            $queryParams['params_format'] = array(
                'status' => '%d'
            );

            $folder->update_item($queryParams);

            $images = new CheetahO_Image_Metadata(new CheetahO_DB());
            $images->remove_folder_not_optimized((int)$_POST['id']);

            wp_send_json(['error' => 'false']);
        }

        wp_send_json_error(__('Failed', 'cheetaho-image-optimizer'));
    }

    private function get_image_list($paths, $folder_id = null)
    {
        // Error with directory tree.
        if (empty($paths)) {
            wp_send_json_error(
                array(
                    'message' => __('There was a problem getting the selected directories', 'cheetaho-image-optimizer'),
                )
            );
        }

        $count = 0;
        $imagesCount = 0;
        $images = array();
        $values = array();
        $timestamp = gmdate('Y-m-d H:i:s');

        // Temporary increase the limit.
        if (function_exists('wp_raise_memory_limit')) {
            wp_raise_memory_limit('image');
        }

        if (!is_array($paths)) {
            $paths = array($paths);
        }

        // Iterate over all the selected items (can be either an image or directory).
        foreach ($paths as $path) {
            // Prevent phar deserialization vulnerability.
            $path = trim($path);
            if (stripos($path, 'phar://') !== false) {
                continue;
            }

            /**
             * Path is an image.
             */
            if (!is_dir($path) && !$this->is_media_library_file($path) && !strpos($path, '.bak')) {

                if (!$this->can_add_file($path)) {
                    continue;
                }

                // Image already added. Skip.
                if (in_array($path, $images, true)) {
                    continue;
                }

                $path_to_save = str_replace(ABSPATH, '', $path);
                $images[] = md5($path_to_save . $this->image_size);
                $images[] = $folder_id;
                $images[] = $path_to_save;
                $images[] = @filesize($path);
                $images[] = $timestamp;
                $images[] = 'full';
                $values[] = '(%s, %d, %s, %d, %s, %s)';
                $count++;
                $imagesCount++;

                // Store the images in db at an interval of 5k.
                if ($count >= 5000) {
                    $count = 0;
                    $this->store_images($values, $images);
                    $images = $values = array();
                }

                continue;
            }

            /**
             * Path is a directory.
             */
            $base_dir = realpath(rawurldecode($path));

            if (!$base_dir) {
                wp_send_json_error(
                    array(
                        'message' => __('Unauthorized', 'cheetaho-image-optimizer'),
                    )
                );
            }

            // Directory Iterator, Exclude . and ..
            $filtered_dir = new Cheetaho_Iterator(new RecursiveDirectoryIterator($base_dir));

            // File Iterator.
            $iterator = new RecursiveIteratorIterator($filtered_dir, RecursiveIteratorIterator::CHILD_FIRST);

            foreach ($iterator as $file) {

                if (!$this->can_add_file($file->getPathName())) {
                    continue;
                }

                // Not a file. Skip.
                if (!$file->isFile()) {
                    continue;
                }

                $file_path = $file->getPathname();

                if (!$this->is_media_library_file($file_path)) {
                    $path_to_save = str_replace(ABSPATH, '', $file_path);
                    $images[] = md5($path_to_save . $this->image_size);
                    $images[] = $folder_id;
                    $images[] = $path_to_save;
                    $images[] = $file->getSize();
                    $images[] = $timestamp;
                    $images[] = 'full';
                    $values[] = '(%s, %d, %s, %d, %s, %s)';
                    $count++;
                    $imagesCount++;
                }

                // Store the images in db at an interval of 5k.
                if ($count >= 5000) {
                    $count = 0;
                    $this->store_images($values, $images);
                    $images = $values = array();
                }
            }
        }

        // Update rest of the images.
        if (!empty($images) && $count > 0) {
            $this->store_images($values, $images);
        }

        return $imagesCount;
    }

    public function can_add_file($path)
    {
        if (CheetahO_Helpers::is_processable_path($path,
                CheetahO_Helpers::convert_exclude_file_patterns_to_array($this->cheetaho_settings)) === false) {
            return false;
        }

        return true;
    }


    /**
     * Write to the database.
     *
     * @param array $values Values for query build.
     * @param array $images Array of images.
     * @since 2.8.1
     *
     */
    private function store_images($values, $images)
    {
        if (empty($images) || empty($values)) {
            return false;
        }

        $image_meta = new CheetahO_Image_Metadata(new CheetahO_DB());
        $values = implode(',', $values);

        $image_meta->store_folder_images($values, $images);
    }

    public function get_custom_images()
    {
        $folder = new CheetahO_Image_Metadata(new CheetahO_DB());

        return $folder->get_custom_images();

    }


}
