<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Drop In PDF</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        h1 {
            color: #333;
        }

        .info {
            margin-bottom: 10px;
        }

        .info label {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <h1>Drop In Information</h1>
    @foreach ($data as $key => $value)
        <div class="info">
            <label>{{ ucfirst($key) }}:</label>
            <span>{{ is_array($value) ? json_encode($value) : $value }}</span>
        </div>
    @endforeach
</body>

</html>
