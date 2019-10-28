# PHP セブンアフィリエイトAPI

セブンアフィリエイトAPIを読み込むためのシンプルなクラスです

[詳細な使い方はこちらで確認できます](http://blog.asfant.com/2019/10/29/php%e3%81%a7%e3%82%bb%e3%83%96%e3%83%b3%e3%82%a2%e3%83%95%e3%82%a3%e3%83%aa%e3%82%a8%e3%82%a4%e3%83%88api%e3%82%92%e5%88%a9%e7%94%a8%e3%81%99%e3%82%8b/)

## 初期化

APIユーザIDと秘密鍵はそれぞれ発行されたキーに書き換えてください

    <?php
    require_once 'src/SevenApi.php';
    //APIユーザID
    $apiUserId = 'XXXXXXXX';
    //秘密鍵
    $secretKey = 'XXXXXXXX';
    $api = new SevenApi( $apiUserId, $secretKey );
    ?>

## カテゴリ検索

以下はカテゴリ検索APIを呼び出している例です

    <?php
    //カテゴリ検索
    $result = $api->category();
    
    if( $result->isOk){
        //取得成功
        print_r($result->response);
    }else{
        //エラーの場合
        print($result->errorMessage);
    }
    ?>

## 商品検索

以下は商品検索APIを呼び出している例です

    <?php
    //商品検索
    $result = $api->product(array(
        'KeywordIn' => 'ありふれた職業で世界最強',
    ));
    
    if( $result->isOk){
        //取得成功
        print_r($result->response);
    }else{
        //エラーの場合
        print($result->errorMessage);
    }
    ?>

## ランキング検索

以下はランキング検索APIを呼び出している例です

    <?php
    //ランキング検索
    $result = $api->ranking(array(
        'CategoryCode' => '002000',
    ));
    
    if( $result->isOk){
        //取得成功
        print_r($result->response);
    }else{
        //エラーの場合
        print($result->errorMessage);
    }
    ?>

## 商品レビュー検索

以下は商品レビュー検索APIを呼び出している例です

    <?php
    //商品レビュー検索
    $result = $api->review(array(
        'ProductCode' => '1106946311',
    ));
    
    if( $result->isOk){
        //取得成功
        print_r($result->response);
    }else{
        //エラーの場合
        print($result->errorMessage);
    }
    ?>

## APIのリクエストパラメータについて

APIのリクエストパラメータの詳細については以下から確認してください

https://7af.omni7.jp/af_static_site/API1.html
