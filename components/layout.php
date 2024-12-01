<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Bar Crawl Planner' ?></title>
    
    <!-- Tailwind and Catalyst UI -->
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    
    <!-- Mapbox -->
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" />
    
    <!-- Custom styles -->
    <link href="assets/css/styles.css" rel="stylesheet" />
    
    <script src="includes/tokens.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter var', ...defaultTheme.fontFamily.sans],
                    },
                },
            },
        }
    </script>
</head>
<body class="h-full relative">
    <?= $content ?>
</body>
</html> 