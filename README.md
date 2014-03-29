mazurka
=======
This software is released under the MIT License, see LICENSE.

ファイル構造
------------
    /  
    form.php           実際にリンクを張る呼び出しプログラムと、各種設定（リネーム可）
    css/               サンプル（※）
      img/               サンプル（※）
      js/                サンプル（※）
      app/               mazurka本体
        contact/         サンプルフォームディレクトリ
          cache/         Smartyのcacheディレクトリ（本システムでは未使用）
          configs/       Smartyのconfigsディレクトリ兼本システムの設定ディレクトリ
            fields.php   フィールド設定ファイル
          templates/     Smartyのtemplatesディレクトリ（サンプル同梱）
          templates_c/   Smartyのコンパイル済データ保存ディレクトリ
        qdmail/          メール送信ライブラリのQdmailを同梱しています。
        smarty/libs/     Smartyのlibsファイルを格納する場所です（同梱していません）

※サンプル用にtwitter Bootstrapが配置してありますが、使用する必要はありません。テンプレート上に書いたパスに準拠するため、全て削除して、本番環境に適合させて下さい。


設置方法
--------
- form.phpは自由に変更して構いませんが、テンプレート上で`action="form.php"`を書き換えるのを忘れないようにして下さい。

設定方法
--------

テンプレート記述方法
--------------------
mazurkaはテンプレートエンジンとしてSmartyを使用しているため、書き方はsmartyに準じます。
公式のマニュアルを参照して下さい。
http://www.smarty.net/docsv2/ja/

ここでは、mazurka特有の変数名の説明を通じて、基本的な記述方法について触れます。

SmartyはPHPを使っていますが、<?php echo 〜 ?>といった記述は不要です。その代わりに{}のカッコに囲んで、専用タグとして使用します。
テンプレート上{}を文字として表示したい場合は、それぞれ{ldelim}{rdelim}と入力します。
JavaScriptがソースコード上にある場合は、{literal}{/literal}で囲みます。この中はSmartyタグとしては解釈しません。

本システムは、入力された項目の内容を$dとして引き渡します。dとはデータを意味します。nameをname_companyとした項目を表示するには` {$d.name_company} `とします。

フォームが投稿された際に、設定に基づきバリデーションチェックを行いますが、エラーとなった場合は入力画面に差し戻します。
そのとき、エラーの内容は$eとして引き渡します。eとはerrorを意味しています。$eは次のような階層でエラー情報が入っています。

    'e' =>
        'f' =>
            'フィールド名' =>
                'エラー名' => true
        'g' =>
            'グループ名' => true

バリデーションエラーが発生していない場合は、$eには値がありません。そのため、エラーが発生しているかどうか自体を次のように利用することができます。

    {if $e}<span class="label">入力エラーがあります。</span>{/if}

`{if 変数名}変数名がtrueの場合に表示させる内容{/if}`という書き方になります。SmartyのifはPHPのifと準じますので、ここでいう変数名がtrueとは、PHPのif関数がどのように判断するかに準じます。簡単にいうと値が存在していて、かつ0以上の値を持つものがtrueと判定されます。（※詳しくは[boolean への変換](http://jp1.php.net/manual/ja/language.types.boolean.php#language.types.boolean.casting)を参照して下さい。)

もちろん、ifはエラーだけでなく、柔軟に記述できます。これについては[組み込み関数 {if},{elseif},{else}](http://www.smarty.net/docsv2/ja/language.function.if.tpl)を参照して下さい。

エラーが発生している場合は、`$e => 'f'`にエラー内容が入ります。このfはフィールドを意味します。フィールド`name_family`が、必須項目違反したときにエラーを表示するには、次のようにします。

    {if $e.f.name_family.required}<span class="help-inline">お名前（姓）を入力してください。</span>{/if}

現在サポートされているエラーは次の通りです。（定義とはconfigs/fields.phpでの定義を指します。）

    required     requiredがtrueと定義されている場合に、入力がなされていない場合にtrue。
    invalid      valid_format定義に反した場合、またはinvalid_format定義に合致した場合にtrue。
    max_length   max_lengthで文字数が定義されている場合に、これを超過した場合にtrue。

例えば、姓と名のように項目としては分かれているが、エラーは一体管理したいという場合があります。mazurkaではこれを「グループ」として管理しています。グループは、configs/fields.phpで定義していますが、グループ内の項目でいずれかのエラーが発生したときにtrueとなります。何のエラーが発生しているかは、個々のフィールドのエラーを参照して下さい。グループは、グループ内でエラーが発生していることを示しているに過ぎません。

    <div class="control-group{if $e.g.familyName} error{/if}">

この場合は、エラーがなければ`class="control-group"`になりますが、エラーがあると`class="control-group error"`となります。twitter Bootstrapの場合は、ラベルと項目に赤い線が表示されて、項目全体でエラーが発生していることが分かりやすく表示されます。
細かなエラー内容を取得できないため、本格的なエラー表示には使いにくい側面がありますが、逆に「個々に表示されるとうざったい画面になる」場合に、簡略化したエラー表示には利用できると思います。（姓名それぞれにエラー表示させずに、グループで「入力を確認して下さい」とエラー表示するなど）

同梱している他のソフトウェアと著作権表示
----------------------------------------
### Qdmail 1.2.6b
[Copyright 2008, Spok.](http://hal456.net/qdmail/)  
[The MIT License](http://www.opensource.org/licenses/mit-license.php)

### Bootstrap v2.1.0
Copyright 2012 Twitter, Inc.
http://www.apache.org/licenses/LICENSE-2.0
