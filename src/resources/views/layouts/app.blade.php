<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{asset('css/common.css')}}">
    <link rel="stylesheet" href="{{asset('css/sanitize.css')}}">
    @yield('css')
    <title>coachtech勤怠管理アプリ</title>
</head>
<body>
    <header class="header">
        <div class="header__inner">
            <a href="{{ Auth::check() && Auth::user()->is_admin ? url('/admin/attendance/list') : url('/attendance') }}" class="header__logo">
                <img src="{{asset('images/logo.svg')}}" alt="COACHTECH">
            </a>
        </div>
        @yield('header-menu')
    </header>
    <main>@yield('content')</main>
</body>

@yield('js')
</html>