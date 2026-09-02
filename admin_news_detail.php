<?php
require_once 'postlog.php';
// admin_news_detail.php
session_start();
// Check if user is admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: admin.php');
    exit;
}

$newsId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$newsId) {
    header('Location: admin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit News Article - Kovai Admin</title>
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
            <h1 class="text-4xl font-bold text-white tracking-tight">Edit News Article</h1>
            <p class="text-gray-400 mt-2">Update details for the selected news article.</p>
        </div>

        <div class="glass-card p-8">
            <form id="edit-news-form" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Post Title -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-gray-300 mb-2">Post Title <span class="text-purple-400 font-bold">*</span></label>
                        <input type="text" id="title" required class="appearance-none block w-full px-4 py-3 border border-white/10 bg-black/30 placeholder-gray-500 text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition-all sm:text-sm" placeholder="Enter headline or article title">
                    </div>

                    <!-- Description -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-gray-300 mb-2">Description <span class="text-purple-400 font-bold">*</span></label>
                        <textarea id="description" rows="5" required class="appearance-none block w-full px-4 py-3 border border-white/10 bg-black/30 placeholder-gray-500 text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition-all sm:text-sm" placeholder="Provide details about this news article..."></textarea>
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-sm font-bold text-gray-300 mb-2">Category <span class="text-purple-400 font-bold">*</span></label>
                        <select id="category" required class="appearance-none block w-full px-4 py-3 border border-white/10 bg-black/30 text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition-all sm:text-sm">
                            <option value="General">General</option>
                            <option value="Metro">Metro</option>
                            <option value="Airport">Airport</option>
                            <option value="IT & Tech">IT & Tech</option>
                            <option value="Civic Projects">Civic Projects</option>
                            <option value="Local NGOs">Local NGOs</option>
                            <option value="Others">Others</option>
                        </select>
                    </div>

                    <!-- State -->
                    <div>
                        <label class="block text-sm font-bold text-gray-300 mb-2">State <span class="text-purple-400 font-bold">*</span></label>
                        <input type="text" id="state" required class="appearance-none block w-full px-4 py-3 border border-white/10 bg-black/30 placeholder-gray-500 text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition-all sm:text-sm">
                    </div>

                    <!-- Post Type -->
                    <div>
                        <label class="block text-sm font-bold text-gray-300 mb-2">Post Type <span class="text-purple-400 font-bold">*</span></label>
                        <select id="postType" required class="appearance-none block w-full px-4 py-3 border border-white/10 bg-black/30 text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition-all sm:text-sm">
                            <option value="News">News</option>
                            <option value="Announcement">Announcement</option>
                            <option value="Article">Article</option>
                            <option value="Blog">Blog</option>
                        </select>
                    </div>

                    <!-- Validity (days) -->
                    <div>
                        <label class="block text-sm font-bold text-gray-300 mb-2">Validity (days) <span class="text-purple-400 font-bold">*</span></label>
                        <input type="number" id="validity" min="1" required class="appearance-none block w-full px-4 py-3 border border-white/10 bg-black/30 placeholder-gray-500 text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition-all sm:text-sm">
                    </div>

                    <!-- Link 1 (Optional) -->
                    <div>
                        <label class="block text-sm font-bold text-gray-300 mb-2">Link 1 (Optional)</label>
                        <input type="url" id="l1" class="appearance-none block w-full px-4 py-3 border border-white/10 bg-black/30 placeholder-gray-500 text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition-all sm:text-sm" placeholder="https://...">
                    </div>

                    <!-- Link 2 (Optional) -->
                    <div>
                        <label class="block text-sm font-bold text-gray-300 mb-2">Link 2 (Optional)</label>
                        <input type="url" id="l2" class="appearance-none block w-full px-4 py-3 border border-white/10 bg-black/30 placeholder-gray-500 text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition-all sm:text-sm" placeholder="https://...">
                    </div>

                    <!-- Video URL (Optional) -->
                    <div>
                        <label class="block text-sm font-bold text-gray-300 mb-2">Video URL (Optional)</label>
                        <input type="url" id="videoURL" class="appearance-none block w-full px-4 py-3 border border-white/10 bg-black/30 placeholder-gray-500 text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition-all sm:text-sm" placeholder="https://youtube.com/...">
                    </div>

                    <!-- Image URL (Optional) -->
                    <div>
                        <label class="block text-sm font-bold text-gray-300 mb-2">Image URL (Optional)</label>
                        <input type="url" id="image" class="appearance-none block w-full px-4 py-3 border border-white/10 bg-black/30 placeholder-gray-500 text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition-all sm:text-sm" placeholder="https://images.unsplash.com/...">
                    </div>

                    <!-- Hidden fields for compatibility -->
                    <div style="display: none;">
                        <input type="date" id="date">
                        <input type="checkbox" id="trending">
                        <input type="text" id="bioText">
                        <input type="number" id="eventFor">
                        <select id="hideEvent">
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
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
        const newsId = "<?php echo htmlspecialchars($newsId); ?>";

        fetch('api.php/api/news/' + newsId + '?t=' + new Date().getTime())
            .then(res => res.json())
            .then(news => {
                document.getElementById('title').value = news.title || '';
                document.getElementById('description').value = news.description || news.summary || news.postInfo || '';
                document.getElementById('category').value = news.category || 'General';
                document.getElementById('state').value = news.state || 'Tamil Nadu';
                document.getElementById('postType').value = news.postType || 'News';
                document.getElementById('validity').value = news.validity !== undefined ? news.validity : 30;
                document.getElementById('l1').value = news.l1 || news.url || '';
                document.getElementById('l2').value = news.l2 || '';
                document.getElementById('videoURL').value = news.videoURL || '';
                document.getElementById('image').value = news.image || '';
                
                // Hidden values
                document.getElementById('date').value = news.date || '';
                document.getElementById('trending').checked = !!news.trending;
                document.getElementById('bioText').value = news.bioText || 'Organized by IndieMa Admin';
                document.getElementById('eventFor').value = news.eventFor !== undefined ? news.eventFor : 1;
                document.getElementById('hideEvent').value = news.hideEvent !== undefined ? news.hideEvent : 1;
            })
            .catch(err => {
                console.error('Error fetching news details:', err);
                alert('Failed to load news article details.');
            });

        document.getElementById('edit-news-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const updatedData = {
                title: document.getElementById('title').value.trim(),
                description: document.getElementById('description').value.trim(),
                category: document.getElementById('category').value,
                state: document.getElementById('state').value.trim(),
                postType: document.getElementById('postType').value,
                validity: parseInt(document.getElementById('validity').value, 10) || 30,
                l1: document.getElementById('l1').value.trim(),
                l2: document.getElementById('l2').value.trim(),
                videoURL: document.getElementById('videoURL').value.trim(),
                image: document.getElementById('image').value.trim(),
                
                // Hidden values
                date: document.getElementById('date').value,
                trending: document.getElementById('trending').checked,
                bioText: document.getElementById('bioText').value,
                eventFor: parseInt(document.getElementById('eventFor').value, 10) || 1,
                hideEvent: parseInt(document.getElementById('hideEvent').value, 10)
            };

            fetch('api.php/api/news/' + newsId, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(updatedData)
            })
            .then(res => {
                if (!res.ok) throw new Error('PATCH update failed');
                return res.json();
            })
            .then(data => {
                alert('News article updated successfully!');
                window.location.href = 'admin.php?t=' + new Date().getTime();
            })
            .catch(err => {
                console.error('Error updating news:', err);
                alert('Failed to update news article.');
            });
        });
    </script>
    <script>
      lucide.createIcons();
    </script>
</body>
</html>
