<?php
require_once 'postlog.php';
// ads_history.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['userId'])) {
    header('Location: index.php?auth_required=1');
    exit();
}

$isAdmin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') || (isset($_SESSION['admin_unlocked']) && $_SESSION['admin_unlocked'] === true);

$currentUserId = strval($_SESSION['userId']);
$dataFile = __DIR__ . '/data.json';
$currentUser = null;
if (file_exists($dataFile)) {
    $data = json_decode(@file_get_contents($dataFile), true);
    $usersList = is_array($data) && isset($data['users']) ? $data['users'] : [];
    $sessionEmail = strtolower(trim($_SESSION['email'] ?? ''));
    foreach ($usersList as $u) {
        $uEmail = strtolower(trim($u['email'] ?? ''));
        if ($sessionEmail !== '' && $uEmail === $sessionEmail) {
            $currentUser = $u;
            break;
        } else if (strval($u['id']) === $currentUserId) {
            $currentUser = $u;
            break;
        }
    }
}

// User identifiers for robust role-based matching
$userEmails = array_filter(array_unique([
    strtolower(trim($_SESSION['email'] ?? '')),
    strtolower(trim($currentUser['email'] ?? ''))
]));

$userNames = array_filter(array_unique([
    strtolower(trim($_SESSION['username'] ?? '')),
    strtolower(trim($_SESSION['name'] ?? '')),
    strtolower(trim($currentUser['username'] ?? '')),
    strtolower(trim($currentUser['name'] ?? ''))
]));

// Load all ads from ads.json
$adsFile = __DIR__ . '/ads.json';
$allAds = [];
if (file_exists($adsFile)) {
    $content = @file_get_contents($adsFile);
    if ($content !== false) {
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $allAds = $decoded;
        }
    }
}

// Filter ads: Admin views ALL ads, regular users view ALL ads associated with their email or name
$userAds = [];
foreach ($allAds as $ad) {
    if ($isAdmin) {
        $userAds[] = $ad;
    } else {
        $adEmail = strtolower(trim($ad['email'] ?? ''));
        $adPostedBy = strtolower(trim($ad['postedBy'] ?? ''));
        
        $emailMatch = !empty($adEmail) && in_array($adEmail, $userEmails);
        $nameMatch = !empty($adPostedBy) && in_array($adPostedBy, $userNames);
        
        if ($emailMatch || $nameMatch) {
            $userAds[] = $ad;
        }
    }
}

// Calculate summary stats
$totalAdsCount = count($userAds);
$approvedCount = 0;
$pendingCount = 0;
$rejectedCount = 0;
$totalViewsSum = 0;
$totalClicksSum = 0;

foreach ($userAds as $ad) {
    $status = strtolower(trim($ad['status'] ?? 'approved'));
    if ($status === 'approved') {
        $approvedCount++;
    } else if ($status === 'pending') {
        $pendingCount++;
    } else if ($status === 'rejected') {
        $rejectedCount++;
    }
    $totalViewsSum += intval($ad['views'] ?? 0);
    $totalClicksSum += intval($ad['clicks'] ?? 0);
}
$ctrSum = $totalViewsSum > 0 ? round(($totalClicksSum / $totalViewsSum) * 100, 2) : 0;

$canonHost = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'www.madras.city';
$canonUri = $_SERVER['REQUEST_URI'] ?? '/ads_history.php';
$canonicalUrl = 'https://' . $canonHost . $canonUri;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advertisements History - Madras Chennai Guide</title>

    <meta name="description" content="View complete advertisement campaign history, performance metrics, and analytics on Madras.city Chennai.">
    <meta name="author" content="Madras.city">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">

    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">

    <link rel="icon" type="image/png" href="logo.png">
    <link rel="apple-touch-icon" sizes="180x180" href="https://www.madras.city/logo.png">
    <link rel="apple-touch-icon" href="https://www.madras.city/logo.png">
    <link rel="apple-touch-icon-precomposed" href="https://www.madras.city/logo.png">
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
      @keyframes fadeUp {
          from { opacity: 0; transform: translateY(15px); }
          to { opacity: 1; transform: translateY(0); }
      }
    </style>
