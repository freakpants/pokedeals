<!DOCTYPE html>
<html>
<head>
    <title>{{ $subject }}</title>
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
        <h1>{{ $subject }}</h1>
        <ul>
            @foreach ($products as $product)
                <li>
                    <a href="{{ $product['url'] }}">{{ $product['title'] }} - {{ $product['price'] }}</a>
 @if (!empty($product['variants']) && is_array($product['variants']))
            @foreach ($product['variants'] as $variant)
                @if (!empty($variant['title']) && (!isset($variant['available']) || $variant['available']))
                    <div style="font-size: 0.95em; color: #555;">
                        Variant: {{ $variant['title'] }}
                    </div>
                    @break
                @endif
            @endforeach
        @endif
                    @if (!empty($product['largest_image_url']))
                        <img src="{{ $product['largest_image_url'] }}" />
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</body>
</html>
