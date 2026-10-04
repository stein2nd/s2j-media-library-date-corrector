import { defineConfig } from 'vite';
import { resolve } from 'path';
import { rmSync } from 'fs';
import autoprefixer from 'autoprefixer';

// FLUSH_DIST 環境変数が true の場合、dist ディレクトリを削除
if (process.env.FLUSH_DIST === 'true') {
  rmSync(
      'dist',
      {
          recursive: true,
          force: true
      }
  );
}

// 初期リリースのビルド対象は admin だけです。
const buildTarget = 'admin';

const buildConfig = {
  entry: resolve(__dirname, 'src/admin/index.tsx'),
  name: 'S2JMediaLibraryDateCorrectorAdmin',
  scss: resolve(__dirname, 'src/styles/admin.scss'),
};

export default defineConfig({
  logLevel: (process.env.VITE_LOG_LEVEL as 'info' | 'warn' | 'error' | 'silent') || 'warn',
  define: {
    'process.env': {},
    'process.env.NODE_ENV': JSON.stringify(process.env.NODE_ENV || 'development'),
    'process.env.VITE_LOG_LEVEL': JSON.stringify(process.env.VITE_LOG_LEVEL || 'warn'),
  },
  build: {
    lib: {
      entry: buildConfig.entry,
      formats: ['iife'],
      name: buildConfig.name,
    },
    rollupOptions: {
      external: (id) => {
        // WordPress Gutenberg 関連のモジュールを外部化
        if (id.startsWith('@wordpress/')) return true;
        // React 関連を外部化
        if (id === 'react' || id === 'react-dom' || id === 'react-dom/client') return true;
        // jQuery を外部化
        if (id === 'jquery') return true;
        return false;
      },
      input: buildConfig.entry,
      output: {
        globals: (id) => {
          // WordPress Gutenberg のグローバル変数名をマッピング
          if (id.startsWith('@wordpress/')) {
            const parts = id.split('/');
            const module = parts[parts.length - 1];
            // 特別なマッピング
            if (id === '@wordpress/data') return 'wp.data';
            if (id === '@wordpress/element') return 'wp.element';
            if (id === '@wordpress/components') return 'wp.components';
            if (id === '@wordpress/i18n') return 'wp.i18n';
            if (id === '@wordpress/editor') return 'wp.editor';
            if (id === '@wordpress/block-editor') return 'wp.blockEditor';
            if (id === '@wordpress/core-data') return 'wp.coreData';
            return `wp.${module}`;
          }
          // React 関連
          if (id === 'react') return 'React';
          if (id === 'react-dom' || id === 'react-dom/client') return 'ReactDOM';
          // jQuery
          if (id === 'jquery') return 'jQuery';
          return id;
        },
        assetFileNames: (assetInfo) => {
          if (assetInfo.name?.endsWith('.css')) {
            return `css/s2j-alliance-manager-${buildTarget}.css`;
          }
          return 'js/[name][extname]';
        },
        chunkFileNames: 'js/[name]-[hash].js',
        entryFileNames: `js/s2j-alliance-manager-${buildTarget}.js`,
        inlineDynamicImports: false
      },
      onwarn(warning, warn) {
        // 特定の警告を抑制
        if (warning.code === 'UNUSED_EXTERNAL_IMPORT') return;
        if (warning.code === 'MODULE_LEVEL_DIRECTIVE') return;
        if (warning.message.includes('"use client"')) return;
        if (warning.message.includes('"useTransition"')) return;
        if (warning.message.includes('"startTransition"')) return;
        if (warning.message.includes('"lazy"')) return;
        if (warning.message.includes('"Suspense"')) return;
        if (warning.message.includes('"findDOMNode"')) return;
        if (warning.message.includes('"render"')) return;
        if (warning.message.includes('"hydrate"')) return;
        if (warning.message.includes('"unmountComponentAtNode"')) return;

        // その他の警告は表示
        warn(warning);
      }
    },
    outDir: 'dist',
    emptyOutDir: false,
    minify: process.env.NODE_ENV === 'production',
    sourcemap: process.env.NODE_ENV !== 'production',
    cssCodeSplit: false,
    reportCompressedSize: false, // 圧縮サイズレポートを無効化
    chunkSizeWarningLimit: 1000, // チャンクサイズ警告の閾値を1MBに設定
  },
  plugins: [],
  css: {
    preprocessorOptions: {
      scss: {
        // @useを使用するため、additionalDataは不要
      }
    },
    postcss: {
      plugins: [
        // 自動プレフィックス機能を有効化 (ブラウザ間対応のため)
        autoprefixer({
          overrideBrowserslist: [
            'last 2 versions',
            '> 1%',
            'not dead',
            'not ie 11'
          ]
        })
      ]
    }
  },
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    }
  }
});
