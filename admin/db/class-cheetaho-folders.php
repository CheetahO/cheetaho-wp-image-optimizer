<?php

/**
 * Work with database tables.
 *
 * @link       https://cheetaho.com
 * @since      1.4.5
 * @package    CheetahO
 * @subpackage CheetahO/admin
 * @author     CheetahO <support@cheetaho.com>
 */
class CheetahO_Folders {

    const STATUS_NOT_OPTIMIZED = 0;
    const STATUS_OPTIMIZED = 1;
    const STATUS_NOT_REMOVED = 2;

    private $db;

    private $table_name = 'cheetaho_folders';

    /**
     *
     * @since    1.4.5
     *
     */
    public function __construct( $db ) {
        $this->db = $db;
    }

    /**
     *
     * @since    1.4.5
     *
     * Get table sql with:
     *
     * id: unique for each record/image,
     * path: folder path,
     * name:
     * path_md5: identifier for folder path
     * file_count: files in folder to optimize
     * status: status of folder
     * updated_at: when folder was updated in optimization list
     * created_at: when folder was added to optimization list
     */
    public static function get_folders_table_sql( $table_name, $db_collation ) {

        $sql = "CREATE TABLE {$table_name} (
			id int(10) unsigned NOT NULL AUTO_INCREMENT,
			path varchar(512),
            name varchar(200),
            path_md5 char(32),
            file_count int,
            status SMALLINT NOT NULL DEFAULT 0,
			updated_at timestamp DEFAULT '1971-01-01 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
            created_at timestamp,
            PRIMARY KEY id (id),
            KEY path_md5 (path_md5)
		) $db_collation;";

        return $sql;
    }

    /**
     * Create or update table
     * @since    1.4.5
     */
    public function create_or_update_tables() {
        $this->db->create_or_update_db_schema( array(
            self::get_folders_table_sql( $this->db->get_prefix() . $this->table_name, $this->db->get_charset_collate() ),
        ) );
    }

    /**
     * Add new folder record
     *
     * @param $image_id
     * @param $meta
     *
     * @since 1.4.5
     *
     * @return mixed
     */
    public function add_item( $params = array() ) {

        $path_parts = explode('/', $params['filter']['path']);
        $name = end($path_parts);

        $id = $this->db->insert( $this->db->get_prefix() . $this->table_name,
            array(
                'path'      => $params['filter']['path'],
                'name'           => $name,
                'path_md5'          => md5($params['filter']['path']),
                'file_count'  => '0',
                'status'      => $params['params']['status'],
                'created_at'  => current_time('mysql', 1)
            ),
            array(
                'path'      => '%s',
                'name'           => '%s',
                'path_md5'          => '%s',
                'file_count'         => '%d',
                'status'           => '%d',
                'created_at'         => '%s',
            ) );

        return $id;
    }

    /**
     * Folder meta update
     *
     * @param $image_id
     * @param $meta
     *
     * @since 1.4.5
     *
     * @return mixed
     */
    public function update_item( $queryParams = array() ) {
       $this->db->update( $this->db->get_prefix() . $this->table_name,
            $queryParams['params'],
            $queryParams['filter'],
            $queryParams['params_format'],
            $queryParams['filter_format']
           );

        return true;
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
    public function get_item(  $path ) {
        $sql = "SELECT * FROM " . $this->db->get_prefix() . $this->table_name . "  WHERE path_md5 = '%s'";

        return $this->db->query( $sql, array(md5($path)), true );
    }

    /**
     * Add or update item by identifier
     *
     * @param $image_id
     * @param $meta
     *
     * @since 1.4.5
     *
     * @return mixed
     */
    public function add_or_update_item( $params = array() ) {
        $image = $this->get_item( $params['filter']['path'] );

        if ( $image == null ) {
            return $this->add_item( $params );
        }

        return $this->update_item( $params );
    }

    /**
     * Get items
     *
     * @since 1.4.5
     *
     * @return mixed
     */
    public function get_items( ) {

        $sql = "SELECT * FROM " . $this->db->get_prefix() . $this->table_name;

        return $this->db->query( $sql, array(  ) );
    }

    /**
     * Get items
     *
     * @since 1.4.5
     *
     * @return mixed
     */
    public function get_item_by_id($item_id) {

        $sql = "SELECT * FROM " . $this->db->get_prefix() . $this->table_name . "  WHERE id = '%d'";

        return $this->db->query( $sql, array($item_id), true );
    }

    /**
     * Get items
     *
     * @since 1.4.5
     *
     * @return mixed
     */
    public function get_active_items( ) {

        $sql = "SELECT * FROM " . $this->db->get_prefix() . $this->table_name . "  WHERE status != '%d'";

        return $this->db->query( $sql, array( CheetahO_Folders::STATUS_NOT_REMOVED ) );
    }

}
