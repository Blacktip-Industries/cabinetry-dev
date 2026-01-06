/**
 * Google Maps Utilities
 * Functions for map initialization, markers, directions, and route optimization
 */

let customerMap = null;
let directionsMap = null;
let routeMap = null;
let directionsService = null;
let directionsRenderer = null;
let markers = [];
let markerCluster = null;

/**
 * Initialize customer map with markers
 */
function initCustomerMap(customers) {
    const mapElement = document.getElementById('customer-map');
    if (!mapElement) return;
    
    // Default center (Australia)
    const defaultCenter = { lat: -25.2744, lng: 133.7751 };
    
    // Calculate bounds from customers
    const bounds = new google.maps.LatLngBounds();
    let hasValidLocations = false;
    
    customers.forEach(customer => {
        if (customer.latitude && customer.longitude) {
            const lat = parseFloat(customer.latitude);
            const lng = parseFloat(customer.longitude);
            if (!isNaN(lat) && !isNaN(lng)) {
                bounds.extend({ lat, lng });
                hasValidLocations = true;
            }
        }
    });
    
    // Initialize map
    customerMap = new google.maps.Map(mapElement, {
        center: hasValidLocations ? bounds.getCenter() : defaultCenter,
        zoom: hasValidLocations ? 8 : 4,
        mapTypeControl: true,
        streetViewControl: true,
        fullscreenControl: true
    });
    
    if (hasValidLocations) {
        customerMap.fitBounds(bounds);
    }
    
    // Clear existing markers
    markers = [];
    
    // Add markers for each customer
    customers.forEach(customer => {
        if (customer.latitude && customer.longitude) {
            const lat = parseFloat(customer.latitude);
            const lng = parseFloat(customer.longitude);
            if (!isNaN(lat) && !isNaN(lng)) {
                const marker = new google.maps.Marker({
                    position: { lat, lng },
                    map: customerMap,
                    title: customer.name,
                    animation: google.maps.Animation.DROP
                });
                
                // Info window
                const infoWindow = new google.maps.InfoWindow({
                    content: `
                        <div style="padding: 10px;">
                            <h3 style="margin: 0 0 10px 0;">${escapeHtml(customer.name)}</h3>
                            ${customer.company ? `<p style="margin: 0 0 5px 0;"><strong>Company:</strong> ${escapeHtml(customer.company)}</p>` : ''}
                            <p style="margin: 0 0 5px 0;"><strong>Address:</strong> ${escapeHtml(customer.address_line1 || '')} ${escapeHtml(customer.city || '')} ${escapeHtml(customer.state || '')}</p>
                            ${customer.phone ? `<p style="margin: 0 0 5px 0;"><strong>Phone:</strong> ${escapeHtml(customer.phone)}</p>` : ''}
                            ${customer.email ? `<p style="margin: 0;"><strong>Email:</strong> ${escapeHtml(customer.email)}</p>` : ''}
                        </div>
                    `
                });
                
                marker.addListener('click', () => {
                    infoWindow.open(customerMap, marker);
                });
                
                markers.push(marker);
            }
        }
    });
    
    // Cluster markers if available (optional - works without clustering if library not loaded)
    if (markers.length > 0) {
        try {
            if (typeof markerClusterer !== 'undefined' && markerClusterer.MarkerClusterer) {
                if (markerCluster) {
                    markerCluster.clearMarkers();
                }
                markerCluster = new markerClusterer.MarkerClusterer({ map: customerMap, markers });
            } else if (typeof MarkerClusterer !== 'undefined') {
                // Alternative library name
                if (markerCluster) {
                    markerCluster.clearMarkers();
                }
                markerCluster = new MarkerClusterer({ map: customerMap, markers });
            }
        } catch (e) {
            console.warn('Marker clustering not available:', e);
        }
    }
}

/**
 * Initialize directions map
 */
