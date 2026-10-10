<!--
目的：「モデルの型、設定配列、CPT、メタキー、option、データフロー、データ更新内容」の明文化
-->

# S2J MediaLibrary Date Corrector - データ辞書

## 投稿タイプ・カスタム投稿タイプ (CPT)

本プラグインは、**新しい CPT を登録しません**。対象は、WordPress コアの **メディア** のみです。

| 投稿タイプ | 用途 |
| --- | --- |
| `attachment` | メディアライブラリの各行に相当し、`post_date` の補正対象である |

## 用語 (本キット内)

| 用語 | 意味 |
| --- | --- |
| 差分 | 一覧の列名。セル値は「一致」「不一致」「不明」 |
| 不一致 | セル値および案内バナーのトリガー。コードは `mismatch` |
| 不整合 | 課題説明でパスと `post_date` のずれを指す語。UI の「不一致」と同義 |
| Date Correct | 行またはチェック選択の補正操作 (`POST …/attachments/correct`) |
| Date Correct (All) | 現在の検索・フィルター全ページの補正 (`POST …/attachments/correct-query`) |

## データベース上の主要フィールド

### `wp_posts` (attachment)

| カラム | 説明 | 本プラグインでの扱い |
| --- | --- | --- |
| `ID` | 添付ファイルの ID | 補正対象のキーである |
| `post_type` | 常に `attachment` | フィルター条件である |
| `post_date` | メディアの「日付」として、UI や年月フィルターに使用される | **補正の主対象である** (パスとの不一致の一方である) |
| `post_date_gmt` | UTC の日時 | `post_date` を変更する際、コアの慣例に合わせ **整合を取る** (サイトのタイムゾーン設定を考慮する) |
| `post_modified` / `post_modified_gmt` | 本文や添付の中身を編集した時刻 | **意図は維持**。取得済みの値を `wp_update_post` 配列に **必ず渡す** (キー省略禁止。省略するとコアが now にする) |

### `wp_postmeta`

| メタキー | 説明 | 本プラグインでの扱い |
| --- | --- | --- |
| `_wp_attached_file` | アップロード相対パス (例: `2017/12/bnr_nec.jpg`) | **年月抽出の Source of Truth** (パスとの不一致の他方) |

その他のメタ (`_wp_attachment_metadata` 等) は、本プラグインの **初期スコープでは読み取り専用** とします。寸法・サムネイルパスと日付の矛盾を直す要件が出た場合は、別タスクで拡張します。

#### 設計方針 (規約)

* パスから年月を読めない場合は、エラーではなく一覧の `unknown` とする。`correct-query` では補正対象にも `results` にも入れない。`correct` に送られた場合だけ `skipped` とし、`error` にはしない。

#### パス形式の前提と例外

本プラグインは、`_wp_attached_file` の先頭が `yyyy/mm/` であることを前提とします。値は、アップロード基準ディレクトリからの相対パスです。例は `2017/12/file.jpg` です。

切り出しは、下記の正規表現だけを使います。

```text
^(\d{4})/(0[1-9]|1[0-2])/
```

年は1000以上、9999以下です。この範囲外は、正規表現に合っても読めないものとして扱います。MySQL の `DATETIME` が受け付けないためです。

月はゼロ埋めの `01` から `12` です。文字列の途中にある数字は使いません。`2017/13` や `2017/1/file.jpg` は読みません。

#### 非対応ケース

下記の場合は、一覧を `unknown` とします。`correct` に入った場合は、更新せず `skipped` とします。`correct-query` では補正対象にしません。

* 先頭が、上記の正規表現に合わない
* 年が1000未満、または9999を超える
* `_wp_attached_file` が未設定または空
* カスタムディレクトリ構成で、先頭が `yyyy/mm/` でない

## 更新ルール

補正で変更する/しないフィールドの方針を、下記の **更新ポリシー** に集約します。

## 更新ポリシー

* `_wp_attached_file`:
  * 読み取り専用である (変更しない)。

* `post_date`:
  * サイトのタイムゾーンで、`yyyy-mm-01 00:00:00` 形式に補正する。
  * パスに日も時刻もないので、1日の0時に固定する。
* `post_date_gmt`:
  * 上記のローカル時刻を `get_gmt_from_date` に渡した値である。
  * ローカル時刻と同じ文字列は入れない。

