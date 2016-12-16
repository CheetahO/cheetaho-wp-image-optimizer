<?php

class cheetahoHelper{
	
	public static $allowed_extensions = array('jpg', 'jpeg', 'gif', 'png');
	
	        
	public static function getStats() {
		$settings = get_option('_cheetaho_options');
		$data = self::getOptimizationStatistics($settings);

		return array('total_size_orig_images' => $data['unoptimizedSize'], 'total_images' => $data['optimizedImagesCount'], 'total_size_images' => $data['optimizedSize'], 'total_perc_optimized' => $data['totalPercOptimized']);
	}

	
	public static function getOptimizationStatistics($settings, $result = null ) {

		if (isset($GLOBALS['cheetahoStats']) ) return $GLOBALS['cheetahoStats'];
		
		global $wpdb;
		
		if ( is_null( $result ) ) {
			// Select posts that have "_wp_attachment_metadata" image metadata
			$query =
				"SELECT
					$wpdb->posts.ID,
					$wpdb->posts.post_title,
					$wpdb->postmeta.meta_value,
					wp_postmeta_file.meta_value AS unique_attachment_name,
					wp_postmeta_cheetaho.meta_value AS cheetaho_meta_value,
					wp_postmeta_cheetaho_thumbs.meta_value AS cheetaho_thumbs_meta_value
				FROM $wpdb->posts
				LEFT JOIN $wpdb->postmeta
					ON $wpdb->posts.ID = $wpdb->postmeta.post_id
				LEFT JOIN $wpdb->postmeta AS wp_postmeta_file
					ON $wpdb->posts.ID = wp_postmeta_file.post_id
						AND wp_postmeta_file.meta_key = '_wp_attached_file'
				LEFT JOIN $wpdb->postmeta AS wp_postmeta_cheetaho
					ON $wpdb->posts.ID = wp_postmeta_cheetaho.post_id
						AND wp_postmeta_cheetaho.meta_key = '_cheetaho_size'
				LEFT JOIN $wpdb->postmeta AS wp_postmeta_cheetaho_thumbs
					ON $wpdb->posts.ID = wp_postmeta_cheetaho_thumbs.post_id
						AND wp_postmeta_cheetaho_thumbs.meta_key = '_cheetaho_thumbs'
				WHERE $wpdb->posts.post_type = 'attachment'
					AND (
						$wpdb->posts.post_mime_type = 'image/jpeg' OR
						$wpdb->posts.post_mime_type = 'image/png' OR
						$wpdb->posts.post_mime_type = 'image/gif'
					)
					AND $wpdb->postmeta.meta_key = '_wp_attachment_metadata'
				GROUP BY unique_attachment_name
				ORDER BY ID DESC";

			$result = $wpdb->get_results( $query, ARRAY_A );
		}

		$stats = array();
		$stats['uploadedImages'] = 0;
		$stats['optimizedImageSizes'] = 0;
		$stats['availableUnoptimisedSizesCount'] = 0;
		$stats['optimizedSize'] = 0;
		$stats['unoptimizedSize'] = 0;
		$stats['optimizedImagesCount'] = 0;
		$stats['optimizedThumbsImagesCount'] = 0;
		$stats['availableForOptimization'] = array();
		$stats['thumbnail'] = '';

		for ( $i = 0; $i < sizeof( $result ); $i++ ) {
			$wp_metadata = @unserialize( $result[ $i ]['meta_value'] );
			$cheetaho_metadata = @unserialize( $result[ $i ]['cheetaho_meta_value'] );
			$cheetaho_thumbs_metadata = @unserialize( $result[ $i ]['cheetaho_thumbs_meta_value'] );
			
			if ( ! is_array( $cheetaho_metadata ) ) {
				$cheetaho_metadata = array();
			}
			
			if ( ! is_array( $cheetaho_thumbs_metadata ) ) {
				$cheetaho_thumbs_metadata = array();
			}
			
			$image_stats = self::generateStats(
				$result[ $i ]['ID'],
				$wp_metadata,
				$cheetaho_metadata,
				$cheetaho_thumbs_metadata,
				$settings
			);

			$stats['uploadedImages']++;
			$stats['availableUnoptimisedSizesCount'] += $image_stats['availableUnoptimisedSizesCount'];
			$stats['optimizedImageSizes'] += $image_stats['optimizedImageSizes'];
			$stats['optimizedThumbsImagesCount'] += $image_stats['optimizedThumbsImagesCount'];
			$stats['unoptimizedSize'] += $image_stats['originalSize'];
			$stats['optimizedSize'] += $image_stats['cheetahoSize'];
			
		
			if (count($image_stats['availableForOptimization']) > 0 ) {
				$file = get_attached_file( $result[ $i ]['ID']);
				$file = '/'.str_replace(get_home_path(), "", $file);
				
				if (isset($wp_metadata['sizes']['thumbnail']['file'])) {			
					$thumbnail  = dirname($file).'/'.$wp_metadata['sizes']['thumbnail']['file'];
				} else {
					$thumbnail  = $file;
				}
				
				$stats['availableForOptimization'][] = array(
					'ID' => $result[ $i ]['ID'],
					'title' => $image_stats['title'],
					'thumbnail' => $thumbnail
				);
			}
		}
		
		$stats['totalToOptimizeCount'] = count($stats['availableForOptimization']) + $stats['availableUnoptimisedSizesCount'];
		$stats['optimizedImagesCount'] = $stats['uploadedImages'] - count($stats['availableForOptimization']) + $stats['optimizedThumbsImagesCount'];
		$stats['totalPercOptimized'] = ($stats['unoptimizedSize'] > 0) ? ceil( ( ( $stats['unoptimizedSize']  - $stats['optimizedSize'] ) / $stats['unoptimizedSize']  ) * 100 ) : 0;
		
		$GLOBALS['cheetahoStats'] = $stats;
		
		return $stats;
	}

