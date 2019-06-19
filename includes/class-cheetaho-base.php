<?php

/**
 *
 * The file that defines the ChetahO base class
 *
 *
 * @link       https://cheetaho.com
 * @since      1.4.3.4
 * @package    CheetahO
 * @subpackage CheetahO/includes
 * @author     CheetahO <support@cheetaho.com>
 */
abstract class Cheetaho_Base {

    /**
     * The version of this plugin.
     *
     * @since   1.4.3.4
     * @access   private
     * @var      string $version The current version of this plugin.
     */

    protected $version;

    /**
     * For main CheetahO data object
     *
     * @var object $cheetaho
     */
    protected $cheetaho;

    /**
     * Settings data.
     *
     * @since    1.4.3.4
     * @access   private
     * @var      array $cheetaho_settings
     */
    protected $cheetaho_settings;

    /**
     * Image meta db table instance.
     *
     * @since    1.4.3.4
     * @access   private
     */
    protected $image_meta;

    /**
     * Initialize the class and set its properties.
     *
     * @since   1.4.3.4
     *
     * @param object $cheetaho
     */

    public function __construct( $cheetaho ) {
        $this->version           = $cheetaho->get_version();
        $this->cheetaho_settings = $cheetaho->get_cheetaho_settings();
        $this->cheetaho          = $cheetaho;
        $this->image_meta        = new CheetahO_Image_Metadata( new CheetahO_DB() );
    }

    /**
     * Call this method to get singleton
     */
    public static function instance($cheetaho) {
        static $instance = false;

        if ( $instance === false ) {
            // Late static binding (PHP 5.3+)
            $instance = new static($cheetaho);
        }

        return $instance;
    }
}