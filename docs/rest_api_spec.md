<!--
目的：「REST API エンドポイント、リクエスト/レスポンス、権限、セキュリティ、nonce」の明文化
-->

# S2J MediaLibrary Date Corrector - REST API 仕様

## 共通事項

### 名前空間とバージョン

| 項目 | 値 (想定) |
| --- | --- |
| Namespace | `s2j-mldc` |
| Version | `v1` |

完全パス例は、右記のとおりです: `/wp-json/s2j-mldc/v1/...`

名前空間の文字列は、プラグイン定数 `S2J_MLDC_REST_NAMESPACE` の1ヵ所だけに置きます。値は `s2j-mldc/v1` です。

初期リリースのルートは、下記の2つです。

* `POST /wp-json/s2j-mldc/v1/attachments/correct`
* `POST /wp-json/s2j-mldc/v1/attachments/correct-query`

一覧の GET、`/attachments/analyze`、`dryRun`、`correct-many`、`/attachments/retry` は、作りません。

### 契約の置き場所

初期リリースのフィールドの形は、[データ辞書](./data_dictionary.md#typescript-型定義-完全版) の型です。管理画面の TypeScript は、この型を手で写します。

対応する型は、下記です。

* `APIResponse` (`correct`)
* `CorrectQueryResponse` (`correct-query`。`APIResponse` + `nextOffset`)
* `Summary`
* `ResultItem`

権限、ステータス、`nextOffset` などの振る舞いは、本仕様に書きます。フィールド名と型は、データ辞書を指します。

パス表記: 本ファイルの初出はフルパス `/wp-json/s2j-mldc/v1/...` です。以降の表・本文では、同じ REST 名前空間を前提に `/attachments/...` と短縮してかまいません。

サーバーの実行時チェックは、`register_rest_route` の `args` です。PHP は zod を実行しません。

OpenAPI、zod、生成スクリプトは、初期リリースに置きません。`openapi.json` も置きません。

後から機械可読な契約を足す場合は、向きは1つです。データ辞書と本仕様から OpenAPI を作り、そこからクライアントの型を生成します。zod は、ブラウザが応答を確かめる任意の層です。書き込みの正本にはしません。

設計方針の置き場所は、[アーキテクチャー > ソース・オブ・トゥルース](./architecture.md#ソースオブトゥルース-source-of-truth) です。

### 要求ヘッダー

| ヘッダー | 必須 | 説明 |
| --- | --- | --- |
| `X-WP-Nonce` | ログインユーザー操作時は **必須** である。 | `wp_create_nonce('wp_rest')` の値である。`api-fetch` はデフォルトで付与する。 |
| `Content-Type` | POST または PUT の場合 **必須** である。 | `application/json` とする。 |

### レスポンス形式

* 成功時: `application/json` とする。本文は各エンドポイントの定義に従う。
* エラー時: WordPress REST のエラーオブジェクト (`code`、`message`、`data.status`) を返す。

#### 型定義との対応

本レスポンス構造は、[データ辞書 > TypeScript 型定義 (完全版)](./data_dictionary.md#typescript-型定義-完全版) に準拠します。

* 参照:
  * `APIResponse` / `CorrectQueryResponse`

### 一括処理レスポンス (本文の共通形)

補正・一括更新など **複数添付を扱うエンドポイント** は、HTTP が成功した場合でも件ごとに成功・スキップ・失敗が混在し得るため、**業務上の成否は、レスポンス本文** で表現します (つまり、HTTP ステータスだけでは判定しません)。

* **トップレベル `status`**
  * 全体の結果区分である。
  * 値は `success` (失敗がなく、処理が最後まで終わった)、`partial` (失敗とそれ以外が混ざる、または未処理が残る)、`error` (処理した件がすべて失敗) である。`skipped` だけでは `partial` にしない。
* **`summary`**
  * UI やログ向けの統計である。
  * `total` はその1リクエストの対象件数、`processed` は試行件数、ほかに `success`、`skipped`、`failed` を含む。一覧の全件数ではない。
* **`results`**
  * 添付 ID ごとの結果である。
  * 各要素は [データ辞書の `ResultItem`](./data_dictionary.md#resultitem) である。`id` と **`status`** (`success`、`skipped`、`error`) を持つ。
  * **`message`** は人間が読める文である。`status` が `error` の場合は必須である。完了の通知に出すのは、この `error` の文と、`message` がある `skipped` である。`success` の `message` は出さない。

HTTP ステータスと業務 `status` の対応は、[HTTP ステータス・コード](#http-ステータスコード-指針) に従います。

#### 設計方針 (規約)

* ページ分割は、メディアライブラリのリストテーブルが行う。`summary.total` からは組み立てない。
* 一覧の「全 N 件」には、パスから年月を読めない行も含まれる。

#### `summary.total` の定義

`summary.total` は、その1リクエストが処理対象にした件数です。

* `/attachments/correct` では、送った ID の件数である。行アクションやチェックで入った、パスから年月を読めない行も含まれる。結果は `skipped` である。
* `/attachments/correct-query` では、その回の **補正の試行件数** である。パスから年月を読めない行は走査対象には入るが補正対象には入れないため、`summary.total` には含めない。
* 続きに進むかは `processed === total` かつ `nextOffset !== null` である。`offset` / `nextOffset` は WP_Query 結果上の走査位置である。1リクエストは最大100件を走査し、パスから年月を読める件だけを補正する。`processed === total` かつ `summary.total` が100未満でも、`nextOffset !== null` の場合は続きがある。
* `processed < total` は、そのリクエストの途中で止まったことである。Date Correct (All) では一連をやめ、次の走査窓は自動送信しない。
* Date Correct (All) の一連の集約は、[複数走査窓の集約 - Date Correct (All)](#複数走査窓の集約---date-correct-all) に従う。
* 初期リリースでは、「全体の何件中」は出さない。出す場合は、`summary.total` とは別の名前で、補正できる件数を返す。ページ分割には使わない。

#### 件別メッセージ

`results[].message` は、人間が読める文です。機械可読コードにはしません。

* 文は、サーバーがリクエストのロケールで組み立てる (gettext)。
* UI は、コードから文言に変換しない。完了の通知に出すのは、`error` の `message` と、`message` がある `skipped` である。`success` の `message` は出さない。
* 再試行の判定は、文言ではなく `status === "error"` で行う。

リクエスト全体が失敗する場合の `WP_Error` は、WordPress 標準の `code` と `message` です。こちらも `message` は人間が読める文です。

### 結果集計ルール (status、summary の算出)

一括処理におけるトップレベル `status` および `summary` の算出ルールを、下記のとおり定めます。

#### 設計意図 (ゴール)

* `skipped` を `success` に含めないことで、「実際に変更があったか」を明確にする。
* 全件失敗を `error` とすることで、UI 上で異常を明確に検知できる。
* 部分成功 (`partial`) を中心に設計することで、バッチ処理の実用性を高める。

* [冪等性 (べきとうせい)](./architecture.md#冪等性-べきとうせい) を維持するため、成功済みデータは再処理しない。
* `skipped` は、再処理しても結果が変わらないため、再試行しない。年月がすでに一致している場合と、`correct` でパスから年月を読めない場合である。
* 手動の再試行は、`error` のみを対象にする。パスが読めない件は `error` ではない。
* 自動再送は、応答本文が得られなかった場合だけである。HTTP `200` の件別 `error` は、自動再送に入れない。

* 未処理は、`total - processed` で分かる。画面は警告だけ出す。

#### 設計方針 (規約)

* API は、処理済み要素のみを、`results` として返す。
* 未処理の ID は、`results` に入れない。件数は `total - processed` である。
* `not_processed` と `includeNotProcessed` は、初期リリースに置かない。

* 件別の `error` は、ユーザーが再試行を選んだ際だけ再送する。応答本文がない場合の自動再送は、[チャンク再試行戦略](#チャンク再試行戦略-バックオフ) に従う。
* [冪等性 (べきとうせい)](./architecture.md#冪等性-べきとうせい) を前提とする (安全に再実行可能である)。
* UI の見出しは `summary` である。`results` は、完了の通知に出す件別の `message` と、Retry Failed の ID に使う。デバッグ出力にはしない。

#### `status` の決定ルール

トップレベル `status` は、件別結果 (`results[].status`) にもとづき、下記の優先順位で決定する。

1. **`processed < total` の場合**

   * `status = "partial"`
   * 未処理が残っているためである。

2. **処理した件がすべて `error` の場合**

   * `status = "error"`
   * たとえば、受理した全件が権限不足で失敗した場合が該当する。

3. **`error` があり、`success` または `skipped` もある場合**

   * `status = "partial"`
   * 再試行する件があるためである。表示は警告である。

4. **`error` が1件もない場合**

   * `status = "success"`
   * `skipped` だけでも、`success` と `skipped` が混ざるだけでも、同じである。
   * 一致によるスキップと、パスから年月を読めないスキップは、ここでは区別しない。

#### `skipped` の扱い

`skipped` は失敗ではありません。更新の有無は `summary` で見ます。

* `success`: 実際に更新が行われた件数
* `skipped`: 更新しなかった件数である。年月がすでに一致している場合である。パスから年月を読めない場合の `skipped` は `correct` のみである (`correct-query` では補正対象外)

`error` が1件もなく、`processed` が `total` と一致する場合は、トップレベル `status` を `success` とします。

#### `unknown` の扱い

パスから年月を読めない要素の一覧状態は、`unknown` です。`ResultItem.status` には使いません。

パスから年月を読めない件を `skipped` にするのは **`correct` のみ** です。エンドポイントごとの扱いは下記のとおりです。

| エンドポイント | パスから年月を読めない件 |
| --- | --- |
| `correct` | 対象に含め、更新せず HTTP `200` の `skipped` である。`results` に出す |
| `correct-query` | 走査はするが **補正対象には入れない**。`results` にも出さない。走査位置だけ進める |

* `correct` で `message` を付ける場合は、人間が読める文だけである。例は「ファイルパスから年月を読み取れないため、補正しません。」である。件別の機械可読コードは置かない。
* 再試行の対象にはしない。`correct` では `summary.skipped` に含め、`summary.failed` には含めない。
* `post_date` が読めなくても、パスの年月が取れる場合は更新する。この件は `skipped` にしない。

#### `summary` の集計ルール

`summary` は、下記のように算出します。

* `total`: その1リクエストの対象件数である。一覧の全件数ではない。
* `processed`: 実行の試行件数である。通常は `total` と一致する。
* `success`: 更新成功の件数
* `skipped`: 処理不要の件数
* `failed`: `error` 件数

#### 実行不完全ケース (`processed != total`) の扱い

通常、`processed` は `total` と一致しますが、下記のような場合に不一致が発生し得ます。

* サーバー側で、(タイムアウトや例外などにより) 処理が途中で中断された
* チャンク処理中に部分失敗の発生

この場合、下記のルールを適用します。

* `processed < total` の場合、トップレベル `status` は必ず `partial` とする。
* 未処理の件は、`results` に含まれない (つまり、未試行として扱う)。

#### 未処理の件数

未処理の件数は、`total - processed` です。`processed < total` の場合、全体の `status` は `partial` です。

未処理の ID は `results` に入りません。`not_processed` も `includeNotProcessed` も置きません。画面は警告だけ出します。自動再送の3回にも、Retry Failed にも入れません。続けるかは、警告を見たユーザーが決めます。

Date Correct (All) で `processed === total` かつ `nextOffset !== null` の場合の次の走査窓は `nextOffset` です。`processed < total` とは別です。`processed < total` の場合は一連をやめ、`nextOffset !== null` でも次窓は自動送信しません (詳細は [複数走査窓の集約 - Date Correct (All)](#複数走査窓の集約---date-correct-all))。

#### 補足

`processed < total` の場合は、下記のとおりです。

* 一部の要素が、まだ処理されていないことを意味する。
* トップレベル `status` は、`partial` とする。

#### リトライ仕様 (統一定義)

本プラグインにおけるリトライ (再試行) の仕様は、本節が唯一の正です。
他仕様 ([管理画面の UI 仕様](./admin_ui_spec.md)、[アーキテクチャー](./architecture.md)) は、本定義を参照します。

#### リトライの二種類

再送は、下記の2つに分けます。

* 自動再送は、応答本文が得られなかった場合だけである。同じチャンクを再実行する。詳細は [チャンク再試行戦略](#チャンク再試行戦略-バックオフ) である。
* 手動の Retry Failed は、HTTP `200` の `results` のうち `status === "error"` の ID だけである。`/attachments/correct` に再送する。

HTTP `200` で `APIResponse` が返った時点で、自動再送はやめます。

#### リトライ対象の定義

手動の再実行は、下記の対象に対して行います。

* 対象は `results[].status === "error"` のみである。

#### リトライ対象に含めないもの

下記は、リトライ対象としません。

* `success` (すでに成功)
* `skipped` (処理不要)
* 未処理。ID は `results` にないため、Retry Failed では送れない。

#### リトライ方法

クライアントは、前回レスポンスの `results` から対象 ID を抽出し、`/attachments/correct` に再送信します。

```json
{
  "ids": [/* error のみ */]
}
```

#### リトライの前提

* 本処理は、冪等 (べきとう) であること
  * 同一リクエストを複数回実行しても、結果は変わらないこと

#### チャンク再試行との関係

* 応答本文がない場合の自動再送は、[チャンク再試行戦略](#チャンク再試行戦略-バックオフ) に従う。
* HTTP `200` のあと、各チャンクで手動再送するのは `error` だけである。

#### UI/クライアントとの関係

* UI は、`summary.failed` を参照して、再試行可否を判断する。
* 実際の対象 ID は、`results` から取得する。

初期リリースでは、専用の retry エンドポイントは作りません。Retry Failed は `/attachments/correct` を再利用します。

#### UI への影響

* UI は、`summary.failed` をもとに「再試行の対象件数」を表示する。
* `processed < total` の場合は、「一部未処理」として警告表示を行う。

#### リトライ UI 設計 (ボタン/UX)

UI は、一括処理の結果にもとづき、ユーザーが再試行 (リトライ) を行えるよう設計します。

#### 基本動作

* `summary.failed > 0` の場合にのみ、リトライ操作を有効化する。
* UI 上に「再試行 (Retry Failed)」ボタンを表示する。

#### リトライ対象

* `results[].status === "error"` の ID のみを対象とする。

#### UX 挙動

* ボタン押下時、対象 ID を再度 `/correct` に送信する。
* 実行後、結果を再集計し UI を更新する。

#### 表示仕様

* 「失敗件数 (n 件)」を、ボタン付近に表示する。
* `processed < total` の場合は、「未処理あり」の警告を併記する。

## 権限 (Capability)

原則として **メディアを編集できるユーザー** のみが、対象添付に対する **読取/更新** を実行できます。

| 操作 | 条件 (想定) |
| --- | --- |
| `correct` と `correct-query` | ルートは `upload_files` を見る。各 ID の `edit_post` 不足は、HTTP `200` の件別 `error` である。 |

補正 API は、option の値では拒みません。`s2j_mldc_mismatch_notice` は、メディアライブラリの案内だけを切り替えます。

### 前提 capability

本 REST API は、**メディア操作権限 (`upload_files`) を持つユーザーを前提** とします。

#### 設計方針 (規約)

* 「操作可能か」と「対象は、編集可能か」を分離する。
* 一括処理では、件別判定により `partial` を許容する。

#### 基本方針

* すべての書き込み系 API (補正処理) は、`upload_files` を必須とする。
* 本 capability を満たさない場合、リクエストは HTTP レベルで拒否される。

```php
current_user_can('upload_files')
```

* メディアライブラリ操作権限との整合を取るためである。
* 管理画面 (メディア配下 UI) と同一の、権限モデルに統一するためである。
* 最小権限の原則に従うためである。

#### ID 単位の権限チェックとの関係

`upload_files` は、「操作可能か否か」の前提条件です。実際の処理可否は、さらに下記で判定します。

```php
current_user_can('edit_post', $id)
```

#### 権限チェックのレイヤー

| レイヤー | 内容 |
| --- | --- |
| HTTP レベル | `upload_files` がない場合は `rest_authorization_required_code()` である。未ログインは `401`、ログイン済みは `403` である。無効な nonce は、コアが先に `403` を返す。 |
| 件別処理レベル | `edit_post` により、個別に判定する。 |

### 一括更新における権限の制御方針

本プラグインの一括更新 (複数 `attachment` を対象とする操作) では、HTTP レベルと件別処理レベルを分離し、**部分成功 (partial) を前提とした設計** を採用します。

#### 設計理由

* バッチ処理において、実用性に優れているためである。
* WordPress REST API の一般的な運用に準拠するためである。
* UI における「成功件数/失敗件数」の表示や再試行に適しているためである。

#### HTTP レベルの扱い

下記のいずれかに該当する場合は、リクエスト全体を拒否します。件別の `results` は返しません。

* 未ログインで `upload_files` がない場合は、`401` である。`permission_callback` が `rest_authorization_required_code()` を返す。
* `X-WP-Nonce` が `wp_rest` と合わない場合は、`403` である。コアの `rest_cookie_check_errors` が先に返す。コードは `rest_cookie_invalid_nonce` である。プラグインは、ここで `401` を返す処理を置かない。
* ログイン済みで `upload_files` がない場合は、`403` である。同じく `rest_authorization_required_code()` である。

#### 件別処理の扱い (「部分成功」前提)

リクエストが受理された場合 (`HTTP 200`)、対象 ID ごとに個別の権限チェックを行います。

* 権限がある場合は、正常処理 (`success`) とする。
* 権限がない場合は、当該件のみ `status` を `error` とし、`message` に人間が読める文を入れる。
* すでに補正済みの場合は、`skipped` とする。
* `correct` でパスから年月を読めない場合も、当該件のみ `skipped` とする。`error` にはしない。`correct-query` では補正対象に入れない。

#### レスポンス表現

* HTTP ステータスは、`200` を返す。
* 業務上の成否は、本文の `status` および `summary`、`results` で表現する。

トップレベル `status` は、下記のとおりです。

| status  | 意味 |
| --- | --- |
| success | 失敗がなく、処理が最後まで終わった状態である。`skipped` だけも含む |
| partial | 失敗と、`success` または `skipped` が混ざる状態である。未処理が残る場合も含む |
| error | 処理した件がすべて失敗である |

#### 採用方針

本プラグインでは、下記を正式仕様とします。

* 件別結果を返す (ID 単位の成否を保持する)。
* 一部失敗を許容する (partial)。
* HTTP ステータスと業務ステータスを分離する。

## セキュリティ

公開用のエンドポイントは設けません。呼ぶ画面は、ログイン済みのメディアライブラリ一覧だけです。

| 観点 | 対策 |
| --- | --- |
| CSRF | 認証済みリクエストでは、**REST nonce** (`wp_rest`) を検証する。 |
| 認証 | ログインセッション (Cookie) とアプリケーションパスワード等の標準 REST 認証に準拠する。 |
| 入力検証 | `attachment` ID は、整数配列にキャストし、存在および `post_type === 'attachment'` を確認する。 |
| エスケープ | レスポンス表示用の文字列は、管理者 UI 側でも適宜エスケープする。 |
| レート制限 | コアに任せる。大量件数は、クライアントが100件ずつ送る。 |
| 情報漏洩 | エラーメッセージに、ファイルシステム絶対パスを含めない。 |

## Nonce と `api-fetch`

管理画面の JavaScript からは `@wordpress/api-fetch` を使用し、ルート URL が同一オリジンの場合 **Nonce ミドルウェア** が `X-WP-Nonce` を付与します。

手動で `fetch` する場合は、下記のとおりです。

```text
X-WP-Nonce: <wpApiSettings.nonce または wp_create_nonce('wp_rest')>
```

## 冪等性 (べきとうせい)

同一リクエストを複数回実行しても、結果は変わりません。実装に関しての詳細は、[アーキテクチャー > 冪等性 (べきとうせい)](./architecture.md#冪等性-べきとうせい) をご覧ください。

## 対象指定

Date Correct (All) の全件は、2つ目のクエリーが返す範囲です。

* 添付 ID は、`POST /attachments/correct` である。
* いまの検索とフィルターは、`POST /attachments/correct-query` である。許可するキーは、`s`、`m`、`post_mime_type`、`author`、`orderby`、`order` である。

## 制限

* 1リクエストあたり、最大100件である。
* 通常の操作は、クライアントが100件ずつ送る。ID の一括は、クライアントが分割する。
* `POST /attachments/correct` の `ids` が101件以上の場合は、1件も更新せず `HTTP 400` を返す。`register_rest_route` の `args` に `maxItems: 100` を置き、コアの検証に任せる。コードは `rest_invalid_param` である。
* この `400` は自動再送しない。自動再送は、応答本文がない場合だけである。先頭の100件だけを処理して残りを捨てる応答にはしない。
* Date Correct (All) は `correct-query` である。サーバーが最大100件ずつ走査し、続きに進むのは `processed === total` かつ `nextOffset !== null` の場合だけである。`processed < total` では次窓を自動送信しない。

## チャンク再試行戦略 (バックオフ)

大量件数の処理は、クライアント側でチャンク分割して実行します。自動再送は、応答本文が得られなかった場合だけです。

#### 設計方針 (規約)

* サーバー負荷を抑制します。
* ネットワーク失敗や、応答を読む前の HTTP 失敗に対応します。
* HTTP `200` の件別結果は、自動では再送しません。続ける判断はユーザーに委ねます。

#### チャンク処理

* 1リクエストあたり最大100件です。
* クライアントは、ID を分割して順次送信します。

#### 自動再送の対象

同じチャンクを再実行します。補正は冪等 (べきとう) ですので、すでに更新した件は次に `skipped` になります。

* ネットワーク失敗
* タイムアウト
* HTTP `408`、`429`、`500`、`502`、`503`、`504`

下記は自動再送に入れません。

* HTTP `400`、`401`、`403`
* HTTP `200` で `APIResponse` が返ったあとの件別 `error`
* `success` と `skipped`
* `correct-query` の `nextOffset` (ただし `processed === total` かつ `nextOffset !== null` の場合だけ次窓に進む)。これは次の走査窓に進む続きである
* `processed < total` の未処理。警告を出し、一連をやめる。`nextOffset !== null` でも自動では送らない。続けるかはユーザーが決める

#### 再試行戦略

応答本文が得られなかったチャンクは、下記の待ち方で再送します。

* 初回失敗後、一定の時間、待機して再試行する。
* 待機時間は、(たとえば、1→2→4のように) 指数的に増加させる。

#### 試行回数の最大値

* 同一チャンクの自動再送は、最大3回までとする。
* HTTP `200` で `APIResponse` が返った時点で、回数の途中でも自動再送をやめる。

#### フォールバック

* 3回でも応答がなければ、そのチャンクを失敗として出す。続きは手動である。
* 手動の Retry Failed は、`results` のうち `status === "error"` の ID だけを `/attachments/correct` に再送する。

## エンドポイント一覧

下記は [管理画面 UI 仕様](./admin_ui_spec.md) の一括/行操作を満たすためのルートです。実装時に URL、フィールド名をコードと完全一致させます。差分の表示は、PHP の列が行います。プレビュー用のルートは作りません。

### 添付ファイルの日付補正 (選択 ID)

**目的:** チェックされた添付、または行アクションの単件の `post_date` を補正します。

| 項目 | 内容 |
| --- | --- |
| Method | `POST` |
| Route | `/s2j-mldc/v1/attachments/correct` |
| Permission | ルートは `upload_files` である。各 ID の `edit_post` 不足は、HTTP `200` の件別 `error` と人間が読める `message` である。 |

**Request JSON (例)**

```json
{
  "ids": [ 101, 102 ]
}
```

| フィールド | 型 | 必須 | 説明 |
| --- | --- | --- | --- |
| `ids` | `number[]` | はい | 添付ファイルの ID である。最大100件である。行の Date Correct、一括の Date Correct、Retry Failed が送る。101件以上は `HTTP 400` の `rest_invalid_param` で、1件も更新しない。 |

**Response JSON (例)**

```json
{
  "status": "partial",
  "summary": {
    "total": 2,
    "processed": 2,
    "success": 1,
    "skipped": 0,
    "failed": 1
  },
  "results": [
    {
      "id": 101,
      "status": "success",
      "message": "日付を補正しました。"
    },
    {
      "id": 102,
      "status": "error",
      "message": "対象のメディアが見つかりません。"
    }
  ]
}
```

* すでに年月が一致している項目は、`status` を `skipped` とする。`message` を付ける場合は、人間が読める文にする。
* `correct` でパスから年月を読めない項目も `skipped` である。`error` にはしない。区別は `message` の文である。

### ID 配列の制約

リクエストで指定される `ids` は、下記の条件を満たすものとします。

#### 設計方針 (規約)

* 処理は、ID 単位で独立して行う。
* 入力順序に、依存しない。

#### 件数

* `args` の `maxItems` は100である。検証は、WordPress コアに任せる。
* 101件以上は、`HTTP 400` の `rest_invalid_param` である。1件も更新しない。先頭の100件だけを残す応答にはしない。

#### 一意性

* 同一 ID の重複は、許容しない。
* 重複が含まれる場合、サーバー側で排除する。

#### 順序

* ID の順序は、処理結果に影響しない。
* レスポンスも、順序を保証しない。

### 現在の一覧相当の、一括補正「Date Correct (All)」

**目的:** [管理画面 UI 仕様](./admin_ui_spec.md) に従い、「**現在の一覧 (検索・フィルター結果)** が対象」の全件を補正します。

表示中のページの「Date Correct」と行操作は、リストテーブルのフォームにある ID を `correct` に送ります。一覧取得用の API は使いません。

「Date Correct (All)」は、クライアントが ID を集めません。`upload.php` の検索・フィルターを許可リストで送り、サーバーが同じ `WP_Query` を再実行します。

| メソッド | ルート | 説明 |
| --- | --- | --- |
| `POST` | `/s2j-mldc/v1/attachments/correct-query` | 許可したクエリー引数に一致する attachment を、最大100件ずつ走査し、パスから年月を読める件だけを補正する。 |

**Request JSON (例)**

```json
{
  "query": {
    "s": "",
    "m": "0",
    "post_mime_type": "",
    "author": 0,
    "orderby": "date",
    "order": "desc"
  },
  "offset": 0
}
```

| フィールド | 型 | 必須 | 説明 |
| --- | --- | --- | --- |
| `query` | `object` | はい | `upload.php` の絞り込みである。許可リスト以外は無視する。 |
| `offset` | `number` | いいえ | WP_Query 結果上の走査位置である。省略時は `0` である。 |

許可する `query` のキーは、`s`、`m`、`post_mime_type`、`author`、`orderby`、`order` です。`paged` と `posts_per_page` は、対象範囲に使いません。サーバーが1回の **走査件数** を100件に固定します。

`m` はメディアライブラリと同じく、`post_date` の年月です。パスの年月では絞りません。

#### `offset` / `nextOffset` (走査窓)

| 語 | 意味 |
| --- | --- |
| `offset` / `nextOffset` | WP_Query 結果上の走査位置である (パスから年月を読めない件も含む) |
| 1リクエスト | `offset` から最大100件を走査し、パスから年月を読めるものだけを補正対象にする |
| `summary.total` | その回の補正の試行件数である (パスから年月を読めない件は含めない。年月一致は `skipped`) |
| `nextOffset` | `offset + 今回走査した件数` である。結果が尽きたら `null` である |

`processed === total` かつ `summary.total` が100未満でも、`nextOffset !== null` の場合は続きがあります (走査窓内にパスから年月を読めない件が多かった場合です)。続きに進むかは `processed === total` かつ `nextOffset !== null` で判断します。`summary.total` だけでは判断しません。`processed < total` の場合は一連をやめます。

走査窓内に補正対象が0件 (`summary.total === 0`) でも、`processed === total` かつ `nextOffset !== null` なら続きがあります。その窓のトップレベル `status` は規則どおり `success` (空の `results`) でかまいません。完了通知と一覧の再読み込みは、一連の終了時だけです (0件窓のたびに出しません)。

応答形はデータ辞書の `CorrectQueryResponse` です (`APIResponse` + `nextOffset`)。各 ID の権限確認と更新は、`correct` と同じサービスが行います。件別の形は `ResultItem` のままです。

#### 複数走査窓の集約 - Date Correct (All)

クライアントは、各窓で `processed === total` かつ `nextOffset !== null` の間、走査窓を順に送ります。一連の終了時だけ、完了通知を出します。

| 項目 | ルール |
| --- | --- |
| 次窓に進む条件 | `processed === total` かつ `nextOffset !== null` の場合だけである |
| `processed < total` | **一連をやめる**。`nextOffset` が非 null でも次窓は自動送信しない。それまで加算・連結した結果で警告する。未処理は Retry Failed の対象外である。続けるかはユーザー判断である |
| `summary` | 各応答の `total` / `processed` / `success` / `skipped` / `failed` を加算する |
| `results` | 各応答の配列を **連結** する (上書きしない) |
| 完了時の UI `status` | 加算後の件数から再計算する。`failed` 合計が1以上で success または skipped もある場合は `partial`。すべて失敗なら `error`。失敗がなければ `success` (`skipped` のみでも可) |
| Retry Failed | 連結後の `results` のうち `status === "error"` の ID だけを `/attachments/correct` に再送する |
| 件別 `message` | 連結後から、`error` と `message` がある `skipped` を完了通知に出す |

ID 一括のチャンク分割も同じです。あるチャンクが `processed < total` なら、残チャンクは自動で送りません。

窓の途中では、完了通知も一覧の再読み込みもしません。

#### 設計意図 (ゴール)

* UI と API の「責務」を分離する。
* 条件解釈の「二重実装」を防止する。

#### 設計方針 (規約)

* UI と API のスコープ定義を一致させる。
* 表示単位 (ページ) と処理単位 (対象集合) を分離する。
* 大量データに対しても、一貫した操作モデルを提供する。

#### スコープ定義 -「All」の意味

「Date Correct (All)」における「All」とは、**現在の検索・フィルター条件に一致する全件** を意味します。

#### ID 収集の責務

現在ページの補正では、対象 ID はリストテーブルのフォームにあります。

「All」の対象 ID は、サーバーが許可リストのクエリーから解決します。クライアントは ID を集めません。

#### 実装方針

* 現在ページの補正は、フォームの ID を `correct` に送る。100件を超える場合は、クライアントが分割する。101件以上を1回で送った場合は、サーバーが `HTTP 400` を返し、1件も更新しない。
* 「All」は、`correct-query` に許可したクエリー引数と `offset` を送る。
* サーバーは、その条件で `WP_Query` を再実行し、`offset` から最大100件を走査する。

#### ページネーションとの関係

本 API において、ページネーションはあくまで取得・表示のための分割であり、処理対象の範囲には影響しない。

* 「All」は、ページに依存しない論理的な集合を指す。
* `page` / `per_page` の値は、対象範囲の定義には使用されない。

#### 実装方式との関係

クライアントは、いまのライブラリクエリーを `correct-query` に送ります。サーバーが WP_Query を再実行し、`offset` から最大100件を走査します。パスから年月を読めない attachment は走査だけ進め、補正対象にも `results` にも入れません。パスから年月を読める件だけを更新します。`processed === total` かつ `nextOffset !== null` の場合、応答の `nextOffset` (`offset + 今回走査した件数`) を次のリクエストの `offset` に入れて再送します。`nextOffset` が `null` の場合は完了です。`processed < total` の場合は一連をやめます。一連の集約は [複数走査窓の集約 - Date Correct (All)](#複数走査窓の集約---date-correct-all) に従います。

#### フィルター条件の再現性

`correct` は ID リストだけを受け取り、フィルター条件は解釈しません。

`correct-query` は、許可リストにあるクエリー引数だけを解釈します。`paged` は対象範囲に使いません。

## HTTP ステータス・コード (指針)

**HTTP と本文 `status` の関係:** 認可・入力・サーバー例外は、適切な HTTP ステータス・コード (`401`/`403`/`400`/`500` 等) で返します。**リクエストとして受理し、件別結果を返す一括処理** では、件の成否が混在しても **`HTTP 200`** とします。全体の区分は、本文のトップレベル `status` および `summary` で表現します (WordPress 標準の REST エラーオブジェクトは、この時点では返しません)。

| コード | 用途 |
| --- | --- |
| `200` | 処理完了である (件別に失敗が混在しても、`200` と `results` で返す運用を許容する) |
| `400` | 入力が不正である。`ids` が101件以上の場合も含む。1件も更新しない。コードは `rest_invalid_param` である |
| `401` | 未ログインで `upload_files` がない場合である |
| `403` | nonce が無効な場合、またはログイン済みで `upload_files` がない場合である |
| `500` | 予期せぬサーバーエラーである |

## エラーコード

件別の形は、[件別メッセージ](#件別メッセージ) に従います。

* **REST エラー (`WP_Error` 相当)**
  * レスポンス全体が失敗する場合の `code` と `message` である。HTTP は `4xx` または `5xx` である。`message` は人間が読める文である。
* **件別 `results[].message`**
  * `200` 応答の本文内で、ID 単位の理由を表す人間が読める文である。完了の通知に出すのは、`error` の `message` と、`message` がある `skipped` である。`success` の `message` は出さない。

## 共通仕様との関係

認証、国際化、エラー表現の共通ルールは [SPECS.md](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/SPECS.md) に従います。
