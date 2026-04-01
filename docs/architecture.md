<!--
目的：「フォルダー構成、主要ファイル、技術スタック、ビルド、責務、実行ロジック」の明文化
-->

# S2J MediaLibrary Date Corrector - アーキテクチャー

## フォルダー構成 (想定)

本プラグインでは、ブートストラップ (PHP)、ドメインロジック (PHP)、管理画面 UI (React)、任意のブロック/フロント資産 (React) を分離します。

```text
s2j-media-library-date-corrector/
├── `README.md`
├── `README.txt`
├── `LICENSE`
├── `package.json`  # ビルド設定
├── node_modules/  # 依存 npm モジュール
├── `vite.config.ts`
├── `tsconfig.json`
├── `eslint.config.js`  # ESLint 設定
├── docs/  # 仕様・設計ドキュメント
├── `s2j-media-library-date-corrector.php`  # プラグイン本体・フック登録
├── `uninstall.php`  # プラグイン削除時の処理
├┬─ languages/  # 翻訳ファイル
│├─ `s2j-media-library-date-corrector.pot`
│├─ `s2j-media-library-date-corrector-[ロケール名].po`
│└─ `s2j-media-library-date-corrector-[ロケール名].mo`  # WordPress 表示用バイナリ
├┬── includes/  # PHP クラス群 (設定画面、REST API、ブロック。オートロード対象)
│├── `class-plugin.php`                    # 初期化・依存登録
│├── `class-rest-controller.php`           # REST の登録と権限
│├── `class-media-date-service.php`        # 年月抽出・比較・更新の核
│├── `class-media-library-list-table.php`  # 一覧カラム・一括操作 (※)
│└── ...
├┬── src/  # TypeScript/React (Gutenberg ブロック、設定画面) /SCSS ソース
│├┬── admin/  # メディアライブラリ拡張 UI
││├─ `index.tsx`  # 管理画面メイン・エントリーポイント
││├┬─ components/
│││└── ...
││├┬─ data/
│││└─ `constants.ts`  # 定数定義 (表示形式、ランク、動作オプション)
││└┬─ utils/  # ユーティリティ
││　├─ `errorHandler.ts`  # エラー・ハンドリング
││　└── ...
│├┬─ frontend/  # フロントエンド表示
││└── ...
│├┬── gutenberg/  # Gutenberg ブロック用
││├─ `index.tsx`
││└┬─ media-library-date-corrector/  # ブロック編集
││　├─ `index.tsx`  # コンポーネント
││　└─ `block.json`  # ブロック定義
│├── classic/  # Classic エディター用スクリプト
│├── frontend/  # フロント表示用 (ブロックの view 等)
│├┬─ styles/  # プラグイン用のスタイル定義
││├─ `admin.scss`  # 設定画面用
││├─ `gutenberg.scss`  # Gutenberg ブロック用
││├─ `frontend.scss`  # フロントエンド表示用
││├─ `classic.scss`  # MetaBox 用
││└─ `variables.scss`  # SCSS 変数定義
│└┬─ types/  # プラグイン用のグローバル型定義
│　├─ `index.ts`  # ContentModel
│　├─ `wordpress.d.ts`  # WordPress
│　├─ `dom.d.ts`  # DOM
└┬─ dist/  # Vite ビルド成果物 (Git 管理外)、アイコン
　├┬─ blocks/
　│└┬─ media-library-date-corrector/
　│　└─ `block.json`  # ブロック定義
　├┬─ css/  # プラグイン用のスタイル定義
　│├─ `s2j-media-library-date-corrector-admin.css`
　│├─ `s2j-media-library-date-corrector-gutenberg.css`
　│├─ `s2j-media-library-date-corrector-frontend.css`
　│└─ `s2j-media-library-date-corrector-classic.css`
　└┬─ js/  # プラグイン用の Gutenberg ブロック、設定画面
　　├─ `s2j-media-library-date-corrector-admin.js`
　　├─ `s2j-media-library-date-corrector-gutenberg.js`
　　├─ `s2j-media-library-date-corrector-frontend.js`
　　└─ `s2j-media-library-date-corrector-classic.js`
```

**注記:** `WP_List_Table` を直接継承するのではなく、`manage_media_custom_column` 等のフィルターと `bulk_actions-upload` 等で拡張する想定です。ファイル名は実装時に確定します。

