# S2J MediaLibrary Date Corrector - CHANGELOG

## unreleased

### Added

* ビルド基盤を追加 (`package.json`、`vite.config.ts`、`tsconfig.json`)。
* ドキュメント Lint 用スクリプト `npm run lint:docs` を追加。
* GitHub Actions ワークフロー `.github/workflows/docs-lint.yml` を追加。
* VS Code 向け textlint 設定 (`.vscode/settings.json`) を追加。

### Changed

* S2J Docs Linter の運用を Git submodule から npm パッケージ (`@s2j/docs-linter`) へ切り替え。
* 依存 npm モジュールを最新化 (`@wordpress/*`、`@s2j/docs-linter` ほか)。
* VS Code の textlint 設定パスを `${workspaceFolder}` 基準に修正。
* README のバッジを更新 (PHP v8.0、WordPress v6.9+)。

### Docs

* 設計ドキュメント (`architecture.md`、`admin_ui_spec.md`、`rest_api_spec.md` ほか) を整備・更新。
