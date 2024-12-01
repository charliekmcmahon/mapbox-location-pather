class LocationSelector {
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

        this.searchBox = document.getElementById('searchBox');
        this.resultsList = document.getElementById('resultsList');
        this.selectedLocations = document.getElementById('selectedLocations');
        this.nextButton = document.getElementById('nextButton');
        this.citySelect = document.getElementById('citySelect');
        
        this.markers = [];
        this.coordinatesArray = [];
        this.city = '';
        this.draggedItem = null;
        this.dropLine = document.createElement('div');
        this.dropLine.className = 'drop-line';
        
        // Check if we're returning to edit before initializing map
        this.isEditing = localStorage.getItem('editingLocations') === 'true';
        this.savedLocations = this.isEditing ? JSON.parse(localStorage.getItem('crawlLocations') || '[]') : [];
        
        this.initializeMap();
        this.initializeEventListeners();

        if (this.isEditing) {
            // Clear the editing flag
            localStorage.removeItem('editingLocations');
            
            // Restore the selected city
            const savedCity = localStorage.getItem('selectedCity');
            if (savedCity) {
                this.citySelect.value = savedCity;
                this.city = savedCity;
                this.searchBox.disabled = false;
                this.searchBox.placeholder = "Search for a location...";
            }
            
            // Add each location to the list
            this.savedLocations.forEach(location => {
                this.addLocation(
                    location.name,
                    location.address,
                    '', // suburb (not stored in route data)
                    '', // postcode (not stored in route data)
                    location.coordinates
                );
            });
        }
    }

    initializeMap() {
        mapboxgl.accessToken = MAPBOX_ACCESS_TOKEN;

        // If we have saved locations, calculate initial bounds
        if (this.isEditing && this.savedLocations.length > 0) {
            const bounds = this.savedLocations.reduce(
                (bounds, location) => bounds.extend(location.coordinates),
                new mapboxgl.LngLatBounds(this.savedLocations[0].coordinates, this.savedLocations[0].coordinates)
            );

            this.map = new mapboxgl.Map({
                container: 'map',
                style: 'mapbox://styles/mapbox/streets-v12',
                center: bounds.getCenter(),
                zoom: 12
            });

            // Fit bounds with padding after map loads
            this.map.on('load', () => {
                this.map.fitBounds(bounds, { 
                    padding: {
                        top: 250,
                        bottom: 250,
                        left: 300,
                        right: 300
                    }
                });
            });
        } else {
            // Default to Australia view if no locations
            this.map = new mapboxgl.Map({
                container: 'map',
                style: 'mapbox://styles/mapbox/streets-v12',
                center: [133.7751, -25.2744], // Center of Australia
                zoom: 4 // Zoomed out to show all of Australia
            });
        }
    }

    initializeEventListeners() {
        this.searchBox.addEventListener('input', this.handleSearch.bind(this));
        this.resultsList.addEventListener('click', this.handleLocationSelect.bind(this));
        this.selectedLocations.addEventListener('click', this.handleLocationRemove.bind(this));
        this.initializeDragAndDrop();
        this.nextButton.addEventListener('click', this.handleNextButton.bind(this));
        this.citySelect.addEventListener('change', this.handleCitySelect.bind(this));
        
        // Close results list when clicking outside
        document.addEventListener('click', (e) => {
            if (!this.resultsList.contains(e.target) && e.target !== this.searchBox) {
                this.resultsList.classList.add('hidden');
            }
        });
    }

    handleCitySelect(e) {
        const selectedCity = e.target.value;
        if (selectedCity) {
            this.city = selectedCity;
            const coordinates = this.cityCoordinates[selectedCity];
            
            // Enable search box and update placeholder
            this.searchBox.disabled = false;
            this.searchBox.placeholder = "Search for a location...";
            
            // Fly to selected city
            this.map.flyTo({
                center: coordinates,
                zoom: 12,
                duration: 5000,
                essential: true
            });
        } else {
            // If no city selected, disable search and reset map
            this.city = '';
            this.searchBox.disabled = true;
            this.searchBox.placeholder = "First, select a city...";
            
            this.map.flyTo({
                center: [133.7751, -25.2744],
                zoom: 4,
                duration: 2000,
                essential: true
            });
        }
    }

    async fetchLocations(query) {
        const searchQuery = `${query}, ${this.city}, Australia`;
        const url = `${MAPBOX_API_URL}/${encodeURIComponent(searchQuery)}.json?access_token=${MAPBOX_ACCESS_TOKEN}&limit=5&types=poi`;
        const response = await fetch(url);
        const data = await response.json();

        return data.features.map(feature => {
            const address = feature.properties.address || "No street address available";
            const suburb = feature.context?.find(c => c.id.includes('locality'))?.text || "No suburb available";
            const postcode = feature.context?.find(c => c.id.includes('postcode'))?.text || "No postcode available";

            return {
                name: feature.place_name,
                displayName: feature.text,
                address,
                suburb,
                postcode,
                coordinates: feature.geometry.coordinates
            };
        });
    }

    async handleSearch(e) {
        const query = e.target.value;

        if (query.length < 3) {
            this.resultsList.classList.add('hidden');
            return;
        }

        const locations = await this.fetchLocations(query);

        // Render results with Catalyst UI styling
        this.resultsList.innerHTML = locations
            .map(location => `
                <li 
                    data-display-name="${location.displayName}" 
                    data-address="${location.address}" 
                    data-suburb="${location.suburb}" 
                    data-postcode="${location.postcode}" 
                    data-coordinates="${location.coordinates}"
                >
                    <div class="flex items-center px-3 py-2">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-900">${location.displayName}</p>
                            <p class="text-sm text-gray-500">${location.address}, ${location.suburb}, ${location.postcode}</p>
                        </div>
                    </div>
                </li>
            `)
            .join('');

        this.resultsList.classList.remove('hidden');
    }

    handleLocationSelect(e) {
        if (e.target.closest('li')) {
            const listItem = e.target.closest('li');
            const displayName = listItem.dataset.displayName;
            const address = listItem.dataset.address;
            const suburb = listItem.dataset.suburb;
            const postcode = listItem.dataset.postcode;
            const coordinates = JSON.parse(`[${listItem.dataset.coordinates}]`);

            this.addLocation(displayName, address, suburb, postcode, coordinates);

            // Clear search
            this.searchBox.value = '';
            this.resultsList.classList.add('hidden');
        }
    }

    handleLocationRemove(e) {
        if (e.target.classList.contains('remove-btn')) {
            const li = e.target.closest('li');
            const index = Array.from(this.selectedLocations.children).indexOf(li);
            this.removeMarker(index);
            this.selectedLocations.removeChild(li);
            this.updateListNumbers();
            this.updateMarkers();
            this.updateNextButton();
        }
    }

    addMarker(coordinates, displayName) {
        const markerNumber = this.markers.length + 1;
        const el = document.createElement('div');
        el.className = 'marker-number';
        el.innerHTML = `<span>${markerNumber}</span>`;
        
        // Create popup with better styling
        const popup = new mapboxgl.Popup({ 
            offset: [0, -24],  // Offset half the height of the marker
            className: 'rounded-lg shadow-lg'
        })
            .setHTML(`
                <div class="px-3 py-2">
                    <p class="font-medium text-gray-900">${displayName}</p>
                </div>
            `);

        const marker = new mapboxgl.Marker({
            element: el,
            anchor: 'center',
        })
            .setLngLat(coordinates)
            .setPopup(popup)
            .addTo(this.map);

        this.markers.push(marker);
        this.coordinatesArray.push(coordinates);
        this.adjustToBounds();
    }

    removeMarker(index) {
        this.markers[index]?.remove();
        this.markers.splice(index, 1);
        this.coordinatesArray.splice(index, 1);
        this.adjustToBounds();
    }

    adjustToBounds() {
        if (this.coordinatesArray.length === 0) {
            this.map.flyTo({
                center: [153.0281, -27.4679],
                zoom: 12,
                duration: 2000,
                essential: true
            });
            return;
        }

        if (this.coordinatesArray.length === 1) {
            // For single location, just center on it with a moderate zoom
            this.map.flyTo({
                center: this.coordinatesArray[0],
                zoom: 14,
                duration: 2000,
                essential: true
            });
            return;
        }

        // For multiple locations, calculate the bounds
        const bounds = this.coordinatesArray.reduce(
            (bounds, coord) => bounds.extend(coord),
            new mapboxgl.LngLatBounds(this.coordinatesArray[0], this.coordinatesArray[0])
        );

        // Calculate center and zoom from bounds
        const center = bounds.getCenter();
        const boundsArray = bounds.toArray();
        const maxZoom = 16;  // Maximum zoom level
        
        this.map.flyTo({
            center: center,
            zoom: Math.min(maxZoom, this.getZoomLevel(boundsArray, 100)),
            duration: 1500,
            essential: true
        });
    }

    // Helper method to calculate appropriate zoom level
    getZoomLevel(bounds, padding) {
        const [[west, south], [east, north]] = bounds;
        const mapDim = { height: this.map.getContainer().offsetHeight, width: this.map.getContainer().offsetWidth };
        
        // Convert padding to fraction
        const paddingFraction = padding / Math.min(mapDim.height, mapDim.width);
        
        // Calculate zoom level
        const latFrac = (north - south) * (1 + paddingFraction * 2);
        const lngFrac = (east - west) * (1 + paddingFraction * 2);
        const latZoom = Math.log2(360 / latFrac);
        const lngZoom = Math.log2(360 / lngFrac);
        
        return Math.min(latZoom, lngZoom);
    }

    updateListNumbers() {
        const items = this.selectedLocations.querySelectorAll('li');
        items.forEach((item, index) => {
            const numberSpan = item.querySelector('.order-number');
            if (numberSpan) {
                numberSpan.textContent = index + 1;
            }
        });
    }

    updateMarkers() {
        this.markers.forEach((marker, index) => {
            const el = marker.getElement();
            el.querySelector('span').textContent = index + 1;
        });
    }

    updateNextButton() {
        this.nextButton.disabled = this.selectedLocations.children.length < 2;
    }

    initializeDragAndDrop() {
        this.selectedLocations.addEventListener('dragstart', this.handleDragStart.bind(this));
        this.selectedLocations.addEventListener('dragend', this.handleDragEnd.bind(this));
        this.selectedLocations.addEventListener('dragover', this.handleDragOver.bind(this));
        this.selectedLocations.addEventListener('drop', this.handleDrop.bind(this));
    }

    handleDragStart(e) {
        if (e.target.tagName === 'LI') {
            this.draggedItem = e.target;
            this.draggedItem.classList.add('dragging');
            
            // Create a drag image that matches the original item
            const dragImage = this.draggedItem.cloneNode(true);
            dragImage.style.position = 'absolute';
            dragImage.style.top = '-1000px';
            document.body.appendChild(dragImage);
            e.dataTransfer.setDragImage(dragImage, 20, 20);
            
            // Remove the drag image after it's no longer needed
            setTimeout(() => {
                document.body.removeChild(dragImage);
                this.draggedItem.style.opacity = '0.5'; // Make the original item semi-transparent
            }, 0);
        }
    }

    handleDragEnd(e) {
        if (this.draggedItem) {
            this.draggedItem.style.opacity = '1'; // Restore opacity
            this.draggedItem.classList.remove('dragging');
            this.draggedItem = null;
        }
        if (this.dropLine.parentNode) {
            this.dropLine.parentNode.removeChild(this.dropLine);
        }
        this.reorderMarkers();
    }

    handleDragOver(e) {
        e.preventDefault();
        
        // Remove existing drop line
        if (this.dropLine.parentNode) {
            this.dropLine.parentNode.removeChild(this.dropLine);
        }

        const afterElement = this.getDragAfterElement(this.selectedLocations, e.clientY);
        
        // Create and insert the drop line
        this.dropLine.className = 'drop-line';
        
        if (afterElement == null) {
            this.selectedLocations.appendChild(this.dropLine);
        } else {
            this.selectedLocations.insertBefore(this.dropLine, afterElement);
        }
    }

    handleDrop(e) {
        e.preventDefault();
        
        // Remove the drop line
        if (this.dropLine.parentNode) {
            this.dropLine.parentNode.removeChild(this.dropLine);
        }

        if (this.draggedItem) {
            const afterElement = this.getDragAfterElement(this.selectedLocations, e.clientY);
            if (afterElement == null) {
                this.selectedLocations.appendChild(this.draggedItem);
            } else {
                this.selectedLocations.insertBefore(this.draggedItem, afterElement);
            }
            
            // Reset the dragged item
            this.draggedItem.style.opacity = '1';
            this.draggedItem.classList.remove('dragging');
            this.draggedItem = null;
            
            // Update the order
            this.reorderMarkers();
        }
    }

    getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('li:not(.dragging)')];

        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    reorderMarkers() {
        const items = this.selectedLocations.querySelectorAll('li');
        const newMarkers = [];
        const newCoordinatesArray = [];

        items.forEach((item) => {
            const index = parseInt(item.querySelector('.order-number').textContent, 10) - 1;
            newMarkers.push(this.markers[index]);
            newCoordinatesArray.push(this.coordinatesArray[index]);
        });

        this.markers.forEach(marker => marker.remove());
        this.markers = newMarkers;
        this.coordinatesArray = newCoordinatesArray;

        this.markers.forEach((marker, index) => {
            const el = marker.getElement();
            el.querySelector('span').textContent = index + 1;
            marker.addTo(this.map);
        });

        this.adjustToBounds();
        this.updateListNumbers();
        this.updateMarkers();
    }

    handleNextButton() {
        const locations = Array.from(this.selectedLocations.children).map((li, index) => {
            const coordinates = this.coordinatesArray[index];
            const name = li.querySelector('h3').textContent;
            const address = li.querySelector('p').textContent;
            return { 
                coordinates, 
                name, 
                address,
                city: this.city // Add city to the stored data
            };
        });

        localStorage.setItem('crawlLocations', JSON.stringify(locations));
        window.location.href = 'route.php';
    }

    // Add a method to create a location item
    addLocation(displayName, address, suburb = '', postcode = '', coordinates) {
        const li = document.createElement('li');
        li.draggable = true;
        li.className = 'flex items-start p-4 bg-white/95 backdrop-blur-sm rounded-lg shadow-sm space-x-4 cursor-move border border-gray-200';
        li.innerHTML = `
            <span class="order-number">${this.selectedLocations.children.length + 1}</span>
            <div class="min-w-0 flex-1">
                <h3 class="text-sm font-medium leading-6 text-gray-900">${displayName}</h3>
                <p class="mt-1 text-sm text-gray-500">${address}</p>
                ${suburb || postcode ? `<p class="text-sm text-gray-500">${suburb}${postcode ? `, ${postcode}` : ''}</p>` : ''}
            </div>
            <button type="button" class="remove-btn">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                </svg>
            </button>
        `;

        // Ensure the remove button is properly bound
        const removeBtn = li.querySelector('.remove-btn');
        removeBtn.addEventListener('click', () => {
            const index = Array.from(this.selectedLocations.children).indexOf(li);
            this.removeMarker(index);
            this.selectedLocations.removeChild(li);
            this.updateListNumbers();
            this.updateMarkers();
            this.updateNextButton();
        });

        this.selectedLocations.appendChild(li);
        this.addMarker(coordinates, displayName);
        this.updateNextButton();
    }
}

// Initialize the location selector when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new LocationSelector();
}); 