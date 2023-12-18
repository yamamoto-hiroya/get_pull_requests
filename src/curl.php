<?php
class Curl
{
  // @var  CurlHandle|false curlのリソース
  private $ch;

  /**
   * curlクライアントのコンストラクタ
   *
   * @param string $url
   * @param string $access_token
   * @return void
   */
  public function __construct(string $url, string $access_token)
  {
    // User-Agentは適当に入れておく
    // これがないとエラーとなるので自身のユーザー名等を指定してください
    $headers = [
      "Authorization: bearer {$access_token}",
      'User-Agent: hogehoge',
    ];
    $this->ch = curl_init();
    curl_setopt($this->ch, CURLOPT_URL, $url);
    curl_setopt($this->ch, CURLOPT_POST, 1);
    curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
  }

  /**
   * curl実行
   *
   * @param string $query graphqlのクエリ
   * @return string|bool 実行結果。失敗の場合はfalse
   */
  public function exec(string $query)
  {
    curl_setopt($this->ch, CURLOPT_POSTFIELDS, $query);
    return curl_exec($this->ch);
  }

  public function __destruct()
  {
    curl_close($this->ch);
  }
}