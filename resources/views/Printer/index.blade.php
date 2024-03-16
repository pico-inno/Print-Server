<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Printers</title>
</head>

<body>
    <h1>Available Printers</h1>

    @if ($printers)
        <ul>
            @foreach ($printers as $printer)
                <li>{{ $printer['name'] }}</li>
            @endforeach
        </ul>
    @else
        <p>No printers found.</p>
    @endif
</body>

</html>

</html>
