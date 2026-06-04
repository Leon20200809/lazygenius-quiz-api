<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\QuizController;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Laravel API is running',
    ]);
});

Route::get('quizzes/sample', function () {
    $question = [
        'question' => [
            'id' => 1,
            'question_text' => '画面に判断と動きを与える魔力',
            'category' => 'フロントエンド',
            'choices' => [
                'HTML',
                'CSS',
                'JavaScript',
                'PHP',
            ],
        ],
    ];

    return response()->json($question, 200, [], JSON_UNESCAPED_UNICODE);
});

// quizzes/sample/answer にPOSTが来たら QuizController の answerSample メソッドを実行する
Route::post('quizzes/sample/answer', [QuizController::class, 'answerSample']);

// quizzes/start にリクエストきたら10問取得
Route::get('/quizzes/start', [QuizController::class, 'start']);