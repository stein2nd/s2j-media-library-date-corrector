<?php
/**
 * IDE / static-analysis stubs for the WordPress PHPUnit test suite.
 *
 * Not loaded by tests/bootstrap.php. Real classes come from wp-env at runtime.
 *
 * @package S2J_MLDC
 */

if ( class_exists( 'WP_UnitTestCase', false ) ) {
	return;
}

/**
 * WordPress core PHPUnit base test case (stub).
 */
abstract class WP_UnitTestCase extends \PHPUnit\Framework\TestCase {

	/**
	 * WordPress test data factory.
	 *
	 * @var WP_UnitTest_Factory
	 */
	protected $factory;
}

/**
 * WordPress test factory (stub).
 */
class WP_UnitTest_Factory {

	/**
	 * Attachment factory.
	 *
	 * @var WP_UnitTest_Factory_For_Attachment
	 */
	public $attachment;

	/**
	 * User factory.
	 *
	 * @var WP_UnitTest_Factory_For_User
	 */
	public $user;
}

/**
 * Attachment factory (stub).
 */
class WP_UnitTest_Factory_For_Attachment {

	/**
	 * Create an attachment post.
	 *
	 * @param array<string, mixed> $args Post arguments.
	 * @return int Post ID.
	 */
	public function create( array $args = array() ) {
		return 0;
	}
}

/**
 * User factory (stub).
 */
class WP_UnitTest_Factory_For_User {

	/**
	 * Create a user.
	 *
	 * @param array<string, mixed> $args User arguments.
	 * @return int User ID.
	 */
	public function create( array $args = array() ) {
		return 0;
	}
}

/**
 * REST API server (stub).
 */
class WP_REST_Server {

	/**
	 * Dispatch a REST request.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function dispatch( WP_REST_Request $request ) {
		return new WP_REST_Response();
	}
}

/**
 * REST API request (stub).
 */
class WP_REST_Request {

	/**
	 * @param string $method HTTP method.
	 * @param string $route  Route path.
	 */
	public function __construct( string $method = 'GET', string $route = '' ) {}

	/**
	 * @param array<string, mixed> $params Body parameters.
	 * @return void
	 */
	public function set_body_params( array $params ) {}
}

/**
 * REST API response (stub).
 */
class WP_REST_Response {

	/**
	 * @return int HTTP status code.
	 */
	public function get_status() {
		return 200;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_data() {
		return array();
	}
}
