<?php
if (isset($_SERVER['HTTP_HOST']) && strtolower($_SERVER['HTTP_HOST']) === 'madras.city') {
    header('Location: https://www.madras.city' . ($_SERVER['REQUEST_URI'] ?? ''), true, 301);
    exit;
}
require_once 'postlog.php';
// about.php
session_start();
?>
<?php
$canonHost = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'www.madras.city';
$canonUri = $_SERVER['REQUEST_URI'] ?? '/about.php';
$canonicalUrl = 'https://' . $canonHost . $canonUri;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Madras - Chennai City Local Guide</title>

    <meta name="description" content="Explore events, local businesses, city updates, job opportunities, and community activities in Chennai with Madras.city.">
    <meta name="keywords" content="Madras City, Chennai Events, Chennai Businesses, Madras News, Chennai Community, Madras Updates, Things to do in Chennai, Chennai Directory, Madras.city">
    <meta name="author" content="Madras.city">
    <meta name="robots" content="index, follow">

    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Madras.city">
    <meta property="og:title" content="About Madras.city - Chennai Local Guide">
    <meta property="og:description" content="Explore events, local businesses, city updates, job opportunities, and community activities in Chennai with Madras.city.">
    <meta property="og:url" content="https://www.madras.city/about.php">
    <meta property="og:image" content="https://www.madras.city/assets/og-image.jpg">
    <meta property="og:locale" content="en_IN">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="About Madras.city - Chennai Local Guide">
    <meta name="twitter:description" content="Explore events, local businesses, city updates, job opportunities, and community activities happening across Chennai.">
    <meta name="twitter:image" content="https://www.madras.city/assets/og-image.jpg">

    <link rel="icon" type="image/png" href="logo.png">
    <link rel="apple-touch-icon" sizes="180x180" href="https://www.madras.city/logo.png">
    <link rel="apple-touch-icon" href="https://www.madras.city/logo.png">
    <link rel="apple-touch-icon-precomposed" href="https://www.madras.city/logo.png">

    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "AboutPage",
      "name": "About Madras.city",
      "url": "https://www.madras.city/about.php",
      "description": "Madras.city is Chennai's premier city intelligence and event discovery platform."
    }
    </script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
      .lucide { width: 24px; height: 24px; }
      .w-4 { width: 16px; } .h-4 { height: 16px; }
      .w-5 { width: 20px; } .h-5 { height: 20px; }
      .w-6 { width: 24px; } .h-6 { height: 24px; }
      .w-8 { width: 32px; } .h-8 { height: 32px; }
    </style>
