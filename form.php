<?php
/**
 * mazurka FormFramework
 * configure / bootstrap loader
 *
 * @version       1.0
 * @since         1.0
 * @copyright     Copyright (c) 2014 macchaka, Omura Printing Co.,ltd.
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

// 基本パス設定
    define( 'APP_DIR', dirname(__FILE__) . '/app');
    define( 'FORM_NAME', 'contact');

// 動作設定
    ini_set( 'display_errors', 1 );     // エラーが発生した場合にエラー表示をする設定
    define( 'LANGUAGE', 'Japanese');
    define( 'ENCODING', 'UTF-8');

// ■テンプレートファイル名（拡張子は自由です）
    define( 'TPL_INDEX',    'contact.html');
    define( 'TPL_PROOF',    'contact_confirm.html');
    define( 'TPL_COMPLETE', 'contact_complete.html');

// ■ボタン名称
    define( 'BTN_SUBMIT', '入力内容を確認する');
    define( 'BTN_BACK',   '修正する');
    define( 'BTN_SEND',   '送信する');

// ■メール関連
// ■管理者メールアドレス
    define( 'ADMIN_MAIL', 'admin@example.com');

// ■BCCメールアドレス
    define( 'BCC_MAIL', '');

// ■メールサブジェクト
    define( 'SUBJECT_EXTERNAL',  '[○○○株式会社] お問い合わせありがとうございました');
    define( 'SUBJECT_INTERNAL',  'ウェブサイト よりお問い合わせを受け付けました');

// ■メールテンプレートファイル名
    define( 'TPL_MAIL_EXTERNAL', 'contact_mail_external.txt');
    define( 'TPL_MAIL_INTERNAL', 'contact_mail_internal.txt');

// ■システムエラー定義
    $systemErrorMessage['timeout'] = <<< EOF
<html><head><META http-equiv="Content-Type" content="text/html; charset=UTF-8"></head>
<body><p>長い時間操作がなかったため、セッションが切れました。最初からやり直して下さい。</p></body></html>
EOF;

    require_once (APP_DIR . '/' . FORM_NAME . '/controller.php');

    $obj = new controller();
    $obj->dispatch($_POST);
