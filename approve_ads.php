<?php
require_once 'postlog.php';
// approve_ads.php
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
    <title>Approve Advertisements | Kovai</title>

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
      
      /* Mirroring detail page ad-popup style specifically inside our preview wrapper */
      .preview-box #ad-popup-preview {
          background-color: #0b0f19 !important;
          border: 1.5px solid #dcfb00 !important;
          box-shadow: 0 10px 30px rgba(220, 251, 0, 0.3) !important;
          border-radius: 1rem !important;
          width: 250px;
          margin: 0 auto;
      }
      .preview-box #ad-popup-preview h4 {
          color: #dcfb00 !important;
          font-weight: 800 !important;
          font-size: 0.875rem !important;
          text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
          margin-bottom: 0.25rem !important;
      }
      .preview-box #ad-popup-preview p {
          color: #cbd5e1 !important;
          font-size: 0.75rem !important;
          line-height: 1.4 !important;
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
                <h3 class="text-2xl font-bold tracking-tight mb-2" style="color: #0f172a !important;">Approve Ads Locked</h3>
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

    <div id="approvals-content" class="max-w-7xl mx-auto px-6 pt-32 pb-12 space-y-8 w-full flex-grow relative z-10 hidden">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-3">
                    <a href="admin.php" class="text-purple-600 hover:text-purple-700 transition-colors p-1.5 rounded-lg bg-purple-500/10 border border-purple-500/20">
                        <i data-lucide="arrow-left" class="w-5 h-5"></i>
                    </a>
                    <h1 class="text-4xl font-bold text-white tracking-tight">Approve Advertisements</h1>
                </div>
                <p class="text-gray-400 mt-2 pl-12">Review user-submitted ads, preview layout placements, and approve (Post) or reject them.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Left Column: Monthly Budget Settings -->
            <div class="lg:col-span-4 flex flex-col">
                <!-- View Pricing Settings -->
                <div class="glass-card p-6 border-white/10 bg-white/5 flex flex-col gap-4">
                    <div>
                        <h3 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                            <i data-lucide="coins" class="text-yellow-400"></i> Monthly Budget
                        </h3>
                        <p class="text-slate-500 text-xs mt-1">Set your monthly advertising budget</p>
                    </div>
                    
                    <div class="flex flex-col gap-3">
                        <div class="flex flex-col gap-1.5">
                            <label for="budget-page" class="text-xs text-gray-300 font-medium">Select Page:</label>
                            <select id="budget-page" class="w-full rounded-xl px-3 py-2 text-gray-900 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-500 font-bold bg-white">
                                <option value="All">All Pages</option>
                                <option value="Homepage page">Home page</option>
                                <option value="Events page">Events page</option>
                                <option value="City-news page">City-news page</option>
                                <option value="New-In-Cbe page">New-In-Cbe page</option>
                                <option value="Classified page">Classified page</option>
                            </select>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label for="budget-month" class="text-xs text-gray-300 font-medium">Select Month:</label>
                            <select id="budget-month" class="w-full rounded-xl px-3 py-2 text-gray-900 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-500 font-bold bg-white">
                                <!-- Dynamically populated -->
                            </select>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label for="single-view-budget" class="text-xs text-gray-300 font-medium">Budget (₹):</label>
                            <input type="number" id="single-view-budget" min="0.01" step="0.01" class="w-full rounded-xl px-3 py-2 text-gray-900 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-500 font-bold" placeholder="10000.00">
                        </div>
                        <button onclick="saveSingleViewBudget()" id="saveBudgetBtn" class="w-full py-2.5 rounded-full text-slate-900 font-extrabold uppercase tracking-wider text-xs transition-all transform hover:scale-[1.01] active:scale-95 shadow-lg shadow-brand/20 cursor-pointer border border-brand/50" style="background-color: #dcfb00 !important; color: #0f172a !important;">
                            Set Budget
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Column: List of Pending Ads -->
            <div class="lg:col-span-8 flex flex-col">
                <div class="glass-card p-6 border-white/10 bg-white/5 flex flex-col flex-grow gap-6">
                    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200/10 pb-4">
                        <div>
                            <h3 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                                <i data-lucide="clock" class="text-yellow-400"></i> Pending Approvals
                            </h3>
                            <p class="text-slate-500 text-xs mt-1">Review, preview, and moderate submitted advertisement placements</p>
                        </div>

                        <!-- Page Filter Dropdown -->
                        <div class="flex items-center gap-2 bg-white/10 border border-white/10 px-3 py-1.5 rounded-lg">
                            <i data-lucide="filter" class="w-3.5 h-3.5 text-purple-400"></i>
                            <label for="filterPendingPageSelect" class="text-xs text-slate-300 font-semibold hidden sm:inline">Page:</label>
                            <select id="filterPendingPageSelect" onchange="filterPendingAdsByPage()" class="bg-transparent text-xs text-white font-bold focus:outline-none cursor-pointer">
                                <option value="All" class="bg-slate-900 text-white">All Pages</option>
                                <option value="Homepage page" class="bg-slate-900 text-white">Home page</option>
                                <option value="Events page" class="bg-slate-900 text-white">Events page</option>
                                <option value="City-news page" class="bg-slate-900 text-white">City-news page</option>
                                <option value="New-In-Cbe page" class="bg-slate-900 text-white">New-In-Cbe page</option>
                                <option value="Classified page" class="bg-slate-900 text-white">Classified page</option>
                            </select>
                        </div>
                    </div>

                    <!-- Pending Ads Container -->
                    <div id="pending-ads-list" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Dynamically populated -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Modal (Hidden) -->
    <div id="details-modal" class="fixed inset-0 flex items-center justify-center p-3 sm:p-6 hidden overflow-y-auto" style="z-index: 99990 !important;">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" onclick="closeDetailsModal()"></div>
        <div class="glass-card bg-white border border-gray-200 p-4 sm:p-6 md:p-8 rounded-3xl shadow-2xl relative w-full max-w-3xl flex flex-col max-h-[calc(100vh-2rem)] sm:max-h-[calc(100vh-3rem)] my-auto z-10" style="animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); background-color: #ffffff !important;">
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-gray-200 pb-3 sm:pb-4 shrink-0">
                <div>
                    <h3 class="text-xl sm:text-2xl font-bold tracking-tight" style="color: #0f172a !important;">Review Advertisement Details</h3>
                    <p class="text-xs mt-0.5 sm:mt-1" style="color: #475569 !important;">Review the details and visual layout before publishing.</p>
                </div>
                <button onclick="closeDetailsModal()" class="text-gray-400 hover:text-slate-600 transition-colors cursor-pointer p-1.5 rounded-lg hover:bg-slate-100 shrink-0">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Modal Content (2-Columns) -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 sm:gap-6 md:gap-8 overflow-y-auto flex-1 min-h-0 py-2 pr-1 my-2">
                <!-- Left Column: Visual Placement Preview -->
                <div class="md:col-span-5 flex flex-col items-center gap-4">
                    <h4 class="text-xs font-bold uppercase tracking-wider self-start flex items-center gap-1.5" style="color: #557f00 !important;">
                        <i data-lucide="eye" class="w-4 h-4"></i> Display Preview
                    </h4>
                    
                    <div class="preview-box p-4 rounded-2xl border border-gray-200 bg-slate-950 w-full flex items-center justify-center min-h-[300px]" style="background-color: #020617 !important;">
                        <!-- Rendered ad popup template preview -->
                        <div id="ad-popup-preview" class="p-4 flex flex-col gap-3">
                            <!-- Populated in JS -->
                        </div>
                    </div>
                </div>

                <!-- Right Column: Full Details Data -->
                <div class="md:col-span-7 space-y-4 text-xs">
                    <h4 class="text-xs font-bold uppercase tracking-wider flex items-center gap-1.5" style="color: #557f00 !important;">
                        <i data-lucide="file-text" class="w-4 h-4"></i> Data Properties
                    </h4>

                    <div class="grid grid-cols-3 gap-4 border border-gray-200 bg-slate-50 p-4 rounded-xl" style="background-color: #f8fafc !important; border-color: #e2e8f0 !important;">
                        <div>
                            <span class="block uppercase tracking-wider font-semibold mb-0.5 text-[10px]" style="color: #64748b !important;">Company Name</span>
                            <span id="detailCompanyName" class="font-extrabold text-sm" style="color: #0f172a !important;"></span>
                        </div>
                        <div>
                            <span class="block uppercase tracking-wider font-semibold mb-0.5 text-[10px]" style="color: #64748b !important;">Select Page</span>
                            <span id="detailCategory" class="font-extrabold text-sm" style="color: #0f172a !important;"></span>
                        </div>
                        <div>
                            <span class="block uppercase tracking-wider font-semibold mb-0.5 text-[10px]" style="color: #64748b !important;">Category</span>
                            <span id="detailSubCategory" class="font-extrabold text-sm" style="color: #0f172a !important;"></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4 border border-gray-200 bg-slate-50 p-4 rounded-xl" style="background-color: #f8fafc !important; border-color: #e2e8f0 !important;">
                        <div>
                            <span class="block uppercase tracking-wider font-semibold mb-0.5 text-[10px]" style="color: #64748b !important;">Posted By</span>
                            <span id="detailPostedBy" class="font-bold text-sm" style="color: #0f172a !important;"></span>
                        </div>
                        <div>
                            <span id="detailTotalViewsLabel" class="block uppercase tracking-wider font-semibold mb-0.5 text-[10px]" style="color: #64748b !important;">Total Views</span>
                            <span id="detailTotalViews" class="font-bold text-sm" style="color: #0f172a !important;"></span>
                        </div>
                        <div>
                            <span class="block uppercase tracking-wider font-semibold mb-0.5 text-[10px]" style="color: #64748b !important;">Total Budget</span>
                            <span id="detailTotalBudget" class="font-bold text-sm" style="color: #0f172a !important;"></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 border border-gray-200 bg-slate-50 p-4 rounded-xl" style="background-color: #f8fafc !important; border-color: #e2e8f0 !important;">
                        <div>
                            <span class="block uppercase tracking-wider font-semibold mb-0.5 text-[10px]" style="color: #64748b !important;">Contact Email</span>
                            <span id="detailEmail" class="font-bold text-sm truncate block" style="color: #0f172a !important;"></span>
                        </div>
                        <div>
                            <span class="block uppercase tracking-wider font-semibold mb-0.5 text-[10px]" style="color: #64748b !important;">Contact Phone</span>
                            <span id="detailContactNumber" class="font-bold text-sm" style="color: #0f172a !important;"></span>
                        </div>
                    </div>

                    <div class="border border-gray-200 bg-slate-50 p-4 rounded-xl" style="background-color: #f8fafc !important; border-color: #e2e8f0 !important;">
                        <span class="block uppercase tracking-wider font-semibold mb-1 text-[10px]" style="color: #64748b !important;">AD Landing page Link</span>
                        <a id="detailDestinationUrl" href="#" target="_blank" class="font-bold truncate flex items-center gap-1 text-sm" style="color: #557f00 !important;">
                            <span class="truncate" id="detailDestinationUrlText"></span> <i data-lucide="external-link" class="w-3.5 h-3.5 shrink-0" style="color: #557f00 !important;"></i>
                        </a>
                    </div>

                    <div class="border border-gray-200 bg-slate-50 p-4 rounded-xl" style="background-color: #f8fafc !important; border-color: #e2e8f0 !important;">
                        <span class="block uppercase tracking-wider font-semibold mb-1 text-[10px]" style="color: #64748b !important;">Image Link</span>
                        <a id="detailImageLinkUrl" href="#" target="_blank" class="font-bold truncate flex items-center gap-1 text-sm" style="color: #557f00 !important;">
                            <span class="truncate" id="detailImageLinkUrlText"></span> <i data-lucide="external-link" class="w-3.5 h-3.5 shrink-0" style="color: #557f00 !important;"></i>
                        </a>
                    </div>

                    <div class="border border-gray-200 bg-slate-50 p-4 rounded-xl" style="background-color: #f8fafc !important; border-color: #e2e8f0 !important;">
                        <span class="block uppercase tracking-wider font-semibold mb-1 text-[10px]" style="color: #64748b !important;">Description</span>
                        <p id="detailDescription" class="leading-relaxed text-sm font-medium" style="color: #1e293b !important;"></p>
                    </div>
                </div>
            </div>

            <!-- Modal Action Buttons Footer -->
            <div class="flex items-center justify-between border-t border-gray-200 pt-3 sm:pt-4 gap-4 shrink-0">
                <button onclick="rejectCurrentAd()" class="px-6 py-3 font-bold text-red-600 bg-red-50 hover:bg-red-100 rounded-xl transition-all border border-red-200 flex items-center gap-1.5 cursor-pointer text-xs uppercase tracking-wider">
                    <i data-lucide="x-circle" class="w-4 h-4"></i> Reject Ad
                </button>

                <div class="flex items-center gap-3">
                    <button onclick="closeDetailsModal()" class="px-6 py-3 font-bold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl border border-slate-200 transition-all cursor-pointer text-xs uppercase tracking-wider">
                        Close
                    </button>
                    <button onclick="approveCurrentAd()" class="px-6 py-3 font-bold rounded-xl shadow-lg transition-all transform hover:scale-[1.02] active:scale-95 flex items-center gap-1.5 cursor-pointer text-xs uppercase tracking-wider" style="background-color: #dcfb00 !important; color: #0f172a !important;">
                        <i data-lucide="check-circle" class="w-4 h-4" style="color: #0f172a !important;"></i> Post Advertisement
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
                <p id="notification-message" class="leading-relaxed font-medium" style="color: #475569 !important;"></p>
            </div>
            <button onclick="closeNotificationModal()" class="w-full text-slate-900 font-bold py-3 px-8 rounded-full shadow-lg shadow-brand/20 uppercase tracking-wider text-sm transition-all transform hover:scale-[1.03] active:scale-95" style="background-color: #dcfb00 !important; color: #0b0f19 !important;">
                Awesome
            </button>
        </div>
    </div>

    <!-- Reject Reason Modal (Hidden) -->
    <div id="reject-reason-modal" class="fixed inset-0 flex items-center justify-center p-4 hidden" style="z-index: 99990 !important;">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeRejectReasonModal()"></div>
        <div class="glass-card bg-white border border-red-200 p-8 rounded-3xl shadow-2xl relative w-full max-w-md flex flex-col gap-5 z-10 animate-fade-in" style="animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); background-color: rgba(255, 255, 255, 0.98) !important; border: 1px solid rgba(239, 68, 68, 0.2) !important;">
            <div class="flex items-center gap-3 border-b border-gray-100 pb-3">
                <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center border border-red-100 shrink-0">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-red-500"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold tracking-tight text-gray-900" style="color: #0f172a !important;">Reject Campaign</h3>
                    <p class="text-[10px] font-semibold text-slate-400" style="color: #64748b !important;">Specify a reason for rejecting this ad</p>
                </div>
            </div>
            
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-gray-700 uppercase tracking-wider" style="color: #475569 !important;">Rejection Reason</label>
                <textarea id="rejection-reason-input" rows="3" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-500 text-gray-900 resize-none bg-white text-xs font-semibold leading-relaxed" placeholder="e.g. Image URL is invalid, description contains inappropriate content, etc."></textarea>
            </div>
            
            <div class="flex gap-3 w-full pt-2">
                <button onclick="closeRejectReasonModal()" class="w-1/2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-3 rounded-full border border-slate-200 uppercase tracking-wider text-xs transition-all cursor-pointer">
                    Cancel
                </button>
                <button onclick="submitRejection()" class="w-1/2 bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-full uppercase tracking-wider text-xs transition-all cursor-pointer shadow-lg shadow-red-600/20" style="background-color: #ef4444 !important; color: #ffffff !important;">
                    Confirm Reject
                </button>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        let pendingAdsList = [];
        let currentActiveAd = null;

        function fetchPendingAds() {
            const container = document.getElementById('pending-ads-list');
            container.innerHTML = `
                <div class="col-span-2 p-8 text-center text-slate-500 flex flex-col items-center justify-center gap-2">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
                    <span>Loading pending advertisements...</span>
                </div>
            `;

            fetch('api.php/api/ads?status=pending&t=' + new Date().getTime())
                .then(res => res.json())
                .then(ads => {
                    pendingAdsList = ads;
                    renderPendingAds();
                    updateNavbarBadge(ads.length);
                })
                .catch(err => {
                    console.error('Error fetching pending ads:', err);
                    container.innerHTML = `
                        <div class="col-span-2 p-8 text-center text-red-500 font-semibold">
                            Failed to load pending advertisements.
                        </div>
                    `;
                });
        }

        function updateNavbarBadge(count) {
            const badge = document.getElementById('nav-pending-badge');
            if (badge) {
                if (count > 0) {
                    badge.textContent = count;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            }
        }

        function filterPendingAdsByPage() {
            renderPendingAds();
        }

        function renderPendingAds() {
            const container = document.getElementById('pending-ads-list');
            if (!container) return;

            const pageSelect = document.getElementById('filterPendingPageSelect');
            const selectedPage = pageSelect ? pageSelect.value : 'All';

            let filteredAds = pendingAdsList;
            if (selectedPage !== 'All') {
                filteredAds = pendingAdsList.filter(ad => {
                    if (!ad.category) return false;
                    const cat = ad.category.toLowerCase().trim();
                    const target = selectedPage.toLowerCase().trim();
                    return cat === target || cat.replace(/\s+page$/, '') === target.replace(/\s+page$/, '');
                });
            }

            if (filteredAds.length === 0) {
                container.innerHTML = `
                    <div class="col-span-2 p-12 text-center text-slate-400 font-medium border border-dashed border-white/10 rounded-2xl flex flex-col items-center justify-center gap-3">
                        <i data-lucide="check-circle" class="w-12 h-12 text-green-400"></i>
                        <span class="text-sm">${pendingAdsList.length === 0 ? 'No pending approvals! All advertisements are currently processed.' : 'No pending advertisements found for the selected page filter.'}</span>
                    </div>
                `;
                lucide.createIcons();
                return;
            }

            container.innerHTML = filteredAds.map(ad => `
                <div class="border border-white/5 rounded-xl p-4 bg-white/5 flex flex-col gap-4 relative hover:bg-white/10 transition-colors">
                    <div class="flex gap-3 items-start">
                        <!-- Ad Thumbnail -->
                        <div class="w-16 h-16 rounded-lg overflow-hidden border border-white/10 bg-slate-950 shrink-0" style="aspect-ratio: 1/1;">
                            <img src="${ad.image}" onerror="this.src='https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=100&q=80'" class="w-full h-full object-contain">
                        </div>
                        
                        <!-- Ad Summary -->
                        <div class="flex-grow min-w-0">
                            <h4 class="text-sm font-bold text-white truncate">${ad.companyName}</h4>
                            <p class="text-[10px] text-slate-400 mt-1 leading-tight">${ad.description || 'No description provided.'}</p>
                            <div class="text-[9px] text-slate-500 mt-2 flex flex-wrap gap-x-3 gap-y-0.5">
                                <span>Category: <strong class="text-purple-400">${ad.category}</strong></span>
                                <span>${ad.month ? 'Post Month' : 'Views Limit'}: <strong class="text-white">${ad.month || ad.totalViews || ad.budget || 0}</strong></span>
                                <span>Posted By: <strong class="text-slate-300">${ad.postedBy || 'N/A'}</strong></span>
                            </div>
                        </div>
                    </div>

                    <!-- View Details Action Button -->
                    <div class="border-t border-white/5 pt-3 flex justify-end">
                        <button onclick="viewAdDetails('${ad.id}')" class="px-4 py-1.5 text-[10px] font-bold text-black bg-brand hover:bg-brand-light rounded-lg transition-all flex items-center gap-1 cursor-pointer" style="background-color: #dcfb00 !important;">
                            <i data-lucide="eye" class="w-3.5 h-3.5" style="color: #0b0f19 !important;"></i> View Details
                        </button>
                    </div>
                </div>
            `).join('');

            lucide.createIcons();
        }

        function viewAdDetails(id) {
            const ad = pendingAdsList.find(item => item.id == id);
            if (!ad) return;

            currentActiveAd = ad;

            // Load properties in detail fields
            document.getElementById('detailCompanyName').textContent = ad.companyName;
            document.getElementById('detailCategory').textContent = ad.category;
            const subCatEl = document.getElementById('detailSubCategory');
            if (subCatEl) subCatEl.textContent = (ad.subCategory && ad.subCategory !== '') ? ad.subCategory : 'All Categories';
            document.getElementById('detailPostedBy').textContent = ad.postedBy || 'N/A';
            if (ad.month) {
                document.getElementById('detailTotalViewsLabel').textContent = 'Post Month';
                document.getElementById('detailTotalViews').textContent = ad.month;
            } else {
                document.getElementById('detailTotalViewsLabel').textContent = 'Total Views';
                document.getElementById('detailTotalViews').textContent = ad.totalViews || ad.budget || 0;
            }
            
            // Calculate and display total budget
            const adBudget = parseFloat(ad.budget || 0);
            document.getElementById('detailTotalBudget').textContent = '₹' + adBudget.toFixed(2);

            document.getElementById('detailEmail').textContent = ad.email || 'N/A';
            document.getElementById('detailContactNumber').textContent = ad.contactNumber || 'N/A';
            document.getElementById('detailDescription').textContent = ad.description || 'No description.';
            
            const linkUrl = ad.url || '#';
            const linkTag = document.getElementById('detailDestinationUrl');
            linkTag.href = linkUrl;
            document.getElementById('detailDestinationUrlText').textContent = linkUrl;
            
            const imageUrl = ad.image || '#';
            const imageTag = document.getElementById('detailImageLinkUrl');
            imageTag.href = imageUrl;
            document.getElementById('detailImageLinkUrlText').textContent = imageUrl;

            // Build layout preview mirroring actual detail page ad layout
            const previewContainer = document.getElementById('ad-popup-preview');
            previewContainer.innerHTML = `
                <div class="relative w-full rounded-lg overflow-hidden border border-white/10 bg-slate-950/50" style="aspect-ratio: 1/1;">
                    <a href="${ad.url}" target="_blank" class="block w-full h-full">
                        <img src="${ad.image}" onerror="this.src='https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=300&q=80'" class="w-full h-full object-contain bg-slate-950">
                    </a>
                    <button type="button" class="absolute top-1.5 right-1.5 text-white transition-colors flex items-center justify-center bg-red-600 w-5 h-5 rounded-full border border-red-500/50 cursor-default pointer-events-none">
                        <i data-lucide="x" class="w-3 h-3 text-white" style="stroke: #ffffff !important;"></i>
                    </button>
                </div>
                
                <div class="flex flex-col gap-1">
                    <h4 class="font-extrabold text-sm tracking-tight truncate">${ad.companyName}</h4>
                    <p class="text-xs leading-relaxed" style="color: #cbd5e1 !important;">${ad.description || ''}</p>
                    ${ad.contactNumber ? `<p class="text-[10px] flex items-center gap-1 font-semibold mt-0.5" style="color: #cbd5e1 !important;"><i data-lucide="phone" class="w-3 h-3 shrink-0" style="color: #dcfb00 !important;"></i> ${ad.contactNumber}</p>` : ''}
                </div>
                
                <div class="flex justify-end mt-1">
                    <a href="${ad.url}" target="_blank" class="font-extrabold text-[9px] py-1 px-2.5 rounded-md uppercase tracking-wider flex items-center justify-center gap-0.5" style="background-color: #dcfb00 !important; color: #0b0f19 !important;">
                        ${ad.ctaLabel} <i data-lucide="arrow-up-right" class="w-2.5 h-2.5" style="color: #0b0f19 !important;"></i>
                    </a>
                </div>
            `;

            lucide.createIcons();
            document.getElementById('details-modal').classList.remove('hidden');
        }

        function closeDetailsModal() {
            document.getElementById('details-modal').classList.add('hidden');
            currentActiveAd = null;
        }

        function approveCurrentAd() {
            if (!currentActiveAd) return;

            fetch('api.php/api/ads/' + currentActiveAd.id, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: 'approved' })
            })
            .then(res => {
                if (!res.ok) {
                    return res.json().then(err => { throw new Error(err.message || err.error || 'Failed to approve advertisement') });
                }
                return res.json();
            })
            .then(data => {
                closeDetailsModal();
                showNotification('Advertisement approved successfully! It has been posted into live rotation.', 'Approved', 'check-circle', true);
                fetchPendingAds();
            })
            .catch(err => {
                console.error('Error approving ad:', err);
                showNotification(err.message || 'Failed to approve advertisement.', 'Error', 'x-circle', false);
            });
        }

        function rejectCurrentAd() {
            if (!currentActiveAd) return;

            document.getElementById('rejection-reason-input').value = '';
            document.getElementById('reject-reason-modal').classList.remove('hidden');
        }

        function closeRejectReasonModal() {
            document.getElementById('reject-reason-modal').classList.add('hidden');
        }

        function submitRejection() {
            if (!currentActiveAd) return;

            const reason = document.getElementById('rejection-reason-input').value.trim();

            fetch('api.php/api/ads/' + currentActiveAd.id, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    status: 'rejected',
                    rejectionReason: reason || 'Inappropriate or low quality content'
                })
            })
            .then(res => res.json())
            .then(data => {
                closeRejectReasonModal();
                closeDetailsModal();
                showNotification('Advertisement rejected and owner notified successfully.', 'Rejected', 'x-circle', true);
                fetchPendingAds();
            })
            .catch(err => {
                console.error('Error rejecting ad:', err);
                showNotification('Failed to reject advertisement.', 'Error', 'x-circle', false);
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
                innerIconBg.className = "w-16 h-16 rounded-full flex items-center justify-center border border-brand/20 shadow-lg relative z-10";
                innerIconBg.style.backgroundColor = "#dcfb00";
                iconEl.style.color = "#0b0f19";
                outerGlow.className = "absolute inset-0 rounded-full blur-xl bg-brand/30 opacity-70";
                outerGlow.style.backgroundColor = "rgba(220, 251, 0, 0.3)";
            } else {
                innerIconBg.className = "w-16 h-16 rounded-full flex items-center justify-center border border-red-300/20 shadow-lg relative z-10";
                innerIconBg.style.backgroundColor = "#ef4444";
                iconEl.style.color = "#ffffff";
                outerGlow.className = "absolute inset-0 rounded-full blur-xl bg-red-500/30 opacity-70";
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
                fetchSettings();
                fetchPendingAds();
            } else {
                document.getElementById('lock-passkey-error').classList.remove('hidden');
                document.getElementById('lock-passkey-input').value = '';
                document.getElementById('lock-passkey-input').focus();
            }
        }

        function cancelApprovals() {
            window.location.href = 'admin.php';
        }

        let settingsData = {
            single_view_budget: 10000,
            monthly_budgets: {}
        };

        function getUpcomingMonths() {
            const months = [];
            const date = new Date();
            for (let i = 0; i < 12; i++) {
                const m = date.toLocaleString('default', { month: 'long' });
                const y = date.getFullYear();
                months.push(`${m} ${y}`);
                date.setMonth(date.getMonth() + 1);
            }
            return months;
        }

        function populateBudgetMonths() {
            const select = document.getElementById('budget-month');
            const pageSelect = document.getElementById('budget-page');
            if (!select) return;
            select.innerHTML = '';
            const upcoming = getUpcomingMonths();
            upcoming.forEach(m => {
                const opt = document.createElement('option');
                opt.value = m;
                opt.textContent = m;
                select.appendChild(opt);
            });
            select.addEventListener('change', updateBudgetInputValue);
            if (pageSelect) {
                pageSelect.addEventListener('change', updateBudgetInputValue);
            }
        }

        function updateBudgetInputValue() {
            const select = document.getElementById('budget-month');
            const pageSelect = document.getElementById('budget-page');
            const input = document.getElementById('single-view-budget');
            if (!select || !input) return;

            const selectedMonth = select.value;
            const selectedPage = pageSelect ? pageSelect.value : 'All';
            const key = (selectedPage && selectedPage !== 'All') ? selectedMonth + '|' + selectedPage : selectedMonth;

            let budget = parseFloat(settingsData.single_view_budget || 10000);
            if (settingsData.monthly_budgets && settingsData.monthly_budgets[key] !== undefined) {
                budget = parseFloat(settingsData.monthly_budgets[key]);
            } else if (settingsData.monthly_budgets && settingsData.monthly_budgets[selectedMonth] !== undefined) {
                budget = parseFloat(settingsData.monthly_budgets[selectedMonth]);
            }
            input.value = budget.toFixed(2);
        }

        function fetchSettings() {
            fetch('api.php/api/settings?t=' + new Date().getTime())
                .then(res => res.json())
                .then(data => {
                    if (data) {
                        settingsData = data;
                    }
                    updateBudgetInputValue();
                })
                .catch(err => console.error('Error fetching settings:', err));
        }

        function saveSingleViewBudget() {
            const select = document.getElementById('budget-month');
            const pageSelect = document.getElementById('budget-page');
            const input = document.getElementById('single-view-budget');
            if (!select || !input) return;
            const selectedMonth = select.value;
            const selectedPage = pageSelect ? pageSelect.value : 'All';
            const key = (selectedPage && selectedPage !== 'All') ? selectedMonth + '|' + selectedPage : selectedMonth;
            const value = parseFloat(input.value);
            if (isNaN(value) || value < 0) {
                alert('Please enter a valid monthly budget.');
                return;
            }

            const btn = document.getElementById('saveBudgetBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = 'Saving...';
            btn.disabled = true;

            fetch('api.php/api/settings', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    month: key,
                    budget: value
                })
            })
            .then(res => res.json())
            .then(data => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                if (data) {
                    settingsData = data;
                    updateBudgetInputValue();
                    const pageText = selectedPage !== 'All' ? ' (' + selectedPage + ')' : '';
                    showNotification('Monthly advertising budget for ' + selectedMonth + pageText + ' updated to ₹' + value.toFixed(2) + ' successfully.', 'Updated', 'check-circle', true);
                }
            })
            .catch(err => {
                console.error('Error saving settings:', err);
                btn.innerHTML = originalText;
                btn.disabled = false;
                showNotification('Failed to update monthly advertising budget.', 'Error', 'x-circle', false);
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined' && lucide.createIcons) {
                lucide.createIcons();
            }
            populateBudgetMonths();
            
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
