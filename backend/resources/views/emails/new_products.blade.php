<!DOCTYPE html>
<html>
<head>
    <title>New Products on Interdiscount</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 80%;
            margin: auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333333;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        li {
            margin-bottom: 20px;
            padding: 10px;
            border-bottom: 1px solid #dddddd;
        }
        a {
            text-decoration: none;
            color: #1a73e8;
            font-weight: bold;
        }
        img {
            max-width: 100px;
            display: block;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>New Product(s) on {{ $shop->name }}</h1>
        <ul>
            @foreach ($products as $product)
                <li>
                    <a href="{{ $product['url'] }}">{{ $product['title'] }} - {{ $product['price'] }}</a>
                    <img src="{{ $product['largest_image_url'] }}" />
                </li>
            @endforeach
        </ul>
    </div>
</body>
</html>
