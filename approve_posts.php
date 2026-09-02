<?php
require_once 'postlog.php';
// approve_posts.php
session_start();

$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
if (!$isAdmin) {
    header('Location: admin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Posts | Kovai</title>

    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">

    <link rel="icon" type="image/png" href="logo.png">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
      .lucide { width: 24px; height: 24px; }
      .w-4 { width: 16px; } .h-4 { height: 16px; }
      .w-5 { width: 20px; } .h-5 { height: 20px; }
      .w-6 { width: 24px; } .h-6 { height: 24px; }
      @keyframes scaleIn {
          from { opacity: 0; transform: scale(0.95); }
          to { opacity: 1; transform: scale(1); }
      }
    </style>
</head>
<body class="min-h-screen flex flex-col relative overflow-x-hidden bg-theme-bg">
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Approvals Lock Overlay -->
    <div id="approvals-lock-overlay" class="fixed inset-0 flex items-center justify-center p-4" style="background-color: rgba(10, 8, 20, 0.98); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); z-index: 999999 !important;">
        <div class="border px-8 py-10 rounded-3xl shadow-2xl relative w-full max-w-md flex flex-col items-center text-center gap-6 bg-white border-gray-200" style="animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); background-color: #ffffff !important; border: 1px solid #cbd5e1 !important;">
            <div class="w-16 h-16 rounded-full bg-red-500/10 text-red-600 flex items-center justify-center border border-red-500/20 shadow-lg relative">
                <i data-lucide="lock" class="w-6 h-6" style="width: 24px; height: 24px;"></i>
            </div>
            <div>
                <h3 class="text-2xl font-bold tracking-tight mb-2" style="color: #0f172a !important;">Approve Posts Locked</h3>
                <p class="text-gray-500 text-xs leading-relaxed px-4" style="color: #475569 !important;">Please enter the administrator passkey to access approvals.</p>
            </div>
            <div class="w-full flex flex-col gap-2">
                <input type="password" id="lock-passkey-input" class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500 text-center font-bold tracking-widest text-lg border border-gray-300 bg-white" placeholder="••••••••" style="background-color: #ffffff !important; border: 1px solid #cbd5e1 !important; color: #000000 !important;">
                <p id="lock-passkey-error" class="text-red-500 text-xs font-bold hidden">Invalid Passkey. Please try again.</p>
            </div>
            <div class="flex items-center gap-3 w-full">
                <button onclick="cancelApprovals()" class="flex-1 bg-slate-100 text-slate-700 font-medium py-2.5 rounded-full hover:bg-slate-200 transition-colors text-sm uppercase tracking-wider cursor-pointer border border-slate-200">
                    Cancel
                </button>
                <button onclick="unlockApprovals()" class="flex-1 bg-brand text-slate-900 font-bold py-2.5 rounded-full shadow-lg shadow-brand/20 uppercase tracking-wider text-sm transition-all transform hover:scale-[1.03] active:scale-95 cursor-pointer" style="background-color: #dcfb00 !important; color: #0f172a !important;">
                    Unlock
                </button>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div id="approvals-content" class="max-w-7xl mx-auto px-6 pt-32 pb-12 space-y-8 w-full flex-grow relative z-10 hidden">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-3">
                    <a href="admin.php" class="text-purple-600 hover:text-purple-700 transition-colors p-1.5 rounded-lg bg-purple-500/10 border border-purple-500/20">
                        <i data-lucide="arrow-left" class="w-5 h-5"></i>
                    </a>
                    <h1 class="text-4xl font-bold text-white tracking-tight">Approve Posts</h1>
                </div>
                <p class="text-gray-400 mt-2 pl-12">Review user-submitted events, city-news, and spotlights before they are posted to live lists.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Left Column: Type Filter Pane -->
            <div class="lg:col-span-3 flex flex-col">
                <div class="glass-card p-6 border-white/10 bg-white/5 flex flex-col gap-4">
                    <div>
                        <h3 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                            <i data-lucide="filter" class="text-brand"></i> Filter Type
                        </h3>
                        <p class="text-slate-500 text-xs mt-1">Select post content category</p>
                    </div>
                    
                    <div class="flex flex-col gap-2 mt-2 font-bold text-xs uppercase tracking-wider">
                        <button onclick="setFilter('all')" id="filter-all" class="w-full py-2.5 px-4 rounded-xl text-left bg-brand text-slate-900 transition-all flex items-center justify-between cursor-pointer">
                            <span>All Submissions</span>
                            <span id="count-all" class="bg-slate-900/10 px-2 py-0.5 rounded-full">0</span>
                        </button>
                        <button onclick="setFilter('event')" id="filter-event" class="w-full py-2.5 px-4 rounded-xl text-left text-gray-400 hover:text-white transition-all flex items-center justify-between cursor-pointer">
                            <span>Events</span>
                            <span id="count-event" class="bg-white/5 px-2 py-0.5 rounded-full">0</span>
                        </button>
                        <button onclick="setFilter('news')" id="filter-news" class="w-full py-2.5 px-4 rounded-xl text-left text-gray-400 hover:text-white transition-all flex items-center justify-between cursor-pointer">
                            <span>City-News</span>
                            <span id="count-news" class="bg-white/5 px-2 py-0.5 rounded-full">0</span>
                        </button>
                        <button onclick="setFilter('fact')" id="filter-fact" class="w-full py-2.5 px-4 rounded-xl text-left text-gray-400 hover:text-white transition-all flex items-center justify-between cursor-pointer">
                            <span>New-In-Cbe</span>
                            <span id="count-fact" class="bg-white/5 px-2 py-0.5 rounded-full">0</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Column: Pending Submissions -->
            <div class="lg:col-span-9 flex flex-col">
                <div class="glass-card p-6 border-white/10 bg-white/5 flex flex-col flex-grow gap-6">
                    <div class="border-b border-gray-200/10 pb-4">
                        <h3 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                            <i data-lucide="clock" class="text-yellow-400"></i> Pending Submissions
                        </h3>
                        <p class="text-slate-500 text-xs mt-1">Review user posts and approve to publish immediately or reject</p>
                    </div>

                    <!-- Pending Posts Container -->
                    <div id="pending-posts-list" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Dynamically populated -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Modal (Hidden) -->
    <div id="details-modal" class="fixed inset-0 flex items-center justify-center p-3 sm:p-6 hidden overflow-y-auto" style="z-index: 99990 !important;">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" onclick="closeDetailsModal()"></div>
        <div class="glass-card bg-white border border-gray-200 p-4 sm:p-6 md:p-8 rounded-3xl shadow-2xl relative w-full max-w-4xl flex flex-col max-h-[calc(100vh-2rem)] sm:max-h-[calc(100vh-3rem)] my-auto z-10" style="animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); background-color: #ffffff !important;">
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-gray-200 pb-3 sm:pb-4 shrink-0">
                <div>
                    <h3 class="text-xl sm:text-2xl font-bold tracking-tight" style="color: #0f172a !important;">Review Post Submission</h3>
                    <p class="text-xs mt-0.5 sm:mt-1" style="color: #475569 !important;">Moderate the post details and visuals before publishing.</p>
                </div>
                <button onclick="closeDetailsModal()" class="text-gray-400 hover:text-slate-600 transition-colors cursor-pointer p-1.5 rounded-lg hover:bg-slate-100 shrink-0">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Modal Content (2-Columns scrollable) -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 sm:gap-6 md:gap-8 overflow-y-auto flex-1 min-h-0 py-2 pr-1 my-2">
                <!-- Left Column: Visual Placement Preview -->
                <div class="md:col-span-5 flex flex-col items-center gap-4">
                    <h4 class="text-xs font-bold uppercase tracking-wider self-start flex items-center gap-1.5" style="color: #557f00 !important;">
                        <i data-lucide="eye" class="w-4 h-4"></i> Display Image
                    </h4>
                    
                    <div class="preview-box p-3 rounded-2xl border border-gray-200 bg-slate-950 w-full flex items-center justify-center min-h-[220px]" style="background-color: #020617 !important;">
                        <img id="detailImage" src="" onerror="this.src='logo.png'" class="w-full max-h-[260px] object-contain rounded-xl">
                    </div>

                    <div class="w-full space-y-2 text-xs">
                        <div class="border border-gray-200 bg-slate-50 p-3 rounded-xl" style="background-color: #f8fafc !important;">
                            <span class="block uppercase tracking-wider font-semibold mb-0.5 text-[9px]" style="color: #64748b !important;">Submitted By</span>
                            <span id="detailSubmittedBy" class="font-extrabold text-sm" style="color: #0f172a !important;"></span>
                        </div>
                        <div class="border border-gray-200 bg-slate-50 p-3 rounded-xl" style="background-color: #f8fafc !important;">
                            <span class="block uppercase tracking-wider font-semibold mb-0.5 text-[9px]" style="color: #64748b !important;">Contact Email</span>
                            <span id="detailSubmittedEmail" class="font-extrabold text-sm" style="color: #0f172a !important;"></span>
                        </div>
                        <div class="border border-gray-200 bg-slate-50 p-3 rounded-xl" style="background-color: #f8fafc !important;">
                            <span class="block uppercase tracking-wider font-semibold mb-0.5 text-[9px]" style="color: #64748b !important;">Submitted At</span>
                            <span id="detailSubmittedAt" class="font-bold text-xs" style="color: #0f172a !important;"></span>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Full Details Data -->
                <div class="md:col-span-7 space-y-4 text-xs">
                    <h4 class="text-xs font-bold uppercase tracking-wider flex items-center gap-1.5" style="color: #557f00 !important;">
                        <i data-lucide="file-text" class="w-4 h-4"></i> Post Properties
                    </h4>

                    <!-- Dynamic Properties Container -->
                    <div id="dynamic-properties" class="space-y-4">
                        <!-- Populated by JS -->
                    </div>
                </div>
            </div>

            <!-- Modal Action Buttons Footer -->
            <div class="flex items-center justify-between border-t border-gray-200 pt-3 sm:pt-4 gap-4 shrink-0">
                <button onclick="rejectCurrentPost()" class="px-6 py-3 font-bold text-red-600 bg-red-50 hover:bg-red-100 rounded-xl transition-all border border-red-200 flex items-center gap-1.5 cursor-pointer text-xs uppercase tracking-wider">
                    <i data-lucide="x-circle" class="w-4 h-4"></i> Reject Post
                </button>

                <div class="flex items-center gap-3">
                    <button onclick="closeDetailsModal()" class="px-6 py-3 font-bold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl border border-slate-200 transition-all cursor-pointer text-xs uppercase tracking-wider">
                        Close
                    </button>
                    <button onclick="approveCurrentPost()" class="px-6 py-3 font-bold rounded-xl shadow-lg transition-all transform hover:scale-[1.02] active:scale-95 flex items-center gap-1.5 cursor-pointer text-xs uppercase tracking-wider" style="background-color: #dcfb00 !important; color: #0f172a !important;">
                        <i data-lucide="check-circle" class="w-4 h-4" style="color: #0f172a !important;"></i> Approve & Publish
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Toast Modal -->
    <div id="notification-modal" class="fixed inset-0 flex items-center justify-center p-4 hidden" style="z-index: 99999 !important;">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeNotificationModal()"></div>
        <div class="glass-card bg-white border border-gray-200 px-8 py-8 rounded-3xl shadow-2xl relative w-full max-w-md flex flex-col items-center text-center gap-6 z-10" style="animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); background-color: #ffffff !important;">
            <div id="notification-icon-container" class="w-20 h-20 rounded-full flex items-center justify-center shrink-0 animate-bounce relative">
                <div class="absolute inset-0 rounded-full blur-xl bg-brand/30 opacity-70"></div>
                <div class="w-16 h-16 rounded-full bg-brand flex items-center justify-center border border-brand/20 shadow-lg relative z-10" style="background-color: #dcfb00 !important;">
                    <i id="notification-icon" data-lucide="check-circle" class="w-8 h-8" style="color: #0b0f19 !important;"></i>
                </div>
            </div>
            <div>
                <h3 id="notification-title" class="text-2xl font-bold tracking-tight mb-2" style="color: #0f172a !important;">Success!</h3>
                <p id="notification-message" class="leading-relaxed font-medium text-xs text-gray-650" style="color: #475569 !important;"></p>
            </div>
            <button onclick="closeNotificationModal()" class="w-full text-slate-900 font-bold py-3 px-8 rounded-full shadow-lg shadow-brand/20 uppercase tracking-wider text-sm transition-all transform hover:scale-[1.03] active:scale-95" style="background-color: #dcfb00 !important; color: #0b0f19 !important;">
                Awesome
            </button>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        let pendingPostsList = [];
        let filteredPosts = [];
        let currentFilter = 'all';
        let currentActivePost = null;

        function fetchPendingPosts() {
            const container = document.getElementById('pending-posts-list');
            container.innerHTML = `
                <div class="col-span-2 p-8 text-center text-slate-500 flex flex-col items-center justify-center gap-2">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
                    <span>Loading pending submissions...</span>
                </div>
            `;

            fetch('api.php/api/pending-posts?t=' + new Date().getTime())
                .then(res => res.json())
                .then(posts => {
                    pendingPostsList = posts;
                    updateFiltersCount();
                    applyFilter();
                })
                .catch(err => {
                    console.error('Error fetching pending posts:', err);
                    container.innerHTML = `
                        <div class="col-span-2 p-8 text-center text-red-500 font-semibold">
                            Failed to load pending submissions.
                        </div>
                    `;
                });
        }

        function updateFiltersCount() {
            const allCount = pendingPostsList.length;
            const eventCount = pendingPostsList.filter(p => p.type === 'event').length;
            const newsCount = pendingPostsList.filter(p => p.type === 'news').length;
            const factCount = pendingPostsList.filter(p => p.type === 'fact').length;

            document.getElementById('count-all').textContent = allCount;
            document.getElementById('count-event').textContent = eventCount;
            document.getElementById('count-news').textContent = newsCount;
            document.getElementById('count-fact').textContent = factCount;
        }

        function setFilter(filterType) {
            currentFilter = filterType;
            
            // Toggle classes on buttons
            const types = ['all', 'event', 'news', 'fact'];
            types.forEach(t => {
                const btn = document.getElementById('filter-' + t);
                if (t === filterType) {
                    btn.className = "w-full py-2.5 px-4 rounded-xl text-left bg-brand text-slate-900 transition-all flex items-center justify-between cursor-pointer";
                } else {
                    btn.className = "w-full py-2.5 px-4 rounded-xl text-left text-gray-400 hover:text-white transition-all flex items-center justify-between cursor-pointer";
                }
            });

            applyFilter();
        }

        function applyFilter() {
            if (currentFilter === 'all') {
                filteredPosts = pendingPostsList;
            } else {
                filteredPosts = pendingPostsList.filter(p => p.type === currentFilter);
            }
            renderPendingPosts();
        }

        function renderPendingPosts() {
            const container = document.getElementById('pending-posts-list');
            if (!container) return;

            if (filteredPosts.length === 0) {
                container.innerHTML = `
                    <div class="col-span-2 p-12 text-center text-slate-400 font-medium border border-dashed border-white/10 rounded-2xl flex flex-col items-center justify-center gap-3">
                        <i data-lucide="check-circle" class="w-12 h-12 text-green-400"></i>
                        <span class="text-sm">No pending submissions here! All posts are processed.</span>
                    </div>
                `;
                lucide.createIcons();
                return;
            }

            container.innerHTML = filteredPosts.map(post => {
                let badgeColor = '';
                let typeText = '';
                if (post.type === 'event') {
                    badgeColor = 'bg-purple-500/10 text-purple-400 border border-purple-400/20';
                    typeText = 'Event';
                } else if (post.type === 'news') {
                    badgeColor = 'bg-blue-500/10 text-blue-400 border border-blue-400/20';
                    typeText = 'City-News';
                } else if (post.type === 'fact') {
                    badgeColor = 'bg-yellow-500/10 text-yellow-400 border border-yellow-400/20';
                    typeText = 'New-In-Cbe';
                }

                const postData = post.data || {};
                const desc = postData.description || postData.content || postData.summary || 'No description.';
                const shortDesc = desc.replace(/<[^>]*>/g, '').substring(0, 100) + (desc.length > 100 ? '...' : '');

                return `
                    <div class="border border-white/5 rounded-xl p-5 bg-white/5 flex flex-col gap-4 relative hover:bg-white/10 transition-colors">
                        <div class="flex gap-4 items-start">
                            <!-- Post Thumbnail -->
                            <div class="w-20 h-20 rounded-lg overflow-hidden border border-white/10 bg-slate-950 shrink-0">
                                <img src="${postData.image}" onerror="this.src='logo.png'" class="w-full h-full object-cover">
                            </div>
                            
                            <!-- Post Summary -->
                            <div class="flex-grow min-w-0">
                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-bold ${badgeColor} mb-2">${typeText}</span>
                                <h4 class="text-sm font-bold text-white truncate">${postData.title}</h4>
                                <p class="text-[10px] text-slate-400 mt-1 leading-tight line-clamp-2">${shortDesc}</p>
                                <div class="text-[9px] text-slate-500 mt-3 flex flex-wrap gap-x-3 gap-y-0.5 font-bold">
                                    <span>Submitted By: <strong class="text-slate-300">${post.submittedBy}</strong></span>
                                    <span>At: <strong class="text-slate-400">${post.submittedAt.split(' ')[0]}</strong></span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Bar -->
                        <div class="border-t border-white/5 pt-3 flex justify-end">
                            <button onclick="viewPostDetails('${post.id}')" class="px-4 py-1.5 text-[10px] font-bold text-black bg-brand hover:bg-brand-light rounded-lg transition-all flex items-center gap-1 cursor-pointer shadow-lg shadow-brand/10" style="background-color: #dcfb00 !important;">
                                <i data-lucide="eye" class="w-3.5 h-3.5" style="color: #0b0f19 !important;"></i> View Details
                            </button>
                        </div>
                    </div>
                `;
            }).join('');

            lucide.createIcons();
        }

        function viewPostDetails(id) {
            const post = pendingPostsList.find(item => item.id == id);
            if (!post) return;

            currentActivePost = post;
            const postData = post.data || {};

            // Load left panel properties
            document.getElementById('detailImage').src = postData.image || 'logo.png';
            document.getElementById('detailSubmittedBy').textContent = post.submittedBy || 'N/A';
            document.getElementById('detailSubmittedEmail').textContent = post.submittedEmail || 'N/A';
            document.getElementById('detailSubmittedAt').textContent = post.submittedAt || 'N/A';

            // Generate right panel properties based on type
            const container = document.getElementById('dynamic-properties');
            container.innerHTML = '';

            let propertiesHtml = `
                <div class="border border-gray-200 bg-slate-50 p-4 rounded-xl" style="background-color: #f8fafc !important;">
                    <span class="block uppercase tracking-wider font-semibold mb-0.5 text-[9px]" style="color: #64748b !important;">Title</span>
                    <span class="font-extrabold text-sm text-slate-800" style="color: #0f172a !important;">${postData.title}</span>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="border border-gray-200 bg-slate-50 p-4 rounded-xl" style="background-color: #f8fafc !important;">
                        <span class="block uppercase tracking-wider font-semibold mb-0.5 text-[9px]" style="color: #64748b !important;">Category</span>
                        <span class="font-extrabold text-sm" style="color: #0f172a !important;">${postData.category}</span>
                    </div>
                    <div class="border border-gray-200 bg-slate-50 p-4 rounded-xl" style="background-color: #f8fafc !important;">
                        <span class="block uppercase tracking-wider font-semibold mb-0.5 text-[9px]" style="color: #64748b !important;">Post Type</span>
                        <span class="font-extrabold text-sm" style="color: #0f172a !important;">${postData.postType || 'Standard'}</span>
                    </div>
                </div>
            `;

            if (post.type === 'event') {
                propertiesHtml += `
                    <div class="grid grid-cols-2 gap-4">
                        <div class="border border-gray-200 bg-slate-50 p-4 rounded-xl" style="background-color: #f8fafc !important;">
                            <span class="block uppercase tracking-wider font-semibold mb-0.5 text-[9px]" style="color: #64748b !important;">Event Date</span>
                            <span class="font-bold text-xs" style="color: #0f172a !important;">${postData.date} to ${postData.endDate || postData.date}</span>
                        </div>
                        <div class="border border-gray-200 bg-slate-50 p-4 rounded-xl" style="background-color: #f8fafc !important;">
                            <span class="block uppercase tracking-wider font-semibold mb-0.5 text-[9px]" style="color: #64748b !important;">Time & Duration</span>
                            <span class="font-bold text-xs" style="color: #0f172a !important;">${postData.time || 'N/A'} (${postData.noOfDays || 1} Days)</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="border border-gray-200 bg-slate-50 p-4 rounded-xl" style="background-color: #f8fafc !important;">
                            <span class="block uppercase tracking-wider font-semibold mb-0.5 text-[9px]" style="color: #64748b !important;">Venue</span>
                            <span class="font-bold text-xs" style="color: #0f172a !important;">${postData.venue}</span>
                        </div>
                        <div class="border border-gray-200 bg-slate-50 p-4 rounded-xl" style="background-color: #f8fafc !important;">
                            <span class="block uppercase tracking-wider font-semibold mb-0.5 text-[9px]" style="color: #64748b !important;">Organizer</span>
                            <span class="font-bold text-xs" style="color: #0f172a !important;">${postData.organizer}</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="border border-gray-200 bg-slate-50 p-4 rounded-xl" style="background-color: #f8fafc !important;">
                            <span class="block uppercase tracking-wider font-semibold mb-0.5 text-[9px]" style="color: #64748b !important;">Entry Cost (Price)</span>
                            <span class="font-bold text-xs" style="color: #0f172a !important;">${postData.price || 'Free'}</span>
                        </div>
                        <div class="border border-gray-200 bg-slate-50 p-4 rounded-xl" style="background-color: #f8fafc !important;">
                            <span class="block uppercase tracking-wider font-semibold mb-0.5 text-[9px]" style="color: #64748b !important;">Max Participants</span>
                            <span class="font-bold text-xs" style="color: #0f172a !important;">${postData.maxParticipants || 'Unlimited'}</span>
                        </div>
                    </div>
                    <div class="border border-gray-200 bg-slate-50 p-4 rounded-xl" style="background-color: #f8fafc !important;">
                        <span class="block uppercase tracking-wider font-semibold mb-1 text-[9px]" style="color: #64748b !important;">Location Map/Registration URL</span>
                        <a href="${postData.registrationUrl || '#'}" target="_blank" class="font-bold truncate flex items-center gap-1 text-xs" style="color: #557f00 !important;">
                            <span class="truncate">${postData.registrationUrl || 'None'}</span> <i data-lucide="external-link" class="w-3.5 h-3.5 shrink-0" style="color: #557f00 !important;"></i>
                        </a>
                    </div>
                `;
            } else {
                // News or Spotlight Spot
                propertiesHtml += `
                    <div class="grid grid-cols-2 gap-4">
                        <div class="border border-gray-200 bg-slate-50 p-4 rounded-xl" style="background-color: #f8fafc !important;">
                            <span class="block uppercase tracking-wider font-semibold mb-0.5 text-[9px]" style="color: #64748b !important;">Location State</span>
                            <span class="font-bold text-xs" style="color: #0f172a !important;">${postData.state || 'Tamil Nadu'}</span>
                        </div>
                        <div class="border border-gray-200 bg-slate-50 p-4 rounded-xl" style="background-color: #f8fafc !important;">
                            <span class="block uppercase tracking-wider font-semibold mb-0.5 text-[9px]" style="color: #64748b !important;">Validity (Days)</span>
                            <span class="font-bold text-xs" style="color: #0f172a !important;">${postData.validity || 30} Days</span>
                        </div>
                    </div>
                    ${postData.source ? `
                    <div class="border border-gray-200 bg-slate-50 p-4 rounded-xl" style="background-color: #f8fafc !important;">
                        <span class="block uppercase tracking-wider font-semibold mb-0.5 text-[9px]" style="color: #64748b !important;">Publisher Source</span>
                        <span class="font-bold text-xs" style="color: #0f172a !important;">${postData.source}</span>
                    </div>` : ''}
                    ${postData.url ? `
                    <div class="border border-gray-200 bg-slate-50 p-4 rounded-xl" style="background-color: #f8fafc !important;">
                        <span class="block uppercase tracking-wider font-semibold mb-1 text-[9px]" style="color: #64748b !important;">Reference Link</span>
                        <a href="${postData.url}" target="_blank" class="font-bold truncate flex items-center gap-1 text-xs" style="color: #557f00 !important;">
                            <span class="truncate">${postData.url}</span> <i data-lucide="external-link" class="w-3.5 h-3.5 shrink-0" style="color: #557f00 !important;"></i>
                        </a>
                    </div>` : ''}
                    ${postData.videoURL ? `
                    <div class="border border-gray-200 bg-slate-50 p-4 rounded-xl" style="background-color: #f8fafc !important;">
                        <span class="block uppercase tracking-wider font-semibold mb-1 text-[9px]" style="color: #64748b !important;">Video URL</span>
                        <a href="${postData.videoURL}" target="_blank" class="font-bold truncate flex items-center gap-1 text-xs" style="color: #557f00 !important;">
                            <span class="truncate">${postData.videoURL}</span> <i data-lucide="external-link" class="w-3.5 h-3.5 shrink-0" style="color: #557f00 !important;"></i>
                        </a>
                    </div>` : ''}
                `;
            }

            const mainDesc = postData.description || postData.content || postData.summary || '';
            propertiesHtml += `
                <div class="border border-gray-200 bg-slate-50 p-4 rounded-xl" style="background-color: #f8fafc !important;">
                    <span class="block uppercase tracking-wider font-semibold mb-1 text-[9px]" style="color: #64748b !important;">Description / Content Details</span>
                    <p class="leading-relaxed text-slate-700 text-xs font-semibold" style="color: #1e293b !important;">${mainDesc}</p>
                </div>
            `;

            container.innerHTML = propertiesHtml;
            lucide.createIcons();
            document.getElementById('details-modal').classList.remove('hidden');
        }

        function closeDetailsModal() {
            document.getElementById('details-modal').classList.add('hidden');
            currentActivePost = null;
        }

        function approveCurrentPost() {
            if (!currentActivePost) return;

            fetch('api.php/api/pending-posts/' + currentActivePost.id + '/approve', {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' }
            })
            .then(res => {
                if (!res.ok) {
                    throw new Error('Failed to approve post');
                }
                return res.json();
            })
            .then(data => {
                closeDetailsModal();
                showNotification('Post approved successfully! It is now live in the Madras feeds.', 'Approved', 'check-circle', true);
                fetchPendingPosts();
            })
            .catch(err => {
                console.error(err);
                showNotification('Failed to approve post.', 'Error', 'x-circle', false);
            });
        }

        function rejectCurrentPost() {
            if (!currentActivePost) return;

            if (confirm('Are you sure you want to reject and delete this submission?')) {
                fetch('api.php/api/pending-posts/' + currentActivePost.id + '/reject', {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json' }
                })
                .then(res => {
                    if (!res.ok) throw new Error('Failed to reject post');
                    return res.json();
                })
                .then(data => {
                    closeDetailsModal();
                    showNotification('Post submission rejected and removed.', 'Rejected', 'x-circle', true);
                    fetchPendingPosts();
                })
                .catch(err => {
                    console.error(err);
                    showNotification('Failed to reject post.', 'Error', 'x-circle', false);
                });
            }
        }

        function showNotification(message, title = 'Success!', icon = 'check-circle', isSuccess = true) {
            document.getElementById('notification-title').textContent = title;
            document.getElementById('notification-message').textContent = message;
            
            const iconEl = document.getElementById('notification-icon');
            iconEl.setAttribute('data-lucide', icon);
            
            const iconContainer = document.getElementById('notification-icon-container');
            const innerIconBg = iconContainer.querySelector('.w-16');
            const outerGlow = iconContainer.querySelector('.blur-xl');
            
            if (isSuccess) {
                innerIconBg.style.backgroundColor = "#dcfb00";
                iconEl.style.color = "#0b0f19";
                outerGlow.style.backgroundColor = "rgba(220, 251, 0, 0.3)";
            } else {
                innerIconBg.style.backgroundColor = "#ef4444";
                iconEl.style.color = "#ffffff";
                outerGlow.style.backgroundColor = "rgba(239, 68, 68, 0.3)";
            }
            
            lucide.createIcons();
            document.getElementById('notification-modal').classList.remove('hidden');
        }

        function closeNotificationModal() {
            document.getElementById('notification-modal').classList.add('hidden');
        }

        function unlockApprovals() {
            const val = document.getElementById('lock-passkey-input').value;
            if (val === 'MasterMind@1986') {
                document.getElementById('approvals-lock-overlay').classList.add('hidden');
                document.getElementById('approvals-content').classList.remove('hidden');
                fetchPendingPosts();
            } else {
                document.getElementById('lock-passkey-error').classList.remove('hidden');
                document.getElementById('lock-passkey-input').value = '';
                document.getElementById('lock-passkey-input').focus();
            }
        }

        function cancelApprovals() {
            window.location.href = 'admin.php';
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined' && lucide.createIcons) {
                lucide.createIcons();
            }
            
            const lockInput = document.getElementById('lock-passkey-input');
            if (lockInput) {
                lockInput.focus();
                setTimeout(() => lockInput.focus(), 100);
                
                lockInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        unlockApprovals();
                    }
                });
            }
        });
    </script>
</body>
</html>
