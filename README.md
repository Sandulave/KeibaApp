<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Project Manual
- Japanese user manual: `docs/USER_MANUAL_JA.md`
- Japanese quick manual (user-only): `docs/USER_QUICK_MANUAL_JA.md`

## 環境別レースデータ

本番G1、検証、夏競馬は同じGit・同じmigrationを使い、環境ごとの `.env` で接続先DBと流すレースデータを分けます。

G1環境:

```env
APP_NAME=初心者G1馬券バトル
APP_URL=https://app.g1keibabattle.com
KEIBA_SITE_TYPE=g1
KEIBA_SITE_LABEL="${APP_NAME}"
```

夏競馬環境:

```env
APP_NAME=夏競馬バトル
APP_URL=https://summer.g1keibabattle.com
KEIBA_SITE_TYPE=summer
KEIBA_SITE_LABEL="${APP_NAME}"
```

どちらの環境でもmigrationは共通です。

```bash
php artisan migrate
```

Seederは `KEIBA_SITE_TYPE` に応じて `DatabaseSeeder` が自動選択します。

```bash
php artisan db:seed
```

個別に流す場合:

```bash
php artisan db:seed --class=G1Races2026Seeder
php artisan db:seed --class=SummerRaces2026Seeder
```

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## DBメンテナンスモード運用

`app_settings` テーブルの値でメンテナンス画面（503）を切り替えられます。

- ON: `maintenance_enabled = 1`
- OFF: `maintenance_enabled = 0`
- 任意メッセージ: `maintenance_message`

例（MySQL）:

```sql
UPDATE app_settings SET value = '1' WHERE `key` = 'maintenance_enabled';
UPDATE app_settings SET value = '本日 22:00 までメンテナンス予定です' WHERE `key` = 'maintenance_message';

UPDATE app_settings SET value = '0' WHERE `key` = 'maintenance_enabled';
UPDATE app_settings SET value = NULL WHERE `key` = 'maintenance_message';
```
