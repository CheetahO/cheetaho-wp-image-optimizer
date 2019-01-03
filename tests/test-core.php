<?php
/**
 * Class CheetahOCoreTest
 *
 * @package cheetaho-wp-image-optimizer
 */
/**
 * CheetahO Core Test case.
 */
class CheetahOCoreTest extends WP_UnitTestCase {

    private $cheetaho_settings = array();
    private $apiKey = 'fake_key';

    private $pngFile = '';
    private $jpgFile = '';

    public function setUp() {
        parent::setUp();

        $this->apiKey = CHEETAHO_API_KEY;

        $input['api_lossy'] = 1;
        $input['auto_optimize'] = 0;
        $input['quality'] = 80;
        $input['backup'] = 1;
        $input['keep_exif'] = 1;
        $input['authUser'] = '';
        $input['authPass'] = '';
        $input['resize'] = 0;
        $input['api_key'] = $this->apiKey;

        $cheetaho = new WPCheetahO();
        $result = $cheetaho->validate_options_data($input);

        update_option('_cheetaho_options', $result['valid']);

        $this->cheetaho_settings = get_option('_cheetaho_options');

        $this->pngFile = dirname( dirname( __FILE__ ) ).'/tests/data/testFile.png';
        $this->jpgFile = dirname( dirname( __FILE__ ) ).'/tests/data/testFile.jpg';
    }

	/**
	 * Change value of one options.
	 */
	function testUpdateOptionsValue() {
        $cheetaho = new WPCheetahO();
        $cheetaho->updateOptionsValue('api_key', $this->apiKey);

        $cheetaho = new WPCheetahO();

	    $this->assertEquals( $cheetaho->getSettings()['api_key'],  $this->apiKey);
        $this->assertEquals( $cheetaho->getSettings()['quality'],  $this->cheetaho_settings['quality']);
	}

    /**
     * Change value of one options.
     */
    function testValidateOptionsData() {
        $input['api_lossy'] = 1;
        $input['auto_optimize'] = 1;
        $input['quality'] = 80;
        $input['backup'] = 1;
        $input['keep_exif'] = 1;
        $input['authUser'] = '';
        $input['authPass'] = '';
        $input['api_key'] = '';

        $cheetaho = new WPCheetahO();
        $result = $cheetaho->validate_options_data($input);

        $this->assertEquals($result['error'][0], 'Please enter API Credentials');

        $input['api_key'] = 'fake key';

        $cheetaho = new WPCheetahO();
        $result = $cheetaho->validate_options_data($input);
        $this->assertEquals($result['error'][0], 'Your API key is invalid. Check it here http://app.cheetaho.com/admin/api-credentials');

        $input['api_key'] = $this->apiKey;

        $cheetaho = new WPCheetahO();
        $result = $cheetaho->validate_options_data($input);
        $this->assertTrue($result['success']);
    }

    /**
     * Test if backup folder removes
     */
    function testEmptyBackup ()
    {
        $attachment = self::uploadTestFiles($this->jpgFile);
        $image_path = wp_get_attachment_url($attachment['attacment_id']);

        cheetahoHelper::makeBackup($image_path, $attachment['attacment_id'], $this->cheetaho_settings);

        $original_image_path = get_attached_file( $attachment['attacment_id'] );
        $file = dirname($original_image_path).'/'.basename($image_path);

        $paths = cheetahoHelper::getImagePaths($file);

        $this->assertTrue(file_exists($paths['backupFile']));

        $cheetaho = new WPCheetahO();
        $cheetaho->emptyBackup();

        $this->assertFalse(file_exists($paths['backupFile']));
    }

    /**
     * test if one file was removed in backup folder
     */
    function testDeleteAttachmentInBackup()
    {
        $attachment = self::uploadTestFiles($this->jpgFile);
        $image_path = wp_get_attachment_url($attachment['attacment_id']);

        cheetahoHelper::makeBackup($image_path, $attachment['attacment_id'], $this->cheetaho_settings);

        $original_image_path = get_attached_file( $attachment['attacment_id'] );
        $file = dirname($original_image_path).'/'.basename($image_path);

        $paths = cheetahoHelper::getImagePaths($file);

        $this->assertTrue(file_exists($paths['backupFile']));

        $cheetaho = new WPCheetahO();
        $cheetaho->deleteAttachmentInBackup($attachment['attacment_id']);

        $this->assertFalse(file_exists($paths['backupFile']));
    }

    function testGetSizesToOptimize()
    {
        $cheetaho = new WPCheetahO();
        $sizes = $cheetaho->get_sizes_to_optimize();
        $this->assertTrue(empty($sizes));

        $input = $this->cheetaho_settings;
        $input['include_size_large'] = 1;
        $input['include_size_thumbnail'] = 1;

        $cheetaho = new WPCheetahO();
        $result = $cheetaho->validate_options_data($input);
        update_option('_cheetaho_options', $result['valid']);

        $cheetaho = new WPCheetahO();
        $sizes = $cheetaho->get_sizes_to_optimize();

        $this->assertFalse(empty($sizes));
    }

    function testCheetahOUpdateNotice()
    {
        $cheetaho = new WPCheetahO();
        $cheetaho->cheetahOUpdateNotice('quota', 1, 1);
        $ignored_notices = get_user_meta( 1, '_cheetaho_ignore_notices', true );

        $this->assertTrue(in_array( 'quota', (array) $ignored_notices  ) || empty($data));

        $cheetaho->cheetahOUpdateNotice('quota', 1, 2);

        $ignored_notices = get_user_meta( 1, '_cheetaho_ignore_notices', true );

        $this->assertFalse(in_array( 'quota', (array) $ignored_notices));
    }

    function testCheetahoUploaderCallback()
    {
        $attachment = self::uploadTestFiles($this->jpgFile);
        $imageData = get_post_meta($attachment['attacment_id'], '_cheetaho_size');

        $this->assertTrue(empty($imageData));

        $cheetaho = new WPCheetahO();
        $cheetaho->cheetahoUploaderCallback($attachment['attacment_id']);

        $original_image_path = get_attached_file( $attachment['attacment_id'] );
        $image_path = wp_get_attachment_url($attachment['attacment_id']);
        $file = dirname($original_image_path).'/'.basename($image_path);

        $paths = cheetahoHelper::getImagePaths($file);

        $this->assertTrue(file_exists($paths['backupFile']));

        $imageData = get_post_meta($attachment['attacment_id'], '_cheetaho_size');

        $this->assertTrue(isset($imageData[0]['error']));
    }

    /*
     * create file and uplaod to db.
     */
    public static function uploadTestFiles($filePath)
    {
        $wp_upload_dir = wp_upload_dir();
        $upload = wp_upload_bits(basename($filePath), null, file_get_contents( $filePath ) );

        $attachment = array(
            'guid' => $wp_upload_dir['baseurl'] . _wp_relative_upload_path( $upload['file'] ),
            'post_mime_type' => $upload['type'],
            'post_title' => 'testFile',
            'post_content' => '',
            'post_status' => 'inherit'
        );

        $attach_id = wp_insert_attachment( $attachment,  $upload['file'] );

        $attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
        wp_update_attachment_metadata( $attach_id, $attach_data );

        return array('attacment_id' => $attach_id);
    }
    
    public function tearDown() {
        parent::tearDown();
        wp_cache_flush();
        $cheetaho = new WPCheetahO();
        $cheetaho->emptyBackup();

        delete_option('_cheetaho_options');
        delete_option('cheetaho_activation_redirect');
    }
}
