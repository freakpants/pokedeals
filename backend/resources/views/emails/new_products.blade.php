<!DOCTYPE html>
<html>
<head>
    <title>New Products on Interdiscount</title>
</head>
<body>
    <h1>New Products on Interdiscount</h1>
    <ul>
        @foreach ($newProducts as $product)
            <li><a href="{{ $product['url'] }}">{{ $product['title'] }} - {{ $product['price'] }}</a></li>
        @endforeach
    </ul>
</body>
</html>
