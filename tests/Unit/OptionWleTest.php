<?php
/**
 * Contains Class TestOptionWle.
 *
 * @package WP-Auth0
 *
 * @since 3.10.0
 */

class OptionWleTest extends WP_Auth0_Test_Case {

	use DomDocumentHelpers;

	/**
	 * WP_Auth0_Admin_Basic instance.
	 *
	 * @var WP_Auth0_Admin_Basic
	 */
	public static $admin;

	/**
	 * Run before the test suite starts.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$admin = new WP_Auth0_Admin_Basic( self::$opts );
	}

	/**
	 * Test that the wordpress_login_enabled field renders properly.
	 */
	public function testThatWleSettingRendersProperly() {
		$field_args = [
			'label_for' => 'wpa0_login_enabled',
			'opt_name'  => 'wordpress_login_enabled',
		];

		// Get the field HTML.
		ob_start();
		self::$admin->render_allow_wordpress_login( $field_args );
		$field_html = ob_get_clean();

		// Test that we have 4 inputs.
		$input = $this->getDomListFromTagName( $field_html, 'input' );
		$this->assertEquals( 4, $input->length );

		// Test DOM elements that should exists for all options.
		for ( $id = 0; $id <= 3; $id++ ) {
			$this->assertEquals( 'wpa0_login_enabled_' . $id, $input->item( $id )->getAttribute( 'id' ) );
			$this->assertEquals( 'radio', $input->item( $id )->getAttribute( 'type' ) );
			$this->assertEquals(
				self::OPTIONS_NAME . '[' . $field_args['opt_name'] . ']',
				$input->item( $id )->getAttribute( 'name' )
			);
		}

		// Test that the correct values and defaults appear.
		$this->assertEquals( 'no', $input->item( 0 )->getAttribute( 'value' ) );
		$this->assertEquals( 'link', $input->item( 1 )->getAttribute( 'value' ) );
		$this->assertEquals( 'checked', $input->item( 1 )->getAttribute( 'checked' ) );
		$this->assertEquals( 'isset', $input->item( 2 )->getAttribute( 'value' ) );
		$this->assertEquals( 'code', $input->item( 3 )->getAttribute( 'value' ) );
	}

	/**
	 * Test that the wle_code is displayed properly if it's empty or not.
	 */
	public function testThatTheWleCodeOutputsCorrectly() {
		self::$opts->set( 'wle_code', '' );

		$field_args = [
			'label_for' => 'wpa0_login_enabled',
			'opt_name'  => 'wordpress_login_enabled',
		];

		// Test that an empty WLE code shows a prompt to save.
		ob_start();
		self::$admin->render_allow_wordpress_login( $field_args );
		$code_block = $this->getDomListFromTagName( ob_get_clean(), 'code' );

		$this->assertEquals( 'code-block', $code_block->item( 1 )->getAttribute( 'class' ) );
		$this->assertEquals( 'Save settings to generate URL.', $code_block->item( 1 )->nodeValue );

		// Test that a non-empty WLE code appears.
		self::$opts->set( 'wle_code', uniqid() );

		ob_start();
		self::$admin->render_allow_wordpress_login( $field_args );
		$code_block = $this->getDomListFromTagName( ob_get_clean(), 'code' );
		$this->assertContains( '?wle=' . self::$opts->get( 'wle_code' ), $code_block->item( 1 )->nodeValue );
	}

	/**
	 * Check that saving a new value changes output.
	 */
	public function testThatWleSettingRendersProperlyWhenChanged() {
		$field_args = [
			'label_for' => 'wpa0_login_enabled',
			'opt_name'  => 'wordpress_login_enabled',
		];

		self::$opts->set( $field_args['opt_name'], 'no' );
		ob_start();
		self::$admin->render_allow_wordpress_login( $field_args );
		$input = $this->getDomListFromTagName( ob_get_clean(), 'input' );
		$this->assertEquals( 'checked', $input->item( 0 )->getAttribute( 'checked' ) );

		self::$opts->set( $field_args['opt_name'], 'link' );
		ob_start();
		self::$admin->render_allow_wordpress_login( $field_args );
		$input = $this->getDomListFromTagName( ob_get_clean(), 'input' );
		$this->assertEquals( 'checked', $input->item( 1 )->getAttribute( 'checked' ) );

		self::$opts->set( $field_args['opt_name'], 'isset' );
		ob_start();
		self::$admin->render_allow_wordpress_login( $field_args );
		$input = $this->getDomListFromTagName( ob_get_clean(), 'input' );
		$this->assertEquals( 'checked', $input->item( 2 )->getAttribute( 'checked' ) );

		self::$opts->set( $field_args['opt_name'], 'code' );
		ob_start();
		self::$admin->render_allow_wordpress_login( $field_args );
		$input = $this->getDomListFromTagName( ob_get_clean(), 'input' );
		$this->assertEquals( 'checked', $input->item( 3 )->getAttribute( 'checked' ) );
	}

	/**
	 * Test that wordpress_login_enabled is validated properly on save.
	 */
	public function testThatWleIsValiatedOnSave() {
		$admin = new WP_Auth0_Admin( self::$opts, new WP_Auth0_Routes( self::$opts ) );

		$validated = $admin->input_validator( [ 'wordpress_login_enabled' => 'link' ] );
		$this->assertEquals( 'link', $validated['wordpress_login_enabled'] );

		$validated = $admin->input_validator( [ 'wordpress_login_enabled' => 'isset' ] );
		$this->assertEquals( 'isset', $validated['wordpress_login_enabled'] );

		$validated = $admin->input_validator( [ 'wordpress_login_enabled' => 'code' ] );
		$this->assertEquals( 'code', $validated['wordpress_login_enabled'] );

		$validated = $admin->input_validator( [ 'wordpress_login_enabled' => 'no' ] );
		$this->assertEquals( 'no', $validated['wordpress_login_enabled'] );

		$validated = $admin->input_validator( [ 'wordpress_login_enabled' => uniqid() ] );
		$this->assertEquals( 'link', $validated['wordpress_login_enabled'] );

		$validated = $admin->input_validator( [ 'wordpress_login_enabled' => false ] );
		$this->assertEquals( 'link', $validated['wordpress_login_enabled'] );
	}

	/**
	 * Test that wle_code is validated properly on save.
	 */
	public function testThatWleCodeIsKeptIfSavedGeneratedIfEmpty() {
		$admin = new WP_Auth0_Admin( self::$opts, new WP_Auth0_Routes( self::$opts ) );

		$wle_code = uniqid();
		self::$opts->set( 'wle_code', $wle_code );
		$validated = $admin->input_validator( [ 'wordpress_login_enabled' => uniqid() ] );
		$this->assertEquals( $wle_code, $validated['wle_code'] );

		self::$opts->set( 'wle_code', null );
		$validated = $admin->input_validator( [ 'wordpress_login_enabled' => uniqid() ] );
		$this->assertGreaterThan( 24, strlen( $validated['wle_code'] ) );

		self::$opts->set( 'wle_code', '' );
		$validated = $admin->input_validator( [ 'wordpress_login_enabled' => uniqid() ] );
		$this->assertGreaterThan( 24, strlen( $validated['wle_code'] ) );
	}
}
