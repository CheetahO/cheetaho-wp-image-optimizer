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
class CheetahO_Image_Metadata {

    const STATUS_FAILED = 'failed';
    const STATUS_SUCCESS = 'success';
    const STATUS_HIDDEN = 'hidden';
    const GALLERY_MEDIA = 'media';
    const GALLERY_CUSTOM = 'custom';

	private $db;

	private $table_name = 'cheetaho_image_metadata';

	/**
	 *
	 * @since    1.4.3
	 *
	 */
	public function __construct( $db ) {
		$this->db = $db;
	}

	/**
	 *
	 * @since    1.4.3
	 *
	 * Get table sql with:
	 *
	 * id: unique for each record/image,
	 * attachment_id: the unique id within the media library, nextgen,
	 * gallery: media, nextgen, custom
	 * image_size_name: size of the image,
	 * width: image original width,
	 * height: image original height,
	 * path: filename of the image, potentially replaced with ABSPATH or WP_CONTENT_DIR,
	 * converted: filename of the image before conversion,
	 * results: human-readable savings message,
	 * image_size: optimized size of the image,
	 * original_size: original size of the image,
	 * saved_percent: count in percents how mutch it
	 * backup_path: hash where the image is stored on the API servers,
	 * level: the optimization level used on the image,
	 * status: success, failed etc.
	 * updates: how many times an image has been optimized,
	 * updated: when the image was last optimized,
	 * log: tracelog from the last optimization if debugging was enabled.
	 */
	public static function get_metadata_table_sql( $table_name, $db_collation ) {

		$sql = "CREATE TABLE {$table_name} (
			id int(10) unsigned NOT NULL AUTO_INCREMENT,
			attachment_id bigint(20) unsigned,
			folder_id int(10) NULL DEFAULT NULL,
			gallery varchar(10),
			identifier varchar(190),
			is_retina tinyint(1),
			image_size_name varchar(75),
			width int(10),
			height int(10),
			path text NOT NULL,
			converted text NOT NULL,
			results varchar(75) NOT NULL,
			image_size int(10) unsigned,
			original_size int(10) unsigned,
			backup_path varchar(255),
			level varchar(25),
			status varchar(25) NOT NULL DEFAULT 0,
			updates int(5) unsigned,
			updated timestamp DEFAULT '1971-01-01 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
			log text,
			PRIMARY KEY id (id),
			UNIQUE KEY identifier (identifier),
			KEY status (status),
			KEY is_retina (is_retina),
			KEY attachment_id (attachment_id),
			KEY image_size_name (image_size_name),
			KEY folder_id (folder_id)
		) $db_collation;";