	public static function generateStats($image_id, $wp_metadata, $cheetaho_metadata, $cheetaho_thumbs_metadata, $settings) {
	 
		$stats = array();
		$stats['originalSize'] = 0;
		$stats['cheetahoSize'] = 0;
		$stats['availableUnoptimisedSizesCount'] =  0;
		$stats['optimizedImageSizes'] = 0;
				
		$thumbsCount = 0;
		if (isset($wp_metadata['sizes'])) {
			$thumbsCount = count($wp_metadata['sizes']);
		}
		
		$allowOptimizeCount = 0;
		
		if (isset($wp_metadata['sizes'])) {
			foreach ($wp_metadata['sizes'] as $key => $size) {
				if ( isset($settings['include_size_' . $key]) && $settings['include_size_' . $key] == 1 ) {
					$allowOptimizeCount++;
				}
			}
				
			$allowOptimizeCount = $allowOptimizeCount - count($cheetaho_thumbs_metadata);	
			
			if ($allowOptimizeCount < 0) {
				$allowOptimizeCount = 0;
			}
		}
		
		$optimizedThumbsCount = count($cheetaho_thumbs_metadata);
		
		$stats['availableUnoptimisedSizesCount'] = $allowOptimizeCount;
		$stats['optimizedThumbsImagesCount'] = $optimizedThumbsCount;
		
		if (isset($cheetaho_metadata['newSize'])) {
			$stats['cheetahoSize'] = $cheetaho_metadata['newSize'];
		}
		
		if (isset($cheetaho_metadata['originalImagesSize'])) {
			$stats['originalSize'] = $cheetaho_metadata['originalImagesSize']; 
		}
		
		foreach ($cheetaho_thumbs_metadata as $cheetaho_thumb) {
			$stats['cheetahoSize'] += $cheetaho_thumb['cheetaho_size'];
			$stats['originalSize'] += $cheetaho_thumb['original_size'];
		}

		$stats['availableForOptimization'] = array();
		if (empty($cheetaho_metadata) || isset($cheetaho_metadata['error'])) {
			$stats['availableForOptimization'][] = $image_id;
		}
		
		$stats['title'] = get_the_title($image_id);
		
		return $stats;
	} 
	
	static public function getBasename($Path){
		$Separator = " qq ";
		$Path = preg_replace("/[^ ]/u", $Separator."\$0".$Separator, $Path);
		$Base = basename($Path);
		$Base = str_replace($Separator, "", $Base);
		return $Base;
	}

	static function makeBackup ($image_path, $image_id, $settings) {
		
		if (isset($settings['backup']) && $settings['backup'] == 1 || !isset($settings['backup'])) {
				
			$original_image_path = get_attached_file( $image_id );
			
			//reformat image path, because sometimes no thumbs
			$file = dirname($original_image_path).'/'.basename($image_path);
			
			$paths = self::getImagePaths($file);
			
			if( !file_exists(CHEETAHO_BACKUP_FOLDER . '/' . $paths['fullSubDir']) && !@mkdir(CHEETAHO_BACKUP_FOLDER . '/' . $paths['fullSubDir'], 0777, true) ) {//creates backup folder if it doesn't exist
				echo json_encode(array(
	               		'error' => array('message'=>'Backup folder does not exist and it cannot be created')
				));

				wp_die();
			}
			
			if ( !file_exists($paths['backupFile']) ) {
				@copy( $paths['originalImagePath'], $paths['backupFile']) ;
			}
		}
	}
	
	static function getImagePaths($original_image_path) {
	
		$fullSubDir = str_replace(get_home_path(), "", dirname($original_image_path)) . '/';
		$backupFile = CHEETAHO_BACKUP_FOLDER . '/' . $fullSubDir . cheetahoHelper::getBasename($original_image_path);

		return array('backupFile' => $backupFile, 'originalImagePath' => $original_image_path, 'fullSubDir' => $fullSubDir);
	}
	
