<!--
目的：「Gutenberg ブロックの属性、フロント表示、Classic 対応方針」の明文化
-->

# S2J MediaLibrary Date Corrector - ブロック仕様

## 1. 位置づけ

本プラグインの **主機能** は「メディア > ライブラリ」一覧での差分表示と日付補正です ([管理画面 UI 仕様](./admin_ui_spec.md))。

初期リリースでは、Gutenberg ブロックの提供は、想定していません。

以降は、今後「Gutenberg ブロック」を提供する際にカバーされるであろう記述となります。

**Gutenberg ブロック** は、補助的な役割として定義する：

* 編集者向けに「メディア日付の整合性」について案内する
* 管理者向けにメディアライブラリへの導線を出す
* 将来的にフロントで「最終メンテナンス日表示」などへ拡張する余地を残す

コアのメディア日付そのものを **フロント公開画面から** 書き換えることは、初期スコープに含めない。

---

## 2. ブロック登録情報 (想定)

Vite 設定上、ブロックスラッグは `media-library-date-corrector` とする (`src/gutenberg/media-library-date-corrector/block.json`)。

| 項目 | 想定値 |
|------|--------|
| `name` | `s2j/media-library-date-corrector` (名前空間は実装時にプラグインと整合) |
| `title` | 翻訳可能な短い名称 |
| `category` | `widgets` または `embed` (プロジェクトのブロックカテゴリ方針に従う) |
| `apiVersion` | 3 |
| `editorScript` | ビルド済み `gutenberg` バンドル |
| `style` / `editorStyle` | 任意。`src/styles/gutenberg.scss` を参照 |

---

## 3. 属性 (attributes)

公開ページとエディターで意味のある **最小属性** を次のように置く。実装時に `block.json` の `attributes` に同期する。

| 属性名 | 型 | デフォルト | 説明 |
|--------|-----|------------|------|
| `title` | `string` | `"Media library date"` 相当の翻訳 | 見出しテキスト |
| `description` | `string` | 短い説明文 | リッチ化しないプレーンテキスト推奨 |
| `showCta` | `boolean` | `true` | 管理画面への CTA リンクを表示するか |
| `ctaLabel` | `string` | 翻訳された「メディアライブラリを開く」等 | ボタン／リンク文言 |
| `align` | `string` | `undefined` | コアの配置スキームに合わせる場合のみ使用 |

高度なデザイントークン (色・フォント) は、テーマ依存が高いため **初期版では持たない**。必要になれば block supports (`spacing`、`color`) を有効化する。

---

## 4. エディター表示 (Edit)

* `InspectorControls` に属性編集 (タイトル、説明、CTA の ON/OFF、ラベル)
* ブロック本体はプレースホルダー＋プレビューに近い静的レイアウト (実データの REST 呼び出しは不要を原則とし、パフォーマンスと権限の複雑化を避ける)
* アクセシビリティ：`aria` ラベル、見出しレベルの適切化

---

## 5. フロント表示 (Save / render_callback)

次のいずれかを採用する (実装時に1つに確定)：

### 5.A 動的レンダリング (推奨)

`register_block_type` で `render_callback` を PHP に渡し、フロント HTML をサーバーで生成する。

* **利点**: キャッシュ互換、属性のエスケープを PHP で一貫させやすい
* **出力**: 見出し、説明段落、条件付き CTA リンク (`admin_url('upload.php')`)。**未ログインユーザーには管理 URL を出さない**/または非表示

### 5.B 静的 Save (`save` が JSX を返す)

* **利点**: ビルドパイプラインが単純
* **注意**: CTA をログイン状態で切り替えるには、`ServerSideRender` や別ブロックではなく **5.A** の方が適する

---

## 6. Classic エディター対応方針

`src/classic/index.ts` 用のビルドターゲットを用意する (`vite.config.ts`)。

| 方針 | 内容 |
|------|------|
| **優先度** | 本プラグインの MVP では **Gutenberg + メディア一覧** を優先。Classic は「最低限の干渉なし」を守る |
| **ショートコード** | 任意で `[s2j_mldc_notice]` のようにブロックと同内容を出力 (`render_callback` とロジック共有) |
| **TinyMCE** | 初期版では専用ボタンは **設けない** (保守コスト削減)。需要があれば後続 |
| **スタイル** | `src/styles/classic.scss` でショートコード枠の最低限の余白のみ |

Classic での「メディア日付補正」自体は **REST ＋メディア画面** と同じバックエンドを利用可能とし、UI は純粋にブロック/ショートコードに限定する。

---

## 7. フロント用フロントエンドバンドル (frontend ターゲット)

`frontend` 用エントリ (`src/frontend/media-library-date-corrector.tsx`) は、**インタラクティブなフロントが不要な限り空または未使用** とし、ブロックを PHP `render_callback` のみで完結させる選択肢もある。

フロントでクライアントサイドハイドレーションが必要になった場合のみ、`frontend` バンドルを `block.json` の `viewScript` に接続する。
