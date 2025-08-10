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
