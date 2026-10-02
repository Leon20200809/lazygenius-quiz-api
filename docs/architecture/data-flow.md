# LazyGenius Quiz API データフロー

対象リポジトリ：

`Leon20200809/lazygenius-quiz-api`

目的：

このドキュメントは、Laravel API内でデータがどのように流れるかを俯瞰するための設計資料です。

---

## 1. 全体像

まずは細かい処理を省き、クイズAPIの基本経路だけを見る。

```mermaid
flowchart TD

    FE["Next.js フロントエンド"]
    ROUTE["routes/api.php"]
    CTRL["QuizController"]
    SERVICE["QuizService"]
    MODEL["Question Model<br/>Eloquent ORM"]
    DB[("questions テーブル<br/>MySQL / MariaDB")]

    FE -->|"HTTP Request"| ROUTE
    ROUTE --> CTRL
    CTRL --> SERVICE
    SERVICE --> MODEL
    MODEL --> DB
```

基本の流れ：

```text
Next.js
↓
Route
↓
Controller
↓
Service
↓
Model / Eloquent
↓
Database
```

DBから取得した結果や採点結果は、この経路を逆方向へ戻り、最終的にJSONとしてNext.jsへ返る。

詳細な処理は後続の「クイズ開始API」「一括採点API」で分けて確認する。

### 対応する主なファイル

```text
routes/api.php
    ↓
app/Http/Controllers/Api/QuizController.php
    ↓
app/Services/QuizService.php
    ↓
app/Models/Question.php
    ↓
questions テーブル
```

### 各層の責務

```text
Route
→ URLとControllerを結ぶ

Controller
→ HTTPリクエストを受ける
→ バリデーションする
→ Serviceを呼ぶ
→ JSONレスポンスを返す

Service
→ クイズ取得
→ 誤答候補生成
→ 選択肢シャッフル
→ 一括採点
→ 得点・結果生成

Model
→ Eloquent ORMを通してDBへアクセスする

questionsテーブル
→ 問題データの実体を保存する
```

---

## 2. クイズ開始API

エンドポイント：

```text
GET /api/quizzes/start
```

```mermaid
flowchart TD

    REQ["GET /api/quizzes/start"]
    ROUTE["routes/api.php"]
    CTRL["QuizController::start()"]
    SERVICE["QuizService::getStartQuestions()"]
    Q1["questionsから<br/>is_active = true の問題を<br/>ランダム10件取得"]
    Q2["10問に含まれるカテゴリを抽出"]
    Q3["同カテゴリの<br/>correct_answer候補を一括取得"]
    Q4["全カテゴリの<br/>correct_answer候補を一括取得"]
    BUILD["各問題ごとに<br/>正解1 + 誤答3 を生成"]
    SHUFFLE["choicesをshuffle"]
    REMOVE["correct_answerは<br/>レスポンスへ含めない"]
    JSON["JSONレスポンス"]

    REQ --> ROUTE
    ROUTE --> CTRL
    CTRL --> SERVICE
    SERVICE --> Q1
    Q1 --> Q2
    Q2 --> Q3
    Q3 --> Q4
    Q4 --> BUILD
    BUILD --> SHUFFLE
    SHUFFLE --> REMOVE
    REMOVE --> JSON
```

フロントへ返す情報：

```text
id
question_text
category
choices
```

返さない情報：

```text
correct_answer
正解フラグ
DB内部の判定情報
```

---

## 3. 一括採点API

エンドポイント：

```text
POST /api/quizzes/submit
```

```mermaid
flowchart TD

    REQ["POST /api/quizzes/submit"]
    CTRL["QuizController::submit()"]
    VALIDATE["Laravel validate()<br/>answers: required,array,size:10<br/>question_id: integer<br/>selected_answer: string"]
    SERVICE["QuizService::submitAnswers()"]
    IDS["question_idを抽出<br/>unique()"]
    DB["Question::whereIn()<br/>対象問題を一括取得"]
    CHECK["取得件数とID件数を比較"]
    INVALID{"存在しないIDあり?"}
    ABORT["422で停止"]
    SCORE["Collection上で<br/>selected_answer と<br/>correct_answer を比較"]
    RESULT["score / total / results"]
    JSON["JSONレスポンス"]

    REQ --> CTRL
    CTRL --> VALIDATE
    VALIDATE --> SERVICE
    SERVICE --> IDS
    IDS --> DB
    DB --> CHECK
    CHECK --> INVALID
    INVALID -- "Yes" --> ABORT
    INVALID -- "No" --> SCORE
    SCORE --> RESULT
    RESULT --> JSON
```

### N+1を避ける設計

