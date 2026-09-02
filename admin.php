<?php
require_once 'postlog.php';
// admin.php
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
        $_SESSION['admin_unlocked'] = true;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

$isAdmin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') || (isset($_SESSION['admin_unlocked']) && $_SESSION['admin_unlocked'] === true);

$eventsFile = __DIR__ . '/events.json';
$newsFile = __DIR__ . '/news.json';
$factsFile = __DIR__ . '/facts.json';

$eventsCount = file_exists($eventsFile) ? count(json_decode(file_get_contents($eventsFile), true) ?? []) : 0;
$newsCount = file_exists($newsFile) ? count(json_decode(file_get_contents($newsFile), true) ?? []) : 0;
$factsCount = file_exists($factsFile) ? count(json_decode(file_get_contents($factsFile), true) ?? []) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Madras</title>

    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">

    <meta property="og:title" content="Admin Dashboard">
    <meta property="og:description" content="Restricted administrative access.">
    <meta property="og:type" content="website">

    <link rel="icon" type="image/png" href="logo.png">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
      .lucide { width: 24px; height: 24px; }
      .w-4 { width: 16px; } .h-4 { height: 16px; }
      .w-5 { width: 20px; } .h-5 { height: 20px; }
      .w-6 { width: 24px; } .h-6 { height: 24px; }
    </style>
