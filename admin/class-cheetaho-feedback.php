<?php

/**
 * Handle CheetahO plugin alerts.
 *
 * @link       https://cheetaho.com
 * @since      1.4.4
 * @package    CheetahO
 * @subpackage CheetahO/admin
 * @author     CheetahO <support@cheetaho.com>
 */
class CheetahO_Feedback {

    /**
     * The version of this plugin.
     *
     * @since    1.4.4
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;


    /**
     * Settings data.
     *
     * @since    1.4.4
     * @access   private
     * @var      array $cheetaho_settings
     */
    private $cheetaho_settings;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.4.4
     */
    public function __construct($cheetaho) {
        $this->version           = $cheetaho->get_version();
        $this->cheetaho_settings = $cheetaho->get_cheetaho_settings();
    }


    /**
     * Filter the deactivation link to allow us to present a form when the user deactivates the plugin
     * @since 1.4.4
     */
    public function filter_action_links( $links ) 
    {

        if( isset( $links['deactivate'] ) ) {
            $deactivation_link = $links['deactivate'];
            // Insert an onClick action to allow form before deactivating
            $deactivation_link = str_replace( '<a ',
                '<a id="cheetaho-deactivate-link-' .  CHEETAHO_PLUGIN_NAME . '" ', $deactivation_link );
            $links['deactivate'] = $deactivation_link;
        }

        return $links;
    }

    /*
     * Form text strings
     * @since 1.4.4
     */
    protected function get_form_info() {
        $form = array();
        $form['heading'] = __( 'Sorry to see you go', 'cheetaho-image-optimizer' );
        $form['before_body'] = __( 'Hi, this is Nerijus, the creator of CheetahO. Thanks so much for giving my plugin a try. I’m sorry that you didn’t love it.', 'cheetaho-image-optimizer'). '😞';
        $form['body'] = __( 'Before you deactivate the plugin, would you quickly give us your reason for doing so?', 'cheetaho-image-optimizer' );
        $form['options'] = array(
            'setup'           => __( 'Set up is too difficult', 'cheetaho-image-optimizer' ),
            'docs'            => __( 'Lack of documentation', 'cheetaho-image-optimizer' ),
            'features'        => __( 'Not the features I wanted', 'cheetaho-image-optimizer' ),
            'better-plugin'   => __( 'Found a better plugin', 'cheetaho-image-optimizer' ),
            'incompatibility' => __( 'Incompatible with theme or plugin', 'cheetaho-image-optimizer' ),
            'maintenance'     => __( 'Other', 'cheetaho-image-optimizer' ),
        );
        $form['details'] = __( 'How could we improve ?', 'cheetaho-image-optimizer' );

        return $form;
    }

    /**
     * Send feedback ajax to backend
     */
    function send_feedback_ajax() {

        try {
            $msg = sanitize_text_field($_REQUEST['msg']);
            $reason = sanitize_text_field($_REQUEST['reason']);
            $nonce = sanitize_text_field($_REQUEST['nonce']);
            $nonceVerified = wp_verify_nonce($nonce, 'cheetaho_feedback_uninstall_nonce') == 1;

            if ($nonceVerified && !empty($msg)) {
                $settings = $this->cheetaho_settings;
                $api_key = '';

                if ( isset( $settings['api_key'] ) && '' != $settings['api_key'] ) {
                    $api_key = $settings['api_key'];
                }

                $cheetaho = new CheetahO_API(array('api_key' => $api_key));
                $cheetaho->send_feedback(array('reason' => $reason, 'message' => $msg));

                wp_send_json_success('OK');
            }

            wp_send_json_error($msg);
        } catch (Exception $e) {
            wp_send_json_success('OK');
        }

    }

    public function enqueue_feedback_scripts( $hook )
    {
        // For option page
        if ('plugins.php' == $hook) {
            wp_enqueue_script(CHEETAHO_PLUGIN_NAME . '-feedback', plugin_dir_url(__FILE__) . 'assets/js/feedback.js',
                array('jquery'), $this->version, false);

            ob_start();
            $form = $this->get_form_info();
            include_once CHEETAHO_PLUGIN_ROOT . 'admin/views/popups/feedback.php';
            $html = ob_get_contents();
            ob_end_clean();

            wp_localize_script(
                CHEETAHO_PLUGIN_NAME . '-feedback',
                'cheetaho_feedback_object',
                array(
                    'html' => $html,
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce( 'cheetaho_feedback_uninstall_nonce' ),
                    'alert_error' => __( 'Please fill form or use skip button...', 'cheetaho-image-optimizer' )
                )
            );
        }
    }

}