### 日付正規化とタイムゾーン

補正後の `post_date` は、**WordPress 管理画面の「設定 > 一般 > タイムゾーン」にもとづくローカル時刻** で、`yyyy-mm-01 00:00:00` 形式に正規化します。

`post_date_gmt` は、上記ローカル時刻から `get_gmt_from_date` により算出します。

#### 注意点

* UTC は直接計算しない。
* サマータイムやオフセットは、WordPress の内部関数に委譲する。

## 設定配列・オプション (options)

初期リリースでは、option を1つ定義します。キーは `s2j_mldc_mismatch_notice` です。型は真偽値です。未保存の場合は、案内を出します。

意味は、メディアライブラリの List View で、表示中のページに「不一致」が1件でもある場合、補正を促す案内を出すことです。一括取り込みのあとも、同じ案内です。見るのはそのページの attachment だけで、ライブラリ全体は数えません。保存は Settings API で、`manage_options` です。

列、行の Date Correct、一括の Date Correct、Date Correct (All) は、この option では切り替えません。補正 API は、この option では拒みません。

アップロード時の自動補正は、初期リリースでは置きません。

`s2j_mldc_batch_size`、`s2j_mldc_last_run_stats`、ジョブ ID 用の Transient は、初期リリースでは置きません。1リクエストの上限は100件で、コードに固定します。option にはしません。

## モデルの型 - 論理/API での表現

管理 UI と REST の間で受け渡す論理モデルを、下記の型として置きます。初期リリースでは、この型がフィールドの契約です。管理画面の TypeScript は、これを手で写します。インターフェース名は、実装時に合わせてよいです。

### `PathYearMonth`

パスから得た年月 (比較キー) です。

| フィールド | 型 | 説明 |
| --- | --- | --- |
| `year` | `number` | 西暦4桁の年 |
| `month` | `number` | 1〜12の月 |
| `label` | `string` (任意) | 表示用の `yyyy/mm` |

### `MismatchStatus`

[管理画面 UI 仕様](./admin_ui_spec.md) の差分列に対応します。値は `match`、`mismatch`、`unknown` です。画面に出す文字は、下記のとおりです。

* `match` は「一致」である。
* `mismatch` は「不一致」である。
* `unknown` は「不明」である。

この3つの文字は、PHP の差分列が gettext で出します。値そのものは翻訳しません。REST の `ResultItem.status` には、この3値を使いません。

| 値 | 画面の文字 | 意味 |
| --- | --- | --- |
| `match` | 一致 | `post_date` の年月とパス年月が一致 |
| `mismatch` | 不一致 | 年月が一致しておらず、補正候補 |
| `unknown` | 不明 | `_wp_attached_file` がない、または先頭が `yyyy/mm` でない状態である。`post_date` が読めなくても、パスの年月が取れる場合は含めない |

### `AttachmentDateRow` - 一覧列の論理モデル

一覧の列にも REST の応答にも、補正候補の日時フィールドは出しません。書く文字列は、更新の際にサービスが作ります。

| フィールド | 型 | 説明 |
| --- | --- | --- |
| `id` | `number` | 添付ファイル ID |
| `postDateYm` | `string \| null` | `post_date` 由来の `yyyy/mm` (抽出不可の場合は、`null`) |
| `pathYm` | `string \| null` | パス由来の `yyyy/mm` (未設定またはパース不可の場合は、`null`) |
| `status` | `MismatchStatus` | 差分状態 (`unknown` を含む) |

### 欠損データの扱い - nullable、unknown

本プラグインでは、`_wp_attached_file`、`post_date`、およびそれらから導出される値が取得できない場合を、**例外ではなく通常状態として扱います**。

#### 設計意図 (ゴール)

* パスと日付の不一致を「例外」ではなく「状態」として扱う。
* バッチ処理における停止を防ぐ。
* UI、API、サービス間の分岐を、単純化する。

#### 基本方針

* パスが読めない欠損は、`null` または一覧の `unknown` として表現する。
* 処理を中断せず、UI に状態として反映する。
* パスが読めない件は、`correct-query` では補正対象外である (`results` に出さない)。`correct` に入った場合だけ `skipped` である。
* `post_date` が読めなくても、パスの年月が取れる件は補正する。

#### フィールド別定義

