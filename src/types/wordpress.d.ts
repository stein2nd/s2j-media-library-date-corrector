interface S2jMldcConfig {
	restNamespace: string;
	chunkSize: number;
	bulkAction: string;
	query: import('./api').MediaLibraryQuery;
	strings: {
		processing: string;
		processingCount: string;
		confirmAll: string;
		retryFailed: string;
	};
}

interface Window {
	wpApiSettings?: {
		root: string;
		nonce: string;
	};
	s2jMldcConfig?: S2jMldcConfig;
	wp?: {
		apiFetch?: typeof import('@wordpress/api-fetch');
		element?: typeof import('@wordpress/element');
		i18n?: typeof import('@wordpress/i18n');
	};
}
