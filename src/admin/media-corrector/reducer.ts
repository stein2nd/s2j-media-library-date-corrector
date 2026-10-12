import type { APIResponse, ApiResultStatus, CorrectorAction, CorrectorState } from '@/types/api';

export const initialState: CorrectorState = {
	status: 'idle',
	summary: null,
	results: [],
	requestError: null,
	processingMessage: null,
	abortedEarly: false,
};

/**
 * Reducer for corrector UI state.
 *
 * @param state Current state.
 * @param action Action.
 */
export function correctorReducer( state: CorrectorState, action: CorrectorAction ): CorrectorState {
	switch ( action.type ) {
		case 'START':
			return {
				...state,
				status: 'loading',
				summary: null,
				results: [],
				requestError: null,
				processingMessage: null,
				abortedEarly: false,
			};
		case 'SET_PROCESSING_MESSAGE':
			return {
				...state,
				processingMessage: action.message,
			};
		case 'SUCCESS':
			return {
				...state,
				status: mapApiStatusToUi( action.payload ),
				summary: action.payload.summary,
				results: action.payload.results,
				requestError: null,
				processingMessage: null,
				abortedEarly: Boolean( action.abortedEarly ),
			};
		case 'ERROR':
			return {
				...state,
				status: 'error',
				summary: null,
				results: [],
				requestError: action.error,
				processingMessage: null,
			};
		case 'RESET':
			return { ...initialState };
		default:
			return state;
	}
}

/**
 * Map API status to UI status (single response; orchestrator may override via SUCCESS payload).
 *
 * @param response API response.
 */
function mapApiStatusToUi( response: APIResponse ): CorrectorState['status'] {
	if ( response.status === 'error' ) {
		return 'error';
	}
	if ( response.status === 'partial' ) {
		return 'partial';
	}
	return 'success';
}

/**
 * Compute aggregated UI status after a multi-chunk or multi-window run.
 *
 * @param summary Aggregated summary.
 * @param abortedEarly Whether the run stopped before finishing all chunks/windows.
 */
export function computeAggregatedUiStatus(
	summary: APIResponse['summary'],
	abortedEarly: boolean
): ApiResultStatus {
	if ( abortedEarly || summary.processed < summary.total ) {
		return 'partial';
	}
	if ( summary.failed > 0 && summary.success === 0 && summary.skipped === 0 ) {
		return 'error';
	}
	if ( summary.failed > 0 ) {
		return 'partial';
	}
	return 'success';
}

/**
 * Add two summaries together.
 *
 * @param a First summary.
 * @param b Second summary.
 */
export function addSummaries(
	a: APIResponse['summary'],
	b: APIResponse['summary']
): APIResponse['summary'] {
	return {
		total: a.total + b.total,
		processed: a.processed + b.processed,
		success: a.success + b.success,
		skipped: a.skipped + b.skipped,
		failed: a.failed + b.failed,
	};
}
