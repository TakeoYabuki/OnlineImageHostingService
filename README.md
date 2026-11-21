# Online Image Hosting Service

PHPとMySQLを使用して開発した、画像アップロード・共有サービスです。

画像をアップロードすると、閲覧用URLと削除用URLが自動生成されます。ユーザー登録なしで画像を共有でき、削除用URLを持つユーザーは画像を削除できます。

また、アップロード制限、閲覧回数の記録、一定期間アクセスされていない画像の自動削除にも対応しています。

## 1. 主な機能

### 画像アップロード
- JPEG、PNG、GIF形式に対応
- 画像1枚あたり最大5MB
- アップロード時にファイル形式・サイズを検証
- 画像ファイルをサーバーのローカルストレージに保存
- 画像情報をMySQLに保存

### 画像共有
- ランダムな閲覧用キーを生成
- 閲覧用URLから画像を表示
- 画像の閲覧回数を記録
- 最終閲覧日時を更新
- 閲覧用URLをコピー可能

### 画像削除
- 閲覧用URLとは別に削除用URLを生成
- 削除用URLから確認画面を表示
- POSTリクエストで画像を削除
- 画像ファイルとDBレコードの両方を削除
- トランザクションと行ロックによる排他制御

### アップロード制限
同一IPアドレスに対して、以下の制限を設定しています。

| 項目 | 制限 |
|---|---|
| アップロード回数 | 直近1時間で10回 |
| アップロード合計容量 | 直近1時間で20MB |
| 画像1枚の最大サイズ | 5MB |
| 対応形式 | JPEG / PNG / GIF |

制限を超えた場合は、HTTP 429を返します。

### 自動削除
- 30日間閲覧されていない画像を削除
- `cleanup.php` で対象画像を判定
- cronによる定期実行に対応
- 削除時は行ロックを使用して競合を防止

## 2. 使用技術

| 分類 | 技術 |
|---|---|
| バックエンド | PHP 8.3 |
| データベース | MySQL 8.0 |
| フロントエンド | HTML / CSS / JavaScript |
| パッケージ管理 | Composer |
| 環境変数管理 | vlucas/phpdotenv |
| 開発環境 | Ubuntu（WSL） |
| エディタ | Visual Studio Code |
| DB管理 | DBeaver |
| バージョン管理 | Git / GitHub |

## 3. 主要ファイル

```text
OnlineImageHostingService/
├── public/
│   ├── index.php
│   ├── upload.php
│   ├── view.php
│   ├── viewer.php
│   ├── delete.php
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── script.js
├── scripts/
│   └── cleanup.php
├── storage/
│   └── images/
├── MySQLWrapper.php
├── composer.json
├── .env
└── README.md
```

※ `.env`、`vendor/`、アップロード画像、ログファイルなどはGitで公開しないでください。

## 4. セットアップ

### 必要環境

- PHP 8.3以上
- MySQL 8.0
- Composer
- PHPのPDO MySQL拡張

### ① リポジトリの取得

```bash
git clone <repository-url>
cd OnlineImageHostingService
```

`<repository-url>` は実際のGitHubリポジトリURLに置き換えてください。

### ② 依存パッケージのインストール

```bash
composer install
```

### ③ データベースの準備

MySQLでデータベースを作成します。

```sql
CREATE DATABASE image_hosting_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

続いて、プロジェクトのマイグレーションを実行して必要なテーブルを作成します。

主なテーブルは以下の2つです。

- `images`：画像情報、閲覧回数、最終閲覧日時など
- `upload_logs`：IPアドレス、アップロード容量、日時など

### ④ 環境変数の設定

プロジェクトのルートに `.env` ファイルを作成します。

```dotenv
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=image_hosting_db
DB_USER=your_db_user
DB_PASSWORD=your_db_password
```

実際のMySQL接続情報を設定してください。

**注意：`.env` はGitHubに公開しないでください。**

### ⑤ PHPサーバーの起動

プロジェクトのルートディレクトリで実行します。

```bash
php -d upload_max_filesize=10M \
    -d post_max_size=12M \
    -S localhost:8000 -t public
