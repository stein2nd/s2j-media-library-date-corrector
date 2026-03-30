<!--
目的：「REST API エンドポイント、リクエスト/レスポンス、権限、セキュリティ、nonce」の明文化
-->

# S2J MediaLibrary Date Corrector - REST API 仕様

## 1. 共通事項

### 1.1. 名前空間とバージョン

| 項目 | 値 (想定) |
|------|------------|
| Namespace | `s2j-mldc` |
| Version | `v1` |

完全パス例：`/wp-json/s2j-mldc/v1/...`

実装時はプラグイン定数 (例: `S2J_MLDC_REST_NAMESPACE`) に寄せ、変更時の影響範囲を限定する。

### 1.2. 要求ヘッダ

| ヘッダ | 必須 | 説明 |
|--------|------|------|
| `X-WP-Nonce` | ログインユーザー操作時 **必須** | `wp_create_nonce( 'wp_rest' )` の値。`api-fetch` は既定で付与 |
| `Content-Type` | POST/PUT 時 | `application/json` |

### 1.3. レスポンス形式

* 成功時：`application/json`、本文は各エンドポイントの定義に従う
* エラー時：WordPress REST のエラーオブジェクト (`code`, `message`, `data.status`)

---

## 2. 権限 (Capability)

原則として **メディアを編集できるユーザー** のみが、対象添付に対する **読取/更新** を行える。

| 操作 | 条件 (想定) |
|------|----------------|
| 一覧・プレビュー系 | 各 `attachment` ID に対し `current_user_can( 'edit_post', $id )` が真 |
| 一括更新 | リクエスト本文の **各 ID** に対し同様に検証。1件でも権限がなければその件は `error` とするか、リクエスト全体を `403` にするかは、実装方針で決定 (推奨: **件別結果** で部分成功を許容) |

サイト全体のメンテナンス用途で `manage_options` のみに制限するモードを将来追加する余地はあるが、初期版では上記でよい。

---

## 3. セキュリティ

| 観点 | 対策 |
|------|------|
| CSRF | 認証済みリクエストは **REST nonce** (`wp_rest`) を検証 |
| 認証 | ログインセッション (Cookie) ＋アプリケーションパスワード等の標準 REST 認証に準拠 |
| 入力検証 | `attachment` ID は整数配列にキャストし、存在・`post_type === 'attachment'` を確認 |
| エスケープ | レスポンスの表示用文字列は管理者 UI 側でも適宜エスケープ |
| レート制限 | コアに任せる。大量件数は **チャンク API** またはクエリ上限で分割 |
| 情報漏洩 | エラーメッセージにファイルシステム絶対パスを含めない |

**匿名公開** のエンドポイントは設けない (フロント用のブロックは PHP レンダリングで完結させ、公開 REST は不要を原則とする)。

---

## 4. Nonce と `api-fetch`

管理画面の JavaScript からは `@wordpress/api-fetch` を使用し、ルート URL が同一オリジンのとき **Nonce ミドルウェア** が `X-WP-Nonce` を付与する。

手動で `fetch` する場合：

```text
X-WP-Nonce: <wpApiSettings.nonce または wp_create_nonce('wp_rest')>
```

---

## 冪等性

同一リクエストを複数回実行しても、結果は変わらない。

## 対象範囲

* IDs 指定
* フィルタ結果
* 全件

## 制限

* 最大件数: 100件/リクエスト
* それ以上は、分割処理

---

## 5. エンドポイント一覧 (想定)

以下は [管理画面 UI 仕様](./admin_ui_spec.md) の一括/行操作を満たすための **最小セット**。実装時に URL・フィールド名をコードと完全一致させる。

### 5.1. 添付の日付診断 (プレビュー)

**目的:** 選択中または現在のフィルターに基づく ID の `match/mismatch` と補正候補の取得。

| 項目 | 内容 |
|------|------|
| Method | `POST` |
| Route | `/s2j-mldc/v1/attachments/analyze` |
| Permission | `edit_posts` 以上 (個別 ID でも再検証) |

**Request JSON (例)**

```json
{
  "ids": [ 101, 102, 103 ]
}
```

**Response JSON (例)**

```json
{
  "items": [
    {
      "id": 101,
      "postDateYm": "2026/03",
      "pathYm": "2017/12",
      "status": "mismatch",
      "suggestedPostDate": "2017-12-01 00:00:00"
    }
  ]
}
```

* `ids` が空の場合は `400` とする。

---

### 5.2. 添付の日付補正 (選択 ID)

**目的:** チェックされた添付、または行アクションの単件の `post_date` を補正。

| 項目 | 内容 |
|------|------|
| Method | `POST` |
| Route | `/s2j-mldc/v1/attachments/correct` |
| Permission | 各 ID で `edit_post` |

**Request JSON (例)**

```json
{
  "ids": [ 101, 102 ],
  "dryRun": false
}
```

| フィールド | 型 | 必須 | 説明 |
|------------|-----|------|------|
| `ids` | `number[]` | はい | 添付 ID |
| `dryRun` | `boolean` | いいえ | `true` なら更新せず結果のみ (実装任意) |

**Response JSON (例)**

```json
{
  "results": [
    { "id": 101, "ok": true },
    { "id": 102, "ok": false, "error": "attachment_not_found" }
  ],
  "summary": {
    "ok": 1,
    "failed": 1
  }
}
```

* `mismatch` でない項目は `ok: true, skipped: true` としてもよい ([データ辞書](./data_dictionary.md) の冪等性)。

---

### 5.3. 現在の一覧相当の一括補正 (Date Correct (All))

**目的:** [管理画面 UI 仕様](./admin_ui_spec.md) に従い、「**現在の一覧 (検索・フィルター結果)** が対象」の全件補正。

一覧のクエリをクライアントから完全に再現するのは複雑なため、次の **いずれか** を採用する：

| 方式 | 概要 |
|------|------|
| **A. ID リスト方式 (推奨)** | 画面上で「表示中の全 ID」をクライアントが収集し、`correct` に複数回チャンクで送る。サーバーは毎回権限検証 |
| **B. クエリ委譲方式** | クライアントが `query` オブジェクト (`post_mime_type`, `m`, `s`, `paged` 等の許可リスト) を送り、サーバーが `WP_Query` を再構築。`manage_options` 相当の厳格なサニタイズが必要 |

初期実装は **A** を推奨する (WordPress 標準 UI の挙動と [管理画面 UI 仕様](./admin_ui_spec.md) の「現在の一覧」を一致させやすい)。

専用ルート例 (任意)：

| Method | Route | 説明 |
|--------|-------|------|
| `POST` | `/s2j-mldc/v1/attachments/correct-many` | 内部で `correct` と同一処理にディスパッチ。`max` で 1 回の上限を制御 |

---

## 6. HTTP ステータスコード (指針)

| コード | 用途 |
|--------|------|
| `200` | 処理完了 (件別に失敗が混在しても、200 + `results` で返す運用を許容) |
| `400` | 入力不正 |
| `401` / `403` | 未認証・権限不足 (全件拒否の場合) |
| `500` | 予期せぬサーバーエラー |

---

## 7. 共通仕様との関係

認証・国際化・エラー表現の共通ルールは [WP_PLUGIN_SPEC.md](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/WP_PLUGIN_SPEC.md) に従う。
