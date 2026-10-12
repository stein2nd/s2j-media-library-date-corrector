import { __, sprintf } from '@wordpress/i18n';
import type { APIResponse, ResultItem } from '@/types/api';

/**
 * Build notice headline from summary.
 *
 * @param response API response.
 * @param abortedEarly Run ended before all chunks/windows finished.
 */
export function buildHeadline( response: APIResponse, abortedEarly: boolean ): string {
	const { summary, status } = response;

	if ( 'error' === status ) {
		return __( 'Processing failed.', 's2j-media-library-date-corrector' );
	}

	if ( summary.success > 0 ) {
		let line: string = sprintf(
			/* translators: %d: number of corrected items */
			__( '%d items corrected.', 's2j-media-library-date-corrector' ),
			summary.success
		);
		if ( summary.skipped > 0 ) {
			line = `${ line } ${ sprintf(
				/* translators: %d: skipped count */
				__( '%d items were not updated.', 's2j-media-library-date-corrector' ),
				summary.skipped
			) }`;
		}
		return line;
	}

	if ( summary.skipped > 0 && 0 === summary.success ) {
		return __( 'No items were updated.', 's2j-media-library-date-corrector' );
	}

	if ( 'partial' === status || abortedEarly || summary.processed < summary.total ) {
		return __( 'Processing completed with warnings.', 's2j-media-library-date-corrector' );
	}

	return __( 'Processing complete.', 's2j-media-library-date-corrector' );
}

/**
 * Detail lines for errors and skipped messages.
 *
 * @param results Result items.
 */
export function buildDetailLines( results: ResultItem[] ): string[] {
	const lines: string[] = [];
	results.forEach( ( item ) => {
		if ( 'error' === item.status && item.message ) {
			lines.push( item.message );
		} else if ( 'skipped' === item.status && item.message ) {
			lines.push( item.message );
		}
	} );
	return lines;
}

/**
 * Whether partial warning about unprocessed items should show.
 *
 * @param response API response.
 * @param abortedEarly Early abort flag.
 */
export function shouldShowUnprocessedWarning(
	response: APIResponse,
	abortedEarly: boolean
): boolean {
	return abortedEarly || response.summary.processed < response.summary.total;
}
