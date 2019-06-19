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
	private $api_key           = 'fake_key';
	private $cheetaho;

	private $pngFile = '';
	private $jpgFile = '';

	public function setUp() {
		parent::setUp();

		(new CheetahO_Image_Metadata( new CheetahO_DB() ))->create_or_update_tables();

		$this->apiKey = CHEETAHO_API_KEY;

		$input['api_lossy']       = 1;
		$input['auto_optimize']   = 0;
		$input['quality']         = 80;
		$input['backup']          = 1;
		$input['keep_exif']       = 1;
		$input['authUser']        = 'admin';
		$input['authPass']        = 'demo';
		$input['resize']          = 0;
		$input['optimize_retina'] = 1;
		$input['api_key']         = $this->apiKey;

		$this->cheetaho = new CheetahO();
		$cheetaho       = new CheetahO_Settings( $this->cheetaho );
		$result         = $cheetaho->validate_options_data( $input );

		update_option( '_cheetaho_options', $result['valid'] );

		$this->cheetaho_settings = get_option( '_cheetaho_options' );

		$this->pngFile = dirname( dirname( __FILE__ ) ) . '/tests/data/testFile.png';
		$this->jpgFile = dirname( dirname( __FILE__ ) ) . '/tests/data/testFile.jpg';
	}

	/**
	 * Change value of one options.
	 */
	function test_update_options_value() {
		$cheetaho = new CheetahO_Settings( $this->cheetaho );
		$cheetaho->update_options_value( 'api_key', $this->apiKey );

		$cheetaho = new CheetahO();

		$this->assertEquals( $cheetaho->get_cheetaho_settings()['api_key'], $this->apiKey );
		$this->assertEquals( $cheetaho->get_cheetaho_settings()['quality'], $this->cheetaho_settings['quality'] );
	}

	/**
	 * Change value of one options.
	 */
	function test_validate_options_data() {
		$input['api_lossy']     = 1;
		$input['auto_optimize'] = 1;
		$input['quality']       = 80;
		$input['backup']        = 1;
		$input['keep_exif']     = 1;
		$input['authUser']      = '';
		$input['authPass']      = '';
		$input['api_key']       = '';

		$cheetaho = new CheetahO_Settings( $this->cheetaho );
		$result   = $cheetaho->validate_options_data( $input );

		$this->assertEquals( $result['error'][0], 'Please enter API Credentials' );

		$input['api_key'] = 'fake key';

		$cheetaho = new CheetahO_Settings( $this->cheetaho );
		$result   = $cheetaho->validate_options_data( $input );
		$this->assertEquals( $result['error'][0], 'Your API key is invalid. Check it here https://app.cheetaho.com/admin/api-credentials' );

		$input['api_key'] = $this->apiKey;

		$cheetaho = new CheetahO_Settings( $this->cheetaho );
		$result   = $cheetaho->validate_options_data( $input );
		$this->assertTrue( $result['success'] );
	}

	/**
	 * Test if backup folder removes
	 */
	function test_empty_backup() {
		$attachment      = self::upload_test_files( $this->jpgFile );
		$image_path      = wp_get_attachment_url( $attachment['attacment_id'] );
		$cheetaho_backup = new CheetahO_Backup( $this->cheetaho );

		$cheetaho_backup->make_backup( $image_path, $attachment['attacment_id'], $this->cheetaho_settings );

		$original_image_path = get_attached_file( $attachment['attacment_id'] );
		$file                = dirname( $original_image_path ) . '/' . basename( $image_path );

		$paths = CheetahO_Helpers::get_image_paths( $file );

		$this->assertTrue( file_exists( $paths['backup_file'] ) );

		$cheetaho_backup->empty_backup();

		$this->assertFalse( file_exists( $paths['backup_file'] ) );
	}

	/**
	 * test if one file was removed in backup folder
	 */
	function test_delete_attachment_in_backup() {
		$attachment      = self::upload_test_files( $this->jpgFile );
		$image_path      = wp_get_attachment_url( $attachment['attacment_id'] );
		$cheetaho_backup = new CheetahO_Backup( $this->cheetaho );

		$cheetaho_backup->make_backup( $image_path, $attachment['attacment_id'], $this->cheetaho_settings );

		$original_image_path = get_attached_file( $attachment['attacment_id'] );
		$file                = dirname( $original_image_path ) . '/' . basename( $image_path );

		$paths = CheetahO_Helpers::get_image_paths( $file );

		$this->assertTrue( file_exists( $paths['backup_file'] ) );

		$cheetaho = new CheetahO_Admin( $this->cheetaho );
		$cheetaho->delete_attachment_img( $attachment['attacment_id'] );

		$this->assertFalse( file_exists( $paths['backup_file'] ) );
	}

	function test_get_sizes_to_optimize() {
		$cheetaho = new CheetahO_Optimizer( $this->cheetaho );
		$sizes    = $cheetaho->get_sizes_to_optimize();
		$this->assertTrue( empty( $sizes ) );

		$input                           = $this->cheetaho_settings;
		$input['include_size_large']     = 1;
		$input['include_size_thumbnail'] = 1;

		$cheetaho = new CheetahO_Settings( $this->cheetaho );
		$result   = $cheetaho->validate_options_data( $input );
		update_option( '_cheetaho_options', $result['valid'] );

		$this->cheetaho = new CheetahO();
		$cheetaho       = new CheetahO_Optimizer( $this->cheetaho );
		$sizes          = $cheetaho->get_sizes_to_optimize();
		$this->assertFalse( empty( $sizes ) );
	}

	function test_cheetaho_update_notice() {
		$cheetaho = new CheetahO_Alert($this->cheetaho);
		$cheetaho->cheetaho_update_notice( 'quota', 1, 1 );
		$ignored_notices = get_user_meta( 1, '_cheetaho_ignore_notices', true );

		$this->assertTrue( in_array( 'quota', (array) $ignored_notices ) || empty( $data ) );

		$cheetaho->cheetaho_update_notice( 'quota', 1, 2 );

		$ignored_notices = get_user_meta( 1, '_cheetaho_ignore_notices', true );

		$this->assertFalse( in_array( 'quota', (array) $ignored_notices ) );
	}

	function test_cheetaho_uploader_callback() {
		$this->cheetaho = new CheetahO();
		$attachment     = self::upload_test_files( getenv( 'TEST_JPG_IMAGE_REMOTE_PATH' ) );
		$original_image_path = get_attached_file( $attachment['attacment_id'] );

		$identifier = (new CheetahO_Image_Metadata( new CheetahO_DB() ))->get_identifier( $attachment['attacment_id'], array('path' => $original_image_path, 'image_size_name' => 'full') );
		$image_meta = (new CheetahO_Image_Metadata( new CheetahO_DB() ))->get_item($attachment['attacment_id'], $identifier);

		$this->assertTrue( empty( $image_meta ) );

		// fake retina image
		$image_path          = wp_get_attachment_url( $attachment['attacment_id'] );
		$file                = dirname( $original_image_path ) . '/' . basename( $image_path );

		$retinaImageUrl = CheetahO_Retina::get_retina_name( $file );
		copy( $file, $retinaImageUrl );

		$this->assertTrue( file_exists( $retinaImageUrl ) );
		$this->assertTrue( CheetahO_Retina::is_retina_img( $retinaImageUrl ) );
		$this->assertEquals( filesize( $retinaImageUrl ), 75503 );
		clearstatcache();
		$cheetaho = new CheetahO_Optimizer( $this->cheetaho );

        $wp_image_meta_data = wp_get_attachment_metadata( $attachment['attacment_id']);

        $cheetaho->optimize_thumbnails_filter($wp_image_meta_data,  $attachment['attacment_id'] );

		$this->assertEquals( filesize( $retinaImageUrl ), 16886 );
		clearstatcache();

		$paths = CheetahO_Helpers::get_image_paths( $file );

		$this->assertTrue( file_exists( $paths['backup_file'] ) );
		$this->assertTrue( file_exists( CheetahO_Retina::get_retina_name( $paths['backup_file'] ) ) );

		$image_meta = (new CheetahO_Image_Metadata( new CheetahO_DB() ))->get_item($attachment['attacment_id'], $identifier);

		$this->assertEquals( filesize( CheetahO_Retina::get_retina_name( $paths['backup_file'] ) ), 75503 );
		$this->assertEquals( $image_meta->image_size, 16886 );
	}

	/*
	 * create file and uplaod to db.
	 */
	public static function upload_test_files( $filePath ) {
		$wp_upload_dir = wp_upload_dir();
		$upload        = wp_upload_bits( basename( $filePath ), null, file_get_contents( $filePath ) );

		$attachment = array(
			'guid'           => $wp_upload_dir['baseurl'] . _wp_relative_upload_path( $upload['file'] ),
			'post_mime_type' => $upload['type'],
			'post_title'     => 'testFile',
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		$attach_id = wp_insert_attachment( $attachment, $upload['file'] );

		$attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		return array( 'attacment_id' => $attach_id );
	}

	public function tearDown() {
		parent::tearDown();
		wp_cache_flush();

		$cheetaho = new CheetahO();
		$cheetaho = new CheetahO_Backup( $cheetaho );
		$cheetaho->empty_backup();
		$cheetaho->delete_dir( '/tmp/wordpress/wp-content/uploads/' );

		delete_option( '_cheetaho_options' );
		delete_option( 'cheetaho_activation_redirect' );
	}
}
