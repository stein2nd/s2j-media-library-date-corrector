<?php
/**
 * Integration tests for MediaDateService (WordPress test suite).
 *
 * @package S2J_MLDC
 */

require_once dirname( __DIR__ ) . '/stubs/wordpress-test-stubs.php';

use S2J\MLDC\MediaDateService;

/**
 * Media date service integration tests.
 */
class MediaDateServiceTest extends WP_UnitTestCase {

	/**
	 * Row data reports mismatch when path and post_date differ.
	 *
	 * @return void
	 */
	public function test_get_row_data_mismatch(): void {
		$id = $this->factory->attachment->create(
			array(
				'post_mime_type' => 'image/jpeg',
				'post_date'      => '2020-05-01 00:00:00',
			)
		);
		update_post_meta( $id, '_wp_attached_file', '2024/01/test.jpg' );

		$row = MediaDateService::getRowData( $id );

		$this->assertSame( '2024/01', $row['pathYm'] );
		$this->assertSame( '2020/05', $row['postDateYm'] );
		$this->assertSame( 'mismatch', $row['status'] );
	}

	/**
	 * correctIds updates post_date to path year/month.
	 *
	 * @return void
	 */
	public function test_correct_ids_updates_mismatched_attachment(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$id = $this->factory->attachment->create(
			array(
				'post_mime_type' => 'image/jpeg',
				'post_date'      => '2020-05-01 00:00:00',
			)
		);
		update_post_meta( $id, '_wp_attached_file', '2024/03/test.jpg' );

		$response = MediaDateService::correctIds( array( $id ) );

		$this->assertSame( 'success', $response['status'] );
		$this->assertSame( 1, $response['summary']['success'] );

		$post = get_post( $id );
		$this->assertSame( '2024-03-01 00:00:00', $post->post_date );
	}
}
