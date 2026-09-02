<?php
require_once 'postlog.php';
session_start();

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['user_role']);
    session_destroy();
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login_admin') {
    header('Content-Type: application/json');
    if (($_POST['passkey'] ?? '') === 'MasterMind@1986') {
        $_SESSION['user_role'] = 'admin';
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Impression Insights | Kovai Admin</title>

    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">

    <link rel="icon" type="image/png" href="logo.png">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
      .lucide { width: 20px; height: 20px; }
      .loader-spinner {
          width: 48px;
          height: 48px;
          border: 4px solid rgba(168, 85, 247, 0.2);
          border-top-color: #a855f7;
          border-radius: 50%;
          animation: spin 1s linear infinite;
      }
      @keyframes spin {
          0% { transform: rotate(0deg); }
          100% { transform: rotate(360deg); }
      }
      /* Custom Visible Horizontal Side Scrollbars for Mobile View */
      .custom-scrollbar-x {
          overflow-x: auto !important;
      }
      .custom-scrollbar-x::-webkit-scrollbar {
          height: 8px;
      }
      .custom-scrollbar-x::-webkit-scrollbar-track {
          background: rgba(255, 255, 255, 0.05);
          border-radius: 8px;
      }
      .custom-scrollbar-x::-webkit-scrollbar-thumb {
          background: linear-gradient(90deg, #7c3aed, #a855f7);
          border-radius: 8px;
          border: 1.5px solid rgba(15, 23, 42, 0.8);
      }
      .custom-scrollbar-x::-webkit-scrollbar-thumb:hover {
          background: #c084fc;
      }
      @media (max-width: 768px) {
          .mobile-scroll-hint {
              display: flex !important;
          }
      }
    </style>
</head>
<body class="min-h-screen flex flex-col relative overflow-x-hidden bg-theme-bg text-white">

    <!-- Admin Lock Overlay -->
    <?php if (!$isAdmin): ?>
    <div id="admin-lock-overlay" class="fixed inset-0 flex items-center justify-center p-4" style="background-color: rgba(10, 8, 20, 0.98); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); z-index: 999999 !important;">
        <div class="border px-8 py-10 rounded-3xl shadow-2xl relative w-full max-w-md flex flex-col items-center text-center gap-6" style="animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); background-color: rgba(30, 27, 46, 0.98) !important; border: 1px solid rgba(255, 255, 255, 0.15) !important;">
            <div class="w-20 h-20 rounded-full flex items-center justify-center shrink-0" style="background-color: rgba(168, 85, 247, 0.1); border: 1px solid rgba(168, 85, 247, 0.2);">
                <i data-lucide="lock" class="w-10 h-10 text-purple-400 animate-pulse"></i>
            </div>
            <div>
                <h3 class="text-3xl font-extrabold tracking-tight mb-2" style="color: #ffffff !important;">Analytics Restricted</h3>
                <p class="text-gray-400 text-sm leading-relaxed px-4" style="color: #cbd5e1 !important;">Please enter the administrator passkey to access Kovai.city impression analytics.</p>
            </div>
            <div class="w-full space-y-4">
                <input type="password" id="lock-passkey-input" class="w-full rounded-xl px-4 py-3.5 focus:outline-none focus:border-brand text-center tracking-widest placeholder:tracking-normal text-lg" style="background-color: #ffffff !important; border: 1px solid #cbd5e1 !important; color: #000000 !important;" placeholder="Enter Passkey" autofocus>
                <p id="lock-passkey-error" class="text-red-400 text-xs text-center font-bold hidden flex items-center justify-center gap-1">
                    <i data-lucide="alert-circle" class="w-3.5 h-3.5 text-red-400"></i> Access Denied! Invalid passkey.
                </p>
            </div>
            <div class="flex gap-4 w-full pt-2">
                <a href="admin.php" class="flex-1 bg-transparent text-white font-medium py-2.5 rounded-full hover:bg-white/5 transition-colors text-sm uppercase tracking-wider cursor-pointer text-center" style="border: 1px solid rgba(255, 255, 255, 0.2); color: #ffffff !important;">
                    Return
                </a>
                <button onclick="unlockAnalyticsAccess()" class="flex-1 bg-gradient-to-r from-[#7c3aed] to-[#a855f7] hover:from-[#6d28d9] hover:to-[#9333ea] text-white font-bold py-2.5 rounded-full shadow-lg shadow-purple-500/20 uppercase tracking-wider text-sm transition-all transform hover:scale-[1.03] active:scale-95 cursor-pointer" style="color: #ffffff !important;">
                    Unlock
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <main class="max-w-7xl mx-auto px-6 pt-32 pb-16 space-y-10 w-full flex-grow relative z-10">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <span class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-widest bg-blue-500/10 text-blue-400 border border-blue-500/20">
                        Admin Analytics
                    </span>
                    <span class="text-gray-500 text-xs font-bold uppercase tracking-wider" id="last-updated-time">Live Data</span>
                </div>
                <h1 class="text-4xl font-extrabold text-white tracking-tight">Navbar & Posts Impression Analytics</h1>
                <p class="text-gray-400 text-sm mt-1">Real-time performance tracking for navbar buttons and community posts</p>
            </div>

            <div class="flex items-center gap-3">
                <button onclick="downloadAdminAnalyticsCSV()" class="px-4 py-2.5 rounded-full border border-emerald-500/30 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 font-bold uppercase tracking-wider text-xs transition-all flex items-center gap-2 cursor-pointer shadow-md">
                    <i data-lucide="download" class="w-4 h-4 text-emerald-400"></i> Export CSV
                </button>
                <button onclick="resetAnalyticsData()" class="px-4 py-2.5 rounded-full border border-red-500/30 bg-red-500/10 hover:bg-red-500/20 text-red-400 font-bold uppercase tracking-wider text-xs transition-all flex items-center gap-2 cursor-pointer shadow-md">
                    <i data-lucide="rotate-ccw" class="w-4 h-4 text-red-400"></i> Reset Data
                </button>
                <button onclick="fetchAnalytics()" class="px-4 py-2.5 rounded-full border border-blue-500/30 bg-blue-500/10 hover:bg-blue-500/20 text-blue-400 font-bold uppercase tracking-wider text-xs transition-all flex items-center gap-2 cursor-pointer shadow-md">
                    <i data-lucide="refresh-cw" class="w-4 h-4 text-blue-400"></i> Refresh Metrics
                </button>
                <a href="admin.php" class="px-4 py-2.5 rounded-full border border-purple-500/30 bg-purple-500/10 hover:bg-purple-500/20 text-purple-400 font-bold uppercase tracking-wider text-xs transition-all flex items-center gap-2 cursor-pointer shadow-md">
                    <i data-lucide="arrow-left" class="w-4 h-4 text-purple-400"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Metric Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            
            <!-- Navbar Impressions -->
            <div class="glass-card p-6 border-white/10 relative overflow-hidden group">
                <div class="absolute top-0 right-0 p-4 text-blue-400 opacity-20 group-hover:opacity-40 transition-opacity">
                    <i data-lucide="navigation" class="w-12 h-12"></i>
                </div>
                <span class="text-gray-400 text-xs font-extrabold uppercase tracking-widest block mb-2">NAVBAR IMPRESSIONS</span>
                <div class="flex items-baseline gap-3">
                    <h2 id="total-nav-impressions" class="text-3xl font-extrabold text-white">0</h2>
                    <span id="nav-ctr-badge" class="text-xs font-bold px-2 py-0.5 rounded-full bg-blue-500/20 text-blue-300">0.0% CTR</span>
                </div>
                <p class="text-xs text-gray-500 mt-2 flex items-center gap-1">
                    Total views across navigation links & buttons
                </p>
            </div>

            <!-- Navbar Clicks -->
            <div class="glass-card p-6 border-white/10 relative overflow-hidden group">
                <div class="absolute top-0 right-0 p-4 text-green-400 opacity-20 group-hover:opacity-40 transition-opacity">
                    <i data-lucide="mouse-pointer" class="w-12 h-12"></i>
                </div>
                <span class="text-gray-400 text-xs font-extrabold uppercase tracking-widest block mb-2">NAVBAR CLICKS</span>
                <div class="flex items-baseline gap-3">
                    <h2 id="total-nav-clicks" class="text-3xl font-extrabold text-green-400">0</h2>
                    <span class="text-xs font-bold text-gray-400">Engagements</span>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    Total user clicks recorded on navbar buttons
                </p>
            </div>

            <!-- Post Impressions -->
            <div class="glass-card p-6 border-white/10 relative overflow-hidden group">
                <div class="absolute top-0 right-0 p-4 text-purple-400 opacity-20 group-hover:opacity-40 transition-opacity">
                    <i data-lucide="eye" class="w-12 h-12"></i>
                </div>
                <span class="text-gray-400 text-xs font-extrabold uppercase tracking-widest block mb-2">POST IMPRESSIONS</span>
                <div class="flex items-baseline gap-3">
                    <h2 id="total-post-impressions" class="text-3xl font-extrabold text-purple-400">0</h2>
                    <span id="post-ctr-badge" class="text-xs font-bold px-2 py-0.5 rounded-full bg-purple-500/20 text-purple-300">0.0% CTR</span>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    Calculated views for events, news, facts & ads
                </p>
            </div>

            <!-- Post Clicks -->
            <div class="glass-card p-6 border-white/10 relative overflow-hidden group">
                <div class="absolute top-0 right-0 p-4 text-yellow-400 opacity-20 group-hover:opacity-40 transition-opacity">
                    <i data-lucide="bar-chart-2" class="w-12 h-12"></i>
                </div>
                <span class="text-gray-400 text-xs font-extrabold uppercase tracking-widest block mb-2">POST CLICKS & ACTIONS</span>
                <div class="flex items-baseline gap-3">
                    <h2 id="total-post-clicks" class="text-3xl font-extrabold text-yellow-400">0</h2>
                    <span id="total-posts-count-badge" class="text-xs font-bold text-gray-400">0 Posts</span>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    Total clicks, detail views & registrations
                </p>
            </div>
        </div>

        <!-- Section 1: Navbar Button Impressions Table -->
        <div class="glass-card overflow-hidden border-white/10">
            <div class="p-6 border-b border-white/5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white/5">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 rounded-2xl bg-blue-500/10 border border-blue-500/20 text-blue-400">
                        <i data-lucide="layout" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-white">Navbar Buttons Impression & Click Breakdown</h2>
                        <p class="text-xs text-gray-400">Calculated impressions, clicks, and click-through rates for each navbar item</p>
                    </div>
                </div>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest bg-white/5 px-3 py-1.5 rounded-full border border-white/5 self-start sm:self-auto">
                    Sorted by Impressions
                </span>
            </div>

            <div class="hidden mobile-scroll-hint items-center gap-1.5 text-[11px] font-bold text-purple-400 bg-purple-500/10 border border-purple-500/20 px-3 py-1.5 rounded-full mx-6 mt-4 w-fit">
                <i data-lucide="arrow-left-right" class="w-3.5 h-3.5 text-purple-400"></i> Scroll sideways to view full table details
            </div>

            <div class="overflow-x-auto custom-scrollbar-x pb-2">
                <table class="w-full text-left min-w-[650px]">
                    <thead>
                        <tr class="border-b border-white/5 uppercase font-bold text-gray-400 tracking-widest bg-white/5" style="font-size: 10px;">
                            <th class="px-6 py-4">Navbar Button</th>
                            <th class="px-6 py-4 text-center">Impressions (Views)</th>
                            <th class="px-6 py-4 text-center">Clicks (Interactions)</th>
                            <th class="px-6 py-4 text-center">CTR %</th>
                            <th class="px-6 py-4">Impression Distribution</th>
                        </tr>
                    </thead>
                    <tbody id="navbar-analytics-body" class="divide-y divide-white/5">
                        <!-- Populated via JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 2: Posts Impression & Click Table -->
        <div class="glass-card overflow-hidden border-white/10">
            <div class="p-6 border-b border-white/5 flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white/5">
                <div>
                    <h2 class="text-xl font-bold text-white mb-1">Posts Impression & Engagement Performance</h2>
                    <p class="text-xs text-gray-400">Impressions calculated for individual events, city-news, new-in-cbe, classified ads, and announcements</p>
                </div>

                <!-- Filters & Search -->
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Category Tabs -->
                    <div class="flex items-center gap-1.5 bg-black/40 p-1 rounded-2xl border border-white/10 text-xs font-bold custom-scrollbar-x max-w-full overflow-x-auto whitespace-nowrap">
                        <button onclick="filterPostType('All')" id="post-tab-All" class="px-3 py-1.5 rounded-xl transition-all uppercase bg-purple-600 text-white font-extrabold shrink-0">All</button>
                        <button onclick="filterPostType('Events')" id="post-tab-Events" class="px-3 py-1.5 rounded-xl transition-all uppercase text-gray-400 hover:text-white shrink-0">Events</button>
                        <button onclick="filterPostType('City-News')" id="post-tab-City-News" class="px-3 py-1.5 rounded-xl transition-all uppercase text-gray-400 hover:text-white shrink-0">News</button>
                        <button onclick="filterPostType('New-In-Cbe')" id="post-tab-New-In-Cbe" class="px-3 py-1.5 rounded-xl transition-all uppercase text-gray-400 hover:text-white shrink-0">New-In-Cbe</button>
                        <button onclick="filterPostType('Classified Ads')" id="post-tab-Classified-Ads" class="px-3 py-1.5 rounded-xl transition-all uppercase text-gray-400 hover:text-white shrink-0">Ads</button>
                        <button onclick="filterPostType('Announcements')" id="post-tab-Announcements" class="px-3 py-1.5 rounded-xl transition-all uppercase text-gray-400 hover:text-white shrink-0">Polls</button>
                    </div>

                    <!-- Search Input -->
                    <div class="relative">
                        <i data-lucide="search" class="w-4 h-4 text-gray-500 absolute left-3 top-1/2 transform -translate-y-1/2"></i>
                        <input type="text" id="post-search-input" oninput="renderPostsTable()" placeholder="Search posts..." class="bg-black/50 border border-white/10 rounded-xl pl-9 pr-4 py-1.5 text-xs text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 w-48">
                    </div>
                </div>
            </div>

            <div class="hidden mobile-scroll-hint items-center gap-1.5 text-[11px] font-bold text-purple-400 bg-purple-500/10 border border-purple-500/20 px-3 py-1.5 rounded-full mx-6 mt-4 w-fit">
                <i data-lucide="arrow-left-right" class="w-3.5 h-3.5 text-purple-400"></i> Scroll sideways to view full table details
            </div>

            <div class="overflow-x-auto custom-scrollbar-x pb-2">
                <table class="w-full text-left min-w-[750px]">
                    <thead>
                        <tr class="border-b border-white/5 uppercase font-bold text-gray-400 tracking-widest bg-white/5" style="font-size: 10px;">
                            <th class="px-6 py-4">Post Content</th>
                            <th class="px-6 py-4">Type / Category</th>
                            <th class="px-6 py-4 text-center">Impressions</th>
                            <th class="px-6 py-4 text-center">Clicks / Actions</th>
                            <th class="px-6 py-4 text-center">CTR %</th>
                            <th class="px-6 py-4 text-right">View Link</th>
                        </tr>
                    </thead>
                    <tbody id="posts-analytics-body" class="divide-y divide-white/5">
                        <!-- Populated via JS -->
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <?php include 'footer.php'; ?>

    <script>
        function unlockAnalyticsAccess() {
            const passkey = document.getElementById('lock-passkey-input').value;
            const errorEl = document.getElementById('lock-passkey-error');

            fetch('analytics.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'login_admin',
                    passkey: passkey
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    if (errorEl) errorEl.classList.remove('hidden');
                }
            })
            .catch(() => {
                if (errorEl) errorEl.classList.remove('hidden');
            });
        }

        let rawAnalyticsData = {
            summary: {},
            navbar: [],
            posts: []
        };
        let activePostFilter = 'All';

        function resetAnalyticsData() {
            if (confirm('Are you sure you want to reset all analytics data to 0 and calculate fresh from scratch?')) {
                fetch('api.php?action=reset_analytics', { method: 'POST' })
                    .then(res => res.json())
                    .then(data => {
                        alert(data.message || 'Analytics data reset successfully!');
                        fetchAnalytics();
                    })
                    .catch(err => {
                        console.error('Failed to reset analytics:', err);
                        alert('Failed to reset analytics data.');
                    });
            }
        }

        function fetchAnalytics() {
            fetch('api.php?action=get_analytics&t=' + new Date().getTime())
                .then(res => res.json())
                .then(data => {
                    rawAnalyticsData = data;
                    updateSummaryUI();
                    renderNavbarTable();
                    renderPostsTable();
                    
                    const timeEl = document.getElementById('last-updated-time');
                    if (timeEl) {
                        timeEl.textContent = 'Updated ' + new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                    }
                })
                .catch(err => {
                    console.error('Failed to load analytics:', err);
                });
        }

        function updateSummaryUI() {
            const sum = rawAnalyticsData.summary || {};
            
            document.getElementById('total-nav-impressions').textContent = (sum.total_navbar_impressions || 0).toLocaleString();
            document.getElementById('total-nav-clicks').textContent = (sum.total_navbar_clicks || 0).toLocaleString();
            document.getElementById('nav-ctr-badge').textContent = (sum.navbar_ctr || 0.0) + '% CTR';
            
            document.getElementById('total-post-impressions').textContent = (sum.total_post_impressions || 0).toLocaleString();
            document.getElementById('total-post-clicks').textContent = (sum.total_post_clicks || 0).toLocaleString();
            document.getElementById('post-ctr-badge').textContent = (sum.post_ctr || 0.0) + '% CTR';
            document.getElementById('total-posts-count-badge').textContent = (sum.total_posts_count || 0) + ' Total Posts';
        }

        function renderNavbarTable() {
            const body = document.getElementById('navbar-analytics-body');
            const navList = rawAnalyticsData.navbar || [];
            
            if (navList.length === 0) {
                body.innerHTML = `<tr><td colspan="5" class="px-6 py-8 text-center text-gray-500 italic">No navbar impression data recorded yet.</td></tr>`;
                return;
            }

            const maxImp = Math.max(...navList.map(n => n.impressions || 0), 1);

            body.innerHTML = navList.map(item => {
                const pct = Math.min(100, Math.round((item.impressions / maxImp) * 100)) || 2;
                return `
                    <tr class="hover:bg-white/5 transition-colors">
                        <td class="px-6 py-4 font-bold text-sm text-white flex items-center gap-2">
                            <i data-lucide="compass" class="w-4 h-4 text-blue-400"></i> ${escapeHtml(item.button)}
                        </td>
                        <td class="px-6 py-4 text-center font-extrabold text-sm text-blue-300">
                            ${(item.impressions || 0).toLocaleString()}
                        </td>
                        <td class="px-6 py-4 text-center font-extrabold text-sm text-green-400">
                            ${(item.clicks || 0).toLocaleString()}
                        </td>
                        <td class="px-6 py-4 text-center font-bold text-xs text-purple-300">
                            <span class="px-2.5 py-1 rounded-full bg-purple-500/10 border border-purple-500/20">${(item.ctr || 0.0)}%</span>
                        </td>
                        <td class="px-6 py-4 w-48">
                            <div class="w-full bg-white/10 rounded-full h-2 overflow-hidden">
                                <div class="bg-gradient-to-r from-blue-500 to-purple-500 h-full rounded-full transition-all duration-500" style="width: ${pct}%"></div>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            if (typeof lucide !== 'undefined' && lucide.createIcons) {
                lucide.createIcons();
            }
        }

        function filterPostType(type) {
            activePostFilter = type;
            const buttons = ['All', 'Events', 'City-News', 'New-In-Cbe', 'Classified Ads', 'Announcements'];
            buttons.forEach(b => {
                const btn = document.getElementById('post-tab-' + b.replace(/\s+/g, '-'));
                if (btn) {
                    if (b === type) {
                        btn.className = 'px-3 py-1.5 rounded-xl transition-all uppercase bg-purple-600 text-white font-extrabold';
                    } else {
                        btn.className = 'px-3 py-1.5 rounded-xl transition-all uppercase text-gray-400 hover:text-white';
                    }
                }
            });
            renderPostsTable();
        }

        function renderPostsTable() {
            const body = document.getElementById('posts-analytics-body');
            let posts = rawAnalyticsData.posts || [];
            
            if (activePostFilter !== 'All') {
                posts = posts.filter(p => (p.type || '').toLowerCase() === activePostFilter.toLowerCase());
            }

            const query = (document.getElementById('post-search-input').value || '').toLowerCase().trim();
            if (query) {
                posts = posts.filter(p => 
                    (p.title || '').toLowerCase().includes(query) || 
                    (p.category || '').toLowerCase().includes(query)
                );
            }

            if (posts.length === 0) {
                body.innerHTML = `<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500 italic">No posts found matching the filter.</td></tr>`;
                return;
            }

            body.innerHTML = posts.map(item => {
                const defaultImg = 'https://images.unsplash.com/photo-1557804506-669a67965ba0?auto=format&fit=crop&w=300&q=80';
                const imgSrc = (item.image && item.image.trim()) ? item.image : defaultImg;
                
                let badgeClass = 'bg-blue-500/10 text-blue-400 border-blue-500/20';
                if (item.type === 'City-News') badgeClass = 'bg-green-500/10 text-green-400 border-green-500/20';
                else if (item.type === 'New-In-Cbe') badgeClass = 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20';
                else if (item.type === 'Classified Ads') badgeClass = 'bg-purple-500/10 text-purple-400 border-purple-500/20';
                else if (item.type === 'Announcements') badgeClass = 'bg-red-500/10 text-red-400 border-red-500/20';

                return `
                    <tr class="hover:bg-white/5 transition-colors">
                        <td class="px-6 py-3 font-semibold text-sm text-white flex items-center gap-3">
                            <img src="${imgSrc}" onerror="this.src='${defaultImg}'" class="w-10 h-10 object-cover rounded-xl border border-white/10 shrink-0">
                            <div>
                                <h4 class="line-clamp-1 font-bold text-white text-xs">${escapeHtml(item.title)}</h4>
                                <span class="text-[10px] text-gray-400">${item.date ? escapeHtml(item.date) : 'Active'}</span>
                            </div>
                        </td>
                        <td class="px-6 py-3">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase border ${badgeClass}">
                                ${escapeHtml(item.type)} &bull; ${escapeHtml(item.category)}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-center font-extrabold text-sm text-purple-300">
                            ${(item.impressions || 0).toLocaleString()}
                        </td>
                        <td class="px-6 py-3 text-center font-extrabold text-sm text-yellow-400">
                            ${(item.clicks || 0).toLocaleString()}
                        </td>
                        <td class="px-6 py-3 text-center font-bold text-xs text-green-400">
                            ${(item.ctr || 0.0)}%
                        </td>
                        <td class="px-6 py-3 text-right">
                            <a href="${escapeHtml(item.url)}" target="_blank" class="p-2 rounded-lg bg-white/5 hover:bg-white/10 text-gray-300 hover:text-white transition-colors inline-flex items-center" title="Open Post">
                                <i data-lucide="external-link" class="w-4 h-4"></i>
                            </a>
                        </td>
                    </tr>
                `;
            }).join('');

            if (typeof lucide !== 'undefined' && lucide.createIcons) {
                lucide.createIcons();
            }
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function downloadAdminAnalyticsCSV() {
            const sum = rawAnalyticsData.summary || {};
            const navList = rawAnalyticsData.navbar || [];
            const postsList = rawAnalyticsData.posts || [];
            const today = new Date().toISOString().split('T')[0];
            const filename = `Kovai_Analytics_Report_${today}.csv`;

            function escapeCSVField(val) {
                if (val === null || val === undefined) return '""';
                let str = String(val).replace(/"/g, '""');
                if (str.includes(',') || str.includes('\n') || str.includes('"')) {
                    return `"${str}"`;
                }
                return str;
            }

            let csv = [];
            csv.push('==================================================');
            csv.push('KOVAI.CITY - ADMIN IMPRESSION & ANALYTICS REPORT');
            csv.push('==================================================');
            csv.push(`Report Generated Date,${escapeCSVField(new Date().toLocaleString())}`);
            csv.push('');

            csv.push('==================================================');
            csv.push('OVERALL SUMMARY METRICS');
            csv.push('==================================================');
            csv.push('Metric,Value');
            csv.push(`Total Navbar Impressions,${sum.total_navbar_impressions || 0}`);
            csv.push(`Total Navbar Clicks,${sum.total_navbar_clicks || 0}`);
            csv.push(`Navbar CTR,${sum.navbar_ctr || 0.0}%`);
            csv.push(`Total Post Impressions,${sum.total_post_impressions || 0}`);
            csv.push(`Total Post Clicks,${sum.total_post_clicks || 0}`);
            csv.push(`Post CTR,${sum.post_ctr || 0.0}%`);
            csv.push(`Total Posts Count,${sum.total_posts_count || 0}`);
            csv.push('');

            csv.push('==================================================');
            csv.push('NAVBAR BUTTON IMPRESSIONS & CLICKS');
            csv.push('==================================================');
            csv.push('Navbar Button,Impressions,Clicks,CTR %');
            if (navList.length > 0) {
                navList.forEach(item => {
                    csv.push(`${escapeCSVField(item.button)},${item.impressions || 0},${item.clicks || 0},${item.ctr || 0.0}%`);
                });
            } else {
                csv.push('No navbar impression data,0,0,0.0%');
            }
            csv.push('');

            csv.push('==================================================');
            csv.push('POSTS IMPRESSIONS & ENGAGEMENT PERFORMANCE');
            csv.push('==================================================');
            csv.push('Post Title,Post Type,Category,Date,Impressions,Clicks,CTR %');
            if (postsList.length > 0) {
                postsList.forEach(item => {
                    csv.push(`${escapeCSVField(item.title)},${escapeCSVField(item.type)},${escapeCSVField(item.category)},${escapeCSVField(item.date || '')},${item.impressions || 0},${item.clicks || 0},${item.ctr || 0.0}%`);
                });
            } else {
                csv.push('No post analytics data,N/A,N/A,N/A,0,0,0.0%');
            }

            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            if (link.download !== undefined) {
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', filename);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }

        // Initial Load
        document.addEventListener('DOMContentLoaded', fetchAnalytics);
    </script>
</body>
</html>
