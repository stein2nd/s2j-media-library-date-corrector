# テスト戦略と結果記録

本プラグインの検証方針と実行記録です。環境の役割 (Local / Studio / PHPUnit) の共通定義は [wp-plugin-spec: SMOKE_AND_WP_ENVIRONMENTS.md](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/SMOKE_AND_WP_ENVIRONMENTS.md) を正とし、本ファイルは **このプラグイン固有の手順と結果** です。

## 段階的な導入 (3段階)

| 段階 | 目的 | 主なツール | 本リポジトリの状態 |
| --- | --- | --- | --- |
| **1** | WordPress 上で最低限動くこと (スモーク) | Local、Site Shell、WP-CLI | `tests/smoke-local.sh` / `npm run test:smoke:local` + 手動チェックリスト |
| **2** | テストサイト構成の再現 (WP / PHP バージョン、プラグイン同梱) | **WordPress Studio**、Blueprints、`studio` CLI | `studio/smoke-test-blueprint.json` |
| **3** | PHP / REST / DB 連携の自動テスト、PR 時のゲート | PHPUnit、GitHub Actions (wp-env) | PHPUnit 8件、`.github/workflows/phpunit.yml` |

段階1と2は「実際の WordPress サイト」、段階3は **テスト専用 DB** の PHPUnit です。開発用 Local の DB を PHPUnit が直接書き換える必要はありません。

---

## 第1段階: Local (結合スモーク)

日常の開発サイト (例: `blockpluginbase`) の `wp-content/plugins/s2j-media-library-date-corrector` に置いた状態で確認します。

| # | 確認項目 | 期待 |
| --- | --- | --- |
| 1 | プラグイン有効化 | エラーなく有効化できる |
| 2 | メディア > ライブラリ (**リスト表示**) | 「年月 (パス)」「差分」列が表示される |
| 3 | `_wp_attached_file` が `yyyy/mm/` 形式のメディア | 差分が match / mismatch / unknown のいずれかで妥当 |
| 4 | 行アクション **Date Correct** | 不一致時に `post_date` がパス年月の1日 `00:00:00` にそろう |
| 5 | 一括 **Date Correct** (チェック選択) | 選択分のみ補正。未選択では実行しない |
| 6 | **Date Correct (All)** | 現在の検索・フィルター範囲で分割補正・完了通知 |
| 7 | 設定 > メディアの日付 | 不一致案内の表示オン / オフ |
| 8 | 処理後 **Reload** 通知 | 一覧再読み込みで差分が更新される |

テスト用メディアは、Bulk Media Register 等の実データ、または `_wp_attached_file` と `post_date` を意図的にずらした attachment でかまいません。

**自動 (段階1の一部):** Local の **Site Shell** でリポジトリルートから `npm run test:smoke:local` (または `WP_PATH=… ./tests/smoke-local.sh`)。有効化、REST ルート・サービスクラスを確認します。管理画面 React UI までは WP-CLI だけではカバーしきれないため、上記の手動項目は残します。

---

## 第2段階: WordPress Studio (再現可能な検証環境)

[WordPress Studio](https://developer.wordpress.com/docs/developer-tools/studio/) を、**Local とは別系統の「検証用サイト工場」** として使います。

* **Local:** いつもの開発・手動スモーク (段階1)
* **Studio:** 同じ手順で立ち上がる **テスト専用サイト** (WP バージョン差、PHP 差、本プラグイン + 依存の事前配置)

### 想定する使い方

1. Studio → Upload Blueprint → [`studio/smoke-test-blueprint.json`](../studio/smoke-test-blueprint.json) ([Blueprints](https://developer.wordpress.com/docs/developer-tools/studio/blueprints/) 形式)。
2. 生成サイトに **`npm run build` 済み** の本プラグインを symlink / コピーし、`wp plugin activate s2j-media-library-date-corrector`。
3. 管理画面を一度開き (mu-plugin が不一致サンプル attachment を追加)、段階1チェックリスト + 必要なら `npm run test:smoke:local`。
4. 結果を下記「最新結果」に記録。

Blueprint には Query Monitor / Plugin Check と、MLDC 有効化後に走るシード mu-plugin が含まれます。`dist/` は Git 管理外のため、**GitHub zip だけでは管理 UI は足りません** (ローカル clone を置く)。

詳細メモ: [studio/README.md](../studio/README.md)

---

## 第3段階: PHPUnit (Local + テスト DB) と CI

`WP_UnitTestCase` を使う PHPUnit は、WordPress 本体とは別の **テスト用 MySQL データベース** に対して動きます。

### 初回セットアップ (Local マシン上)

1. `composer install`
2. [WordPress のユニットテスト手順](https://developer.wordpress.org/apis/handbook/configuring-unit-tests/) に沿い、`install-wp-tests.sh` でテストライブラリを取得する。
3. Local の **Database** タブの接続情報を参照し、**テスト専用 DB** を指定してスクリプトを実行する。

例:

```bash
bash bin/install-wp-tests.sh wordpress_unit_tests root 'root' localhost latest
export WP_TESTS_DIR=/tmp/wordpress-tests-lib
```

### 実行

```bash
composer test
# または
npm run test:unit:php:local
```

期待件数: **8** (`Class_Test_Media_Date_Service` 6、`Class_Test_Rest_Controller` 2)

### CI (GitHub Actions)

Push / PR では wp-env (Docker) 上で同じ PHPUnit を実行します。Local 開発者が Docker を入れていなくても問題ありません。

```bash
npm ci && composer install && npm run test:unit:php
```

---

## 最新結果

| 日付 | 段階 | 環境 | 結果 | メモ |
| --- | --- | --- | --- | --- |
| 2026-10-12 | 1 | Local | PASS (部分) | `tests/smoke-local.sh` 追加。Site Shell から実行。UI は手動 checklist |
| 2026-10-12 | 2 | WordPress Studio | PASS (初版) | `smoke-test-blueprint.json` + mu-plugin シード。MLDC は symlink + build 後に activate / upload.php 確認 |
| (未記録) | 3 | Local + PHPUnit | — | `composer test` |
| (未記録) | 3 | GitHub Actions | — | wp-env + PHPUnit |

実行後、日付、Pass/Fail、メモを追記してください。
