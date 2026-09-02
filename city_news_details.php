<?php
// Handle AJAX database insertion at the very top of city_news_details.php before any other includes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'db_insert_news') {
    header('Content-Type: application/json');
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Verify passkey
    if (($_POST['passkey'] ?? '') !== 'MasterMind@1986') {
        echo json_encode(['success' => false, 'error' => 'Unauthorized: Invalid administrative passkey.']);
        exit;
    }
    
    // Extract and sanitize input parameters
    $title = trim($_POST['title'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $source = trim($_POST['source'] ?? 'Unknown');
    $date = trim($_POST['date'] ?? '');
    $image = trim($_POST['image'] ?? '');
    $url = trim($_POST['url'] ?? '');
    
    // Validate required fields
    if (empty($title) || empty($summary)) {
        echo json_encode(['success' => false, 'error' => 'Title and Summary are required fields.']);
        exit;
    }
    
    // Parse Date safely
    $dbDate = null;
    if (!empty($date)) {
        $dbDate = date('Y-m-d', strtotime($date));
    } else {
        $dbDate = date('Y-m-d');
    }
    
    // Include db connection using robust relative path checking
    $dbLoaded = false;
    $possiblePaths = [
        'config/db.php',
        '../config/db.php',
        '../../config/db.php',
        '../../../config/db.php'
    ];
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $dbLoaded = true;
            break;
        }
    }
    
    if (!$dbLoaded) {
        @include_once 'config/db.php';
        if (isset($conn) || isset($db) || isset($pdo)) {
            $dbLoaded = true;
        }
    }
    
    if (!$dbLoaded) {
        echo json_encode(['success' => false, 'error' => 'Database configuration file config/db.php not found.']);
        exit;
    }
    
    // Find the active database connection variable
    $connection = null;
    $connectionType = '';
    $varNames = ['conn', 'db', 'pdo', 'link', 'con', 'connect', 'dbh', 'dbConn', 'mysql', 'db_conn'];
    
    foreach ($varNames as $varName) {
        $candidate = null;
        if (isset($$varName)) {
            $candidate = $$varName;
        } elseif (isset($GLOBALS[$varName])) {
            $candidate = $GLOBALS[$varName];
        }
        
        if ($candidate !== null) {
            if ($candidate instanceof PDO) {
                $connection = $candidate;
                $connectionType = 'pdo';
                break;
            } elseif (class_exists('mysqli') && $candidate instanceof mysqli) {
                $connection = $candidate;
                $connectionType = 'mysqli';
                break;
            }
        }
    }
    
    if (!$connection) {
        echo json_encode(['success' => false, 'error' => 'No active database connection variable found in config/db.php.']);
        exit;
    }
    
    // Database Insert
    if ($connectionType === 'pdo') {
        try {
            // Auto-create city_news table if missing
            $connection->exec("CREATE TABLE IF NOT EXISTS city_news (
                id INT AUTO_INCREMENT PRIMARY KEY,
                newsTitle VARCHAR(255) NOT NULL,
                newsDescription TEXT NOT NULL,
                source VARCHAR(100),
                date VARCHAR(100),
                image VARCHAR(255),
                url TEXT,
                date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            $sql = "INSERT INTO city_news (
                newsTitle, newsDescription, source, date, image, url
            ) VALUES (
                :title, :summary, :source, :date_added, :image, :url
            )";
            
            $stmt = $connection->prepare($sql);
            $result = $stmt->execute([
                ':title' => $title,
                ':summary' => $summary,
                ':source' => $source,
                ':date_added' => $dbDate,
                ':image' => $image,
                ':url' => $url
            ]);
            
            if ($result) {
                echo json_encode(['success' => true]);
                exit;
            } else {
                $errorInfo = $stmt->errorInfo();
                echo json_encode(['success' => false, 'error' => 'PDO execution failed: ' . ($errorInfo[2] ?? 'Unknown error')]);
                exit;
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'Database error (PDO): ' . $e->getMessage()]);
            exit;
        }
    } else if ($connectionType === 'mysqli') {
        // Auto-create city_news table if missing
        $connection->query("CREATE TABLE IF NOT EXISTS city_news (
            id INT AUTO_INCREMENT PRIMARY KEY,
            newsTitle VARCHAR(255) NOT NULL,
            newsDescription TEXT NOT NULL,
            source VARCHAR(100),
            date VARCHAR(100),
            image VARCHAR(255),
            url TEXT,
            date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $sql = "INSERT INTO city_news (newsTitle, newsDescription, source, date, image, url) VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $connection->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssssss", $title, $summary, $source, $dbDate, $image, $url);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
                exit;
            } else {
                echo json_encode(['success' => false, 'error' => 'MySQLi execution failed: ' . $stmt->error]);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'MySQLi prepare failed: ' . $connection->error]);
            exit;
        }
    }
}