</head>
<body class="min-h-screen flex flex-col relative overflow-x-hidden bg-theme-bg">
    
    <?php include 'navbar.php'; ?>

    <!-- Main Content -->
    <main class="flex-grow pt-32 pb-20 relative z-10">
        <div class="max-w-5xl mx-auto px-6">
            
            <!-- Hero Header -->
            <div class="space-y-6 text-center max-w-4xl mx-auto mb-16">
                <span class="text-brand-light font-bold text-xs uppercase tracking-widest block">About Madras.city</span>
                <h1 class="text-3xl md:text-5xl font-extrabold text-white leading-tight">
                    About Madras.city - Chennai's Local Guide for Events, News & Discoveries
                </h1>
                <p class="text-gray-300 text-base md:text-lg leading-relaxed mt-4">
                    Madras.city is the definitive digital intelligence hub and community guide for Chennai (Madras). We bring together local events, civic updates, business promotions, job opportunities, and city spotlights into one seamless, modern platform.
                </p>
            </div>

            <!-- Welcome & Platform Overview Section -->
            <div class="glass-card p-8 md:p-12 mb-16 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-[300px] h-[300px] rounded-full blur-[100px] pointer-events-none" style="background-color: rgba(220, 251, 0, 0.08);"></div>
                <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                    <div class="lg:col-span-7 space-y-6">
                        <h2 class="text-2xl md:text-3xl font-bold text-white mb-4">Welcome to Madras.city — Chennai's Premier Digital Hub</h2>
                        <p class="text-gray-400 text-sm md:text-base leading-relaxed text-justify">
                            Chennai, historically known as Madras, is the capital of Tamil Nadu and South India's major economic, cultural, and educational center. Renowned as the "Detroit of Asia" for its automotive manufacturing strength, celebrated as India's health capital, and globally recognized for its rich Carnatic music, Bharatnatyam heritage, and long coastal beaches, Chennai is a vibrant metropolis that seamlessly integrates timeless culture with high-tech growth.
                        </p>
                        <p class="text-gray-400 text-sm md:text-base leading-relaxed text-justify">
                            Madras.city was created out of a passion to unify this sprawling metropolis into a single, comprehensive digital ecosystem. Whether you are a lifelong Madras resident, a student along the IT corridor, a professional in Guindy tech parks, or an event organizer hosting gatherings across the city, Madras.city empowers you with real-time intelligence.
                        </p>
                        <p class="text-gray-400 text-sm md:text-base leading-relaxed text-justify">
                            Our platform indexes verified event schedules, breaking city announcements, civic infrastructure progress, local business classifieds, job opportunities, and hidden local spots—giving citizens and visitors complete clarity on what is happening across every neighborhood in Chennai.
                        </p>
                        
                        <!-- External Authoritative City Links -->
                        <div class="pt-4 border-t border-white/10 flex flex-wrap items-center gap-4 text-xs font-bold">
                            <span class="text-gray-300 uppercase tracking-widest text-[10px]">Official City Resources:</span>
                            <a href="https://chennai.nic.in/" target="_blank" rel="noopener noreferrer" class="text-brand-light hover:underline flex items-center gap-1 transition-colors">
                                <i data-lucide="external-link" class="w-3.5 h-3.5"></i> District Administration Portal
                            </a>
                            <a href="https://en.wikipedia.org/wiki/Chennai" target="_blank" rel="noopener noreferrer" class="text-brand-light hover:underline flex items-center gap-1 transition-colors">
                                <i data-lucide="external-link" class="w-3.5 h-3.5"></i> Wikipedia Overview
                            </a>
                            <a href="https://maps.google.com/?q=Chennai+Tamil+Nadu" target="_blank" rel="noopener noreferrer" class="text-brand-light hover:underline flex items-center gap-1 transition-colors">
                                <i data-lucide="external-link" class="w-3.5 h-3.5"></i> Google Maps Location
                            </a>
                        </div>
                    </div>
                    <div class="lg:col-span-5 flex justify-center">
                        <div class="glass-card p-4 overflow-hidden border border-brand/20 hover:border-brand/40 transition-all duration-300 shadow-2xl max-w-md w-full">
                            <img src="assets/chennai_map.jpg" alt="Chennai Tourist Spots - Madras.city Local Guide" class="w-full h-auto rounded-xl object-contain shadow-lg hover:scale-[1.02] transition-transform duration-300">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Key Features & Offerings Grid -->
            <div class="space-y-12 mb-16">
                <div class="text-center max-w-2xl mx-auto">
                    <h2 class="text-2xl md:text-3xl font-bold text-white">What You Can Explore on Madras.city</h2>
                    <p class="text-gray-400 text-xs mt-2 uppercase tracking-widest font-bold">Comprehensive city coverage tailored for Chennai</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <!-- Feature 1 -->
                    <div class="glass-card p-8 flex flex-col justify-between hover:border-brand/30 transition-all duration-300">
                        <div class="space-y-4">
                            <div class="w-12 h-12 bg-brand/10 border border-brand/20 rounded-xl flex items-center justify-center">
                                <i data-lucide="calendar" class="w-6 h-6 text-brand-light"></i>
                            </div>
                            <h3 class="text-lg font-bold text-white">Chennai Event Directory</h3>
                            <p class="text-xs md:text-sm text-gray-400 leading-relaxed text-justify">
                                Discover tech summits, medical expos, job fairs, workshops, Carnatic music concerts, comedy shows, and community gatherings happening across OMR, Mylapore, T. Nagar, and Guindy.
                            </p>
                        </div>
                    </div>

                    <!-- Feature 2 -->
                    <div class="glass-card p-8 flex flex-col justify-between hover:border-brand/30 transition-all duration-300">
                        <div class="space-y-4">
                            <div class="w-12 h-12 bg-brand/10 border border-brand/20 rounded-xl flex items-center justify-center">
                                <i data-lucide="newspaper" class="w-6 h-6 text-brand-light"></i>
                            </div>
                            <h3 class="text-lg font-bold text-white">Real-Time City News & Updates</h3>
                            <p class="text-xs md:text-sm text-gray-400 leading-relaxed text-justify">
                                Stay informed with civic project milestones including Chennai Metro Rail Phase 2, Parandur greenfield airport, Port-Maduravoyal expressway, and OMR infrastructure announcements.
                            </p>
                        </div>
                    </div>

                    <!-- Feature 3 -->
                    <div class="glass-card p-8 flex flex-col justify-between hover:border-brand/30 transition-all duration-300">
                        <div class="space-y-4">
                            <div class="w-12 h-12 bg-brand/10 border border-brand/20 rounded-xl flex items-center justify-center">
                                <i data-lucide="shopping-bag" class="w-6 h-6 text-brand-light"></i>
                            </div>
                            <h3 class="text-lg font-bold text-white">Local Business Classifieds</h3>
                            <p class="text-xs md:text-sm text-gray-400 leading-relaxed text-justify">
                                Explore verified local business ads, automotive listings, real estate properties, professional services, educational training, and retail promotions from Chennai entrepreneurs.
                            </p>
                        </div>
                    </div>

                    <!-- Feature 4 -->
                    <div class="glass-card p-8 flex flex-col justify-between hover:border-brand/30 transition-all duration-300">
                        <div class="space-y-4">
                            <div class="w-12 h-12 bg-brand/10 border border-brand/20 rounded-xl flex items-center justify-center">
                                <i data-lucide="compass" class="w-6 h-6 text-brand-light"></i>
                            </div>
                            <h3 class="text-lg font-bold text-white">New Spots & Culinary Discoveries</h3>
                            <p class="text-xs md:text-sm text-gray-400 leading-relaxed text-justify">
                                Uncover newly opened cafes, specialty food joints, weekend beach getaways along ECR, heritage walks near Fort St. George, and unique local stories shaping modern Madras.
                            </p>
                        </div>
                    </div>

                    <!-- Feature 5 -->
                    <div class="glass-card p-8 flex flex-col justify-between hover:border-brand/30 transition-all duration-300">
                        <div class="space-y-4">
                            <div class="w-12 h-12 bg-brand/10 border border-brand/20 rounded-xl flex items-center justify-center">
                                <i data-lucide="megaphone" class="w-6 h-6 text-brand-light"></i>
                            </div>
                            <h3 class="text-lg font-bold text-white">Ad Credits & Business Marketing</h3>
                            <p class="text-xs md:text-sm text-gray-400 leading-relaxed text-justify">
                                Empower your brand with Madras.city ad credits. Post rotational banner advertisements, sponsored cards, and target dedicated local audiences across high-traffic event and news pages.
                            </p>
                        </div>
                    </div>

                    <!-- Feature 6 -->
                    <div class="glass-card p-8 flex flex-col justify-between hover:border-brand/30 transition-all duration-300">
                        <div class="space-y-4">
                            <div class="w-12 h-12 bg-brand/10 border border-brand/20 rounded-xl flex items-center justify-center">
                                <i data-lucide="shield-check" class="w-6 h-6 text-brand-light"></i>
                            </div>
                            <h3 class="text-lg font-bold text-white">Verified & Curated Content</h3>
                            <p class="text-xs md:text-sm text-gray-400 leading-relaxed text-justify">
                                All event submissions, news updates, and classified advertisements undergo systematic verification to ensure high quality, accuracy, and maximum trust for the entire Chennai community.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mission, Vision & Community Impact Section -->
            <div class="glass-card p-8 md:p-12 mb-16 border-white/10 bg-white/5 space-y-8">
                <div class="max-w-3xl">
                    <h2 class="text-2xl md:text-3xl font-bold text-white mb-4">Our Mission & Commitment to Chennai</h2>
                    <p class="text-gray-400 text-sm md:text-base leading-relaxed text-justify">
                        At Madras.city, our mission is to foster community growth, boost local commerce, and celebrate the rich culture of Chennai. We believe that every local business, independent artist, tech startup, and community organizer deserves a powerful platform to share their vision with the city.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4 border-t border-white/10">
                    <div class="space-y-2">
                        <h4 class="text-brand-light font-extrabold text-sm uppercase tracking-wider">1. Community Connection</h4>
                        <p class="text-gray-400 text-xs leading-relaxed">Connecting over 10 million citizens with meaningful events, educational workshops, and civic news.</p>
                    </div>
                    <div class="space-y-2">
                        <h4 class="text-brand-light font-extrabold text-sm uppercase tracking-wider">2. Empowering Local MSMEs</h4>
                        <p class="text-gray-400 text-xs leading-relaxed">Providing affordable, high-impact digital advertising tools for local shops, services, and startups.</p>
                    </div>
                    <div class="space-y-2">
                        <h4 class="text-brand-light font-extrabold text-sm uppercase tracking-wider">3. Modern City Intelligence</h4>
                        <p class="text-gray-400 text-xs leading-relaxed">Delivering lightning-fast, mobile-optimized, accessible information for citizens on the go.</p>
                    </div>
                </div>
            </div>

            <!-- Frequently Asked Questions (FAQ) -->
            <div class="space-y-8 mb-12">
                <div class="text-center max-w-2xl mx-auto">
                    <h2 class="text-2xl md:text-3xl font-bold text-white">Frequently Asked Questions</h2>
                    <p class="text-gray-400 text-xs mt-2 uppercase tracking-widest font-bold">Everything you need to know about Madras.city</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="glass-card p-6 space-y-2">
                        <h3 class="text-base font-bold text-white flex items-center gap-2">
                            <i data-lucide="help-circle" class="w-4 h-4 text-brand-light shrink-0"></i>
                            How can I list my event on Madras.city?
                        </h3>
                        <p class="text-xs text-gray-400 leading-relaxed">
                            Simply log in to your account, click on "Add Event" or navigate to your Profile page. Fill in the event title, venue, dates, category, and description. Once submitted, our team reviews it for quick publication.
                        </p>
                    </div>

                    <div class="glass-card p-6 space-y-2">
                        <h3 class="text-base font-bold text-white flex items-center gap-2">
                            <i data-lucide="help-circle" class="w-4 h-4 text-brand-light shrink-0"></i>
                            How do Ad Credits work for businesses?
                        </h3>
                        <p class="text-xs text-gray-400 leading-relaxed">
                            Ad Credits allow local businesses to create sponsored banner ads and classified listings. 1 INR equals 1 Ad Credit. You can purchase credits directly from your user profile and place ads on the homepage or detail pages.
                        </p>
                    </div>

                    <div class="glass-card p-6 space-y-2">
                        <h3 class="text-base font-bold text-white flex items-center gap-2">
                            <i data-lucide="help-circle" class="w-4 h-4 text-brand-light shrink-0"></i>
                            Is Madras.city free for citizens to use?
                        </h3>
                        <p class="text-xs text-gray-400 leading-relaxed">
                            Yes! Browsing events, reading city news, exploring new spots, and viewing local business classifieds on Madras.city is completely free for all users and visitors.
                        </p>
                    </div>

                    <div class="glass-card p-6 space-y-2">
                        <h3 class="text-base font-bold text-white flex items-center gap-2">
                            <i data-lucide="help-circle" class="w-4 h-4 text-brand-light shrink-0"></i>
                            What regions does Madras.city cover?
                        </h3>
                        <p class="text-xs text-gray-400 leading-relaxed">
                            We cover the entire Chennai metropolitan region including Mylapore, T. Nagar, OMR IT Corridor, Velachery, Guindy, Anna Nagar, Porur, Tambaram, Chromepet, and surrounding districts.
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <?php include 'footer.php'; ?>

    <script>
      lucide.createIcons();
    </script>
</body>
</html>