function initDirections() {
    const mapElement = document.getElementById('directions-map');
    if (!mapElement) return;
    
    directionsMap = new google.maps.Map(mapElement, {
        center: { lat: -25.2744, lng: 133.7751 },
        zoom: 6,
        mapTypeControl: true,
        streetViewControl: true,
        fullscreenControl: true
    });
    
    directionsService = new google.maps.DirectionsService();
    directionsRenderer = new google.maps.DirectionsRenderer({
        map: directionsMap,
        panel: document.getElementById('directions-list')
    });
    
    // Handle form submission
    document.getElementById('directions-form').addEventListener('submit', function(e) {
        e.preventDefault();
        calculateDirections();
    });
    
    document.getElementById('clear-directions').addEventListener('click', function() {
        directionsRenderer.setDirections({ routes: [] });
        document.getElementById('directions-panel').style.display = 'none';
        document.getElementById('directions-summary').innerHTML = '';
    });
}

/**
 * Calculate directions between origin and destination
 */
function calculateDirections() {
    const originType = document.getElementById('origin_type').value;
    const destinationType = document.getElementById('destination_type').value;
    const travelMode = document.getElementById('travel_mode').value;
    
    let origin = '';
    let destination = '';
    
    // Get origin
    if (originType === 'customer') {
        const originSelect = document.getElementById('origin_customer');
        const selectedOption = originSelect.options[originSelect.selectedIndex];
        if (selectedOption && selectedOption.dataset.lat && selectedOption.dataset.lng) {
            origin = {
                lat: parseFloat(selectedOption.dataset.lat),
                lng: parseFloat(selectedOption.dataset.lng)
            };
        } else {
            alert('Please select a valid origin customer with coordinates');
            return;
        }
    } else {
        origin = document.getElementById('origin_address').value;
        if (!origin) {
            alert('Please enter an origin address');
            return;
        }
    }
    
    // Get destination
    if (destinationType === 'customer') {
        const destSelect = document.getElementById('destination_customer');
        const selectedOption = destSelect.options[destSelect.selectedIndex];
        if (selectedOption && selectedOption.dataset.lat && selectedOption.dataset.lng) {
            destination = {
                lat: parseFloat(selectedOption.dataset.lat),
                lng: parseFloat(selectedOption.dataset.lng)
            };
        } else {
            alert('Please select a valid destination customer with coordinates');
            return;
        }
    } else {
        destination = document.getElementById('destination_address').value;
        if (!destination) {
            alert('Please enter a destination address');
            return;
        }
    }
    
    // Calculate directions
    directionsService.route({
        origin: origin,
        destination: destination,
        travelMode: google.maps.TravelMode[travelMode]
    }, (result, status) => {
        if (status === 'OK') {
            directionsRenderer.setDirections(result);
            document.getElementById('directions-panel').style.display = 'block';
            
            // Display summary
            const route = result.routes[0];
            const leg = route.legs[0];
            const summary = `
                <p><strong>Distance:</strong> ${leg.distance.text}</p>
                <p><strong>Duration:</strong> ${leg.duration.text}</p>
            `;
            document.getElementById('directions-summary').innerHTML = summary;
        } else {
            alert('Directions request failed: ' + status);
        }
    });
}

/**
 * Initialize route optimizer
 */
function initRouteOptimizer() {
    const mapElement = document.getElementById('route-map');
    if (!mapElement) return;
    
    routeMap = new google.maps.Map(mapElement, {
        center: { lat: -25.2744, lng: 133.7751 },
        zoom: 6,
        mapTypeControl: true,
        streetViewControl: true,
        fullscreenControl: true
    });
    
    document.getElementById('optimize-route').addEventListener('click', optimizeRoute);
    document.getElementById('clear-route').addEventListener('click', clearRoute);
}

/**
 * Optimize route for selected customers
 */