require_once 'postlog.php';
session_start();
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

// --- SEO-friendly slug support ---
if (!function_exists('slugify')) {
    function slugify(string $text): string {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = preg_replace('/\s+/', '-', $text);
        $text = preg_replace('/[^\w\-]+/', '', $text);
        $text = preg_replace('/\-+/', '-', $text);
        $text = trim($text, '-');
        return empty($text) ? 'news' : $text;
    }
}

$newsId   = isset($_GET['id'])   ? trim($_GET['id'])   : null;
$newsSlug = isset($_GET['slug']) ? trim($_GET['slug']) : null;

$canonHost = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'www.madras.city';
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') == 443 || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
$scheme = $isHttps ? 'https://' : 'http://';

$news = [];
$rawTitle = 'Chennai News Details';
$seoTitle = 'Chennai News Details | Madras.city';
$seoDescription = 'Latest news and updates from Chennai city.';
$seoImage = 'https://www.madras.city/assets/og-image.jpg';
$seoUrl = $scheme . $canonHost . '/city_news.php';

try {
    $newsFile = __DIR__ . '/news.json';
    if (file_exists($newsFile)) {
        $newsList = json_decode(file_get_contents($newsFile), true);
        if (is_array($newsList)) {
            $norm = function($str) {
                return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', (string)$str));
            };
            foreach ($newsList as $item) {
                // Match by ID (old style)
                if ($newsId !== null && isset($item['id']) && (string)$item['id'] === (string)$newsId) {
                    $news = $item;
                    break;
                }
                // Match by slug (new clean URL)
                if ($newsSlug !== null) {
                    $titleSlug = slugify($item['title'] ?? '');
                    if ($titleSlug === $newsSlug || (isset($item['id']) && (string)$item['id'] === $newsSlug) || (!empty($item['title']) && $norm($item['title']) === $norm($newsSlug))) {
                        $news = $item;
                        $newsId = $item['id'] ?? $newsSlug;
                        break;
                    }
                }
            }
        }
    }

    // Fallback for default news
    if (empty($news)) {
        $defaultNews = [
            "1" => [
                "id" => "1",
                "title" => "Chennai Metro Rail Phase 2 Project: Elevating Urban Transit Across OMR & Porur",
                "summary" => "The Tamil Nadu government has accelerated construction on Phase 2 of Chennai Metro Rail connecting major IT corridors and commercial hubs.",
                "image" => "chennai_news.png"
            ],
            "2" => [
                "id" => "2",
                "title" => "Parandur Greenfield Airport: Land Acquisition and Expressway Access Route Approved",
                "summary" => "The greenfield airport at Parandur gets final connectivity approvals to enhance Madras international air transit.",
                "image" => "chennai_news.png"
            ],
            "3" => [
                "id" => "3",
                "title" => "Chennai OMR IT Corridor Welcomes 5 New MNC Global Capability Centres",
                "summary" => "Chennai's IT corridor gets a major boost as five global technology companies expand engineering centers along OMR.",
                "image" => "chennai_news.png"
            ]
        ];
        if ($newsId !== null && isset($defaultNews[$newsId])) {
            $news = $defaultNews[$newsId];
        } elseif ($newsSlug !== null) {
            foreach ($defaultNews as $dn) {
                if (slugify($dn['title']) === $newsSlug) {
                    $news = $dn;
                    $newsId = $dn['id'];
                    break;
                }
            }
        }
    }

    if (!empty($news)) {
        if (!$newsId && isset($news['id'])) {
            $newsId = $news['id'];
        }
        $rawTitle = !empty($news['title']) ? $news['title'] : 'News Article Details';
        $itemSlug = slugify($rawTitle);
        $shortTitle = mb_strimwidth($rawTitle, 0, 42, '...');
        $seoTitle = $shortTitle . " | Madras.city";

        $seoDescription = mb_strimwidth(
            strip_tags($news['summary'] ?? ''),
            0,
            135,
            '...'
        );

        if (!empty($news['image'])) {
            $img = trim($news['image']);
            if (strpos($img, 'http://') === 0 || strpos($img, 'https://') === 0) {
                $seoImage = $img;
            } else {
                $seoImage = $scheme . $canonHost . '/' . ltrim($img, '/');
            }
        }

        // Clean canonical URL
        $seoUrl = $scheme . $canonHost . '/news-' . $itemSlug;
    }
} catch(Exception $e) {}

