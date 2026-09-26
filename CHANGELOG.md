# S2J MediaLibrary Date Corrector - CHANGELOG

## unreleased

## 1.0.4 - 2026-09-26

### Changed

* 依存 npm モジュールを更新 (`@wordpress/block-editor` v18.0、`@wordpress/blocks` v16.1、`@wordpress/components` v41.0、`@wordpress/scripts` v36.0、`React` v19.3、`@s2j/docs-linter` v1.0.25、`ESLint` v10.11、`SCSS` v1.105、`Vite` v8.3、`Rollup` v4.63.5ほか)。
* README のバッジを更新 (React v19.3、SCSS v1.105、Vite v8.3)。

## 1.0.3 - 2026-09-10

### Changed

* 依存 npm モジュールを更新 (`@wordpress/block-editor` v17.0、`@wordpress/components` v40.0、`@wordpress/scripts` v34.2、`@s2j/docs-linter` v1.0.24、`@typescript-eslint/*` v8.70、`ESLint` v10.10、`SCSS` v1.104、`Rollup` v4.63.1ほか)。
* README のバッジを更新 (SCSS v1.104、Rollup v4.63)。

## 1.0.2 - 2026-08-11

### Changed

* TypeScript を公式の side-by-side 構成に変更 (`@typescript/native` で `tsc` v7.0を維持し、`typescript` は `@typescript/typescript6` を alias して `typescript-eslint` 向け API を提供)。
* `allowScripts` のキーをパッケージ名指定 (`@s2j/docs-linter`) に変更。
* `@typescript-eslint/eslint-plugin` / `parser` を v8.67に更新。

### Fixed

* `src/` 未作成時に `npm run lint` が失敗する問題を修正 (ESLint に `--no-error-on-unmatched-pattern`、Stylelint に `--allow-empty-input` を追加。グロブを引用符で囲み、不要な `--ext` を削除)。

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
