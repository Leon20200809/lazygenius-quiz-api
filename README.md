# LazyGenius Quiz API

[![Deploy Laravel API](https://github.com/Leon20200809/lazygenius-quiz-api/actions/workflows/deploy.yml/badge.svg)](https://github.com/Leon20200809/lazygenius-quiz-api/actions/workflows/deploy.yml)

Web開発用語の4択クイズを提供するLaravel製APIです。

Next.js製フロントエンドとLaravel APIを分離し、問題取得、選択肢生成、回答バリデーション、一括採点、DBアクセスをサーバー側で担当します。

## 公開URL

- API: https://api.lazygenius.dev/
- ヘルスチェック: https://api.lazygenius.dev/api/health
- クイズ開始API: https://api.lazygenius.dev/api/quizzes/start
- フロントエンド: https://lazygenius-quiz-front.vercel.app/
- フロントエンドリポジトリ: https://github.com/Leon20200809/lazygenius-quiz-front

## 概要

MySQLに保存したWeb開発用語から、ランダムに10問を取得して4択問題として返します。

誤答候補はCSVへ固定保存せず、同じカテゴリに属する別問題の正解用語からLaravel側で動的に生成します。

ユーザーが10問回答した後、回答配列を一括で受け取り、サーバー側で採点します。

```txt
GET /api/quizzes/start
↓
10問取得
↓
Next.js側で1問ずつ表示
↓
POST /api/quizzes/submit
↓
10問を一括採点
↓
score / total / resultsを返却
```

## 作った理由

Laravel APIとNext.jsを分離した構成を使い、実際のWebサービスに近い通信、データ管理、責務分離、デプロイの流れを学ぶために開発しました。

このプロジェクトでは以下を実践しています。

- LaravelによるREST API開発
- ControllerとServiceの責務分離
- MySQLによる問題データ管理
- CSVからSeederを使った初期データ登録
- 正解情報をフロントへ渡さない設計
- 10問分の回答一括採点
- Laravel標準バリデーション
- ループ内DBアクセスを避ける設計
- GitHub ActionsによるXserver自動デプロイ
- レンタルサーバー制約下でのLaravel安全配置

## 主な機能

- APIサーバーのヘルスチェック
- Web開発用語クイズの取得
- 1回10問のランダム出題
- 同カテゴリを優先した誤答選択肢の自動生成
- 正解を含む4つの選択肢のシャッフル
- 正解情報を含めない問題レスポンス
- 10問分の回答バリデーション
- 10問一括採点
- 得点計算
- 問題ごとの正誤結果生成
- 存在しない問題IDの拒否
- CSVからMySQLへの問題データ登録・更新
- GitHub ActionsによるXserver自動デプロイ

## 技術構成

| 分類 | 技術 |
|---|---|
| バックエンド | Laravel 13 |
| 言語 | PHP 8.3 |
| データベース | MySQL |
| ORM | Eloquent |
| 初期データ | CSV / Seeder |
| 本番環境 | Xserver |
| CI/CD | GitHub Actions |
| フロントエンド | Next.js（別リポジトリ） |

## API設計

### `GET /api/health`

Laravel APIの生存確認に使用します。

```txt
GET https://api.lazygenius.dev/api/health
```

レスポンス例:

```json
{
  "status": "ok",
  "message": "Laravel API is running"
}
```

### `GET /api/quizzes/sample`

DBを使用せず、固定のクイズデータを1問返します。

APIレスポンス形式の確認用エンドポイントです。

### `POST /api/quizzes/sample/answer`

サンプル問題の回答を受け取り、サーバー側で正誤を判定します。

### `GET /api/quizzes/start`

MySQLから問題を10問取得し、4択クイズとして返します。

```txt
GET https://api.lazygenius.dev/api/quizzes/start
```

レスポンス例:

```json
{
  "questions": [
    {
      "id": 245,
      "question_text": "サーバー設定を自動化する道具",
      "category": "インフラ",
      "choices": [
        "Daemon",
        "Rollback",
        "Nginx",
        "Ansible"
      ]
    }
  ]
}
```

正解情報はレスポンスへ含めません。

### `POST /api/quizzes/submit`

10問分の回答を受け取り、一括採点します。

リクエスト例:

```json
{
  "answers": [
    {
      "question_id": 1,
      "selected_answer": "HTML"
    },
    {
      "question_id": 2,
      "selected_answer": "CSS"
    }
  ]
}
```

実際のリクエストでは`answers`が10件必要です。

レスポンス例:

```json
{
  "score": 7,
  "total": 10,
  "results": [
    {
      "question_id": 1,
      "question_text": "情報に意味を与える骨格",
      "selected_answer": "HTML",
      "correct_answer": "HTML",
      "is_correct": true
    }
  ]
}
```

## バリデーション

Laravel標準の`$request->validate()`を使用しています。

```php
$validated = $request->validate([
    'answers' => ['required', 'array', 'size:10'],
    'answers.*.question_id' => ['required', 'integer'],
    'answers.*.selected_answer' => ['required', 'string'],
]);
```

| ルール | 意味 |
|---|---|
| `required` | 必須 |
| `array` | 配列であること |
| `size:10` | 10件ちょうど |
| `integer` | 整数 |
| `string` | 文字列 |
| `answers.*` | answers配列の全要素 |

検査を通過した回答配列だけを`QuizService`へ渡します。

## ControllerとServiceの責務分離

### QuizController

HTTPの受付を担当します。

- Requestの受け取り
- バリデーション
- QuizServiceの呼び出し
- JSONレスポンス返却

```txt
Request
↓
validate
↓
QuizService
↓
response()->json()
```

### QuizService

クイズの業務ロジックを担当します。

- クイズ開始用10問取得
- 誤答候補生成
- 選択肢シャッフル
- 回答対象の問題一括取得
- 正誤判定
- 得点計算
- 採点結果生成

```txt
Controller
→ HTTPの箱

Service
→ 採点・選択肢生成の箱

Model
→ DBデータの箱
```

## 一括採点ロジック

回答データから`question_id`を抽出し、`whereIn()`で対象問題をまとめて取得します。

```txt
10件の回答
↓
question_idを抽出
↓
whereIn()で対象問題を一括取得
↓
keyBy('id')
↓
Collection上で正解と照合
↓
score / total / resultsを生成
```

ループ内でDBアクセスは行いません。

存在しない問題IDが含まれる場合は、422で処理を停止します。

## 正解情報をフロントへ渡さない

問題取得APIでは、以下の情報だけを返します。

- 問題ID
- 問題文
- カテゴリ
- 選択肢

以下は返しません。

- 正解
- 正解フラグ
- DB内部の判定情報

```txt
GET /api/quizzes/start
→ 正解情報なし

POST /api/quizzes/submit
→ 回答後に採点結果を返す
```

ブラウザのDevToolsから回答前に正解を確認できないようにし、判定責務をLaravel側へ集約しています。

## 通信をまとめる

1問ごとにAPIへアクセスするのではなく、問題取得と回答送信をまとめます。

```txt
開始時
GET /api/quizzes/start
↓
10問まとめて取得
```

```txt
終了時
POST /api/quizzes/submit
↓
10問まとめて採点
```

通信回数を抑え、フロント側では回答中の状態をReact stateで管理します。

## DB設計

### questionsテーブル

| カラム | 内容 |
|---|---|
| `id` | 主キー |
| `correct_answer` | 正解となる用語 |
| `question_text` | 問題文 |
| `category` | カテゴリ |
| `is_active` | 出題対象か |
| `created_at` | 作成日時 |
| `updated_at` | 更新日時 |

### 重複判定

同じ正解用語でも、問題文が違えば別問題として扱います。

```txt
correct_answer + question_text
```

この組み合わせを重複判定のキーとして使用します。

## CSV取り込み

初期データはCSVで管理し、Laravel SeederでMySQLへ取り込みます。

CSVの主な列:

```txt
correct_answer
question_text
category
isActive
```

Seederでは`correct_answer`と`question_text`の組み合わせをキーにして`updateOrCreate`を行います。

これにより、CSVを再投入しても同じ問題が重複登録されません。

## 選択肢生成ロジック

誤答選択肢はLaravel側で動的に生成します。

```txt
1. 出題問題のcorrect_answerを正解にする
2. 同カテゴリの別問題から誤答候補を取得
3. 正解1つ + 誤答3つを作る
4. 選択肢をシャッフル
5. 正解情報を除外して返す
```

同カテゴリだけで誤答候補が足りない場合は、全カテゴリから不足分を補充します。

## パフォーマンス方針

MVP段階でも、ループ内でDBアクセスを繰り返さない設計を採用しています。

### 選択肢生成

```txt
10問取得
↓
必要なカテゴリを集める
↓
誤答候補をまとめて取得
↓
Collection上で選択肢を生成
```

### 一括採点

```txt
question_idをまとめる
↓
whereIn()で問題を一括取得
↓
Collection上で採点
```

問題ごとにSQLを発行しないことで、N+1問題を避けています。

## データ構成

初期データとして322件のWeb開発用語をCSVから登録しています。

誤答選択肢はCSVへ保存せず、Laravel側で生成します。

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

Laragonを使用する場合:

```txt
http://lazygenius-quiz-api.test
```

Artisanを使用する場合:

```bash
php artisan serve
```

```txt
http://127.0.0.1:8000
```

### 8. 動作確認

```bash
curl http://lazygenius-quiz-api.test/api/health
```

```bash
curl http://lazygenius-quiz-api.test/api/quizzes/start
```

## Xserverでの配置方針

Laravel本体と公開フォルダを分離しています。

```txt
Laravel本体
/home/xs227617/laravel-apps/lazygenius-quiz-api/

公開フォルダ
/home/xs227617/lazygenius.dev/public_html/api.lazygenius.dev/
```

Laravel本体は`public_html`の外へ配置し、外部から直接アクセスできないようにしています。

公開フォルダにはLaravelの`public`配下のみを配置します。

```txt
api.lazygenius.dev/
├─ index.php
├─ .htaccess
└─ その他の公開ファイル
```

公開側の`index.php`は、本番Laravel本体を絶対パスで参照します。

```php
define(
    'LARAVEL_BASE_PATH',
    '/home/xs227617/laravel-apps/lazygenius-quiz-api'
);
```

以下を公開領域の外へ隔離しています。

- `.env`
- `app`
- `bootstrap`
- `config`
- `routes`
- `storage`
- `vendor`

## 自動デプロイ

`main`ブランチへのpushをトリガーに、GitHub Actions上でPHPUnitを実行します。

テストがすべて成功した場合のみ、Xserverへのデプロイを開始します。

```txt
mainへpush
↓
PHP 8.3のテスト環境を準備
↓
Composer依存関係をインストール
↓
SQLiteメモリDBでPHPUnitを実行
↓
テスト成功時のみXserverへSSH接続
↓
Laravel本体でgit pull
↓
本番用Composer依存関係をインストール
↓
Laravelキャッシュ削除
↓
未実行migrationを反映
↓
本番設定をキャッシュ
↓
public配下を公開フォルダへ同期
```

テスト失敗時はデプロイジョブを実行しないため、既存機能を壊したコードが本番へ反映される事故を防ぎます。

現在は以下のAPI仕様をFeature Testで確認しています。

- ヘルスチェックAPIが正常なJSONを返す
- クイズ開始APIが10問と各4択を返す
- 問題取得時に正解情報を公開しない
- 一括採点APIがscore、total、resultsを正しく返す
- 回答数不足を422で拒否する
- 存在しない問題IDを422で拒否する
- 不正な回答形式を422で拒否する

テスト環境では、本番MySQLへ接続せずSQLiteのインメモリDBを使用します。
```env
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```
これにより、テストごとに独立した使い捨てDBを使用し、本番データへ影響を与えずにAPI全体の動作を確認できます。

公開側の`index.php`は本番専用のため、`rsync`の対象から除外しています。

```bash
rsync -a \
  --delete \
  --exclude="index.php" \
  "$REMOTE_APP_PATH/public/" \
  "$REMOTE_PUBLIC_PATH/"
```

初回のみ本番用`index.php`を手動配置し、それ以降は自動デプロイで上書きしません。

## GitHub ActionsでPHP 8.3を明示する理由

手動SSHではシェル関数によってPHP 8.3版Composerが実行されます。

GitHub ActionsからのSSHは非対話シェルであり、その関数が読み込まれません。

そのため、ワークフロー内ではPHPとComposerを絶対パスで指定しています。

```txt
PHP
/usr/bin/php8.3

Composer
/home/xs227617/bin/composer
```

これにより、サーバー標準のPHP 8.0が使われる環境差分を防いでいます。

## 本番確認

```bash
curl https://api.lazygenius.dev/api/health
```

```bash
curl https://api.lazygenius.dev/api/quizzes/start
```

一括採点APIは、10件の回答JSONをPOSTして確認します。

## 開発中に発生した問題

### 本番APIが最新コードではなかった

Vercel上のNext.jsから`POST /api/quizzes/submit`を実行した際、404になりました。

原因はCORSではなく、Laravel本番環境へ一括採点APIの最新コードが反映されていなかったことでした。

```txt
ローカルで動く
≠
本番に最新コードがある
```

以下の順で切り分けました。

```txt
Request URL
Status
Payload
Response
本番APIのroute
最新コミット
GitHub Actions
デプロイ履歴
```

### 10件以外の回答を拒否

フロント側の二重送信により11件の回答が送信された際、Laravelの`size:10`バリデーションが422を返しました。

これにより、サーバー側の入力検査が正常に動作していることも確認できました。

## 今後の改善

- Form Requestへのバリデーション分離
- APIエラーレスポンスの統一
- カテゴリ指定出題
- 難易度追加
- 問題管理API
- スコア履歴
- ランキング
- キャッシュ導入
- レート制限
