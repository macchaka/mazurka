<?php
namespace Mazurka;
use \Smarty;
use \Qdmail;

/**
 * mazurka FormFramework
 *
 * @version       1.0
 * @since         1.0
 * @copyright     Copyright (c) 2014 macchaka, Omura Printing Co.,ltd.
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

class Mazurka
{
    public $inputData = array();
    public $errorResult = array();
    public $Smarty;
    public $template;

/**
 * [__construct description]
 */
    public function __construct()
    {
        mb_language(LANGUAGE);
        mb_internal_encoding(ENCODING);
        session_start();

        // Smartyを使うための準備
        require_once( APP_DIR . '/smarty/libs/Smarty.class.php' );
        $this->Smarty = new Smarty();
        $this->Smarty->template_dir = APP_DIR . '/' . FORM_NAME . '/templates/';
        $this->Smarty->compile_dir  = APP_DIR . '/' . FORM_NAME . '/templates_c/';
        $this->Smarty->config_dir   = APP_DIR . '/' . FORM_NAME . '/configs/';
        $this->Smarty->cache_dir    = APP_DIR . '/' . FORM_NAME . '/cache/';
    }

/**
 * [dispatch description]
 * @param  [type] $postdata [description]
 * @return [type]           [description]
 */
    public function dispatch($postdata)
    {
        require (APP_DIR . '/' . FORM_NAME . '/configs/fields.php');

        if (!empty($postdata)) {
            //フォームデータの取得
            foreach ($postdata as $key => $val) {
                //変換指定があれば、変換してから値をセットする
                if (isset($formItem['field'][$key]['convert'])) {
                    $this->inputData[$key] = $this->deleteNullbyte(mb_convert_kana($val, $formItem['field'][$key]['convert']));
                } else {
                    $this->inputData[$key] = $this->deleteNullbyte($val);
                }
            }

            switch ($this->inputData['submit']) {
                case BTN_BACK:
                    //戻るボタンを押した状態
                    //ただしセッションが空の場合は不正アクセス
                    if (!empty($_SESSION['form'])) {
                        $this->back();
                    }
                    break;

                case BTN_SUBMIT:
                    //入力が完了し、「確認」ボタンを押した状態
                    $this->proof();
                    break;

                case BTN_SEND:
                    //確認が終了し、「送信」ボタンを押した状態
                    //ただしセッションが空の場合は不正アクセス
                    if (!empty($_SESSION['form'])) {
                        //確認画面からはデータはPOSTされてきていないため、セッションから復元
                        $this->inputData = $_SESSION['form'];
                        $this->send();
                    }
                    break;
                default:
                    echo $systemErrorMessage['timeout'];
                    exit();
            }
        } else {
            $this->first();
        }
    }

/**
 * [first description]
 * @return [type] [description]
 */
    public function first()
    {
        //初回アクセス（POSTが送られてきていない）
        $this->template = TPL_INDEX;
    }

/**
 * [back description]
 * @return [type] [description]
 */
    public function back()
    {
        //戻るボタンを押した→フォームへ差し戻し
        $inputData = $_SESSION['form'];
        $this->Smarty->assign('d', $inputData);
        $this->template = TPL_INDEX;
    }

/**
 * [proof description]
 * @return [type] [description]
 */
    public function proof()
    {
        //======== 入力審査
        $errorResult = $this->inputCheck();

        if ($errorResult) {
            //エラーあり→入力画面へ
            $this->Smarty->assign('d', $this->inputData);
            $this->Smarty->assign('e', $errorResult);
            $this->template = TPL_INDEX;
        } else {
            //入力エラーなし→確認画面へ
            //セッションへ記憶
            $_SESSION['form'] = $this->inputData;
            $this->Smarty->assign('d', $this->inputData);
            $this->template = TPL_PROOF;
        }
    }

