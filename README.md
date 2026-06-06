# LazyGenius Quiz API

[![Deploy Laravel API](https://github.com/Leon20200809/lazygenius-quiz-api/actions/workflows/deploy.yml/badge.svg)](https://github.com/Leon20200809/lazygenius-quiz-api/actions/workflows/deploy.yml)

Web開発用語の4択クイズを提供するLaravel製APIです。

Next.js製フロントエンドとLaravel APIを分離し、問題取得・選択肢生成・正解判定をサーバー側で担当します。

## 公開API

### ヘルスチェック

```txt
GET https://api.lazygenius.dev/api/health
```

[ヘルスチェックAPIを開く](https://api.lazygenius.dev/api/health)

レスポンス例：

```json
{
    "status": "ok",
    "message": "Laravel API is running"
}
```

### クイズ開始

```txt
GET https://api.lazygenius.dev/api/quizzes/start
```

[クイズ取得APIを開く](https://api.lazygenius.dev/api/quizzes/start)

MySQLからランダムに10問取得し、各問題に4つの選択肢を生成して返します。

正解データはレスポンスに含めません。

## このプロジェクトを作った理由

Laravel APIとNext.jsを分離した構成を使い、実際のWebサービスに近い通信・データ管理・デプロイの流れを学ぶために開発しています。

単にクイズを表示するだけではなく、以下を実践することが目的です。

- LaravelによるREST API開発
- Next.jsとLaravel間のAPI通信
- MySQLによる問題データ管理
- CSVからの初期データ登録
- 正解情報をフロントへ渡さない設計
- GitHub Actionsによる自動デプロイ
- レンタルサーバー制約下での安全なLaravel配置

## 主な機能

- Web開発用語クイズの取得
- 1回10問のランダム出題
- 同カテゴリを優先した誤答選択肢の自動生成
- 正解を含む4つの選択肢のシャッフル
- サーバー側での正解判定
- CSVからMySQLへの問題データ登録・更新
- APIサーバーのヘルスチェック
- GitHub ActionsによるXserverへの自動デプロイ

## 技術構成

| 分類           | 技術                    |
| -------------- | ----------------------- |
| バックエンド   | Laravel 13              |
| 言語           | PHP 8.3                 |
| データベース   | MySQL                   |
| ORM            | Eloquent                |
| 初期データ     | CSV / Seeder            |
| 本番環境       | Xserver                 |
| CI/CD          | GitHub Actions          |
| フロントエンド | Next.js（別リポジトリ） |

フロントエンド：

[Leon20200809/lazygenius-quiz-front](https://github.com/Leon20200809/lazygenius-quiz-front)

## API設計

### `GET /api/health`

Laravel APIの生存確認に使用します。

### `GET /api/quizzes/sample`

DBを使用せず、固定のクイズデータを1問返します。

APIレスポンス形式の確認用エンドポイントです。

### `POST /api/quizzes/sample/answer`

サンプル問題の回答を受け取り、サーバー側で正誤を判定します。

### `GET /api/quizzes/start`

MySQLから問題を10問取得し、4択クイズとして返します。

レスポンス例：

```json
{
    "questions": [
        {
            "id": 245,
            "question_text": "サーバー設定を自動化する道具",
            "category": "インフラ",
            "choices": ["Daemon", "Rollback", "Nginx", "Ansible"]
        }
    ]
}
```

## 重要な設計方針

### 正解情報をフロントへ渡さない

問題取得APIでは、以下の情報だけを返します。

- 問題ID
- 問題文
- カテゴリ
- 選択肢

以下は返しません。

- 正解
- 正解フラグ
- DB内部の判定情報

ブラウザのDevToolsから正解を確認できないようにし、判定処理はLaravel側に集約します。

### 通信はまとめる

1問ごとにAPIへアクセスするのではなく、クイズ開始時に10問をまとめて取得します。

```txt
GET /api/quizzes/start
↓
10問まとめて取得
↓
Next.js側で1問ずつ表示
```

回答も最終的にはまとめてLaravelへ送信し、一括採点する構成を目指します。

### ループ内でDBへアクセスしない

N+1問題を避けるため、ループ内で問題や選択肢候補を1件ずつ取得しません。

```txt
必要なデータをまとめて取得
↓
LaravelのCollection上で分類・加工
↓
各問題の選択肢を生成
```

### SWR / React Queryを使用していない理由

MVPでは、クイズ開始時に10問をまとめて取得し、回答中はフロント側のstateで管理します。

頻繁な再取得、キャッシュ同期、バックグラウンド更新がまだ必要ないため、SWRやReact Queryは現時点では過剰と判断し、標準の`fetch`を使用しています。

スコア履歴、ランキング、管理画面などを追加し、サーバーデータの再取得や同期が重要になった段階で導入を検討します。

## データ構成

初期データとして322件のWeb開発用語をCSVから登録しています。

CSVの主な列：

```txt
correct_answer
question_text
category
isActive
```

誤答選択肢はCSVへ固定保存せず、同じカテゴリに属する別問題の`correct_answer`からLaravel側で動的に生成します。

候補が不足する場合は、他カテゴリの回答候補から補充します。

## ローカル環境構築

### 1. リポジトリをクローン

```bash
git clone https://github.com/Leon20200809/lazygenius-quiz-api.git
cd lazygenius-quiz-api
```

### 2. Composer依存関係をインストール

```bash
composer install
```

### 3. 環境設定ファイルを作成

```bash
cp .env.example .env
php artisan key:generate
```

### 4. `.env`へMySQL接続情報を設定

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lazygenius_quiz_api
DB_USERNAME=root
DB_PASSWORD=
```

### 5. テーブルを作成

```bash
php artisan migrate
```

### 6. クイズデータを登録

```bash
php artisan db:seed --class=QuestionSeeder
```

### 7. 開発サーバーを起動

```bash
php artisan serve
```

確認URL：

```txt
http://127.0.0.1:8000/api/health
```

## Xserverでの配置方針

Xserverでは、サブドメインの公開フォルダをLaravelの`public`ディレクトリへ直接指定できません。

そのため、Laravel本体と公開フォルダを分離しています。

```txt
Laravel本体
/home/xs227617/laravel-apps/lazygenius-quiz-api/

公開フォルダ
/home/xs227617/lazygenius.dev/public_html/api.lazygenius.dev/
```

Laravel本体は`public_html`の外へ配置し、外部から直接アクセスできないようにしています。

公開フォルダには、Laravelの`public`配下だけを配置します。

```txt
api.lazygenius.dev/
├── index.php
├── .htaccess
└── その他の公開ファイル
```

公開側の`index.php`はXserver本番専用です。

`LARAVEL_BASE_PATH`で、非公開領域に置かれたLaravel本体を参照します。

```php
define(
    'LARAVEL_BASE_PATH',
    '/home/xs227617/laravel-apps/lazygenius-quiz-api'
);
```

これにより、Laravel本体の以下のファイルを公開領域の外へ隔離できます。

- `.env`
- `app`
- `bootstrap`
- `config`
- `routes`
- `storage`
- `vendor`

## 自動デプロイ

`main`ブランチへのpushをトリガーに、GitHub ActionsからXserverへ自動デプロイします。

```txt
mainへpush
↓
XserverへSSH接続
↓
Laravel本体でgit pull
↓
PHP 8.3でComposer install
↓
Laravelキャッシュ削除
↓
未実行のmigrationを反映
↓
本番設定をキャッシュ
↓
public配下を公開フォルダへ同期
```

公開側の`index.php`は本番専用のため、`rsync`の対象から除外しています。

```bash
rsync -a \
  --delete \
  --exclude="index.php" \
  "$REMOTE_APP_PATH/public/" \
  "$REMOTE_PUBLIC_PATH/"
```

初回のみ本番用`index.php`を手動配置し、それ以降は自動デプロイで上書きしません。

### GitHub ActionsでPHP 8.3を明示する理由

手動SSHではシェル関数によってPHP 8.3版Composerが実行されます。

一方、GitHub ActionsからのSSHは非対話シェルであり、その関数が読み込まれません。

そのため、ワークフロー内ではPHPとComposerを絶対パスで指定しています。

```txt
PHP
/usr/bin/php8.3

Composer
/home/xs227617/bin/composer
```

これにより、サーバー標準のPHP 8.0が使われる環境差分を防いでいます。

## 初回デプロイで行う作業

初回のみ、以下を手動で実行します。

```txt
1. Xserver上でMySQLデータベースを作成
2. Laravel本体をgit clone
3. Composer依存関係をインストール
4. 本番用.envを配置
5. APP_KEYを生成
6. migrationを実行
7. QuestionSeederを実行
8. 公開フォルダへ本番用index.phpを配置
9. GitHub Actions Secretsを登録
```

2回目以降は、GitHubへpushすることで自動反映されます。

## 本番環境での確認コマンド

マイグレーション状態：

```bash
php artisan migrate:status
```

登録された問題数：

```bash
php artisan tinker
```

```php
App\Models\Question::count();
```

期待値：

```txt
322
```

## 今後の予定

- 10問分の回答送信API
- 一括採点処理
- Next.jsとの本番通信
- CORS設定
- 結果画面
- ユーザー認証
- スコア履歴
- 苦手カテゴリ分析
- ランキング
- 問題管理画面
- APIテスト
- CIテスト

## ライセンス

学習およびポートフォリオ目的で開発しています。
