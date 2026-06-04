<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    /**
     * 複数代入を許可するカラム
     *
     * CSV Seeder から questions テーブルへ登録・更新するときに使う。
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'correct_answer',
        'question_text',
        'category',
        'is_active',
    ];
}