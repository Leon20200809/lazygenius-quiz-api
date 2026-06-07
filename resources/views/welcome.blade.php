<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>LazyGenius Quiz API</title>

    <meta name="description" content="Web開発用語クイズへ問題データと採点機能を提供するLaravel APIです。">

    <link rel="stylesheet" href="{{ asset('css/welcome.css') }}">

</head>

<body>
    <main class="l-main">
        <div class="l-container">
            <section class="c-panel">
                <header class="c-panel__header">
                    <span class="c-status">
                        API Running
                    </span>

                    <h1 class="c-heading">
                        LazyGenius<br>
                        Quiz API
                    </h1>

                    <p class="c-lead">
                        Web開発用語クイズへ問題データと採点機能を提供する、
                        Laravel製のREST APIです。
                        正解情報はフロントエンドへ渡さず、
                        判定処理をサーバー側で管理します。
                    </p>
                </header>

                <div class="c-panel__body">
                    <section class="c-section">
                        <h2 class="c-section__title">
                            Available Endpoints
                        </h2>

                        <ul class="c-endpoint-list">
                            <li>
                                <a class="c-endpoint" href="{{ url('/api/health') }}">
                                    <span class="c-endpoint__meta">
                                        <span class="c-endpoint__method">
                                            GET
                                        </span>

                                        <span class="c-endpoint__path">
                                            /api/health
                                        </span>
                                    </span>

                                    <span class="c-endpoint__description">
                                        APIサーバーの稼働状態を確認します。
                                    </span>
                                </a>
                            </li>

                            <li>
                                <a class="c-endpoint" href="{{ url('/api/quizzes/sample') }}">
                                    <span class="c-endpoint__meta">
                                        <span class="c-endpoint__method">
                                            GET
                                        </span>

                                        <span class="c-endpoint__path">
                                            /api/quizzes/sample
                                        </span>
                                    </span>

                                    <span class="c-endpoint__description">
                                        動作確認用の固定クイズを1問返します。
                                    </span>
                                </a>
                            </li>

                            <li>
                                <a class="c-endpoint" href="{{ url('/api/quizzes/start') }}">
                                    <span class="c-endpoint__meta">
                                        <span class="c-endpoint__method">
                                            GET
                                        </span>

                                        <span class="c-endpoint__path">
                                            /api/quizzes/start
                                        </span>
                                    </span>

                                    <span class="c-endpoint__description">
                                        MySQLからランダムに10問取得し、
                                        4択クイズとして返します。
                                    </span>
                                </a>
                            </li>
                        </ul>
                    </section>

                    <div class="c-actions">
                        <a class="c-button c-button--primary" href="{{ url('/api/quizzes/start') }}">
                            クイズAPIを確認
                        </a>

                        <a class="c-button" href="https://github.com/Leon20200809/lazygenius-quiz-api" target="_blank"
                            rel="noopener noreferrer">
                            GitHub Repository
                        </a>
                    </div>
                </div>

                <footer class="c-footer">
                    Laravel API / PHP 8.3 / MySQL / GitHub Actions / Xserver
                </footer>
            </section>
        </div>
    </main>
</body>

</html>
