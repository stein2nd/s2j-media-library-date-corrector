# WordPress Studio (第2段階テスト環境)

本ディレクトリは、**WordPress Studio の Blueprint** で検証用サイトを再現するための置き場です (共通方針: [wp-plugin-spec SMOKE_AND_WP_ENVIRONMENTS.md](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/SMOKE_AND_WP_ENVIRONMENTS.md) 段階2)。日常開発は Local (段階1)、PHP の自動テストは PHPUnit (段階3) です。

## ファイル

| ファイル | 内容 |
| --- | --- |
| `smoke-test-blueprint.json` | Studio / Playground 形式の検証サイト定義 (PHP v8.3、WP latest、Query Monitor、Plugin Check、シード用 mu-plugin) |
| `mu-plugin-smoke-seed.php` | 上記 Blueprint に同梱される mu-plugin の **ソース** (JSON 内 `writeFile` と同期) |

## 手順

1. Studio → **Add site** → **Upload a Blueprint** → `smoke-test-blueprint.json` を選択してサイトを作成する。
2. 作成されたサイトの `wp-content/plugins/` に、**`npm run build` 済み** の本リポジトリを配置する (シンボリックリンク推奨)。
3. Site Shell または `studio wp` で有効化する。

   ```bash
   wp plugin activate s2j-media-library-date-corrector
   # またはリポジトリルートで
   npm run test:smoke:local
   ```

4. 管理画面を一度開く (mu-plugin が不一致サンプル attachment を1件追加する)。
5. **メディア > ライブラリ** (リスト表示) で [test-results.md](../docs/test-results.md) 段階1チェックリストを確認する。
6. 結果を `docs/test-results.md` の「最新結果」に記録する。

### 段階2環境の再構築 (fixtures + build + 反映)

Blueprint でサイトを一度作ったあと、テスト用画像の再取得・ビルド・プラグイン反映・シードリセットをまとめて行う場合:

```bash
export WP_PATH="$HOME/Studio/mldc-smoke"   # Studio サイトの WordPress ルート
npm run test:smoke:studio:rebuild
```

* デフォルトは `wp-content/plugins/` にリポジトリへの **symlink** (`STUDIO_PLUGIN_LINK=0` で rsync)。
* フィクスチャは GitHub `main` の `studio/assets/` から取得 (失敗時はローカル `studio/assets/`)。
* 完了後、管理画面を1回開いて mu-plugin シードを再実行する。

Local の開発サイトと Studio の検証サイトを分けておくと、WP / PHP バージョン差の確認が Local の作業を壊しにくくなります。
