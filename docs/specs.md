# S2J MediaLibrary Date Corrector - 仕様書の起点

本プロジェクトの仕様は、以下のドキュメントに分散して定義しています。
共通仕様に加え、プラグイン固有の仕様を参照してください。

## 読み方ガイド

新規参入者は、次の順で読むと、全体像から画面、API にたどりやすくなります。

1. [コンセプト](./concept.md) — 背景理解 (想定ユースケース、課題、処理の流れ)
2. [管理画面の UI 仕様](./admin_ui_spec.md) — 操作 (画面上の操作と表示)
3. [REST API 仕様](./rest_api_spec.md) — 処理 (エンドポイントとデータのやりとり)
4. [アーキテクチャー](./architecture.md) - 実装 (技術スタック、責務)

その後は、下表の [概要](./overview.md) や [データ辞書](./data_dictionary.md) など、必要なトピックから参照してください。

## 共通仕様

* [WP_PLUGIN_SPEC.md (共通仕様)](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/WP_PLUGIN_SPEC.md)

## プラグイン固有の仕様

| ドキュメント | 内容 |
|--------------|------|
| [概要](./overview.md) | プロジェクトの存在理由、概要、基本情報 |
| [コンセプト](./concept.md) | 想定ユースケース、解決する課題、処理フロー (Before / After) |
| [アーキテクチャー](./architecture.md) | フォルダー構成、主要ファイル、技術スタック、ビルド、責務 |
| [データ辞書](./data_dictionary.md) | モデルの型、設定配列、CPT、メタキー、option、データフロー |
| [REST API 仕様](./rest_api_spec.md) | REST API エンドポイント、リクエスト/レスポンス、権限、セキュリティ |
| [ブロック仕様](./block_spec.md) | Gutenberg ブロックの属性、フロント表示、Classic 対応方針 |
| [管理画面の UI 仕様](./admin_ui_spec.md) | 管理画面のレイアウト、各機能 |
| [実装状況](./status.md) | 実装状況サマリー、Backlog、品質レポート、まとめ |

*本プラグインの設計は上記共通仕様に準拠し、ここに列挙したドキュメントがプラグイン固有の仕様を定義します。*