## 主要ファイルの責務

| 領域 | 役割 |
|------|------|
| メインプラグインファイル | 定数・バージョン・ファイルパス、`plugins_loaded` でコアクラスを起動し、翻訳をロードします。 |
| `Media_Date_Service` (想定クラス名) | `_wp_attached_file` から `yyyy/mm` を抽出し、`post_date` と比較し、単体/一括の DB 更新を行います。副作用をここに集約します。 |
| REST コントローラ | 管理画面・将来の WP-CLI からコール可能な API です。入力検証、権限、`service` の呼び出しを担います。 |
| 管理画面 JS (`src/admin`) | 一覧の操作 UI、ローディング、REST との通信 (`api-fetch` 等) を扱います。見た目の状態遷移は [管理画面 UI 仕様](./admin_ui_spec.md) に従います。 |
| Gutenberg/Classic/Frontend | [ブロック仕様](./block_spec.md) に従います。本プラグインの主機能は管理画面にあるため、ブロックは補助的に扱い、将来拡張も許容します。 |

## レイヤー責務

### UI レイヤー

* 選択状態の管理
* API のコール
* 状態の表示

### API レイヤー

* 認証・認可
* 入力の検証
* レスポンスの整形

### サービスレイヤー

* 日付補正を担います。
* 差分を判定します。

### データレイヤー

* `post_date` の更新
* meta の取得

## 責務分離ポリシー - What と How

本プロジェクトでは、仕様 (What) と実装 (How) を明確に分離します。

### 仕様 - What

以下は、[管理画面の UI 仕様](./admin_ui_spec.md) にて定義します。

* UI の構造
* 操作仕様
* 状態遷移
* スコープ定義
* 表示ルール

### 実装 - How

本ドキュメントでは、以下を定義します。

* API コール方法 (`api-fetch`)
* state 管理方式
* ミドルウェア構成
* エラーハンドリング
* 非同期処理 (チャンク、リトライ)

### 境界ルール

| 項目 | 記述先 |
| ---------------------- | ---------------- |
| UI の見た目・動き | [管理画面の UI 仕様](./admin_ui_spec.md) |
| データ取得方法 | [アーキテクチャー](./architecture.md) |
| state の型・構造 | [アーキテクチャー](./architecture.md) |
| UX 要件 (ページ跨ぎ選択など) | [管理画面の UI 仕様](./admin_ui_spec.md) |
| 実装方法 (sessionStorage 等) | [アーキテクチャー](./architecture.md) |

### 設計方針

* UI 仕様は、技術に依存しない形で記述します。
* 実装詳細は、すべて [アーキテクチャー](./architecture.md) に集約します。
* 両者は、参照関係を持ちますが、重複しません。

## 権限設計 (Capabilities)

本プラグインは、WordPress の権限モデルにもとづき、操作画面と設定画面で異なる権限を適用します。

### メディア補正画面

* capability: `upload_files`
* 対象ユーザー: 投稿者以上

理由:

* メディア操作権限と整合しているためです。
* 既存のメディア管理フローに準拠するためです。

### 設定画面

* capability: `manage_options`
* 対象ユーザー: 管理者のみ

理由:

* システム全体に影響し得る設定を扱うためです。

### REST API

* nonce による認証が必須です。
* capability のチェックを、必ず行います。

### REST API における前提 capability

REST API は、メディア補正画面と同様に、`upload_files` を前提とします。

これにより、UI と API の権限モデルを統一します。

### 設計方針

* 権限は、「最小権限の原則」に従います。
* UI と API で、同一の権限チェックを行います。

### nonce 設計 (REST API)

REST API コールは、WordPress の nonce による認証が必須です。

#### 使用方式

* `wp_create_nonce('wp_rest')` を使用します。
* クライアントは `X-WP-Nonce` ヘッダーとして送信します。

#### フロントエンド実装

* `wpApiSettings.nonce` を利用します。
* `@wordpress/api-fetch` により自動付与されます。

#### 検証

* WordPress REST API の標準機構により検証されます。
* nonce が無効な場合は、`401` を返します。

#### 設計方針

* Cookie 認証と nonce による、CSRF 対策を採用します。
* 独自トークンは導入しません。WordPress 標準に準拠します。