```text
10件の回答
↓
question_idをまとめる
↓
whereIn()で1回にまとめて取得
↓
keyBy('id')
↓
Collection上で採点
```

---

## 4. CSVからquestionsテーブルへのデータ投入

対象：

```text
database/seeders/csv/web_development_quiz.csv
```

Seeder：

```text
database/seeders/QuestionSeeder.php
```

```mermaid
flowchart LR

    CSV["web_development_quiz.csv"]
    SEEDER["QuestionSeeder"]
    PARSE["fgetcsv()<br/>CSVを1行ずつ読む"]
    VALIDATE["correct_answer<br/>question_text<br/>category<br/>isActive"]
    MODEL["Question Model<br/>Eloquent"]
    UPSERT["updateOrCreate()<br/>correct_answer + question_text"]
    DB[("questions テーブル")]

    CSV --> SEEDER
    SEEDER --> PARSE
    PARSE --> VALIDATE
    VALIDATE --> UPSERT
    UPSERT --> MODEL
    MODEL --> DB
```

重複判定キー：

```text
correct_answer + question_text
```

同じ答えでも問題文が違えば別問題として扱う。

Seederを再実行しても、同じ組み合わせは重複登録せず更新対象となる。

---

## 5. Migrationから実DBまで

対象Migration：

```text
database/migrations/2026_06_04_014750_create_questions_table.php
```

```mermaid
flowchart LR

    MIGRATION["Migration"]
    SCHEMA["Schema::create('questions')"]
    COLUMN["カラム・制約を定義"]
    DB[("questions テーブル")]

    MIGRATION --> SCHEMA
    SCHEMA --> COLUMN
    COLUMN --> DB
```

questionsテーブル：

```text
id
correct_answer
question_text
category
is_active
created_at
updated_at
```

制約：

```text
PRIMARY KEY
→ id

UNIQUE
→ correct_answer + question_text
```

---

## 6. Laravelの各役割

```mermaid
flowchart TD

    MIGRATION["Migration<br/>DBの箱を作る"]
    SEEDER["Seeder<br/>初期データを入れる"]
    MODEL["Model / Eloquent<br/>DBを操作する"]
    SERVICE["Service<br/>業務ロジックを担当"]
    CONTROLLER["Controller<br/>HTTPを受ける"]
    ROUTE["Route<br/>URLと処理を結ぶ"]
    FRONT["Next.js<br/>APIを利用する"]

    MIGRATION --> MODEL
    SEEDER --> MODEL
    MODEL --> SERVICE
    SERVICE --> CONTROLLER
    CONTROLLER --> ROUTE
    ROUTE --> FRONT
```

```text
Migration
→ テーブル構造を作る

Seeder
→ 初期データをDBへ入れる

Model / Eloquent
→ PHPからDBを操作する

Service
→ データをどう使うか決める

Controller
→ HTTPを受けてServiceへ渡す

Route
→ URLとControllerを結ぶ

Next.js
→ Laravel APIを利用する
```

---

## 7. 実DB確認まで含めた全体像

```mermaid
flowchart TD

    CSV["CSV"]
    SEEDER["QuestionSeeder"]
    ORM["Question Model<br/>Eloquent ORM"]
    DB[("MySQL / MariaDB<br/>questions")]
    SERVICE["QuizService"]
    CTRL["QuizController"]
    API["Laravel API"]
    NEXT["Next.js"]
    PMA["phpMyAdmin"]

    CSV --> SEEDER
    SEEDER --> ORM
    ORM --> DB

    DB --> ORM
    ORM --> SERVICE
    SERVICE --> CTRL
    CTRL --> API
    API --> NEXT

    DB --> PMA
```

学習上の確認ルート：

```text
Migration
↓
Seeder
↓
Eloquent ORM
↓
MySQL / MariaDB
↓
phpMyAdminで実物確認
```

---

## 8. 現在の主要ファイル

```text
routes/
└─ api.php

app/
├─ Http/
│  └─ Controllers/
│     └─ Api/
│        └─ QuizController.php
├─ Models/
│  └─ Question.php
└─ Services/
   └─ QuizService.php

database/
├─ migrations/
│  └─ 2026_06_04_014750_create_questions_table.php
└─ seeders/
   ├─ QuestionSeeder.php
   └─ csv/
      └─ web_development_quiz.csv
```

---

## 9. 最重要の一本線

```text
Next.js
↓
routes/api.php
↓
QuizController
↓
QuizService
↓
Question Model
↓
Eloquent ORM
↓
questions テーブル
```

逆方向：

```text
questions テーブル
↓
Eloquent ORM
↓
Question Model
↓
QuizService
↓
QuizController
↓
JSON
↓
Next.js
```

これが LazyGenius Quiz API の基本データフロー。
