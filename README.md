# BookShelf

## 概要

BookShelf は、書籍の登録・閲覧・レビュー・お気に入り・レビューへのいいね・ジャンル管理・ランキング表示などを行える書籍管理アプリケーションです。

Laravel を使用した Web アプリケーションとして実装しており、Blade を利用した画面機能に加えて、JSON API も提供しています。

## 主な機能

- ユーザー登録・ログイン・ログアウト
- 書籍一覧表示
- 書籍詳細表示
- 書籍の新規登録・編集・削除
- ジャンル管理
- レビュー投稿・編集・削除
- レビューへのいいね
- お気に入り登録・解除
- お気に入り一覧
- 平均評価によるランキング表示
- 書籍API
- キーワード検索
- ジャンル絞り込み
- ページネーション

## ER図

```mermaid
erDiagram
    USERS ||--o{ BOOKS : creates
    USERS ||--o{ REVIEWS : writes
    USERS ||--o{ GENRES : creates

    BOOKS ||--o{ REVIEWS : has

    USERS ||--o{ FAVORITES : has
    BOOKS ||--o{ FAVORITES : has

    USERS ||--o{ REVIEW_LIKES : has
    REVIEWS ||--o{ REVIEW_LIKES : has

    BOOKS ||--o{ BOOK_GENRE : has
    GENRES ||--o{ BOOK_GENRE : has

    USERS {
        bigint id PK
        string name
        string email
        string password
    }

    BOOKS {
        bigint id PK
        bigint user_id FK
        string title
        string author
        string isbn
        date published_date
        string image_url
        text description
    }

    GENRES {
        bigint id PK
        bigint user_id FK
        string name
    }

    REVIEWS {
        bigint id PK
        bigint user_id FK
        bigint book_id FK
        tinyint rating
        text comment
    }

    FAVORITES {
        bigint id PK
        bigint user_id FK
        bigint book_id FK
    }

    REVIEW_LIKES {
        bigint id PK
        bigint user_id FK
        bigint review_id FK
    }

    BOOK_GENRE {
        bigint id PK
        bigint book_id FK
        bigint genre_id FK
    }