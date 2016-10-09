<?php 

class cheetahoHelper{
	public static function getStats() {
	
		$optimizedData = self::getOptimizesAttachmentsData();
		$totalImages = self::countOptimizedAttachments();
		return array('total_size_orig_images' => $optimizedData['original_size'], 'total_images' => $totalImages, 'total_size_images' => $optimizedData['optimized_size'], 'total_perc_optimized' => $optimizedData['percent']);
	}
	
	
	public static function countOptimizedAttachments() {
		global $wpdb;
	
		static $count;
		
		if ( ! $count ) {
			$count = $wpdb->get_var(
				"SELECT COUNT($wpdb->posts.ID)
				 FROM $wpdb->posts
				 INNER JOIN $wpdb->postmeta
				 	ON $wpdb->posts.ID = $wpdb->postmeta.post_id
				 WHERE ($wpdb->posts.post_mime_type = 'image/jpeg' OR $wpdb->posts.post_mime_type = 'image/png' OR $wpdb->posts.post_mime_type = 'image/gif')
				 	AND ( ( $wpdb->postmeta.meta_key = '_cheetaho_size' AND CAST($wpdb->postmeta.meta_value AS CHAR) = 'success' ) OR ( $wpdb->postmeta.meta_key = '_cheetaho_size'  ) )
				 	AND $wpdb->posts.post_type = 'attachment'
				 	AND $wpdb->posts.post_status = 'inherit'"
			);
		}

		return (int) $count;
	}
	
	public static function getOptimizesAttachmentsData () {
	
		global $wpdb;
	
		$data = $wpdb->get_col(
				"SELECT  $wpdb->postmeta.meta_value
				 FROM $wpdb->posts
				 INNER JOIN $wpdb->postmeta
				 	ON $wpdb->posts.ID = $wpdb->postmeta.post_id
				 WHERE ($wpdb->posts.post_mime_type = 'image/jpeg' OR $wpdb->posts.post_mime_type = 'image/png' OR $wpdb->posts.post_mime_type = 'image/gif')
				 	AND ( ( $wpdb->postmeta.meta_key = '_cheetaho_size' AND CAST($wpdb->postmeta.meta_value AS CHAR) = 'success' ) OR ( $wpdb->postmeta.meta_key = '_cheetaho_size'  ) )
				 	AND $wpdb->posts.post_type = 'attachment'
				 	AND $wpdb->posts.post_status = 'inherit'"
			);

		$data = array_map( 'maybe_unserialize', (array) $data );
		
		$original_size  = 0;
		$optimized_size = 0;
		$savings_percentage = 0;
	
		foreach( $data as $attachment_data ) {
			if ( ! $attachment_data ) {
				continue;
			}
			
			$optimized_size += $attachment_data['cheetaho_size'];
			$savings_percentage += $attachment_data['saved_percent'];
			$original_size += $attachment_data['original_size'];

		}
			
		
		$data = array(
			'original_size'  => (int) $original_size*1024,
			'optimized_size' => (int) $optimized_size*1024,
			'percent'		 => ( 0 !== $optimized_size ) ? ceil( ( ( $original_size - $optimized_size ) / $original_size ) * 100 ) : 0
		);
	
	
	
		return $data;
	
	}
	

} 