function optimizeRoute() {
    // Get selected customers
    const checkboxes = document.querySelectorAll('.customer-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Please select at least one customer to visit');
        return;
    }
    
    const waypoints = [];
    checkboxes.forEach(checkbox => {
        const lat = parseFloat(checkbox.dataset.lat);
        const lng = parseFloat(checkbox.dataset.lng);
        if (!isNaN(lat) && !isNaN(lng)) {
            waypoints.push({
                location: { lat, lng },
                stopover: true
            });
        }
    });
    
    if (waypoints.length === 0) {
        alert('Selected customers do not have valid coordinates');
        return;
    }
    
    // Get start location
    const startType = document.getElementById('start_location_type').value;
    let origin = '';
    
    if (startType === 'customer') {
        const startSelect = document.getElementById('start_customer');
        const selectedOption = startSelect.options[startSelect.selectedIndex];
        if (selectedOption && selectedOption.dataset.lat && selectedOption.dataset.lng) {
            origin = {
                lat: parseFloat(selectedOption.dataset.lat),
                lng: parseFloat(selectedOption.dataset.lng)
            };
        } else {
            alert('Please select a valid starting customer with coordinates');
            return;
        }
    } else {
        origin = document.getElementById('start_address').value;
        if (!origin) {
            alert('Please enter a starting address');
            return;
        }
    }
    
    // Get end location (optional)
    const endType = document.getElementById('end_location_type').value;
    let destination = null;
    
    if (endType !== 'none') {
        if (endType === 'customer') {
            const endSelect = document.getElementById('end_customer');
            const selectedOption = endSelect.options[endSelect.selectedIndex];
            if (selectedOption && selectedOption.dataset.lat && selectedOption.dataset.lng) {
                destination = {
                    lat: parseFloat(selectedOption.dataset.lat),
                    lng: parseFloat(selectedOption.dataset.lng)
                };
            }
        } else {
            destination = document.getElementById('end_address').value;
        }
    }
    
    // If no destination, use last waypoint as destination
    if (!destination && waypoints.length > 0) {
        destination = waypoints.pop().location;
    } else if (!destination) {
        alert('Please specify a destination or select customers');
        return;
    }
    
    // Use DirectionsService to get optimized route
    const directionsService = new google.maps.DirectionsService();
    const directionsRenderer = new google.maps.DirectionsRenderer({
        map: routeMap,
        optimizeWaypoints: true,
        suppressMarkers: false
    });
    
    directionsService.route({
        origin: origin,
        destination: destination,
        waypoints: waypoints,
        optimizeWaypoints: true,
        travelMode: google.maps.TravelMode.DRIVING
    }, (result, status) => {
        if (status === 'OK') {
            directionsRenderer.setDirections(result);
            document.getElementById('route-summary').style.display = 'block';
            
            // Display route summary
            const route = result.routes[0];
            let totalDistance = 0;
            let totalDuration = 0;
            let stopsList = '<ol>';
            
            route.legs.forEach((leg, index) => {
                totalDistance += leg.distance.value;
                totalDuration += leg.duration.value;
                stopsList += `<li>${leg.start_address} - ${leg.distance.text} - ${leg.duration.text}</li>`;
            });
            
            stopsList += '</ol>';
            
            const distanceText = (totalDistance / 1000).toFixed(1) + ' km';
            const durationText = Math.round(totalDuration / 60) + ' minutes';
            
            document.getElementById('route-stops-list').innerHTML = stopsList;
            document.getElementById('route-info').innerHTML = `
                <p><strong>Total Distance:</strong> ${distanceText}</p>
                <p><strong>Total Duration:</strong> ${durationText}</p>
                <p><strong>Number of Stops:</strong> ${route.legs.length}</p>
            `;
        } else {
            alert('Route optimization failed: ' + status);
        }
    });
}

/**
 * Clear route
 */
function clearRoute() {
    if (directionsRenderer) {
        directionsRenderer.setDirections({ routes: [] });
    }
    document.getElementById('route-summary').style.display = 'none';
    document.querySelectorAll('.customer-checkbox').forEach(cb => cb.checked = false);
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

