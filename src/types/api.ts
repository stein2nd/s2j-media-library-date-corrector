export type MismatchStatus = 'match' | 'mismatch' | 'unknown';

export type ResultStatus = 'success' | 'skipped' | 'error';

export interface ResultItem {
	id: number;
	status: ResultStatus;
	message?: string;
}

export interface Summary {
	total: number;
	processed: number;
	success: number;
	skipped: number;
	failed: number;
}

/** REST top-level result status (not UI idle/loading). */
export type ApiResultStatus = 'success' | 'partial' | 'error';

export interface APIResponse {
	status: ApiResultStatus;
	summary: Summary;
	results: ResultItem[];
}

export interface CorrectQueryResponse extends APIResponse {
	nextOffset: number | null;
}

export interface MediaLibraryQuery {
	s: string;
	m: string;
	post_mime_type: string;
	author: number;
	orderby: string;
	order: string;
}

export type UiStatus = 'idle' | 'loading' | 'success' | 'partial' | 'error';

export interface CorrectorState {
	status: UiStatus;
	summary: Summary | null;
	results: ResultItem[];
	requestError: string | null;
	processingMessage: string | null;
	abortedEarly: boolean;
}

export type CorrectorAction =
	| { type: 'START' }
	| { type: 'SET_PROCESSING_MESSAGE'; message: string }
	| { type: 'SUCCESS'; payload: APIResponse; abortedEarly?: boolean }
	| { type: 'ERROR'; error: string }
	| { type: 'RESET' };
