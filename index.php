<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mapbox Location Selector</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="tokens.js"></script> <!-- Include the tokens -->
  <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
  <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" />
  <style>
    .dragging {
      opacity: 0.5;
    }
    .drop-line {
      height: 4px;
      background-color: #4f46e5; /* Indigo-500 Tailwind color */
      margin-top: -2px;
      margin-bottom: -2px;
    }
    .marker-number {
      background-color: #4f46e5;
      color: white;
      border-radius: 50%;
      width: 30px;
      height: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
    }
  </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col md:flex-row md:space-x-6 p-4 md:p-6">

  <!-- Main Selector Section -->
  <div class="w-full md:max-w-lg bg-white p-4 md:p-6 rounded-lg shadow-md mb-6 md:mb-0">
    <h1 class="text-xl md:text-2xl font-bold text-gray-800 mb-4">Select Locations</h1>

    <!-- City Display -->
    <div class="mb-4">
      <p id="cityDisplay" class="text-base md:text-lg text-gray-700">City: Brisbane</p>
    </div>

    <!-- Combo Box -->
    <div class="relative mb-4">
      <input 
        id="searchBox"
        type="text" 
        placeholder="Search for a location..."
        class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
        autocomplete="off"
      />
      <ul id="resultsList" class="absolute w-full bg-white border border-gray-200 rounded-lg mt-1 hidden z-10">
        <!-- Search results will appear here -->
      </ul>
    </div>

    <!-- Selected Locations -->
    <h2 class="text-base md:text-lg font-semibold text-gray-700 mb-2">Selected Locations:</h2>
    <ul id="selectedLocations" class="space-y-2">
      <!-- Selected locations will appear here -->
    </ul>
  </div>

  <!-- Map Section -->
  <div id="map" class="w-full md:w-1/2 h-96 md:h-screen rounded-lg shadow-md"></div>

  <script>
    const searchBox = document.getElementById('searchBox');
    const resultsList = document.getElementById('resultsList');
    const selectedLocations = document.getElementById('selectedLocations');
    const city = 'Brisbane'; // Hardcoded city

    // Initialize Mapbox map
    mapboxgl.accessToken = MAPBOX_ACCESS_TOKEN;
    const map = new mapboxgl.Map({
      container: 'map',
      style: 'mapbox://styles/mapbox/streets-v12',
      center: [153.0281, -27.4679], // Default center on Brisbane
      zoom: 12
    });

    const markers = []; // Array to store map markers
    const coordinatesArray = []; // Array to store coordinates for bounds

    // Animate map smoothly to frame all points
    function adjustToBounds() {
      if (coordinatesArray.length === 0) {
        map.flyTo({
          center: [153.0281, -27.4679],
          zoom: 12,
          duration: 1000, // Reset to default view smoothly
          essential: true
        });
        return;
      }

      const bounds = coordinatesArray.reduce(
        (bounds, coord) => bounds.extend(coord),
        new mapboxgl.LngLatBounds(coordinatesArray[0], coordinatesArray[0])
      );

      const center = bounds.getCenter();
      const distance = bounds.getNorthEast().distanceTo(bounds.getSouthWest());
      const isMobile = window.matchMedia("(max-width: 768px)").matches;
      const zoom = isMobile ? Math.min(15, Math.max(12, Math.log2(40075017 / distance) - 8)) : Math.min(18, Math.max(14, Math.log2(40075017 / distance) - 8));

      map.flyTo({
        center,
        zoom,
        duration: 1000, // Smooth transition
        essential: true
      });
    }

    // Add a marker to the map and animate to its location
    function addMarker(coordinates, displayName) {
      const markerNumber = markers.length + 1;
      const el = document.createElement('div');
      el.className = 'marker-number';
      el.textContent = markerNumber;

      const marker = new mapboxgl.Marker(el).setLngLat(coordinates).setPopup(
        new mapboxgl.Popup({ offset: 25 }).setHTML(`<p>${displayName}</p>`)
      );
      marker.addTo(map);
      markers.push(marker);
      coordinatesArray.push(coordinates);

      adjustToBounds(); // Always animate bounds after adding a point
    }

    // Remove a marker from the map and re-adjust the view
    function removeMarker(index) {
      markers[index]?.remove();
      markers.splice(index, 1);
      coordinatesArray.splice(index, 1);
      updateMarkers(); // Update markers numbering after removal
      adjustToBounds(); // Adjust bounds after removal
    }

    // Update markers numbering
    function updateMarkers() {
      const items = selectedLocations.querySelectorAll('li');
      markers.forEach((marker, index) => {
        const el = marker.getElement();
        el.textContent = index + 1;
      });
    }

    // Fetch locations from Mapbox API
    async function fetchLocations(query) {
      const searchQuery = `${query}, Brisbane, Queensland, Australia`;
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

    searchBox.addEventListener('input', async (e) => {
      const query = e.target.value;

      if (query.length < 3) {
        resultsList.classList.add('hidden');
        return;
      }

      const locations = await fetchLocations(query);

      // Render results
      resultsList.innerHTML = locations
        .map(location => `
          <li 
            data-display-name="${location.displayName}" 
            data-address="${location.address}" 
            data-suburb="${location.suburb}" 
            data-postcode="${location.postcode}" 
            data-coordinates="${location.coordinates}" 
            class="p-2 hover:bg-gray-100 cursor-pointer">
            <p class="text-lg font-semibold">${location.displayName}</p>
            <p class="text-sm text-gray-500">${location.address}, ${location.suburb}, ${location.postcode}</p>
          </li>
        `)
        .join('');

      resultsList.classList.remove('hidden');
    });

    // Update list item numbers
    function updateListNumbers() {
      const items = selectedLocations.querySelectorAll('li');
      items.forEach((item, index) => {
        const numberSpan = item.querySelector('.order-number');
        if (numberSpan) {
          numberSpan.textContent = index + 1;
        }
      });
    }

    // Handle selection of a location
    resultsList.addEventListener('click', (e) => {
      if (e.target.closest('li')) {
        const listItem = e.target.closest('li');

        const displayName = listItem.dataset.displayName;
        const address = listItem.dataset.address;
        const suburb = listItem.dataset.suburb;
        const postcode = listItem.dataset.postcode;
        const coordinates = JSON.parse(`[${listItem.dataset.coordinates}]`);

        // Add to selected list
        const li = document.createElement('li');
        li.className = 'flex items-start p-3 bg-gray-200 rounded-lg space-x-4 cursor-move';
        li.draggable = true; // Make the item draggable
        li.innerHTML = `
          <span class="order-number flex items-center justify-center w-8 h-8 bg-indigo-500 text-white rounded-full">${selectedLocations.children.length + 1}</span>
          <div class="flex-grow">
            <h3 class="text-lg font-semibold">${displayName}</h3>
            <p class="text-sm text-gray-600">${address}</p>
            <p class="text-sm text-gray-600">${suburb}, ${postcode}</p>
          </div>
          <button class="text-red-500 hover:text-red-700 focus:outline-none remove-btn">Remove</button>
        `;

        selectedLocations.appendChild(li);

        // Plot the marker on the map
        addMarker(coordinates, displayName);

        // Clear search box and results
        searchBox.value = '';
        resultsList.classList.add('hidden');
      }
    });

    // Handle removal of a location
    selectedLocations.addEventListener('click', (e) => {
      if (e.target.classList.contains('remove-btn')) {
        const li = e.target.closest('li');
        const index = Array.from(selectedLocations.children).indexOf(li);

        // Remove marker from the map
        removeMarker(index);

        // Remove the list item
        selectedLocations.removeChild(li);
        updateListNumbers(); // Update numbers after removal
        updateMarkers(); // Update marker numbers after removal
      }
    });

    // Handle drag and drop for reordering
    let draggedItem = null;
    let dropLine = document.createElement('div');
    dropLine.className = 'drop-line';

    selectedLocations.addEventListener('dragstart', (e) => {
      if (e.target.tagName === 'LI') {
        draggedItem = e.target;
        draggedItem.classList.add('dragging');
        setTimeout(() => {
          e.target.style.display = 'none';
        }, 0);
      }
    });

    selectedLocations.addEventListener('dragend', (e) => {
        setTimeout(() => {
            draggedItem.style.display = 'flex';
            draggedItem.classList.remove('dragging');
            draggedItem = null;
            if (dropLine.parentNode) {
            dropLine.parentNode.removeChild(dropLine);
            }

            // Reorder markers array based on new list order
            const items = selectedLocations.querySelectorAll('li');
            const newMarkers = [];
            const newCoordinatesArray = [];

            items.forEach((item) => {
            const index = parseInt(item.querySelector('.order-number').textContent, 10) - 1;
            newMarkers.push(markers[index]);
            newCoordinatesArray.push(coordinatesArray[index]);
            });

            markers.forEach(marker => marker.remove()); // Remove existing markers
            markers.length = 0; // Clear original markers array
            coordinatesArray.length = 0; // Clear original coordinates array

            // Re-add markers in the new order
            newMarkers.forEach((marker, index) => {
            const el = marker.getElement();
            el.textContent = index + 1;
            marker.addTo(map);
            markers.push(marker);
            coordinatesArray.push(newCoordinatesArray[index]);
            });

            adjustToBounds(); // Adjust bounds to new marker positions

            updateListNumbers(); // Update the list order numbers
            updateMarkers(); // Update marker numbers after reordering
        }, 0);
    });


    selectedLocations.addEventListener('dragover', (e) => {
      e.preventDefault();
      const afterElement = getDragAfterElement(selectedLocations, e.clientY);
      if (afterElement == null) {
        selectedLocations.appendChild(dropLine);
      } else {
        selectedLocations.insertBefore(dropLine, afterElement);
      }
    });

    selectedLocations.addEventListener('drop', (e) => {
      e.preventDefault();
      if (draggedItem) {
        const afterElement = getDragAfterElement(selectedLocations, e.clientY);
        if (afterElement == null) {
          selectedLocations.appendChild(draggedItem);
        } else {
          selectedLocations.insertBefore(draggedItem, afterElement);
        }
      }
    });

    function getDragAfterElement(container, y) {
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

    // Close results list when clicking outside
    document.addEventListener('click', (e) => {
      if (!resultsList.contains(e.target) && e.target !== searchBox) {
        resultsList.classList.add('hidden');
      }
    });
  </script>
</body>
</html>
