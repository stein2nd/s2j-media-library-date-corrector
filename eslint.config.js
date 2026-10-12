import js from '@eslint/js';
import typescript from '@typescript-eslint/eslint-plugin';
import typescriptParser from '@typescript-eslint/parser';
import react from 'eslint-plugin-react';
import reactHooks from 'eslint-plugin-react-hooks';

export default [
	js.configs.recommended,
	{
		files: [ 'src/**/*.{ts,tsx}' ],
		languageOptions: {
			parser: typescriptParser,
			parserOptions: {
				ecmaVersion: 'latest',
				sourceType: 'module',
				ecmaFeatures: {
					jsx: true,
				},
			},
			globals: {
				window: 'readonly',
				document: 'readonly',
				sessionStorage: 'readonly',
				setTimeout: 'readonly',
				clearTimeout: 'readonly',
				HTMLFormElement: 'readonly',
				HTMLSelectElement: 'readonly',
				HTMLInputElement: 'readonly',
				HTMLAnchorElement: 'readonly',
				HTMLElement: 'readonly',
				Event: 'readonly',
				wp: 'readonly',
				wpApiSettings: 'readonly',
				React: 'readonly',
				ReactDOM: 'readonly',
			},
		},
		plugins: {
			'@typescript-eslint': typescript,
			react,
			'react-hooks': reactHooks,
		},
		rules: {
			'@typescript-eslint/no-unused-vars': [ 'error', { argsIgnorePattern: '^_' } ],
			'@typescript-eslint/no-explicit-any': 'warn',
			'@typescript-eslint/explicit-function-return-type': 'off',
			'@typescript-eslint/explicit-module-boundary-types': 'off',
			'@typescript-eslint/no-non-null-assertion': 'warn',
			'react/react-in-jsx-scope': 'off',
			'react/prop-types': 'off',
			'react/jsx-uses-react': 'off',
			'react/jsx-uses-vars': 'error',
			'react/jsx-key': 'error',
			'react-hooks/rules-of-hooks': 'error',
			'react-hooks/exhaustive-deps': 'warn',
			'no-unused-vars': 'off',
			'no-console': 'error',
			'no-debugger': 'error',
			'prefer-const': 'error',
			'no-var': 'error',
			'object-shorthand': 'error',
			'prefer-template': 'error',
		},
		settings: {
			react: {
				version: '19.3',
			},
		},
	},
	{
		files: [ 'src/**/*.ts' ],
		rules: {
			'react/jsx-uses-vars': 'off',
		},
	},
	{
		ignores: [
			'node_modules/**',
			'dist/**',
			'*.min.js',
			'vendor/**',
			'build/**',
			'coverage/**',
		],
	},
];
