<!-- 
目的：「プロジェクトの存在理由、概要、基本情報」の明文化
 -->

# S2J MediaLibrary Date Corrector - 概要

## はじめに

* 本ドキュメントでは、WordPress プラグイン「s2j-media-library-date-corrector」の専用仕様を定義します。
* 本プラグインの設計は、以下の共通 SPEC に準拠します。
    * [WP_PLUGIN_SPEC.md (共通仕様)](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/WP_PLUGIN_SPEC.md)

---

## 1. プラグイン概要

本章では、「基本情報」を記載します。

* 名称: S2J MediaLibrary Date Corrector
* プラグイン・スラッグ: s2j-media-library-date-corrector
* テキスト・ドメイン: s2j-media-library-date-corrector
* ライセンス: GPL v3以降
* 特徴: 
    * S2J MediaLibrary Date Corrector は、WordPress のメディアライブラリにおける日付メタデータ (post_date) と、実際のファイル配置 (wp-content/uploads/yyyy/mm) との不整合を補正するためのプラグインです。
    * Bulk Media Register などのツールを用いた一括登録後、メディアの「日付」が現在日時として保存されることにより、メディアライブラリの年月フィルターが正しく機能しなくなる問題を解消します。
    * 本プラグインは以下の特徴を持ちます：
        * ファイルパス (_wp_attached_file) から年月情報を抽出し、post_date を補正
        * メディアライブラリ画面上で不整合の可視化 (差分確認)
        * チェックボックス選択による選択的な一括補正
        * 全件対象の一括補正 (バッチ処理) に対応
        * 将来的な WP-CLI 連携を考慮した設計
    * また、フロントエンドは React・TypeScript・Vite を用いて構築し、@wordpress/element を介して WordPress 管理画面に統合することで、モダンな開発体験と互換性の両立を図ります。

## 2. 本プラグインの責務

本プラグインの対象範囲 (責務と非責務) を次に示します。

* post_date の補正のみを行う
* ファイル移動は行わない
* メタデータの再生成は行わない