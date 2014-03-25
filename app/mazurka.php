<?php
/**
 * mazurka FormFramework
 *
 * @version       1.0
 * @since         1.0
 * @copyright     Copyright (c) 2014 macchaka, Omura Printing Co.,ltd.
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

class mazurka {
    public $input_data = array();
    public $err_result = array();
    public $smarty;
    public $template;

    /**
     * [__construct description]
     */
    public function __construct() {
        mb_language(LANGUAGE);
        mb_internal_encoding(ENCODING);
        session_start();
        // Smartyを使うための準備
        require_once( APP_DIR . '/smarty/libs/Smarty.class.php' );
        $this->smarty = new Smarty();
        $this->smarty->template_dir = APP_DIR . '/' . FORM_NAME . '/templates/';
        $this->smarty->compile_dir  = APP_DIR . '/' . FORM_NAME . '/templates_c/';
        $this->smarty->config_dir   = APP_DIR . '/' . FORM_NAME . '/configs/';
        $this->smarty->cache_dir    = APP_DIR . '/' . FORM_NAME . '/cache/';
    }

    /**
     * [dispatch description]
     * @param  [type] $postdata [description]
     * @return [type]           [description]
     */
    public function dispatch($postdata) {
        require (APP_DIR . '/' . FORM_NAME . '/configs/field.php');

        if(!empty($postdata)){
            //フォームデータの取得
            foreach($postdata as $key => $val) {
                $convert_regulation = @$FormItem['field'][$key]['convert'];

                //変換指定があれば、変換してから値をセットする
                if ($convert_regulation){
                    $this->input_data[$key] = $this->_gpc_stripslashes($this->_delete_nullbyte(mb_convert_kana($val,$convert_regulation)));
                } else {
                    $this->input_data[$key] = $this->_gpc_stripslashes($this->_delete_nullbyte($val));
                }
            }

            switch($this->input_data['submit']) {
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
                        $this->input_data = $_SESSION['form'];
                        $this->send();
                    }
                    break;
                default:
                    echo $msg_syserr['timeout'];
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
    public function first() {
        //初回アクセス（POSTが送られてきていない）
        $this->template = TPL_INDEX;
    }

    /**
     * [back description]
     * @return [type] [description]
     */
    public function back() {
        //戻るボタンを押した→フォームへ差し戻し
        $input_data = $_SESSION['form'];
        $this->smarty->assign('d',$input_data);
        $this->template = TPL_INDEX;
    }

    /**
     * [proof description]
     * @return [type] [description]
     */
    public function proof() {
        //======== 入力審査
        $err_result = $this->input_chk();

        if ($err_result) {
            //エラーあり→入力画面へ
            $this->smarty->assign('d', $this->input_data);
            $this->smarty->assign('e', $err_result);
            $this->template = TPL_INDEX;
        } else {
            //入力エラーなし→確認画面へ
            //セッションへ記憶
            $_SESSION['form'] = $this->input_data;
            $this->smarty->assign('d',$this->input_data);
            $this->template = TPL_PROOF;
        }
    }

    /**
     * [send description]
     * @return [type] [description]
     */
    public function send() {
        //送信（確認画面で送信）
        //======== 送信実行＜確認画面で「送信」ボタンを押されたため

        require_once (APP_DIR . '/qdmail/qdmail.php' );
        require      (APP_DIR . '/' . FORM_NAME . '/configs/field.php');
        $MailKey = $FormItem['MailItemName'];
        $MailAdr = $this->input_data[$MailKey];

        //■メール送信
        //（登録者向け）
        $this->smarty->assign('d', $this->input_data);
        $body = $this->smarty->fetch(TPL_MAIL_EXTERNAL);

        $mail = new Qdmail();
        $mail->lineFeed("\n");

        $mail->easyText(
             array( $MailAdr , '' ),
             SUBJECT_EXTERNAL ,
             $body,
             array(ADMIN_MAIL , '')
         );

        //（管理者向け）
        $this->smarty->assign('d',$this->input_data);
        $body = $this->smarty->fetch(TPL_MAIL_INTERNAL);

        $mail->easyText(
              array( ADMIN_MAIL , '' ),
              SUBJECT_INTERNAL ,
              $body,
              array($MailAdr , '')
         );

        //画面切り替え
        $this->template = TPL_COMPLETE;
        session_destroy();
    }

    /**
     * [render description]
     * @return [type] [description]
     */
    public function render() {
        $this->smarty->display($this->template);
    }

    /**
     * [input_chk description]
     * @return [type] [description]
     */
    public function input_chk(){
        $err_result = array();

        require (APP_DIR . '/' . FORM_NAME . '/configs/field.php');

        //メールアドレスチェック
        $MailKey = $FormItem['MailItemName'];
        if (isset($this->input_data[$MailKey]) && $this->input_data[$MailKey] != '' ) {
            if($this->_address_check($this->input_data[$MailKey])){
                $err_result['f'][$MailKey]['invalid'] = TRUE;
            }
        } else {
            $err_result['f'][$MailKey]['required'] = TRUE;
        }

        //合致チェック
        foreach($FormItem['match'] as $key => $val){
            if ( @$this->input_data["{$key}"] != @$this->input_data["{$val}"] ) {
                $err_result['f'][$key]['mismatch'] = TRUE;
            } else {
                $err_result['f'][$key]['mismatch'] = FALSE;
            }
        }

        //設定された内容でチェック
        foreach($FormItem['field'] as $key => $val){
            $null_chk = @$val['require'];
            $str_length = @$val['max_length'];
            $accept_pattern = @$val['valid_format'];
            $reject_pattern = @$val['invalid_format'];

            $err_msg = "";
            $err_pattern = false;

            if (isset($this->input_data[$key])) {
                $value = $this->input_data[$key];
            } else {
                $value = "";
            }

            //必須チェック
            if($null_chk){
                if($value == ""){
                    $err_result['f'][$key]['required'] = TRUE;
                }
            }

            //文字数
            if($str_length){
                if(mb_strlen($value,ENCODING) > $str_length ){
                    $err_result['f'][$key]['max_length'] = TRUE;
                }
            }

            //有効書式
            if($accept_pattern){
                if($value != "" && !preg_match("/".$accept_pattern."/",$value)){
                    $err_result['f'][$key]['invalid'] = TRUE;
                }
            }

            //無効書式
            if($reject_pattern){
                if($value != "" && preg_match("/".$reject_pattern."/",$value)){
                    $err_result['f'][$key]['invalid'] = TRUE;
                }
            }
        }

        //親フィールドへのエラー状況更新
        foreach($FormItem['group'] as $parentname => $child){
            $errflg = 0;
            foreach($child as $fieldname){
                if (@$err_result['f'][$fieldname]){
                    $errflg += 1;
                }
            }
            if ($errflg){
                $err_result['g'][$parentname] = TRUE;
            }
        }

        return $err_result;
    }

    /**
     * [_gpc_stripslashes description]
     * @param  [type] $str [description]
     * @return [type]      [description]
     */
    private function _gpc_stripslashes($str){
        //gpcの設定に応じて、stripslashersをかける
        if (get_magic_quotes_gpc()==1) {
            return stripslashes($str);
        } else {
            return $str;
        }
    }

    /**
     * [_delete_nullbyte description]
     * @param  [type] $str [description]
     * @return [type]      [description]
     */
    private function _delete_nullbyte($str)
    {
        return str_replace("\0", "", $str);
    }

    /**
     * [_address_check description]
     * @param  [type] $src [description]
     * @return [type]      [description]
     */
    private function _address_check($src){
		$regulation = '/^[^0-9][a-zA-Z0-9_\-]+([.][a-zA-Z0-9_\-]+)*[@][a-zA-Z0-9_\-]+([.][a-zA-Z0-9_\-]+)*[.][a-zA-Z]{2,4}$/';
        if(!preg_match($regulation,$src)){
        	return TRUE;
        } else {
        	return FALSE;
        }
    }
}