// Always force clean SEO URL in the browser
if ($newsId && empty($newsSlug) && !empty($news) && !empty($news['title'])) {
    $cleanSlug = slugify($news['title']);
    $cleanUrl  = $scheme . $canonHost . '/news-' . $cleanSlug;

    $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($currentPath !== '/news-' . $cleanSlug && $currentPath !== '/news-' . $cleanSlug . '/') {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $cleanUrl);
        exit;
    }
}

if (empty($news)) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($seoTitle) ?></title>

    <meta name="description" content="<?= htmlspecialchars($seoDescription) ?>">
    <meta name="keywords" content="Madras City, Chennai Events, Chennai Businesses, Madras News, Chennai Community, Madras Updates, Things to do in Chennai, Chennai Directory, Madras.city">
    <meta name="author" content="Madras.city">
    <meta name="robots" content="index, follow">

    <link rel="canonical" href="<?= htmlspecialchars($seoUrl) ?>">

    <!-- Open Graph Social Share Preview Meta Tags -->
    <meta property="og:type" content="article">
    <meta property="og:site_name" content="Madras.city">
    <meta property="og:title" content="<?= htmlspecialchars($rawTitle ?? $seoTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($seoDescription) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($seoUrl) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($seoImage) ?>">
    <meta property="og:image:secure_url" content="<?= htmlspecialchars($seoImage) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    <!-- Twitter Card Share Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($rawTitle ?? $seoTitle) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($seoDescription) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($seoImage) ?>">

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
        },
        {
          "@type": "ListItem",
          "position": 3,
          "name": "<?= addslashes(htmlspecialchars($news['title'] ?? 'Chennai News')) ?>",
          "item": "<?= addslashes(htmlspecialchars($seoUrl)) ?>"
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
      @keyframes scaleIn {
          from { opacity: 0; transform: scale(0.95); }
          to { opacity: 1; transform: scale(1); }
      }
      @keyframes slideUp {
          from { opacity: 0; transform: translateY(20px) scale(0.95); }
          to { opacity: 1; transform: translateY(0) scale(1); }
      }
      #site-sponsor-banner, #ad-popup {
          position: absolute;
          top: 1rem;
          right: 1rem;
          z-index: 50;
          width: 65% !important;
          max-width: 65% !important;
          border-color: rgba(220, 251, 0, 0.55) !important;
          box-shadow: 0 30px 70px rgba(0, 0, 0, 0.95), 0 0 30px rgba(220, 251, 0, 0.35) !important;
      }
      @media (max-width: 768px) {
          #site-sponsor-banner, #ad-popup {
              position: absolute !important;
              top: 0.5rem !important;
              right: 0.5rem !important;
              left: auto !important;
              width: 65% !important;
              min-width: 300px !important;
              max-width: calc(100vw - 1rem) !important;
              margin: 0 !important;
              z-index: 60 !important;
          }
      }
     </style>

    <?php if (!empty($news)): ?>
    <script type="application/ld+json">
    {
     "@context":"https://schema.org",
     "@type":"NewsArticle",
     "headline":"<?= addslashes($news['title'] ?? '') ?>",
     "description":"<?= addslashes(strip_tags($news['summary'] ?? '')) ?>",
     "image":"<?= addslashes($seoImage) ?>",
     "url":"<?= addslashes($seoUrl) ?>"
    }
    </script>
    <?php endif; ?>
