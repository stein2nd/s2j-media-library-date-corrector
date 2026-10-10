<!-- 
目的：「プロジェクトの存在理由、概要、基本情報」の明文化
 -->

# S2J MediaLibrary Date Corrector - 概要

## はじめに

本ドキュメントでは、WordPress プラグイン「s2j-media-library-date-corrector」の専用仕様を定義します。

本プラグインの設計は、右記の共通 SPEC に準拠します: [SPECS.md (共通仕様)](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/SPECS.md)

## プラグイン概要

本章では、「基本情報」を記載します。

* 名称: S2J MediaLibrary Date Corrector
* プラグイン・スラッグ: s2j-media-library-date-corrector
* テキスト・ドメイン: s2j-media-library-date-corrector
* ライセンス: GPL v3以降
* 特徴: 
    * 本『S2J MediaLibrary Date Corrector』は、WordPress のメディアライブラリにおける日付メタデータ (`post_date`) と、添付の相対パスメタ `_wp_attached_file` (先頭 `yyyy/mm/`。アップロード基準ディレクトリからの相対パス。例: `2017/12/file.jpg`。文字列に `uploads/` は含めない) との不一致を補正するためのプラグインである。ディスク上の実ファイル配置は読まない。物理移動はスコープ外である。
    * [Bulk Media Register](https://ja.wordpress.org/plugins/bulk-media-register/) などのツールを用いた一括登録後、メディアの「日付」が現在日時として保存されることにより、メディアライブラリの年月フィルターが正しく機能しなくなる問題を解消する。
    * 本プラグインは、下記の特徴を持つ。
        * `_wp_attached_file` から年月を抽出し、`post_date` と `post_date_gmt` を同じ `wp_update_post` で書く。
        * メディアライブラリの List View で、表示中のページに差分 `mismatch` (画面「不一致」) が1件でもあると、補正を促す案内を出す (`unknown` だけでは出さない)。一括取り込みのあとも同じである。設定画面で、この案内だけを消せる。
        * 行の Date Correct が可能である。
        * チェック選択後の一括 Date Correct が可能である。
        * Date Correct (All) で、現在の検索・フィルター全ページを補正できる。
        * 補正処理は、管理画面の表示から切り離す。WP-CLI コマンドは、初期リリースでは置かない。
    * また、フロントエンドは React、TypeScript、Vite を用いて構築し、@wordpress/element を介して WordPress 管理画面に統合することで、モダンな開発体験と互換性の両立を図る。

## 本プラグインの責務と、非対応スコープ

本プラグインの対象範囲 (責務と非責務) を下記に示します。

書くのは、attachment の `post_date` と `post_date_gmt` です。同じ `wp_update_post` の1回です。

* `post_date` は、サイトのローカル時刻 `yyyy-mm-01 00:00:00` である。
* `post_date_gmt` は、その文字列を `get_gmt_from_date` に渡した値である。
* `post_modified` と `post_modified_gmt` は、取得済みの値を同じ配列に **必ず** 渡す (キー省略禁止。省略するとコアが now にする)。

非対応スコープは、下記の通りです。

* ファイル移動は、行わない。
* EXIF ベースの日付補正は、対象外である。
* サムネイルの再生成は、行わない。
* メタデータの再生成は、行わない。
