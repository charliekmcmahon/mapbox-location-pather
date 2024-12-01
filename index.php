<?php
$title = "Select Locations";
ob_start();
?>

<!-- Full Screen Map -->
<div class="absolute inset-0 z-0">
    <div id="map" class="w-full h-full"></div>
</div>

<!-- Overlay UI -->
<div class="absolute inset-y-0 left-0 z-10 w-full md:w-[420px] p-4">
    <div class="bg-white/95 backdrop-blur-sm rounded-lg shadow-lg h-full overflow-hidden flex flex-col">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200">
            <h1 class="text-xl font-semibold text-gray-900">Plan Your Bar Crawl</h1>
        </div>

        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto px-6 py-4 space-y-6">
            <!-- City Selection -->
            <div>
                <label for="citySelect" class="block text-sm font-medium leading-6 text-gray-900">Select a City</label>
                <select 
                    id="citySelect" 
                    class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6"
                >
                    <option value="">Select a city...</option>
                    <option value="Brisbane">Brisbane</option>
                    <option value="Sunshine Coast">Sunshine Coast</option>
                    <option value="Sydney">Sydney</option>
                    <option value="Melbourne">Melbourne</option>
                    <option value="Adelaide">Adelaide</option>
                    <option value="Canberra">Canberra</option>
                    <option value="Perth">Perth</option>
                </select>
            </div>

            <!-- Location Search -->
            <div class="relative">
                <label for="searchBox" class="block text-sm font-medium leading-6 text-gray-900">Search Locations</label>
                <div class="relative mt-2">
                    <input 
                        type="text" 
                        id="searchBox" 
                        placeholder="First, select a city..." 
                        disabled
                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500 disabled:ring-gray-200 sm:text-sm sm:leading-6"
                    />
                </div>
                <!-- Search Results -->
                <ul id="resultsList" class="absolute z-20 mt-1 hidden max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                    <!-- Results will be populated here -->
                </ul>
            </div>

            <!-- Selected Locations -->
            <div>
                <h2 class="text-base font-semibold leading-7 text-gray-900">Selected Locations</h2>
                <ul id="selectedLocations" class="mt-3 space-y-3">
                    <!-- Selected locations will appear here -->
                </ul>
            </div>
        </div>

        <!-- Footer with Next Button -->
        <div class="px-6 py-4 border-t border-gray-200 bg-white">
            <button 
                type="button" 
                id="nextButton"
                disabled
                class="w-full rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                Plan Route
            </button>
        </div>
    </div>
</div>

<script src="assets/js/location-selector.js"></script>

<?php
$content = ob_get_clean();
include 'components/layout.php';
?>
