
<?php
// route.php
// Visualize the route on the map
require 'venues.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $selectedVenues = $_POST['venues'] ?? [];
    if (empty($selectedVenues)) {
        echo "<p>Please select at least one venue.</p>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bar Hopping Route</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.5/flowbite.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.5/flowbite.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src='https://api.mapbox.com/mapbox-gl-js/v2.14.0/mapbox-gl.js'></script>
    <link href='https://api.mapbox.com/mapbox-gl-js/v2.14.0/mapbox-gl.css' rel='stylesheet' />
</head>
<body class="bg-white text-black">
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Your Bar Hopping Route</h1>
    <div id="map" class="w-full h-96"></div>
</div>

<script>
    mapboxgl.accessToken = 'pk.eyJ1IjoiY2hhcmxpZW1jIiwiYSI6ImNtM3d2NW1jZDE3ejAyaW9ub3NzZWpwYzcifQ.6MHMVVW8LV1-bXcgGh1siw';
    const map = new mapboxgl.Map({
        container: 'map',
        style: 'mapbox://styles/mapbox/streets-v11',
        center: [153.021072, -27.470125],
        zoom: 14
    });

    const venues = <?php echo json_encode(array_intersect_key($venues, array_flip($selectedVenues))); ?>;
    const coordinates = [];

    for (let key in venues) {
        const venue = venues[key];
        const marker = new mapboxgl.Marker()
            .setLngLat([venue.lng, venue.lat])
            .setPopup(new mapboxgl.Popup().setText(venue.name))
            .addTo(map);

        coordinates.push([venue.lng, venue.lat]);
    }

    if (coordinates.length > 1) {
        map.on('load', function () {
            map.addSource('route', {
                'type': 'geojson',
                'data': {
                    'type': 'Feature',
                    'geometry': {
                        'type': 'LineString',
                        'coordinates': coordinates
                    }
                }
            });

            map.addLayer({
                'id': 'route',
                'type': 'line',
                'source': 'route',
                'layout': {
                    'line-join': 'round',
                    'line-cap': 'round'
                },
                'paint': {
                    'line-color': '#000',
                    'line-width': 6
                }
            });
        });
    }
</script>
</body>
</html>
