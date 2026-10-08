<!-- 
目的：「プロジェクトの存在理由、概要、基本情報」の明文化
 -->

# S2J MediaLibrary Date Corrector - 概要

## はじめに

* 本ドキュメントでは、WordPress プラグイン「s2j-media-library-date-corrector」の専用仕様を定義します。
* 本プラグインの設計は、下記の共通 SPEC に準拠します。
    * [WP_PLUGIN_SPEC.md (共通仕様)](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/WP_PLUGIN_SPEC.md)

## プラグイン概要

本章では、「基本情報」を記載します。

* 名称: S2J MediaLibrary Date Corrector
* プラグイン・スラッグ: s2j-media-library-date-corrector
* テキスト・ドメイン: s2j-media-library-date-corrector
* ライセンス: GPL v3以降
* 特徴: 
    * 本『S2J MediaLibrary Date Corrector』は、WordPress のメディアライブラリにおける日付メタデータ (post_date) と、実際のファイル配置 (wp-content/uploads/yyyy/mm) との不整合を補正するためのプラグインです。
    * [Bulk Media Register](https://ja.wordpress.org/plugins/bulk-media-register/) などのツールを用いた一括登録後、メディアの「日付」が現在日時として保存されることにより、メディアライブラリの年月フィルターが正しく機能しなくなる問題を解消します。
    * 本プラグインは、下記の特徴を持ちます。
        * ファイルパス (`_wp_attached_file`) から年月を抽出し、`post_date` と `post_date_gmt` を同じ `wp_update_post` で書きます。
        * メディアライブラリの List View で、表示中のページに「不一致」があると、補正を促す案内を出します。一括取り込みのあとも同じです。設定画面で、この案内だけを消せます。
        * チェックボックス選択による選択的な一括補正が可能です。
        * 全件対象の一括補正 (バッチ処理) に対応します。
        * 補正処理は、管理画面の表示から切り離します。WP-CLI コマンドは、初期リリースでは置きません。
    * また、フロントエンドは React、TypeScript、Vite を用いて構築し、@wordpress/element を介して WordPress 管理画面に統合することで、モダンな開発体験と互換性の両立を図ります。

## 本プラグインの責務と、非対応スコープ

本プラグインの対象範囲 (責務と非責務) を下記に示します。

書くのは、attachment の `post_date` と `post_date_gmt` です。同じ `wp_update_post` の1回です。

* `post_date` は、サイトのローカル時刻 `yyyy-mm-01 00:00:00` です。
* `post_date_gmt` は、その文字列を `get_gmt_from_date` に渡した値です。
* `post_modified` と `post_modified_gmt` は、取得済みの値を同じ配列に渡します。

非対応スコープは、下記の通りです。

* ファイル移動は、行いません。
* EXIF ベースの日付補正は、対象外です。
* サムネイルの再生成は、行いません。
* メタデータの再生成は、行いません。