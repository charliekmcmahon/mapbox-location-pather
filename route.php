<?php
$title = "Plan Your Route";
ob_start();
?>

<!-- Full Screen Map -->
<div class="absolute inset-0 z-0">
    <div id="map" class="w-full h-full"></div>
    <div class="legend absolute top-4 right-4 rounded-md bg-white/95 backdrop-blur-sm p-4 shadow-lg">
        <h3 class="text-sm font-medium leading-6 text-gray-900">Transport Types</h3>
        <div class="mt-2 space-y-2">
            <div class="flex items-center gap-x-2">
                <div class="h-1 w-5 rounded bg-indigo-600"></div>
                <span class="text-sm text-gray-600">Walking</span>
            </div>
            <div class="flex items-center gap-x-2">
                <div class="h-1 w-5 rounded bg-emerald-600"></div>
                <span class="text-sm text-gray-600">Public Transport</span>
            </div>
            <div class="flex items-center gap-x-2">
                <div class="h-1 w-5 rounded bg-orange-600"></div>
                <span class="text-sm text-gray-600">Driving</span>
            </div>
        </div>
    </div>
</div>

<!-- Overlay UI -->
<div class="absolute inset-y-0 left-0 z-10 w-full md:w-[420px] p-4">
    <div class="bg-white/95 backdrop-blur-sm rounded-lg shadow-lg h-full overflow-hidden flex flex-col">
        <!-- Header with Back Button -->
        <div class="px-6 py-4 border-b border-gray-200 flex items-center space-x-4">
            <button 
                data-action="back"
                class="inline-flex items-center gap-x-2 text-sm font-semibold text-gray-900"
            >
                <svg class="-ml-0.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 4.158a.75.75 0 11-1.04 1.04l-5.5-5.5a.75.75 0 010-1.08l5.5-5.5a.75.75 0 111.04 1.04L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                </svg>
                Back
            </button>
            <h1 class="text-xl font-semibold text-gray-900">Route Details</h1>
        </div>

        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto">
            <div class="px-6 py-4 space-y-6">
                <!-- Transport Preferences -->
                <div class="bg-white rounded-lg shadow-sm ring-1 ring-gray-900/5">
                    <div class="px-4 py-5">
                        <h3 class="text-base font-semibold leading-6 text-gray-900">Transport Preferences</h3>
                        <div class="mt-4 space-y-4">
                            <div class="relative flex items-start">
                                <div class="flex h-6 items-center">
                                    <input type="checkbox" id="allowWalking" checked class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                                </div>
                                <div class="ml-3">
                                    <label for="allowWalking" class="text-sm font-medium leading-6 text-gray-900">Walking</label>
                                </div>
                            </div>
                            <div class="relative flex items-start">
                                <div class="flex h-6 items-center">
                                    <input type="checkbox" id="allowTransit" checked class="h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-600">
                                </div>
                                <div class="ml-3">
                                    <label for="allowTransit" class="text-sm font-medium leading-6 text-gray-900">Public Transport</label>
                                </div>
                            </div>
                            <div class="relative flex items-start">
                                <div class="flex h-6 items-center">
                                    <input type="checkbox" id="allowDriving" class="h-4 w-4 rounded border-gray-300 text-orange-600 focus:ring-orange-600">
                                </div>
                                <div class="ml-3">
                                    <label for="allowDriving" class="text-sm font-medium leading-6 text-gray-900">Driving</label>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5">
                            <button 
                                type="button" 
                                id="recalculateRoute"
                                class="rounded-md bg-gray-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900"
                            >
                                Recalculate Route
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Route Statistics -->
                <dl class="grid grid-cols-2 gap-4">
                    <div class="rounded-lg bg-white p-4 shadow">
                        <dt class="text-sm font-medium leading-6 text-gray-600">Total Distance</dt>
                        <dd id="totalDistance" class="mt-1 text-2xl font-semibold tracking-tight text-gray-900">Calculating...</dd>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow">
                        <dt class="text-sm font-medium leading-6 text-gray-600">Estimated Time</dt>
                        <dd id="totalTime" class="mt-1 text-2xl font-semibold tracking-tight text-gray-900">Calculating...</dd>
                    </div>
                </dl>

                <!-- Directions -->
                <div class="bg-white rounded-lg shadow-sm ring-1 ring-gray-900/5">
                    <div class="px-4 py-5 sm:p-6">
                        <button 
                            type="button" 
                            id="toggleDirections"
                            class="flex w-full items-center justify-between text-left"
                        >
                            <span class="text-base font-semibold leading-6 text-gray-900">Detailed Directions</span>
                            <svg class="h-5 w-5 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div id="directionsList" class="directions-list mt-4 space-y-4">
                            <!-- Directions will be populated here -->
                        </div>
                    </div>
                </div>

                <!-- Venues List -->
                <div>
                    <h2 class="text-base font-semibold leading-7 text-gray-900">Venues</h2>
                    <ul id="venuesList" class="mt-3 space-y-3">
                        <!-- Venues will be listed here -->
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/route.js"></script>

<?php
$content = ob_get_clean();
include 'components/layout.php';
?>