#### 注意点

* nonce は、「認証」ではなく「CSRF 対策」です。
* capability チェックとの組み合わせが必須です。

### capability の粒度設計

本プラグインでは、WordPress 標準 capability を基本とします。過度な細分化は行いません。

#### 基本方針

* 既存 capability を優先して使用します。
* (初期リリースでは) カスタム capability は導入しません。

#### 採用する capability

| 機能 | capability |
| ------ | ---------------- |
| メディア補正 | `upload_files` |
| 設定変更 | `manage_options` |

#### 粒度の考え方

本プラグインは、以下の理由により、細分化を行いません。

* 機能が、単一責務 (日時補正) にとどまるためです。
* WordPress の既存権限モデルと整合しているためです。
* 権限管理の「複雑化の回避」を維持するためです。

#### 将来的な拡張

必要に応じて、以下のカスタム capability 名の導入を検討します。

* `s2j_correct_media_date`
* `s2j_manage_settings`

ただし、その導入検討は、次の条件をすべて満たす場合に限ります。

* 機能が増加し、責務が分離されていること。
* 権限分離の要件が、明確になっていること。

#### REST API における適用

* 各リクエストごとに `current_user_can` を実行します。
* ID 単位で権限チェックを行います。

#### 設計方針

* 「最小権限の原則」を維持します。
* UI と API の権限を一致させます。

### 適用レイヤー

* UI レイヤー: 表示の制御
* API レイヤー: 権限のチェック
* サービスレイヤー: 実行可否の判断

### permission_callback 実装例

REST API の各エンドポイントでは、`permission_callback` により、認可チェックを実装します。

#### 基本実装

```php
register_rest_route(
  's2j/v1',
  '/media/date-correct',
  [
    'methods'  => 'POST',
    'callback' => [ $this, 'handle_date_correct' ],
    'permission_callback' => [ $this, 'can_correct_media' ],
  ]
);
```

#### `permission_callback` の実装

```php
public function can_correct_media( WP_REST_Request $request ) {
  // 基本権限チェック
  if ( ! current_user_can( 'upload_files' ) ) {
    return new WP_Error(
      'rest_forbidden',
      __( 'You do not have permission to correct media.', 's2j-media-library-date-corrector' ),
      [ 'status' => 403 ]
    );
  }

  // ID 単位の追加チェック (任意)
  $ids = $request->get_param( 'ids' );

  if ( is_array( $ids ) ) {
    foreach ( $ids as $id ) {
      if ( ! current_user_can( 'edit_post', $id ) ) {
        return new WP_Error(
          'rest_forbidden',
          __( 'You cannot edit one or more items.', 's2j-media-library-date-corrector' ),
          [ 'status' => 403 ]
        );
      }
    }
  }

  return true;
}
```

#### 設計ポイント

* capability は、「操作単位」と「対象単位」の2段階でチェックします。
* エラーは、`WP_Error` で統一します。
* ステータスコードは `403` を返します。

#### 方針

* `permission_callback` を省略することはしません。必ず定義します。
* ロジックは、Controller に集約します。

### `api-fetch` ミドルウェア設計

管理画面の API 通信は、`@wordpress/api-fetch` を使用します。共通ミドルウェアを導入します。

#### 目的

* nonce の自動付与
* エラーハンドリングの統一
* ログ・デバッグの一元化

#### 基本設定

```ts
import apiFetch from '@wordpress/api-fetch';

apiFetch.use( apiFetch.createNonceMiddleware( wpApiSettings.nonce ) );
```

#### カスタムミドルウェア

```ts
apiFetch.use( ( options, next ) => {
  return next( options ).catch( ( error ) => {
    console.error( 'API Error:', error );

    // 共通エラーハンドリング
    if ( error.code === 'rest_forbidden' ) {
      alert( '権限がありません' );
    }

    throw error;
  });
});
```

#### 設計方針

* nonce は、middleware で一元管理します。
* 各コンポーネントで、個別付与しません。
* エラーハンドリングは、共通化します。

#### 拡張ポイント

将来的に、以下の機能追加を検討します。

* リトライ処理
* ローディング管理
* 通信ログ収集

#### 注意点

* middleware は、グローバルに1回だけ登録します。
* 多重登録を防ぎます。

