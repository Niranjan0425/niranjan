<?php
if (isset($_SERVER['HTTP_HOST']) && strtolower($_SERVER['HTTP_HOST']) === 'madras.city') {
    header('Location: https://www.madras.city' . ($_SERVER['REQUEST_URI'] ?? ''), true, 301);
    exit;
}
require_once 'postlog.php';
session_start();
?>
<?php
$canonHost = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'www.madras.city';
$canonUri = $_SERVER['REQUEST_URI'] ?? '/city_news.php';
$canonicalUrl = 'https://' . $canonHost . $canonUri;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chennai News & City Updates | Madras Guide</title>

    <meta name="description" content="Read latest news, civic project updates, business announcements, and city developments in Chennai with Madras.city.">
    <meta name="keywords" content="Madras City, Chennai Events, Chennai Businesses, Madras News, Chennai Community, Madras Updates, Things to do in Chennai, Chennai Directory, Madras.city">
    <meta name="author" content="Madras.city">
    <meta name="robots" content="index, follow">

    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">

    <meta property="og:type" content="website">
    <meta property="og:title" content="Chennai News & City Updates | Madras.city">
    <meta property="og:description" content="Stay updated with the latest happenings, news, and developments in Chennai with Madras.city.">
    <meta property="og:url" content="https://www.madras.city/city_news.php">
    <meta property="og:site_name" content="Madras.city">
    <meta property="og:image" content="https://www.madras.city/assets/og-image.jpg">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Chennai News & City Updates | Madras.city">
    <meta name="twitter:description" content="Stay updated with the latest happenings, news, and developments in Chennai with Madras.city.">
    <meta name="twitter:image" content="https://www.madras.city/assets/og-image.jpg">

    <link rel="icon" type="image/png" href="logo.png">
    <link rel="apple-touch-icon" sizes="180x180" href="https://www.madras.city/logo.png">
    <link rel="apple-touch-icon" href="https://www.madras.city/logo.png">
    <link rel="apple-touch-icon-precomposed" href="https://www.madras.city/logo.png">

    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": [
        {
          "@type": "ListItem",
          "position": 1,
          "name": "Home",
          "item": "https://www.madras.city/"
        },
        {
          "@type": "ListItem",
          "position": 2,
          "name": "News",
          "item": "https://www.madras.city/city_news.php"
        }
      ]
    }
    </script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
      .lucide { width: 24px; height: 24px; }
      .w-4 { width: 16px; } .h-4 { height: 16px; }
      .w-5 { width: 20px; } .h-5 { height: 20px; }
      .w-10 { width: 40px; } .h-10 { height: 40px; }
      .loader-spinner {
          width: 64px;
          height: 64px;
          border: 4px solid var(--color-brand-light);
          border-top-color: transparent;
          border-radius: 50%;
          animation: spin 1s linear infinite;
          margin-bottom: 1rem;
      }
      @keyframes spin {
          0% { transform: rotate(0deg); }
          100% { transform: rotate(360deg); }
      }
      @keyframes slideUp {
          from { opacity: 0; transform: translateY(24px) scale(0.97); }
          to   { opacity: 1; transform: translateY(0)    scale(1);    }
      }
    </style>
