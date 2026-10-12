import { render, useReducer, useCallback, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import '@/styles/admin.scss';
import { setupApiClient } from '@/api/client';
import { NoticeBar } from './components/NoticeBar';
import { correctorReducer, initialState } from './media-corrector/reducer';
import {
	consumeStoredNotice,
	runCorrectAll,
	runCorrectIds,
	storeNoticeForReload,
} from './media-corrector/orchestrator';
import React, { type ReactElement } from 'react';
import type { APIResponse } from '@/types/api';

/**
 * Root UI for media library corrections.
 */
function MediaCorrectorApp(): ReactElement {
	const [ state, dispatch ] = useReducer( correctorReducer, initialState );
	const config = window.s2jMldcConfig;
	const chunkSize = config?.chunkSize ?? 100;

	const finishRun = useCallback( ( response: APIResponse, abortedEarly: boolean ) => {
		dispatch( { type: 'SUCCESS', payload: response, abortedEarly } );

		if ( ! abortedEarly && response.summary.success > 0 ) {
			storeNoticeForReload( response, abortedEarly );
			window.location.reload();
		}
	}, [] );

	const runIds = useCallback(
		async ( ids: number[] ) => {
			dispatch( { type: 'START' } );
			try {
				const { response, abortedEarly } = await runCorrectIds( ids, chunkSize, ( message ) => {
					dispatch( { type: 'SET_PROCESSING_MESSAGE', message } );
				} );
				finishRun( response, abortedEarly );
			} catch ( error ) {
				const message =
					error instanceof Error ? error.message : __( 'Request failed.', 's2j-media-library-date-corrector' );
				dispatch( { type: 'ERROR', error: message } );
			}
		},
		[ chunkSize, finishRun ]
	);

	const runAll = useCallback( async () => {
		if ( ! config?.query ) {
			return;
		}
		if ( ! window.confirm( config.strings.confirmAll ) ) {
			return;
		}
		dispatch( { type: 'START' } );
		try {
			const { response, abortedEarly } = await runCorrectAll( config.query, chunkSize, ( message ) => {
				dispatch( { type: 'SET_PROCESSING_MESSAGE', message } );
			} );
			finishRun( response, abortedEarly );
		} catch ( error ) {
			const message =
				error instanceof Error ? error.message : __( 'Request failed.', 's2j-media-library-date-corrector' );
			dispatch( { type: 'ERROR', error: message } );
		}
	}, [ config, chunkSize, finishRun ] );

	const retryFailed = useCallback( () => {
		const errorIds = state.results
			.filter( ( item ) => 'error' === item.status )
			.map( ( item ) => item.id );
		if ( errorIds.length > 0 ) {
			void runIds( errorIds );
		}
	}, [ state.results, runIds ] );

	useEffect( () => {
		const stored = consumeStoredNotice();
		if ( stored ) {
			dispatch( { type: 'SUCCESS', payload: stored.response } );
		}
	}, [] );

	useEffect( () => {
		const bulkAction = config?.bulkAction;
		if ( ! bulkAction ) {
			return undefined;
		}

		const form = document.getElementById( 'posts-filter' ) as HTMLFormElement | null;
		if ( ! form ) {
			return undefined;
		}

		const onSubmit = ( event: Event ) => {
			const actionSelect = form.querySelector< HTMLSelectElement >( '#bulk-action-selector-top, #bulk-action-selector-bottom' );
			const value = actionSelect?.value;
			if ( value !== bulkAction ) {
				return;
			}
			event.preventDefault();
			event.stopPropagation();

			const checked = form.querySelectorAll< HTMLInputElement >(
				'input[name="media[]"]:checked, input[name="post[]"]:checked'
			);
			const ids = Array.from( checked ).map( ( input ) => parseInt( input.value, 10 ) );
			if ( 0 === ids.length ) {
				return;
			}
			void runIds( ids );
		};

		form.addEventListener( 'submit', onSubmit, true );
		return () => form.removeEventListener( 'submit', onSubmit, true );
	}, [ config?.bulkAction, runIds ] );

	useEffect( () => {
		const onRowClick = ( event: Event ) => {
			const target = event.target as HTMLElement | null;
			const link = target?.closest( '.s2j-mldc-date-correct-row' ) as HTMLAnchorElement | null;
			if ( ! link ) {
				return;
			}
			event.preventDefault();
			const id = parseInt( link.dataset.attachmentId ?? '0', 10 );
			if ( id > 0 ) {
				void runIds( [ id ] );
			}
		};

		document.addEventListener( 'click', onRowClick );
		return () => document.removeEventListener( 'click', onRowClick );
	}, [ runIds ] );

	useEffect( () => {
		const button = document.getElementById( 's2j-mldc-date-correct-all' );
		if ( ! button ) {
			return undefined;
		}
		const handler = () => {
			void runAll();
		};
		button.addEventListener( 'click', handler );
		return () => button.removeEventListener( 'click', handler );
	}, [ runAll ] );

	const busy = 'loading' === state.status;

	return (
		<div className={ `s2j-mldc-ui-root${ busy ? ' is-busy' : '' }` }>
			<NoticeBar state={ state } onRetry={ retryFailed } abortedEarly={ state.abortedEarly } />
		</div>
	);
}

/**
 * Mount React UI above media list.
 */
function bootstrap(): void {
	setupApiClient();

	let container = document.getElementById( 's2j-mldc-root' );
	if ( ! container ) {
		container = document.createElement( 'div' );
		container.id = 's2j-mldc-root';
		const wrap = document.querySelector( '.wrap' );
		if ( wrap ) {
			wrap.insertBefore( container, wrap.firstChild );
		} else {
			document.body.prepend( container );
		}
	}

	render( <MediaCorrectorApp />, container );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', bootstrap );
} else {
	bootstrap();
}