| フィールド | 型 | 欠損時の扱い |
| --- | --- | --- |
| `_wp_attached_file` | `string` | 未設定または空の場合は、`null` |
| `pathYm` | `string \| null` | パース不可の場合は、`null` |
| `post_date` | `string` | 原則として存在する。不正値でも `pathYm` があれば、パスの年月で補正する |
| `postDateYm` | `string \| null` | 抽出不可の場合は、`null` である。`pathYm` がある場合は補正を続ける |

#### ステータスとの関係

欠損データが存在する場合、`MismatchStatus` は下記のように扱います。

| 状態 | 条件 |
| --- | --- |
| `unknown` | `pathYm === null` |
| `match` | `pathYm` と `postDateYm` があり、年月が一致すること |
| `mismatch` | `pathYm` があり、`postDateYm` が `null` であるか、年月が一致しないこと |

#### UI との関係

* `unknown` は、差分列で「不明」と表示する。これは一覧の `MismatchStatus` である。
* `correct-query` では補正対象にも `results` にも含めない。`correct` に送られた場合は `skipped` である。
* 行操作などで `correct` に入っても、更新しない。

#### REST API との関係

* `ResultItem.status` に `match`、`mismatch`、`unknown` は使わない。
* パスから年月を読めない件を `skipped` にするのは **`correct` のみ** である。`correct-query` では走査だけ進め、`results` に出さない。
* `correct` で `message` を付ける場合は、人間が読める文だけである。例は「ファイルパスから年月を読み取れないため、補正しません。」です。件別の機械可読コードは置かない。
* `correct` では `summary.skipped` に含める。`summary.failed` には含めない。
* 年月がすでに一致している件も `skipped` である。区別は `message` の文である。
* `post_date` が読めなくても、パスの年月が取れる場合は更新する。この件は `skipped` にしない。

### TypeScript 型定義 (完全版)

本プラグインのデータ構造は、下記の TypeScript 型として定義します。

#### 設計方針 (規約)

* API レスポンスと UI 状態は、「同一構造」を共有する。
* `nullable` は、明示的に扱う。
* `unknown` を、「状態」として保持する。

#### 契約としての位置

本節の型が、初期リリースのフィールドの契約です。管理画面は、これを手で写します。

サーバーは、同じ形を `register_rest_route` の `args` で確かめます。PHP は zod を実行しません。

OpenAPI と zod は、初期リリースに置きません。後から機械可読な契約を足す際は、本辞書と [REST 仕様](./rest_api_spec.md) から OpenAPI を作り、そこからクライアントの型を生成します。zod は、ブラウザが応答を確かめる任意の層です。書き込みの正本にはしません。

#### MismatchStatus

```ts
type MismatchStatus = 'match' | 'mismatch' | 'unknown';
```

#### AttachmentDateRow

```ts
interface AttachmentDateRow {
  id: number;
  postDateYm: string | null;
  pathYm: string | null;
  status: MismatchStatus;
}
```

#### ResultStatus

```ts
type ResultStatus = 'success' | 'skipped' | 'error';
```

#### ResultItem

補正 API の1件は、この型だけを使います。

* `message` は、人間が読める文である。
* `status` が `error` の場合は、`message` を必ず付ける。
* `success` と `skipped` では、`message` は任意である。
* 件別結果に、機械可読の理由コードは置かない。

```ts
interface ResultItem {
  id: number;
  status: ResultStatus;
  message?: string;
}
```

#### Summary

```ts
interface Summary {
  total: number; // その1リクエストの対象件数。一覧の全件数ではない
  processed: number;
  success: number;
  skipped: number;
  failed: number;
}
```

#### APIResponse

`POST …/attachments/correct` の応答形です。

```ts
interface APIResponse {
  status: 'success' | 'partial' | 'error';
  summary: Summary;
  results: ResultItem[];
}
```

#### CorrectQueryResponse

Date Correct (All) 用の `POST …/attachments/correct-query` の応答形です。`APIResponse` に続き位置を足します。

```ts
interface CorrectQueryResponse extends APIResponse {
  // WP_Query 結果上の次の走査位置。null の場合は完了。続きがある場合は次リクエストの offset に入れる
  nextOffset: number | null;
}
```

