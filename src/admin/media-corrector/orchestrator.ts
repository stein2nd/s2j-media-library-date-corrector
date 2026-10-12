import { correctIds, correctQuery } from '@/api/client';
import type {
	APIResponse,
	ApiResultStatus,
	CorrectQueryResponse,
	MediaLibraryQuery,
	ResultItem,
	Summary,
} from '@/types/api';
import { addSummaries, computeAggregatedUiStatus } from './reducer';

const SESSION_NOTICE_KEY = 's2j_mldc_completion_notice';

/**
 * Chunk IDs into batches of chunkSize.
 *
 * @param ids IDs.
 * @param chunkSize Max per request.
 */
export function chunkIds( ids: number[], chunkSize: number ): number[][] {
	const chunks: number[][] = [];
	for ( let i = 0; i < ids.length; i += chunkSize ) {
		chunks.push( ids.slice( i, i + chunkSize ) );
	}
	return chunks;
}

/**
 * Run correct for ID list with chunking and aggregation.
 *
 * @param ids Attachment IDs.
 * @param chunkSize Max IDs per request.
 * @param onProgress Progress callback.
 */
export async function runCorrectIds(
	ids: number[],
	chunkSize: number,
	onProgress?: ( message: string ) => void
): Promise<{ response: APIResponse; abortedEarly: boolean }> {
	const unique = Array.from( new Set( ids.map( ( id ) => Number( id ) ).filter( ( id ) => id > 0 ) ) );
	if ( 0 === unique.length ) {
		throw new Error( 'No items selected.' );
	}

	const chunks = chunkIds( unique, chunkSize );
	let aggregateSummary: Summary | null = null;
	let aggregateResults: ResultItem[] = [];
	let abortedEarly = false;

	for ( let index = 0; index < chunks.length; index += 1 ) {
		const chunk = chunks[ index ];
		onProgress?.(
			window.s2jMldcConfig?.strings.processingCount.replace( '%d', String( chunk.length ) ) ??
				`${ chunk.length } items processing…`
		);

		let response: APIResponse;
		try {
			response = await correctIds( chunk );
		} catch {
			abortedEarly = true;
			break;
		}

		aggregateSummary = aggregateSummary
			? addSummaries( aggregateSummary, response.summary )
			: response.summary;
		aggregateResults = aggregateResults.concat( response.results );

		if ( response.summary.processed < response.summary.total ) {
			abortedEarly = true;
			break;
		}
	}

	if ( ! aggregateSummary ) {
		throw new Error( 'Request failed.' );
	}

	const status: ApiResultStatus = computeAggregatedUiStatus( aggregateSummary, abortedEarly );

	return {
		response: {
			status,
			summary: aggregateSummary,
			results: aggregateResults,
		},
		abortedEarly,
	};
}

/**
 * Run Date Correct (All) via correct-query windows.
 *
 * @param query Media library filters.
 * @param chunkSize Unused scan size (server fixed at 100).
 * @param onProgress Progress callback.
 */
export async function runCorrectAll(
	query: MediaLibraryQuery,
	chunkSize: number,
	onProgress?: ( message: string ) => void
): Promise<{ response: APIResponse; abortedEarly: boolean }> {
	unsetChunkSizeGuard( chunkSize );

	let offset = 0;
	let aggregateSummary: Summary | null = null;
	let aggregateResults: ResultItem[] = [];
	let abortedEarly = false;

	while ( true ) {
		onProgress?.( window.s2jMldcConfig?.strings.processing ?? 'Processing…' );

		let response: CorrectQueryResponse;
		try {
			response = await correctQuery( query, offset );
		} catch {
			abortedEarly = true;
			break;
		}

		aggregateSummary = aggregateSummary
			? addSummaries( aggregateSummary, response.summary )
			: response.summary;
		aggregateResults = aggregateResults.concat( response.results );

		if ( response.summary.processed < response.summary.total ) {
			abortedEarly = true;
			break;
		}

		if ( null === response.nextOffset ) {
			break;
		}

		if ( response.summary.processed === response.summary.total ) {
			offset = response.nextOffset;
		} else {
			abortedEarly = true;
			break;
		}
	}

	if ( ! aggregateSummary ) {
		throw new Error( 'Request failed.' );
	}

	const status: ApiResultStatus = computeAggregatedUiStatus( aggregateSummary, abortedEarly );

	return {
		response: {
			status,
			summary: aggregateSummary,
			results: aggregateResults,
		},
		abortedEarly,
	};
}

/**
 * Avoid unused parameter lint for chunkSize (server scan size is fixed).
 *
 * @param _chunkSize Config chunk size.
 */
function unsetChunkSizeGuard( _chunkSize: number ): void {
	// Intentionally empty.
}

/**
 * Persist completion notice before reload.
 *
 * @param response Final API response.
 * @param abortedEarly Whether run ended early.
 */
export function storeNoticeForReload( response: APIResponse, abortedEarly: boolean ): void {
	try {
		sessionStorage.setItem(
			SESSION_NOTICE_KEY,
			JSON.stringify( { response, abortedEarly } )
		);
	} catch {
		// Ignore quota errors.
	}
}

/**
 * Read and clear stored notice after reload.
 */
export function consumeStoredNotice(): { response: APIResponse; abortedEarly: boolean } | null {
	try {
		const raw = sessionStorage.getItem( SESSION_NOTICE_KEY );
		if ( ! raw ) {
			return null;
		}
		sessionStorage.removeItem( SESSION_NOTICE_KEY );
		return JSON.parse( raw ) as { response: APIResponse; abortedEarly: boolean };
	} catch {
		return null;
	}
}

export { SESSION_NOTICE_KEY };
