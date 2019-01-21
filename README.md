## 概要
salesforceのAPI経由にOAuthログイン、データ取得するための関数  

https://help.salesforce.com/articleView?id=remoteaccess_oauth_username_password_flow.htm&type=5

## 仕様について注意
APIのバージョンは「v44.0」の時に作成しています。  
以降のバージョンや以前のバージョンでは動作しない場合がありますのでご了承ください。

また、[Webサーバー認証フロー](https://help.salesforce.com/articleView?id=remoteaccess_oauth_web_server_flow.htm&type=5)とは別の方法でOAuthログインします。

取得するデータの形式は```json```にする。

## ライブラリを読み込み
### ファイルを直接読み込む
リポジトリ内の```src```ディレクトリ内にある、```SalesforceFunctions.php```をファイルを読み込める場所に移動します。

```php
require_once 'SalesforceFunctions.php';

use SalesforceAPIByUsernameAndPassword;

$salesforce = new SalesforceAPIByUsernameAndPassword\SalesforceFunctions([
  'client_id' => '',        // クライアントID
  'client_secret' => '',    // シークレット鍵
  'user_name' => '',        // ユーザ名
  'password' => '',         // パスワード
  'domain' => 'https://(ドメイン).salesforce.com'
]);
```

#### バージョンを指定する場合
何も指定しない場合は、最新バージョンを取得します。
バージョンを指定する場合は、```api_version```を追加します。  
例えばバージョン44を指定する場合は、```v44.0```と指定します。

#### セキュリティートークンを指定する
セキュリティトークンを指定する場合は、```security_token```を追加します。

## OAuth認証
salesforceにOAuth認証します。  
戻り値に、<b>アクセストークン</b>と<b>インスタンスURL</b>を返します。

```php
$token = $salesforce->joinOAuth();
if(empty($token)){
    // 取得できていなければエラー処理をする
}
```

## データ取得
json形式でデータを取得します。

```php
$salesforce->getRecodes(SOQLクエリ);
// レコードの連想配列
```
