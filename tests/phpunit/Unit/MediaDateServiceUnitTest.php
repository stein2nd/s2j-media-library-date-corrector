<?php
/**
 * Pure unit tests for MediaDateService (no WordPress bootstrap).
 *
 * @package S2J_MLDC
 */

use S2J\MLDC\MediaDateService;

/**
 * Media date service unit tests.
 */
class MediaDateServiceUnitTest extends \PHPUnit\Framework\TestCase {

	/**
	 * Valid yyyy/mm path prefix is parsed.
	 *
	 * @return void
	 */
	public function test_extract_path_ym_parses_valid_prefix(): void {
		$result = MediaDateService::extractPathYm( '2024/06/photo.jpg' );

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
		$this->assertNull( MediaDateService::extractPathYm( '2024/13/file.jpg' ) );
	}

	/**
	 * Post date ym extraction uses UTC month.
	 *
	 * @return void
	 */
	public function test_extract_post_date_ym(): void {
		$this->assertSame(
			'2023/01',
			MediaDateService::extractPostDateYm( '2023-01-15 12:00:00' )
		);
		$this->assertNull( MediaDateService::extractPostDateYm( '' ) );
	}

	/**
	 * buildApiResponse marks partial when not all items processed.
	 *
	 * @return void
	 */
	public function test_build_api_response_partial(): void {
		$payload = MediaDateService::buildApiResponse(
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
