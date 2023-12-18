<?php
require_once 'curl.php';

/**
 * 久々に実行する際はアクセストークンを再生成して入れ替え
 * 実行時は検索対象のmerged_atを指定する。
 * この期間が長ければ長い程実行時間は長くなるので注意。
 */
class Github
{
  const ACCESS_TOKEN = "";
  const URL = "https://api.github.com/graphql";
  const USER = "";

  // @var Curl
  private $curl_client;

  // @var string 検索対象のユーザー
  private $user;

  /**
   * Githubクライアントのコンストラクタ
   *
   * @param string $url エンドポイント
   * @param string $access_token アクセストークン
   * @param string $user 検索対象のユーザー
   * @return void
   */
  public function __construct(
    string $url = self::URL,
    string $access_token = self::ACCESS_TOKEN,
    string $user = self::USER
  ) {
    $this->curl_client = new Curl($url, $access_token);
    $this->user = $user;
  }

  /**
   * 実行関数
   *
   * @param string $merged_at 検索対象のmerged_at
   * @return bool 成功したらtrue
   */
  public function exec(string $merged_at): bool
  {
    $next_cursor = null;
    $has_next_page = true;
    // ネクストページがある分ループで取得する
    while ($has_next_page) {
      if ($next_cursor === null) {
        $after = '';
      } else {
        $after = 'after: \"' . $next_cursor . '\",';
      }
      $query = $this->_get_query($after, $merged_at);
      $output = $this->curl_client->exec($query);
      if ($output === false) {
        echo "curl実行失敗\n";
        return false;
      }
      $result = json_decode($output);

      // レスポンスからendCursorとhasNextPageを抜き出す
      $pull_requests = $result->data->search->nodes;
      $next_cursor  = $result->data->search->pageInfo->endCursor;
      $has_next_page  = $result->data->search->pageInfo->hasNextPage;
      $this->_output_csv($pull_requests);
    }
    return true;
  }

  /**
   * GraphQLのクエリを取得する
   *
   * @param string $after 次のページのカーソル
   * @param string $merged_at 検索対象のmerged_at
   * @return string graphqlのクエリ
   */
  private function _get_query(string $after, string $merged_at): string
  {
    $query = <<<END
{
  "query": "query {
    search(first: 100, {$after} query: \"user:{$this->user} is:pr is:merged merged:{$merged_at} base:master base:main\", type: ISSUE) {
      nodes {
        ... on PullRequest {
          title
          permalink
          repository{name}
          author{login}
          baseRefName
          mergedAt
          updatedAt
          commits(first: 1){nodes{commit{committedDate}}}
        }
      }
      pageInfo{endCursor hasNextPage}
    }
  }"
}
END;

    // MEMO: 改行を含んでいるとうまくいかなかったので削除
    $one_line_query = str_replace("\n", '', $query);
    return $one_line_query;
  }

  /**
   * 結果をCSVに出力する
   *
   * @param array[object] $pull_requests プルリクエストの配列
   * @return void
   */
  private function _output_csv(array $pull_requests)
  {
    // ループで呼び出す都合で追記形式とする。
    // 再実行時はファイルを削除してください。
    $fp = fopen('data.csv', 'a');

    /**
     * 出力形式
     * author,プルリクタイトル,プルリクURL,repository,base_branch,mergedAt,updatedAt,first_commit
     */
    foreach ($pull_requests as $pull_request) {
      $author = $pull_request->author->login ?? '削除済みユーザー';
      $title = '"' . $pull_request->title . '"';
      $permalink = $pull_request->permalink;
      $repository_name = $pull_request->repository->name;
      $baseRefName = $pull_request->baseRefName;
      $mergedAt = $this->_format_date($pull_request->mergedAt);
      $updatedAt = $this->_format_date($pull_request->updatedAt);
      $firstCommittedDate = $this->_format_date($pull_request->commits->nodes[0]->commit->committedDate);
      $line = [$author, $title, $permalink, $repository_name, $baseRefName, $mergedAt, $updatedAt, $firstCommittedDate];
      fwrite($fp, implode(',', $line) . "\n");
    }

    fclose($fp);
  }

  /**
   * スプレッドシートで扱いやすい形に変換する
   *
   * 例
   * 2023-08-16T04:33:02Z
   * ↓
   * 2023-08-16 13:33:02
   *
   * @param string $string_date 日付の文字列
   * @return string フォーマットした日付の文字列
   */
  private function _format_date(string $string_date): string
  {
    $datetime = new DateTime($string_date);
    $datetime->setTimeZone(new DateTimeZone('Asia/Tokyo'));
    return $datetime->format('Y-m-d H:i:s');
  }
}