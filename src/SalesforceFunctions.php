<?php
/***
 * sales force api用のデータ取得クラス
 * api version 44に対応
 */

namespace SalesforceAPIByUsernameAndPassword;

class SalesforceFunctions {

  // カスタムオブジェクトの識別子
  const ID_CUSTOM_KEY = "__c";
  const ID_CUSTOM_RELATION = "__r";

  // APIの基準パス
  const API_PATH_BASE = '/services/data/';
  const API_PATH_OAUTH = '/services/oauth2/token';

  private $config = null;
  private $instanceURL = null;
  private $accessToken = null;
  private $urlVersion = null;
  private $salesForceIDPaterns = [];

  public function __construct($config = []) {
    $defaultConfig = [
      'grant_type' => 'password',
      'client_id' => '',
      'client_secret' => '',
      'user_name' => '',
      'password' => '',
      'security_token' => null,
      'domain' => '',
      'api_version' => null
    ];
    $this->config = array_merge($defaultConfig, $config);
    $this->salesForceIDPaterns = array_map(function($key){
      return "/{$key}?$/";
    }, [self::ID_CUSTOM_KEY, self::ID_CUSTOM_RELATION]);
  }

  protected static $_ns = __NAMESPACE__;

  // アクセストークン取得
  public function joinOAuth(){
    $config = $this->config;

    // Salesforceに接続
    $posts = [
      'grant_type' => $config['grant_type'],
      'client_id' => $config['client_id'],
      'client_secret' => $config['client_secret'],
      'username' => $config['user_name'],
      'password' => $config['password']
    ];

    // セキュリティトークン付与
    if(! is_null($config['security_token'])){
      $posts['password'] = $posts['password'] . $config['security_token'];
    }

    //　セッションに退避
    $instanceURL = '';
    $accessToken = '';
    try {
      // リクエストを解析して変数に代入
      $json = json_decode($this->getHttpResponseInPost(
        $config['domain'] . self::API_PATH_OAUTH,
        $posts
      ));
      $instanceURL = $json->instance_url;
      $accessToken = $json->access_token;
    } catch ( SalesforceApiException $e) {
      return null;
    }

    // トークンを変数にセットする
    $this->setToken($accessToken, $instanceURL);
    $this->setVersionURL($this->config['api_version']);

    // 戻り値を吐き出し
    return $this->getToken();

  }

  // トークン情報をセットする
  public function setToken($accessToken, $instanceURL){
    $this->instanceURL = $instanceURL;
    $this->accessToken = $accessToken;
  }

  // トークン情報を取得
  public function getToken(){
    return [$this->accessToken, $this->instanceURL];
  }

  // GETでリクエストする場合
  private function getHttpResponseInGet($url, $accessToken = null) {
    $headers = [];
    if(! is_null($accessToken)){
      $headers[] = $this->getAccessTokenHeader($accessToken);
    }
    return $this->getHttpResponse($url, 'GET', [], $headers);
  }

  // POSTでリクエストする場合
  private function getHttpResponseInPost($url, array $posts = [], $accessToken = null) {
    $headers = [];
    if(! is_null($accessToken)){
      $headers[] = $this->getAccessTokenHeader($accessToken);
    }
    return $this->getHttpResponse($url, 'POST', $posts, $headers);
  }

  // アクセストークンを指定する場合、Headerに追加する内容を取得
  private function getAccessTokenHeader($accessToken){
    return "Authorization: Bearer {$accessToken}";
  }

