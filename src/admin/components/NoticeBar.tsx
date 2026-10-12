import React, { type ReactElement } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import type { APIResponse, CorrectorState } from '@/types/api';
import { buildDetailLines, buildHeadline, shouldShowUnprocessedWarning } from '../utils/messages';

interface NoticeBarProps {
	state: CorrectorState;
	onRetry: () => void;
	abortedEarly?: boolean;
}

/**
 * Top-of-screen admin notice for correction results.
 *
 * @param root0 Props.
 */
export function NoticeBar( { state, onRetry, abortedEarly = false }: NoticeBarProps ): ReactElement | null {
	if ( 'idle' === state.status && ! state.requestError ) {
		return null;
	}

	if ( 'loading' === state.status ) {
		return (
			<div className="notice notice-info is-dismissible s2j-mldc-notice">
				<p>{ state.processingMessage ?? __( 'Processing…', 's2j-media-library-date-corrector' ) }</p>
			</div>
		);
	}

	if ( state.requestError ) {
		return (
			<div className="notice notice-error is-dismissible s2j-mldc-notice">
				<p>{ state.requestError }</p>
			</div>
		);
	}

	if ( ! state.summary ) {
		return null;
	}

	const response: APIResponse = {
		status: state.status === 'success' || state.status === 'partial' || state.status === 'error'
			? state.status
			: 'partial',
		summary: state.summary,
		results: state.results,
	};

	const noticeClass =
		'error' === response.status
			? 'notice-error'
			: 'partial' === response.status
				? 'notice-warning'
				: 'notice-success';

	const headline = buildHeadline( response, abortedEarly );
	const details = buildDetailLines( state.results );
	const showUnprocessed = shouldShowUnprocessedWarning( response, abortedEarly );

	return (
		<div className={ `notice ${ noticeClass } is-dismissible s2j-mldc-notice` }>
			<p>
				<strong>{ headline }</strong>
			</p>
			{ showUnprocessed && (
				<p>{ __( 'Some items were not processed.', 's2j-media-library-date-corrector' ) }</p>
			) }
			{ details.length > 0 && (
				<ul className="s2j-mldc-notice-details">
					{ details.map( ( line, index ) => (
						<li key={ `${ index }-${ line.slice( 0, 20 ) }` }>{ line }</li>
					) ) }
				</ul>
			) }
			{ state.summary.failed > 0 && (
				<p>
					<button type="button" className="button s2j-mldc-retry-failed" onClick={ onRetry }>
						{ window.s2jMldcConfig?.strings.retryFailed ?? __( 'Retry Failed', 's2j-media-library-date-corrector' ) }
						{ ' ' }
						{ sprintf(
							/* translators: %d: failed count */
							__( '(%d)', 's2j-media-library-date-corrector' ),
							state.summary.failed
						) }
					</button>
				</p>
			) }
		</div>
	);
}
