<?php
/**
 *
 * The file that defines hook class for cloud flare cache purge
 *
 * @link       https://cheetaho.com
 * @since      1.4.3.4
 * @package    CheetahO
 * @subpackage CheetahO/admin
 * @author     CheetahO <support@cheetaho.com>
 */

if (!defined('ABSPATH')) {
    die('Direct access is not allowed.');
}

class CheetahO_Cloudflare_Hooks extends Cheetaho_Base
{
    /**
     * Purge Cloudflare files cache
     * @param $image_id
     * @since    1.4.3
     */
    function cheetaho_cloudflare_purge($image_id)
    {
        $cloudflare = new CheetahO_Cloudflare($this->cheetaho);

        if ($cloudflare->valid_credentials()) {
            $urls_to_purge = array();

            $images = (new CheetahO_Image_Metadata(new CheetahO_DB()))->get_items($image_id);

            if (is_array($images) && count($images) > 0) {
                foreach ($images as $image) {
                    array_push($urls_to_purge, get_site_url() . '/' . $image->path);
                }

                if (!empty($urls_to_purge)) {
                    $cloudflare->purge_files($urls_to_purge);
                }
            }
        }
    }

    /**
     * Purge Cloudflare files cache
     * @since    1.5
     * @param $meta_id
     */
    public function cheetaho_cloudflare_purge_custom_file($meta_id)
    {
        $cloudflare = new CheetahO_Cloudflare($this->cheetaho);

        if ($cloudflare->valid_credentials()) {
            $urls_to_purge = array();

            $image = (new CheetahO_Image_Metadata(new CheetahO_DB()))->get_image_meta_by_meta_id($meta_id);

            if ($image !== null) {
                array_push($urls_to_purge, get_site_url() . '/' . $image->path);

                if (!empty($urls_to_purge)) {
                    $cloudflare->purge_files($urls_to_purge);
                }
            }
        }
    }
}