</head>
<body class="min-h-screen flex flex-col relative overflow-x-hidden bg-theme-bg">
    
    <?php include 'navbar.php'; ?>

    <main class="flex-grow pt-32 pb-20 relative z-10">
        <div id="news-page-container" class="max-w-7xl mx-auto px-6 relative">
            <!-- Search & Filter Header -->
            <div class="space-y-8 mb-12">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <img src="chennai_news.png" alt="Chennai News Logo" class="h-16 w-auto object-contain shrink-0">
                        <div>
                            <h1 class="text-3xl md:text-4xl font-bold text-white mb-1">Chennai News & City Updates</h1>
                            <p class="text-gray-400 uppercase text-xs tracking-widest font-bold">All the latest Chennai headlines and local updates in one place</p>
                        </div>
                    </div>
                    <a href="index.php" class="inline-flex items-center gap-2 text-brand-light hover:text-white transition-colors font-bold text-xs uppercase tracking-widest">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Home
                    </a>
                </div>

                <div class="flex flex-col lg:flex-row gap-6">
                    <!-- Search Bar -->
                    <div class="relative flex-grow group">
                        <i data-lucide="search" class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-500 group-focus-within:text-brand-light transition-colors"></i>
                        <input
                            type="text"
                            id="search-input"
                            placeholder="Search latest Chennai updates by headlines, summaries, or sources..."
                            class="w-full bg-theme-bg-dark/50 border border-white/5 rounded-2xl pl-12 pr-4 py-4 focus:outline-none focus:border-brand/50 focus:ring-4 focus:ring-brand/10 shadow-[inner_0_2px_4px_rgba(0,0,0,0.3)] transition-all text-white placeholder-gray-600"
                        >
                    </div>

                    <div class="flex flex-wrap gap-4">
                        <div class="glass-card flex items-center px-4 border-white/5 hover:border-brand/20 transition-colors">
                            <i data-lucide="sliders-horizontal" class="w-4 h-4 text-gray-500 mr-2"></i>
                            <select id="sort-select" class="bg-transparent text-sm text-gray-300 focus:outline-none py-3 cursor-pointer">
                                <option value="recently-added" class="bg-theme-bg-dark text-white">Sort: Recently Added</option>
                            </select>
                        </div>

                        <div class="glass-card flex items-center p-1 border-white/5 gap-1">
                            <button id="btn-grid" class="p-2 rounded-xl transition-all bg-brand text-slate-950 shadow-[0_0_10px_rgba(220,251,0,0.4)]">
                                <i data-lucide="layout-grid" class="w-5 h-5"></i>
                            </button>
                            <button id="btn-list" class="p-2 rounded-xl transition-all text-gray-500 hover:text-gray-700">
                                <i data-lucide="list" class="w-5 h-5"></i>
                            </button>
                            <div class="h-6 w-px bg-gray-300/20 mx-1"></div>
                            <!-- Hide/Show Image Button -->
                            <button id="btn-toggle-images" onclick="toggleImagesVisibility()" class="p-2 rounded-xl transition-all flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider cursor-pointer" style="color:#dcfb00;">
                                <span id="toggle-images-icon-container">
                                    <i data-lucide="image" class="w-4 h-4" style="color:#dcfb00;"></i>
                                </span>
                                <span id="toggle-images-text">Hide Images</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div id="content-area">
                <!-- Loading State (shown by default) -->
                <div id="loading-state" class="flex flex-col items-center justify-center py-32">
                    <div class="loader-spinner"></div>
                    <span class="text-gray-500 tracking-widest uppercase font-bold text-xs">Fetching local news...</span>
                </div>
                
                <!-- Results Grid (hidden by default) -->
                <div id="results-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 hidden">
                    <!-- News cards will be populated here -->
                </div>
                
                <!-- Empty State (hidden by default) -->
                <div id="empty-state" class="text-center py-32 glass-card border-dashed border-white/10 hidden">
                    <div class="w-20 h-20 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i data-lucide="newspaper" class="w-10 h-10 text-gray-700"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-2">No news updates found</h3>
                    <p class="text-gray-500">Try adjusting your search terms or filters.</p>
                    <button id="btn-clear" class="mt-8 text-brand-light font-bold border-b border-brand-light hover:pb-1 transition-all">
                        Clear Search
                    </button>
                </div>
            </div>

            <!-- Server-Rendered Chennai News Guide Section -->
            <div class="mt-20 pt-16 border-t border-white/10 space-y-12">
                <div class="max-w-4xl space-y-4">
                    <span class="text-brand-light font-bold text-xs uppercase tracking-widest block">City News Intelligence</span>
                    <h2 class="text-2xl md:text-4xl font-extrabold text-white">Chennai News & Civic Infrastructure Updates</h2>
                    <p class="text-gray-400 text-sm md:text-base leading-relaxed text-justify">
                        Stay connected with real-time news reporting and civic developments across Chennai (Madras). As Tamil Nadu's capital and South India's economic engine, Chennai undergoes constant infrastructure expansion including the Chennai Metro Rail Phase 2 network, Port-Maduravoyal elevated corridor, Parandur Greenfield Airport project, and OMR IT corridor upgrades.
                    </p>
                    <p class="text-gray-400 text-sm md:text-base leading-relaxed text-justify">
                        Madras.city curates verified news announcements, public notices, business inaugurations, tech park developments along OMR & Guindy, educational achievements across IIT Madras and Anna University, and civic administration updates. Get reliable local insights delivered with speed and clarity for citizens, entrepreneurs, and students.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-xs text-gray-400">
                    <div class="glass-card p-6 space-y-2">
                        <h3 class="text-base font-bold text-white flex items-center gap-2">
                            <i data-lucide="building-2" class="w-4 h-4 text-brand-light"></i> Infrastructure & Metro Rail
                        </h3>
                        <p class="leading-relaxed">Track progress on urban transit lines, flyovers, storm-water drains, and smart city developments across Chennai metro area.</p>
                    </div>

                    <div class="glass-card p-6 space-y-2">
                        <h3 class="text-base font-bold text-white flex items-center gap-2">
                            <i data-lucide="trending-up" class="w-4 h-4 text-brand-light"></i> Business & Industrial Growth
                        </h3>
                        <p class="leading-relaxed">Read about automotive plant investments, IT software park expansions, SaaS startup funding, and port trade in Chennai.</p>
                    </div>

                    <div class="glass-card p-6 space-y-2">
                        <h3 class="text-base font-bold text-white flex items-center gap-2">
                            <i data-lucide="graduation-cap" class="w-4 h-4 text-brand-light"></i> Education & Community News
                        </h3>
                        <p class="leading-relaxed">Stay updated on university research breakthroughs, school announcements, civic achievements, and community initiatives.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <script>
        function slugify(text) {
            return (text || '')
                .toString()
                .toLowerCase()
                .trim()
                .replace(/\s+/g, '-')
                .replace(/[^\w\-]+/g, '')
                .replace(/\-\-+/g, '-')
                .replace(/^-+/, '')
                .replace(/-+$/, '') || 'news';
        }

        function sharePost(eventObj, title, path) {
            if (eventObj) {
                eventObj.stopPropagation();
                eventObj.preventDefault();
            }
            const url = (path.startsWith('http://') || path.startsWith('https://'))
                ? path
                : window.location.origin + '/' + path;
            if (navigator.share) {
                navigator.share({
                    title: title,
                    text: title + ' - Check this out on Madras.city',
                    url: url
                }).catch(err => console.error('Error sharing:', err));
            } else {
                navigator.clipboard.writeText(url).then(() => {
                    alert('Link copied to clipboard!\n' + url);
                }).catch(err => {
                    console.error('Clipboard error:', err);
                });
            }
        }

        let currentView = 'grid';
        let allNews = [];

        const elements = {
            search: document.getElementById('search-input'),
            sort: document.getElementById('sort-select'),
            btnGrid: document.getElementById('btn-grid'),
            btnList: document.getElementById('btn-list'),
            btnClear: document.getElementById('btn-clear'),
            loading: document.getElementById('loading-state'),
            results: document.getElementById('results-container'),
            empty: document.getElementById('empty-state')
        };

        function setViewMode(mode) {
            currentView = mode;
            if (mode === 'grid') {
                elements.btnGrid.className = 'p-2 rounded-xl transition-all bg-brand text-slate-950 shadow-[0_0_10px_rgba(220,251,0,0.4)]';
                elements.btnList.className = 'p-2 rounded-xl transition-all text-gray-500 hover:text-gray-700';
                elements.results.className = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8';
            } else {
                elements.btnList.className = 'p-2 rounded-xl transition-all bg-brand text-slate-950 shadow-[0_0_10px_rgba(220,251,0,0.4)]';
                elements.btnGrid.className = 'p-2 rounded-xl transition-all text-gray-500 hover:text-gray-700';
                elements.results.className = 'space-y-6';
            }
            renderNews(filterAndSortNews(allNews));
        }

        function safeCreateIcons() {
            if (typeof lucide !== 'undefined' && lucide.createIcons) {
                lucide.createIcons();
            }
        }

        function fetchNews() {
            elements.loading.classList.remove('hidden');
            elements.results.classList.add('hidden');
            elements.empty.classList.add('hidden');

            const pathUrl = 'api.php/api/news?t=' + new Date().getTime();
            const fallbackUrl = 'api.php?action=get_news&t=' + new Date().getTime();

            fetch(pathUrl)
                .then(res => {
                    if (!res.ok) {
                        throw new Error('Path-style endpoint returned non-OK status');
                    }
                    return res.json();
                })
                .then(data => {
                    if (!Array.isArray(data)) {
                        throw new TypeError('Data is not an array');
                    }
                    allNews = deduplicateNews(data);
                    renderNews(filterAndSortNews(allNews));
                })
                .catch(err => {
                    console.warn('Path-style fetch failed, trying query parameter fallback:', err);
                    fetch(fallbackUrl)
                        .then(res => {
                            if (!res.ok) throw new Error('Query-style fallback also failed');
                            return res.json();
                        })
                        .then(data => {
                            if (!Array.isArray(data)) {
                                throw new TypeError('Fallback data is not an array');
                            }
                            allNews = deduplicateNews(data);
                            renderNews(filterAndSortNews(allNews));
                        })
                        .catch(fallbackErr => {
                            console.error('All fetch attempts failed:', fallbackErr);
                            elements.loading.classList.add('hidden');
                            elements.empty.classList.remove('hidden');
                        });
                });
        }

        function deduplicateNews(newsList) {
            const seen = new Set();
            return newsList.filter(item => {
                const key = (item.title || '').toLowerCase().trim();
                if (!key || seen.has(key)) return false;
                seen.add(key);
                return true;
            });
        }

        function filterAndSortNews(newsList) {
            let result = [...newsList];
            
            const searchTerm = elements.search.value.toLowerCase().trim();
            if (searchTerm) {
                result = result.filter(n => 
                    (n.title || '').toLowerCase().includes(searchTerm) || 
                    (n.summary || '').toLowerCase().includes(searchTerm) ||
                    (n.source || '').toLowerCase().includes(searchTerm)
                );
            }

            const sort = elements.sort.value;
            const parseDate = (d, defaultTime = 0) => {
                if (!d || d === 'TBA') return defaultTime;
                const parsed = Date.parse(d);
                return isNaN(parsed) ? defaultTime : parsed;
            };

            if (sort === 'latest') {
                result.sort((a, b) => {
                    const timeA = parseDate(a.date, 0);
                    const timeB = parseDate(b.date, 0);
                    if (timeB !== timeA) return timeB - timeA;
                    return parseInt(b.id || 0, 10) - parseInt(a.id || 0, 10);
                });
            } else if (sort === 'recently-added') {
                result.sort((a, b) => parseInt(b.id || 0, 10) - parseInt(a.id || 0, 10));
            }

            return result;
        }

        function renderNews(newsList) {
            elements.loading.classList.add('hidden');
            
            if (newsList.length === 0) {
                elements.results.classList.add('hidden');
                elements.empty.classList.remove('hidden');
                return;
            }

            elements.empty.classList.add('hidden');
            elements.results.classList.remove('hidden');

            elements.results.innerHTML = newsList.map((item, i) => {
                const itemSlug = slugify(item.title || 'news');
const detailLink = 'news-' + itemSlug;
                const safeTitle = (item.title || 'News').replace(/'/g, "\\'");

                if (currentView === 'grid') {
                    return `
                        <div class="glass-card overflow-hidden hover:-translate-y-2 transition-transform duration-300 group flex flex-col justify-between h-full relative" style="animation: fadeUp 0.5s ease forwards ${i * 0.05}s; opacity: 0; transform: translateY(20px);">
                            <button onclick="sharePost(event, '${safeTitle}', '${detailLink}')" class="absolute top-4 right-4 z-30 p-2 rounded-full border border-[#a9c200]/30 shadow-lg transition-all cursor-pointer hover:scale-110 active:scale-95" style="background-color: #dcfb00 !important; color: #0f172a !important;" title="Share News">
                                <i data-lucide="share-2" class="w-4 h-4 text-slate-950" style="stroke: #0f172a !important;"></i>
                            </button>
                            <div>
                                <a href="${detailLink}" class="block relative h-48 overflow-hidden bg-slate-950 ${showPostImages ? '' : 'hidden'}">
                                    <img src="${item.image}" onerror="this.src='chennai_news.png'" alt="${item.title}" class="w-full h-full object-contain p-2 transition-transform duration-500 group-hover:scale-105">
                                    <div class="absolute top-4 right-14 bg-purple-500/80 backdrop-blur-md px-3 py-1 rounded-full border border-purple-400/20 text-xs font-bold text-white uppercase tracking-wider" style="font-size: 9px;">
                                        ${item.source || 'News'}
                                    </div>
                                </a>
                                <div class="p-6">
                                    <div class="flex items-center gap-2 text-brand-light text-xs font-bold uppercase tracking-wider mb-3">
                                        <i data-lucide="calendar" class="w-3 h-3 text-brand-light"></i> ${item.date || 'Today'}
                                    </div>
                                    <h3 class="text-xl font-bold text-white mb-2 line-clamp-2 hover:text-brand-light transition-colors">${item.title}</h3>
                                    <p class="text-gray-400 text-sm line-clamp-4 leading-relaxed">${item.summary || ''}</p>
                                </div>
                            </div>
                            <div class="px-6 pb-6 pt-4 border-t border-white/5 flex items-center justify-between">
                                <span class="text-xs text-gray-500">Chennai Updates</span>
                                ${item.id ? `
                                    <a href="${detailLink}" class="text-brand hover:text-brand-light text-sm font-bold flex items-center gap-1 transition-colors">
                                        Read News Article <i data-lucide="arrow-right" class="w-4 h-4"></i>
                                    </a>
                                ` : ''}
                            </div>
                        </div>
                    `;
                } else {
                    return `
                        <div class="glass-card flex flex-col md:flex-row overflow-hidden border-white/5 hover:border-brand/30 transition-colors group relative" style="animation: fadeUp 0.5s ease forwards ${i * 0.05}s; opacity: 0; transform: translateY(20px);">
                            <button onclick="sharePost(event, '${safeTitle}', '${detailLink}')" class="absolute top-4 right-4 z-30 p-2 rounded-full border border-[#a9c200]/30 shadow-lg transition-all cursor-pointer hover:scale-110 active:scale-95" style="background-color: #dcfb00 !important; color: #0f172a !important;" title="Share News">
                                <i data-lucide="share-2" class="w-4 h-4 text-slate-950" style="stroke: #0f172a !important;"></i>
                            </button>
                            <a href="${detailLink}" class="block w-full md:w-48 h-48 md:h-auto overflow-hidden shrink-0 bg-slate-950 ${showPostImages ? '' : 'hidden'}">
                                <img src="${item.image}" onerror="this.src='chennai_news.png'" alt="${item.title}" class="w-full h-full object-contain p-2 group-hover:scale-105 transition-transform duration-500">
                            </a>
                            <div class="p-6 flex-grow flex flex-col justify-between">
                                <div>
                                    <div class="flex gap-4 text-xs text-brand-light font-bold mb-2 uppercase tracking-widest relative">
                                        <span>${item.source || 'News'}</span>
                                        <span class="text-white/20">|</span>
                                        <span>${item.date || 'Today'}</span>
                                    </div>
                                    <h3 class="text-2xl font-bold text-white mb-2 group-hover:text-brand-light transition-colors">${item.title}</h3>
                                    <p class="text-gray-400 text-sm line-clamp-2">${item.summary || ''}</p>
                                </div>
                                <div class="pt-4 flex items-center justify-between mt-4 md:mt-0 border-t border-white/5">
                                    <a href="${detailLink}" class="text-brand hover:text-brand-light text-sm font-bold flex items-center gap-1 transition-colors">
                                        Read Full Story <i data-lucide="arrow-right" class="w-4 h-4"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    `;
                }
            }).join('');
            
            safeCreateIcons();
        }

        elements.search.addEventListener('input', () => renderNews(filterAndSortNews(allNews)));
        elements.sort.addEventListener('change', () => renderNews(filterAndSortNews(allNews)));
        elements.btnGrid.addEventListener('click', () => setViewMode('grid'));
        elements.btnList.addEventListener('click', () => setViewMode('list'));
        elements.btnClear.addEventListener('click', () => {
            elements.search.value = '';
            elements.sort.value = 'recently-added';
            renderNews(filterAndSortNews(allNews));
        });

        let showPostImages = localStorage.getItem('showPostImages') !== 'false';
        function toggleImagesVisibility() {
            showPostImages = !showPostImages;
            localStorage.setItem('showPostImages', showPostImages.toString());
            updateToggleImagesButton();
            renderNews(filterAndSortNews(allNews));
        }

        function updateToggleImagesButton() {
            const btn = document.getElementById('btn-toggle-images');
            const text = document.getElementById('toggle-images-text');
            const iconContainer = document.getElementById('toggle-images-icon-container');
            
            if (showPostImages) {
                btn.className = 'p-2 rounded-xl transition-all flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider cursor-pointer';
                btn.style.cssText = 'background-color:#dcfb00 !important; color:#0f172a !important; box-shadow:0 0 10px rgba(220,251,0,0.4);';
                if (iconContainer) {
                    iconContainer.innerHTML = '<i data-lucide="image" class="w-4 h-4" style="color:#0f172a !important; stroke:#0f172a !important;"></i>';
                }
                text.textContent = 'Hide Images';
            } else {
                btn.className = 'p-2 rounded-xl transition-all flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider cursor-pointer';
                btn.style.cssText = 'background-color:transparent !important; color:#dcfb00 !important; border:1px solid rgba(220,251,0,0.4) !important;';
                if (iconContainer) {
                    iconContainer.innerHTML = '<i data-lucide="image-off" class="w-4 h-4" style="color:#dcfb00 !important; stroke:#dcfb00 !important;"></i>';
                }
                text.textContent = 'Show Images';
            }
            safeCreateIcons();
        }

        // Initial fetch
        fetchNews();
        updateToggleImagesButton();
    </script>
    <style>
        @keyframes fadeUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</body>
            }
        }
    </style>
</body>
</html>