`offset` / `nextOffset` は WP_Query 結果上の走査位置です (パスから年月を読めない件も含みます)。1リクエストは `offset` から最大100件を走査し、パスから年月を読める件だけを補正します。`nextOffset` は `offset + 今回走査した件数` です。
`summary.total` はその回の補正の試行件数です (パスから年月を読めない件は含めません)。`processed === total` かつ `summary.total` が100未満でも `nextOffset !== null` の場合は続きがあります。
補正対象0件の窓でも `processed === total` かつ `nextOffset !== null` なら続きます。`processed < total` の場合は一連をやめます。
Date Correct (All) の一連では、各応答の `summary` を加算し、`results` を連結します。
詳細は [REST API 仕様](./rest_api_spec.md) です。

トップレベル `status` は、下記のとおりです。

* `error` が1件もなく、`processed` が `total` と一致する場合は `success` である。`skipped` だけでも同じである。
* `error` と、`success` または `skipped` が混ざる場合は `partial` である。
* 処理した件がすべて `error` の場合は `error` である。
* `processed < total` の場合も `partial` である。

## データフロー

```mermaid
flowchart LR
  subgraph Read["読み取り"]
    A["_wp_attached_file"]
    B["post_date"]
    A --> P["yyyy/mm 抽出"]
    B --> Q["yyyy/mm 抽出"]
  end
  subgraph Compare["比較"]
    P --> C{"一致？"}
    Q --> C
  end
  subgraph Write["更新 (mismatch 時のみ)"]
    C -->|no| U["post_date / post_date_gmt 更新"]
    C -->|yes| S["スキップ可"]
  end
```

1. **READ**:
  * 対象 `attachment` の `post_date` と `get_post_meta( ID, '_wp_attached_file', true )` を取得する。
2. **NORMALIZE**:
  * パス先頭だけを、`^(\d{4})/(0[1-9]|1[0-2])/` で抽出する。年は1000以上、9999以下である。先頭にサブディレクトリがある場合は、`unknown` とする。
  * 補足 (欠損データ)
    * `_wp_attached_file` が未設定、または先頭が `yyyy/mm` でない場合、`pathYm` は `null` である。一覧は `unknown` とする。
    * `post_date` が不正な場合、`postDateYm` は `null` とする。`pathYm` がある場合は `mismatch` とし、パスの年月で補正する。
    * `pathYm` が `null` の場合、比較は行わず `unknown` とする。
3. **COMPARE**:
  * 日 (dd) と時刻は無視し、年月のみ比較する ([管理画面 UI 仕様](./admin_ui_spec.md))。
4. **WRITE**:
  * `mismatch` の場合だけ、`wp_update_post` を1回呼ぶ。
  * 同じ配列に、新しい `post_date`、`get_gmt_from_date` の `post_date_gmt`、取得済みの `post_modified` と `post_modified_gmt` を **必ず渡す** (キー省略禁止)。
  * コアは、`post_modified` と `post_modified_gmt` が空または省略の場合に現在時刻を入れる。取得値を渡せば、その値が残る。
  * 書いたあとにもう一度更新して戻すことはしない。
  * `skipped` の件は、`wp_update_post` を呼ばない。

## データ更新内容 (まとめ)

[冪等性 (べきとうせい)](./architecture.md#冪等性-べきとうせい): `match` の項目を再実行しても、スキップまたは no-op にできるよう、サービス層で判定します。

| 対象 | 更新内容 |
| --- | --- |
| `wp_posts.post_date` | パス上の `yyyy/mm` に対応する **`yyyy-mm-01 00:00:00`** (サイトのローカルタイム) に変更する |
| `wp_posts.post_date_gmt` | 上記のローカル時刻を `get_gmt_from_date` に渡した値である。同じ文字列は入れない |
| `wp_posts.post_modified` / `post_modified_gmt` | **意図は維持**。取得済みの値を配列に **必ず渡す** (省略禁止。省略するとコアが now にする) |
| `_wp_attached_file` | **変更しない** |
| その他メタ | 初期スコープでは **変更しない** |

## セキュリティ・整合性 (データ観点)

* 更新対象 ID は、必ず **`attachment` かつ、権限のある投稿** に限定する。
* パスから年月を読めない場合は、一覧の値は **`unknown`**、画面では「不明」と表示する。`correct-query` では補正対象外である。`correct` に入った場合だけ **`skipped`** である。`post_date` が読めなくても、パスの年月が取れる場合は補正する。