 	public static function emptyBackup(){
 		$dirPath = CHEETAHO_BACKUP_FOLDER;
 		self::deleteDir($dirPath);
 		
    }
    
    public static function deleteDir($dirPath) {
    	if(file_exists($dirPath)) {
 			if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
 				$dirPath .= '/';
 			}
 			$files = glob($dirPath . '*', GLOB_MARK);
 			foreach ($files as $file) {
 				if (is_dir($file)) {
 					self::deleteDir($file);
 					@rmdir($file);//remove empty dir
 				} else {
 					@unlink($file);//remove file
 				}
 			}
 		}
    }
 	
    protected static function setFilePerms($file) {
        //die(getenv('USERNAME') ? getenv('USERNAME') : getenv('USER'));
        $perms = @fileperms($file);
        if(!($perms & 0x0100) || !($perms & 0x0080)) {
            if(!@chmod($file, $perms | 0x0100 | 0x0080)) {
                return false;
            }
        }
        return true;
    }
    
    public static function restoreOriginalImage($attachmentID, $meta = null) {
    	$file = get_attached_file($attachmentID);
    	
    	$meta = wp_get_attachment_metadata($attachmentID);
    	
    	$pathInfo = pathinfo($file);

    	$paths = self::getImagePaths($file);
    	
    	$bkFile = $paths['backupFile'];

    	//first check if the file is readable by the current user - otherwise it will be unaccessible for the web browser
    	if(! self::setFilePerms($bkFile) ) return false;
    	
    	$thumbsPaths = array();
    	if( !empty($meta['file']) && is_array($meta["sizes"]) ) {
    		foreach($meta["sizes"] as $size => $imageData) {
    			$originalImagePath = dirname($file). '/'. $imageData['file'];
    			
    			$paths = self::getImagePaths($originalImagePath);
    			
    			$source = $paths['backupFile'];
    			if(!file_exists($source)) continue; // if thumbs were not optimized, then the backups will not be there.
    			$thumbsPaths[$source] = $paths['originalImagePath'];
    			if(! self::setFilePerms($source)) return false;
    		}
    	}

    	if(file_exists($bkFile)) {
    		try {
    			//main file
    			self::renameFile($bkFile, $file);


    			//overwriting thumbnails
    			foreach($thumbsPaths as $source => $destination) {
    				self::renameFile($source, $destination);
    			}
    			
    		} catch(Exception $e) {
    			return false;
    		}
    	} else {
    		return false;
    	}

    	return true;
    }
    
 	protected static function renameFile($bkFile, $file) {
        @rename($bkFile, $file);
        $ext = pathinfo($file, PATHINFO_EXTENSION);
      //  @rename(substr($bkFile, 0, strlen($bkFile) - 1 - strlen($ext)) . "@2x." . $ext, substr($file, 0, strlen($file) - 1 - strlen($ext)) . "@2x." . $ext);
        
    }
    
 	public static function isProcessable($ID) {
        $path = get_attached_file($ID);//get the full file PATH
        return self::isProcessablePath($path);
    }
    
    public static function isProcessablePath($path) {
        $pathParts = pathinfo($path);
        if( isset($pathParts['extension']) && in_array(strtolower($pathParts['extension']), self::$allowed_extensions)) {
            return true;
        } else {
            return false;
        }
    }
    
	public static function handleDeleteAttachmentInBackup($ID) {
        $file = get_attached_file($ID);
        $meta = wp_get_attachment_metadata($ID);
       
        if(self::isProcessable($ID) != false) 
        {
            try {
            	$paths = self::getImagePaths($file);
    			$bkFile = $paths['backupFile'];
    			
    			if (file_exists($bkFile)) {
    				@unlink($bkFile);
    			}                    
                    
                if ( !empty($meta['file']) ) {

                   	//remove thumbs thumbnails
                   	if(isset($meta["sizes"])) {
                   		foreach($meta["sizes"] as $size => $imageData) {
                   			$originalImagePath = dirname($file). '/'. $imageData['file'];
							$pathsThumb = self::getImagePaths($originalImagePath);

							if (file_exists($pathsThumb['backupFile'])) {
                   				@unlink($pathsThumb['backupFile']);//remove thumbs
                   			}
                   		}
                     }
                }
                
                $backupFile = CHEETAHO_BACKUP_FOLDER . '/' . $paths['fullSubDir'] ;
                @rmdir($backupFile);
                
        	} catch(Exception $e) {
               //what to do, what to do?
          	}
        }
    }

    public static function getNotOptimizedImagesIDs($settings) {
    	$data = self::getOptimizationStatistics($settings);
    	
    	return  array('uploadedImages' => $data['totalToOptimizeCount'],  'uploaded_images' => $data['availableForOptimization']);
    }
}