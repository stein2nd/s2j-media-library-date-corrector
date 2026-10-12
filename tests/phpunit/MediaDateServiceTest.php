<?php
/**
 * Tests for S2J_MLDC_Media_Date_Service.
 *
 * @package S2J_MLDC
 */

require_once dirname( __DIR__ ) . '/stubs/wordpress-test-stubs.php';

/**
 * Media date service tests.
 */
class MediaDateServiceTest extends WP_UnitTestCase {

	/**
	 * Valid yyyy/mm path prefix is parsed.
	 *
	 * @return void
	 */
	public function test_extract_path_ym_parses_valid_prefix(): void {
		$result = S2J_MLDC_Media_Date_Service::extract_path_ym( '2024/06/photo.jpg' );

		$this->assertIsArray( $result );
		$this->assertSame( 2024, $result['year'] );
		$this->assertSame( 6, $result['month'] );
		$this->assertSame( '2024/06', $result['label'] );
	}

	/**
	 * Invalid month segment returns null.
	 *
	 * @return void
	 */
	public function test_extract_path_ym_rejects_invalid_month(): void {
		$this->assertNull( S2J_MLDC_Media_Date_Service::extract_path_ym( '2024/13/file.jpg' ) );
	}

	/**
	 * Post date ym extraction uses UTC month.
	 *
	 * @return void
	 */
	public function test_extract_post_date_ym(): void {
		$this->assertSame(
			'2023/01',
			S2J_MLDC_Media_Date_Service::extract_post_date_ym( '2023-01-15 12:00:00' )
		);
		$this->assertNull( S2J_MLDC_Media_Date_Service::extract_post_date_ym( '' ) );
	}

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

		$row = S2J_MLDC_Media_Date_Service::get_row_data( $id );

		$this->assertSame( '2024/01', $row['pathYm'] );
		$this->assertSame( '2020/05', $row['postDateYm'] );
		$this->assertSame( 'mismatch', $row['status'] );
	}

	/**
	 * correct_ids updates post_date to path year/month.
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

		$response = S2J_MLDC_Media_Date_Service::correct_ids( array( $id ) );

		$this->assertSame( 'success', $response['status'] );
		$this->assertSame( 1, $response['summary']['success'] );

		$post = get_post( $id );
		$this->assertSame( '2024-03-01 00:00:00', $post->post_date );
	}

	/**
	 * build_api_response marks partial when not all items processed.
	 *
	 * @return void
	 */
	public function test_build_api_response_partial(): void {
		$payload = S2J_MLDC_Media_Date_Service::build_api_response(
			array(
				array(
					'id'     => 1,
					'status' => 'success',
				),
			),
			2,
			1
		);

		$this->assertSame( 'partial', $payload['status'] );
		$this->assertSame( 2, $payload['summary']['total'] );
		$this->assertSame( 1, $payload['summary']['processed'] );
	}
}
