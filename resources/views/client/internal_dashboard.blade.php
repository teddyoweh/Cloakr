<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cloakr Dashboard</title>

    <link rel="apple-touch-icon" sizes="180x180" href="/files/build/internal-dashboard/img/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/files/build/internal-dashboard/img/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/files/build/internal-dashboard/img/favicon/favicon-16x16.png">

    <link href="{{ $cssFile }}" rel="stylesheet">
</head>

<body>
    <div id="internalDashboard" data-page='@json($page)'></div>

    <script src="{{ $jsFile }}"></script>
</body>

</html>
