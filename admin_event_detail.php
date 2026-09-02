<?php
require_once 'postlog.php';
// admin_event_detail.php
session_start();
// Real app check: if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') { header('Location: login.php'); exit; }

$eventId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$eventId) {
    header('Location: admin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event - Madras Admin</title>
    <link rel="icon" type="image/png" href="logo.png">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
      .lucide { width: 24px; height: 24px; }
      .w-4 { width: 16px; } .h-4 { height: 16px; }
      .w-5 { width: 20px; } .h-5 { height: 20px; }
    </style>
</head>
<body class="min-h-screen flex flex-col relative overflow-x-hidden bg-theme-bg">
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-32 pb-12 w-full flex-grow relative z-10">
        <div class="mb-10">
            <h1 class="text-4xl font-bold text-white tracking-tight">Edit Event</h1>
            <p class="text-gray-400 mt-2">Update details for the selected event.</p>
        </div>

        <div class="glass-card p-8">
            <form id="edit-event-form" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label class="block text-sm font-bold text-gray-300 mb-2">Title</label>
                        <input type="text" id="title" class="appearance-none block w-full px-4 py-3 border border-white/10 bg-black/30 placeholder-gray-500 text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition-all sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-300 mb-2">Date</label>
                        <input type="date" id="date" class="appearance-none block w-full px-4 py-3 border border-white/10 bg-black/30 placeholder-gray-500 text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition-all sm:text-sm" style="color-scheme: dark;">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-300 mb-2">End Date</label>
                        <input type="date" id="endDate" class="appearance-none block w-full px-4 py-3 border border-white/10 bg-black/30 placeholder-gray-500 text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition-all sm:text-sm" style="color-scheme: dark;">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-300 mb-2">No. of Days</label>
                        <input type="number" id="noOfDays" readonly class="appearance-none block w-full px-4 py-3 border border-white/10 bg-black/30 placeholder-gray-500 text-white/50 rounded-xl focus:outline-none transition-all sm:text-sm" style="background-color: #f1f5f9 !important; border-color: #cbd5e1 !important; color: #64748b !important;">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-300 mb-2">Time</label>
                        <input type="text" id="time" class="appearance-none block w-full px-4 py-3 border border-white/10 bg-black/30 placeholder-gray-500 text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition-all sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-300 mb-2">Category</label>
                        <select id="category" class="appearance-none block w-full px-4 py-3 border border-white/10 bg-black/30 text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition-all sm:text-sm">
                            <option value="Networking Events">Networking Events</option>
                            <option value="Expo Exhibitions">Expo Exhibitions</option>
                            <option value="Job Fair">Job Fair</option>
                            <option value="Workshops">Workshops</option>
                            <option value="Meetups">Meetups</option>
                            <option value="Kids Exclusive">Kids Exclusive</option>
                            <option value="Elders Exclusive">Elders Exclusive</option>
                            <option value="Rides & Treks">Rides & Treks</option>
                            <option value="Comedy Shows">Comedy Shows</option>
                            <option value="Live Concerts">Live Concerts</option>
                            <option value="Travel">Travel</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-300 mb-2">Venue</label>
                        <input type="text" id="venue" class="appearance-none block w-full px-4 py-3 border border-white/10 bg-black/30 placeholder-gray-500 text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition-all sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-300 mb-2">Organizer</label>
                        <input type="text" id="organizer" class="appearance-none block w-full px-4 py-3 border border-white/10 bg-black/30 placeholder-gray-500 text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition-all sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-300 mb-2">Price</label>
                        <input type="text" id="price" class="appearance-none block w-full px-4 py-3 border border-white/10 bg-black/30 placeholder-gray-500 text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition-all sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-300 mb-2">Image URL</label>
                        <input type="url" id="image" class="appearance-none block w-full px-4 py-3 border border-white/10 bg-black/30 placeholder-gray-500 text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition-all sm:text-sm" placeholder="https://images.unsplash.com/...">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-300 mb-2">Event Bio</label>
                    <textarea id="bioText" rows="2" readonly class="appearance-none block w-full px-4 py-3 border border-white/10 bg-black/30 placeholder-gray-500 text-white/50 rounded-xl focus:outline-none transition-all sm:text-sm cursor-not-allowed" style="background-color: #f1f5f9 !important; border-color: #cbd5e1 !important; color: #64748b !important;" placeholder="Auto-generated organized by text..."></textarea>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-300 mb-2">Location/Registration URL</label>
                    <input type="url" id="registrationUrl" class="appearance-none block w-full px-4 py-3 border border-white/10 bg-black/30 placeholder-gray-500 text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition-all sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-300 mb-2">Venue Description</label>
                    <textarea id="venueText" rows="2" readonly class="appearance-none block w-full px-4 py-3 border border-white/10 bg-black/30 placeholder-gray-500 text-white/50 rounded-xl focus:outline-none transition-all sm:text-sm" style="background-color: #f1f5f9 !important; border-color: #cbd5e1 !important; color: #64748b !important;" placeholder="Auto-generated venue description..."></textarea>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-300 mb-2">Description</label>
                    <textarea id="description" rows="5" class="appearance-none block w-full px-4 py-3 border border-white/10 bg-black/30 placeholder-gray-500 text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition-all sm:text-sm"></textarea>
                </div>

                <div class="flex justify-end pt-6 border-t border-white/10">
                    <button type="submit" class="primary-button text-sm flex items-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const eventId = "<?php echo htmlspecialchars($eventId); ?>";

        function updateBioTextSync(organizerName) {
            const bioInput = document.getElementById('bioText');
            if (!bioInput) return;
            const prefix = "Organized by ";
            const trimmedName = (organizerName || '').trim();
            bioInput.value = trimmedName ? (prefix + trimmedName) : '';
        }

        document.getElementById('organizer').addEventListener('input', function(e) {
            updateBioTextSync(e.target.value);
        });

        let eventCity = '';
        let eventState = '';

        fetch('api.php/api/events/' + eventId + '?t=' + new Date().getTime())
            .then(res => res.json())
            .then(event => {
                eventCity = event.city || '';
                eventState = event.state || '';
                document.getElementById('title').value = event.title || '';
                document.getElementById('date').value = event.date || '';
                document.getElementById('endDate').value = event.endDate || event.date || '';
                document.getElementById('noOfDays').value = event.noOfDays || '1';
                document.getElementById('time').value = event.time || '';
                document.getElementById('category').value = event.category || 'Technology';
                document.getElementById('venue').value = event.venue || '';
                document.getElementById('organizer').value = event.organizer || '';
                document.getElementById('bioText').value = event.bioText || '';
                document.getElementById('venueText').value = event.eventvenueText || '';
                document.getElementById('registrationUrl').value = event.registrationUrl || '';
                document.getElementById('price').value = event.price || '';
                document.getElementById('image').value = event.image || '';
                document.getElementById('description').value = event.description || '';
            });

        function calculateTotalDays(startDateStr, endDateStr) {
            if (!startDateStr) return 1;
            const start = new Date(startDateStr);
            const end = endDateStr ? new Date(endDateStr) : start;
            if (isNaN(start.getTime()) || isNaN(end.getTime())) return 1;
            
            const utcStart = Date.UTC(start.getFullYear(), start.getMonth(), start.getDate());
            const utcEnd = Date.UTC(end.getFullYear(), end.getMonth(), end.getDate());
            
            if (utcEnd < utcStart) return 1;
            
            const diffTime = utcEnd - utcStart;
            const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24)) + 1;
            return diffDays;
        }

        function syncTotalDays() {
            const startVal = document.getElementById('date').value;
            let endInput = document.getElementById('endDate');
            let endVal = endInput.value;
            
            if (startVal && !endVal) {
                endInput.value = startVal;
                endVal = startVal;
            }
            
            if (startVal && endVal) {
                const start = new Date(startVal);
                const end = new Date(endVal);
                if (end < start) {
                    endInput.value = startVal;
                    endVal = startVal;
                }
            }
            
            const days = calculateTotalDays(startVal, endVal);
            document.getElementById('noOfDays').value = days;
        }

        function generateVenueText(venue, city, state) {
            const venueClean = (venue || '').trim();
            const cityClean = (city || '').trim();
            const stateClean = (state || '').trim();
            
            if (!venueClean) {
                return '';
            }
            
            let location = '';
            if (cityClean && stateClean) {
                location = `${cityClean}, ${stateClean}`;
            } else if (cityClean) {
                location = cityClean;
            } else if (stateClean) {
                location = stateClean;
            }
            
            let venueName = venueClean;
            if (!location && venueClean.includes(',')) {
                const parts = venueClean.split(',');
                venueName = parts[0].trim();
                location = parts.slice(1).join(',').trim();
            }
            
            if (!location) {
                location = 'the local area';
            }
            
            const outdoorKeywords = ['ground', 'stadium', 'park', 'garden', 'lawn', 'beach', 'open air', 'outdoor', 'street', 'field', 'turf', 'lake', 'river'];
            const isOutdoor = outdoorKeywords.some(keyword => venueClean.toLowerCase().includes(keyword));
            
            if (isOutdoor) {
                return `${venueName}, located in ${location}, offers a vibrant and spacious outdoor setting perfect for hosting memorable events and celebrations.`;
            } else {
                return `${venueName}, located in ${location}, offers a comfortable and elegant space for hosting memorable events and celebrations.`;
            }
        }

        function syncVenueText() {
            const venue = document.getElementById('venue').value;
            const generated = generateVenueText(venue, eventCity, eventState);
            document.getElementById('venueText').value = generated;
        }

        function matchLocationUrl(venue) {
            const v = (venue || '').trim();
            if (!v) return '';
            const vLower = v.toLowerCase();
            if (vLower.includes('trade centre') || vLower.includes('nandambakkam')) return 'https://www.chennaitradecentre.org/';
            if (vLower.includes('iit') || vLower.includes('taramani')) return 'https://www.iitm.ac.in/';
            if (vLower.includes('anna university')) return 'https://www.annauniv.edu/';
            if (vLower.includes('express avenue')) return 'https://www.expressavenue.in/';
            if (vLower.includes('phoenix')) return 'https://www.phoenixmarketcity.com/chennai';
            if (vLower.includes('music academy')) return 'https://musicacademymadras.in/';
            if (vLower.includes('kalakshetra')) return 'https://www.kalakshetra.in/';
            
            return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(v)}`;
        }

        let lastAutoUrl = '';
        function syncLocationUrl() {
            const venue = document.getElementById('venue').value;
            const currentUrl = document.getElementById('registrationUrl').value;
            if (!currentUrl || currentUrl === lastAutoUrl) {
                const newUrl = matchLocationUrl(venue);
                document.getElementById('registrationUrl').value = newUrl;
                lastAutoUrl = newUrl;
            }
        }

        document.getElementById('date').addEventListener('change', () => { syncTotalDays(); });
        document.getElementById('endDate').addEventListener('change', () => { syncTotalDays(); });
        document.getElementById('venue').addEventListener('input', () => { syncVenueText(); syncLocationUrl(); });

        document.getElementById('edit-event-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const updatedData = {
                title: document.getElementById('title').value,
                date: document.getElementById('date').value,
                endDate: document.getElementById('endDate').value,
                noOfDays: parseInt(document.getElementById('noOfDays').value, 10) || 1,
                time: document.getElementById('time').value,
                category: document.getElementById('category').value,
                venue: document.getElementById('venue').value,
                organizer: document.getElementById('organizer').value,
                bioText: document.getElementById('bioText').value,
                eventvenueText: document.getElementById('venueText').value,
                registrationUrl: document.getElementById('registrationUrl').value,
                price: document.getElementById('price').value,
                image: document.getElementById('image').value,
                description: document.getElementById('description').value,
                city: eventCity,
                state: eventState
            };

            fetch('api.php/api/events/' + eventId, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(updatedData)
            })
            .then(res => {
                if (!res.ok) throw new Error('PATCH update failed');
                return res.json();
            })
            .then(data => {
                alert('Event updated successfully!');
                window.location.href = 'admin.php?t=' + new Date().getTime();
            })
            .catch(err => {
                alert('Failed to update event.');
            });
        });
    </script>
    <script>
      lucide.createIcons();
    </script>
</body>
</html>
