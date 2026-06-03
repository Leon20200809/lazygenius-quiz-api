<?php

use Illuminate\Support\Facades\Route;

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
