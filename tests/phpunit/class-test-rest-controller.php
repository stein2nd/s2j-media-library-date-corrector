<?php
/**
 * REST API smoke tests.
 *
 * @package S2J_MLDC
 */

require_once dirname( __DIR__ ) . '/stubs/wordpress-test-stubs.php';

/**
 * REST controller smoke tests.
 */
class Class_Test_Rest_Controller extends WP_UnitTestCase {

	/**
	 * REST server instance.
	 *
	 * @var WP_REST_Server
	 */
	private $server;

	/**
	 * Set up REST server and routes.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		global $wp_rest_server;
		$this->server       = $wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init' );
	}

	/**
	 * Unauthenticated requests are rejected.
	 *
	 * @return void
	 */
	public function test_correct_route_requires_upload_files(): void {
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'POST', '/' . S2J_MLDC_REST_NAMESPACE . '/attachments/correct' );
		$request->set_body_params( array( 'ids' => array( 1 ) ) );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 401, $response->get_status() );
	}

	/**
	 * Administrator can correct a mismatched attachment via REST.
	 *
	 * @return void
	 */
	public function test_correct_route_corrects_attachment(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$id = $this->factory->attachment->create(
			array(
				'post_mime_type' => 'image/jpeg',
				'post_date'      => '2019-11-01 00:00:00',
			)
		);
		update_post_meta( $id, '_wp_attached_file', '2024/08/test.jpg' );

		$request = new WP_REST_Request( 'POST', '/' . S2J_MLDC_REST_NAMESPACE . '/attachments/correct' );
		$request->set_body_params( array( 'ids' => array( $id ) ) );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertSame( 'success', $data['status'] );
		$this->assertSame( 1, $data['summary']['success'] );

		$post = get_post( $id );
		$this->assertSame( '2024-08-01 00:00:00', $post->post_date );
	}
}
