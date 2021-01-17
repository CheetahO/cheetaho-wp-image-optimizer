<?php

/**
 * The statistics of the plugin.
 *
 * @link       https://cheetaho.com
 * @since      1.4.3
 * @package    CheetahO
 * @subpackage CheetahO/admin
 * @author     CheetahO <support@cheetaho.com>
 */
class CheetahO_Stats {

	/**
	 * Get stats of optimized images
	 *
	 * @since      1.4.3
	 */
	public static function get_stats()
	{
		$data =  (new CheetahO_Image_Metadata( new CheetahO_DB()))->get_stats_data();
		$total_percents_optimized = 0;

		if ($data->total_original_size - $data->total_image_size > 0) {
			$total_percents_optimized = round(($data->total_original_size - $data->total_image_size) / $data->total_original_size * 100, 2);
		}

		return array(
			'total_size_orig_images'    => (int)$data->total_original_size,
			'total_images'              => (int)$data->count,
			'total_size_images'         => (int)$data->total_image_size,
			'total_percents_optimized'  => $total_percents_optimized
		);
	}

	/**
	 * Get not optimized images ids
	 *
	 * @since      1.4.3
	 */
	public static function get_not_optimized_images($settings)
	{
		global $wpdb;

		$result = $wpdb->get_results(
			"SELECT
				$wpdb->posts.ID,
				$wpdb->posts.post_title,
				$wpdb->posts.guid,
				wp_postmeta.post_id,
				wp_postmeta.meta_value AS unique_attachment_name,
				cheetaho_meta.attachment_id,
				cheetaho_meta.status,
				cheetaho_meta_old.meta_key as cheetaho_meta_old_key
			FROM $wpdb->posts
			LEFT JOIN $wpdb->postmeta as wp_postmeta
				ON $wpdb->posts.ID = wp_postmeta.post_id
					AND wp_postmeta.meta_key = '_wp_attached_file'
			LEFT JOIN {$wpdb->prefix}cheetaho_image_metadata AS cheetaho_meta
				ON $wpdb->posts.ID = cheetaho_meta.attachment_id
			LEFT JOIN $wpdb->postmeta as cheetaho_meta_old
				ON $wpdb->posts.ID = cheetaho_meta_old.post_id AND cheetaho_meta_old.meta_key = '_cheetaho_size'
			WHERE $wpdb->posts.post_type = 'attachment'
				AND (
					$wpdb->posts.post_mime_type = 'image/jpeg' OR
					$wpdb->posts.post_mime_type = 'image/png' OR
					$wpdb->posts.post_mime_type = 'image/gif'
				)
				AND wp_postmeta.meta_key = '_wp_attached_file'
				AND (cheetaho_meta_old.meta_key is null AND ( cheetaho_meta.attachment_id is null OR (cheetaho_meta.status != 'success' AND cheetaho_meta.status != 'nosavings')) )

			GROUP BY unique_attachment_name
			ORDER BY ID DESC",
			ARRAY_A
		);

		$items = array();

		foreach($result as $item) {
            if ( CheetahO_Helpers::is_excluded_file( $item['guid'], CheetahO_Helpers::convert_exclude_file_patterns_to_array($settings) ) === false ) {
                $items[] = array('ID' => $item['ID'], 'title' => $item['post_title'], 'thumbnail' => $item['guid']);
            }
		}

		return array('images_ids_to_optimize' => $items);
	}
}
