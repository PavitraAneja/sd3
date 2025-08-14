
document.addEventListener("DOMContentLoaded", function() {
    if (typeof listings !== 'undefined' && listings.length) {
        var map = L.map('map').setView([34.0522, -118.2437], 10);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        listings.forEach(function(home) {
            if (home.lat && home.lng) {
                L.marker([home.lat, home.lng])
                    .addTo(map)
                    .bindPopup(`<a href='property.php?id=${home.id}'>${home.address}</a>`);
            }
        });
    }

    // Clear search functionality
    const clearButton = document.getElementById('clearSearch');
    if (clearButton) {
        clearButton.addEventListener('click', function() {
            // Get current URL without address parameter
            const url = new URL(window.location);
            url.searchParams.delete('address');
            
            // Redirect to show all listings
            window.location.href = url.toString();
        });
    }
});
