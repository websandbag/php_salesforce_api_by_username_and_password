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

$salesforce = new SalesforceAPIByUsernameAndPassword\SalesforceFunctions([
  'client_id' => '',        // クライアントID
  'client_secret' => '',    // シークレット鍵
  'user_name' => '',        // ユーザ名
  'password' => '',         // パスワード
  'domain' => 'https://(登録しているAPIのドメイン).salesforce.com'
]);
```

#### configファイル
クラスのコンストラクタに指定する要素は、```config/config.php```にまとめているのでファイルを複製して指定する事も可能。

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
if(! is_array($lists)) {
    // 配列以外のデータの場合はエラー処理
}
```