  // POST送信して値を取得する
  private function getHttpResponse(
    $url,
    $requestType = 'GET',
    array $posts,
    array $headers
  ){
    // curl初期設定
    $curl = curl_init();

    // curl設定
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $requestType);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $posts);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    // curl実行
    $buf = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    // エラーのステータスコードが帰ってきた場合はnullを返す
    if(curl_errno($curl)){
      return null;
    }
    curl_close($curl);

    return $buf;

  }

  // Attachmentオブジェクトからファイルを取得する
  public function getAttachementFile(
    $id
  ){
    // Attachement経由でデータを取得する。
    return $this->getBlobDataBody('Attachment', $id);

  }

  // sObjectから生データを取得
  // https://developer.salesforce.com/docs/atlas.ja-jp.api_rest.meta/api_rest/resources_sobject_blob_retrieve.htm
  // https://developer.salesforce.com/docs/atlas.ja-jp.api_rest.meta/api_rest/dome_sobject_blob_retrieve.htm
  public function getBlobDataBody(
    $sObjectName,
    $id
  ){
    $url = $this->instanceURL . $this->urlVersion . 'sobjects/' . $sObjectName . '/' . $id . '/body';
    return $this->getHttpResponseInGet($url, $this->accessToken);
  }

  // 取得可能なリソース名を確認
  // https://developer.salesforce.com/docs/atlas.ja-jp.api_rest.meta/api_rest/resources_discoveryresource.htm
  public function getResources(){
    $url = $this->instanceURL . $this->urlVersion;
    return $this->getHttpResponseInGet($url, $this->accessToken);
  }

  // 取得可能なsObjectを確認
  // https://developer.salesforce.com/docs/atlas.ja-jp.api_rest.meta/api_rest/resources_describeGlobal.htm
  public function getAcquirableObject(){
    $url = $this->instanceURL . $this->urlVersion . 'sobjects/';
    return $this->getHttpResponseInGet($url, $this->accessToken);
  }

  // 項目リストを取得
  // https://developer.salesforce.com/docs/atlas.ja-jp.api_rest.meta/api_rest/resources_sobject_describe.htm
  public function getItemList($sObjectName){
    $url = $this->instanceURL  . $this->urlVersion . 'sobjects/' . $sObjectName . '/describe/';
    return $this->getHttpResponseInGet($url, $this->accessToken);
  }

  // SalesforceからSOQLクエリを使ってデータを取得する
  public function getDataInSoql($query){
    // ベースのURLを作成
    $data = [];
    $url = $this->instanceURL . $this->urlVersion . 'query?q=' . urlencode($query);
    try {
      // リクエストを解析して変数に代入
      $rowData = json_decode(
        $this->getHttpResponseInGet($url, $this->accessToken), true);
    } catch ( SalesforceApiException $e) {
      return null;
    }
    return $rowData;
  }

  // API経由で取得したデータからRecodesのデータを返す
  public function getRecodes($query) {
    $rowData = $this->getDataInSoql($query);
    if(! isset($rowData['done']) || ! $rowData['done']){
      return null;
    }
    array_shift($rowData);
    return $this->processRowData(
      $rowData['records']
    );
  }

  // レコード数を取得する
  public function getRecodeCount($query){
    $rowData = $this->getDataInSoql($query);
    if(! isset($rowData['done']) || ! $rowData['done']){
      return null;
    }
    return $this->processRowData(
      $rowData['totalSize']
    );
  }

  // レコードの有無
  public function hasRecode($query){
    $count = $this->getRecodeCount($query);
    return (is_integer($count) && $count > 0);
  }

  // salesforceから取得した値を整形する
  private function processRowData($string) {
    if(is_array($string)) {
      // attrivuteキーを除去
      unset($string['attributes']);

      // キーからsalesforce用の識別子削除
      $buff = [];
      foreach ($string as $key => $value) {
        if(gettype($key) === 'string') {
          $key = preg_replace($this->salesForceIDPaterns, '', $key);
        }
        $buff[$key] = $value;
      };
      return array_map(array($this, __FUNCTION__), $buff);
    } else {
      return $string;
    }
  }

  // APIのベースURLを取得する
  private function setVersionURL($apiVersion = null){
    if(! is_null($apiVersion)){
      // version固定
      $this->urlVersion = self::API_PATH_BASE . $apiVersion . '/';
      return $this->urlVersion;
    }

    $url = $this->instanceURL . self::API_PATH_BASE;
    try {
      // リクエストを解析して変数に代入
      $json = json_decode($this->getHttpResponseInGet($url));
      $newVersion = end($json);
      $this->urlVersion = $newVersion->url . '/';
    } catch ( SalesforceApiException $e) {
      return;
    }
    return $this->urlVersion;
  }

}
?>
