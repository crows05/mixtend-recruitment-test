# Mixtend Schedule Calendar

ミクステンド株式会社エンジニア採用コーディングテストの実装。
指定されたスケジュールAPIをPHPバックエンドから取得し、日別カレンダーとして表示。

## 実装内容

- PHPバックエンドから `https://mixtend.github.io/schedule.json` を取得
- 取得データの構造・時刻・日付・タイムゾーンを検証
- 営業時間に合わせた日別カレンダー表示
- API取得中と通信エラーの表示
- APIアクセス時のUser-Agentを `Mixtend Coding Test` に設定
- APIレスポンスを `logs/schedule-api.log` に記録

## 必要環境

- PHP 7.3以上

今回外部ライブラリは使用していません。
`composer.json` はPHPと拡張機能の要件、およびテストコマンドを明示するために含めています。

## ローカル環境起動方法

```bash
php -S 127.0.0.1:8080 -t public
```

ブラウザで `http://127.0.0.1:8080` を開いてください。

## 動作確認

- [画面キャプチャ](docs/screenshots/calendar.png)
- [テスト結果](docs/test-results/php-tests.md)

## テスト

```bash
composer test
# または
php tests/run.php
```

テストは外部APIへアクセスせず、HTTP通信をテストダブルに置き換えて検証しています。

- User-Agentヘッダー
- 正常なスケジュールの取得
- APIレスポンスのログ記録
- HTTPエラー、JSON不正、スキーマ不正の例外処理

## ディレクトリ構成

```text
public/                 Web公開ディレクトリ
  api/schedule.php      フロントエンド向けAPI
  assets/               CSS / JavaScript
src/
  Http/                  HTTP通信層
  Logging/               ログ出力層
  ScheduleClient.php     API取得・検証
tests/                   依存ライブラリ不要のテスト
logs/                    APIレスポンスログ
```

## 参考資料

- コーディングテスト要件
  - https://mixtend.notion.site/29aca0461512809b9f20e05bfc0a475c?pvs=143
- UI設計（figma）
  - https://www.figma.com/file/KxCKgxI4pGhBeRd3Up2EHI/Mixtend-Engineer-Recruitment-Test-Calendar-UI?node-id=0%3A1
