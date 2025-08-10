<h2>アプリケーション名</h2>
<p>coachtech 勤怠管理アプリ</p>

<h2>環境構築</h2>
<p>1.ディレクトリの作成</p>
<p>2.Docker-compose.yml の作成</p>
<p>3.Nginx / PHP / MySQL / phpMyAdmin の設定</p>
<p>4.コンテナ起動<br>
docker-compose up -d --build </p>
<p>5.Laravelプロジェクト作成<br>
docker-compose exec php bash<br>
composer create-project "laravel/laravel=8.*" . --prefer-dist
</p>
<p>6.環境設定<br>
config/app.php で 'timezone' => 'Asia/Tokyo',へ変更<br>
.env.example をコピーして .env を作成し、必要に応じて環境変数を変更<br>
.env に SESSION_COOKIE=laravel_session を追加
</p>

<h2>テーブル構造</h2>

<h3>usersテーブル</h3>

| カラム名    | 型               | NULL許可 | 外部キー         | 備考           |
|-------------|------------------|----------|------------------|----------------|
| id          | unsigned bigint   | NO       |                  | 主キー         |
| name        | varchar          | YES      |                  |                |
| email       | varchar          | YES      |                  |                |
| password    | varchar          | YES      |                  |                |
| created_at  | timestamp        | YES      |                  |                |
| updated_at  | timestamp        | YES      |                  |                |

---

<h3>attendancesテーブル</h3>

| カラム名   | 型               | NULL許可 | 外部キー         | 備考           |
|------------|------------------|----------|------------------|----------------|
| id         | unsigned bigint   | NO       |                  | 主キー         |
| user_id    | unsigned bigint   | YES      | users(id)        |                |
| clock_in   | datetime         | YES      |                  |                |
| clock_out  | datetime         | YES      |                  |                |
| created_at | timestamp        | YES      |                  |                |
| updated_at | timestamp        | YES      |                  |                |

---

<h3>correction_requestsテーブル</h3>

| カラム名        | 型                         | NULL許可 | 外部キー           | 備考                              |
|-----------------|----------------------------|----------|--------------------|-----------------------------------|
| id              | unsigned bigint            | NO       |                    | 主キー                            |
| user_id         | unsigned bigint            | YES      | users(id)          |                                   |
| attendance_id   | unsigned bigint            | YES      | attendances(id)    |                                   |
| work_break_id   | unsigned bigint            | YES      | work_breaks(id)    |                                   |
| column_name     | enum('clock_in','clock_out','start','end') | YES |                    | 修正対象カラム名                  |
| original_value  | datetime                  | YES      |                    | 修正前の値                       |
| corrected_value | datetime                  | YES      |                    | 修正後の値                       |
| reason          | text                      | YES      |                    | 修正理由                        |
| status          | enum('pending','approved') | YES      |                    | ステータス（保留 or 承認済み）  |
| created_at      | timestamp                 | YES      |                    |                                   |
| updated_at      | timestamp                 | YES      |                    |                                   |

---

<h3>work_breaksテーブル</h3>

| カラム名       | 型                      | NULL許可 | 外部キー           | 備考            |
|----------------|-------------------------|----------|--------------------|-----------------|
| id             | unsigned bigint         | NO       |                    | 主キー          |
| attendance_id  | unsigned bigint         | YES      | attendances(id)    |                 |
| break_type     | enum('start','end')     | YES      |                    | 休憩開始・終了  |
| occurred_at    | datetime                | YES      |                    | 休憩時間        |
| created_at     | timestamp               | YES      |                    |                 |
| updated_at     | timestamp               | YES      |                    |                 |

---

<h3>adminsテーブル</h3>

| カラム名    | 型               | NULL許可 | 外部キー | 備考    |
|-------------|------------------|----------|----------|---------|
| id          | unsigned bigint   | YES      |          | 主キー  |
| name        | varchar          | YES      |          |         |
| email       | varchar          | YES      |          |         |
| password    | varchar          | YES      |          |         |
| created_at  | timestamp        | YES      |          |         |
| updated_at  | timestamp        | YES      |          |         |


<h2>使用技術</h2>
<p>言語: php 7.4.9</p>
<p>フレームワーク: Laravel 8.83.8</p>
<p>データベース: MySQL 8.0.26 </p>
<p>Docker: Docker, docker-compose</p>

<h2>ER図</h2>
<img src="" alt="ER図">

<h2>URL</h2>
<p>開発環境：http://localhost/</p>
<p>phpMyAdmin：http://localhost:8080/</p>
