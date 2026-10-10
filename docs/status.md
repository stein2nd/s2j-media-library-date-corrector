<!--
目的：「実装状況サマリー、Backlog、品質レポート、まとめ」の明文化
-->

# S2J MediaLibrary Date Corrector - 実装状況

本ページは、現状の実装状況を機能単位で一覧します。索引は [specs.md](./specs.md) です。

最終更新: 2026-10-10

## 仕様書 (参照元)

* [specs.md](./specs.md) — 索引
* [overview.md](./overview.md) / [concept.md](./concept.md) / [architecture.md](./architecture.md)
* [data_dictionary.md](./data_dictionary.md) / [rest_api_spec.md](./rest_api_spec.md) / [admin_ui_spec.md](./admin_ui_spec.md)
* [block_spec.md](./block_spec.md)

## 機能一覧

| 機能名 | 実装済み/未実装 | 実装％ | 完了条件 |
| --- | --- | --- | --- |
| 仕様 (`docs/`) | 確定 | — | 監査 BP 反映済み。大きな改訂は合意後に本 `docs/` を直す |
| メディア一覧「年月 (パス)」「差分」列 | 未実装 | 0 | [admin_ui_spec.md](./admin_ui_spec.md) / [data_dictionary.md](./data_dictionary.md) |
| 行アクション Date Correct | 未実装 | 0 | `POST /wp-json/s2j-mldc/v1/attachments/correct`。1件の ID |
| 一括 Date Correct (チェック選択) | 未実装 | 0 | 同上。チェックなしでは実行しない。最大100件 |
| Date Correct (All) | 未実装 | 0 | `POST …/attachments/correct-query`。続きは `processed === total` かつ `nextOffset !== null` の場合だけ。`processed < total` なら一連をやめる。パスから年月を読めない件は対象外 |
| 不一致案内バナー | 未実装 | 0 | 表示中ページに不一致があれば表示。option `s2j_mldc_mismatch_notice` |
| サイト設定 (案内の表示) | 未実装 | 0 | `manage_options`。[admin_ui_spec.md](./admin_ui_spec.md) |
| REST (`correct` / `correct-query`) | 未実装 | 0 | [rest_api_spec.md](./rest_api_spec.md)。NS `s2j-mldc/v1` |
| `post_date` / `post_date_gmt` 補正 | 未実装 | 0 | 同一 `wp_update_post`。`post_modified*` は取得した値を必ず渡す |
| WP-CLI | 非対象 | — | 初期リリースでは置かない |
| ブロック / ショートコード | 非対象 | — | [block_spec.md](./block_spec.md) |

## 補足

* WordPress 下限は6.9+ である (README / architecture と同一)。
* 共通仕様は [SPECS.md](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/SPECS.md) である。
