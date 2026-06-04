<?php

namespace Database\Seeders;

use App\Models\Question;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    /**
     * CSVからWeb開発用語クイズの問題データを登録する
     *
     * correct_answer + question_text の組み合わせを重複判定に使う。
     * 同じ答えでも問題文が違えば、別問題として登録する。
     */
    public function run(): void
    {
        $csv_path = database_path('seeders/csv/web_development_quiz.csv');

        if (! file_exists($csv_path)) {
            $this->command->error("CSVファイルが見つかりません: {$csv_path}");
            return;
        }

        $file = fopen($csv_path, 'r');

        if ($file === false) {
            $this->command->error("CSVファイルを開けません: {$csv_path}");
            return;
        }

        $header = fgetcsv($file);

        if ($header === false) {
            fclose($file);
            $this->command->error('CSVヘッダーを読み取れません。');
            return;
        }

        $import_count = 0;

        while (($row = fgetcsv($file)) !== false) {
            $data = array_combine($header, $row);

            if ($data === false) {
                continue;
            }

            $correct_answer = trim($data['correct_answer'] ?? '');
            $question_text = trim($data['question_text'] ?? '');
            $category = trim($data['category'] ?? '');
            $is_active = (int) ($data['isActive'] ?? 1) === 1;

            if ($correct_answer === '' || $question_text === '' || $category === '') {
                continue;
            }

            // データベース更新
            Question::updateOrCreate(
                [
                    'correct_answer' => $correct_answer,
                    'question_text' => $question_text,
                ],
                [
                    'category' => $category,
                    'is_active' => $is_active,
                ]
            );

            $import_count++;
        }

        fclose($file);

        $this->command->info("questions テーブルへ {$import_count} 件のCSVデータを登録・更新しました。");
    }
}