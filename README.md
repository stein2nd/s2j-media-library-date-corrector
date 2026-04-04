# S2J MediaLibrary Date Corrector

[![License: GPL v3](https://img.shields.io/badge/License-GPL%20v3-blue.svg)](https://www.gnu.org/licenses/old-licenses/gpl-3.0.en.html)
[![WordPress](https://img.shields.io/badge/WordPress-6.3-blue.svg)](https://wordpress.org/)
[![React](https://img.shields.io/badge/React-19.2-blue.svg)](https://reactjs.org/)
[![TypeScript](https://img.shields.io/badge/TypeScript-6.0-blue.svg)](https://www.typescriptlang.org/)
[![Dart SASS](https://img.shields.io/badge/SCSS-1.98-blue.svg)](https://sass-lang.com/dart-sass/)
[![Vite](https://img.shields.io/badge/vite-8.0-blue.svg)](https://vite.dev)

## Description

本『S2J MediaLibrary Date Corrector』は、WordPress におけるメディア一括登録後のメタデータ不整合を解消するための補正ツールです。

[Bulk Media Register](https://ja.wordpress.org/plugins/bulk-media-register/) 等で登録されたメディアは、ファイルが `uploads/yyyy/mm` 配下に配置されていても、データベース上の `post_date` が現在日時となる場合があります。この状態では、メディアライブラリの年月フィルターと実際のファイル構造が一致しません。

本プラグインは、`_wp_attached_file` に格納されたパス情報をもとに年月を抽出し、`post_date` を適切な値へ補正します。差分の可視化および選択的な一括更新を、メディアライブラリ画面上で実行可能です。

実装には React + TypeScript + Vite を採用し、`@wordpress/element` を介して WordPress 管理画面へ統合します。

## クイックスタート

1. プラグインを有効化します。
2. 「メディア > ライブラリ」を開きます。
3. 「差分」列を確認します。
4. 「Bulk Actions」から「Date Correct」を選択します。
5. 補正処理を実行します。

## ユースケース

* [Bulk Media Register](https://ja.wordpress.org/plugins/bulk-media-register/) などで一括登録した後に使います。
* メディアの年月フィルターが崩れている場合に使います。
* 既存の uploads 構造を維持したい場合に使います。
