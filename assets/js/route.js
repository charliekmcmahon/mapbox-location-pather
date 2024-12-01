class RouteManager {
    constructor() {
        this.cityCoordinates = {
            'Brisbane': [153.0281, -27.4679],
            'Sunshine Coast': [153.0666, -26.6500],
            'Sydney': [151.2093, -33.8688],
            'Melbourne': [144.9631, -37.8136],
            'Adelaide': [138.6007, -34.9285],
            'Canberra': [149.1300, -35.2809],
            'Perth': [115.8575, -31.9505]
        };

        this.initializeElements();
        this.loadLocations();
        this.initializeMap();
        this.initializeEventListeners();
    }

    initializeElements() {
        this.toggleDirections = document.getElementById('toggleDirections');
        this.directionsList = document.getElementById('directionsList');
        this.venuesList = document.getElementById('venuesList');
        this.allowWalking = document.getElementById('allowWalking');
        this.allowTransit = document.getElementById('allowTransit');
        this.allowDriving = document.getElementById('allowDriving');
        this.recalculateButton = document.getElementById('recalculateRoute');
        this.totalDistance = document.getElementById('totalDistance');
        this.totalTime = document.getElementById('totalTime');
        this.markers = [];
    }

    loadLocations() {
        const savedLocations = localStorage.getItem('crawlLocations');
        if (!savedLocations) {
            window.location.href = 'index.php';
            return;
        }
        this.locations = JSON.parse(savedLocations);
    }

    initializeMap() {
        mapboxgl.accessToken = MAPBOX_ACCESS_TOKEN;

        // Find the center point of all locations
        const bounds = this.locations.reduce((bounds, location) => {
            return bounds.extend(location.coordinates);
        }, new mapboxgl.LngLatBounds(this.locations[0].coordinates, this.locations[0].coordinates));

        this.map = new mapboxgl.Map({
            container: 'map',
            style: 'mapbox://styles/mapbox/streets-v12',
            center: bounds.getCenter(),
            zoom: 12
        });

        // Add markers for all locations
        this.locations.forEach((location, index) => {
            const el = document.createElement('div');
            el.className = 'marker-number';
            el.textContent = index + 1;

            const marker = new mapboxgl.Marker(el)
                .setLngLat(location.coordinates)
                .setPopup(new mapboxgl.Popup({ offset: 25 }).setHTML(`<p>${location.name}</p>`))
                .addTo(this.map);

            this.markers.push(marker);
        });

        // Fit map to show all markers with increased padding
        this.map.fitBounds(bounds, { 
            padding: {
                top: 250,
                bottom: 250,
                left: 300,
                right: 300
            }
        });
    }

    initializeEventListeners() {
        this.toggleDirections.addEventListener('click', () => {
            this.directionsList.classList.toggle('expanded');
            this.toggleDirections.querySelector('svg').classList.toggle('rotate-180');
        });

        this.recalculateButton.addEventListener('click', () => this.calculateRoute());

        // Handle back button click - preserve locations when going back
        document.querySelector('[data-action="back"]').addEventListener('click', (e) => {
            e.preventDefault();
            
            // Store current locations in localStorage with a flag indicating we're editing
            localStorage.setItem('editingLocations', 'true');
            // Store the current city
            localStorage.setItem('selectedCity', this.locations[0].city);
            
            // Navigate back to selector
            window.location.href = 'index.php';
        });

        // Initialize the route calculation
        this.map.on('load', () => this.calculateRoute());
    }

    async calculateRoute() {
        // Clear existing routes
        this.clearRoutes();
        this.directionsList.innerHTML = '';
        let totalDistance = 0;
        let totalDuration = 0;

        // Calculate routes between each pair of points
        for (let i = 0; i < this.locations.length - 1; i++) {
            const start = this.locations[i].coordinates;
            const end = this.locations[i + 1].coordinates;
            const routes = [];

            // Collect available routes based on preferences
            if (this.allowWalking.checked) {
                const walkingData = await this.getRoute(start, end, 'walking');
                if (walkingData?.routes?.[0]) {
                    routes.push({
                        type: 'walking',
                        data: walkingData.routes[0],
                        color: '#4f46e5',
                        icon: '🚶'
                    });
                }
            }

            if (this.allowTransit.checked) {
                const transitData = await this.getRoute(start, end, 'cycling');
                if (transitData?.routes?.[0]) {
                    routes.push({
                        type: 'transit',
                        data: transitData.routes[0],
                        color: '#059669',
                        icon: '🚌'
                    });
                }
            }

            if (this.allowDriving.checked) {
                const drivingData = await this.getRoute(start, end, 'driving');
                if (drivingData?.routes?.[0]) {
                    routes.push({
                        type: 'driving',
                        data: drivingData.routes[0],
                        color: '#f97316',
                        icon: '🚗'
                    });
                }
            }

            if (routes.length > 0) {
                // Choose the fastest route
                const bestRoute = routes.reduce((best, current) =>
                    current.data.duration < best.data.duration ? current : best
                );

                this.drawRoute(bestRoute, i);
                this.addDirectionsToList(bestRoute, i);
                totalDistance += bestRoute.data.distance;
                totalDuration += bestRoute.data.duration;
            }
        }

        // Update statistics
        this.totalDistance.textContent = `${(totalDistance / 1000).toFixed(2)} km`;
        this.totalTime.textContent = `${Math.round(totalDuration / 60)} mins`;
    }

    async getRoute(start, end, profile) {
        try {
            const response = await fetch(
                `https://api.mapbox.com/directions/v5/mapbox/${profile}/${start[0]},${start[1]};${end[0]},${end[1]}?geometries=geojson&access_token=${MAPBOX_ACCESS_TOKEN}&steps=true`
            );
            return await response.json();
        } catch (error) {
            console.error(`Error fetching ${profile} route:`, error);
            return null;
        }
    }

    drawRoute(route, index) {
        // Add the route to the map
        this.map.addSource(`route${index}`, {
            type: 'geojson',
            data: {
                type: 'Feature',
                properties: {},
                geometry: route.data.geometry
            }
        });

        this.map.addLayer({
            id: `route${index}`,
            type: 'line',
            source: `route${index}`,
            layout: {
                'line-join': 'round',
                'line-cap': 'round'
            },
            paint: {
                'line-color': route.color,
                'line-width': 4,
                'line-opacity': 0.75
            }
        });

        // Add transport change markers with updated styling
        if (index > 0) {
            const start = route.data.geometry.coordinates[0];
            const el = document.createElement('div');
            el.className = 'transport-marker';
            el.style.backgroundImage = `url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="${encodeURIComponent(route.color)}"><path d="M12 2L2 12h5v8h10v-8h5L12 2z"/></svg>')`;
            
            new mapboxgl.Marker(el)
                .setLngLat(start)
                .setPopup(
                    new mapboxgl.Popup({ offset: 25 })
                        .setHTML(`
                            <div class="px-2 py-1">
                                <p class="text-sm font-medium text-gray-900">
                                    Change to ${route.type}
                                </p>
                            </div>
                        `)
                )
                .addTo(this.map);
        }
    }

    addDirectionsToList(route, index) {
        const legElement = document.createElement('div');
        legElement.className = 'py-3';
        legElement.innerHTML = `
            <div class="flex items-center justify-between">
                <h4 class="text-sm font-medium text-gray-900">
                    Leg ${index + 1}: ${this.locations[index].name} to ${this.locations[index + 1].name}
                </h4>
                <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                    ${route.icon} ${route.type.charAt(0).toUpperCase() + route.type.slice(1)}
                </span>
            </div>
            <div class="mt-1 text-sm text-gray-500">
                ${(route.data.distance / 1000).toFixed(2)}km • ${Math.round(route.data.duration / 60)} mins
            </div>
            <ul class="mt-2 space-y-1">
                ${route.data.legs[0].steps.map(step => `
                    <li class="text-sm text-gray-600 pl-4 before:content-['•'] before:absolute before:ml-[-1em]">
                        ${step.maneuver.instruction}
                    </li>
                `).join('')}
            </ul>
        `;
        this.directionsList.appendChild(legElement);
    }

    clearRoutes() {
        // Remove existing route layers and sources
        this.locations.forEach((_, i) => {
            if (this.map.getLayer(`route${i}`)) {
                this.map.removeLayer(`route${i}`);
            }
            if (this.map.getSource(`route${i}`)) {
                this.map.removeSource(`route${i}`);
            }
        });

        // Remove transport change markers
        const markersToRemove = document.querySelectorAll('.transport-marker');
        markersToRemove.forEach(marker => marker.remove());
    }
}

// Initialize the route manager when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new RouteManager();
}); 