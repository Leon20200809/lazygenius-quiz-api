<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * questions テーブルを作成する
     *
     * Web開発用語クイズの問題データを管理する。
     * 1つの correct_answer に対して、複数の question_text を許可する。
     */
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();

            $table->string('correct_answer');
            $table->string('question_text', 500);
            $table->string('category');
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // 同じ答え＋同じ問題文の重複を防ぐ
            $table->unique(['correct_answer', 'question_text']);
        });
    }

    /**
     * questions テーブルを削除する
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
