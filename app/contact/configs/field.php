<?php
// ■フォームの項目
// 'フォームのname' => array (必須,改行,最大文字数,有効書式,無効書式,項目名,フィールド名)
// ※必須＞0:任意,1:必須　改行＞0:無効,1:有効　書式＞正規表現で入力
// 　フィールド名＞DBに更新しない項目は空に
// ※行末のカンマ注意（最終行のみ不要）

$FormItem['field'] = array (
    'name_family'       => array ('require' => true, 'convert' => 'aKV'),
    'name_first'        => array ('require' => true, 'convert' => 'aKV'),
    'name_family_kana'  => array ('require' => true, 'convert' => 'aKV'),
    'name_first_kana'   => array ('require' => true, 'convert' => 'aKV'),
    'name_company'      => array ('convert' => 'aKV'),
    'name_section'      => array ('convert' => 'aKV'),
    'name_position'     => array ('convert' => 'aKV'),
    'tel'               => array ('require' => true, 'valid_format' => '^0(3-\d{4}|6-\d{4}|\d{2}-\d{3}|\d{3}-\d{2}|\d{4}-\d{1})-\d{4}$', 'convert' => 'a'),
    'email'             => array ('convert' => 'a'),
    'comments'          => array ('require' => true, 'max_length' => 400, 'convert' => 'aKV')
);

// ■項目のグループ関係
$FormItem['group'] = array (
    'familyName'  => array ('name_family','name_first'),
    'familyNameKana'  => array ('name_family_kana','name_first_kana')
);

// ■宛先メールアドレスとして認識させる項目名（１つまで）
// ここで指定した項目はメールアドレスとしてのチェックを受けるので、
// フォーム項目で有効書式等チェックは必要ありません。
$FormItem['MailItemName'] = 'email';


// ■フィールド合致チェックの項目
//
$FormItem['match'] = array (
);