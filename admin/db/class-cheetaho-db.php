<?php

/**
 * Work with database tables.
 *
 * @link       https://cheetaho.com
 * @since      1.4.3
 * @package    CheetahO
 * @subpackage CheetahO/admin
 * @author     CheetahO <support@cheetaho.com>
 */
class CheetahO_DB {

	protected $prefix;
	protected $default_show_errors;

	public function __construct( $prefix = null ) {
		$this->prefix = $prefix;
	}

	public static function create_or_update_db_schema( $table_definitions ) {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$res = array();

		foreach ( $table_definitions as $item ) {
			array_merge( $res, dbDelta( $item ) );
		}

		return $res;
	}

	public static function check_custom_tables() {
		global $wpdb;

		$cheetaho_db_version = get_option( "cheetaho_db_version" );

		if ( $cheetaho_db_version != CHEETAHO_DB_VERSION ) {
			if ( function_exists( "is_multisite" ) && is_multisite() ) {
				$sites = function_exists( "get_sites" ) ? get_sites() : wp_get_sites();

				foreach ( $sites as $site ) {
					if ( ! is_array( $site ) ) {
						$site = (array) $site;
					}

					$prefix = $wpdb->get_blog_prefix( $site['blog_id'] );

					$spMetaDao = new CheetahO_Image_Metadata( new self( $prefix ) );
					$spMetaDao->create_or_update_tables();

                    $spMetaDao = new CheetahO_Folders( new self( $prefix ) );
                    $spMetaDao->create_or_update_tables();
				}

			} else {
				$spMetaDao = new CheetahO_Image_Metadata( new self() );
				$spMetaDao->create_or_update_tables();

				$spMetaDao = new CheetahO_Folders( new self() );
				$spMetaDao->create_or_update_tables();
			}

			update_option( "cheetaho_db_version", CHEETAHO_DB_VERSION );
		}
	}

	public static function check_db_update() {

		self::check_custom_tables();

	}

	public function get_charset_collate() {
		global $wpdb;

		return $wpdb->get_charset_collate();
	}

	public function get_prefix() {
		global $wpdb;

		return $this->prefix ? $this->prefix : $wpdb->prefix;
	}

	public function get_db_name() {
		global $wpdb;

		return $wpdb->dbname;
	}

	public function query( $sql, $params = false, $get_row = false ) {
		global $wpdb;

		if ( $params ) {
			$sql = $wpdb->prepare( $sql, $params );
		}

		if ($get_row == false) {
			return $wpdb->get_results( $sql );
		} else {
			return $wpdb->get_row($sql);
		}
	}

	public function insert( $table, $params, $format = null ) {
		global $wpdb;
		//$wpdb->show_errors();
		$wpdb->insert( $table, $params, $format );
		//$wpdb->print_error();
		return $wpdb->insert_id;
	}

	public function update( $table, $params, $where, $format = null, $where_format = null ) {
		global $wpdb;

		$updated = $wpdb->update( $table, $params, $where, $format, $where_format );
     
		return $updated;

	}

	public function delete_rows ($table, $where, $where_format = null ) {
		global $wpdb;

		$wpdb->delete( $table, $where, $where_format = null );
	}

	public function prepare( $query, $args ) {
		global $wpdb;

		return $wpdb->prepare( $query, $args );
	}

	public function hide_errors() {
		global $wpdb;

		$this->default_show_errors = $wpdb->show_errors;
		$wpdb->show_errors         = false;
	}

	public function restore_errors() {
		global $wpdb;

		$wpdb->show_errors = $this->default_show_errors;
	}
}
