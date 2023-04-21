<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title')</title>

    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">

    @include("errors::minimal.style")
</head>
<body>
<div class="container">
    <div class="row">
        <div class="error">
            <div class="code">@yield('code')</div>
            <div class="message">@yield('message')</div>
        </div>
        <div class="to_main"><a href="/">To main page</a></div>
    </div>
</div>

</body>
</html>