		return $sql;

	}

	/**
	 * Create or update table
	 * @since    1.4.3
	 */
	public function create_or_update_tables() {
		$this->db->create_or_update_db_schema( array(
			self::get_metadata_table_sql( $this->db->get_prefix() . $this->table_name, $this->db->get_charset_collate() )
            ) );
	}

	/**
	 * Add new image meta record
	 *
	 * @param $image_id
	 * @param $meta
	 *
	 * @since 1.4.3
	 *
	 * @return mixed
	 */
	public function add_image( $image_id, $meta ) {

		$identifier = $this->get_identifier( $image_id, $meta );
		$id         = $this->db->insert( $this->db->get_prefix() . $this->table_name,
			array( 'is_retina'       => $meta['is_retina'],
			       'identifier'      => $identifier,
			       'width'           => $meta['width'],
			       'height'          => $meta['height'],
			       'updates'         => '0',
			       'level'           => $meta['type'],
			       'attachment_id'   => $image_id,
			       'gallery'         => 'media',
			       'image_size'      => $meta['new_size'],
			       'path'            => $meta['path'],
			       'converted'       => 0,
			       'results'         => json_encode( $meta['results'] ),
			       'image_size_name' => $meta['image_size_name'],
			       'original_size'   => $meta['original_images_size'],
			       'backup_path'     => $meta['backup_path'],
			       'status'          => $meta['status'],
			       'log'             => ''
			),
			array( 'is_retina'       => '%d',
			       'identifier'      => '%s',
			       'width'           => '%d',
			       'height'          => '%d',
			       'updates'         => '%d',
			       'level'           => '%s',
			       'attachment_id'   => '%d',
			       'gallery'         => '%s',
			       'image_size'      => '%d',
			       'path'            => '%s',
			       'converted'       => '%d',
			       'results'         => '%s',
			       'image_size_name' => '%s',
			       'original_size'   => '%d',
			       'backup_path'     => '%s',
			       'status'          => '%s',
			       'log'             => '%s'
			) );

		return $id;
	}

	/**
	 * Image identifier from metadata
	 *
	 * @param $image_id
	 * @param $meta
	 *
	 * @since 1.4.3
	 *
	 * @return mixed
	 */
	public function get_identifier( $image_id, $meta ) {

		$path = str_replace( ABSPATH, '', $meta['path'] );

		return md5( $image_id . $path. $meta['image_size_name'] );
	}

	/**
	 * Image meta update
	 *
	 * @param $image_id
	 * @param $meta
	 *
	 * @since 1.4.3
	 *
	 * @return mixed
	 */
	public function update_image( $image_id, $meta ) {

		$id = $this->db->update( $this->db->get_prefix() . $this->table_name,
			array( 'updates'         => $meta['updates'],
			       'width'           => $meta['width'],
			       'height'          => $meta['height'],
			       'image_size'      => $meta['new_size'],
			       'path'            => $meta['path'],
			       'converted'       => 0,
			       'results'         => json_encode( $meta['results'] ),
			       'image_size_name' => $meta['image_size_name'],
			       'original_size'   => $meta['original_images_size'],
			       'backup_path'     => $meta['backup_path'],
			       'status'          => $meta['status'],
			       'log'             => json_encode( $meta['log'] )
			),
			array( 'attachment_id' => $image_id, 'identifier' => $this->get_identifier( $image_id, $meta ) ),
			array( 'updates'         => '%d',
			       'width'           => '%d',
			       'height'          => '%d',
			       'image_size'      => '%d',
			       'path'            => '%s',
			       'converted'       => '%d',
			       'results'         => '%s',
			       'image_size_name' => '%s',
			       'original_size'   => '%d',
			       'backup_path'     => '%s',
			       'status'          => '%s',
			       'log'             => '%s'
			),
			array( '%d', '%s' ) );

		return $id;
	}

	/**
	 * Add or update item by image identifier
	 *
	 * @param $image_id
	 * @param $meta
	 *
	 * @since 1.4.3
	 *
	 * @return mixed
	 */
	public function add_or_update_image_meta( $image_id, $meta ) {
		$image = $this->get_item( $image_id, $this->get_identifier( $image_id, $meta ) );

		if ( $image == null ) {
			return $this->add_image( $image_id, $meta );
		}

		$meta['updates'] = $image->updates + 1;

		return $this->update_image( $image_id, $meta );
	}

	/**
	 * Get item by attachment id and image identifier
	 *
	 * @param $image_id
	 * @param $identifier
	 *
	 * @since 1.4.3
	 *
	 * @return mixed
	 */
	public function get_item( $image_id, $identifier ) {
		$sql = "SELECT * FROM " . $this->db->get_prefix() . $this->table_name . "  WHERE attachment_id = '%d' AND identifier = '%s'";

		return $this->db->query( $sql, array( $image_id, $identifier ), true );
	}

    /**
     * Get item by attachment id and image size
     *
     * @param $image_id
     * @param $identifier
     *
     * @since 1.4.3
     *
     * @return mixed
     */
    public function get_item_by_size( $image_id, $size ) {
        $sql = "SELECT * FROM " . $this->db->get_prefix() . $this->table_name . "  WHERE attachment_id = '%d' AND image_size_name = '%s'";

        return $this->db->query( $sql, array( $image_id, $size ), true );
    }

	/**
	 * Get item stats
	 *
	 * @param $image_id
	 * @param $identifier
	 *
	 * @since 1.4.3
	 *
	 * @return mixed
	 */
	public function get_stats_data() {
		$sql = "SELECT count(id) as count, sum(original_size) as total_original_size, sum(image_size) as total_image_size  FROM " . $this->db->get_prefix() . $this->table_name ;

		return $this->db->query( $sql, array(  ), true );
	}

	/**
	 * Get items by attachment id
	 *
	 * @param $image_id
	 *
	 * @since 1.4.3
	 *
	 * @return mixed
	 */
	public function get_items( $image_id ) {
		$sql = "SELECT * FROM " . $this->db->get_prefix() . $this->table_name . "  WHERE attachment_id = '%d'";

		return $this->db->query( $sql, array( $image_id ) );
	}

	/**
	 * Get item by attachment id and image identifier
	 *
	 * @param $image_id
	 * @since 1.4.3
	 *
	 * @return mixed
	 */
	public function get_image_stats( $image_id ) {
		$items = $this->get_items ( $image_id );

		return $this->format_stats_data($items);
	}

	/**
	 * Delete item by attachment id
	 *
	 * @param $image_id
	 * @since 1.4.3
	 *
	 * @return mixed
	 */
	public function delete_image_meta ($image_id) {
		$this->db->delete_rows(  $this->db->get_prefix() . $this->table_name,
			array( 'attachment_id' => $image_id ),
			array( 'attachment_id' => '%d' )
		);
	}


	/**
	 * Format stats data
	 *
	 * @param array $items
	 * @since 1.4.3
	 * @return array
	 */
	protected function format_stats_data ($items) {
		$data = array();

		if ( $items != null ) {
			$retina_images        = array();
			$thumbs_images        = array();
			$image_size           = 0;
			$original_images_size = 0;

			foreach ( $items as $item ) {
				if ( $item->is_retina == 1 ) {
					$retina_images[] = $item;
				}

				if ( $item->is_retina == 0 && $item->image_size_name != 'full' ) {
					$thumbs_images[] = $item;
				}

				$image_size           += $item->image_size;
				$original_images_size += $item->original_size;
			}

			$saved_bytes  = $original_images_size - $image_size;
			$savings_perc = ( $saved_bytes > 0 ) ? $saved_bytes / (int) $original_images_size * 100 : 0;

			$data = array(
				'attachment_id'        => $item->attachment_id,
				'level'                => $item->level,
				'image_size'           => $image_size,
				'original_images_size' => $original_images_size,
				'retina_images_count'  => count( $retina_images ),
				'thumbs_images_count'  => count( $thumbs_images ),
				'savings_percentage'   => round( $savings_perc,2 ),
                'status'               => $item->status,
                'results'              => json_decode($item->results),
			);
		}

		return $data;
	}


	public function store_folder_images($values, $images)
    {
        // Replace with image path and respective parameters.
        $sql = "INSERT INTO `" . $this->db->get_prefix() . $this->table_name . "` (identifier, folder_id, path, original_size, updated, image_size_name) VALUES $values ON DUPLICATE KEY UPDATE image_size = IF( original_size < VALUES(original_size), NULL, image_size )";
        $query = $this->db->prepare( $sql, $images );
        $data = $this->db->query( $query ); // Db call ok; no-cache ok.

        return $data;
    }

    public function get_custom_images()
    {
        global $wpdb, $paged, $max_num_pages, $total_image, $post_per_page;

        if ((int)$post_per_page === 0) {
            $post_per_page = 20;
        }

        if ((int)$paged === 0) {
            $paged = 1;
        }

        $offset = ($paged - 1) * $post_per_page;

        $sql = "
        SELECT  SQL_CALC_FOUND_ROWS chmd.*
        FROM ".$this->db->get_prefix() . $this->table_name." chmd
        INNER JOIN  {$this->db->get_prefix()}cheetaho_folders chf on chmd.folder_id = chf.id
        where attachment_id is null AND chf.status IN (0,1)
        ORDER BY chmd.id DESC
        LIMIT ".$offset.", ".$post_per_page."; ";

        $sql_result = $wpdb->get_results( $sql, OBJECT);

        /* Determine the total of results found to calculate the max_num_pages
         for next_posts_link navigation */
        $total_image = $wpdb->get_var( "SELECT FOUND_ROWS();" );
        $max_num_pages = ($total_image > 0) ? ceil($total_image / $post_per_page) : 1;

        return $sql_result;
    }

    public function remove_folder_not_optimized($folder_id)
    {
        $this->db->delete_rows(  $this->db->get_prefix() . $this->table_name,
            array(
                'folder_id' => $folder_id,
                'status' => 0
            ),
            array(
                'folder_id' => '%d',
                'status' => '%s'
            )
        );
    }

    public function remove_folder_item_not_optimized($meta_id)
    {
        $this->db->delete_rows($this->db->get_prefix() . $this->table_name,
            array(
                'id' => $meta_id,
                'status' => 0
            ),
            array(
                'id' => '%d',
                'status' => '%s'
            )
        );

    }

    /**
     * Get item by meta id
     *
     * @param $meta_id
     *
     * @since 1.5
     *
     * @return mixed
     */
    public function get_image_meta_by_meta_id( $meta_id ) {
        $sql = "SELECT * FROM " . $this->db->get_prefix() . $this->table_name . "  WHERE id = '%d'";

        return $this->db->query( $sql, array( $meta_id ), true );
    }

    /**
     * Get count items by folder id
     *
     * @param $folder_id
     *
     * @since 1.5
     *
     * @return mixed
     */
    public function get_image_count_meta_by_folder_id( $folder_id ) {
        $sql = "SELECT count(*) as count FROM " . $this->db->get_prefix() . $this->table_name . "  WHERE folder_id = '%d'";

        $data = $this->db->query( $sql, array( $folder_id ), true );

        if ($data !== null) {
            return $data->count;
        }

        return 0;
    }

    public function update_optimized_custom_meta( $meta_id, $meta ) {

        $id = $this->db->update( $this->db->get_prefix() . $this->table_name,
            array(
                'status'          => (isset( $meta['status'])) ? $meta['status'] : null,
                'width'           => (isset( $meta['width'])) ? $meta['width'] : 0,
                'height'          => (isset( $meta['height'])) ? $meta['height'] : 0,
                'backup_path'     => (isset( $meta['backup_path'])) ? $meta['backup_path'] : '',
                'image_size'      => (isset( $meta['image_size'])) ? $meta['image_size'] : 0,
                'level'           => (isset( $meta['level'])) ? $meta['level'] : 0,
                'results'         => (isset( $meta['results'])) ? json_encode( $meta['results'] ) : '',
            ),
            array( 'id' => $meta_id ),
            array(
                'status'          => '%s',
                'width'           => '%d',
                'height'          => '%d',
                'backup_path'     => '%s',
                'image_size'      => '%d',
                'level'           => '%s',
                'results'         => '%s',
            ),
            array( '%d' ) );

        return $id;
    }
}
