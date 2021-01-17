<?php

/**
 * Filters the list of directories, exclude the media subfolders.
 *
 * @link       https://cheetaho.com
 * @since      1.4.5
 * @package    CheetahO
 * @subpackage CheetahO/helpers
 * @author     CheetahO <support@cheetaho.com>
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Iterator extends RecursiveFilterIterator
 */
class Cheetaho_Iterator extends RecursiveFilterIterator {
    /**
     * Accept method.
     *
     * @return bool
     */
    public function accept() {
        $path = $this->current()->getPathname();
      
        if ( $this->isDir() && ! CheetahO::get_instance()->get_loader()->modules->cheetaho_folder->skip_dir( $path ) ) {
            return true;
        }

        if ( ! $this->isDir() ) {
            return true;
        }

        return false;
    }
}
