<?php

namespace App\Services;

use App\Models\Question;

class QuizService
{
    /**
     * サンプルクイズの回答を判定する
     *
     * @param string $selected_answer ユーザーが選択した回答
     * @return array 判定結果
     */
    public function judgeSampleAnswer(string $selected_answer): array
    {
        $correct_answer = 'JavaScript';

        return [
            'is_correct' => $selected_answer === $correct_answer,
            'correct_answer' => $correct_answer,
        ];
    }

    /**
     * クイズ開始用の問題を取得する
     *
     * questions テーブルから出題対象の問題をランダムに10件取得する。
     * 各問題に対して、正解1つ + 誤答3つの選択肢を生成する。
     *
     * correct_answer は選択肢生成のために内部では使うが、
     * フロントには返さない。
     *
     * @return array<int, array<string, mixed>>
     */
    public function getStartQuestions(): array
    {
        // まずは出題対象の問題を10件ランダム取得する。
        // correct_answer は choices 生成に必要なので、内部処理用として取得する。
        $questions = Question::query()
            ->where('is_active', true)
            ->inRandomOrder()
            ->limit(10)
            ->get([
                'id',
                'correct_answer',
                'question_text',
                'category',
            ]);

        // 今回取得した10問に登場するカテゴリだけを集める。
        // 後で「同カテゴリの誤答候補」をまとめて取得するため。
        $categories = $questions
            ->pluck('category')
            ->unique()
            ->values();

        // 同カテゴリの correct_answer 候補をまとめて取得する。
        // ここで先にまとめて取ることで、ループ内DBアクセスを避ける。
        $answers_by_category = Question::query()
            ->where('is_active', true)
            ->whereIn('category', $categories)
            ->get([
                'correct_answer',
                'category',
            ])
            ->groupBy('category');

        // 同カテゴリだけで誤答が3つ足りない場合に備えて、
        // 全カテゴリの correct_answer 候補もまとめて取得しておく。
        $all_answers = Question::query()
            ->where('is_active', true)
            ->pluck('correct_answer')
            ->unique()
            ->values();

        return $questions
            ->map(function (Question $question) use ($answers_by_category, $all_answers): array {
                // まずは同じカテゴリから、自分自身の正解を除外して誤答候補を3つ選ぶ。
                $same_category_answers = $answers_by_category
                    ->get($question->category, collect())
                    ->pluck('correct_answer')
                    ->unique()
                    ->reject(function (string $answer) use ($question): bool {
                        return $answer === $question->correct_answer;
                    })
                    ->shuffle()
                    ->take(3)
                    ->values();

                // 同カテゴリだけで3つ集まらない場合は、
                // 全カテゴリ候補から不足分を補充する。
                if ($same_category_answers->count() < 3) {
                    $needed_count = 3 - $same_category_answers->count();

                    $extra_answers = $all_answers
                        ->reject(function (string $answer) use ($question, $same_category_answers): bool {
                            return $answer === $question->correct_answer
                                || $same_category_answers->contains($answer);
                        })
                        ->shuffle()
                        ->take($needed_count)
                        ->values();

                    $same_category_answers = $same_category_answers->merge($extra_answers);
                }

                // 誤答3つに正解1つを混ぜて、最後にシャッフルする。
                // フロントには「どれが正解か」は渡さない。
                $choices = $same_category_answers
                    ->push($question->correct_answer)
                    ->shuffle()
                    ->values()
                    ->toArray();

                return [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'category' => $question->category,
                    'choices' => $choices,
                ];
            })
            ->toArray();
    }
}