</head>
<body class="min-h-screen flex flex-col relative overflow-x-hidden bg-theme-bg">
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-32 pb-12 w-full flex-grow relative z-10">
        <div id="news-container" class="glass-card">
            <div class="p-8 space-y-4">
                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider border" style="background-color: rgba(220, 251, 0, 0.15); color: #dcfb00; border-color: rgba(220, 251, 0, 0.3);">
                    Chennai Updates
                </span>
                <h1 class="text-3xl md:text-4xl font-extrabold text-white mt-4 tracking-tight"><?= htmlspecialchars($news['title'] ?? 'Chennai News') ?></h1>
                <p class="text-gray-300 text-sm leading-relaxed"><?= htmlspecialchars($seoDescription) ?></p>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        function sharePage() {
            const title = document.title;
            const url = window.location.href;
            if (navigator.share) {
                navigator.share({
                    title: title,
                    text: 'Check this out on Madras.city: ' + title,
                    url: url
                }).catch(err => console.error('Error sharing:', err));
            } else {
                navigator.clipboard.writeText(url).then(() => {
                    alert('Link copied to clipboard!');
                }).catch(err => {
                    console.error('Clipboard error:', err);
                });
            }
        }

        function safeCreateIcons() {
            if (typeof lucide !== 'undefined' && lucide.createIcons) {
                lucide.createIcons();
            }
        }

        const newsId = "<?php echo htmlspecialchars($newsId); ?>";
        
        function handleCtaAction(action) {
            const news = window.currentNews;
            if (!action) return;
            if (action === 'post') {
                alert('post_in_indieMa.in action: Prepare article for publishing');
            } else if (action === 'post-url') {
                if (news && news.url) {
                    navigator.clipboard.writeText(news.url);
                    window.open(news.url, '_blank');
                } else {
                    alert('No URL available for this article');
                }
            } else if (action === 'edit-url') {
                if (news) {
                    const currentUrl = news.url || '';
                    const newUrl = prompt('Edit URL for this news article:', currentUrl);
                    if (newUrl !== null) {
                        fetch('api.php/api/news/' + newsId, {
                            method: 'PATCH',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ url: newUrl.trim() })
                        })
                        .then(res => {
                            if (!res.ok) throw new Error('Failed to update URL');
                            return res.json();
                        })
                        .then(updatedNews => {
                            window.currentNews = updatedNews;
                            alert('URL updated successfully!');
                            location.reload();
                        })
                        .catch(err => alert('Error: ' + err.message));
                    }
                } else {
                    alert('News data not loaded yet.');
                }
            } else if (action === 'share') {
                if (news) {
                    const shareText = `Check out this article: ${news.title}`;
                    const shareUrl = window.location.href;
                    if (navigator.share) {
                        navigator.share({ title: news.title, text: shareText, url: shareUrl }).catch(err => console.log('Share error:', err));
                    } else {
                        navigator.clipboard.writeText(shareUrl);
                        alert('Share link copied to clipboard');
                    }
                }
            }
        }

        function initCustomCta() {
            const btn = document.getElementById('cta-button');
            const menu = document.getElementById('cta-menu');
            if (!btn || !menu) return;

            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                menu.classList.toggle('hidden');
            });

            // menu item clicks
            Array.from(menu.querySelectorAll('li[data-value]')).forEach(li => {
                li.addEventListener('click', function(e) {
                    const val = this.getAttribute('data-value');
                    menu.classList.add('hidden');
                    handleCtaAction(val);
                });
            });

            // close on outside click
            if (!window.ctaOutsideClickRegistered) {
                document.addEventListener('click', function() {
                    const m = document.getElementById('cta-menu');
                    if (m) m.classList.add('hidden');
                });
                window.ctaOutsideClickRegistered = true;
            }
        }

        function loadNewsDetails(id) {
            fetch('api.php/api/news/' + id + '?t=' + new Date().getTime())
                .then(res => {
                    if (!res.ok) throw new Error('News item not found');
                    return res.json();
                })
                .then(news => {
                    window.currentNews = news;
                    const title = news.title || 'Untitled News';
                    const date = news.date || 'Today';
                    const source = news.source || 'News';
                    const summary = news.summary || news.description || 'No summary available.';
                    const articleBody = news.content || news.description || summary;
                    document.title = title + ' - Madras.city';
                    
                    const newsImg = (news.image && news.image.trim() !== '') ? news.image : 'chennai_news.png';

                    const prefilledExplanation = news.aiExplanation || (summary ? `${summary}\n\nKeeping a pulse on the rapidly evolving urban landscape of Chennai requires constant attention to municipal announcements, infrastructure progress, and community affairs. Major regional reporting streams ensure that citizens stay aware of decisions made right in their neighborhoods.\n\nFor the people of Madras, remaining informed about these local stories directly affects daily routines, commute planning, and quality of life. Timely news updates allow residents to navigate around construction detours, prepare for temporary utility maintenance schedules, and understand upcoming changes to public amenities.` : title);

                    const paragraphsHtml = prefilledExplanation.split('\n\n')
                        .filter(p => p.trim() !== '')
                        .map(p => `<p class="mb-4 text-gray-300 leading-relaxed text-justify">${p.trim()}</p>`)
                        .join('');

                    document.getElementById('news-container').innerHTML = `
                        <!-- Header Banner Container -->
                        <div id="post-image-header" class="relative rounded-t-3xl overflow-hidden flex items-center justify-center hidden" style="height: 400px; background-color: #0b0f19 !important;">
                            <!-- Blurred Background Image -->
                            <div id="post-image-blur" class="absolute inset-0 bg-cover bg-center filter blur-xl opacity-40 transform scale-110 pointer-events-none" style="background-image: url('${newsImg}');"></div>
                            
                            <!-- Main Image -->
                            <img id="post-image-main" src="${newsImg}" onerror="this.src='chennai_news.png'" alt="${title}" class="max-w-full max-h-full w-auto h-auto object-contain relative z-10">
                            
                            <!-- Dark Gradient Overlay -->
                            <div class="absolute inset-0 bg-gradient-to-t pointer-events-none z-20" style="background: linear-gradient(to top, rgba(11, 15, 25, 0.95), rgba(11, 15, 25, 0.4) 60%, transparent) !important;"></div>
                            
                            <!-- Text overlay inside banner -->
                            <div class="absolute bottom-6 left-6 right-6 z-30">
                                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider border" style="background-color: rgba(220, 251, 0, 0.15) !important; color: #dcfb00 !important; border-color: rgba(220, 251, 0, 0.3) !important;">
                                    Chennai Updates
                                </span>
                                <h1 class="text-4xl font-extrabold text-white mt-4 tracking-tight drop-shadow-lg" style="color: #ffffff !important;">${title}</h1>
                            </div>
                        </div>

                        <!-- Normal Title Header (when image is hidden) -->
                        <div id="post-title-header" class="p-8 pb-0">
                            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider border" style="background-color: rgba(220, 251, 0, 0.15) !important; color: #0f172a !important; border-color: rgba(220, 251, 0, 0.3) !important;">
                                Chennai Updates
                            </span>
                            <h1 class="text-3xl font-extrabold text-[#0f172a] mt-4 tracking-tight">${title}</h1>
                        </div>
                        
                        <div class="p-8 grid grid-cols-1 md:grid-cols-3 gap-8">
                            <div class="md:col-span-2 space-y-8">
                                <div class="mt-0">
                                    <h2 class="text-2xl font-bold text-white mb-4 flex items-center gap-2" style="border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:1rem;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dcfb00" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                                        Detailed News Report
                                    </h2>
                                    <div id="detailed-content-body" class="text-gray-300 leading-loose text-base space-y-6">
                                        ${paragraphsHtml}
                                    </div>
                                </div>
                            </div>
                            
                            <div class="space-y-6">
                                <div class="bg-white/5 p-6 rounded-2xl border border-white/10 backdrop-blur-md">
                                    <h3 class="font-bold text-white mb-6 text-lg tracking-tight">Article Info</h3>
                                    <ul class="space-y-5">
                                        <li class="flex items-start text-gray-300 gap-3">
                                            <div class="mt-0.5 bg-brand/20 p-2 rounded-lg border border-brand/30">
                                                <i data-lucide="calendar" class="w-4 h-4 text-brand-light"></i>
                                            </div>
                                            <div>
                                                <div class="font-bold text-white">${date}</div>
                                                <div class="text-xs text-gray-500">Published Date</div>
                                            </div>
                                        </li>
                                        <li class="flex items-start text-gray-300 gap-3">
                                            <div class="mt-0.5 bg-brand/20 p-2 rounded-lg border border-brand/30">
                                                <i data-lucide="globe" class="w-4 h-4 text-brand-light"></i>
                                            </div>
                                            <div>
                                                <div class="font-bold text-white">${source}</div>
                                                <div class="text-xs text-gray-500">Source</div>
                                            </div>
                                        </li>
                                    </ul>
                                    
                                    <?php if ($isAdmin): ?>
                                    <!-- SELECT CTA Dropdown -->
                                    <div class="mt-6 pt-4 border-t border-white/10 relative">
                                        <button id="cta-button" class="w-full rounded-lg px-4 py-3 text-white text-sm font-bold flex items-center justify-between" style="background: linear-gradient(135deg,#7c3aed,#a855f7); box-shadow: 0 8px 30px rgba(124,58,237,0.18); border: none;">
                                            <span>SELECT CTA</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                        </button>
                                        <ul id="cta-menu" class="hidden absolute right-0 left-0 mt-2 rounded-lg shadow-lg overflow-hidden z-50" style="list-style:none;padding:0;margin:0;background: linear-gradient(135deg,#7c3aed,#a855f7); color:#ffffff;">
                                            <li data-value="post" class="px-4 py-3 cursor-pointer" style="color:#ffffff;" onmouseover="this.style.background='rgba(255,255,255,0.08)'" onmouseout="this.style.background='transparent'">post_in_indieMa.in</li>
                                            <li data-value="post-url" class="px-4 py-3 cursor-pointer" style="color:#ffffff;" onmouseover="this.style.background='rgba(255,255,255,0.08)'" onmouseout="this.style.background='transparent'">POST URL</li>
                                            <li data-value="edit-url" class="px-4 py-3 cursor-pointer" style="color:#ffffff;" onmouseover="this.style.background='rgba(255,255,255,0.08)'" onmouseout="this.style.background='transparent'">EDIT URL</li>
                                            <li data-value="share" class="px-4 py-3 cursor-pointer" style="color:#ffffff;" onmouseover="this.style.background='rgba(255,255,255,0.08)'" onmouseout="this.style.background='transparent'">SHARE</li>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Action Buttons -->
                                     <div class="mt-6 pt-4 border-t border-white/10 space-y-3">
                                         <!-- Toggle Image Button -->
                                         <button type="button" id="toggle-image-btn" onclick="togglePostImage()" class="w-full inline-flex items-center justify-center gap-2 rounded-full border font-bold py-3 px-6 uppercase tracking-wider text-xs cursor-pointer shadow-md transition-all" style="background:#000 !important; color:#dcfb00 !important; border-color:rgba(220,251,0,0.5) !important;">
                                             <i data-lucide="image" class="w-4 h-4" style="color:#dcfb00 !important;"></i>
                                             <span id="toggle-image-text">Show Image</span>
                                         </button>
                                         <button onclick="sharePage()" class="w-full inline-flex items-center justify-center gap-2 rounded-full font-bold py-3 px-6 uppercase tracking-wider text-xs transition-all duration-300 hover:scale-[1.02] active:scale-95 shadow-md shadow-brand/20 border border-brand/50 cursor-pointer animate-pulse" style="background-color: #dcfb00 !important; color: #0f172a !important;">
                                             <i data-lucide="share-2" class="w-4 h-4" style="color: #0f172a !important;"></i> Share This Article
                                         </button>
                                         <a href="city_news.php" class="w-full inline-flex items-center justify-center gap-2 rounded-full border border-white/20 bg-white/5 text-white font-bold py-3 px-6 uppercase tracking-wider text-xs transition-all duration-300 hover:bg-white/10">
                                             <i data-lucide="chevron-left" class="w-4 h-4 text-brand-light"></i> Back to News
                                         </a>
                                     </div>
                                </div>
                            </div>
                        </div>
                    `;
                    safeCreateIcons();
                    initCustomCta();
                    loadAds('City-news');
                    syncImageVisibilityState();

                    // Background async refresh only if aiExplanation was completely missing
                    if (!news.aiExplanation) {
                        fetch('api.php/api/explain', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                title: title,
                                summary: summary,
                                type: 'news',
                                id: id
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.explanation) {
                                const bodyEl = document.getElementById('detailed-content-body');
                                if (bodyEl) {
                                    const paragraphs = data.explanation.split('\n\n').filter(p => p.trim() !== '');
                                    bodyEl.innerHTML = paragraphs.map(p => `<p class="mb-4 text-gray-300 leading-relaxed text-justify">${p.trim()}</p>`).join('');
                                }
                            }
                        })
                        .catch(err => console.log('Background explanation sync skipped:', err));
                    }
                })
                .catch(err => {
                    console.error('Error loading news:', err);
                    document.getElementById('news-container').innerHTML = `
                        <div class="p-12 text-center text-red-400 font-bold text-xl flex flex-col items-center justify-center gap-4">
                            <i data-lucide="alert-triangle" class="w-12 h-12"></i>
                            Error loading news details or article not found.
                        </div>
                    `;
                    safeCreateIcons();
                });
        }

        function getDeviceType() {
            const ua = (navigator.userAgent || '').toLowerCase();
            const width = window.innerWidth || screen.width || 0;
            if (/(ipad|tablet|playbook|silk|kindle|(android(?!.*mobile)))/i.test(ua)) {
                return 'tablet';
            }
            if (/(mobile|iphone|ipod|android|blackberry|opera mini|windows phone|iemobile)/i.test(ua)) {
                return 'mobile';
            }
            if (width <= 640) {
                return 'mobile';
            }
            if (width > 640 && width <= 1024) {
                return 'tablet';
            }
            return 'desktop';
        }

        function loadAds(category) {
            const dev = getDeviceType();
            fetch('api.php/api/promotions?category=' + category + '&device=' + dev + '&t=' + new Date().getTime())
                .then(res => res.json())
                .then(ads => {
                    const container = document.getElementById('news-container');
                    if (!container || ads.length === 0) return;
                    
                    container.style.position = 'relative';
                    const ad = ads[Math.floor(Math.random() * ads.length)];
                    
                    const popup = document.createElement('div');
                    popup.id = 'ad-popup';
                    popup.className = 'glass-card p-4 sm:p-4.5 border border-[#dcfb00]/50 shadow-2xl flex flex-col gap-3 backdrop-blur-xl transition-all duration-300 rounded-2xl z-50';
                    
                    const ctaText = (ad.ctaLabel || 'VISIT SITE').toUpperCase();

                    popup.innerHTML = `
                        <div class="ad-img-box relative w-full rounded-xl overflow-hidden border border-white/10 bg-black/40 backdrop-blur-sm flex items-center justify-center cursor-pointer group" style="max-height: 310px !important;" onclick="openAdFullscreen('${ad.image}', '${(ad.companyName || '').replace(/'/g, "\\'")}', '${(ad.url || '#').replace(/'/g, "\\'")}', '${ad.id}')" title="Click to open ad in full screen">
                            <img src="${ad.image}" onerror="this.src='https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=400&q=80'" class="w-full h-full object-contain bg-transparent transition-transform duration-300 group-hover:scale-105" style="max-height: 310px !important;">
                            
                            <!-- Full Screen Button (Top Left) -->
                            <button type="button" onclick="event.stopPropagation(); openAdFullscreen('${ad.image}', '${(ad.companyName || '').replace(/'/g, "\\'")}', '${(ad.url || '#').replace(/'/g, "\\'")}', '${ad.id}')" class="absolute top-2 left-2 text-[#dcfb00] hover:text-white transition-all flex items-center justify-center bg-black/80 hover:bg-black w-8 h-8 rounded-full cursor-pointer border border-[#dcfb00]/50 shadow-md z-20" title="Full Screen">
                                <i data-lucide="maximize-2" class="w-4 h-4 text-[#dcfb00]" style="stroke: #dcfb00 !important;"></i>
                            </button>
                            
                            <!-- Close Button (Top Right - Solid Bright Red) -->
                            <button type="button" onclick="event.stopPropagation(); dismissAdBanner();" class="ad-close-btn absolute top-2 right-2 cursor-pointer shadow-lg z-20" style="background-color: #ef4444 !important; color: #ffffff !important; border: 2px solid #ffffff !important; width: 2.25rem !important; height: 2.25rem !important; border-radius: 9999px !important; display: flex !important; align-items: center !important; justify-content: center !important;" title="Close">
                                <i data-lucide="x" class="w-4 h-4 text-white" style="stroke: #ffffff !important; stroke-width: 3px !important;"></i>
                            </button>
                        </div>
                        
                        <div class="flex flex-col gap-1.5 px-0.5">
                            <h4 class="font-extrabold text-base sm:text-lg tracking-tight truncate" style="color: #dcfb00 !important;">${ad.companyName}</h4>
                            <p class="text-xs sm:text-sm leading-relaxed font-medium line-clamp-3" style="color: #f8fafc !important;">${ad.description || ''}</p>
                            ${ad.contactNumber ? `<p class="text-xs sm:text-sm flex items-center gap-1.5 font-semibold mt-0.5" style="color: #f8fafc !important;"><i data-lucide="phone" class="w-4 h-4 shrink-0" style="color: #dcfb00 !important;"></i> ${ad.contactNumber}</p>` : ''}
                        </div>
                        
                        <div class="flex justify-end mt-1">
                            <a href="${ad.url}" target="_blank" class="font-black text-xs sm:text-sm py-2.5 px-4 rounded-xl uppercase tracking-wider transition-all transform hover:scale-105 active:scale-95 flex items-center justify-center gap-1.5 shrink-0 shadow-lg" style="background-color: #dcfb00 !important; color: #0f172a !important;" onclick="fetch('api.php/api/promotions/${ad.id}/click', {method: 'POST', keepalive: true}).catch(e => console.error(e))">
                                ${ctaText} <i data-lucide="arrow-up-right" class="w-4 h-4" style="stroke: #0f172a !important;"></i>
                            </a>
                        </div>
                    `;
                    
                    container.appendChild(popup);
                    safeCreateIcons();
                })
                .catch(err => console.error('Error loading ads:', err));
        }

        function dismissAdBanner() {
            const banner = document.getElementById('ad-popup') || document.getElementById('site-sponsor-banner');
            if (banner) {
                banner.style.transition = 'all 0.25s cubic-bezier(0.4, 0, 0.2, 1)';
                banner.style.opacity = '0';
                banner.style.transform = 'scale(0.8) translateY(-10px)';
                setTimeout(() => { if (banner && banner.parentNode) banner.remove(); }, 250);
            }
        }

        if (!window.adBlankSpaceClickRegistered) {
            document.addEventListener('click', function(e) {
                const banner = document.getElementById('ad-popup') || document.getElementById('site-sponsor-banner');
                if (!banner) return;
                if (e.target.closest('a') || e.target.closest('button')) return;
                dismissAdBanner();
            }, true);
            window.adBlankSpaceClickRegistered = true;
        }

        function syncImageVisibilityState() {
            const isVisible = localStorage.getItem('showPostImages') !== 'false';
            const headerImg = document.getElementById('post-image-header');
            const titleHeader = document.getElementById('post-title-header');
            const btnText = document.getElementById('toggle-image-text');
            const btnIcon = document.getElementById('toggle-image-btn') ? document.getElementById('toggle-image-btn').querySelector('i') : null;
            
            if (!headerImg) return;
            
            if (isVisible) {
                headerImg.classList.remove('hidden');
                if (titleHeader) titleHeader.classList.add('hidden');
                if (btnText) btnText.textContent = 'Hide Image';
                if (btnIcon) {
                    btnIcon.setAttribute('data-lucide', 'image-off');
                }
            } else {
                headerImg.classList.add('hidden');
                if (titleHeader) titleHeader.classList.remove('hidden');
                if (btnText) btnText.textContent = 'Show Image';
                if (btnIcon) {
                    btnIcon.setAttribute('data-lucide', 'image');
                }
            }
            if (typeof lucide !== 'undefined' && lucide.createIcons) {
                lucide.createIcons();
            }
        }

        function togglePostImage() {
            const isVisible = localStorage.getItem('showPostImages') !== 'false';
            localStorage.setItem('showPostImages', (!isVisible).toString());
            syncImageVisibilityState();
        }


        // Call news details loader
        loadNewsDetails(newsId);
    </script>

    <script>
      if (typeof lucide !== 'undefined' && lucide.createIcons) {
          lucide.createIcons();
      }
    </script>


</body>
</html>