### フロント状態管理 (error / success / retry)

フロントエンドは、REST API のレスポンスに応じて、明確な状態遷移を持ちます。

#### 状態定義

UI 状態は、REST API の `status` と一致させます。

* idle: 初期状態
* loading: API ロード中
* success: 全件成功
* partial: 一部成功
* error: 全体失敗

#### 状態遷移

```mermaid
flowchart TD
  A["idle"] --> B["loading"]
  B --> C["(success | partial | error)"]
```

#### state 構造 (例)

```ts
{
  status: 'idle' | 'loading' | 'success' | 'partial' | 'error',
  message: string | null,
  summary: {
    success: number,
    failed: number,
    skipped: number
  }
}
```

#### UI 挙動

* success:
  * 「成功メッセージ」を表示します。
  * 一覧を再ロードします。
* partial:
  * 「警告メッセージ」を表示します。
  * エラー件数を明示します。
* error:
  * 「エラーメッセージ」を表示します。
  * 再試行可能な状態にします。

#### リトライ処理の設計

フロントエンドにおけるリトライ処理は、REST API 仕様に従って実装します。

#### 基本動作

* API レスポンスの `results` を解析し、`error` のみを抽出します。
* 抽出した ID を、同一エンドポイントに再送信します。
* ユーザー操作により、再送信します。

#### 状態管理

* `failed > 0` の場合は、再試行可能な状態にします。
* 再試行後は、レスポンスにもとづき状態を更新します。

#### リトライ仕様

リトライ対象および挙動の詳細は、REST API 仕様に従います。