```

ブラウザで以下にアクセスします。

```text
http://localhost:8000/index.php
```

## 5. エンドポイント

| パス | メソッド | 機能 |
|---|---|---|
| `/index.php` | GET | アップロード画面 |
| `/upload.php` | POST | 画像アップロード |
| `/viewer.php?key=...` | GET | 画像閲覧ページ |
| `/view.php?key=...` | GET | 画像データ取得 |
| `/delete.php?key=...` | GET | 削除確認画面 |
| `/delete.php` | POST | 画像削除 |

### アップロード成功時のレスポンス例

```json
{
  "success": true,
  "view_url": "http://localhost:8000/viewer.php?key=...",
  "delete_url": "http://localhost:8000/delete.php?key=..."
}
```

閲覧用キーと削除用キーは別々に生成されます。

削除用URLは画像を削除する権限を持つため、第三者に公開しないでください。

## 6. HTTPステータスコード

| ステータス | 内容 |
|---|---|
| 200 | 処理成功 |
| 400 | 不正なリクエスト・キー |
| 404 | 画像が存在しない |
| 405 | 許可されていないHTTPメソッド |
| 413 | ファイルサイズ超過 |
| 415 | 非対応ファイル形式 |
| 429 | アップロード制限超過 |
| 500 | サーバー内部エラー |

## 7. 排他制御

画像の閲覧・手動削除・自動削除が同時に実行される可能性を考慮し、MySQLのトランザクションと `SELECT ... FOR UPDATE` を使用しています。

削除処理の流れ：

1. トランザクション開始
2. 対象画像のレコードをロック
3. 画像ファイルを削除
4. DBレコードを削除
5. コミットしてロック解除

これにより、同じ画像に対する競合操作を制御します。

なお、ファイルシステム上の削除はDBトランザクションの対象外であるため、DB操作に失敗しても削除済みファイルは自動復元されません。

## 8. 自動削除の設定

30日間閲覧されていない画像を削除するスクリプトを用意しています。

手動実行：

```bash
php scripts/cleanup.php
```

cronで毎日午前3時に実行する場合の設定例：

```cron
0 3 * * * /usr/bin/php /path/to/OnlineImageHostingService/scripts/cleanup.php >> /path/to/OnlineImageHostingService/storage/cleanup.log 2>&1
```

`/path/to/OnlineImageHostingService` は実際のプロジェクトパスに置き換えてください。

## 9. セキュリティ対策

- アップロード可能なファイル形式の制限
- ファイルサイズの制限
- IPアドレス単位のアップロード制限
- PDOのプリペアドステートメントによるSQLインジェクション対策
- `htmlspecialchars()` によるHTML出力のエスケープ
- ランダムな閲覧キー・削除キーの生成
- 削除処理でのGET・POST分離
- トランザクションと行ロックによる競合対策
- 画像ファイルを公開ディレクトリの外に保存
- `.env` によるDB接続情報の管理

## 10. 動作確認

以下の項目を確認しています。

- 画像のアップロード
- 閲覧用URLの生成
- 画像閲覧と閲覧回数の更新
- GETでの削除確認画面表示
- POSTによる画像削除
- 削除後のHTTP 404
- 不正なキーに対するHTTP 400
- アップロード回数制限によるHTTP 429
- 30日間未閲覧画像の自動削除
- 行ロックによる排他制御
- 非対応ファイル形式に対するHTTP 415
- ファイルサイズ超過に対するHTTP 413

## 11. 今後の改善案

- ドラッグ＆ドロップによる画像アップロード
- アップロード進捗バー
- 画像のサムネイル表示
- エラーメッセージの改善
- 自動テストの導入
- ファイル削除とDB更新の不整合に対する復旧処理
- 本番環境向けのセキュリティ強化

## 12. 開発目的

PHPとMySQLを使用したWebアプリケーション開発を通じて、以下の技術を学習することを目的としています。

- HTTPリクエスト・レスポンスの仕組み
- PHPによるファイルアップロード処理
- MySQLによるデータ管理
- PDOを使用したDB操作
- トランザクションと排他制御
- JavaScriptのFetch APIによる非同期通信
- Webアプリケーションのセキュリティ対策
- cronによる定期処理

本プロジェクトはローカル開発・学習を目的としています。