<?php

namespace Tests\Feature\Api;

// Laravelのダミーデータ生成機能を使えるようにする
use Illuminate\Foundation\Testing\WithFaker;

// questionsテーブルを操作するQuestionモデルを使えるようにする
use App\Models\Question;

// PHPUnitのテスト名表示を日本語などに差し替えるTestDox属性を使えるようにする
use PHPUnit\Framework\Attributes\TestDox;

// 各テストごとにDBを初期状態へ戻すRefreshDatabase機能を使えるようにする
use Illuminate\Foundation\Testing\RefreshDatabase;

// Laravelのテスト用基底クラスを継承するために使う
use Tests\TestCase;

class QuizApiTest extends TestCase
{
    use RefreshDatabase;
    /**
     * ヘルスチェックAPIが正常なJSONを返すことを確認する
     */
    #[TestDox('ヘルスチェックAPIが正常なJSONを返すことを確認する')]
    public function test_api_health_check(): void
    {
        // JSON APIを叩いている意図が明確
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);

        $response->assertJson([
            'status' => 'ok',
            'message' => 'Laravel API is running',
        ]);
    }

    /**
     * クイズ開始APIが10問と各4択を返すことを確認する
     */
    #[TestDox('クイズ開始APIが10問と各4択を返すことを確認する')]
    public function test_quiz_start_returns_ten_questions_with_four_choices(): void
    {
        // 同カテゴリから誤答候補を作れるように、十分な問題を登録する
        // 12問置いておけば、どの問題がランダム選出されても、誤答候補が十分あることを明確にできる
        for ($i = 1; $i <= 12; $i++) {
            Question::create([
                'correct_answer' => "Answer{$i}",
                'question_text' => "Question{$i}",
                'category' => 'テストカテゴリ',
                'is_active' => true,
            ]);
        }

        $response = $this->getJson('/api/quizzes/start');

        // 10問返ったか確認
        $response->assertOk()->assertJsonCount(10, 'questions');

        // 全問題を回して4択か確認
        foreach ($response->json('questions') as $question) {
            $this->assertCount(4, $question['choices']);
        }
    }

    /**
     * クイズ開始APIが正解情報を返さないことを確認する
     */
    #[TestDox('クイズ開始APIは正解情報を公開してないことを確認する')]
    public function test_quiz_start_does_not_expose_correct_answer(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            Question::create([
                'correct_answer' => "Answer{$i}",
                'question_text' => "Question{$i}",
                'category' => 'テストカテゴリ',
                'is_active' => true,
            ]);
        }

        $response = $this->getJson('/api/quizzes/start');

        $response->assertOk();

        foreach ($response->json('questions') as $question) {
            $this->assertArrayNotHasKey('correct_answer', $question);
        }
    }

    #[TestDox('一括採点APIが得点・問題数・採点結果を正しく返すことを確認する')]
    public function test_quiz_submit_returns_correct_score_total_and_results(): void
    {
        $answers = [];

        for ($i = 1; $i <= 10; $i++) {
            $question = Question::create([
                'correct_answer' => "Answer{$i}",
                'question_text' => "Question{$i}",
                'category' => 'テストカテゴリ',
                'is_active' => true,
            ]);

            $answers[] = [
                'question_id' => $question->id,

                // 奇数は正解、偶数は不正解にする
                'selected_answer' => $i % 2 === 1
                    ? "Answer{$i}"
                    : 'WrongAnswer',
            ];
        }

        $response = $this->postJson('/api/quizzes/submit', [
            'answers' => $answers,
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'score' => 5,
                'total' => 10,
            ])
            ->assertJsonCount(10, 'results');
    }

    #[TestDox('一括採点APIが回答数不足を422で拒否することを確認する')]
    public function test_quiz_submit_rejects_insufficient_answers(): void
    {
        $answers = [];

        for ($i = 1; $i <= 9; $i++) {
            $question = Question::create([
                'correct_answer' => "Answer{$i}",
                'question_text' => "Question{$i}",
                'category' => 'テストカテゴリ',
                'is_active' => true,
            ]);

            $answers[] = [
                'question_id' => $question->id,
                'selected_answer' => "Answer{$i}",
            ];
        }

        $response = $this->postJson('/api/quizzes/submit', [
            'answers' => $answers,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['answers']);
    }

    #[TestDox('一括採点APIが存在しない問題IDを422で拒否することを確認する')]
    public function test_quiz_submit_rejects_nonexistent_question_id(): void
    {
        $answers = [];

        for ($i = 1; $i <= 10; $i++) {
            $question = Question::create([
                'correct_answer' => "Answer{$i}",
                'question_text' => "Question{$i}",
                'category' => 'テストカテゴリ',
                'is_active' => true,
            ]);

            $answers[] = [
                'question_id' => $question->id,
                'selected_answer' => "Answer{$i}",
            ];
        }

        // 最後の1件だけ、DBに存在しない問題IDへ差し替える
        $answers[9]['question_id'] = 999999;

        $response = $this->postJson('/api/quizzes/submit', [
            'answers' => $answers,
        ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'message' => '存在しない問題が含まれています。',
            ]);
    }

    #[TestDox('一括採点APIが不正な回答形式を422で拒否することを確認する')]
    public function test_quiz_submit_rejects_invalid_answer_format(): void
    {
        $answers = [];

        for ($i = 1; $i <= 10; $i++) {
            $answers[] = [
                'question_id' => $i,
                'selected_answer' => "Answer{$i}",
            ];
        }

        // selected_answerは文字列必須だが、配列を送る
        $answers[0]['selected_answer'] = ['InvalidAnswer'];

        $response = $this->postJson('/api/quizzes/submit', [
            'answers' => $answers,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'answers.0.selected_answer',
            ]);
    }
}