* 参照: [REST API 仕様 > リトライ仕様 (統一定義)](./rest_api_spec.md#リトライ仕様-統一定義)

#### 設計方針

* 状態は、単一の「state machine」として扱います。
* 表示と状態を分離します。
* API レスポンスを、そのまま UI 状態にマッピングします。

### 認証・認可フロー

```mermaid
flowchart TD
  A["UI"] --> B["nonce 付与"]
  B --> C["REST"]
  C --> D["nonce 検証"]
  D --> E["capability チェック"]
  E --> F["実行"]
```

## 処理フロー (レイヤー横断)

```mermaid
flowchart TD
  A["UI"] --> B["REST API"]
  B --> C["Service"]
  C --> D["Repository"]
  D --> E["DB"]
```

* UI: 選択と実行
* REST: 認証とバリデーション
* Service: 補正ロジックの実行
* Repository: 更新処理

### REST API レスポンス仕様

本プラグインの REST API は、一括処理を前提とします。
成功/部分成功/失敗を明示的に区別する、レスポンス構造を採用します。

#### レスポンス構造

```json
{
  "status": "success | partial | error",
  "summary": {
    "total": 10,
    "processed": 10,
    "success": 8,
    "skipped": 1,
    "failed": 1
  },
  "results": [
    {
      "id": 123,
      "status": "success",
      "message": "updated"
    },
    {
      "id": 124,
      "status": "skipped",
      "message": "already_correct"
    },
    {
      "id": 125,
      "status": "error",
      "message": "permission_denied"
    }
  ]
}
```

#### ステータス定義

| status | 意味 |
| ------- | ---- |
| success | 全件成功 |
| partial | 一部成功 |
| error | 全件失敗 |

#### 設計方針

* HTTP ステータスとは別に、業務ステータスを持ちます。
* 部分成功を許容します。
* UI は、summary を主に参照します。

#### HTTP ステータス

| ケース | HTTP |
| --------------------- | ---- |
| 正常 (success/partial) | 200 |
| 認可エラー | 403 |
| 入力エラー | 400 |
| サーバーエラー | 500 |

## 冪等性 (べきとうせい)

同一 ID に対して同一処理を複数回実行しても、結果に変化はありません。

### 実現方法

* `match` の場合は、更新しません。
* `mismatch` のみ更新します。

## 技術スタック

| 層 | 採用技術 | 備考 |
|----|----------|------|
| 基盤 | WordPress 6.3+ (README の下限に準拠) | メディアは `attachment` 投稿タイプです。 |
| サーバー | PHP (WordPress 要件に準拠) | 直接 SQL は `wpdb` 経由に限定します。 |
| 管理 UI | React、TypeScript、`@wordpress/element`、`components`、`i18n` 等 | README 記載の方針。 |
| ビルド | Vite、Dart Sass、PostCSS (Autoprefixer) | ビルドの定義は、`vite.config.ts` にあります。 |
| スタイル | SCSS | スタイルのソースは、`src/styles/*.scss` です。 |

## ビルド

### ビルドターゲット

`vite.config.ts` の `npm_lifecycle_event` から対象を判定します。

| ターゲット | エントリ (想定) | 用途 |
|------------|------------------|------|
| `admin` | `src/admin/index.tsx` | メディアライブラリ一覧の拡張 UI です。 |
| `gutenberg` | `src/gutenberg/index.tsx` | ブロックの登録とエディター UI です。 |
| `classic` | `src/classic/index.ts` | Classic エディター側の補助処理です。 |
| `frontend` | `src/frontend/media-library-date-corrector.tsx` | ブロックのフロント表示です。 |

`gutenberg` ビルド時は `src/gutenberg/media-library-date-corrector/block.json` を `dist/blocks/...` にコピーします (`vite-plugin-static-copy`)。

### 外部化

Rollup の `external` に `@wordpress/*`、`react`、`react-dom`、`jquery` を指定し、管理画面で WordPress がすでに提供しているグローバル (`wp.*`、`React` 等) にマッピングします。

### 出力

* 出力先は、ディストリビューションのルートの `dist` にします。`emptyOutDir: false` により、ターゲット間の連続ビルドを想定します。
* `FLUSH_DIST=true` の場合、ビルド前に `dist` を削除できます。
* 本番時は、`NODE_ENV=production` を設定します。成果物を縮小 `minify` します。

> **実装上の注意:** 現行 `vite.config.ts` の成果物ファイル名に別プロジェクト由来の接頭辞が含まれる場合は、リリース前にプラグインスラッグへ統一することを推奨します。

## 実行ロジック (エンドツーエンド)

以下は [コンセプト](./concept.md) の「補正ロジック」と [管理画面 UI 仕様](./admin_ui_spec.md) の操作をサーバー/クライアントに分割した流れです。

```mermaid
sequenceDiagram
  participant User as 管理者
  participant WP as WordPress (一覧・権限)
  participant UI as 管理 UI (React)
  participant REST as REST API
  participant Svc as Media_Date_Service
  participant DB as wp_posts / postmeta

  User->>WP: メディア一覧の表示
  WP->>UI: スクリプト・データの初期化
  UI->>REST: 差分確認・補正対象 ID (任意でプレビュー)
  REST->>Svc: 権限チェック後に処理を委譲
  Svc->>DB: _wp_attached_file を取得し post_date を比較・更新
  Svc-->>REST: 結果 (成功、スキップ、エラーの集計)
  REST-->>UI: JSON レスポンス
  UI-->>User: メッセージと一覧の再描画
```

1. **表示**:
  * メディア一覧で標準カラムに加え、「年月 (パス)」「差分」を表示します (PHP フィルターまたは初期データと REST の組み合わせです。実装方針は一覧のデータ取得コストに応じて選択します)。
2. **選択**:
  * ユーザーがチェックボックスで対象を選ぶか、「差分のみ選択」等を行います。
3. **実行**:
  * UI が REST へ補正リクエストを送ります。サーバー側で **各添付ファイルごと** に `current_user_can` を検証します。
4. **更新**:
  * `Media_Date_Service` が `yyyy/mm-01 00:00:00` (サイトのタイムゾーン) へ `post_date` をそろえ、必要に応じて `post_date_gmt` も整合させます (詳細は [データ辞書 > 日付正規化とタイムゾーン](./data_dictionary.md#日付正規化とタイムゾーン) を参照します)。
5. **完了**:
  * UI が成功/失敗を表示し、一覧を更新します。

バッチ件数が大きい場合は、REST でチャンク処理を行うか、バックグラウンドキュー (将来拡張) を検討します。
初期実装では、「1リクエスト = 限定件数」としてタイムアウトを避けます。

## 共通仕様との関係

プラグイン全体の規約・品質・セキュリティの共通ルールは、[WP_PLUGIN_SPEC.md](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/WP_PLUGIN_SPEC.md) に従います。
