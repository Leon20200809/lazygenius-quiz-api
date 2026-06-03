<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    // 1. ルーティング設定：Web、API、コマンド、生存確認用のURL（/up）のルート定義を紐付け
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // 2. ミドルウェア設定：アプリ全体や特定の通信に挟む、共通のセキュリティ・前処理をここに書く
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    // 3. エラーハンドリング：API（api/*）へのアクセス時にエラーが起きたら、必ずJSON形式で返すように制御
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
