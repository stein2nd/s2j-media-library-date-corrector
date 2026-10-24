# S2J MediaLibrary Date Corrector - PHPUnit (wp-env なし / Local 開発)

## 正本

下準備 (MySQL / `svn` / PATH、Local の socket、`.env.tests`、開発 DB `local` を使わない方針、トラブルシューティング) は **[wp-plugin-spec: PHPUNIT_LOCAL.md](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/PHPUNIT_LOCAL.md)** を参照してください。

本ファイルは **S2J Media Library Date Corrector** 固有の件数・配置・コマンドのみを書きます。段階1〜2と結果ログは [test-results.md](./test-results.md) です。

## 開発配置 (Local)

日常開発では、例として Local サイト `blockpluginbase` の  
`~/Local Sites/blockpluginbase/app/public/wp-content/plugins/s2j-media-library-date-corrector`  
(他プラグイン clone と並置) で作業します。PHPUnit の MySQL 接続は [`.env.tests.example`](../.env.tests.example) → `.env.tests` (gitignore) で指定します (手順は正本)。

* **Requires PHP:** v8.3+ (platform / CI / wp-env も v8.3)
* **PHPUnit:** Composer `vendor/bin/phpunit` v9 (wp-env CI も同じ)

## テスト構成 (8件)

| 種別 | 件数 | 設定 | 主な対象 |
| --- | --- | --- | --- |
| ユニット | 4 | `phpunit.unit.xml.dist` | `MediaDateService` のパス解析・集約 (`tests/phpunit/Unit/MediaDateServiceUnitTest.php`)。**DB 不要** |
| 統合 | 4 | `phpunit.xml.dist` | `MediaDateServiceTest` (一覧行・`correctIds`)、`RestControllerTest` (REST 権限・補正)。**`wordpress_test` のみ** |

統合側は attachment の `post_date` 更新と REST `POST …/attachments/correct` を含みます。開発サイト DB 名 `local` には接続しません (`bin/phpunit-local.sh` / `bin/install-wp-tests.sh` が拒否)。

**Local が Stop 中**だと `mysqld.sock` がなく、`No such file or directory` / `Error establishing a database connection` になります。該当サイトを **Start** してから再実行してください。`.env.tests` を変えた場合は `bin/phpunit-local.sh` が `wp-tests-config.php` の `DB_*` を同期します。

## 実行コマンド (本リポジトリ)

| コマンド | 件数 | 備考 |
| --- | --- | --- |
| `npm run test:unit:php:unit-only` | 4 | `pretest:unit:php:local` で `composer install` |
| `npm run test:unit:php:local` | 8 | `bin/phpunit-local.sh` (初回 install あり) |
| `composer test:unit` / `composer test:all` | 4 / 8 | Node 不要 |
| `npm run test:unit:php` | 8 | **CI のみ** (wp-env → `bin/phpunit-ci.sh`) |

```bash
composer install
cp .env.tests.example .env.tests   # 正本に従い編集
npm run test:unit:php:local
```

## リポジトリ内の PHPUnit 関連ファイル

| パス | 役割 |
| --- | --- |
| [phpunit.unit.xml.dist](../phpunit.unit.xml.dist) / [phpunit.xml.dist](../phpunit.xml.dist) | ユニット / 統合 |
| [tests/bootstrap-unit.php](../tests/bootstrap-unit.php) / [tests/bootstrap.php](../tests/bootstrap.php) | bootstrap |
| [bin/phpunit-local.sh](../bin/phpunit-local.sh) | ローカル8件 |
| [bin/install-wp-tests.sh](../bin/install-wp-tests.sh) | テスト lib + `wordpress_test` |
| [bin/phpunit-ci.sh](../bin/phpunit-ci.sh) | wp-env 用8件 |
| `tests/.wordpress` / `tests/.wordpress-tests-lib` | 初回 install 成果物 (gitignore) |

## CI

[`.github/workflows/phpunit.yml`](../.github/workflows/phpunit.yml) が push / PR で `npm run test:unit:php` を実行します。ローカルでは Docker なしで上記 `test:unit:php:local` により同じ8件を再現できます。

## 関連

* [PHPUNIT_LOCAL.md](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/PHPUNIT_LOCAL.md) — 共通手順 (正本)
* [test-results.md](./test-results.md) — スモーク / Studio / 結果記録
* [SMOKE_AND_WP_ENVIRONMENTS.md](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/SMOKE_AND_WP_ENVIRONMENTS.md) — 段階1〜3の環境分担
