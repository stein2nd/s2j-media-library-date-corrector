# S2J MediaLibrary Date Corrector

[![License: GPL v3](https://img.shields.io/badge/License-GPL%20v3-blue.svg)](https://www.gnu.org/licenses/old-licenses/gpl-3.0.en.html)
[![PHP](https://img.shields.io/badge/PHP-8.0-blue.svg)](https://www.php.net/)
[![WordPress](https://img.shields.io/badge/WordPress-6.9+-blue.svg)](https://wordpress.org/)
[![React](https://img.shields.io/badge/React-19.3-blue.svg)](https://reactjs.org/)
[![TypeScript](https://img.shields.io/badge/TypeScript-7.0-blue.svg)](https://www.typescriptlang.org/)
[![Dart SASS](https://img.shields.io/badge/SCSS-1.105-blue.svg)](https://sass-lang.com/dart-sass/)
[![Vite](https://img.shields.io/badge/vite-8.3-blue.svg)](https://vite.dev)
[![Rollup](https://img.shields.io/badge/rollup-4.64-blue.svg)](https://rollupjs.org)

## Description

本『S2J MediaLibrary Date Corrector』は、WordPress におけるメディア一括登録後のメタデータ不一致を解消するための補正ツールです。

[Bulk Media Register](https://ja.wordpress.org/plugins/bulk-media-register/) 等で登録されたメディアは、ファイルが `uploads/yyyy/mm` 配下に配置されていても、データベース上の `post_date` が現在日時となる場合があります。この状態では、メディアライブラリの年月フィルターと実際のファイル構造が一致しません。

本プラグインは、`_wp_attached_file` に格納されたパス情報をもとに年月を抽出し、`post_date` / `post_date_gmt` を適切な値に補正します。差分の可視化および選択的な一括更新を、メディアライブラリ画面上で実行可能です。

実装には React + TypeScript + Vite を採用し、`@wordpress/element` を介して WordPress 管理画面に統合します。

## クイックスタート

1. プラグインを有効化します。
2. 「メディア > ライブラリ」(List View) を開きます。
3. 「差分」列で一致 / 不一致 / 不明を確認します。
4. 行の「Date Correct」、またはチェック選択後に Bulk Actions の「Date Correct」で補正します。
5. 現在の検索・フィルター全体を直す場合は「Date Correct (All)」を使います。
6. 表示中ページに不一致がある場合の案内は、設定画面で消せます。

## ユースケース

* [Bulk Media Register](https://ja.wordpress.org/plugins/bulk-media-register/) などで一括登録した後に使います。
* メディアの年月フィルターが崩れている場合に使います。
* 既存の uploads 構造を維持したい場合に使います。

## License

このプロジェクトは GPL v3以降の下でライセンスされています - 詳細は [LICENSE](LICENSE) ファイルを参照してください。

## Support and Contact

サポート、機能リクエスト、またはバグ報告については、[GitHub Issues](https://github.com/stein2nd/s2j-media-library-date-corrector/issues) ページをご覧ください。
