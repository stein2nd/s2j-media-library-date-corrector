<!--
目的：「実装状況サマリー、Backlog、品質レポート、まとめ」の明文化
-->

# S2J MediaLibrary Date Corrector - 実装状況

本ページは、現状の実装状況を機能単位で一覧します。索引は [specs.md](./specs.md) です。

最終更新: 2026-10-12

## 仕様書 (参照元)

* [specs.md](./specs.md) — 索引
* [overview.md](./overview.md) / [concept.md](./concept.md) / [architecture.md](./architecture.md)
* [data_dictionary.md](./data_dictionary.md) / [rest_api_spec.md](./rest_api_spec.md) / [admin_ui_spec.md](./admin_ui_spec.md)
* [block_spec.md](./block_spec.md)

## 機能一覧

| 機能名 | 実装済み/未実装 | 実装％ | 完了条件 |
| --- | --- | --- | --- |
| 仕様 (`docs/`) | 確定 | — | 監査 BP 反映済み。大きな改訂は合意後に本 `docs/` を直す |
| メディア一覧「年月 (パス)」「差分」列 | 実装済み | 100 | [admin_ui_spec.md](./admin_ui_spec.md) / [data_dictionary.md](./data_dictionary.md) |
| 行アクション Date Correct | 実装済み | 100 | `POST /wp-json/s2j-mldc/v1/attachments/correct` |
| 一括 Date Correct (チェック選択) | 実装済み | 100 | 同上。チェックなしでは実行しない。クライアントが100件分割 |
| Date Correct (All) | 実装済み | 100 | `POST …/attachments/correct-query`。走査窓の集約は orchestrator |
| 不一致案内バナー | 実装済み | 100 | option `s2j_mldc_mismatch_notice` |
| サイト設定 (案内の表示) | 実装済み | 100 | 設定 > メディアの日付 |
| REST (`correct` / `correct-query`) | 実装済み | 100 | [rest_api_spec.md](./rest_api_spec.md) |
| `post_date` / `post_date_gmt` 補正 | 実装済み | 100 | `Media_Date_Service` |
| 管理画面 JS (通知・再試行・再読み込み) | 実装済み | 90 | 手動確認、E2E は未 |
| WP-CLI | 非対象 | — | 初期リリースでは置かない |
| ブロック / ショートコード | 非対象 | — | [block_spec.md](./block_spec.md) |

## 補足

* WordPress 下限は6.9+ である (README / architecture と同一)。
* 共通仕様は [SPECS.md](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/SPECS.md) である。
* 配布 zip は `npm run dist` (`release/s2j-media-library-date-corrector-<version>.zip`)。CI は `.github/workflows/dist-zip.yml` (タグ `v*` または手動実行)。

## Backlog

* テスト:
    * 段階1: Local 結合
    * 段階2: WordPress Studio (Blueprint は [studio/README.md](../studio/README.md))
    * 段階3: PHPUnit — [test-results.md](./test-results.md)
* 日本語 `.po` の追加 (UI 文言は gettext 済み)