/**
 * [send description]
 * @return [type] [description]
 */
    public function send()
    {
        //送信（確認画面で送信）
        //======== 送信実行＜確認画面で「送信」ボタンを押されたため
        require_once (APP_DIR . '/qdmail/qdmail.php' );
        require      (APP_DIR . '/' . FORM_NAME . '/configs/fields.php');
        $mailKey = $formItem['MailItemName'];
        $mailAddress = $this->inputData[$mailKey];

        //■メール送信
        //（登録者向け）
        $this->sendMail($mailAddress, ADMIN_MAIL, SUBJECT_EXTERNAL, TPL_MAIL_EXTERNAL);

        //（管理者向け）
        $this->sendMail(ADMIN_MAIL, $mailAddress, SUBJECT_INTERNAL, TPL_MAIL_INTERNAL);

        //画面切り替え
        $this->template = TPL_COMPLETE;
        session_destroy();
    }

/**
 * [render description]
 * @return [type] [description]
 */
    public function render()
    {
        $this->Smarty->display($this->template);
    }

/**
 * [inputCheck description]
 * @return [type] [description]
 */
    public function inputCheck()
    {
        $errorResult = array();

        require (APP_DIR . '/' . FORM_NAME . '/configs/fields.php');

        //メールアドレスチェック
        $mailKey = $formItem['MailItemName'];
        if (isset($this->inputData[$mailKey]) && $this->inputData[$mailKey] != '' ) {
            if ($this->addressCheck($this->inputData[$mailKey])) {
                $errorResult['f'][$mailKey]['invalid'] = true;
            }
        } else {
            $errorResult['f'][$mailKey]['required'] = true;
        }

        //合致チェック
        foreach ($formItem['match'] as $key => $val) {
            if (isset($this->inputData["{$key}"]) && isset($this->inputData["{$val}"])
                && $this->inputData["{$key}"] !== $this->inputData["{$val}"]) {
                $errorResult['f'][$key]['mismatch'] = true;
            } else {
                $errorResult['f'][$key]['mismatch'] = false;
            }
        }

        //設定された内容でチェック
        foreach ($formItem['field'] as $key => $val) {
            $value = isset($this->inputData[$key]) ? $this->inputData[$key] : '';

            //必須チェック
            if (!empty($val['required']) && $value === '') {
                $errorResult['f'][$key]['required'] = true;
            }

            //文字数
            if (!empty($val['max_length'])
                && (mb_strlen($value, ENCODING) > (integer)$val['max_length'])) {
                $errorResult['f'][$key]['max_length'] = true;
            }

            //有効書式
            if (!empty($val['valid_format'])
                && $value !== '' && !preg_match('/' . $val['valid_format'] . '/', $value)) {
                $errorResult['f'][$key]['invalid'] = true;
            }

            //無効書式
            if (!empty($val['invalid_format'])
                && $value !== '' && preg_match('/' . $val['invalid_format'] . '/', $value)) {
                $errorResult['f'][$key]['invalid'] = true;
            }
        }

        //親フィールドへのエラー状況更新
        foreach ($formItem['group'] as $parentname => $child) {
            foreach ($child as $fieldname) {
                if (!empty($errorResult['f'][$fieldname])) {
                    $errorResult['g'][$parentname] = true;
                    break;
                }
            }
        }

        return $errorResult;
    }

/**
 * [deleteNullbyte description]
 * @param  [type] $str [description]
 * @return [type]      [description]
 */
    private function deleteNullbyte($str)
    {
        return str_replace("\0", '', $str);
    }

/**
 * [addressCheck description]
 * @param  [type] $src [description]
 * @return [type]      [description]
 */
    private function addressCheck($src)
    {
        $regulation = '/^[^0-9][a-zA-Z0-9_\-]+([.][a-zA-Z0-9_\-]+)*[@][a-zA-Z0-9_\-]+([.][a-zA-Z0-9_\-]+)*[.][a-zA-Z]{2,4}$/';
        if (!preg_match($regulation, $src)) {
            return true;
        } else {
            return false;
        }
    }

/**
 * メール送信
 * @param  string $to 宛先メールアドレス
 * @param  string $from 発信元メールアドレス
 * @param  string $subject タイトル
 * @param  string $template メールテンプレート
 * @return null
 */
    protected function sendMail($to, $from, $subject, $template)
    {
        $Mail = new Qdmail();
        $Mail->lineFeed("\n");

        $this->Smarty->assign('d', $this->inputData);
        $body = $this->Smarty->fetch($template);

        $Mail->easyText(
             array($to, ''),
             $subject ,
             $body,
             array($from, '')
         );
    }
}
