# S2J MediaLibrary Date Corrector - CHANGELOG

## unreleased

## 1.0.6 - 2026-10-10

### Changed

* Date Correct (All) / ID 分割の一連完了: 各応答の `status` をそのまま使わず、`summary` 加算・`results` 連結後に UI `status` を REST 集約表どおり再計算する。orchestrator (reducer 外) を正本に、単発1リクエストとの success / partial 定義を分離
* フォールバック (自動再送3回失敗、残走査窓 / 残チャンク): 加算後 `processed === total` でも UI は `partial` と「一部未処理」を REST / admin / architecture で統一
* データ辞書に `summary.total` / `processed` のエンドポイント別定義 (`correct` はユニーク化後、`correct-query` は補正試行件数)。存在しない ID・非 attachment は件別 `error`
* 不一致案内 (`s2j_mldc_mismatch_notice`): List View で表示中ページの差分 `mismatch` のみ (`unknown` 除外)。真偽意味をデータ辞書に追記
* `correct-query` の `WP_Query` 再構築条件と、自動再送が尽きた場合の完了 UI を REST / admin に追記
* concept の Before/After パス表記と `_wp_attached_file` 正本、status / overview / specs / architecture の監査 BP 表記・内部リンク (見出し参照) を整理

* `ids` の `maxItems` は受信配列の長さ (重複込み)。受理後にユニーク化し、`summary.total` はユニーク化後。クライアントは重複を送らない
* 補正の正本を `_wp_attached_file` と明記 (ディスクは読まない)。architecture の REST (PHP) と `src/api/` を分離。`edit_post` 後はサービス判定。一括 Date Correct と All のスコープ見出しを分離
* Idle の「差分を確認してください」は常時ヒント。`Retry Failed` 表記と「ですある」を直し、Content-Type は POST のみにそろえた。`status.md` 最終更新を2026-10-10に

* `status.md` を機能一覧で埋めた。WordPress 下限を6.9+、共通リンクを `SPECS.md` に統一。README クイックスタートを三操作 + 案内に拡張
* `CorrectQueryResponse` (`nextOffset`) をデータ辞書に追加。`offset` / `nextOffset` は WP_Query の走査位置。続きは `processed === total` かつ `nextOffset !== null` の場合だけ。`processed < total` なら一連をやめる
* Date Correct (All) は走査窓をまたいで `summary` 加算・`results` 連結。完了時の status / Retry Failed は集約後から。補正対象0件の窓でも続きがあれば継続
* パス不能の `skipped` は `correct` のみ。`correct-query` では対象外。`post_modified*` は取得した値を必ず渡すと明記
* UI 状態を `loading` に一本化。処理中件数は `{count}` / 直近の `summary.total`。再読み込みは加算後 `summary.success` がある場合だけ
* 「デフォルトの一括補正」を Date Correct / All に言い換え。一覧は値 `unknown` / 画面「不明」。architecture から Repository / `features/` を外し、Service 内の DB 更新と明記

## 1.0.6 - 2026-10-09

### Changed

* 仕様書の「とき」「次」「以下」などを、文書ルールに合わせて「場合」「下記」に改めた。
* REST 仕様の見出しを「HTTP ステータス・コード」に直し、本文からの参照先を合わせた。

## 1.0.6 - 2026-10-04

### Changed

* 仕様を、メディアライブラリ一覧 (List View) の拡張にそろえた。ルートは `POST /attachments/correct` と `POST /attachments/correct-query`。1リクエストは最大100件。`ids` が101件以上の場合は更新せず `HTTP 400`。
* `post_date` と `post_date_gmt` は、同じ `wp_update_post` で書く。`post_modified` は維持する。
* 完了通知は画面上部の1つ。`upload.php` の読み直しは、一連の補正が終わり `summary.success` が1件でもある場合だけ。通知は `sessionStorage` に残す。
* 設定画面を初期リリースに置く。List View の表示中ページに「不一致」がある場合、補正を促す案内を出す。
* ビルド対象を admin だけにした。TypeScript の `baseUrl` を削除し、`paths` を相対パスにした。
* 依存 npm モジュールを更新 (`@s2j/docs-linter` v1.0.27)。
* 開発依存の `braces` v3.0.3 (GHSA-vfj7-8cjw-p6xm: 深くネストしたパターンで Node.js プロセスが終了する) は修正版が未公開のため、深刻度 high の指摘12件 (CVE-2026-93687) は残す。

## 1.0.5 - 2026-10-03

### Changed

* 依存 npm モジュールを更新 (`@s2j/docs-linter` v1.0.26、`@typescript-eslint/*` v8.71、`ESLint` v10.12、`Stylelint` v17.16、`SCSS` v1.105.1、`Vite` v8.3.2、`Rollup` v4.64.0ほか)。
* README のバッジを更新 (Rollup v4.64)。

### Fixed

* `@wordpress/scripts` 経由の脆弱性を `overrides` で修正 (`serialize-javascript` v7.1.2、`js-yaml` v5.4.2、`uuid` v11.1.1)。

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

* S2J Docs Linter の運用を Git submodule から npm パッケージ (`@s2j/docs-linter`) に切り替え。
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
