/**
 * Customer Management JavaScript
 * Handles address autocomplete, geocoding, and form validation
 */

let autocomplete = null;
let mapPreview = null;
let geocoder = null;

/**
 * Initialize address autocomplete
 */
function initAddressAutocomplete() {
    if (typeof google === 'undefined' || !google.maps || !google.maps.places) {
        console.warn('Google Maps Places API not loaded');
        return;
    }
    
    const addressInput = document.getElementById('address_line1');
    if (!addressInput) return;
    
    autocomplete = new google.maps.places.Autocomplete(addressInput, {
        componentRestrictions: { country: 'au' }, // Restrict to Australia
        fields: ['address_components', 'geometry', 'formatted_address']
    });
    
    geocoder = new google.maps.Geocoder();
    
    autocomplete.addListener('place_changed', function() {
        const place = autocomplete.getPlace();
        
        if (!place.geometry) {
            console.warn('No geometry found for place');
            return;
        }
        
        // Extract address components
        const addressComponents = {};
        place.address_components.forEach(component => {
            const type = component.types[0];
            addressComponents[type] = component.long_name;
        });
        
        // Fill in form fields
        document.getElementById('address_line1').value = addressComponents.street_number 
            ? (addressComponents.street_number + ' ' + (addressComponents.route || ''))
            : (addressComponents.route || '');
        
        document.getElementById('city').value = addressComponents.locality || addressComponents.postal_town || '';
        document.getElementById('state').value = addressComponents.administrative_area_level_1 || '';
        document.getElementById('postal_code').value = addressComponents.postal_code || '';
        document.getElementById('country').value = addressComponents.country || 'Australia';
        
        // Set coordinates
        const location = place.geometry.location;
        document.getElementById('latitude').value = location.lat();
        document.getElementById('longitude').value = location.lng();
        
        // Update map preview if it exists
        if (mapPreview) {
            mapPreview.setCenter(location);
            const marker = new google.maps.Marker({
                position: location,
                map: mapPreview,
                title: 'Customer Location'
            });
        } else {
            initMapPreview(location.lat(), location.lng());
        }
    });
}

/**
 * Initialize map preview
 */
function initMapPreview(lat, lng) {
    const mapElement = document.getElementById('map-preview');
    if (!mapElement) return;
    
    mapPreview = new google.maps.Map(mapElement, {
        center: { lat: parseFloat(lat), lng: parseFloat(lng) },
        zoom: 15,
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: false
    });
    
    new google.maps.Marker({
        position: { lat: parseFloat(lat), lng: parseFloat(lng) },
        map: mapPreview,
        title: 'Customer Location'
    });
}

/**
 * Geocode address manually (if autocomplete doesn't work)
 */
function geocodeAddress() {
    const addressInput = document.getElementById('address_line1');
    const cityInput = document.getElementById('city');
    const stateInput = document.getElementById('state');
    const postalCodeInput = document.getElementById('postal_code');
    const countryInput = document.getElementById('country');
    
    if (!addressInput || !geocoder) return;
    
    const address = [
        addressInput.value,
        cityInput.value,
        stateInput.value,
        postalCodeInput.value,
        countryInput.value || 'Australia'
    ].filter(Boolean).join(', ');
    
    if (!address) return;
    
    geocoder.geocode({ address: address }, (results, status) => {
        if (status === 'OK' && results[0]) {
            const location = results[0].geometry.location;
            document.getElementById('latitude').value = location.lat();
            document.getElementById('longitude').value = location.lng();
            
            if (mapPreview) {
                mapPreview.setCenter(location);
            } else {
                initMapPreview(location.lat(), location.lng());
            }
        }
    });
}

// Geocode address when form fields change (debounced)
let geocodeTimeout = null;
['address_line1', 'city', 'state', 'postal_code'].forEach(fieldId => {
    const field = document.getElementById(fieldId);
    if (field) {
        field.addEventListener('input', function() {
            clearTimeout(geocodeTimeout);
            geocodeTimeout = setTimeout(geocodeAddress, 1000); // Wait 1 second after user stops typing
        });
    }
});

