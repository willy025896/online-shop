<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Redirecting to payment…</title>
</head>
<body>
    <p>Redirecting to the payment gateway, please wait…</p>

    <form id="ecpay-form" method="POST" action="{{ $url }}">
        @foreach ($fields as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach
    </form>

    <script>
        document.getElementById('ecpay-form').submit();
    </script>
</body>
</html>
