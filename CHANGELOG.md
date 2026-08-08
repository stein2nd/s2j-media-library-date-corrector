# S2J MediaLibrary Date Corrector - CHANGELOG

## unreleased

## 1.0.1 - 2026-08-08

### Added

* `package.json` に `allowScripts` を追加 (`@s2j/docs-linter@1.0.22` の postinstall スクリプトを許可)。

### Changed

* 依存 npm モジュールを更新 (`@wordpress/components` v38.0、`@wordpress/scripts` v34.0、`@s2j/docs-linter` v1.0.22、`Vite` v8.2、`SCSS` v1.102、`Rollup` v4.62.4ほか)。
* README のバッジを更新 (SCSS v1.102、Vite v8.2)。

### Fixed

* npm v12で `@s2j/docs-linter` の postinstall スクリプトがブロックされる問題を `allowScripts` により修正。

## 1.0.0

### Added

* ビルド基盤を追加 (`package.json`、`vite.config.ts`、`tsconfig.json`)。
* ドキュメント Lint 用スクリプト `npm run lint:docs` を追加。
* GitHub Actions ワークフロー `.github/workflows/docs-lint.yml` を追加。
* VS Code 向け textlint 設定 (`.vscode/settings.json`) を追加。
* VS Code 推奨拡張の設定 (`.vscode/extensions.json`) を追加。
* `.npmrc` を追加 (`legacy-peer-deps=true`、`allow-git=all`)。

### Changed

* S2J Docs Linter の運用を Git submodule から npm パッケージ (`@s2j/docs-linter`) へ切り替え。
* 依存 npm モジュールを最新化 (`@wordpress/*`、`@s2j/docs-linter` ほか)。
* 依存 npm モジュールを再更新 (`TypeScript` v7.0、`@wordpress/block-editor` v16.0、`@wordpress/scripts` v33.0、`Vite` v8.1、`Rollup` v4.62ほか)。
* VS Code の textlint 設定パスを `${workspaceFolder}` 基準に修正。
* README のバッジを更新 (PHP v8.0、WordPress v6.9+、TypeScript v7.0、SCSS v1.101、Vite v8.1、Rollup v4.62)。
* `lint:docs` の対象に `CHANGELOG.md` を追加。

### Fixed

* GitHub Actions での `npm ci` 失敗を `.npmrc` (`legacy-peer-deps=true`) により修正。
* npm v12での `npm install` 失敗 (`EALLOWGIT`) を `.npmrc` (`allow-git=all`) により修正。

### Docs

* 設計ドキュメント (`architecture.md`、`admin_ui_spec.md`、`rest_api_spec.md` ほか) を整備・更新。