</head>
<body class="min-h-screen flex flex-col relative overflow-x-hidden bg-theme-bg">
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <main class="flex-grow pt-32 pb-20 relative z-10">
        <div class="max-w-7xl mx-auto px-6 space-y-8 relative">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <a href="<?= $isAdmin ? 'manageAds.php' : 'profile.php?tab=ads' ?>" class="text-purple-400 hover:text-white transition-colors p-2 rounded-xl bg-white/5 border border-white/10" title="Back">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        </a>
                        <h1 class="text-3xl md:text-4xl font-extrabold text-white tracking-tight">Advertisements History</h1>
                        <?php if ($isAdmin): ?>
                        <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-purple-500/20 text-purple-300 border border-purple-500/30 flex items-center gap-1.5 shadow-sm">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5 text-purple-400"></i> Admin View (All Ads)
                        </span>
                        <?php else: ?>
                        <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 flex items-center gap-1.5 shadow-sm">
                            <i data-lucide="user-check" class="w-3.5 h-3.5 text-emerald-400"></i> User View (My Ads)
                        </span>
                        <?php endif; ?>
                    </div>
                    <p class="text-slate-400 text-sm pl-11">
                        <?= $isAdmin ? 'Comprehensive historical records and performance analytics for all client advertisement campaigns.' : 'Historical records, daily impression statistics, and delivery progress for your advertisement campaigns.' ?>
                    </p>
                </div>

                <div class="flex items-center gap-3 flex-wrap">
                    <a href="create_ads.php" class="px-6 py-3 rounded-full text-slate-900 font-extrabold uppercase tracking-widest text-xs transition-all transform hover:scale-[1.02] active:scale-95 shadow-lg border border-brand/50 flex items-center gap-2 cursor-pointer" style="background-color: #dcfb00 !important; color: #0f172a !important;">
                        <i data-lucide="plus-circle" class="w-4 h-4" style="color: #0f172a !important;"></i> Post New Ad
                    </a>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="glass-card p-5 border-white/5 bg-white/5 flex flex-col justify-between rounded-2xl shadow-lg">
                    <span class="text-slate-400 text-[10px] font-extrabold uppercase tracking-wider">Total Campaigns</span>
                    <h3 class="text-2xl font-black text-white mt-1"><?= number_format($totalAdsCount) ?></h3>
                    <span class="text-[10px] text-slate-500 font-semibold mt-2">Historical Count</span>
                </div>
                <div class="glass-card p-5 border-white/5 bg-white/5 flex flex-col justify-between rounded-2xl shadow-lg">
                    <span class="text-emerald-400 text-[10px] font-extrabold uppercase tracking-wider">Approved / Active</span>
                    <h3 class="text-2xl font-black text-emerald-400 mt-1"><?= number_format($approvedCount) ?></h3>
                    <span class="text-[10px] text-slate-500 font-semibold mt-2">Live Rotation</span>
                </div>
                <div class="glass-card p-5 border-white/5 bg-white/5 flex flex-col justify-between rounded-2xl shadow-lg">
                    <span class="text-amber-400 text-[10px] font-extrabold uppercase tracking-wider">Pending Review</span>
                    <h3 class="text-2xl font-black text-amber-400 mt-1"><?= number_format($pendingCount) ?></h3>
                    <span class="text-[10px] text-slate-500 font-semibold mt-2">Awaiting Approval</span>
                </div>
                <div class="glass-card p-5 border-white/5 bg-white/5 flex flex-col justify-between rounded-2xl shadow-lg">
                    <span class="text-red-400 text-[10px] font-extrabold uppercase tracking-wider">Rejected</span>
                    <h3 class="text-2xl font-black text-red-400 mt-1"><?= number_format($rejectedCount) ?></h3>
                    <span class="text-[10px] text-slate-500 font-semibold mt-2">Not Published</span>
                </div>
                <div class="glass-card p-5 border-white/5 bg-white/5 flex flex-col justify-between rounded-2xl shadow-lg">
                    <span class="text-blue-400 text-[10px] font-extrabold uppercase tracking-wider">Total Views</span>
                    <h3 class="text-2xl font-black text-blue-400 mt-1"><?= number_format($totalViewsSum) ?></h3>
                    <span class="text-[10px] text-slate-500 font-semibold mt-2">Delivered Impressions</span>
                </div>
                <div class="glass-card p-5 border-white/5 bg-white/5 flex flex-col justify-between rounded-2xl shadow-lg">
                    <span class="text-brand-light text-[10px] font-extrabold uppercase tracking-wider">Total Clicks</span>
                    <h3 class="text-2xl font-black text-brand-light mt-1"><?= number_format($totalClicksSum) ?></h3>
                    <span class="text-[10px] text-slate-500 font-semibold mt-2"><?= $ctrSum ?>% CTR Rate</span>
                </div>
            </div>

            <!-- Search, Filter & Controls Panel -->
            <div class="glass-card p-6 border-white/10 bg-white/5 rounded-3xl shadow-xl space-y-4">
                <div class="flex flex-col lg:flex-row gap-4 items-center justify-between">
                    <!-- Search Input -->
                    <div class="relative w-full lg:w-96 group">
                        <i data-lucide="search" class="absolute left-4 top-1/2 transform -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-brand-light transition-colors"></i>
                        <input
                            type="text"
                            id="searchHistoryInput"
                            oninput="applyAdsHistoryFilters()"
                            placeholder="Search by Ad ID (#10), company, title, month, or URL..."
                            class="w-full bg-slate-900/60 border border-white/10 rounded-2xl pl-11 pr-4 py-3 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 transition-all"
                        >
                    </div>

                    <!-- Filters Group -->
                    <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                        <!-- Status Filter -->
                        <div class="flex items-center gap-2 bg-slate-900/60 border border-white/10 px-3.5 py-2 rounded-xl text-xs">
                            <i data-lucide="filter" class="w-3.5 h-3.5 text-purple-400"></i>
                            <span class="text-slate-400 font-semibold hidden sm:inline">Status:</span>
                            <select id="statusHistoryFilter" onchange="applyAdsHistoryFilters()" class="bg-transparent text-white font-bold focus:outline-none cursor-pointer">
                                <option value="All" class="bg-slate-900 text-white">All Statuses</option>
                                <option value="approved" class="bg-slate-900 text-white">Approved / Live</option>
                                <option value="pending" class="bg-slate-900 text-white">Pending Review</option>
                                <option value="rejected" class="bg-slate-900 text-white">Rejected</option>
                            </select>
                        </div>

                        <!-- Page Category Filter -->
                        <div class="flex items-center gap-2 bg-slate-900/60 border border-white/10 px-3.5 py-2 rounded-xl text-xs">
                            <i data-lucide="layout" class="w-3.5 h-3.5 text-blue-400"></i>
                            <span class="text-slate-400 font-semibold hidden sm:inline">Target Page:</span>
                            <select id="categoryHistoryFilter" onchange="applyAdsHistoryFilters()" class="bg-transparent text-white font-bold focus:outline-none cursor-pointer">
                                <option value="All" class="bg-slate-900 text-white">All Pages</option>
                                <option value="Homepage page" class="bg-slate-900 text-white">Home page</option>
                                <option value="Events page" class="bg-slate-900 text-white">Events page</option>
                                <option value="City-news page" class="bg-slate-900 text-white">City-news page</option>
                                <option value="New-In-Cbe page" class="bg-slate-900 text-white">New-In-Cbe page</option>
                                <option value="Classified page" class="bg-slate-900 text-white">Classified page</option>
                            </select>
                        </div>

                        <!-- Sort Filter -->
                        <div class="flex items-center gap-2 bg-slate-900/60 border border-white/10 px-3.5 py-2 rounded-xl text-xs">
                            <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5 text-amber-400"></i>
                            <span class="text-slate-400 font-semibold hidden sm:inline">Sort:</span>
                            <select id="sortHistoryFilter" onchange="applyAdsHistoryFilters()" class="bg-transparent text-white font-bold focus:outline-none cursor-pointer">
                                <option value="newest" class="bg-slate-900 text-white">Newest First</option>
                                <option value="oldest" class="bg-slate-900 text-white">Oldest First</option>
                                <option value="views-desc" class="bg-slate-900 text-white">Most Views</option>
                                <option value="clicks-desc" class="bg-slate-900 text-white">Most Clicks</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ads History List Container -->
            <div id="adsHistoryContainer" class="space-y-4">
                <!-- Dynamically populated via JS -->
            </div>

            <!-- Empty State -->
            <div id="adsHistoryEmptyState" class="glass-card p-16 text-center border-dashed border-2 border-white/10 hidden flex flex-col items-center justify-center gap-4 rounded-3xl">
                <div class="w-20 h-20 rounded-full bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400">
                    <i data-lucide="history" class="w-10 h-10"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-white mb-1">No Advertisement Records Found</h3>
                    <p class="text-slate-400 text-xs max-w-md mx-auto">No ad campaigns match your current search or filter criteria.</p>
                </div>
                <a href="create_ads.php" class="px-6 py-2.5 rounded-full text-slate-900 font-extrabold uppercase tracking-widest text-xs shadow-lg border border-brand/50 mt-2" style="background-color: #dcfb00 !important; color: #0f172a !important;">
                    Post An Ad Campaign
                </a>
            </div>
        </div>
    </main>

    <!-- Analytics & Detail Modal -->
    <div id="adHistoryModal" class="fixed inset-0 flex items-center justify-center p-4 hidden overflow-y-auto" style="z-index: 99990 !important;">
        <div class="fixed inset-0 bg-black/75 backdrop-blur-md" onclick="closeAdHistoryModal()"></div>
        <div class="glass-card p-6 sm:p-8 rounded-3xl shadow-2xl relative w-full max-w-4xl flex flex-col max-h-[calc(100vh-2rem)] my-auto z-10 text-left space-y-6" style="animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); background-color: #ffffff !important; border: 1.5px solid #cbd5e1 !important; color: #0f172a !important;">
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-4 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center border border-purple-200 shrink-0">
                        <i data-lucide="bar-chart-2" class="w-5 h-5 text-purple-600"></i>
                    </div>
                    <div>
                        <h3 id="modalAdCompanyName" class="text-xl font-extrabold tracking-tight" style="color: #0f172a !important;">Campaign History Details</h3>
                        <p class="text-xs font-semibold mt-0.5" id="modalAdSubText" style="color: #64748b !important;">Performance metrics and submission audit log</p>
                    </div>
                </div>
                <button onclick="closeAdHistoryModal()" class="text-slate-400 hover:text-slate-700 transition-colors p-2 rounded-full hover:bg-slate-100 cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Modal Scrollable Content -->
            <div class="overflow-y-auto flex-1 space-y-6 pr-1">
                <!-- Overview Stats Cards -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 text-center">
                        <span class="text-[10px] font-extrabold uppercase block" style="color: #64748b !important;">Total Views</span>
                        <span id="modalAdViews" class="text-2xl font-black text-blue-600 mt-1 block">0</span>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 text-center">
                        <span class="text-[10px] font-extrabold uppercase block" style="color: #64748b !important;">Total Clicks</span>
                        <span id="modalAdClicks" class="text-2xl font-black text-emerald-600 mt-1 block">0</span>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 text-center">
                        <span class="text-[10px] font-extrabold uppercase block" style="color: #64748b !important;">CTR Rate</span>
                        <span id="modalAdCtr" class="text-2xl font-black text-purple-600 mt-1 block">0%</span>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 text-center">
                        <span class="text-[10px] font-extrabold uppercase block mb-1" style="color: #64748b !important;">Status</span>
                        <span id="modalAdStatus" class="text-xs font-extrabold inline-block px-3 py-1 rounded-full uppercase">Approved</span>
                    </div>
                </div>

                <!-- Campaign Meta Info Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-2">
                        <h4 class="font-extrabold uppercase tracking-wider text-[11px] flex items-center gap-1.5" style="color: #6d28d9 !important;">
                            <i data-lucide="info" class="w-3.5 h-3.5"></i> Campaign Information
                        </h4>
                        <div class="space-y-2">
                            <div class="flex justify-between"><span class="font-semibold" style="color: #64748b !important;">Posted By:</span> <strong id="modalAdPostedBy" class="font-bold" style="color: #0f172a !important;"></strong></div>
                            <div class="flex justify-between"><span class="font-semibold" style="color: #64748b !important;">Contact Email:</span> <strong id="modalAdEmail" class="font-bold" style="color: #0f172a !important;"></strong></div>
                            <div class="flex justify-between"><span class="font-semibold" style="color: #64748b !important;">Contact Phone:</span> <strong id="modalAdPhone" class="font-bold" style="color: #0f172a !important;"></strong></div>
                            <div class="flex justify-between"><span class="font-semibold" style="color: #64748b !important;">Target Page:</span> <strong id="modalAdCategory" class="font-bold" style="color: #0f172a !important;"></strong></div>
                            <div class="flex justify-between"><span class="font-semibold" style="color: #64748b !important;">Target Sub-Category:</span> <strong id="modalAdSubCategory" class="font-bold" style="color: #0f172a !important;"></strong></div>
                            <div class="flex justify-between"><span class="font-semibold" style="color: #64748b !important;">Target Month:</span> <strong id="modalAdMonth" class="font-bold" style="color: #6d28d9 !important;"></strong></div>
                            <div class="flex justify-between"><span class="font-semibold" style="color: #64748b !important;">Date Submitted:</span> <strong id="modalAdDate" class="font-bold" style="color: #0f172a !important;"></strong></div>
                        </div>
                    </div>

                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-2">
                        <h4 class="font-extrabold uppercase tracking-wider text-[11px] flex items-center gap-1.5" style="color: #0284c7 !important;">
                            <i data-lucide="link-2" class="w-3.5 h-3.5"></i> Creative & Destination
                        </h4>
                        <div class="space-y-2">
                            <div>
                                <span class="font-semibold block mb-0.5" style="color: #64748b !important;">Landing URL:</span>
                                <a id="modalAdUrl" href="#" target="_blank" class="font-bold truncate block underline hover:text-purple-600" style="color: #2563eb !important;"></a>
                            </div>
                            <div>
                                <span class="font-semibold block mb-0.5" style="color: #64748b !important;">CTA Label:</span>
                                <span id="modalAdCta" class="px-2.5 py-1 rounded-lg bg-slate-200 text-slate-800 font-extrabold uppercase text-[10px] inline-block"></span>
                            </div>
                            <div>
                                <span class="font-semibold block mb-0.5" style="color: #64748b !important;">Ad Description:</span>
                                <p id="modalAdDescription" class="leading-relaxed font-normal bg-white p-3 rounded-xl border border-slate-200" style="color: #1e293b !important;"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Daily Breakdown Table -->
                <div class="space-y-3">
                    <h4 class="font-extrabold uppercase tracking-wider text-xs flex items-center gap-1.5" style="color: #0f172a !important;">
                        <i data-lucide="calendar" class="w-4 h-4 text-purple-600"></i> Daily Performance Breakdown (History)
                    </h4>
                    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="border-b border-slate-200 bg-slate-100 text-[10px] font-extrabold uppercase" style="color: #475569 !important;">
                                    <th class="px-4 py-3">Date</th>
                                    <th class="px-4 py-3 text-right">Daily Views</th>
                                    <th class="px-4 py-3 text-right">Daily Clicks</th>
                                    <th class="px-4 py-3 text-right">Daily CTR</th>
                                </tr>
                            </thead>
                            <tbody id="modalDailyHistoryBody" class="divide-y divide-slate-100 font-medium">
                                <!-- Populated dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex justify-end pt-3 border-t border-slate-200 shrink-0">
                <button onclick="closeAdHistoryModal()" class="px-6 py-2.5 rounded-full border border-slate-300 text-slate-700 font-bold uppercase tracking-wider text-xs hover:bg-slate-100 transition-colors cursor-pointer" style="color: #334155 !important;">
                    Close History Details
                </button>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        const initialRawAds = <?= json_encode($userAds, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        let currentFilteredAds = [...initialRawAds];

        function parseAdId(idVal) {
            if (!idVal) return 0;
            const clean = String(idVal).replace(/[^0-9]/g, '');
            const parsed = parseInt(clean, 10);
            return isNaN(parsed) ? 0 : parsed;
        }

        function applyAdsHistoryFilters() {
            const searchTerm = (document.getElementById('searchHistoryInput').value || '').toLowerCase().trim();
            const statusFilter = document.getElementById('statusHistoryFilter').value;
            const categoryFilter = document.getElementById('categoryHistoryFilter').value;
            const sortFilter = document.getElementById('sortHistoryFilter').value;

            let result = [...initialRawAds];

            // 1. Search filter (supports ID match e.g. #10 or 10, company, description, email, month, URL)
            if (searchTerm) {
                const cleanSearchId = searchTerm.replace('#', '');
                result = result.filter(ad => {
                    const idStr = String(ad.id || '').toLowerCase();
                    const comp = (ad.companyName || '').toLowerCase();
                    const title = (ad.adTitle || ad.description || ad.detailedExplanation || '').toLowerCase();
                    const email = (ad.email || '').toLowerCase();
                    const postedBy = (ad.postedBy || '').toLowerCase();
                    const url = (ad.url || '').toLowerCase();
                    const month = (ad.month || '').toLowerCase();

                    return idStr === cleanSearchId || 
                           comp.includes(searchTerm) || 
                           title.includes(searchTerm) || 
                           email.includes(searchTerm) || 
                           postedBy.includes(searchTerm) || 
                           url.includes(searchTerm) || 
                           month.includes(searchTerm);
                });
            }

            // 2. Status filter
            if (statusFilter !== 'All') {
                result = result.filter(ad => (ad.status || 'approved').toLowerCase() === statusFilter.toLowerCase());
            }

            // 3. Category filter
            if (categoryFilter !== 'All') {
                result = result.filter(ad => {
                    const adCat = (ad.category || '').toLowerCase().replace(' page', '');
                    const selCat = categoryFilter.toLowerCase().replace(' page', '');
                    return adCat === selCat;
                });
            }

            // 4. Sorting
            if (sortFilter === 'newest') {
                result.sort((a, b) => parseAdId(b.id) - parseAdId(a.id));
            } else if (sortFilter === 'oldest') {
                result.sort((a, b) => parseAdId(a.id) - parseAdId(b.id));
            } else if (sortFilter === 'views-desc') {
                result.sort((a, b) => intval(b.views || 0) - intval(a.views || 0));
            } else if (sortFilter === 'clicks-desc') {
                result.sort((a, b) => intval(b.clicks || 0) - intval(a.clicks || 0));
            }

            currentFilteredAds = result;
            renderAdsHistoryList();
        }

        function intval(val) {
            const parsed = parseInt(val, 10);
            return isNaN(parsed) ? 0 : parsed;
        }

        function renderAdsHistoryList() {
            const container = document.getElementById('adsHistoryContainer');
            const emptyState = document.getElementById('adsHistoryEmptyState');

            if (!container) return;

            if (currentFilteredAds.length === 0) {
                container.innerHTML = '';
                if (emptyState) emptyState.classList.remove('hidden');
                return;
            }

            if (emptyState) emptyState.classList.add('hidden');

            container.innerHTML = currentFilteredAds.map((ad, idx) => {
                const status = (ad.status || 'approved').toLowerCase();
                let statusBadgeHtml = '';
                if (status === 'approved') {
                    statusBadgeHtml = '<span class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 flex items-center gap-1"><i data-lucide="check-circle" class="w-3 h-3 text-emerald-400"></i> Approved (Live)</span>';
                } else if (status === 'pending') {
                    statusBadgeHtml = '<span class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-amber-500/20 text-amber-300 border border-amber-500/30 flex items-center gap-1"><i data-lucide="clock" class="w-3 h-3 text-amber-400"></i> Pending Approval</span>';
                } else {
                    statusBadgeHtml = '<span class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-red-500/20 text-red-300 border border-red-500/30 flex items-center gap-1"><i data-lucide="x-circle" class="w-3 h-3 text-red-400"></i> Rejected</span>';
                }

                const views = intval(ad.views || 0);
                const clicks = intval(ad.clicks || 0);
                const ctr = views > 0 ? ((clicks / views) * 100).toFixed(1) : '0.0';

                const companyName = ad.companyName || 'Campaign Ad #' + ad.id;
                const postedBy = ad.postedBy || 'Unknown';
                const pageCategory = ad.category || 'General Page';
                const subCategory = ad.subCategory || '';
                const month = ad.month || 'Current';
                const imageSrc = ad.image || 'https://via.placeholder.com/150';
                const description = ad.adTitle || ad.description || ad.detailedExplanation || 'No description provided.';
                const adIdDisplay = '#' + (ad.id || 'N/A');

                return `
                    <div class="glass-card p-6 border-white/10 bg-white/5 rounded-3xl shadow-xl hover:border-purple-500/30 transition-all duration-300 flex flex-col md:flex-row items-start md:items-center justify-between gap-6" style="animation: fadeUp 0.4s ease forwards ${idx * 0.04}s;">
                        <div class="flex items-start gap-4 flex-1 min-w-0">
                            <!-- Thumbnail -->
                            <div class="w-20 h-20 rounded-2xl overflow-hidden bg-slate-950 border border-white/10 shrink-0 p-1 flex items-center justify-center">
                                <img src="${imageSrc}" onerror="this.src='logo.png'" alt="${companyName}" class="w-full h-full object-contain">
                            </div>

                            <div class="space-y-1.5 flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs font-black text-purple-400 bg-purple-500/10 border border-purple-500/20 px-2 py-0.5 rounded-lg">Ad ${adIdDisplay}</span>
                                    <h3 class="text-lg font-bold text-white truncate">${companyName}</h3>
                                    ${statusBadgeHtml}
                                </div>
                                <p class="text-slate-300 text-xs line-clamp-2 leading-relaxed">${description}</p>
                                
                                <div class="flex items-center gap-4 text-[11px] text-slate-400 flex-wrap pt-1">
                                    <span class="flex items-center gap-1"><i data-lucide="user" class="w-3.5 h-3.5 text-purple-400"></i> ${postedBy}</span>
                                    <span class="text-white/20">•</span>
                                    <span class="flex items-center gap-1"><i data-lucide="layout" class="w-3.5 h-3.5 text-blue-400"></i> ${pageCategory}</span>
                                    ${subCategory ? `<span class="text-white/20">•</span><span>${subCategory}</span>` : ''}
                                    <span class="text-white/20">•</span>
                                    <span class="flex items-center gap-1"><i data-lucide="calendar" class="w-3.5 h-3.5 text-amber-400"></i> ${month}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Stats & Action -->
                        <div class="flex flex-row md:flex-col lg:flex-row items-center justify-between md:justify-end gap-6 w-full md:w-auto border-t md:border-t-0 border-white/10 pt-4 md:pt-0 shrink-0">
                            <div class="flex items-center gap-4 text-center">
                                <div class="bg-white/5 px-3 py-2 rounded-xl border border-white/5">
                                    <span class="text-[9px] font-extrabold uppercase text-slate-400 block">Views</span>
                                    <span class="text-sm font-extrabold text-blue-400">${views.toLocaleString()}</span>
                                </div>
                                <div class="bg-white/5 px-3 py-2 rounded-xl border border-white/5">
                                    <span class="text-[9px] font-extrabold uppercase text-slate-400 block">Clicks</span>
                                    <span class="text-sm font-extrabold text-brand-light">${clicks.toLocaleString()}</span>
                                </div>
                                <div class="bg-white/5 px-3 py-2 rounded-xl border border-white/5">
                                    <span class="text-[9px] font-extrabold uppercase text-slate-400 block">CTR</span>
                                    <span class="text-sm font-extrabold text-purple-400">${ctr}%</span>
                                </div>
                            </div>

                            <button onclick="openAdHistoryModal(${idx})" class="px-5 py-2.5 rounded-full bg-white/10 hover:bg-white/20 border border-white/10 text-white font-extrabold text-xs uppercase tracking-wider transition-all flex items-center gap-1.5 cursor-pointer whitespace-nowrap">
                                <i data-lucide="bar-chart-2" class="w-4 h-4 text-purple-400"></i> History & Analytics
                            </button>
                        </div>
                    </div>
                `;
            }).join('');

            if (typeof lucide !== 'undefined' && lucide.createIcons) {
                lucide.createIcons();
            }
        }

        function openAdHistoryModal(index) {
            const ad = currentFilteredAds[index];
            if (!ad) return;

            document.getElementById('modalAdCompanyName').textContent = ad.companyName || 'Campaign Ad #' + ad.id;
            document.getElementById('modalAdSubText').textContent = 'Ad ID: #' + ad.id + ' • Target Month: ' + (ad.month || 'N/A');

            const views = intval(ad.views || 0);
            const clicks = intval(ad.clicks || 0);
            const ctr = views > 0 ? ((clicks / views) * 100).toFixed(2) + '%' : '0.00%';

            document.getElementById('modalAdViews').textContent = views.toLocaleString();
            document.getElementById('modalAdClicks').textContent = clicks.toLocaleString();
            document.getElementById('modalAdCtr').textContent = ctr;

            const statusEl = document.getElementById('modalAdStatus');
            const status = (ad.status || 'approved').toLowerCase();
            if (status === 'approved') {
                statusEl.textContent = 'Approved (Live)';
                statusEl.className = 'text-xs font-extrabold inline-block px-3 py-1 rounded-full uppercase bg-emerald-100 text-emerald-800 border border-emerald-300';
            } else if (status === 'pending') {
                statusEl.textContent = 'Pending Approval';
                statusEl.className = 'text-xs font-extrabold inline-block px-3 py-1 rounded-full uppercase bg-amber-100 text-amber-800 border border-amber-300';
            } else {
                statusEl.textContent = 'Rejected';
                statusEl.className = 'text-xs font-extrabold inline-block px-3 py-1 rounded-full uppercase bg-red-100 text-red-800 border border-red-300';
            }

            document.getElementById('modalAdPostedBy').textContent = ad.postedBy || 'N/A';
            document.getElementById('modalAdEmail').textContent = ad.email || 'N/A';
            document.getElementById('modalAdPhone').textContent = ad.contactNumber || 'N/A';
            document.getElementById('modalAdCategory').textContent = ad.category || 'N/A';
            document.getElementById('modalAdSubCategory').textContent = ad.subCategory || 'General / All';
            document.getElementById('modalAdMonth').textContent = ad.month || 'N/A';
            document.getElementById('modalAdDate').textContent = ad.date || 'N/A';

            const urlEl = document.getElementById('modalAdUrl');
            urlEl.textContent = ad.url || '#';
            urlEl.href = ad.url || '#';

            document.getElementById('modalAdCta').textContent = ad.ctaLabel || 'visit site';
            document.getElementById('modalAdDescription').textContent = ad.adTitle || ad.description || ad.detailedExplanation || 'No description provided.';

            // Populate Daily History breakdown
            const historyBody = document.getElementById('modalDailyHistoryBody');
            const historyObj = ad.history || {};
            const dates = Object.keys(historyObj).sort().reverse();

            if (dates.length === 0) {
                historyBody.innerHTML = `
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-slate-500 italic">No daily history records logged for this campaign yet.</td>
                    </tr>
                `;
            } else {
                historyBody.innerHTML = dates.map(d => {
                    const dViews = intval(historyObj[d].views || 0);
                    const dClicks = intval(historyObj[d].clicks || 0);
                    const dCtr = dViews > 0 ? ((dClicks / dViews) * 100).toFixed(1) + '%' : '0.0%';
                    return `
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-bold" style="color: #0f172a !important;">${d}</td>
                            <td class="px-4 py-3 text-right text-blue-600 font-bold">${dViews.toLocaleString()}</td>
                            <td class="px-4 py-3 text-right text-emerald-600 font-bold">${dClicks.toLocaleString()}</td>
                            <td class="px-4 py-3 text-right font-bold text-purple-700">${dCtr}</td>
                        </tr>
                    `;
                }).join('');
            }

            document.getElementById('adHistoryModal').classList.remove('hidden');
            if (typeof lucide !== 'undefined' && lucide.createIcons) {
                lucide.createIcons();
            }
        }

        function closeAdHistoryModal() {
            document.getElementById('adHistoryModal').classList.add('hidden');
        }

        // Initialize on load
        document.addEventListener('DOMContentLoaded', () => {
            renderAdsHistoryList();
        });
    </script>
</body>
</html>
