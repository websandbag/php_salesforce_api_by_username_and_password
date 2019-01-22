## 概要
salesforceのAPI経由にOAuthログイン、データ取得するための関数  

## 仕様について注意
### APIのバージョンについて
APIのバージョンは<b>v44.0</b>の時に作成しています。  
以降のバージョンや以前のバージョンでは動作しない場合がありますのでご了承ください。

### OAuth認証の方法について
[ユーザー名パスワード](https://help.salesforce.com/articleView?id=remoteaccess_oauth_username_password_flow.htm&type=5)の方法を使用します。  
[Webサーバー認証フロー](https://help.salesforce.com/articleView?id=remoteaccess_oauth_web_server_flow.htm&type=5)(ブラウザ経由)とは別の方法でOAuthログインします。

### APIで出力するデータの形式について
取得するデータの形式に```xml```と```json```があると思います。
このスクリプトは```json```にしてください。

## ライブラリを読み込み
### ファイルを直接読み込む
リポジトリ内の```src```ディレクトリ内にある、```SalesforceFunctions.php```をファイルを読み込める場所に移動します。

```php
require_once 'SalesforceFunctions.php';

$salesforce = new SalesforceAPIByUsernameAndPassword\SalesforceFunctions([
  'client_id' => '',        // クライアントID
  'client_secret' => '',    // シークレット鍵
  'user_name' => '',        // ユーザ名
  'password' => '',         // パスワード
  'domain' => ''            // APIのドメイン
]);
```

APIのドメインは、```https://login.salesforce.com```の様な形式の情報です。

#### configファイル
クラスのコンストラクタに指定する要素は、```config/config.php```にまとめているのでファイルを複製して指定する事も可能です。

## OAuth認証
salesforceにOAuth認証します。  
戻り値に、<b>アクセストークン</b>と<b>インスタンスURL</b>を返します。

```php
$token = $salesforce->joinOAuth();
if(empty($token)){
    // 取得できていなければエラー処理
}
```

## データ取得
SOQLクエリを実行して結果を取得します。

```php
$records = $salesforce->getRecodes(SOQLクエリ);　// レコードの連想配列
if(! is_array($records)) {
    // 配列以外のデータの場合はエラー処理
}
```
