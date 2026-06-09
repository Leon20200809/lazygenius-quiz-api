<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\QuizService;


class QuizController extends Controller
{
    /**
     * サンプルクイズの回答を判定する
     *
     * @param Request $request Next.jsから送られてきた回答データ
     * @param QuizService $quiz_service サンプルクイズの判定処理を担当するサービス
     * @return \Illuminate\Http\JsonResponse 判定結果のJSONレスポンス
     */
    public function answerSample(Request $request, QuizService $quiz_service)
    {
        $selected_answer = $request->input('selected_answer');

        $result = $quiz_service->judgeSampleAnswer($selected_answer);

        return response()->json($result);
    }

    /**
     * クイズ開始用の問題一覧を返す
     *
     * questions テーブルから出題対象の問題を10件取得し、
     * Next.js側へJSONで返す。
     *
     * @param QuizService $quiz_service クイズ関連の処理を担当するサービス
     * @return \Illuminate\Http\JsonResponse クイズ開始用の問題一覧
     */
    public function start(QuizService $quiz_service)
    {
        $questions = $quiz_service->getStartQuestions();

        return response()->json(
            ['questions' => $questions],
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * 10問分の回答を受け取り、一括採点結果を返す
     *
     * Next.jsから送られてきた回答データをバリデーションし、
     * 採点処理をQuizServiceへ任せる。
     *
     * @param Request $request Next.jsから送られてきた回答データ
     * @param QuizService $quiz_service クイズ採点処理を担当するサービス
     * @return \Illuminate\Http\JsonResponse 一括採点結果
     */
    public function submit(Request $request, QuizService $quiz_service)
    {
        // Laravel標準のバリデーションで回答データを検査する
        $validated = $request->validate([
            'answers' => ['required', 'array', 'size:10'],
            'answers.*.question_id' => ['required', 'integer'],
            'answers.*.selected_answer' => ['required', 'string'],
        ]);

        // 検査を通過した回答配列だけをServiceへ渡す
        $result = $quiz_service->submitAnswers($validated['answers']);

        return response()->json(
            $result,
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }
}