</head>
<body class="min-h-screen flex flex-col relative overflow-x-hidden bg-theme-bg">
    <!-- Full Screen Admin Lock Overlay -->
    <?php if (!$isAdmin): ?>
    <div id="admin-lock-overlay" class="fixed inset-0 flex items-center justify-center p-4" style="background-color: rgba(10, 8, 20, 0.98); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); z-index: 999999 !important;">
        <style id="admin-lock-style">
            body { overflow: hidden !important; }
            #lock-passkey-input::placeholder {
                color: rgba(0, 0, 0, 0.4) !important;
            }
        </style>
        <div class="border px-8 py-10 rounded-3xl shadow-2xl relative w-full max-w-md flex flex-col items-center text-center gap-6" style="animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); background-color: rgba(30, 27, 46, 0.98) !important; border: 1px solid rgba(255, 255, 255, 0.15) !important;">
            <div class="w-20 h-20 rounded-full flex items-center justify-center shrink-0" style="background-color: rgba(168, 85, 247, 0.1); border: 1px solid rgba(168, 85, 247, 0.2);">
                <i data-lucide="lock" class="w-10 h-10 text-purple-400 animate-pulse"></i>
            </div>
            <div>
                <h3 class="text-3xl font-extrabold tracking-tight mb-2" style="color: #ffffff !important;">Portal Restricted</h3>
                <p class="text-gray-400 text-sm leading-relaxed px-4" style="color: #cbd5e1 !important;">Please enter the administrator passkey to unlock access to the dashboard.</p>
            </div>
            <div class="w-full space-y-4">
                <input type="password" id="lock-passkey-input" class="w-full rounded-xl px-4 py-3.5 focus:outline-none focus:border-brand text-center tracking-widest placeholder:tracking-normal text-lg" style="background-color: #ffffff !important; border: 1px solid #cbd5e1 !important; color: #000000 !important;" placeholder="Enter Passkey" autofocus>
                <p id="lock-passkey-error" class="text-red-400 text-xs text-center font-bold hidden flex items-center justify-center gap-1">
                    <i data-lucide="alert-circle" class="w-3.5 h-3.5 text-red-400"></i> Access Denied! Invalid passkey.
                </p>
            </div>
            <div class="flex gap-4 w-full pt-2">
                <button onclick="cancelAdminAccess()" class="flex-1 bg-transparent text-white font-medium py-2.5 rounded-full hover:bg-white/5 transition-colors text-sm uppercase tracking-wider cursor-pointer" style="border: 1px solid rgba(255, 255, 255, 0.2); color: #ffffff !important;">
                    Exit Portal
                </button>
                <button onclick="unlockAdminAccess()" class="flex-1 bg-gradient-to-r from-[#7c3aed] to-[#a855f7] hover:from-[#6d28d9] hover:to-[#9333ea] text-white font-bold py-2.5 rounded-full shadow-lg shadow-purple-500/20 uppercase tracking-wider text-sm transition-all transform hover:scale-[1.03] active:scale-95 cursor-pointer" style="color: #ffffff !important;">
                    Unlock
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Navbar -->
    <?php include 'navbar.php'; ?>
    <div class="max-w-7xl mx-auto px-6 pt-32 pb-12 space-y-12 w-full flex-grow relative z-10">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <h1 class="text-4xl font-bold text-white tracking-tight">Admin Dashboard</h1>
                <p class="text-gray-400">Manage Madras & Chennai's ecosystem</p>
            </div>
            <?php if ($isAdmin): ?>
            <div>
                <a href="admin.php?action=logout" class="px-6 py-2.5 rounded-full border border-red-500/30 bg-red-500/10 hover:bg-red-500/20 text-red-400 font-bold uppercase tracking-widest text-[10px] transition-all duration-300 flex items-center gap-2 cursor-pointer shadow-lg shadow-red-500/5">
                    <i data-lucide="log-out" class="w-4 h-4 text-red-400"></i> Logout
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Stats Cards & Quick Actions -->
        <div class="flex flex-wrap items-stretch gap-6">
            <!-- Total Events -->
            <div class="glass-card p-6 border-white/5 flex items-center gap-6 min-w-[280px]" style="min-width: 280px;">
                <div class="p-4 rounded-2xl bg-white/5 text-purple-400">
                    <i data-lucide="calendar" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="text-gray-500 text-xs font-bold uppercase tracking-widest">EVENTS</span>
                    <h3 id="total-events" class="text-2xl font-bold text-white"><?php echo number_format($eventsCount); ?></h3>
                </div>
            </div>

            <!-- Total News -->
            <div class="glass-card p-6 border-white/5 flex items-center gap-6 min-w-[280px]" style="min-width: 280px;">
                <div class="p-4 rounded-2xl bg-white/5 text-blue-400">
                    <i data-lucide="newspaper" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="text-gray-500 text-xs font-bold uppercase tracking-widest">CITY-NEWS</span>
                    <h3 id="total-news" class="text-2xl font-bold text-white"><?php echo number_format($newsCount); ?></h3>
                </div>
            </div>

            <!-- Total Facts -->
            <div class="glass-card p-6 border-white/5 flex items-center gap-6 min-w-[280px]" style="min-width: 280px;">
                <div class="p-4 rounded-2xl bg-white/5 text-yellow-400">
                    <i data-lucide="lightbulb" class="w-6 h-6 animate-pulse"></i>
                </div>
                <div>
                    <span class="text-gray-500 text-xs font-bold uppercase tracking-widest">NEW-IN-CBE</span>
                    <h3 id="total-facts" class="text-2xl font-bold text-white"><?php echo number_format($factsCount); ?></h3>
                </div>
            </div>





            <!-- Auto Fetch Dropdown -->
            <div class="glass-card p-6 border-white/5 flex items-center justify-center min-w-[280px] relative z-20" style="min-width: 280px; z-index: 20;">
                <div class="relative w-full inline-block text-left" id="fetch-dropdown-container">
                    <button onclick="toggleFetchDropdown(event)" id="discoverBtn" class="w-full flex items-center justify-between px-6 py-3 rounded-full fetch-dropdown-btn font-bold uppercase tracking-widest text-[10px] transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed group">
                        <span class="flex items-center gap-2">
                            <span id="discoverBtnIconContainer" class="flex items-center">
                                <i data-lucide="sparkles" class="w-4 h-4"></i>
                            </span>
                            <span id="discoverBtnText">Select AI Fetch</span>
                        </span>
                        <span id="discoverChevronIconContainer" class="flex items-center">
                            <i data-lucide="chevron-down" class="w-4 h-4"></i>
                        </span>
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <div id="fetchDropdownMenu" class="hidden absolute right-0 left-0 mt-2 rounded-2xl shadow-2xl p-2 z-50 transition-all duration-200 fetch-dropdown-menu">
                        <button onclick="selectFetchOption('events')" class="w-full text-left px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider flex items-center gap-2 transition-all fetch-dropdown-item">
                            <i data-lucide="calendar" class="w-4 h-4"></i> Auto-Fetch Chennai Events
                        </button>
                        <button onclick="selectFetchOption('news')" class="w-full text-left px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider flex items-center gap-2 transition-all fetch-dropdown-item">
                            <i data-lucide="newspaper" class="w-4 h-4"></i> Auto-Fetch News Updates
                        </button>
                        <button onclick="selectFetchOption('facts')" class="w-full text-left px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider flex items-center gap-2 transition-all fetch-dropdown-item">
                            <i data-lucide="lightbulb" class="w-4 h-4 animate-pulse"></i> Auto-Fetch New-In-Madras
                        </button>
                        <a href="custompost.php" class="w-full text-left px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider flex items-center gap-2 transition-all fetch-dropdown-item text-purple-600 hover:text-purple-700">
                            <i data-lucide="wand-2" class="w-4 h-4 text-purple-500"></i> Custom AI Post & Image
                        </a>
                    </div>
                </div>
            </div>



            <!-- Auto-Fetch Bot Control -->
            <div class="glass-card p-6 border-white/5 flex items-center justify-between min-w-[280px]" style="min-width: 280px;">
                <div class="flex items-center gap-4">
                    <div id="auto-fetch-status-icon-container" class="p-3.5 rounded-2xl bg-green-500/10 text-green-400 border border-green-500/20">
                        <i data-lucide="sparkles" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <span class="text-gray-500 text-xs font-bold uppercase tracking-widest block">Auto-Fetch Bot</span>
                        <h4 id="auto-fetch-status-text" class="text-lg font-bold text-white mt-0.5">Active</h4>
                    </div>
                </div>
                <div class="flex items-center">
                    <button onclick="toggleAutoFetchStatus()" id="autoFetchToggleBtn" class="px-4 py-2 rounded-full border text-xs font-extrabold uppercase tracking-wider transition-all duration-300 cursor-pointer text-red-400 border-red-500/30 bg-red-500/10 hover:bg-red-500/20">
                        Stop Bot
                    </button>
                </div>
            </div>

            <!-- Quick Add Content -->
            <div class="glass-card p-6 border-white/5 flex flex-col justify-between min-w-[280px]" style="min-width: 280px;">
                <span class="text-gray-500 text-xs font-bold uppercase tracking-widest block mb-3">Quick Add Content</span>
                <div class="flex flex-col gap-2 w-full">
                    <a href="custompost.php" class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-full border border-purple-500/40 bg-purple-500/20 hover:bg-purple-500/30 text-purple-300 font-extrabold uppercase tracking-wider text-[10px] transition-all cursor-pointer text-center shadow-lg shadow-purple-500/10">
                        <i data-lucide="wand-2" class="w-4 h-4 text-purple-400"></i> Custom AI Post & Image
                    </a>
                    <button onclick="openCreateModal('events')" class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-full border border-green-500/30 bg-green-500/10 hover:bg-green-500/20 text-green-400 font-bold uppercase tracking-wider text-[10px] transition-all cursor-pointer">
                        <i data-lucide="calendar" class="w-4 h-4 text-green-400"></i> Add Event
                    </button>
                    <button onclick="openCreateModal('news')" class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-full border border-green-500/30 bg-green-500/10 hover:bg-green-500/20 text-green-400 font-bold uppercase tracking-wider text-[10px] transition-all cursor-pointer">
                        <i data-lucide="newspaper" class="w-4 h-4 text-green-400"></i> Add City-News
                    </button>
                    <button onclick="openCreateModal('facts')" class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-full border border-green-500/30 bg-green-500/10 hover:bg-green-500/20 text-green-400 font-bold uppercase tracking-wider text-[10px] transition-all cursor-pointer">
                        <i data-lucide="lightbulb" class="w-4 h-4 text-green-400"></i> Add New-in-Madras
                    </button>
                    <button onclick="openCreateAnnouncementModal()" class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-full border border-green-500/30 bg-green-500/10 hover:bg-green-500/20 text-green-400 font-bold uppercase tracking-wider text-[10px] transition-all cursor-pointer">
                        <i data-lucide="vote" class="w-4 h-4 text-green-400"></i> New Announcement & Poll
                    </button>
                </div>
            </div>

            <!-- Approvals & Campaigns -->
            <div class="glass-card p-6 border-white/5 flex flex-col justify-between min-w-[280px]" style="min-width: 280px;">
                <span class="text-gray-500 text-xs font-bold uppercase tracking-widest block mb-3">Approvals & Campaigns</span>
                    <a href="manage_profile.php" class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-full border border-amber-500/30 bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 font-bold uppercase tracking-wider text-[10px] transition-all cursor-pointer text-center relative">
                        <i data-lucide="users" class="w-4 h-4 text-amber-400"></i> Manage Profiles
                    </a>
                    <a href="manageAds.php" class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-full border border-green-500/30 bg-green-500/10 hover:bg-green-500/20 text-green-400 font-bold uppercase tracking-wider text-[10px] transition-all cursor-pointer text-center">
                        <i data-lucide="megaphone" class="w-4 h-4 text-green-400"></i> Manage Ads
                    </a>
                    <a href="ads_history.php" class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-full border border-purple-500/30 bg-purple-500/10 hover:bg-purple-500/20 text-purple-400 font-bold uppercase tracking-wider text-[10px] transition-all cursor-pointer text-center relative">
                        <i data-lucide="history" class="w-4 h-4 text-purple-400"></i> Ads History
                    </a>
                    <a href="approve_ads.php" class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-full border border-purple-500/30 bg-purple-500/10 hover:bg-purple-500/20 text-purple-400 font-bold uppercase tracking-wider text-[10px] transition-all cursor-pointer text-center relative">
                        <i data-lucide="shield-check" class="w-4 h-4 text-purple-400"></i> Approve Ads
                        <span id="admin-pending-badge" class="absolute -top-1 -right-1 bg-red-500 text-real-white text-[9px] px-1.5 py-0.5 rounded-full font-bold hidden">0</span>
                    </a>
                    <a href="approve_posts.php" class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-full border border-purple-500/30 bg-purple-500/10 hover:bg-purple-500/20 text-purple-400 font-bold uppercase tracking-wider text-[10px] transition-all cursor-pointer text-center relative">
                        <i data-lucide="file-check" class="w-4 h-4 text-purple-400"></i> Approve Posts
                        <span id="admin-pending-posts-badge" class="absolute -top-1 -right-1 bg-red-500 text-real-white text-[9px] px-1.5 py-0.5 rounded-full font-bold hidden">0</span>
                    </a>
                    <a href="analytics.php" class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-full border border-blue-500/30 bg-blue-500/10 hover:bg-blue-500/20 text-blue-400 font-bold uppercase tracking-wider text-[10px] transition-all cursor-pointer text-center relative">
                        <i data-lucide="bar-chart-3" class="w-4 h-4 text-blue-400"></i> Analytics
                    </a>
                </div>
            </div>

            <!-- Delete All -->
            <div class="glass-card-danger p-6 flex items-center justify-center min-w-[280px]" style="min-width: 280px;">
                <button onclick="promptDeleteAll()" class="flex items-center gap-2 px-6 py-3 rounded-full border border-red-500/30 bg-red-500/10 hover:bg-red-500/20 text-red-700 font-bold uppercase tracking-widest transition-all w-full justify-center group" style="font-size: 10px;">
                    <i data-lucide="trash-2" class="w-4 h-4 text-red-600 group-hover:scale-110 transition-transform"></i> Delete All
                </button>
            </div>
        </div>

        <!-- Button Visibility Controls (Matching Kovai.city UI Color Theme) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 w-full">
            <!-- NAVBAR BUTTONS CONTROL -->
            <div class="glass-card p-6 rounded-3xl relative overflow-hidden flex flex-col justify-between" style="background-color: rgba(255, 255, 255, 0.95) !important; border: 1.5px solid #e2e8f0 !important; box-shadow: 0 10px 30px -10px rgba(15, 23, 42, 0.05) !important;">
                <div>
                    <div class="flex items-center justify-between mb-5 pb-3 border-b border-slate-100">
                        <span class="text-xs font-black uppercase tracking-widest block" style="color: #475569 !important;">NAVBAR BUTTONS CONTROL</span>
                        <span class="text-[10px] bg-purple-100 text-purple-700 border border-purple-200 px-3 py-1 rounded-full font-black uppercase tracking-wider">Live Navbar Toggle</span>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <!-- HOME -->
                        <label class="flex items-center justify-between px-4 py-3 rounded-full border border-slate-200 bg-white hover:border-purple-500 hover:shadow-md transition-all cursor-pointer select-none" style="background-color: #ffffff !important; border: 1.5px solid #cbd5e1 !important;">
                            <span class="flex items-center gap-3 font-extrabold text-xs tracking-wider uppercase" style="color: #0f172a !important;">
                                <i data-lucide="home" class="w-4 h-4" style="color: #7c3aed !important;"></i> HOME
                            </span>
                            <input type="checkbox" id="nav-toggle-home" onchange="toggleButtonVisibility('navbar', 'home', this.checked)" class="w-5 h-5 rounded border-2 border-slate-300 bg-white text-purple-600 focus:ring-0 cursor-pointer accent-purple-600">
                        </label>

                        <!-- EVENTS -->
                        <label class="flex items-center justify-between px-4 py-3 rounded-full border border-slate-200 bg-white hover:border-purple-500 hover:shadow-md transition-all cursor-pointer select-none" style="background-color: #ffffff !important; border: 1.5px solid #cbd5e1 !important;">
                            <span class="flex items-center gap-3 font-extrabold text-xs tracking-wider uppercase" style="color: #0f172a !important;">
                                <i data-lucide="calendar" class="w-4 h-4" style="color: #7c3aed !important;"></i> EVENTS
                            </span>
                            <input type="checkbox" id="nav-toggle-events" onchange="toggleButtonVisibility('navbar', 'events', this.checked)" class="w-5 h-5 rounded border-2 border-slate-300 bg-white text-purple-600 focus:ring-0 cursor-pointer accent-purple-600">
                        </label>

                        <!-- CITY-NEWS -->
                        <label class="flex items-center justify-between px-4 py-3 rounded-full border border-slate-200 bg-white hover:border-purple-500 hover:shadow-md transition-all cursor-pointer select-none" style="background-color: #ffffff !important; border: 1.5px solid #cbd5e1 !important;">
                            <span class="flex items-center gap-3 font-extrabold text-xs tracking-wider uppercase" style="color: #0f172a !important;">
                                <i data-lucide="newspaper" class="w-4 h-4" style="color: #7c3aed !important;"></i> CITY-NEWS
                            </span>
                            <input type="checkbox" id="nav-toggle-city_news" onchange="toggleButtonVisibility('navbar', 'city_news', this.checked)" class="w-5 h-5 rounded border-2 border-slate-300 bg-white text-purple-600 focus:ring-0 cursor-pointer accent-purple-600">
                        </label>

                        <!-- NEW-IN-CHE -->
                        <label class="flex items-center justify-between px-4 py-3 rounded-full border border-slate-200 bg-white hover:border-purple-500 hover:shadow-md transition-all cursor-pointer select-none" style="background-color: #ffffff !important; border: 1.5px solid #cbd5e1 !important;">
                            <span class="flex items-center gap-3 font-extrabold text-xs tracking-wider uppercase" style="color: #0f172a !important;">
                                <i data-lucide="lightbulb" class="w-4 h-4" style="color: #7c3aed !important;"></i> NEW-IN-CHE
                            </span>
                            <input type="checkbox" id="nav-toggle-new_in_che" onchange="toggleButtonVisibility('navbar', 'new_in_che', this.checked)" class="w-5 h-5 rounded border-2 border-slate-300 bg-white text-purple-600 focus:ring-0 cursor-pointer accent-purple-600">
                        </label>

                        <!-- CLASSIFIEDS -->
                        <label class="flex items-center justify-between px-4 py-3 rounded-full border border-slate-200 bg-white hover:border-purple-500 hover:shadow-md transition-all cursor-pointer select-none" style="background-color: #ffffff !important; border: 1.5px solid #cbd5e1 !important;">
                            <span class="flex items-center gap-3 font-extrabold text-xs tracking-wider uppercase" style="color: #0f172a !important;">
                                <i data-lucide="layout-grid" class="w-4 h-4" style="color: #7c3aed !important;"></i> CLASSIFIEDS
                            </span>
                            <input type="checkbox" id="nav-toggle-classifieds" onchange="toggleButtonVisibility('navbar', 'classifieds', this.checked)" class="w-5 h-5 rounded border-2 border-slate-300 bg-white text-purple-600 focus:ring-0 cursor-pointer accent-purple-600">
                        </label>

                        <!-- ABOUT -->
                        <label class="flex items-center justify-between px-4 py-3 rounded-full border border-slate-200 bg-white hover:border-purple-500 hover:shadow-md transition-all cursor-pointer select-none" style="background-color: #ffffff !important; border: 1.5px solid #cbd5e1 !important;">
                            <span class="flex items-center gap-3 font-extrabold text-xs tracking-wider uppercase" style="color: #0f172a !important;">
                                <i data-lucide="info" class="w-4 h-4" style="color: #7c3aed !important;"></i> ABOUT
                            </span>
                            <input type="checkbox" id="nav-toggle-about" onchange="toggleButtonVisibility('navbar', 'about', this.checked)" class="w-5 h-5 rounded border-2 border-slate-300 bg-white text-purple-600 focus:ring-0 cursor-pointer accent-purple-600">
                        </label>
                    </div>
                </div>
            </div>

            <!-- EXPLORE BUTTONS CONTROL -->
            <div class="glass-card p-6 rounded-3xl relative overflow-hidden flex flex-col justify-between" style="background-color: rgba(255, 255, 255, 0.95) !important; border: 1.5px solid #e2e8f0 !important; box-shadow: 0 10px 30px -10px rgba(15, 23, 42, 0.05) !important;">
                <div>
                    <div class="flex items-center justify-between mb-5 pb-3 border-b border-slate-100">
                        <span class="text-xs font-black uppercase tracking-widest block" style="color: #475569 !important;">EXPLORE BUTTONS CONTROL</span>
                        <span class="text-[10px] bg-purple-100 text-purple-700 border border-purple-200 px-3 py-1 rounded-full font-black uppercase tracking-wider">Homepage Hero Section</span>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <!-- EXPLORE EVENTS -->
                        <label class="flex items-center justify-between px-4 py-3 rounded-full border border-slate-200 bg-white hover:border-purple-500 hover:shadow-md transition-all cursor-pointer select-none" style="background-color: #ffffff !important; border: 1.5px solid #cbd5e1 !important;">
                            <span class="flex items-center gap-3 font-extrabold text-xs tracking-wider uppercase" style="color: #0f172a !important;">
                                <i data-lucide="calendar" class="w-4 h-4" style="color: #7c3aed !important;"></i> EXPLORE EVENTS
                            </span>
                            <input type="checkbox" id="explore-toggle-events" onchange="toggleButtonVisibility('explore', 'events', this.checked)" class="w-5 h-5 rounded border-2 border-slate-300 bg-white text-purple-600 focus:ring-0 cursor-pointer accent-purple-600">
                        </label>

                        <!-- EXPLORE NEWS UPDATES -->
                        <label class="flex items-center justify-between px-4 py-3 rounded-full border border-slate-200 bg-white hover:border-purple-500 hover:shadow-md transition-all cursor-pointer select-none" style="background-color: #ffffff !important; border: 1.5px solid #cbd5e1 !important;">
                            <span class="flex items-center gap-3 font-extrabold text-xs tracking-wider uppercase" style="color: #0f172a !important;">
                                <i data-lucide="newspaper" class="w-4 h-4" style="color: #7c3aed !important;"></i> EXPLORE NEWS UPDATES
                            </span>
                            <input type="checkbox" id="explore-toggle-city_news" onchange="toggleButtonVisibility('explore', 'city_news', this.checked)" class="w-5 h-5 rounded border-2 border-slate-300 bg-white text-purple-600 focus:ring-0 cursor-pointer accent-purple-600">
                        </label>

                        <!-- EXPLORE NEW-IN-CHE -->
                        <label class="flex items-center justify-between px-4 py-3 rounded-full border border-slate-200 bg-white hover:border-purple-500 hover:shadow-md transition-all cursor-pointer select-none" style="background-color: #ffffff !important; border: 1.5px solid #cbd5e1 !important;">
                            <span class="flex items-center gap-3 font-extrabold text-xs tracking-wider uppercase" style="color: #0f172a !important;">
                                <i data-lucide="lightbulb" class="w-4 h-4" style="color: #7c3aed !important;"></i> EXPLORE NEW-IN-CHE
                            </span>
                            <input type="checkbox" id="explore-toggle-new_in_che" onchange="toggleButtonVisibility('explore', 'new_in_che', this.checked)" class="w-5 h-5 rounded border-2 border-slate-300 bg-white text-purple-600 focus:ring-0 cursor-pointer accent-purple-600">
                        </label>

                        <!-- EXPLORE CLASSIFIEDS -->
                        <label class="flex items-center justify-between px-4 py-3 rounded-full border border-slate-200 bg-white hover:border-purple-500 hover:shadow-md transition-all cursor-pointer select-none" style="background-color: #ffffff !important; border: 1.5px solid #cbd5e1 !important;">
                            <span class="flex items-center gap-3 font-extrabold text-xs tracking-wider uppercase" style="color: #0f172a !important;">
                                <i data-lucide="layout-grid" class="w-4 h-4" style="color: #7c3aed !important;"></i> EXPLORE CLASSIFIEDS
                            </span>
                            <input type="checkbox" id="explore-toggle-classifieds" onchange="toggleButtonVisibility('explore', 'classifieds', this.checked)" class="w-5 h-5 rounded border-2 border-slate-300 bg-white text-purple-600 focus:ring-0 cursor-pointer accent-purple-600">
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Table Area -->
        <div class="glass-card overflow-hidden border-white/10">
            <div class="p-6 border-b border-white/5 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white/5">
                <div class="flex flex-wrap items-center gap-4">
                    <!-- Sleek segment tab switcher styled matching the navbar -->
                    <div class="flex items-center gap-4 text-xs font-bold tracking-wider">
                        <button onclick="switchTab('events')" id="tab-events-btn" class="transition-all hover:text-purple-500 uppercase text-purple-500 font-bold">
                            EVENTS
                        </button>
                        <span class="text-gray-300 select-none">|</span>
                        <button onclick="switchTab('news')" id="tab-news-btn" class="transition-all hover:text-purple-500 uppercase text-gray-500 font-bold">
                            CITY-NEWS
                        </button>
                        <span class="text-gray-300 select-none">|</span>
                        <button onclick="switchTab('facts')" id="tab-facts-btn" class="transition-all hover:text-purple-500 uppercase text-gray-500 font-bold">
                            NEW-IN-CBE
                        </button>
                        <span class="text-gray-300 select-none">|</span>
                        <button onclick="switchTab('announcements')" id="tab-announcements-btn" class="transition-all hover:text-purple-500 uppercase text-gray-500 font-bold">
                            ANNOUNCEMENTS & POLLS
                        </button>
                    </div>

                    <button id="delete-selected-btn" onclick="promptDeleteSelected()" class="hidden items-center gap-2 px-4 py-1.5 rounded-full bg-red-500/20 hover:bg-red-500/30 border border-red-500/30 text-red-700 font-bold uppercase tracking-wider text-xs transition-all transform active:scale-95 shadow-md shadow-red-500/10 cursor-pointer">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5 text-red-600"></i> Delete Selected (<span id="selected-count">0</span>)
                    </button>
                </div>
                <div class="flex items-center gap-4">
                    <div id="category-filter-container" class="flex items-center gap-2 border border-slate-200 rounded-full px-4 py-1.5 bg-white/80 hover:border-slate-300 transition-all text-xs font-semibold text-gray-700">
                        <i data-lucide="filter" class="w-4 h-4 text-brand"></i>
                        <span class="text-gray-500 font-medium">Category:</span>
                        <select id="category-filter" onchange="loadDashboard()" class="bg-transparent text-gray-800 focus:outline-none font-bold cursor-pointer pr-2 select-custom">
                            <option value="All">All Categories</option>
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
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-white/5 uppercase font-bold text-gray-500 tracking-widest bg-white/5 px-6" style="font-size: 10px;">
                            <th class="px-6 py-4 w-12 text-center">
                                <input type="checkbox" id="select-all-checkbox" onchange="toggleSelectAll(this)" class="rounded border-gray-700 bg-gray-900 text-purple-600 focus:ring-purple-500 cursor-pointer w-4 h-4">
                            </th>
                            <th class="px-6 py-4" id="col-header-details">Event Details</th>
                            <th class="px-6 py-4" id="col-header-category">Category</th>
                            <th class="px-6 py-4" id="col-header-status">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="events-table-body" class="divide-y divide-white/5">
                        <!-- Populated via JS -->
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Activity Panel -->
        <div class="grid grid-cols-1 gap-8">
            <div class="glass-card p-8 border-white/5">
                <h3 class="text-xl font-bold text-white mb-6">Recent Activity</h3>
                <div id="recent-activity-container" class="space-y-6">
                    <div class="text-gray-500 text-sm italic">
                        No recent activity found.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Modal (Hidden) -->
    <div id="notification-modal" class="fixed inset-0 flex items-center justify-center p-4 hidden" style="z-index: 99990 !important;">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeNotificationModal()"></div>
        <div class="glass-card bg-[#1e1b2e] border border-white/10 px-8 py-8 rounded-3xl shadow-2xl relative w-full max-w-md flex flex-col items-center text-center gap-6 z-10" style="animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); background-color: rgba(30, 27, 46, 0.95);">
            <div id="notification-icon-container" class="w-20 h-20 rounded-full flex items-center justify-center shrink-0 animate-bounce relative">
                <!-- Glowing backing -->
                <div class="absolute inset-0 rounded-full blur-xl bg-purple-500/30 opacity-70"></div>
                <div class="w-16 h-16 rounded-full bg-gradient-to-tr from-purple-600 to-purple-400 flex items-center justify-center border border-purple-300/20 shadow-lg relative z-10">
                    <i id="notification-icon" data-lucide="check-circle" class="w-8 h-8 text-white"></i>
                </div>
            </div>
            <div>
                <h3 id="notification-title" class="text-2xl font-bold text-white tracking-tight mb-2">Success!</h3>
                <p id="notification-message" class="text-gray-300 leading-relaxed font-medium">Successfully fetched new Chennai events!</p>
            </div>
            <button onclick="closeNotificationModal()" class="w-full bg-gradient-to-r from-[#7c3aed] to-[#a855f7] hover:from-[#6d28d9] hover:to-[#9333ea] text-white font-bold py-3 px-8 rounded-full shadow-lg shadow-purple-500/20 uppercase tracking-wider text-sm transition-all transform hover:scale-[1.03] active:scale-95">
                Awesome
            </button>
        </div>
    </div>

    <!-- Custom AI Prompt & Image Generator Modal -->
    <div id="custom-ai-modal" class="fixed inset-0 flex items-center justify-center p-4 hidden" style="z-index: 99988 !important;">
        <div class="absolute inset-0 bg-black/75 backdrop-blur-md" onclick="closeCustomAiModal()"></div>
        <div class="glass-card bg-white border border-slate-200 p-8 rounded-3xl shadow-2xl relative w-full max-w-xl flex flex-col gap-6 z-10" style="animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); background-color: #ffffff !important;">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-purple-500/10 text-purple-600 flex items-center justify-center font-bold shrink-0 border border-purple-500/20">
                        <i data-lucide="wand-2" class="w-5 h-5 text-purple-600"></i>
                    </div>
                    <div class="text-left">
                        <h3 class="text-xl font-extrabold text-slate-900" style="color: #0f172a !important;">Custom AI Post & Image Generator</h3>
                        <p class="text-xs text-slate-500 font-semibold mt-0.5" style="color: #64748b !important;">Enter any custom command or topic to generate post & image</p>
                    </div>
                </div>
                <button onclick="closeCustomAiModal()" class="text-slate-400 hover:text-slate-700 transition-colors p-1.5 rounded-full hover:bg-slate-100 cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Form -->
            <form id="custom-ai-form" onsubmit="submitCustomAiPrompt(event)" class="space-y-4 text-left">
                <!-- Select Post Type -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: #64748b !important;">Select Content Type <span class="text-red-500">*</span></label>
                    <select id="customAiPostType" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-900 focus:ring-2 focus:ring-purple-500 bg-slate-50">
                        <option value="events">Event Post</option>
                        <option value="news">City-News Update</option>
                        <option value="facts">New-In-Madras Spotlight / Fact</option>
                    </select>
                </div>

                <!-- Custom Command Input -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: #64748b !important;">Custom Prompt Command <span class="text-red-500">*</span></label>
                    <textarea id="customAiPromptInput" rows="4" class="w-full rounded-2xl p-4 text-sm focus:outline-none resize-none transition-all" style="background-color: #f8fafc !important; border: 1.5px solid #cbd5e1 !important; color: #0f172a !important; font-weight: 500;" placeholder="e.g. Search for upcoming music concerts in Chennai this weekend and generate an event post with image..."></textarea>
                </div>

                <!-- Preset Quick Buttons -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider mb-2" style="color: #94a3b8 !important;">Or Click Quick Sample Ideas:</label>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" onclick="setAiPrompt('Search for new food festivals and food trucks in Chennai and generate a post')" class="text-xs px-3.5 py-2 rounded-full transition-all cursor-pointer font-semibold" style="background-color: #f1f5f9 !important; border: 1px solid #cbd5e1 !important; color: #0f172a !important;">
                            🍔 Chennai Food Fest
                        </button>
                        <button type="button" onclick="setAiPrompt('Find latest Chennai Metro rail construction status and generate a news update')" class="text-xs px-3.5 py-2 rounded-full transition-all cursor-pointer font-semibold" style="background-color: #f1f5f9 !important; border: 1px solid #cbd5e1 !important; color: #0f172a !important;">
                            🚆 Metro Construction Update
                        </button>
                        <button type="button" onclick="setAiPrompt('Discover new tourist spots or heritage places opening near Chennai')" class="text-xs px-3.5 py-2 rounded-full transition-all cursor-pointer font-semibold" style="background-color: #f1f5f9 !important; border: 1px solid #cbd5e1 !important; color: #0f172a !important;">
                            🏖️ Tourist / Heritage Spot
                        </button>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeCustomAiModal()" class="px-6 py-2.5 rounded-full border border-slate-200 text-slate-600 font-bold uppercase tracking-wider text-xs hover:bg-slate-50 transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" id="submitCustomAiBtn" class="px-8 py-2.5 rounded-full bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-extrabold uppercase tracking-wider text-xs shadow-md shadow-purple-500/20 border border-purple-500/50 cursor-pointer flex items-center gap-1.5">
                        <i data-lucide="sparkles" class="w-4 h-4 text-white"></i> Generate Content
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add User Credits Modal (Admin Only) -->
    <div id="add-user-credits-modal" class="fixed inset-0 flex items-center justify-center p-4 hidden" style="z-index: 99985 !important;">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="closeAddCreditsModal()"></div>
        <div class="glass-card bg-white border border-slate-200 p-8 rounded-3xl shadow-2xl relative w-full max-w-lg flex flex-col gap-6 z-10" style="animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); background-color: #ffffff !important;">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-amber-400/20 text-amber-600 flex items-center justify-center font-bold shrink-0">
                        <i data-lucide="zap" class="w-5 h-5 text-amber-500"></i>
                    </div>
                    <div class="text-left">
                        <h3 class="text-xl font-extrabold text-slate-900" style="color: #0f172a !important;">Add User Credits</h3>
                        <p class="text-xs text-slate-500 font-semibold mt-0.5" style="color: #64748b !important;">Grant advertising credits to a specific user</p>
                    </div>
                </div>
                <button onclick="closeAddCreditsModal()" class="text-slate-400 hover:text-slate-700 transition-colors p-1.5 rounded-full hover:bg-slate-100 cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Form -->
            <form id="admin-add-credits-form" onsubmit="submitAddUserCredits(event)" class="space-y-4 text-left">
                <!-- Select Registered User (Optional Quick Select) -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: #64748b !important;">Select Registered User (Optional)</label>
                    <select id="quickSelectUser" onchange="onQuickUserSelect(this)" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-900 focus:ring-2 focus:ring-amber-400 bg-slate-50">
                        <option value="">-- Choose User or Enter Details Below --</option>
                    </select>
                </div>

                <!-- User Name -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: #64748b !important;">User Name <span class="text-red-500">*</span></label>
                    <input type="text" id="creditUserName" required class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-900 focus:ring-2 focus:ring-amber-400" placeholder="e.g. Ak or Niranjan">
                </div>

                <!-- Mail ID -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: #64748b !important;">Mail ID / Email Address <span class="text-red-500">*</span></label>
                    <input type="email" id="creditUserEmail" required class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-900 focus:ring-2 focus:ring-amber-400" placeholder="e.g. kanthisback@gmail.com">
                </div>

                <!-- Credits Amount -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: #64748b !important;">Credits to Add <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="number" id="creditAmountToAdd" min="1" step="1" required class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-xs font-extrabold text-slate-900 focus:ring-2 focus:ring-amber-400" placeholder="e.g. 10000">
                        <span class="absolute right-4 top-2.5 text-xs font-extrabold text-amber-600 uppercase">Credits</span>
                    </div>
                </div>

                <!-- User Balance Preview -->
                <div id="creditUserBalanceInfo" class="hidden p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs font-semibold text-amber-800 flex items-center justify-between">
                    <span>Current User Balance:</span>
                    <strong id="creditUserBalanceVal" class="text-sm font-extrabold text-amber-900">0 Credits</strong>
                </div>

                <!-- Action Buttons -->
                <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeAddCreditsModal()" class="px-6 py-2.5 rounded-full border border-slate-200 text-slate-600 font-bold uppercase tracking-wider text-xs hover:bg-slate-50 transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" id="submitAddCreditsBtn" class="px-8 py-2.5 rounded-full bg-gradient-to-r from-amber-500 to-yellow-400 hover:from-amber-600 hover:to-yellow-500 text-white font-extrabold uppercase tracking-wider text-xs shadow-md shadow-amber-500/20 border border-amber-500/50 cursor-pointer flex items-center gap-1.5" style="background-color: #f59e0b !important; color: #ffffff !important;">
                        <i data-lucide="zap" class="w-4 h-4 text-white"></i> Add Credits
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div id="delete-modal" class="fixed inset-0 flex items-center justify-center p-4 hidden" style="z-index: 99980 !important;">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeDeleteModal()"></div>
        <div class="glass-card-danger px-8 py-6 rounded-2xl shadow-2xl relative w-full max-w-sm flex flex-col items-center text-center gap-4 z-10" style="animation: scaleIn 0.2s ease-out;">
            <div class="w-12 h-12 rounded-full bg-red-500/10 flex items-center justify-center shrink-0 mb-2">
                <i data-lucide="alert-circle" class="w-6 h-6 text-red-600"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Delete Item</h3>
                <p class="text-gray-600 text-sm leading-relaxed">Are you sure you want to delete this item?</p>
            </div>
            <div class="flex gap-4 w-full mt-4">
                <button onclick="confirmDelete()" class="flex-1 bg-red-600 text-white font-medium py-2.5 rounded-lg hover:bg-red-700 transition-colors" style="color: #ffffff !important;">
                    OK
                </button>
                <button onclick="closeDeleteModal()" class="flex-1 bg-transparent border border-gray-300 text-gray-700 font-medium py-2.5 rounded-lg hover:bg-gray-55 transition-colors" style="color: #475569 !important; border-color: #cbd5e1 !important;">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Selected Modal (Hidden) -->
    <div id="delete-selected-modal" class="fixed inset-0 flex items-center justify-center p-4 hidden" style="z-index: 99982 !important;">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeDeleteSelectedModal()"></div>
        <div class="glass-card-danger px-8 py-6 rounded-2xl shadow-2xl relative w-full max-w-sm flex flex-col items-center text-center gap-4 z-10" style="animation: scaleIn 0.2s ease-out;">
            <div class="w-12 h-12 rounded-full bg-red-500/10 flex items-center justify-center shrink-0 mb-2">
                <i data-lucide="alert-circle" class="w-6 h-6 text-red-600"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Delete Selected Items</h3>
                <p class="text-gray-600 text-sm leading-relaxed">Are you sure you want to delete <span id="delete-selected-modal-count" class="font-bold text-red-600">0</span> selected items?</p>
            </div>
            <div class="flex gap-4 w-full mt-4">
                <button onclick="confirmDeleteSelected()" class="flex-1 bg-red-600 text-white font-medium py-2.5 rounded-lg hover:bg-red-700 transition-colors" style="color: #ffffff !important;">
                    OK
                </button>
                <button onclick="closeDeleteSelectedModal()" class="flex-1 bg-transparent border border-gray-300 text-gray-700 font-medium py-2.5 rounded-lg hover:bg-gray-55 transition-colors" style="color: #475569 !important; border-color: #cbd5e1 !important;">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <!-- Delete All Modal (Hidden) -->
    <div id="delete-all-modal" class="fixed inset-0 flex items-center justify-center p-4 hidden" style="z-index: 99985 !important;">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeDeleteAllModal()"></div>
        <div class="glass-card-danger px-8 py-8 rounded-3xl shadow-2xl relative w-full max-w-md flex flex-col items-center text-center gap-6 z-10" style="animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">
            <div class="w-16 h-16 rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center shrink-0 animate-pulse">
                <i data-lucide="trash-2" class="w-8 h-8 text-red-600"></i>
            </div>
            <div>
                <h3 class="text-2xl font-bold text-gray-900 tracking-tight mb-2">Delete All Database Content</h3>
                <p class="text-red-800 text-sm leading-relaxed px-4">Warning: This action is irreversible. Are you sure you want to delete all events, news updates, and New-In-Cbe facts from the database?</p>
            </div>
            <div class="flex gap-4 w-full pt-2">
                <button onclick="confirmDeleteAll()" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-full shadow-lg shadow-red-500/20 uppercase tracking-wider text-xs transition-all transform hover:scale-[1.03] active:scale-95 cursor-pointer" style="color: #ffffff !important;">
                    Yes, Delete All
                </button>
                <button onclick="closeDeleteAllModal()" class="flex-1 bg-transparent border border-gray-300 text-gray-700 font-medium py-3 rounded-full hover:bg-gray-55 transition-colors uppercase tracking-wider text-xs cursor-pointer" style="color: #475569 !important; border-color: #cbd5e1 !important;">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <!-- Custom AI Prompt Input Modal (Hidden) -->
    <div id="custom-ai-modal" class="fixed inset-0 flex items-center justify-center p-4 hidden" style="z-index: 99975 !important;">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeCustomAiModal()"></div>
        <div class="w-full max-w-2xl rounded-3xl overflow-hidden shadow-2xl relative flex flex-col z-10" style="background-color: #ffffff !important; border: 1px solid rgba(15, 23, 42, 0.15) !important; animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">
            <!-- Header -->
            <div class="p-6 flex items-center justify-between shadow-sm" style="background: linear-gradient(135deg, #f8fafc, #f1f5f9) !important; border-bottom: 1px solid #e2e8f0 !important;">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 rounded-2xl flex items-center justify-center" style="background-color: rgba(124, 58, 237, 0.1) !important; border: 1px solid rgba(124, 58, 237, 0.2) !important;">
                        <i data-lucide="wand-2" class="w-6 h-6" style="color: #7c3aed !important;"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-extrabold tracking-tight" style="color: #0f172a !important;">Generate Custom AI Post & Image</h2>
                        <p class="text-xs mt-0.5" style="color: #475569 !important;">Enter a command or prompt to search live search engine data and generate a post with AI image</p>
                    </div>
                </div>
                <button type="button" onclick="closeCustomAiModal()" class="transition-colors p-2 rounded-full hover:bg-slate-200 cursor-pointer" style="color: #475569 !important;">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-5" style="background-color: #ffffff !important;">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-2 flex items-center gap-2" style="color: #0f172a !important;">
                        <i data-lucide="terminal" class="w-4 h-4" style="color: #7c3aed !important;"></i> Enter Your Command / Search Prompt <span style="color: #7c3aed !important;">*</span>
                    </label>
                    <textarea id="customAiPromptInput" rows="4" class="w-full rounded-2xl p-4 text-sm focus:outline-none resize-none transition-all" style="background-color: #f8fafc !important; border: 1.5px solid #cbd5e1 !important; color: #0f172a !important; font-weight: 500;" placeholder="e.g. Search for upcoming music concerts in Coimbatore this weekend and generate an event post with image..."></textarea>
                </div>

                <!-- Suggestions pills -->
                <div>
                    <span class="text-xs font-bold block mb-2" style="color: #475569 !important;">Quick Prompt Suggestions:</span>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" onclick="setAiPrompt('Search for new food festivals and food trucks in Coimbatore and generate a post')" class="text-xs px-3.5 py-2 rounded-full transition-all cursor-pointer font-semibold" style="background-color: #f1f5f9 !important; border: 1px solid #cbd5e1 !important; color: #0f172a !important;">
                            🍔 Food Festivals
                        </button>
                        <button type="button" onclick="setAiPrompt('Find latest Coimbatore Metro rail construction status and generate a news update')" class="text-xs px-3.5 py-2 rounded-full transition-all cursor-pointer font-semibold" style="background-color: #f1f5f9 !important; border: 1px solid #cbd5e1 !important; color: #0f172a !important;">
                            🚆 Metro Rail News
                        </button>
                        <button type="button" onclick="setAiPrompt('Search for upcoming tech hackathons and startup meetups in Kovai')" class="text-xs px-3.5 py-2 rounded-full transition-all cursor-pointer font-semibold" style="background-color: #f1f5f9 !important; border: 1px solid #cbd5e1 !important; color: #0f172a !important;">
                            💻 Tech Meetups
                        </button>
                        <button type="button" onclick="setAiPrompt('Discover new tourist spots or heritage places opening near Coimbatore')" class="text-xs px-3.5 py-2 rounded-full transition-all cursor-pointer font-semibold" style="background-color: #f1f5f9 !important; border: 1px solid #cbd5e1 !important; color: #0f172a !important;">
                            🌿 New Spots in CBE
                        </button>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-6 flex items-center justify-end gap-3" style="background-color: #f8fafc !important; border-top: 1px solid #e2e8f0 !important;">
                <button type="button" onclick="closeCustomAiModal()" class="px-5 py-2.5 rounded-full text-xs font-bold uppercase tracking-wider transition-all cursor-pointer" style="background-color: #ffffff !important; border: 1px solid #cbd5e1 !important; color: #475569 !important;">
                    Cancel
                </button>
                <button type="button" onclick="generateCustomAiPost()" id="customAiGenerateBtn" class="px-6 py-2.5 rounded-full text-xs font-extrabold uppercase tracking-wider transition-all flex items-center gap-2 cursor-pointer shadow-lg" style="background: linear-gradient(135deg, #7c3aed, #9333ea) !important; color: #ffffff !important;">
                    <i data-lucide="sparkles" class="w-4 h-4" style="color: #ffffff !important;"></i> Generate Preview
                </button>
            </div>
        </div>
    </div>

    <!-- Custom AI Preview & Approval Modal (Hidden) -->
    <div id="custom-ai-preview-modal" class="fixed inset-0 flex items-center justify-center p-4 hidden" style="z-index: 99980 !important;">
        <div class="absolute inset-0 bg-black/85 backdrop-blur-md" onclick="closeCustomAiPreviewModal()"></div>
        <div class="w-full max-w-4xl rounded-3xl overflow-hidden shadow-2xl relative flex flex-col z-10" style="max-height: calc(100vh - 40px); background-color: #ffffff !important; border: 1px solid rgba(15, 23, 42, 0.15) !important; animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">
            <!-- Header -->
            <div class="p-6 flex items-center justify-between shadow-sm" style="background: linear-gradient(135deg, #f8fafc, #f1f5f9) !important; border-bottom: 1px solid #e2e8f0 !important;">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 rounded-2xl flex items-center justify-center" style="background-color: rgba(34, 197, 94, 0.1) !important; border: 1px solid rgba(34, 197, 94, 0.2) !important;">
                        <i data-lucide="eye" class="w-6 h-6" style="color: #16a34a !important;"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-extrabold tracking-tight" style="color: #0f172a !important;">AI Post Preview & Approval</h2>
                        <p class="text-xs mt-0.5" style="color: #475569 !important;">Review and make corrections to the generated content before posting to the home page</p>
                    </div>
                </div>
                <button type="button" onclick="closeCustomAiPreviewModal()" class="transition-colors p-2 rounded-full hover:bg-slate-200 cursor-pointer" style="color: #475569 !important;">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Scrollable Body with Card Preview & Form Fields -->
            <div class="flex-grow overflow-y-auto p-6 space-y-6" style="background-color: #ffffff !important;">
                <!-- Visual Banner & Card Preview -->
                <div class="rounded-2xl p-4 space-y-4" style="background-color: #f8fafc !important; border: 1px solid #e2e8f0 !important;">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-widest flex items-center gap-1.5" style="color: #7c3aed !important;">
                            <i data-lucide="sparkles" class="w-3.5 h-3.5" style="color: #7c3aed !important;"></i> Generated Post Card Preview
                        </span>
                        <span id="previewPostTypeBadge" class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider" style="background-color: rgba(124, 58, 237, 0.15) !important; color: #6d28d9 !important; border: 1px solid rgba(124, 58, 237, 0.3) !important;">
                            Event
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                        <div class="md:col-span-5 relative group">
                            <img id="previewImageDisplay" src="" alt="AI Generated Image" class="w-full h-48 object-cover rounded-xl shadow-md" style="border: 1px solid #cbd5e1 !important;">
                            <div class="mt-2 flex items-center justify-between text-[11px]" style="color: #64748b !important;">
                                <span>Image Source: AI / Search Engine</span>
                                <button type="button" onclick="regenerateAiImage()" class="font-bold flex items-center gap-1 cursor-pointer hover:underline" style="color: #7c3aed !important;">
                                    <i data-lucide="refresh-cw" class="w-3 h-3" style="color: #7c3aed !important;"></i> Regenerate Image
                                </button>
                            </div>
                        </div>
                        <div class="md:col-span-7 space-y-2">
                            <h3 id="previewTitleDisplay" class="text-lg font-extrabold leading-snug" style="color: #0f172a !important;">Title Here</h3>
                            <p id="previewDescriptionDisplay" class="text-xs leading-relaxed line-clamp-3" style="color: #334155 !important;">Description here...</p>
                            <div class="flex flex-wrap items-center gap-2 pt-2 text-[11px]">
                                <span id="previewCategoryBadge" class="px-2.5 py-1 rounded-md font-semibold" style="background-color: #e2e8f0 !important; color: #0f172a !important;">Category</span>
                                <span id="previewDateBadge" class="px-2.5 py-1 rounded-md font-semibold" style="background-color: #e2e8f0 !important; color: #0f172a !important;">Date</span>
                                <span id="previewVenueBadge" class="px-2.5 py-1 rounded-md font-semibold" style="background-color: #e2e8f0 !important; color: #0f172a !important;">Venue</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Editable Fields -->
                <form id="customAiPreviewForm" class="space-y-4" onsubmit="event.preventDefault(); publishCustomAiPost();">
                    <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                        <!-- Post Target Destination Selector -->
                        <div class="md:col-span-3">
                            <label class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: #0f172a !important;">Post Destination Page <span style="color: #7c3aed !important;">*</span></label>
                            <select id="editPostType" onchange="onPreviewPostTypeChange()" class="w-full rounded-xl px-3.5 py-2 text-xs font-extrabold focus:outline-none cursor-pointer" style="background-color: #ffffff !important; border: 1.5px solid #7c3aed !important; color: #0f172a !important;">
                                <option value="events">📅 Events (events.php)</option>
                                <option value="news">📰 City-News (city_news.php)</option>
                                <option value="facts">💡 New-In-Che (new_in_che.php)</option>
                            </select>
                        </div>

                        <!-- Category -->
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: #0f172a !important;">Category</label>
                            <input type="text" id="editCategory" oninput="updatePreviewDisplayUI()" class="w-full rounded-xl px-3 py-2 text-xs focus:outline-none" style="background-color: #ffffff !important; border: 1px solid #cbd5e1 !important; color: #0f172a !important;" placeholder="e.g. Food, Networking, Metro">
                        </div>

                        <!-- Date -->
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: #0f172a !important;">Date</label>
                            <input type="date" id="editDate" onchange="updatePreviewDisplayUI()" class="w-full rounded-xl px-3 py-2 text-xs focus:outline-none" style="background-color: #ffffff !important; border: 1px solid #cbd5e1 !important; color: #0f172a !important;">
                        </div>

                        <!-- Title -->
                        <div class="md:col-span-6">
                            <label class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: #0f172a !important;">Title / Headline <span style="color: #7c3aed !important;">*</span></label>
                            <input type="text" id="editTitle" required oninput="updatePreviewDisplayUI()" class="w-full rounded-xl px-3.5 py-2 text-sm font-semibold focus:outline-none" style="background-color: #ffffff !important; border: 1px solid #cbd5e1 !important; color: #0f172a !important;" placeholder="Post title">
                        </div>

                        <!-- Description / Content -->
                        <div class="md:col-span-6">
                            <label class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: #0f172a !important;">Description / Content <span style="color: #7c3aed !important;">*</span></label>
                            <textarea id="editDescription" rows="4" required oninput="updatePreviewDisplayUI()" class="w-full rounded-xl p-3 text-xs focus:outline-none resize-none" style="background-color: #ffffff !important; border: 1px solid #cbd5e1 !important; color: #0f172a !important;" placeholder="Detailed content"></textarea>
                        </div>

                        <!-- Dynamic Fields for Event (Venue, Organizer, Time, Price, Reg URL) -->
                        <div id="eventFieldsGroup" class="md:col-span-6 grid grid-cols-1 md:grid-cols-6 gap-4">
                            <div class="md:col-span-3">
                                <label class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: #0f172a !important;">Venue / Location</label>
                                <input type="text" id="editVenue" oninput="updatePreviewDisplayUI()" class="w-full rounded-xl px-3 py-2 text-xs focus:outline-none" style="background-color: #ffffff !important; border: 1px solid #cbd5e1 !important; color: #0f172a !important;" placeholder="e.g. CODISSIA Complex">
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: #0f172a !important;">Organizer</label>
                                <input type="text" id="editOrganizer" class="w-full rounded-xl px-3 py-2 text-xs focus:outline-none" style="background-color: #ffffff !important; border: 1px solid #cbd5e1 !important; color: #0f172a !important;" placeholder="e.g. Kovai Tech Hub">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: #0f172a !important;">Event Time</label>
                                <input type="text" id="editTime" class="w-full rounded-xl px-3 py-2 text-xs focus:outline-none" style="background-color: #ffffff !important; border: 1px solid #cbd5e1 !important; color: #0f172a !important;" placeholder="e.g. 10:00 AM">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: #0f172a !important;">Price</label>
                                <input type="text" id="editPrice" class="w-full rounded-xl px-3 py-2 text-xs focus:outline-none" style="background-color: #ffffff !important; border: 1px solid #cbd5e1 !important; color: #0f172a !important;" placeholder="e.g. Free or ₹200">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: #0f172a !important;">Registration / Source URL</label>
                                <input type="url" id="editUrl" class="w-full rounded-xl px-3 py-2 text-xs focus:outline-none" style="background-color: #ffffff !important; border: 1px solid #cbd5e1 !important; color: #0f172a !important;" placeholder="https://...">
                            </div>
                        </div>

                        <!-- Image URL Field -->
                        <div class="md:col-span-6">
                            <label class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: #0f172a !important;">Image URL</label>
                            <div class="flex gap-2">
                                <input type="url" id="editImageUrl" oninput="updatePreviewImageFromInput()" class="flex-grow rounded-xl px-3.5 py-2 text-xs focus:outline-none" style="background-color: #ffffff !important; border: 1px solid #cbd5e1 !important; color: #0f172a !important;" placeholder="https://image.pollinations.ai/prompt/...">
                                <button type="button" onclick="updatePreviewImageFromInput()" class="px-4 py-2 rounded-xl text-xs font-bold uppercase transition-all cursor-pointer" style="background-color: #f1f5f9 !important; border: 1px solid #cbd5e1 !important; color: #0f172a !important;">
                                    Refresh Image
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Footer with Approve/Post & Reject Buttons -->
            <div class="p-6 flex flex-col sm:flex-row items-center justify-between gap-4" style="background-color: #f8fafc !important; border-top: 1px solid #e2e8f0 !important;">
                <div class="flex items-center gap-2">
                    <button type="button" onclick="closeCustomAiPreviewModal()" class="px-6 py-2.5 rounded-full text-xs font-extrabold uppercase tracking-wider transition-all cursor-pointer flex items-center gap-1.5" style="background-color: #fef2f2 !important; border: 1px solid #fca5a5 !important; color: #dc2626 !important;">
                        <i data-lucide="x-circle" class="w-4 h-4" style="color: #dc2626 !important;"></i> Reject & Discard
                    </button>
                </div>
                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <button type="button" onclick="closeCustomAiPreviewModal(); openCustomAiModal();" class="px-5 py-2.5 rounded-full text-xs font-bold uppercase tracking-wider transition-all cursor-pointer" style="background-color: #ffffff !important; border: 1px solid #cbd5e1 !important; color: #475569 !important;">
                        Edit Prompt
                    </button>
                    <button type="button" onclick="publishCustomAiPost()" id="publishPostBtn" class="flex-grow sm:flex-grow-0 px-8 py-3 rounded-full text-xs font-extrabold uppercase tracking-wider shadow-xl transition-all transform hover:scale-[1.03] active:scale-95 flex items-center justify-center gap-2 cursor-pointer" style="background: linear-gradient(135deg, #dcfb00, #e6ff33) !important; color: #0f172a !important;">
                        <i data-lucide="check-circle-2" class="w-4 h-4" style="color: #0f172a !important;"></i> Post to Home Page
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Announcement & Poll Modal (Hidden) -->
    <div id="create-announcement-modal" class="fixed inset-0 flex items-center justify-center p-4 hidden" style="z-index: 99970 !important;">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeCreateAnnouncementModal()"></div>
        <div class="w-full max-w-2xl rounded-3xl overflow-hidden shadow-2xl relative flex flex-col z-10" style="max-height: calc(100vh - 40px); background-color: #ffffff !important; border: 1px solid rgba(15, 23, 42, 0.15) !important; animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">
            <!-- Header -->
            <div class="p-6 flex items-center justify-between shadow-sm" style="background: linear-gradient(135deg, #f8fafc, #f1f5f9) !important; border-bottom: 1px solid #e2e8f0 !important;">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 rounded-2xl flex items-center justify-center" style="background-color: rgba(124, 58, 237, 0.1) !important; border: 1px solid rgba(124, 58, 237, 0.2) !important;">
                        <i data-lucide="vote" class="w-6 h-6" style="color: #7c3aed !important;"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-extrabold tracking-tight" style="color: #0f172a !important;">Create Public Announcement & Poll</h2>
                        <p class="text-xs mt-0.5" style="color: #475569 !important;">Publish an announcement message or create a live public opinion poll for all visitors</p>
                    </div>
                </div>
                <button type="button" onclick="closeCreateAnnouncementModal()" class="transition-colors p-2 rounded-full hover:bg-slate-200 cursor-pointer" style="color: #475569 !important;">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="flex-grow overflow-y-auto p-6 space-y-5" style="background-color: #ffffff !important;">
                <form id="createAnnouncementForm" onsubmit="handleCreateAnnouncement(event)" class="space-y-4">
                    <!-- Title -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: #0f172a !important;">Announcement / Poll Title <span style="color: #7c3aed !important;">*</span></label>
                        <input type="text" id="ancTitle" required class="w-full rounded-xl px-3.5 py-2.5 text-sm font-semibold focus:outline-none" style="background-color: #ffffff !important; border: 1px solid #cbd5e1 !important; color: #0f172a !important;" placeholder="e.g. 🗳️ Public Opinion Poll: Coimbatore Metro Corridor Expansion">
                    </div>

                    <!-- Message / Description -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: #0f172a !important;">Description / Message <span style="color: #7c3aed !important;">*</span></label>
                        <textarea id="ancMessage" rows="3" required class="w-full rounded-xl p-3 text-xs focus:outline-none resize-none" style="background-color: #ffffff !important; border: 1px solid #cbd5e1 !important; color: #0f172a !important;" placeholder="Provide details about this announcement or voting context..."></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Category -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: #0f172a !important;">Category</label>
                            <select id="ancCategory" class="w-full rounded-xl px-3 py-2 text-xs font-bold focus:outline-none" style="background-color: #ffffff !important; border: 1px solid #cbd5e1 !important; color: #0f172a !important;">
                                <option value="Public Poll">Public Poll</option>
                                <option value="Announcement">City Announcement</option>
                                <option value="Community Voting">Community Voting</option>
                                <option value="Civic Survey">Civic Survey</option>
                            </select>
                        </div>

                        <!-- Image URL -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: #0f172a !important;">Image URL (Optional)</label>
                            <input type="url" id="ancImage" class="w-full rounded-xl px-3 py-2 text-xs focus:outline-none" style="background-color: #ffffff !important; border: 1px solid #cbd5e1 !important; color: #0f172a !important;" placeholder="https://images.unsplash.com/...">
                        </div>
                    </div>

                    <!-- Enable Polling Toggle -->
                    <div class="pt-2 border-t border-slate-200">
                        <label class="flex items-center gap-3 cursor-pointer select-none">
                            <input type="checkbox" id="ancHasPoll" checked onchange="togglePollFields(this.checked)" class="w-4 h-4 rounded text-purple-600 focus:ring-purple-500 cursor-pointer">
                            <span class="text-xs font-extrabold uppercase tracking-wider" style="color: #0f172a !important;">Include Interactive Polling & Voting Section</span>
                        </label>
                    </div>

                    <!-- Poll Options Section -->
                    <div id="pollOptionsContainer" class="space-y-3 p-4 rounded-2xl" style="background-color: #f8fafc !important; border: 1px solid #e2e8f0 !important;">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider flex items-center gap-1.5" style="color: #7c3aed !important;">
                                <i data-lucide="list-checks" class="w-4 h-4" style="color: #7c3aed !important;"></i> Voting Options
                            </span>
                            <button type="button" onclick="addPollOptionField()" class="text-xs px-3 py-1 rounded-full font-bold uppercase transition-all cursor-pointer" style="background-color: #ffffff !important; border: 1px solid #cbd5e1 !important; color: #7c3aed !important;">
                                + Add Option
                            </button>
                        </div>

                        <div id="pollOptionsList" class="space-y-2">
                            <div class="flex gap-2 items-center">
                                <input type="text" required class="poll-option-input flex-grow rounded-xl px-3 py-2 text-xs focus:outline-none" style="background-color: #ffffff !important; border: 1px solid #cbd5e1 !important; color: #0f172a !important;" placeholder="Option 1 e.g. Avinashi Road Express Flyover Route">
                            </div>
                            <div class="flex gap-2 items-center">
                                <input type="text" required class="poll-option-input flex-grow rounded-xl px-3 py-2 text-xs focus:outline-none" style="background-color: #ffffff !important; border: 1px solid #cbd5e1 !important; color: #0f172a !important;" placeholder="Option 2 e.g. Trichy Road & Singanallur Link">
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-4 flex justify-end">
                        <button type="submit" id="submitAnnouncementBtn" class="px-8 py-3 rounded-full text-xs font-extrabold uppercase tracking-wider shadow-lg transition-all transform hover:scale-[1.03] active:scale-95 flex items-center gap-2 cursor-pointer" style="background: linear-gradient(135deg, #dcfb00, #e6ff33) !important; color: #0f172a !important;">
                            <i data-lucide="check-circle-2" class="w-4 h-4" style="color: #0f172a !important;"></i> Publish Announcement & Poll
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Create Modal (Hidden) -->
    <div id="create-modal" class="fixed inset-0 flex items-center justify-center p-4 hidden" style="z-index: 99970 !important;">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeCreateModal()"></div>
        <div class="w-full max-w-4xl rounded-3xl overflow-hidden shadow-2xl relative flex flex-col z-10 glass-card" style="max-height: calc(100vh - 40px); background-color: rgba(255, 255, 255, 0.98) !important; border: 1px solid rgba(15, 23, 42, 0.08) !important; animation: slideUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">
            <!-- Modal Header -->
            <div class="p-6 flex items-center justify-between shadow-lg bg-gradient-to-r from-slate-50 to-slate-100/50" style="border-bottom: 1px solid rgba(15, 23, 42, 0.08);">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Add Public Event</h2>
                    <p class="text-slate-500 text-xs mt-1">Create and publish a new event to the Coimbatore Kovai.city directory</p>
                </div>
                <button onclick="closeCreateModal()" class="text-slate-500 hover:text-slate-900 transition-colors p-2 rounded-full hover:bg-slate-100">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            <!-- Modal Content (Scrollable) -->
            <div class="flex-grow overflow-y-auto p-8 bg-white">
                <form id="createEventForm" onsubmit="handleCreateEvent(event)" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-6 gap-6">
                        <!-- Field 1: Event Title -->
                        <div class="md:col-span-6">
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Event Title <span class="text-purple-400 font-bold">*</span></label>
                            <input type="text" id="eventTitle" required class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="e.g. Coimbatore Tech Summit 2026">
                        </div>

                        <!-- Field 2: Event Description -->
                        <div class="md:col-span-6">
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Event Description <span class="text-purple-400 font-bold">*</span></label>
                            <textarea id="eventDescription" rows="4" required class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500 resize-none" placeholder="Describe what attendees can expect..."></textarea>
                        </div>

                        <!-- Field 3: Poster Image URL -->
                        <div class="md:col-span-3">
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Poster Image URL</label>
                            <input type="url" id="eventImage" placeholder="https://images.unsplash.com/..." class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>

                        <!-- Field 4: Promo Video URL -->
                        <div class="md:col-span-3">
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Promo Video URL</label>
                            <input type="url" id="eventPromoVideo" placeholder="https://youtube.com/watch?v=..." class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>

                        <!-- Field 5: Event Type -->
                        <div class="md:col-span-3">
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Event Type <span class="text-purple-400 font-bold">*</span></label>
                            <select id="eventType" required class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="Public" selected>Public</option>
                            </select>
                        </div>

                        <!-- Field 6: Category -->
                        <div class="md:col-span-3">
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Category <span class="text-purple-400 font-bold">*</span></label>
                            <select id="eventCategory" required class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500">
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

                        <!-- Field 7: State -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-300 mb-2">State <span class="text-purple-400 font-bold">*</span></label>
                            <input type="text" id="eventState" required value="Tamil Nadu" class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>

                        <!-- Field 8: City -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-300 mb-2">City</label>
                            <input type="text" id="eventCity" placeholder="e.g. Coimbatore" class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>

                        <!-- Field 9: Event Venue -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Event Venue <span class="text-purple-400 font-bold">*</span></label>
                            <input type="text" id="eventVenue" required placeholder="e.g. CODISSIA Complex" class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>

                        <!-- Field 10: Event Date -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Start Date <span class="text-purple-400 font-bold">*</span></label>
                            <input type="date" id="eventDate" required class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>

                        <!-- Field 11: Event End Date -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-300 mb-2">End Date</label>
                            <input type="date" id="eventEndDate" class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>

                        <!-- Field 12: No. of Days -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-300 mb-2">No. of Days</label>
                            <input type="number" id="eventNoOfDays" readonly class="w-full rounded-xl px-4 py-2.5 focus:outline-none text-gray-500 cursor-not-allowed bg-slate-800 border-white/5" style="background-color: rgba(30, 41, 59, 0.5); color: #94a3b8;" value="1">
                        </div>

                        <!-- Field 13: Event Time (24h) -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Event Time (24h)</label>
                            <input type="text" id="eventTime" placeholder="e.g. 18:00" class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>

                        <!-- Field 14: Registration URL -->
                        <div class="md:col-span-4">
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Location/Registration URL</label>
                            <input type="url" id="eventRegistrationUrl" placeholder="https://..." class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>

                        <!-- Field 15: Entry Cost (0 for Free) -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Entry Cost (0 for Free)</label>
                            <input type="number" id="eventPrice" min="0" value="0" class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>

                        <!-- Field 16: Max Participants (Gold styled) -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold mb-2" style="color: #b45309;">Max Participants</label>
                            <input type="number" id="eventMaxParticipants" min="1" placeholder="e.g. 500" class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-500 animate-pulse-subtle" style="border-color: #d97706 !important;">
                        </div>

                        <!-- Field 17: Organizer -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Organizer</label>
                            <input type="text" id="eventOrganizer" placeholder="e.g. CODISSIA" class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>

                        <!-- Field 18: Event Bio -->
                        <div class="md:col-span-6">
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Event Bio</label>
                            <input type="text" id="eventBioText" readonly class="w-full rounded-xl px-4 py-2.5 focus:outline-none text-gray-500 cursor-not-allowed bg-slate-800 border-white/5" style="background-color: rgba(30, 41, 59, 0.5); color: #94a3b8;" placeholder="Auto-generated: Organized by [Organizer]">
                        </div>

                        <!-- Field 19: Venue Description -->
                        <div class="md:col-span-6">
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Venue Description</label>
                            <textarea id="eventVenueText" rows="2" readonly class="w-full rounded-xl px-4 py-2.5 focus:outline-none text-gray-500 cursor-not-allowed bg-slate-800 border-white/5 resize-none" style="background-color: rgba(30, 41, 59, 0.5); color: #94a3b8;" placeholder="Auto-generated venue description..."></textarea>
                        </div>

                        <!-- Field 20: Moderator Emails -->
                        <div class="md:col-span-6">
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Moderator Emails</label>
                            <textarea id="eventModerators" rows="2" class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500 resize-none" placeholder="niranjanindhu1704@gmail.com">niranjanindhu1704@gmail.com</textarea>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-6 flex justify-center pb-8">
                        <button type="submit" id="submitEventBtn" class="px-12 py-3.5 rounded-full text-white font-bold uppercase tracking-wider text-sm transition-all transform hover:scale-[1.03] active:scale-95 shadow-lg shadow-purple-500/20" style="background: linear-gradient(135deg, #dcfb00, #e6ff33);">
                            Submit Event
                        </button>
                    </div>
                </form>

                <!-- News manual creation form -->
                <form id="createNewsForm" onsubmit="handleCreateNews(event)" class="space-y-6 hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Post Title -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Post Title <span class="text-purple-400 font-bold">*</span></label>
                            <input type="text" id="newsTitle" required class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Enter headline or article title">
                        </div>

                        <!-- Description -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Description <span class="text-purple-400 font-bold">*</span></label>
                            <textarea id="newsDescription" rows="4" required class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500 resize-none" placeholder="Provide details about this news article..."></textarea>
                        </div>

                        <!-- Category -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Category <span class="text-purple-400 font-bold">*</span></label>
                            <select id="newsCategory" required class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="" disabled selected hidden>Choose Category</option>
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
                            <label class="block text-sm font-semibold text-gray-300 mb-2">State <span class="text-purple-400 font-bold">*</span></label>
                            <input type="text" id="newsState" required value="Tamil Nadu" class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>

                        <!-- Post Type -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Post Type <span class="text-purple-400 font-bold">*</span></label>
                            <select id="newsPostType" required class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="" disabled selected hidden>Choose Type</option>
                                <option value="News">News</option>
                                <option value="Announcement">Announcement</option>
                                <option value="Article">Article</option>
                                <option value="Blog">Blog</option>
                            </select>
                        </div>

                        <!-- Validity (days) -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Validity (days) <span class="text-purple-400 font-bold">*</span></label>
                            <input type="number" id="newsValidity" min="1" required value="30" class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>

                        <!-- Link 1 (Optional) -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Link 1 (Optional)</label>
                            <input type="url" id="newsLink1" class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="https://...">
                        </div>

                        <!-- Link 2 (Optional) -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Link 2 (Optional)</label>
                            <input type="url" id="newsLink2" class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="https://...">
                        </div>

                        <!-- Video URL (Optional) -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Video URL (Optional)</label>
                            <input type="url" id="newsVideoUrl" class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="https://youtube.com/...">
                        </div>

                        <!-- Image URL (Optional) -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Image URL (Optional)</label>
                            <input type="url" id="newsImageUrl" class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="https://images.unsplash.com/...">
                        </div>

                        <!-- Hidden fields: bioText, eventFor, hideEvent -->
                        <div style="display: none;">
                            <input type="text" id="newsBioText" value="Organized by IndieMa Admin">
                            <input type="number" id="newsEventFor" value="1">
                            <select id="newsHideEvent">
                                <option value="1" selected>Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-6 flex justify-center pb-8">
                        <button type="submit" id="submitNewsBtn" class="px-12 py-3.5 rounded-full text-white font-bold uppercase tracking-wider text-sm transition-all transform hover:scale-[1.03] active:scale-95 shadow-lg shadow-purple-500/20" style="background: linear-gradient(135deg, #dcfb00, #e6ff33);">
                            Create Post
                        </button>
                    </div>
                </form>

                <!-- Facts manual creation form -->
                <form id="createFactForm" onsubmit="handleCreateFact(event)" class="space-y-6 hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Post Title -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Post Title <span class="text-purple-400 font-bold">*</span></label>
                            <input type="text" id="factTitle" required class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Enter spotlight or development title">
                        </div>

                        <!-- Description -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Description <span class="text-purple-400 font-bold">*</span></label>
                            <textarea id="factDescription" rows="4" required class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500 resize-none" placeholder="Provide details about this local discovery or project..."></textarea>
                        </div>

                        <!-- Category -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Category <span class="text-purple-400 font-bold">*</span></label>
                            <select id="factCategory" required class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="" disabled selected hidden>Choose Category</option>
                                <option value="General">General</option>
                                <option value="History">History</option>
                                <option value="Culture">Culture</option>
                                <option value="Industry">Industry</option>
                                <option value="Nature">Nature</option>
                                <option value="Food">Food</option>
                            </select>
                        </div>

                        <!-- State -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">State <span class="text-purple-400 font-bold">*</span></label>
                            <input type="text" id="factState" required value="Tamil Nadu" class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>

                        <!-- Post Type -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Post Type <span class="text-purple-400 font-bold">*</span></label>
                            <select id="factPostType" required class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="" disabled selected hidden>Choose Type</option>
                                <option value="Spotlight">Spotlight</option>
                                <option value="Development">Development</option>
                                <option value="Trivia">Trivia</option>
                                <option value="Heritage">Heritage</option>
                            </select>
                        </div>

                        <!-- Validity (days) -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Validity (days) <span class="text-purple-400 font-bold">*</span></label>
                            <input type="number" id="factValidity" min="1" required value="30" class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>

                        <!-- Link 1 (Optional) -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Link 1 (Optional)</label>
                            <input type="url" id="factLink1" class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="https://...">
                        </div>

                        <!-- Link 2 (Optional) -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Link 2 (Optional)</label>
                            <input type="url" id="factLink2" class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="https://...">
                        </div>

                        <!-- Video URL (Optional) -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Video URL (Optional)</label>
                            <input type="url" id="factVideoUrl" class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="https://youtube.com/...">
                        </div>

                        <!-- Image URL (Optional) -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Image URL (Optional)</label>
                            <input type="url" id="factImageUrl" class="w-full rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="https://images.unsplash.com/...">
                        </div>

                        <!-- Hidden fields: bioText, eventFor, hideEvent -->
                        <div style="display: none;">
                            <input type="text" id="factBioText" value="Organized by IndieMa Admin">
                            <input type="number" id="factEventFor" value="1">
                            <select id="factHideEvent">
                                <option value="1" selected>Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-6 flex justify-center pb-8">
                        <button type="submit" id="submitFactBtn" class="px-12 py-3.5 rounded-full text-white font-bold uppercase tracking-wider text-sm transition-all transform hover:scale-[1.03] active:scale-95 shadow-lg shadow-purple-500/20" style="background: linear-gradient(135deg, #dcfb00, #e6ff33);">
                            Create Post
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function updateAutoFetchUI(autoFetchStopped) {
            const statusText = document.getElementById('auto-fetch-status-text');
            const iconContainer = document.getElementById('auto-fetch-status-icon-container');
            const toggleBtn = document.getElementById('autoFetchToggleBtn');
            const discoverBtn = document.getElementById('discoverBtn');
            const btnText = document.getElementById('discoverBtnText');
            const iconContainerBtn = document.getElementById('discoverBtnIconContainer');
            const chevronContainer = document.getElementById('discoverChevronIconContainer');
            
            if (autoFetchStopped) {
                statusText.textContent = 'Stopped';
                iconContainer.className = 'p-3.5 rounded-2xl bg-gray-500/10 text-gray-400 border border-gray-500/20';
                toggleBtn.textContent = 'Start Bot';
                toggleBtn.className = 'px-4 py-2 rounded-full border text-xs font-extrabold uppercase tracking-wider transition-all duration-300 cursor-pointer text-green-400 border-green-500/30 bg-green-500/10 hover:bg-green-500/20';
                
                // Disable the manual discover button
                if (discoverBtn) {
                    discoverBtn.disabled = true;
                    discoverBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    discoverBtn.classList.remove('hover:bg-brand/20');
                }
                if (btnText) {
                    btnText.textContent = 'Select AI Fetch (Offline)';
                }
                if (chevronContainer) {
                    chevronContainer.innerHTML = '';
                }
            } else {
                statusText.textContent = 'Active';
                iconContainer.className = 'p-3.5 rounded-2xl bg-green-500/10 text-green-400 border border-green-500/20';
                toggleBtn.textContent = 'Stop Bot';
                toggleBtn.className = 'px-4 py-2 rounded-full border text-xs font-extrabold uppercase tracking-wider transition-all duration-300 cursor-pointer text-red-400 border-red-500/30 bg-red-500/10 hover:bg-red-500/20';
                
                // Enable the manual discover button
                if (discoverBtn) {
                    discoverBtn.disabled = false;
                    discoverBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    discoverBtn.classList.add('hover:bg-brand/20');
                }
                if (btnText) {
                    btnText.textContent = 'Select AI Fetch';
                }
                if (chevronContainer) {
                    chevronContainer.innerHTML = '<i data-lucide="chevron-down" class="w-4 h-4"></i>';
                }
            }
            if (typeof lucide !== 'undefined' && lucide.createIcons) {
                lucide.createIcons();
            }
        }

        function toggleAutoFetchStatus() {
            const toggleBtn = document.getElementById('autoFetchToggleBtn');
            const isStopping = toggleBtn.textContent.trim() === 'Stop Bot';
            
            toggleBtn.disabled = true;
            toggleBtn.innerHTML = 'Updating...';
            
            fetch('api.php/api/auto-fetch-status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ auto_fetch_stopped: isStopping })
            })
            .then(res => res.json())
            .then(data => {
                toggleBtn.disabled = false;
                updateAutoFetchUI(data.auto_fetch_stopped);
                const actionWord = data.auto_fetch_stopped ? 'Stopped' : 'Started';
                showNotification(`Event Auto-Fetch Bot has been successfully ${actionWord.toLowerCase()}!`, `Bot ${actionWord}`, 'sparkles', true);
                loadDashboard(); // reload to get updated activity logs
            })
            .catch(err => {
                toggleBtn.disabled = false;
                showNotification('Failed to update auto-fetch status.', 'Error', 'wifi-off', false);
                updateAutoFetchUI(!isStopping);
            });
        }

        let currentTab = 'events';

        function toggleFetchDropdown(event) {
            event.stopPropagation();
            const menu = document.getElementById('fetchDropdownMenu');
            if (menu) {
                menu.classList.toggle('hidden');
            }
        }

        function selectFetchOption(option) {
            const menu = document.getElementById('fetchDropdownMenu');
            if (menu) menu.classList.add('hidden');
            
            discoverEvents(option);
        }

        // Close dropdown when clicking outside
        window.addEventListener('click', function(e) {
            const menu = document.getElementById('fetchDropdownMenu');
            if (menu && !menu.classList.contains('hidden')) {
                menu.classList.add('hidden');
            }
        });

        function updateCategoryFilterOptions(tabName) {
            const select = document.getElementById('category-filter');
            if (!select) return;
            
            select.innerHTML = '';
            
            if (tabName === 'events') {
                select.innerHTML = `
                    <option value="All">All Categories</option>
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
                `;
            } else if (tabName === 'news') {
                select.innerHTML = `
                    <option value="All">All Sources</option>
                    <option value="The Hindu">The Hindu</option>
                    <option value="Coimbatore Times">Coimbatore Times</option>
                    <option value="Kovai Mail">Kovai Mail</option>
                    <option value="Coimbatore News Network">Coimbatore News Network</option>
                    <option value="Coimbatore Mirror">Coimbatore Mirror</option>
                `;
            } else if (tabName === 'facts') {
                select.innerHTML = `
                    <option value="All">All Categories</option>
                    <option value="History">History</option>
                    <option value="Culture">Culture</option>
                    <option value="Industry">Industry</option>
                    <option value="Nature">Nature</option>
                    <option value="Food">Food</option>
                `;
            }
        }

        function switchTab(tabName) {
            currentTab = tabName;
            
            const tabs = ['events', 'news', 'facts', 'announcements'];
            tabs.forEach(t => {
                const btn = document.getElementById(`tab-${t}-btn`);
                if (btn) {
                    if (t === tabName) {
                        btn.className = "transition-all hover:text-purple-500 uppercase text-purple-500 font-bold";
                    } else {
                        btn.className = "transition-all hover:text-purple-500 uppercase text-gray-500 font-bold";
                    }
                }
            });
            
            const colDetails = document.getElementById('col-header-details');
            const colCategory = document.getElementById('col-header-category');
            const colStatus = document.getElementById('col-header-status');
            const selectAll = document.getElementById('select-all-checkbox');
            if (selectAll) selectAll.checked = false;
            
            updateCategoryFilterOptions(tabName);
            
            const filterContainer = document.getElementById('category-filter-container');
            if (filterContainer) {
                if (tabName === 'announcements') {
                    filterContainer.classList.add('hidden');
                } else {
                    filterContainer.classList.remove('hidden');
                }
            }
            
            if (tabName === 'events') {
                if (colDetails) colDetails.textContent = 'Event Details';
                if (colCategory) { colCategory.className = 'px-6 py-4'; colCategory.textContent = 'Category'; }
                if (colStatus) { colStatus.className = 'px-6 py-4'; colStatus.textContent = 'Status'; }
            } else if (tabName === 'news') {
                if (colDetails) colDetails.textContent = 'News Headline';
                if (colCategory) { colCategory.className = 'px-6 py-4'; colCategory.textContent = 'Source & Date'; }
                if (colStatus) colStatus.className = 'px-6 py-4 hidden';
            } else if (tabName === 'facts') {
                if (colDetails) colDetails.textContent = 'New-In-Cbe';
                if (colCategory) { colCategory.className = 'px-6 py-4'; colCategory.textContent = 'Category'; }
                if (colStatus) colStatus.className = 'px-6 py-4 hidden';
            } else if (tabName === 'announcements') {
                if (colDetails) colDetails.textContent = 'Announcement / Poll Title';
                if (colCategory) { colCategory.className = 'px-6 py-4'; colCategory.textContent = 'Category'; }
                if (colStatus) { colStatus.className = 'px-6 py-4'; colStatus.textContent = 'Votes / Type'; }
            }
            
            loadDashboard();
        }

        function loadDashboard() {
            fetch('api.php/api/analytics?t=' + new Date().getTime())
                .then(res => res.json())
                .then(stats => {
                    document.getElementById('total-events').textContent = stats.totalEvents || '0';
                    const totalNewsEl = document.getElementById('total-news');
                    if (totalNewsEl) totalNewsEl.textContent = stats.totalNews || '0';
                    const totalFactsEl = document.getElementById('total-facts');
                    if (totalFactsEl) totalFactsEl.textContent = stats.totalFacts || '0';
                    
                    // Render activities
                    const actContainer = document.getElementById('recent-activity-container');
                    if (actContainer) {
                        actContainer.innerHTML = '';
                        const activities = stats.recentActivity || [];
                        if (activities.length === 0) {
                            actContainer.innerHTML = `<div class="text-gray-500 text-sm italic">No recent activity found.</div>`;
                        } else {
                            // Show last 5 activities with calculated Date and Time
                            activities.slice(0, 5).forEach(act => {
                                const type = act.type || act.category || '';
                                const message = act.message || act.title || act.description || '';
                                
                                let dateStr = act.date || new Date().toISOString().split('T')[0];
                                let timeStr = act.time || new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                                
                                if (dateStr === 'Just now' || !dateStr) {
                                    dateStr = new Date().toISOString().split('T')[0];
                                }
                                if (timeStr === 'Just now' || !timeStr) {
                                    timeStr = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                                }

                                let icon = 'activity';
                                let iconColor = 'text-purple-400';
                                let bgColor = 'bg-purple-500/10';
                                
                                if (type === 'EVENT_CREATED' || type === 'AI_DISCOVERY' || type === 'ANNOUNCEMENT_CREATED') {
                                    icon = 'plus-circle';
                                    iconColor = 'text-green-400';
                                    bgColor = 'bg-green-500/10';
                                } else if (type === 'EVENT_DELETED' || type === 'ANNOUNCEMENT_DELETED') {
                                    icon = 'trash-2';
                                    iconColor = 'text-red-400';
                                    bgColor = 'bg-red-500/10';
                                } else if (type === 'BOT_STATUS_CHANGED') {
                                    icon = 'bot';
                                    iconColor = 'text-blue-400';
                                    bgColor = 'bg-blue-500/10';
                                }

                                actContainer.innerHTML += `
                                    <div class="flex items-start gap-4">
                                        <div class="p-2 rounded-xl ${bgColor} ${iconColor} border border-white/5 mt-0.5" style="border: 1px solid rgba(255, 255, 255, 0.05);">
                                            <i data-lucide="${icon}" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <p class="text-gray-200 text-sm font-medium leading-relaxed">${message}</p>
                                            <div class="flex flex-wrap items-center gap-2 mt-1">
                                                <span class="inline-flex items-center gap-1 text-[11px] text-gray-400 font-semibold bg-white/5 px-2.5 py-0.5 rounded-full border border-white/10">
                                                    <i data-lucide="calendar" class="w-3 h-3 text-purple-400"></i> ${dateStr}
                                                </span>
                                                <span class="inline-flex items-center gap-1 text-[11px] text-gray-400 font-semibold bg-white/5 px-2.5 py-0.5 rounded-full border border-white/10">
                                                    <i data-lucide="clock" class="w-3 h-3 text-brand-light"></i> ${timeStr}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });
                        }
                    }
                });

            // Fetch auto-fetch bot status
            fetch('api.php/api/auto-fetch-status?t=' + new Date().getTime())
                .then(res => res.json())
                .then(status => {
                    updateAutoFetchUI(status.auto_fetch_stopped);
                })
                .catch(err => console.error('Error fetching auto-fetch status:', err));

            const categorySelect = document.getElementById('category-filter');
            const selectedCategory = categorySelect ? categorySelect.value : 'All';
            
            let url = '';
            if (currentTab === 'events') {
                url = `api.php/api/events?category=${encodeURIComponent(selectedCategory)}&t=` + new Date().getTime();
            } else if (currentTab === 'news') {
                url = `api.php/api/news?category=${encodeURIComponent(selectedCategory)}&t=` + new Date().getTime();
            } else if (currentTab === 'facts') {
                url = `api.php/api/facts?category=${encodeURIComponent(selectedCategory)}&t=` + new Date().getTime();
            } else if (currentTab === 'announcements') {
                url = `api.php/api/announcements?t=` + new Date().getTime();
            }

            fetch(url)
                .then(res => res.json())
                .then(items => {
                    const selectAll = document.getElementById('select-all-checkbox');
                    if (selectAll) selectAll.checked = false;

                    const tbody = document.getElementById('events-table-body');
                    tbody.innerHTML = '';
                    
                    if (!items || items.length === 0) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 italic">
                                    No ${currentTab} found. Click "New Announcement & Poll" to create one!
                                </td>
                            </tr>
                        `;
                        handleSelectionChange();
                        return;
                    }

                    items.forEach(item => {
                        if (currentTab === 'events') {
                            tbody.innerHTML += `
                                <tr class="group hover:bg-white/5 transition-colors">
                                    <td class="px-6 py-4 w-12 text-center">
                                        <input type="checkbox" name="event-select" value="${item.id}" onchange="handleSelectionChange()" class="rounded border-gray-700 bg-gray-900 text-purple-600 focus:ring-purple-500 cursor-pointer w-4 h-4">
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-4">
                                            <img src="${item.image}" onerror="this.src='https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=800&q=80'" class="w-12 h-12 rounded-lg object-cover">
                                            <div>
                                                <div class="font-bold text-white">${item.title}</div>
                                                <div class="text-xs text-gray-500">${item.date} • ${item.venue}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-300">${item.category}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 rounded bg-green-500/10 text-green-400 font-bold uppercase border border-green-500/20" style="font-size: 10px;">
                                            Active
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <a href="admin_event_detail.php?id=${item.id}" class="p-2 text-gray-400 hover:text-brand-light transition-colors"><i data-lucide="edit-2" class="w-4 h-4"></i></a>
                                            <a href="event_details.php?id=${item.id}" target="_blank" class="p-2 text-gray-400 hover:text-brand-light transition-colors"><i data-lucide="external-link" class="w-4 h-4"></i></a>
                                            <button onclick="promptDelete('${item.id}')" class="p-2 text-gray-400 hover:text-red-400 transition-colors"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        } else if (currentTab === 'news') {
                            tbody.innerHTML += `
                                <tr class="group hover:bg-white/5 transition-colors">
                                    <td class="px-6 py-4 w-12 text-center">
                                        <input type="checkbox" name="event-select" value="${item.id}" onchange="handleSelectionChange()" class="rounded border-gray-700 bg-gray-900 text-purple-600 focus:ring-purple-500 cursor-pointer w-4 h-4">
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-4">
                                            <img src="${item.image}" onerror="this.src='coimbatore_news.png'" class="w-12 h-12 rounded-lg object-contain bg-slate-950 p-1">
                                            <div class="min-w-0 flex-1">
                                                <div class="font-bold text-white leading-snug">${item.title}</div>
                                                <div class="text-xs text-gray-400 mt-1 line-clamp-1">${item.summary || item.description || ''}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-300">
                                        <div class="font-semibold text-gray-200">${item.source || 'City Updates'}</div>
                                        <div class="text-xs text-gray-500">${item.date || ''}</div>
                                    </td>
                                    <td class="px-6 py-4 hidden"></td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <a href="admin_news_detail.php?id=${item.id}" class="p-2 text-gray-400 hover:text-brand-light transition-colors"><i data-lucide="edit-2" class="w-4 h-4"></i></a>
                                            <a href="city_news_details.php?id=${item.id}" target="_blank" class="p-2 text-gray-400 hover:text-brand-light transition-colors"><i data-lucide="external-link" class="w-4 h-4"></i></a>
                                            <button onclick="promptDelete('${item.id}')" class="p-2 text-gray-400 hover:text-red-400 transition-colors"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        } else if (currentTab === 'facts') {
                            tbody.innerHTML += `
                                <tr class="group hover:bg-white/5 transition-colors">
                                    <td class="px-6 py-4 w-12 text-center">
                                        <input type="checkbox" name="event-select" value="${item.id}" onchange="handleSelectionChange()" class="rounded border-gray-700 bg-gray-900 text-purple-600 focus:ring-purple-500 cursor-pointer w-4 h-4">
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-4">
                                            <img src="${item.image}" onerror="this.src='coimbatore_spotlight.png'" class="w-12 h-12 rounded-lg object-contain bg-slate-950 p-1">
                                            <div class="min-w-0 flex-1">
                                                <div class="font-bold text-white leading-snug">${item.title}</div>
                                                <div class="text-xs text-gray-400 mt-1 line-clamp-1">${item.content || ''}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-300">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-brand/10 text-brand-light border border-brand/20">
                                            ${item.category || 'General'}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 hidden"></td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <a href="admin_fact_detail.php?id=${item.id}" class="p-2 text-gray-400 hover:text-brand-light transition-colors"><i data-lucide="edit-2" class="w-4 h-4"></i></a>
                                            <a href="new_in_che_details.php?id=${item.id}" target="_blank" class="p-2 text-gray-400 hover:text-brand-light transition-colors"><i data-lucide="external-link" class="w-4 h-4"></i></a>
                                            <button onclick="promptDelete('${item.id}')" class="p-2 text-gray-400 hover:text-red-400 transition-colors"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        } else if (currentTab === 'announcements') {
                            const pollInfo = item.hasPoll ? `<span class="text-purple-400 font-extrabold">Poll (${item.totalVotes || 0} votes)</span>` : '<span class="text-gray-400">Announcement</span>';
                            tbody.innerHTML += `
                                <tr class="group hover:bg-white/5 transition-colors">
                                    <td class="px-6 py-4 w-12 text-center">
                                        <input type="checkbox" name="event-select" value="${item.id}" onchange="handleSelectionChange()" class="rounded border-gray-700 bg-gray-900 text-purple-600 focus:ring-purple-500 cursor-pointer w-4 h-4">
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-4">
                                            <div class="p-3 rounded-xl bg-purple-500/10 text-purple-400 border border-purple-500/20 shrink-0">
                                                <i data-lucide="vote" class="w-5 h-5"></i>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="font-bold text-white leading-snug">${item.title}</div>
                                                <div class="text-xs text-gray-400 mt-1 line-clamp-1">${item.message || ''}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-300">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-purple-500/10 text-purple-300 border border-purple-500/20">
                                            ${item.category || 'Announcement'}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-xs font-semibold">
                                        ${pollInfo}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button onclick="promptDelete('${item.id}')" class="p-2 text-gray-400 hover:text-red-400 transition-colors cursor-pointer" title="Delete Announcement"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        }
                    });
                    handleSelectionChange();
                    if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();
                });
        }



        function promptDelete(id) {
            eventToDelete = id;
            document.getElementById('delete-modal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            eventToDelete = null;
            document.getElementById('delete-modal').classList.add('hidden');
        }

        function confirmDelete() {
            if(eventToDelete) {
                fetch('api.php/api/' + currentTab + '/' + eventToDelete, { method: 'DELETE' })
                    .then(() => {
                        closeDeleteModal();
                        loadDashboard();
                        let label = currentTab === 'events' ? 'Event' : (currentTab === 'news' ? 'News item' : 'Fact');
                        showNotification(`${label} deleted successfully!`, 'Success', 'trash-2', true);
                    });
            }
        }

        function promptDeleteAll() {
            const titleEl = document.querySelector('#delete-all-modal h3');
            const descEl = document.querySelector('#delete-all-modal p');
            
            if (titleEl) titleEl.textContent = 'Delete All Database Content';
            if (descEl) descEl.textContent = 'Warning: This action is irreversible. Are you sure you want to delete all events, news updates, and New-In-Cbe facts from the database?';
            
            document.getElementById('delete-all-modal').classList.remove('hidden');
        }

        function closeDeleteAllModal() {
            document.getElementById('delete-all-modal').classList.add('hidden');
        }

        function confirmDeleteAll() {
            fetch('api.php/api/clear-all', { method: 'DELETE' })
                .then(res => res.json())
                .then(data => {
                    closeDeleteAllModal();
                    loadDashboard();
                    showNotification('All events, news updates, and New-In-Cbe facts have been deleted successfully!', 'Database Cleared', 'trash-2', true);
                })
                .catch(err => {
                    closeDeleteAllModal();
                    showNotification('Failed to clear database.', 'Error', 'alert-circle', false);
                });
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
                innerIconBg.className = "w-16 h-16 rounded-full bg-gradient-to-tr from-purple-600 to-purple-400 flex items-center justify-center border border-purple-300/20 shadow-lg relative z-10";
                outerGlow.className = "absolute inset-0 rounded-full blur-xl bg-purple-500/30 opacity-70";
            } else {
                innerIconBg.className = "w-16 h-16 rounded-full bg-gradient-to-tr from-red-600 to-red-400 flex items-center justify-center border border-red-300/20 shadow-lg relative z-10";
                outerGlow.className = "absolute inset-0 rounded-full blur-xl bg-red-500/30 opacity-70";
            }
            
            lucide.createIcons();
            document.getElementById('notification-modal').classList.remove('hidden');
        }

        function closeNotificationModal() {
            document.getElementById('notification-modal').classList.add('hidden');
        }

        async function discoverEvents(type) {
            type = type || 'events';
            const btn = document.getElementById('discoverBtn');
            const btnText = document.getElementById('discoverBtnText');
            const iconContainer = document.getElementById('discoverBtnIconContainer');
            const chevronContainer = document.getElementById('discoverChevronIconContainer');
            
            let label = 'Events';
            if (type === 'events') label = 'Coimbatore Events';
            else if (type === 'news') label = 'News Updates';
            else if (type === 'facts') label = 'New-In-Cbe';
            
            if (btn) btn.disabled = true;
            if (iconContainer) {
                iconContainer.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>';
            }
            if (btnText) btnText.textContent = `Fetching ${label}...`;
            if (chevronContainer) chevronContainer.innerHTML = '';
            if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();

            try {
                const payload = {
                    events: type === 'events',
                    news: type === 'news',
                    facts: type === 'facts'
                };

                const res = await fetch('api.php/api/discover', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                if (!res.ok) {
                    const errorData = await res.json().catch(() => ({}));
                    throw new Error(errorData.message || `Backend returned ${res.status}`);
                }

                const result = await res.json();
                const count = type === 'events'
                    ? (result.eventsCount || 0)
                    : type === 'news'
                        ? (result.newsCount || 0)
                        : (result.factsCount || 0);

                if (count > 0) {
                    showNotification(`Successfully fetched ${count} new ${type === 'events' ? 'events' : type === 'news' ? 'news items' : 'spotlights'}!`, 'Discovered!', 'sparkles', true);
                    switchTab(type);
                } else {
                    showNotification('No new items were added or the discovery service did not return anything new.', 'No New Content', 'alert-circle', false);
                }
            } catch (err) {
                console.error('AI Fetch Error:', err);
                showNotification(`AI Fetch failed: ${err.message || 'Unknown network error'}. Please try again.`, 'Fetch Failed', 'alert-circle', false);
            } finally {
                if (btn) btn.disabled = false;
                if (iconContainer) {
                    iconContainer.innerHTML = '<i data-lucide="sparkles" class="w-4 h-4"></i>';
                }
                if (btnText) btnText.textContent = 'Select AI Fetch';
                if (chevronContainer) {
                    chevronContainer.innerHTML = '<i data-lucide="chevron-down" class="w-4 h-4"></i>';
                }
                if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();
                loadDashboard();
            }
        }

        function updateBioTextSync(organizerName) {
            const bioInput = document.getElementById('eventBioText');
            if (!bioInput) return;
            const prefix = "Organized by ";
            const trimmedName = (organizerName || '').trim();
            bioInput.value = trimmedName ? (prefix + trimmedName) : '';
        }

        function openCreateModal(type) {
            type = type || 'events';
            
            // Reset all forms
            document.getElementById('createEventForm').reset();
            const newsForm = document.getElementById('createNewsForm');
            if (newsForm) newsForm.reset();
            const factForm = document.getElementById('createFactForm');
            if (factForm) factForm.reset();
            
            // Hide all forms
            document.getElementById('createEventForm').classList.add('hidden');
            if (newsForm) newsForm.classList.add('hidden');
            if (factForm) factForm.classList.add('hidden');
            
            // Show the selected form and update headers
            let titleText = 'Add Public Event';
            let descText = 'Create and publish a new event to the Coimbatore Kovai.city directory';
            
            if (type === 'events') {
                document.getElementById('createEventForm').classList.remove('hidden');
                document.getElementById('eventNoOfDays').value = 1;
                const todayStr = new Date().toISOString().split('T')[0];
                const dateEl = document.getElementById('eventDate');
                if (dateEl) dateEl.value = todayStr;
                const endDateEl = document.getElementById('eventEndDate');
                if (endDateEl) endDateEl.value = todayStr;
                const venueTextEl = document.getElementById('eventVenueText');
                if (venueTextEl) venueTextEl.value = '';
                const regUrlEl = document.getElementById('eventRegistrationUrl');
                if (regUrlEl) regUrlEl.value = '';
                lastAutoUrl = '';
                
                // Dynamically fetch organizer name
                fetch('api.php/api/organizer?t=' + new Date().getTime())
                    .then(res => res.json())
                    .then(data => {
                        const orgInput = document.getElementById('eventOrganizer');
                        if (orgInput && data.organizer_name) {
                            orgInput.value = data.organizer_name;
                            updateBioTextSync(data.organizer_name);
                        }
                    })
                    .catch(err => console.error('Error fetching organizer name:', err));
            } else if (type === 'news') {
                titleText = 'Create New Post (City-News)';
                descText = 'Publish local city-news or trending updates about Coimbatore';
                if (newsForm) {
                    newsForm.classList.remove('hidden');
                    const newsCat = document.getElementById('newsCategory');
                    if (newsCat && !newsCat.value) newsCat.value = "General";
                    const newsType = document.getElementById('newsPostType');
                    if (newsType && !newsType.value) newsType.value = "News";
                    document.getElementById('newsState').value = "Tamil Nadu";
                    document.getElementById('newsValidity').value = 30;
                    document.getElementById('newsBioText').value = "Organized by IndieMa Admin";
                    document.getElementById('newsEventFor').value = 1;
                    document.getElementById('newsHideEvent').value = 1;
                }
            } else if (type === 'facts') {
                titleText = 'Create New Post (New-In-Cbe)';
                descText = 'Share a local development, historical fact, cultural spotlight, or industry achievement';
                if (factForm) {
                    factForm.classList.remove('hidden');
                    const factCat = document.getElementById('factCategory');
                    if (factCat && !factCat.value) factCat.value = "General";
                    const factType = document.getElementById('factPostType');
                    if (factType && !factType.value) factType.value = "Spotlight";
                    document.getElementById('factState').value = "Tamil Nadu";
                    document.getElementById('factValidity').value = 30;
                    document.getElementById('factBioText').value = "Organized by IndieMa Admin";
                    document.getElementById('factEventFor').value = 1;
                    document.getElementById('factHideEvent').value = 1;
                }
            }
            
            // Update modal header
            const modalHeader = document.querySelector('#create-modal h2');
            const modalDesc = document.querySelector('#create-modal p.text-slate-500');
            if (modalHeader) modalHeader.textContent = titleText;
            if (modalDesc) modalDesc.textContent = descText;
            
            document.getElementById('create-modal').classList.remove('hidden');
        }
        function closeCreateModal() {
            document.getElementById('create-modal').classList.add('hidden');
        }

        function handleCreateNews(e) {
            e.preventDefault();
            
            const btn = document.getElementById('submitNewsBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = 'Submitting...';
            btn.disabled = true;

            const titleVal = document.getElementById('newsTitle').value.trim();
            const descVal = document.getElementById('newsDescription').value.trim();
            const categoryVal = document.getElementById('newsCategory').value;
            const stateVal = document.getElementById('newsState').value.trim();
            const postTypeVal = document.getElementById('newsPostType').value;
            const validityVal = parseInt(document.getElementById('newsValidity').value, 10) || 30;
            const l1Val = document.getElementById('newsLink1').value.trim();
            const l2Val = document.getElementById('newsLink2').value.trim();
            const videoUrlVal = document.getElementById('newsVideoUrl').value.trim();
            const imageUrlVal = document.getElementById('newsImageUrl').value.trim();
            const bioTextVal = document.getElementById('newsBioText').value.trim() || 'Organized by IndieMa Admin';
            const eventForVal = parseInt(document.getElementById('newsEventFor').value, 10) || 1;
            const hideEventVal = parseInt(document.getElementById('newsHideEvent').value, 10);

            const newNews = {
                title: titleVal,
                postTitle: titleVal,
                summary: descVal,
                postInfo: descVal,
                category: categoryVal,
                state: stateVal,
                postType: postTypeVal,
                validity: validityVal,
                l1: l1Val,
                url: l1Val,
                l2: l2Val,
                videoURL: videoUrlVal,
                image: imageUrlVal || 'coimbatore_news.png',
                imageURL: imageUrlVal || 'coimbatore_news.png',
                source: 'Admin',
                date: new Date().toISOString().split('T')[0],
                trending: false,
                bioText: bioTextVal,
                eventFor: eventForVal,
                hideEvent: hideEventVal
            };

            fetch('api.php/api/news', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(newNews)
            })
            .then(res => res.json())
            .then(data => {
                closeCreateModal();
                switchTab('news');
                showNotification('News update added successfully!', 'Success', 'check-circle', true);
                btn.innerHTML = originalText;
                btn.disabled = false;
            })
            .catch(err => {
                showNotification('Failed to add news update.', 'Error', 'x-circle', false);
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        }

        function handleCreateFact(e) {
            e.preventDefault();
            
            const btn = document.getElementById('submitFactBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = 'Submitting...';
            btn.disabled = true;

            const titleVal = document.getElementById('factTitle').value.trim();
            const descVal = document.getElementById('factDescription').value.trim();
            const categoryVal = document.getElementById('factCategory').value;
            const stateVal = document.getElementById('factState').value.trim();
            const postTypeVal = document.getElementById('factPostType').value;
            const validityVal = parseInt(document.getElementById('factValidity').value, 10) || 30;
            const l1Val = document.getElementById('factLink1').value.trim();
            const l2Val = document.getElementById('factLink2').value.trim();
            const videoUrlVal = document.getElementById('factVideoUrl').value.trim();
            const imageUrlVal = document.getElementById('factImageUrl').value.trim();
            const bioTextVal = document.getElementById('factBioText').value.trim() || 'Organized by IndieMa Admin';
            const eventForVal = parseInt(document.getElementById('factEventFor').value, 10) || 1;
            const hideEventVal = parseInt(document.getElementById('factHideEvent').value, 10);

            const newFact = {
                title: titleVal,
                postTitle: titleVal,
                content: descVal,
                postInfo: descVal,
                category: categoryVal,
                state: stateVal,
                postType: postTypeVal,
                validity: validityVal,
                l1: l1Val,
                url: l1Val,
                l2: l2Val,
                videoURL: videoUrlVal,
                image: imageUrlVal || 'coimbatore_spotlight.png',
                imageURL: imageUrlVal || 'coimbatore_spotlight.png',
                bioText: bioTextVal,
                eventFor: eventForVal,
                hideEvent: hideEventVal
            };

            fetch('api.php/api/facts', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(newFact)
            })
            .then(res => res.json())
            .then(data => {
                closeCreateModal();
                switchTab('facts');
                showNotification('New-In-Cbe spotlight added successfully!', 'Success', 'check-circle', true);
                btn.innerHTML = originalText;
                btn.disabled = false;
            })
            .catch(err => {
                showNotification('Failed to add spotlight.', 'Error', 'x-circle', false);
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        }

        function handleCreateEvent(e) {
            e.preventDefault();
            
            const btn = document.getElementById('submitEventBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = 'Submitting...';
            btn.disabled = true;

            const cityEl = document.getElementById('eventCity');
            const cityVal = cityEl ? cityEl.value.trim() : 'Coimbatore';
            const stateEl = document.getElementById('eventState');
            const stateVal = stateEl ? stateEl.value.trim() : 'Tamil Nadu';
            const venueInputEl = document.getElementById('eventVenue');
            const venueVal = venueInputEl ? venueInputEl.value.trim() : (cityVal ? `${cityVal}, ${stateVal}` : stateVal);

            const dateVal = document.getElementById('eventDate').value;
            const endDateEl = document.getElementById('eventEndDate');
            const endDateVal = (endDateEl && endDateEl.value) ? endDateEl.value : dateVal;
            const noOfDaysVal = parseInt(document.getElementById('eventNoOfDays').value, 10) || 1;
            const descVal = document.getElementById('eventDescription').value.trim();

            const organizerEl = document.getElementById('eventOrganizer');
            const organizerVal = organizerEl ? organizerEl.value.trim() : 'IndieMa Admin';
            const bioTextEl = document.getElementById('eventBioText');
            const bioTextVal = bioTextEl ? bioTextEl.value.trim() : `Organized by ${organizerVal}`;
            const venueTextEl = document.getElementById('eventVenueText');
            const venueTextVal = venueTextEl ? venueTextEl.value.trim() : (typeof generateVenueText === 'function' ? generateVenueText(venueVal, cityVal, stateVal) : `${venueVal} offers a comfortable and elegant space for hosting memorable events and celebrations.`);
            const regUrlEl = document.getElementById('eventRegistrationUrl');
            const registrationUrlVal = regUrlEl ? regUrlEl.value.trim() : (typeof matchLocationUrl === 'function' ? matchLocationUrl(venueVal) : `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(venueVal)}`);

            const newEvent = {
                title: document.getElementById('eventTitle').value.trim(),
                type: document.getElementById('eventType').value,
                category: document.getElementById('eventCategory').value,
                state: stateVal,
                city: cityVal,
                noOfDays: noOfDaysVal,
                date: dateVal,
                endDate: endDateVal,
                time: document.getElementById('eventTime').value.trim(),
                image: document.getElementById('eventImage').value.trim() || 'coimbatore_events.png',
                promoVideo: document.getElementById('eventPromoVideo').value.trim(),
                validity: document.getElementById('eventValidity') ? document.getElementById('eventValidity').value : '30',
                price: document.getElementById('eventPrice').value,
                maxParticipants: document.getElementById('eventMaxParticipants').value,
                description: descVal,
                moderators: document.getElementById('eventModerators').value.trim(),
                venue: venueVal,
                organizer: organizerVal,
                bioText: bioTextVal,
                eventvenueText: venueTextVal,
                registrationUrl: registrationUrlVal,
                trending: false
            };

            fetch('api.php/api/events', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(newEvent)
            })
            .then(res => res.json())
            .then(data => {
                closeCreateModal();
                loadDashboard();
                showNotification('Event added successfully!', 'Success', 'check-circle', true);
                btn.innerHTML = originalText;
                btn.disabled = false;
            })
            .catch(err => {
                showNotification('Failed to add event.', 'Error', 'x-circle', false);
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        }

        function unlockAdminAccess() {
            const val = document.getElementById('lock-passkey-input').value;
            const formData = new FormData();
            formData.append('action', 'login_admin');
            formData.append('passkey', val);

            fetch('admin.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    document.getElementById('lock-passkey-error').classList.remove('hidden');
                    document.getElementById('lock-passkey-input').value = '';
                    document.getElementById('lock-passkey-input').focus();
                }
            })
            .catch(err => {
                console.error(err);
                alert('An error occurred during authentication.');
            });
        }

        function cancelAdminAccess() {
            window.location.href = 'index.php';
        }

        function toggleSelectAll(master) {
            const checkboxes = document.querySelectorAll('input[name="event-select"]');
            checkboxes.forEach(cb => cb.checked = master.checked);
            handleSelectionChange();
        }

        function handleSelectionChange() {
            const checkboxes = document.querySelectorAll('input[name="event-select"]');
            const selectedCheckboxes = document.querySelectorAll('input[name="event-select"]:checked');
            const selectAllCheckbox = document.getElementById('select-all-checkbox');
            const deleteSelectedBtn = document.getElementById('delete-selected-btn');
            const selectedCountSpan = document.getElementById('selected-count');

            if (selectAllCheckbox) {
                selectAllCheckbox.checked = checkboxes.length > 0 && checkboxes.length === selectedCheckboxes.length;
            }

            if (deleteSelectedBtn) {
                if (selectedCheckboxes.length > 0) {
                    deleteSelectedBtn.classList.remove('hidden');
                    deleteSelectedBtn.classList.add('inline-flex');
                    selectedCountSpan.textContent = selectedCheckboxes.length;
                } else {
                    deleteSelectedBtn.classList.add('hidden');
                    deleteSelectedBtn.classList.remove('inline-flex');
                }
            }
        }

        function promptDeleteSelected() {
            const selectedCheckboxes = document.querySelectorAll('input[name="event-select"]:checked');
            if (selectedCheckboxes.length === 0) return;
            
            document.getElementById('delete-selected-modal-count').textContent = selectedCheckboxes.length;
            document.getElementById('delete-selected-modal').classList.remove('hidden');
        }

        function closeDeleteSelectedModal() {
            document.getElementById('delete-selected-modal').classList.add('hidden');
        }

        function confirmDeleteSelected() {
            const selectedCheckboxes = document.querySelectorAll('input[name="event-select"]:checked');
            const ids = Array.from(selectedCheckboxes).map(cb => cb.value);
            
            closeDeleteSelectedModal();
            
            fetch('api.php/api/' + currentTab, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids: ids })
            })
            .then(res => res.json())
            .then(data => {
                const selectAll = document.getElementById('select-all-checkbox');
                if (selectAll) selectAll.checked = false;
                
                loadDashboard();
                let label = currentTab === 'events' ? 'events' : (currentTab === 'news' ? 'news articles' : 'facts');
                showNotification(`${ids.length} ${label} deleted successfully!`, 'Success', 'trash-2', true);
            })
            .catch(err => {
                console.error('Error during bulk deletion:', err);
                let label = currentTab === 'events' ? 'events' : (currentTab === 'news' ? 'news articles' : 'facts');
                showNotification(`Failed to delete selected ${label}.`, 'Error', 'x-circle', false);
            });
        }

        // Run keypress listener for the lock input
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
            const startValEl = document.getElementById('eventDate');
            if (!startValEl) return;
            const startVal = startValEl.value;
            let endInput = document.getElementById('eventEndDate');
            if (!endInput) {
                // If there's no end date input, days = 1 or calculated from startDate
                document.getElementById('eventNoOfDays').value = 1;
                return;
            }
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
            document.getElementById('eventNoOfDays').value = days;
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
            const venueInput = document.getElementById('eventVenue');
            if (!venueInput) return;
            const venue = venueInput.value;
            const city = document.getElementById('eventCity').value;
            const state = document.getElementById('eventState').value;
            const generated = generateVenueText(venue, city, state);
            document.getElementById('eventVenueText').value = generated;
        }

        function matchLocationUrl(venue) {
            const v = (venue || '').trim();
            if (!v) return '';
            const vLower = v.toLowerCase();
            if (vLower.includes('codissia')) return 'https://www.codissia.com/events/';
            if (vLower.includes('psg')) return 'https://www.psgtech.edu/';
            if (vLower.includes('kumaraguru') || vLower.includes('kct')) return 'https://kct.ac.in/';
            if (vLower.includes('karunya')) return 'https://www.karunya.edu/';
            if (vLower.includes('amrita')) return 'https://www.amrita.edu/';
            if (vLower.includes('brookefield')) return 'https://www.brookefields.com/';
            if (vLower.includes('prozone')) return 'http://www.prozonemalls.com/coimbatore/';
            if (vLower.includes('fun republic')) return 'https://www.funrepublicmall.com/';
            
            return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(v)}`;
        }

        // lastAutoUrl declared at top
        function syncLocationUrl() {
            const venueInput = document.getElementById('eventVenue');
            if (!venueInput) return;
            const venue = venueInput.value;
            const currentUrlInput = document.getElementById('eventRegistrationUrl');
            if (!currentUrlInput) return;
            const currentUrl = currentUrlInput.value;
            if (!currentUrl || currentUrl === lastAutoUrl) {
                const newUrl = matchLocationUrl(venue);
                currentUrlInput.value = newUrl;
                lastAutoUrl = newUrl;
            }
        }

        let currentGeneratedPost = null;

        function openCustomAiModal() {
            window.location.href = 'custompost.php';
        }

        function closeCustomAiModal() {
            document.getElementById('custom-ai-modal').classList.add('hidden');
        }

        function setAiPrompt(text) {
            const input = document.getElementById('customAiPromptInput');
            if (input) {
                input.value = text;
                input.focus();
            }
        }

        async function generateCustomAiPost() {
            const promptInput = document.getElementById('customAiPromptInput');
            const prompt = (promptInput ? promptInput.value : '').trim();
            if (!prompt) {
                alert('Please enter a command or topic prompt first.');
                return;
            }

            const btn = document.getElementById('customAiGenerateBtn');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Generating Preview...';
                if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();
            }

            try {
                const res = await fetch('api.php/api/generate-custom-post', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ prompt: prompt })
                });

                if (!res.ok) {
                    const errData = await res.json().catch(() => ({}));
                    throw new Error(errData.message || 'Failed to generate custom AI post.');
                }

                const data = await res.json();
                if (!data.success || !data.post) {
                    throw new Error(data.message || 'Custom post generation returned empty result.');
                }

                currentGeneratedPost = data.post;
                closeCustomAiModal();
                openCustomAiPreviewModal(currentGeneratedPost);

            } catch (err) {
                console.error('Custom AI Post Error:', err);
                showNotification(`Generation failed: ${err.message}. Please try again.`, 'Generation Error', 'alert-circle', false);
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i data-lucide="sparkles" class="w-4 h-4"></i> Generate Preview';
                    if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();
                }
            }
        }

        function openCustomAiPreviewModal(post) {
            if (!post) return;
            
            const pType = (post.postType || 'events').toLowerCase();
            document.getElementById('editPostType').value = pType;
            document.getElementById('editTitle').value = post.title || '';
            document.getElementById('editDescription').value = post.description || post.content || post.summary || '';
            document.getElementById('editCategory').value = post.category || 'General';
            document.getElementById('editDate').value = post.date || new Date().toISOString().split('T')[0];
            document.getElementById('editVenue').value = post.venue || '';
            document.getElementById('editOrganizer').value = post.organizer || '';
            document.getElementById('editTime').value = post.time || '10:00 AM';
            document.getElementById('editPrice').value = post.price || 'Free';
            document.getElementById('editUrl').value = post.url || '';
            document.getElementById('editImageUrl').value = post.image || '';

            updatePreviewDisplayUI();

            document.getElementById('custom-ai-preview-modal').classList.remove('hidden');
            if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();
        }

        function closeCustomAiPreviewModal() {
            document.getElementById('custom-ai-preview-modal').classList.add('hidden');
        }

        function onPreviewPostTypeChange() {
            updatePreviewDisplayUI();
        }

        function updatePreviewImageFromInput() {
            const url = document.getElementById('editImageUrl').value.trim();
            const imgEl = document.getElementById('previewImageDisplay');
            if (imgEl && url) {
                imgEl.src = url;
            }
        }

        function regenerateAiImage() {
            const title = document.getElementById('editTitle').value.trim() || 'Coimbatore';
            const seed = Math.floor(Math.random() * 900000) + 1000;
            const newUrl = "https://image.pollinations.ai/prompt/" + encodeURIComponent(title + " Coimbatore highly detailed photo") + "?width=800&height=600&nologo=true&seed=" + seed;
            
            document.getElementById('editImageUrl').value = newUrl;
            updatePreviewImageFromInput();
        }

        function updatePreviewDisplayUI() {
            const type = document.getElementById('editPostType').value;
            const title = document.getElementById('editTitle').value || 'Post Title';
            const desc = document.getElementById('editDescription').value || 'Post description...';
            const cat = document.getElementById('editCategory').value || 'General';
            const date = document.getElementById('editDate').value || new Date().toISOString().split('T')[0];
            const venue = document.getElementById('editVenue').value || 'Coimbatore';
            const img = document.getElementById('editImageUrl').value || 'coimbatore_events.png';

            const typeBadge = document.getElementById('previewPostTypeBadge');
            let targetLabel = 'Events';
            if (type === 'news') targetLabel = 'City-News';
            else if (type === 'facts') targetLabel = 'New-In-Cbe';

            if (typeBadge) {
                typeBadge.textContent = targetLabel;
            }

            const publishBtn = document.getElementById('publishPostBtn');
            if (publishBtn) {
                publishBtn.innerHTML = `<i data-lucide="check-circle-2" class="w-4 h-4" style="color: #0f172a !important;"></i> Post to ${targetLabel}`;
                if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();
            }

            const titleEl = document.getElementById('previewTitleDisplay');
            if (titleEl) titleEl.textContent = title;

            const descEl = document.getElementById('previewDescriptionDisplay');
            if (descEl) descEl.textContent = desc;

            const catBadge = document.getElementById('previewCategoryBadge');
            if (catBadge) catBadge.textContent = cat;

            const dateBadge = document.getElementById('previewDateBadge');
            if (dateBadge) dateBadge.textContent = date;

            const venueBadge = document.getElementById('previewVenueBadge');
            if (venueBadge) {
                if (type === 'events') {
                    venueBadge.textContent = venue;
                    venueBadge.classList.remove('hidden');
                } else {
                    venueBadge.classList.add('hidden');
                }
            }

            const imgEl = document.getElementById('previewImageDisplay');
            if (imgEl) imgEl.src = img;

            const eventGroup = document.getElementById('eventFieldsGroup');
            if (eventGroup) {
                if (type === 'events') eventGroup.classList.remove('hidden');
                else eventGroup.classList.add('hidden');
            }
        }

        async function publishCustomAiPost() {
            const type = document.getElementById('editPostType').value;
            const title = document.getElementById('editTitle').value.trim();
            const desc = document.getElementById('editDescription').value.trim();

            if (!title || !desc) {
                alert('Please fill out the post title and description before publishing.');
                return;
            }

            let sectionName = 'Events';
            if (type === 'news') sectionName = 'City-News';
            else if (type === 'facts') sectionName = 'New-In-Cbe';

            const postPayload = {
                postType: type,
                title: title,
                description: desc,
                content: desc,
                summary: desc,
                category: document.getElementById('editCategory').value.trim() || 'General',
                date: document.getElementById('editDate').value || new Date().toISOString().split('T')[0],
                venue: document.getElementById('editVenue').value.trim() || 'Coimbatore',
                organizer: document.getElementById('editOrganizer').value.trim() || 'Kovai.city',
                time: document.getElementById('editTime').value.trim() || '10:00 AM',
                price: document.getElementById('editPrice').value.trim() || 'Free',
                url: document.getElementById('editUrl').value.trim() || '',
                source: 'Kovai.city AI',
                image: document.getElementById('editImageUrl').value.trim() || 'coimbatore_events.png'
            };

            const btn = document.getElementById('publishPostBtn');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = `<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Publishing to ${sectionName}...`;
                if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();
            }

            try {
                const res = await fetch('api.php/api/save-custom-post', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ postType: type, post: postPayload })
                });

                if (!res.ok) {
                    const errData = await res.json().catch(() => ({}));
                    throw new Error(errData.message || 'Failed to publish custom post.');
                }

                const data = await res.json();
                closeCustomAiPreviewModal();

                showNotification(`Your AI post "${title}" has been published to ${sectionName}!`, 'Posted Successfully!', 'check-circle', true);
                
                switchTab(type);
                loadDashboard();

            } catch (err) {
                console.error('Publish Custom Post Error:', err);
                showNotification(`Publish failed: ${err.message}. Please try again.`, 'Publish Failed', 'alert-circle', false);
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i data-lucide="check-circle-2" class="w-4 h-4"></i> Post to Home Page';
                    if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();
                }
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const lockInput = document.getElementById('lock-passkey-input');
            if (lockInput) {
                lockInput.focus();
                setTimeout(() => lockInput.focus(), 100);
                
                lockInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        unlockAdminAccess();
                    }
                });
            }

            const orgInput = document.getElementById('eventOrganizer');
            if (orgInput) {
                orgInput.addEventListener('input', (e) => {
                    updateBioTextSync(e.target.value);
                });
            }

            const dateInput = document.getElementById('eventDate');
            const endDateInput = document.getElementById('eventEndDate');
            const venueInput = document.getElementById('eventVenue');
            const cityInput = document.getElementById('eventCity');
            const stateInput = document.getElementById('eventState');

            if (dateInput) dateInput.addEventListener('change', () => { syncTotalDays(); });
            if (endDateInput) endDateInput.addEventListener('change', () => { syncTotalDays(); });
            if (venueInput) {
                venueInput.addEventListener('input', () => { syncVenueText(); syncLocationUrl(); });
            }
            if (cityInput) cityInput.addEventListener('input', () => { syncVenueText(); });
            if (stateInput) stateInput.addEventListener('input', () => { syncVenueText(); });

            // Fetch pending ads count for Sponsor Ads sidebar card badge
            fetch('api.php/api/ads?status=pending&t=' + new Date().getTime())
                .then(res => res.json())
                .then(ads => {
                    if (ads && ads.length > 0) {
                        const badge = document.getElementById('admin-pending-badge');
                        if (badge) {
                            badge.textContent = ads.length;
                            badge.classList.remove('hidden');
                        }
                    }
                })
                .catch(err => console.warn('Error fetching pending ads count for dashboard:', err));

            // Fetch pending posts count
            fetch('api.php/api/pending-posts?t=' + new Date().getTime())
                .then(res => res.json())
                .then(posts => {
                    if (posts && posts.length > 0) {
                        const badge = document.getElementById('admin-pending-posts-badge');
                        if (badge) {
                            badge.textContent = posts.length;
                            badge.classList.remove('hidden');
                        }
                    }
                })
                .catch(err => console.warn('Error fetching pending posts count for dashboard:', err));
        });

        function openCreateAnnouncementModal() {
            document.getElementById('create-announcement-modal').classList.remove('hidden');
            if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();
        }

        function closeCreateAnnouncementModal() {
            document.getElementById('create-announcement-modal').classList.add('hidden');
            document.getElementById('createAnnouncementForm').reset();
            togglePollFields(true);
        }

        function togglePollFields(enabled) {
            const container = document.getElementById('pollOptionsContainer');
            if (container) {
                if (enabled) {
                    container.classList.remove('hidden');
                } else {
                    container.classList.add('hidden');
                }
            }
            const optInputs = document.querySelectorAll('.poll-option-input');
            optInputs.forEach(inp => {
                inp.required = enabled;
            });
        }

        function addPollOptionField() {
            const list = document.getElementById('pollOptionsList');
            if (!list) return;
            const count = list.children.length + 1;
            const hasPoll = document.getElementById('ancHasPoll') ? document.getElementById('ancHasPoll').checked : true;
            const reqAttr = hasPoll ? 'required' : '';
            const div = document.createElement('div');
            div.className = "flex gap-2 items-center";
            div.innerHTML = `
                <input type="text" ${reqAttr} class="poll-option-input flex-grow rounded-xl px-3 py-2 text-xs focus:outline-none" style="background-color: #ffffff !important; border: 1px solid #cbd5e1 !important; color: #0f172a !important;" placeholder="Option ${count}">
                <button type="button" onclick="this.parentElement.remove()" class="p-2 text-red-500 hover:text-red-700 cursor-pointer" title="Remove Option">
                    <i data-lucide="minus-circle" class="w-4 h-4"></i>
                </button>
            `;
            list.appendChild(div);
            if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();
        }

        function handleCreateAnnouncement(e) {
            e.preventDefault();
            const btn = document.getElementById('submitAnnouncementBtn');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = `<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Publishing...`;

            const title = document.getElementById('ancTitle').value.trim();
            const message = document.getElementById('ancMessage').value.trim();
            const category = document.getElementById('ancCategory').value;
            const image = document.getElementById('ancImage').value.trim();
            const hasPoll = document.getElementById('ancHasPoll').checked;

            const options = [];
            if (hasPoll) {
                const optInputs = document.querySelectorAll('.poll-option-input');
                optInputs.forEach(inp => {
                    if (inp.value.trim()) {
                        options.push(inp.value.trim());
                    }
                });
            }

            fetch('api.php/api/announcements', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    title: title,
                    message: message,
                    category: category,
                    image: image,
                    hasPoll: hasPoll,
                    options: options
                })
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                if (data.success) {
                    closeCreateAnnouncementModal();
                    showNotification('Public Announcement & Poll published live to the Home Screen!', 'Live on Home Screen!', 'vote', true);
                    switchTab('announcements');
                } else {
                    alert(data.message || 'Failed to publish announcement.');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                console.error('Error creating announcement:', err);
                alert('An error occurred while creating the announcement.');
            });
        }

        function loadButtonVisibility() {
            fetch('api.php?action=get_button_visibility&t=' + new Date().getTime())
                .then(res => res.json())
                .then(data => {
                    if (!data) return;
                    
                    const nav = data.navbar || {};
                    const explore = data.explore || {};
                    
                    for (const [key, val] of Object.entries(nav)) {
                        const el = document.getElementById('nav-toggle-' + key);
                        if (el) {
                            el.checked = !!val;
                        }
                    }
                    
                    for (const [key, val] of Object.entries(explore)) {
                        const el = document.getElementById('explore-toggle-' + key);
                        if (el) {
                            el.checked = !!val;
                        }
                    }
                })
                .catch(err => console.error('Error loading button visibility:', err));
        }

        function toggleButtonVisibility(type, key, isChecked) {
            fetch('api.php?action=update_button_visibility', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    type: type,
                    key: key,
                    visible: isChecked
                })
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    console.log('Visibility updated successfully:', type, key, isChecked);
                } else {
                    alert('Failed to update visibility setting');
                }
            })
            .catch(err => {
                console.error('Error updating visibility:', err);
                alert('Failed to communicate with server.');
            });
        }

        let adminRegisteredUsers = [];

        function strval(val) {
            return String(val !== null && val !== undefined ? val : '');
        }

        function openAddCreditsModal() {
            fetch('api.php/api/admin/users')
            .then(res => res.json())
            .then(users => {
                adminRegisteredUsers = users || [];
                const select = document.getElementById('quickSelectUser');
                if (select) {
                    select.innerHTML = '<option value="">-- Choose User or Enter Details Below --</option>';
                    adminRegisteredUsers.forEach(u => {
                        const opt = document.createElement('option');
                        opt.value = u.email;
                        opt.textContent = (u.name || u.email) + ' (' + u.email + ') - ' + Number(u.credits || 0).toLocaleString() + ' Credits';
                        select.appendChild(opt);
                    });
                }
            })
            .catch(err => console.error('Error fetching admin users:', err));

            document.getElementById('add-user-credits-modal').classList.remove('hidden');
            if (typeof lucide !== 'undefined' && lucide.createIcons) {
                lucide.createIcons();
            }
        }

        function closeAddCreditsModal() {
            document.getElementById('add-user-credits-modal').classList.add('hidden');
            document.getElementById('admin-add-credits-form').reset();
            document.getElementById('creditUserBalanceInfo').classList.add('hidden');
        }

        function onQuickUserSelect(selectEl) {
            const email = selectEl.value;
            if (!email) {
                document.getElementById('creditUserBalanceInfo').classList.add('hidden');
                return;
            }
            const u = adminRegisteredUsers.find(user => strval(user.email).toLowerCase() === email.toLowerCase());
            if (u) {
                document.getElementById('creditUserName').value = u.name || '';
                document.getElementById('creditUserEmail').value = u.email || '';
                document.getElementById('creditUserBalanceVal').textContent = Number(u.credits || 0).toLocaleString() + ' Credits';
                document.getElementById('creditUserBalanceInfo').classList.remove('hidden');
            }
        }

        function submitAddUserCredits(e) {
            e.preventDefault();
            const name = document.getElementById('creditUserName').value.trim();
            const email = document.getElementById('creditUserEmail').value.trim();
            const credits = parseFloat(document.getElementById('creditAmountToAdd').value);

            if (!email) {
                alert('Please enter a valid User Mail ID.');
                return;
            }
            if (!credits || credits <= 0) {
                alert('Please enter a valid credit amount greater than 0.');
                return;
            }

            const btn = document.getElementById('submitAddCreditsBtn');
            const origText = btn.innerHTML;
            btn.innerHTML = 'Adding...';
            btn.disabled = true;

            fetch('api.php/api/admin/add-credits', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, email, credits })
            })
            .then(res => {
                if (!res.ok) {
                    return res.json().then(err => { throw new Error(err.message || 'Failed to add credits') });
                }
                return res.json();
            })
            .then(data => {
                btn.innerHTML = origText;
                btn.disabled = false;
                closeAddCreditsModal();
                showNotification(data.message || `Successfully added ${credits.toLocaleString()} credits!`, 'Credits Added', 'check-circle', true);
            })
            .catch(err => {
                console.error('Error adding credits:', err);
                btn.innerHTML = origText;
                btn.disabled = false;
                alert(err.message || 'Failed to add credits for user.');
            });
        }

        loadButtonVisibility();
        switchTab('events');
        lucide.createIcons();
    </script>
    <style>
        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
    </style>
</body>
</html>
