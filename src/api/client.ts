import apiFetch from '@wordpress/api-fetch';
import type { APIResponse, CorrectQueryResponse, MediaLibraryQuery } from '@/types/api';
import { CORRECT_PATH, CORRECT_QUERY_PATH } from './endpoints';

const RETRYABLE_STATUS = new Set( [ 408, 429, 500, 502, 503, 504 ] );
const MAX_AUTO_RETRIES = 3;

let middlewareRegistered = false;

/**
 * Register nonce middleware once.
 */
export function setupApiClient(): void {
	if ( middlewareRegistered ) {
		return;
	}

	const nonce = window.wpApiSettings?.nonce;
	if ( nonce && apiFetch.createNonceMiddleware ) {
		apiFetch.use( apiFetch.createNonceMiddleware( nonce ) );
	}

	middlewareRegistered = true;
}

/**
 * Sleep for backoff.
 *
 * @param ms Milliseconds.
 */
function delay( ms: number ): Promise<void> {
	return new Promise( ( resolve ) => {
		setTimeout( resolve, ms );
	} );
}

/**
 * Whether an error should trigger auto-retry (no response body).
 *
 * @param error Caught error.
 */
function isRetryableFetchError( error: unknown ): boolean {
	if ( ! error || typeof error !== 'object' ) {
		return true;
	}

	const err = error as { code?: string; data?: { status?: number } };
	if ( err.code === 'fetch_error' || err.code === 'timeout' ) {
		return true;
	}

	const status = err.data?.status;
	return typeof status === 'number' && RETRYABLE_STATUS.has( status );
}

/**
 * POST with automatic retry when no API body is returned.
 *
 * @param path REST path under namespace.
 * @param data JSON body.
 */
export async function postWithRetry<T extends APIResponse>(
	path: string,
	data: Record<string, unknown>
): Promise<T> {
	let attempt = 0;
	while ( true ) {
		try {
			return await apiFetch<T>( {
				path,
				method: 'POST',
				data,
			} );
		} catch ( error ) {
			if ( attempt >= MAX_AUTO_RETRIES - 1 || ! isRetryableFetchError( error ) ) {
				throw error;
			}
			attempt += 1;
			await delay( 1000 * 2 ** ( attempt - 1 ) );
		}
	}
}

/**
 * Correct explicit attachment IDs.
 *
 * @param ids Attachment IDs.
 */
export function correctIds( ids: number[] ): Promise<APIResponse> {
	const namespace = window.s2jMldcConfig?.restNamespace ?? 's2j-mldc/v1';
	return postWithRetry<APIResponse>( `${ namespace }${ CORRECT_PATH }`, { ids } );
}

/**
 * Correct by media library query window.
 *
 * @param query List filters.
 * @param offset Scan offset.
 */
export function correctQuery(
	query: MediaLibraryQuery,
	offset: number
): Promise<CorrectQueryResponse> {
	const namespace = window.s2jMldcConfig?.restNamespace ?? 's2j-mldc/v1';
	return postWithRetry<CorrectQueryResponse>( `${ namespace }${ CORRECT_QUERY_PATH }`, {
		query,
		offset,
	} );
}
