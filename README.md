## 概要

GitHub GraphQLを使って任意の条件のプルリクエストを取得するスクリプトです。  

## GitHub GraphQLお試し方法

```
curl -H "Authorization: bearer 自分のアクセストークン" -X POST -d 'クエリ' "https://api.github.com/graphql"
```

クエリはこんな感じでファイルから読み取ることができます。
```
curl -H "Authorization: bearer 自分のアクセストークン" -X POST -d @sample_search_query.txt "https://api.github.com/graphql"
```

## 事前準備

github.phpの以下を自身のものに変更してください。  

* ACCESS_TOKEN
* USER

## 実行方法

```
php src/main.php
```

data.csvが生成されます。