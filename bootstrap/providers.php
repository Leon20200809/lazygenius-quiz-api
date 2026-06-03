<?php

use App\Providers\AppServiceProvider;

// Laravelの起動時に走る初期化・共通ルール設定クラスの登録リスト。
// 外部連携の切り替えやカスタムバリデーションなどを有効化する起点。
// 独自の設定クラスやパッケージを追加した時は、この配列の下に追記する。

return [
    AppServiceProvider::class,
];
