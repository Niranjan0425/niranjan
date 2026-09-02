<?php
date_default_timezone_set('Asia/Kolkata');
require_once 'postlog.php';

// Safe default values for server variables to prevent warnings in CLI/test environments
if (!isset($_SERVER['REQUEST_METHOD'])) {
    $_SERVER['REQUEST_METHOD'] = 'GET';
}
if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = '';
}

// api.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'CityConfig.php';
require_once 'GeminiService.php';

function convertGoogleDriveLink(string $url): string {
    $url = trim($url ?? '');
    if (empty($url)) {
        return '';
    }
    
    // Check if the URL belongs to Google Drive, Google Docs, Drive User Content, or Google User Content
    if (strpos($url, 'drive.google.com') !== false || 
        strpos($url, 'docs.google.com') !== false || 
        strpos($url, 'drive.usercontent.google.com') !== false ||
        strpos($url, 'googleusercontent.com') !== false) {
        
        $fileId = '';
        
        // 1. Check for /file/d/FILE_ID/
        if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            $fileId = $matches[1];
        } 
        // 2. Check for /d/FILE_ID (e.g. lh3.googleusercontent.com/d/FILE_ID)
        elseif (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            $fileId = $matches[1];
        }
        // 3. Check for id=FILE_ID query parameter
        elseif (preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            $fileId = $matches[1];
        }
        
        if (!empty($fileId)) {
            return "api.php/api/drive-proxy?id=" . $fileId;
        }
    }
    
    return $url;
}

function generateVenueText(string $venue, string $city, string $state): string {
    $venueClean = trim($venue ?? '');
    $cityClean = trim($city ?? '') ?: CityConfig::CITY;
    $stateClean = trim($state ?? '') ?: CityConfig::STATE;
    
    if (empty($venueClean)) {
        return '';
    }
    
    $location = '';
    if (!empty($cityClean) && !empty($stateClean)) {
        $location = "$cityClean, $stateClean";
    } else if (!empty($cityClean)) {
        $location = $cityClean;
    } else if (!empty($stateClean)) {
        $location = $stateClean;
    }
    
    $venueName = $venueClean;
    if (empty($location) && strpos($venueClean, ',') !== false) {
        $parts = explode(',', $venueClean);
        $venueName = trim($parts[0]);
        $location = trim(implode(',', array_slice($parts, 1)));
    }
    
    if (empty($location)) {
        $location = CityConfig::CITY . ', ' . CityConfig::STATE;
    }
    
    $outdoorKeywords = ['ground', 'stadium', 'park', 'garden', 'lawn', 'beach', 'open air', 'outdoor', 'street', 'field', 'turf', 'lake', 'river'];
    $isOutdoor = false;
    $venueLower = strtolower($venueClean);
    foreach ($outdoorKeywords as $keyword) {
        if (strpos($venueLower, $keyword) !== false) {
            $isOutdoor = true;
            break;
        }
    }
    
    if ($isOutdoor) {
        return "$venueName, located in $location, offers a vibrant and spacious outdoor setting perfect for hosting memorable events and celebrations.";
    } else {
        return "$venueName, located in $location, offers a comfortable and elegant space for hosting memorable events and celebrations.";
    }
}

function matchLocationUrl(string $venue): string {
    $v = trim($venue ?? '');
    if (empty($v)) {
        return '';
    }
    $vLower = strtolower($v);
    if (strpos($vLower, 'trade centre') !== false || strpos($vLower, 'nandambakkam') !== false) {
        return 'https://www.chennaitradecentre.org/';
    }
    if (strpos($vLower, 'iit') !== false || strpos($vLower, 'taramani') !== false) {
        return 'https://www.iitm.ac.in/';
    }
    if (strpos($vLower, 'anna university') !== false) {
        return 'https://www.annauniv.edu/';
    }
    if (strpos($vLower, 'express avenue') !== false) {
        return 'https://www.expressavenue.in/';
    }
    if (strpos($vLower, 'phoenix') !== false) {
        return 'https://www.phoenixmarketcity.com/chennai';
    }
    if (strpos($vLower, 'music academy') !== false) {
        return 'https://musicacademymadras.in/';
    }
    if (strpos($vLower, 'kalakshetra') !== false) {
        return 'https://www.kalakshetra.in/';
    }
    
    return 'https://www.google.com/maps/search/?api=1&query=' . urlencode($v);
}

function sanitizeEvent(array $input): array {
    return [
        "title" => isset($input['title']) ? strval($input['title']) : "",
        "date" => isset($input['date']) ? strval($input['date']) : "",
        "time" => isset($input['time']) ? strval($input['time']) : "",
        "venue" => isset($input['venue']) ? strval($input['venue']) : "",
        "organizer" => isset($input['organizer']) ? strval($input['organizer']) : "",
        "category" => isset($input['category']) ? strval($input['category']) : "",
        "image" => isset($input['image']) ? convertGoogleDriveLink(strval($input['image'])) : "",
        "description" => isset($input['description']) ? strval($input['description']) : "",
        "price" => isset($input['price']) ? strval($input['price']) : "",
        "registrationUrl" => isset($input['registrationUrl']) ? strval($input['registrationUrl']) : "",
        "trending" => isset($input['trending']) ? (bool)$input['trending'] : false,
        "id" => isset($input['id']) ? strval($input['id']) : "",
        "attendees" => isset($input['attendees']) ? intval($input['attendees']) : 0,
        "sales" => isset($input['sales']) ? intval($input['sales']) : 0,
        "summary" => isset($input['summary']) ? strval($input['summary']) : "",
        "bioText" => isset($input['bioText']) ? strval($input['bioText']) : "",
        "eventFor" => isset($input['eventFor']) ? intval($input['eventFor']) : 0,
        "hideEvent" => isset($input['hideEvent']) ? intval($input['hideEvent']) : 0,
        "endDate" => isset($input['endDate']) ? strval($input['endDate']) : "",
        "noOfDays" => isset($input['noOfDays']) ? intval($input['noOfDays']) : 0,
        "eventvenueText" => isset($input['eventvenueText']) ? strval($input['eventvenueText']) : "",
        "city" => isset($input['city']) ? strval($input['city']) : "",
        "state" => isset($input['state']) ? strval($input['state']) : ""
    ];
}

function getNextEventId(array $events): string {
    $maxId = 0;
    foreach ($events as $event) {
        $idVal = intval($event['id'] ?? 0);
        if ($idVal > $maxId) {
            $maxId = $idVal;
        }
    }
    return strval($maxId + 1);
}

function getNextNewsId(array $news): string {
    $maxId = 0;
    foreach ($news as $item) {
        $cleanId = preg_replace('/[^0-9]/', '', $item['id'] ?? '');
        $idVal = intval($cleanId);
        if ($idVal > $maxId) {
            $maxId = $idVal;
        }
    }
    return strval($maxId + 1);
}

function getNextFactId(array $facts): string {
    $maxId = 0;
    foreach ($facts as $item) {
        $cleanId = preg_replace('/[^0-9]/', '', $item['id'] ?? '');
        $idVal = intval($cleanId);
        if ($idVal > $maxId) {
            $maxId = $idVal;
        }
    }
    return strval($maxId + 1);
}

function getNextActivityId(array $activities): string {
    $maxId = 0;
    foreach ($activities as $item) {
        $cleanId = preg_replace('/[^0-9]/', '', $item['id'] ?? '');
        $idVal = intval($cleanId);
        if ($idVal > $maxId) {
            $maxId = $idVal;
        }
    }
    return strval($maxId + 1);
}

function sanitizeActivity(array $act): array {
    $currentDate = date('Y-m-d');
    $currentTime = date('h:i A');
    $currentTimestamp = date('Y-m-d H:i:s');

    $actDate = (isset($act['date']) && $act['date'] !== 'Just now' && !empty($act['date'])) ? strval($act['date']) : $currentDate;
    $actTime = (isset($act['time']) && $act['time'] !== 'Just now' && !empty($act['time'])) ? strval($act['time']) : $currentTime;
    $actTimestamp = (isset($act['timestamp']) && $act['timestamp'] !== 'Just now' && !empty($act['timestamp'])) ? strval($act['timestamp']) : ($actDate . ' ' . $actTime);

    return [
        "title" => isset($act['title']) ? strval($act['title']) : (isset($act['message']) ? strval($act['message']) : ""),
        "date" => $actDate,
        "time" => $actTime,
        "timestamp" => $actTimestamp,
        "venue" => isset($act['venue']) ? strval($act['venue']) : "",
        "organizer" => isset($act['organizer']) ? strval($act['organizer']) : "",
        "category" => isset($act['category']) ? strval($act['category']) : (isset($act['type']) ? strval($act['type']) : "INFO"),
        "image" => isset($act['image']) ? strval($act['image']) : "",
        "description" => isset($act['description']) ? strval($act['description']) : (isset($act['message']) ? strval($act['message']) : ""),
        "price" => isset($act['price']) ? strval($act['price']) : "",
        "registrationUrl" => isset($act['registrationUrl']) ? strval($act['registrationUrl']) : "",
        "trending" => isset($act['trending']) ? (bool)$act['trending'] : false,
        "id" => isset($act['id']) ? strval($act['id']) : "",
        "attendees" => isset($act['attendees']) ? intval($act['attendees']) : 0,
        "sales" => isset($act['sales']) ? intval($act['sales']) : 0,
        "summary" => isset($act['summary']) ? strval($act['summary']) : (isset($act['message']) ? strval($act['message']) : ""),
        "bioText" => isset($act['bioText']) ? strval($act['bioText']) : "",
        "eventFor" => isset($act['eventFor']) ? intval($act['eventFor']) : 0,
        "hideEvent" => isset($act['hideEvent']) ? intval($act['hideEvent']) : 0,
        "endDate" => isset($act['endDate']) ? strval($act['endDate']) : $actTimestamp,
        "noOfDays" => isset($act['noOfDays']) ? intval($act['noOfDays']) : 0,
        "eventvenueText" => isset($act['eventvenueText']) ? strval($act['eventvenueText']) : ""
    ];
}

function logActivity(string $type, string $message): void {
    global $activities;
    array_unshift($activities, sanitizeActivity([
        "id" => getNextActivityId($activities),
        "type" => $type,
        "message" => $message,
        "date" => date('Y-m-d'),
        "time" => date('h:i:s A'),
        "timestamp" => date('Y-m-d H:i:s')
    ]));
}

function sanitizeNews(array $input): array {
    $desc = isset($input['description']) ? strval($input['description']) : (isset($input['summary']) ? strval($input['summary']) : (isset($input['postInfo']) ? strval($input['postInfo']) : ""));
    $title = isset($input['title']) ? strval($input['title']) : (isset($input['postTitle']) ? strval($input['postTitle']) : "");
    
    // Automatically translate/replace any Tamil Unicode text with English
    if (preg_match('/[\x{0B80}-\x{0BFF}]/u', $title)) {
        if (!empty($input['aiExplanation'])) {
            $parts = explode('.', $input['aiExplanation']);
            $title = trim($parts[0]);
        } else {
            $title = "Chennai Local News Update";
        }
    }
    if (preg_match('/[\x{0B80}-\x{0BFF}]/u', $desc) || empty($desc)) {
        $desc = "Latest local news headline and civic update from Chennai / Madras region.";
    }

    $l1 = isset($input['l1']) ? strval($input['l1']) : (isset($input['url']) ? strval($input['url']) : "");
    $image = isset($input['image']) ? convertGoogleDriveLink(strval($input['image'])) : (isset($input['imageURL']) ? convertGoogleDriveLink(strval($input['imageURL'])) : "");
    
    return [
        "title" => $title,
        "postTitle" => $title,
        "description" => $desc,
        "summary" => $desc,
        "postInfo" => $desc,
        "category" => isset($input['category']) ? strval($input['category']) : "General",
        "state" => isset($input['state']) ? strval($input['state']) : "Tamil Nadu",
        "postType" => isset($input['postType']) ? strval($input['postType']) : "News",
        "validity" => isset($input['validity']) ? intval($input['validity']) : 30,
        "l1" => $l1,
        "url" => $l1,
        "l2" => isset($input['l2']) ? strval($input['l2']) : "",
        "videoURL" => isset($input['videoURL']) ? strval($input['videoURL']) : "",
        "image" => $image,
        "imageURL" => $image,
        "id" => isset($input['id']) ? strval($input['id']) : "",
        "date" => isset($input['date']) ? strval($input['date']) : date('Y-m-d'),
        "trending" => isset($input['trending']) ? (bool)$input['trending'] : false,
        "eventFor" => isset($input['eventFor']) ? intval($input['eventFor']) : 0,
        "hideEvent" => isset($input['hideEvent']) ? intval($input['hideEvent']) : 0,
        "aiExplanation" => isset($input['aiExplanation']) ? strval($input['aiExplanation']) : ""
    ];
}

function sanitizeFact(array $input): array {
    $desc = isset($input['description']) ? strval($input['description']) : (isset($input['content']) ? strval($input['content']) : (isset($input['postInfo']) ? strval($input['postInfo']) : ""));
    $title = isset($input['title']) ? strval($input['title']) : (isset($input['postTitle']) ? strval($input['postTitle']) : "");
    $l1 = isset($input['l1']) ? strval($input['l1']) : (isset($input['url']) ? strval($input['url']) : "");
    $image = isset($input['image']) ? convertGoogleDriveLink(strval($input['image'])) : (isset($input['imageURL']) ? convertGoogleDriveLink(strval($input['imageURL'])) : "");

    return [
        "title" => $title,
        "postTitle" => $title,
        "description" => $desc,
        "content" => $desc,
        "postInfo" => $desc,
        "category" => isset($input['category']) ? strval($input['category']) : "General",
        "state" => isset($input['state']) ? strval($input['state']) : "Tamil Nadu",
        "postType" => isset($input['postType']) ? strval($input['postType']) : "Spotlight",
        "validity" => isset($input['validity']) ? intval($input['validity']) : 30,
        "l1" => $l1,
        "url" => $l1,
        "l2" => isset($input['l2']) ? strval($input['l2']) : "",
        "videoURL" => isset($input['videoURL']) ? strval($input['videoURL']) : "",
        "image" => $image,
        "imageURL" => $image,
        "id" => isset($input['id']) ? strval($input['id']) : "",
        "date" => isset($input['date']) ? strval($input['date']) : date('Y-m-d'),
        "trending" => isset($input['trending']) ? (bool)$input['trending'] : false,
        "eventFor" => isset($input['eventFor']) ? intval($input['eventFor']) : 0,
        "hideEvent" => isset($input['hideEvent']) ? intval($input['hideEvent']) : 0
    ];
}

function sanitizeAd(array $input): array {
    return [
        "id" => isset($input['id']) ? strval($input['id']) : "",
        "companyName" => isset($input['companyName']) ? strval($input['companyName']) : "",
        "postedBy" => isset($input['postedBy']) ? strval($input['postedBy']) : "",
        "contactNumber" => isset($input['contactNumber']) ? strval($input['contactNumber']) : "",
        "email" => isset($input['email']) ? strval($input['email']) : "",
        "image" => isset($input['image']) ? convertGoogleDriveLink(strval($input['image'])) : "",
        "description" => isset($input['description']) ? strval($input['description']) : "",
        "adTitle" => isset($input['adTitle']) ? strval($input['adTitle']) : (isset($input['ad_title']) ? strval($input['ad_title']) : ""),
        "detailedExplanation" => isset($input['detailedExplanation']) ? strval($input['detailedExplanation']) : (isset($input['detailed_explanation']) ? strval($input['detailed_explanation']) : ""),
        "url" => isset($input['url']) ? strval($input['url']) : "",
        "ctaLabel" => isset($input['ctaLabel']) ? strval($input['ctaLabel']) : "visit site",
        "category" => isset($input['category']) ? strval($input['category']) : "Homepage page",
        "subCategory" => (isset($input['subCategory']) && trim($input['subCategory']) !== '') ? strval($input['subCategory']) : "All Categories",
        "month" => isset($input['month']) ? strval($input['month']) : date('F Y'),
        "totalViews" => isset($input['totalViews']) ? intval($input['totalViews']) : 0,
        "budget" => isset($input['budget']) ? floatval($input['budget']) : 10000.0,
        "views" => isset($input['views']) ? intval($input['views']) : 0,
        "clicks" => isset($input['clicks']) ? intval($input['clicks']) : 0,
        "history" => (isset($input['history']) && is_array($input['history'])) ? $input['history'] : [],
        "hourly" => (isset($input['hourly']) && is_array($input['hourly'])) ? $input['hourly'] : [],
        "devices" => (isset($input['devices']) && is_array($input['devices'])) ? $input['devices'] : [
            'mobile'  => ['views' => 0, 'clicks' => 0],
            'desktop' => ['views' => 0, 'clicks' => 0],
            'tablet'  => ['views' => 0, 'clicks' => 0]
        ],
        "traffic_sources" => (isset($input['traffic_sources']) && is_array($input['traffic_sources'])) ? $input['traffic_sources'] : [
            'direct'   => ['views' => 0, 'clicks' => 0],
            'search'   => ['views' => 0, 'clicks' => 0],
            'social'   => ['views' => 0, 'clicks' => 0],
            'referral' => ['views' => 0, 'clicks' => 0]
        ],
        "locations" => (isset($input['locations']) && is_array($input['locations'])) ? $input['locations'] : [],
        "geo_locations" => (isset($input['geo_locations']) && is_array($input['geo_locations'])) ? $input['geo_locations'] : [],
        "status" => isset($input['status']) ? strval($input['status']) : "pending",
        "date" => isset($input['date']) ? strval($input['date']) : date('Y-m-d'),
        "rejectionReason" => isset($input['rejectionReason']) ? strval($input['rejectionReason']) : ""
    ];
}

function detectDeviceType(?string $ua, ?string $clientOverride = null): string {
    if (!empty($clientOverride)) {
        $cleanParam = strtolower(trim($clientOverride));
        if (in_array($cleanParam, ['mobile', 'tablet', 'desktop'], true)) {
            return $cleanParam;
        }
    }
    if (empty($ua)) {
        return 'desktop';
    }
    
    $uaLower = strtolower($ua);
    
    // Check tablets first (including iPad, Android tablets without mobile token, Kindle, Silk, PlayBook)
    if (preg_match('/(ipad|tablet|playbook|silk|kindle|(android(?!.*mobile)))/i', $uaLower)) {
        return 'tablet';
    }
    
    // Check mobile devices
    if (preg_match('/(mobile|iphone|ipod|android|blackberry|opera mini|windows phone|iemobile|mobile safari|webos|ucbrowser)/i', $uaLower)) {
        return 'mobile';
    }
    
    return 'desktop';
}

function recordAdView(array &$ad, ?string $clientDevOverride = null, ?string $locationOverride = null): void {
    if (!isset($ad['views'])) {
        $ad['views'] = 0;
    }
    $ad['views']++;

    $currentDate = date('Y-m-d');
    $currentHour = date('H');

    if (!isset($ad['history'])) {
        $ad['history'] = [];
    }
    if (!isset($ad['history'][$currentDate])) {
        $ad['history'][$currentDate] = ['views' => 0, 'clicks' => 0];
    }
    if (!isset($ad['history'][$currentDate]['views'])) {
        $ad['history'][$currentDate]['views'] = 0;
    }
    $ad['history'][$currentDate]['views']++;

    // Record hourly views
    if (!isset($ad['hourly'])) {
        $ad['hourly'] = [];
    }
    if (!isset($ad['hourly'][$currentDate])) {
        $ad['hourly'][$currentDate] = [];
    }
    if (!isset($ad['hourly'][$currentDate][$currentHour])) {
        $ad['hourly'][$currentDate][$currentHour] = ['views' => 0, 'clicks' => 0];
    }
    if (!isset($ad['hourly'][$currentDate][$currentHour]['views'])) {
        $ad['hourly'][$currentDate][$currentHour]['views'] = 0;
    }
    $ad['hourly'][$currentDate][$currentHour]['views']++;

    // Record device views
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $clientDev = $clientDevOverride ?? $_GET['device'] ?? $_POST['device'] ?? null;
    if (empty($clientDev)) {
        $queryStr = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);
        if ($queryStr) {
            parse_str($queryStr, $qParams);
            $clientDev = $qParams['device'] ?? null;
        }
    }
    $dev = detectDeviceType($ua, $clientDev);

    if (!isset($ad['devices']) || !is_array($ad['devices'])) {
        $ad['devices'] = ['mobile' => ['views' => 0, 'clicks' => 0], 'desktop' => ['views' => 0, 'clicks' => 0], 'tablet' => ['views' => 0, 'clicks' => 0]];
    }
    if (!isset($ad['devices'][$dev])) {
        $ad['devices'][$dev] = ['views' => 0, 'clicks' => 0];
    }
    if (!isset($ad['devices'][$dev]['views'])) {
        $ad['devices'][$dev]['views'] = 0;
    }
    $ad['devices'][$dev]['views']++;

    // Record traffic source views
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $src = 'direct';
    if (preg_match('/google|bing|yahoo|duckduckgo/i', $ref)) {
        $src = 'search';
    } else if (preg_match('/facebook|instagram|twitter|x\.com|linkedin|whatsapp/i', $ref)) {
        $src = 'social';
    } else if (!empty($ref) && strpos($ref, $_SERVER['HTTP_HOST'] ?? '') === false) {
        $src = 'referral';
    }
    if (!isset($ad['traffic_sources'])) {
        $ad['traffic_sources'] = ['direct' => ['views' => 0, 'clicks' => 0], 'search' => ['views' => 0, 'clicks' => 0], 'social' => ['views' => 0, 'clicks' => 0], 'referral' => ['views' => 0, 'clicks' => 0]];
    }
    if (!isset($ad['traffic_sources'][$src])) {
        $ad['traffic_sources'][$src] = ['views' => 0, 'clicks' => 0];
    }
    if (!isset($ad['traffic_sources'][$src]['views'])) {
        $ad['traffic_sources'][$src]['views'] = 0;
    }
    $ad['traffic_sources'][$src]['views']++;

    // Record views by location page URL
    $adId = $ad['id'] ?? '';
    $locParam = $locationOverride ?? $_GET['location'] ?? $_POST['location'] ?? null;
    $page = 'classified_details.php?id=' . $adId;
    if (!empty($locParam)) {
        $page = urldecode($locParam);
    } else if (!empty($ref)) {
        $path = parse_url($ref, PHP_URL_PATH);
        $page = basename($path);
        if (empty($page) || $page === '/' || $page === '\\' || $page === '.') {
            $page = 'index.php';
        }
        $query = parse_url($ref, PHP_URL_QUERY);
        if (!empty($query)) {
            $page .= '?' . $query;
        }
    }
    if (!isset($ad['locations'])) {
        $ad['locations'] = [];
    }
    if (!isset($ad['locations'][$page])) {
        $ad['locations'][$page] = ['views' => 0, 'clicks' => 0];
    }
    if (!isset($ad['locations'][$page]['views'])) {
        $ad['locations'][$page]['views'] = 0;
    }
    $ad['locations'][$page]['views']++;

    // Record views by IP-based geo location
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    $geo = getIpLocation(trim($ip));
    $geoKey = $geo['city'] . ', ' . $geo['state'] . ', ' . $geo['country'];

    if (!isset($ad['geo_locations'])) {
        $ad['geo_locations'] = [];
    }
    if (!isset($ad['geo_locations'][$geoKey])) {
        $ad['geo_locations'][$geoKey] = ['views' => 0, 'clicks' => 0];
    }
    if (!isset($ad['geo_locations'][$geoKey]['views'])) {
        $ad['geo_locations'][$geoKey]['views'] = 0;
    }
    $ad['geo_locations'][$geoKey]['views']++;
}

function recordAdClick(array &$ad, ?string $clientDevOverride = null): void {
    if (!isset($ad['clicks'])) {
        $ad['clicks'] = 0;
    }
    $ad['clicks']++;

    $currentDate = date('Y-m-d');
    $currentHour = date('H');

    if (!isset($ad['history'])) {
        $ad['history'] = [];
    }
    if (!isset($ad['history'][$currentDate])) {
        $ad['history'][$currentDate] = ['views' => 0, 'clicks' => 0];
    }
    if (!isset($ad['history'][$currentDate]['clicks'])) {
        $ad['history'][$currentDate]['clicks'] = 0;
    }
    $ad['history'][$currentDate]['clicks']++;

    // Record hourly clicks
    if (!isset($ad['hourly'])) {
        $ad['hourly'] = [];
    }
    if (!isset($ad['hourly'][$currentDate])) {
        $ad['hourly'][$currentDate] = [];
    }
    if (!isset($ad['hourly'][$currentDate][$currentHour])) {
        $ad['hourly'][$currentDate][$currentHour] = ['views' => 0, 'clicks' => 0];
    }
    $ad['hourly'][$currentDate][$currentHour]['clicks'] = intval($ad['hourly'][$currentDate][$currentHour]['clicks'] ?? 0) + 1;

    // Record device clicks
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $clientDev = $clientDevOverride ?? $_GET['device'] ?? $_POST['device'] ?? null;
    if (empty($clientDev)) {
        $queryStr = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);
        if ($queryStr) {
            parse_str($queryStr, $qParams);
            $clientDev = $qParams['device'] ?? null;
        }
    }
    $dev = detectDeviceType($ua, $clientDev);

    if (!isset($ad['devices']) || !is_array($ad['devices'])) {
        $ad['devices'] = ['mobile' => ['views' => 0, 'clicks' => 0], 'desktop' => ['views' => 0, 'clicks' => 0], 'tablet' => ['views' => 0, 'clicks' => 0]];
    }
    if (!isset($ad['devices'][$dev])) {
        $ad['devices'][$dev] = ['views' => 0, 'clicks' => 0];
    }
    $ad['devices'][$dev]['clicks'] = intval($ad['devices'][$dev]['clicks'] ?? 0) + 1;

    // Record traffic source clicks
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $src = 'direct';
    if (preg_match('/google|bing|yahoo|duckduckgo/i', $ref)) {
        $src = 'search';
    } else if (preg_match('/facebook|instagram|twitter|x\.com|linkedin|whatsapp/i', $ref)) {
        $src = 'social';
    } else if (!empty($ref) && strpos($ref, $_SERVER['HTTP_HOST'] ?? '') === false) {
        $src = 'referral';
    }
    if (!isset($ad['traffic_sources'])) {
        $ad['traffic_sources'] = ['direct' => ['views' => 0, 'clicks' => 0], 'search' => ['views' => 0, 'clicks' => 0], 'social' => ['views' => 0, 'clicks' => 0], 'referral' => ['views' => 0, 'clicks' => 0]];
    }
    if (!isset($ad['traffic_sources'][$src])) {
        $ad['traffic_sources'][$src] = ['views' => 0, 'clicks' => 0];
    }
    $ad['traffic_sources'][$src]['clicks'] = intval($ad['traffic_sources'][$src]['clicks'] ?? 0) + 1;

    // Record clicks by location page URL
    $page = 'Unknown';
    if (!empty($ref)) {
        $path = parse_url($ref, PHP_URL_PATH);
        $page = basename($path);
        if (empty($page) || $page === '/' || $page === '\\' || $page === '.') {
            $page = 'index.php';
        }
        $query = parse_url($ref, PHP_URL_QUERY);
        if (!empty($query)) {
            $page .= '?' . $query;
        }
    }
    if (!isset($ad['locations'])) {
        $ad['locations'] = [];
    }
    if (!isset($ad['locations'][$page])) {
        $ad['locations'][$page] = ['views' => 0, 'clicks' => 0];
    }
    $ad['locations'][$page]['clicks'] = intval($ad['locations'][$page]['clicks'] ?? 0) + 1;

    // Record clicks by IP-based geo location
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    $geo = getIpLocation(trim($ip));
    $geoKey = $geo['city'] . ', ' . $geo['state'] . ', ' . $geo['country'];

    if (!isset($ad['geo_locations'])) {
        $ad['geo_locations'] = [];
    }
    if (!isset($ad['geo_locations'][$geoKey])) {
        $ad['geo_locations'][$geoKey] = ['views' => 0, 'clicks' => 0];
    }
    $ad['geo_locations'][$geoKey]['clicks'] = intval($ad['geo_locations'][$geoKey]['clicks'] ?? 0) + 1;
}

function getNextAnnouncementId(array $announcements): string {
    $maxId = 0;
    foreach ($announcements as $item) {
        $cleanId = preg_replace('/[^0-9]/', '', $item['id'] ?? '');
        $idVal = intval($cleanId);
        if ($idVal > $maxId) {
            $maxId = $idVal;
        }
    }
    return strval($maxId + 1);
}

function sanitizeAnnouncement(array $input): array {
    $options = [];
    if (isset($input['options']) && is_array($input['options'])) {
        foreach ($input['options'] as $idx => $opt) {
            if (is_array($opt)) {
                $options[] = [
                    "id" => isset($opt['id']) ? strval($opt['id']) : ("opt_" . ($idx + 1)),
                    "text" => isset($opt['text']) ? strval($opt['text']) : "",
                    "votes" => isset($opt['votes']) ? intval($opt['votes']) : 0
                ];
            } else if (is_string($opt) && trim($opt) !== '') {
                $options[] = [
                    "id" => "opt_" . ($idx + 1),
                    "text" => trim($opt),
                    "votes" => 0
                ];
            }
        }
    }

    $totalVotes = 0;
    foreach ($options as $opt) {
        $totalVotes += intval($opt['votes']);
    }

    return [
        "id" => isset($input['id']) ? strval($input['id']) : "",
        "title" => isset($input['title']) ? strval($input['title']) : "",
        "message" => isset($input['message']) ? strval($input['message']) : (isset($input['description']) ? strval($input['description']) : ""),
        "category" => isset($input['category']) ? strval($input['category']) : "Announcement",
        "image" => isset($input['image']) ? convertGoogleDriveLink(strval($input['image'])) : "",
        "date" => isset($input['date']) ? strval($input['date']) : date('Y-m-d'),
        "status" => isset($input['status']) ? strval($input['status']) : "active",
        "hasPoll" => isset($input['hasPoll']) ? (bool)$input['hasPoll'] : (count($options) > 0),
        "totalVotes" => $totalVotes,
        "options" => $options
    ];
}





// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Helper to read JSON data securely with a shared flock read lock
function secure_read_json(string $file): ?array {
    if (!file_exists($file)) return null;
    $fp = fopen($file, 'r');
    if (!$fp) return null;
    $data = null;
    if (flock($fp, LOCK_SH)) {
        $content = '';
        while (!feof($fp)) {
            $content .= fread($fp, 8192);
        }
        flock($fp, LOCK_UN);
        $data = json_decode($content, true);
    }
    fclose($fp);
    return is_array($data) ? $data : null;
}

// Helper to write JSON data securely with an exclusive flock write lock
function secure_write_json(string $file, array $data): bool {
    $fp = fopen($file, 'c');
    if (!$fp) return false;
    $success = false;
    if (flock($fp, LOCK_EX)) {
        ftruncate($fp, 0);
        fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
        fflush($fp);
        flock($fp, LOCK_UN);
        $success = true;
    }
    fclose($fp);
    return $success;
}

// In a real PHP application, this would be a database connection.
// For this conversion, we'll simulate the array using local JSON files to maintain state.
$eventsFile = __DIR__ . '/events.json';
$newsFile   = __DIR__ . '/news.json';
$factsFile  = __DIR__ . '/facts.json';
$activitiesFile = __DIR__ . '/activities.json';
$adsFile    = __DIR__ . '/ads.json';
$announcementsFile = __DIR__ . '/announcements.json';
$dataFile   = __DIR__ . '/data.json';

function sync_data_to_mysql() {
    global $data;
    @include_once __DIR__ . '/db.php';
    @include_once __DIR__ . '/export_json_to_sql.php';
    global $conn, $pdo, $db;
    $dbConn = $conn ?? $pdo ?? $db ?? null;

    $cityId = 22;
    $cityName = 'madras';
    $domain = 'madras.city';

    $eventsFile = __DIR__ . '/events.json';
    $newsFile   = __DIR__ . '/news.json';
    $factsFile  = __DIR__ . '/facts.json';
    $adsFile    = __DIR__ . '/ads.json';
    $dataFile   = __DIR__ . '/data.json';

    $eventsList = (!empty($data['events']) && is_array($data['events'])) ? $data['events'] : (file_exists($eventsFile) ? (json_decode(file_get_contents($eventsFile), true) ?? []) : []);
    $newsList   = (!empty($data['news']) && is_array($data['news'])) ? $data['news'] : (file_exists($newsFile) ? (json_decode(file_get_contents($newsFile), true) ?? []) : []);
    $factsList  = (!empty($data['facts']) && is_array($data['facts'])) ? $data['facts'] : (file_exists($factsFile) ? (json_decode(file_get_contents($factsFile), true) ?? []) : []);
    $adsList    = (!empty($data['ads']) && is_array($data['ads'])) ? $data['ads'] : (file_exists($adsFile) ? (json_decode(file_get_contents($adsFile), true) ?? []) : []);
    $usersList  = (!empty($data['users']) && is_array($data['users'])) ? $data['users'] : (file_exists($dataFile) ? ((json_decode(file_get_contents($dataFile), true)['users'] ?? [])) : []);

    if (function_exists('log_db_sync_error')) {
        log_db_sync_error("SYNC START: Loaded " . count($eventsList) . " events, " . count($newsList) . " news, " . count($factsList) . " facts, " . count($adsList) . " ads.");
    }

    if ($dbConn && ($dbConn instanceof PDO)) {
        try {
            // 1. Sync Users
            if (!empty($usersList) && is_array($usersList)) {
                $stmt = $dbConn->prepare("INSERT INTO users (cityId, city_name, domain, email, mobile, password, name, username, bio, role, credits, credit_history, is_blocked, created_at) VALUES (:cityId, :cityName, :domain, :email, :mobile, :pass, :name, :uname, :bio, :role, :cred, :hist, :blocked, :created) ON DUPLICATE KEY UPDATE email=VALUES(email), credits=VALUES(credits)");
                $uCount = 0;
                foreach ($usersList as $u) {
                    if (!is_array($u) || empty($u['email'])) continue;
                    $stmt->execute([
                        ':cityId' => $cityId,
                        ':cityName' => $cityName,
                        ':domain' => $domain,
                        ':email' => $u['email'],
                        ':mobile' => $u['mobile'] ?? '',
                        ':pass' => $u['password'] ?? '',
                        ':name' => $u['name'] ?? '',
                        ':uname' => $u['username'] ?? '',
                        ':bio' => $u['bio'] ?? '',
                        ':role' => $u['role'] ?? 'user',
                        ':cred' => $u['credits'] ?? 0,
                        ':hist' => json_encode($u['credit_history'] ?? []),
                        ':blocked' => !empty($u['is_blocked']) ? 1 : 0,
                        ':created' => $u['created_at'] ?? date('Y-m-d H:i:s')
                    ]);
                    $uCount++;
                }
                if (function_exists('log_db_sync_error')) {
                    log_db_sync_error("USERS SYNC SUCCESS: Processed $uCount users.");
                }
            }

            // 2. Incremental Sync Events (Non-destructive UPSERT)
            if (!empty($eventsList) && is_array($eventsList)) {
                $checkStmt = $dbConn->prepare("SELECT id FROM events WHERE (cityId = :cityId OR domain = :domain) AND eventTitle = :title LIMIT 1");
                $updateStmt = $dbConn->prepare("UPDATE events SET eventInfo = :info, releasedOn = :releasedOn, image1 = :img, imageURL = :imgUrl, registrationUrl = :regUrl, eventVenue = :venue, eventDate = :eventDate, eventTime = :eventTime, organiserName = :org, eventvenueText = :venueText WHERE id = :id");
                $insertStmt = $dbConn->prepare("INSERT INTO events (cityId, city_name, domain, eventTitle, eventInfo, eventCategory, state, releasedOn, image1, imageURL, registrationUrl, eventCity, eventVenue, eventDate, eventTime, organiserName, eventvenueText) VALUES (:cityId, :cityName, :domain, :title, :info, 1, 23, :releasedOn, :img, :imgUrl, :regUrl, 'Chennai', :venue, :eventDate, :eventTime, :org, :venueText)");

                $eInserted = 0;
                $eUpdated = 0;
                foreach ($eventsList as $e) {
                    if (!is_array($e) || empty($e['title'])) continue;
                    $eventDate = !empty($e['date']) ? date('Y-m-d', strtotime($e['date'])) : date('Y-m-d');
                    
                    $checkStmt->execute([':cityId' => $cityId, ':domain' => $domain, ':title' => $e['title']]);
                    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($existing && !empty($existing['id'])) {
                        $updateStmt->execute([
                            ':info' => $e['description'] ?? ($e['summary'] ?? ''),
                            ':releasedOn' => $eventDate,
                            ':img' => $e['image'] ?? '',
                            ':imgUrl' => $e['image'] ?? '',
                            ':regUrl' => $e['registrationUrl'] ?? '',
                            ':venue' => $e['venue'] ?? 'Chennai',
                            ':eventDate' => $eventDate,
                            ':eventTime' => $e['time'] ?? '10:00 AM',
                            ':org' => $e['organizer'] ?? 'Madras.city',
                            ':venueText' => $e['eventvenueText'] ?? '',
                            ':id' => $existing['id']
                        ]);
                        $eUpdated++;
                    } else {
                        $insertStmt->execute([
                            ':cityId' => $cityId,
                            ':cityName' => $cityName,
                            ':domain' => $domain,
                            ':title' => $e['title'],
                            ':info' => $e['description'] ?? ($e['summary'] ?? ''),
                            ':releasedOn' => $eventDate,
                            ':img' => $e['image'] ?? '',
                            ':imgUrl' => $e['image'] ?? '',
                            ':regUrl' => $e['registrationUrl'] ?? '',
                            ':venue' => $e['venue'] ?? 'Chennai',
                            ':eventDate' => $eventDate,
                            ':eventTime' => $e['time'] ?? '10:00 AM',
                            ':org' => $e['organizer'] ?? 'Madras.city',
                            ':venueText' => $e['eventvenueText'] ?? ''
                        ]);
                        $eInserted++;
                    }
                }
                if (function_exists('log_db_sync_error')) {
                    log_db_sync_error("EVENTS SYNC SUCCESS: Inserted $eInserted new, Updated $eUpdated existing events.");
                }
            }

            // 3. Incremental Sync City News (Target base table `news`)
            if (!empty($newsList) && is_array($newsList)) {
                $checkStmt = $dbConn->prepare("SELECT id FROM news WHERE (cityId = :cityId OR domain = :domain) AND newsTitle = :title LIMIT 1");
                $updateStmt = $dbConn->prepare("UPDATE news SET newsDescription = :desc, category = :cat, source = :src, date = :date, url = :url, image = :img, views = :views WHERE id = :id");
                $insertStmt = $dbConn->prepare("INSERT INTO news (cityId, city_name, domain, newsTitle, newsDescription, category, source, date, url, image, views) VALUES (:cityId, :cityName, :domain, :title, :desc, :cat, :src, :date, :url, :img, :views)");

                $nInserted = 0;
                $nUpdated = 0;
                foreach ($newsList as $n) {
                    if (!is_array($n) || empty($n['title'])) continue;
                    
                    $checkStmt->execute([':cityId' => $cityId, ':domain' => $domain, ':title' => $n['title']]);
                    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

                    if ($existing && !empty($existing['id'])) {
                        $updateStmt->execute([
                            ':desc' => $n['description'] ?? ($n['summary'] ?? ''),
                            ':cat' => $n['category'] ?? 'General',
                            ':src' => $n['source'] ?? 'Madras.city',
                            ':date' => $n['date'] ?? date('Y-m-d'),
                            ':url' => $n['url'] ?? '',
                            ':img' => $n['image'] ?? '',
                            ':views' => intval($n['views'] ?? 0),
                            ':id' => $existing['id']
                        ]);
                        $nUpdated++;
                    } else {
                        $insertStmt->execute([
                            ':cityId' => $cityId,
                            ':cityName' => $cityName,
                            ':domain' => $domain,
                            ':title' => $n['title'],
                            ':desc' => $n['description'] ?? ($n['summary'] ?? ''),
                            ':cat' => $n['category'] ?? 'General',
                            ':src' => $n['source'] ?? 'Madras.city',
                            ':date' => $n['date'] ?? date('Y-m-d'),
                            ':url' => $n['url'] ?? '',
                            ':img' => $n['image'] ?? '',
                            ':views' => intval($n['views'] ?? 0)
                        ]);
                        $nInserted++;
                    }
                }
                if (function_exists('log_db_sync_error')) {
                    log_db_sync_error("NEWS SYNC SUCCESS: Inserted $nInserted new, Updated $nUpdated existing news items.");
                }
            }

            // 4. Incremental Sync Facts / Spotlights (Target base table `spotlight`)
            if (!empty($factsList) && is_array($factsList)) {
                $checkStmt = $dbConn->prepare("SELECT id FROM spotlight WHERE (cityId = :cityId OR domain = :domain) AND factTitle = :title LIMIT 1");
                $updateStmt = $dbConn->prepare("UPDATE spotlight SET factDescription = :desc, category = :cat, image = :img, date = :date, views = :views WHERE id = :id");
                $insertStmt = $dbConn->prepare("INSERT INTO spotlight (cityId, city_name, domain, factTitle, factDescription, category, image, date, views) VALUES (:cityId, :cityName, :domain, :title, :desc, :cat, :img, :date, :views)");

                $fInserted = 0;
                $fUpdated = 0;
                foreach ($factsList as $f) {
                    if (!is_array($f) || empty($f['title'])) continue;

                    $checkStmt->execute([':cityId' => $cityId, ':domain' => $domain, ':title' => $f['title']]);
                    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

                    if ($existing && !empty($existing['id'])) {
                        $updateStmt->execute([
                            ':desc' => $f['description'] ?? ($f['content'] ?? ''),
                            ':cat' => $f['category'] ?? 'Spotlight',
                            ':img' => $f['image'] ?? '',
                            ':date' => $f['date'] ?? date('Y-m-d'),
                            ':views' => intval($f['views'] ?? 0),
                            ':id' => $existing['id']
                        ]);
                        $fUpdated++;
                    } else {
                        $insertStmt->execute([
                            ':cityId' => $cityId,
                            ':cityName' => $cityName,
                            ':domain' => $domain,
                            ':title' => $f['title'],
                            ':desc' => $f['description'] ?? ($f['content'] ?? ''),
                            ':cat' => $f['category'] ?? 'Spotlight',
                            ':img' => $f['image'] ?? '',
                            ':date' => $f['date'] ?? date('Y-m-d'),
                            ':views' => intval($f['views'] ?? 0)
                        ]);
                        $fInserted++;
                    }
                }
                if (function_exists('log_db_sync_error')) {
                    log_db_sync_error("SPOTLIGHT SYNC SUCCESS: Inserted $fInserted new, Updated $fUpdated existing spotlight facts.");
                }
            }

            // 5. Incremental Sync Ads (Target base table `ads`)
            if (!empty($adsList) && is_array($adsList)) {
                $checkStmt = $dbConn->prepare("SELECT id FROM ads WHERE (cityId = :cityId OR domain = :domain) AND company_name = :company AND description = :desc LIMIT 1");
                $updateStmt = $dbConn->prepare("UPDATE ads SET category = :cat, image_url = :img, target_link = :link, status = :status WHERE id = :id");
                $insertStmt = $dbConn->prepare("INSERT INTO ads (cityId, city_name, domain, company_name, category, image_url, description, target_link, validity_days, credits_used, status) VALUES (:cityId, :cityName, :domain, :company, :cat, :img, :desc, :link, 30, 0, :status)");

                $aInserted = 0;
                $aUpdated = 0;
                foreach ($adsList as $a) {
                    $company = $a['companyName'] ?? ($a['company_name'] ?? '');
                    if (empty($company)) continue;
                    $desc = $a['description'] ?? '';

                    $checkStmt->execute([':cityId' => $cityId, ':domain' => $domain, ':company' => $company, ':desc' => $desc]);
                    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

                    if ($existing && !empty($existing['id'])) {
                        $updateStmt->execute([
                            ':cat' => $a['category'] ?? 'General',
                            ':img' => $a['image'] ?? ($a['image_url'] ?? ''),
                            ':link' => $a['url'] ?? ($a['target_link'] ?? ''),
                            ':status' => $a['status'] ?? 'approved',
                            ':id' => $existing['id']
                        ]);
                        $aUpdated++;
                    } else {
                        $insertStmt->execute([
                            ':cityId' => $cityId,
                            ':cityName' => $cityName,
                            ':domain' => $domain,
                            ':company' => $company,
                            ':cat' => $a['category'] ?? 'General',
                            ':img' => $a['image'] ?? ($a['image_url'] ?? ''),
                            ':desc' => $desc,
                            ':link' => $a['url'] ?? ($a['target_link'] ?? ''),
                            ':status' => $a['status'] ?? 'approved'
                        ]);
                        $aInserted++;
                    }
                }
                if (function_exists('log_db_sync_error')) {
                    log_db_sync_error("ADS SYNC SUCCESS: Inserted $aInserted new, Updated $aUpdated existing ads.");
                }
            }
        } catch (\Throwable $ex) {
            if (function_exists('log_db_sync_error')) {
                log_db_sync_error("MySQL Database Sync Failed", $ex);
            } else {
                error_log("MADRAS SQL SYNC FAILED: " . $ex->getMessage());
                @file_put_contents(
                    __DIR__ . '/db_sync_error.log',
                    "[" . date('Y-m-d H:i:s') . "] MADRAS SQL SYNC FAILED: " . $ex->getMessage() . PHP_EOL,
                    FILE_APPEND
                );
            }
        }
    } else {
        if (function_exists('log_db_sync_error')) {
            log_db_sync_error("MySQL DB Connection Inactive / Local mode. Continuing with JSON & madras_city.sql export.");
        }
    }

    // Always regenerate madras_city.sql export file
    try {
        if (function_exists('export_json_to_sql')) {
            export_json_to_sql();
        }
    } catch (\Throwable $exSql) {
        if (function_exists('log_db_sync_error')) {
            log_db_sync_error("SQL Export Generation Note", $exSql);
        } else {
            error_log("SQL Export Generation Note: " . $exSql->getMessage());
        }
    }
}


// Define save_data first so it can be called safely
function save_data() {
    global $eventsFile, $newsFile, $factsFile, $activitiesFile, $adsFile, $announcementsFile, $dataFile, $data;
    secure_write_json($eventsFile, $data['events'] ?? []);
    secure_write_json($newsFile, $data['news'] ?? []);
    secure_write_json($factsFile, $data['facts'] ?? []);
    secure_write_json($activitiesFile, $data['activities'] ?? []);
    secure_write_json($adsFile, $data['ads'] ?? []);
    secure_write_json($announcementsFile, $data['announcements'] ?? []);
    
    $settingsData = [
        'users' => $data['users'] ?? [],
        'pending_posts' => $data['pending_posts'] ?? [],
        'auto_fetch_stopped' => $data['auto_fetch_stopped'] ?? false,
        'single_view_budget' => $data['single_view_budget'] ?? 10000.0,
        'monthly_budgets' => $data['monthly_budgets'] ?? [],
        'cleanup_last_run' => $data['cleanup_last_run'] ?? 0,
        'navbar_visibility' => $data['navbar_visibility'] ?? [
            'home' => true,
            'events' => true,
            'city_news' => true,
            'new_in_cbe' => true,
            'classifieds' => true,
            'about' => true
        ],
        'explore_visibility' => $data['explore_visibility'] ?? [
            'events' => true,
            'city_news' => true,
            'new_in_cbe' => true,
            'classifieds' => true
        ],
        'analytics' => $data['analytics'] ?? []
    ];
    secure_write_json($dataFile, $settingsData);

    // Automatically sync newly added items to MySQL database tables
    sync_data_to_mysql();
}

function save_ads_data() {
    global $adsFile, $data;
    secure_write_json($adsFile, $data['ads'] ?? []);
    sync_data_to_mysql();
}

// Automatic migration from monolithic data.json if events.json does not exist yet but data.json does
if (file_exists($dataFile) && !file_exists($eventsFile)) {
    $monolith = secure_read_json($dataFile);
    if (is_array($monolith)) {
        if (isset($monolith['events'])) {
            secure_write_json($eventsFile, $monolith['events']);
        }
        if (isset($monolith['news'])) {
            secure_write_json($newsFile, $monolith['news']);
        }
        if (isset($monolith['facts'])) {
            secure_write_json($factsFile, $monolith['facts']);
        }
        if (isset($monolith['activities'])) {
            secure_write_json($activitiesFile, $monolith['activities']);
        }
        if (isset($monolith['ads'])) {
            secure_write_json($adsFile, $monolith['ads']);
        }
        $settingsData = [
            'users' => $monolith['users'] ?? [],
            'pending_posts' => $monolith['pending_posts'] ?? [],
            'auto_fetch_stopped' => $monolith['auto_fetch_stopped'] ?? false,
            'single_view_budget' => $monolith['single_view_budget'] ?? 10000.0,
            'monthly_budgets' => $monolith['monthly_budgets'] ?? [],
            'analytics' => $monolith['analytics'] ?? []
        ];
        secure_write_json($dataFile, $settingsData);
    }
}

clearstatcache(true, $dataFile);

function load_json_file(string $file, ?array $default = []): ?array {
    if (!file_exists($file)) return $default;
    $res = null;
    for ($retry = 0; $retry < 10; $retry++) {
        $res = secure_read_json($file);
        if ($res !== null) {
            break;
        }
        usleep(50000); // 50ms
    }
    return is_array($res) ? $res : $default;
}

$data = [];
$data['events'] = load_json_file($eventsFile, null);
$data['news']   = load_json_file($newsFile, null);
$data['facts']  = load_json_file($factsFile, null);
$data['activities'] = load_json_file($activitiesFile, null);
$data['ads']    = load_json_file($adsFile, null);
$data['announcements'] = load_json_file($announcementsFile, null);

$settings = load_json_file($dataFile, null);
if ($settings === null) {
    $settings = [
        'users' => [],
        'pending_posts' => [],
        'auto_fetch_stopped' => false,
        'single_view_budget' => 10000.0,
        'monthly_budgets' => []
    ];
}

$data['users'] = $settings['users'] ?? [];
$data['pending_posts'] = $settings['pending_posts'] ?? [];
$data['auto_fetch_stopped'] = $settings['auto_fetch_stopped'] ?? false;
$data['single_view_budget'] = $settings['single_view_budget'] ?? 10000.0;
$data['monthly_budgets'] = $settings['monthly_budgets'] ?? [];
$data['cleanup_last_run'] = $settings['cleanup_last_run'] ?? 0;
$data['navbar_visibility'] = $settings['navbar_visibility'] ?? [
    'home' => true,
    'events' => true,
    'city_news' => true,
    'new_in_cbe' => true,
    'classifieds' => true,
    'about' => true
];
$data['explore_visibility'] = $settings['explore_visibility'] ?? [
    'events' => true,
    'city_news' => true,
    'new_in_cbe' => true,
    'classifieds' => true
];
$data['analytics'] = $settings['analytics'] ?? [
    'navbar' => [
        'HOME' => ['impressions' => 0, 'clicks' => 0],
        'EVENTS' => ['impressions' => 0, 'clicks' => 0],
        'CITY-NEWS' => ['impressions' => 0, 'clicks' => 0],
        'NEW-IN-CBE' => ['impressions' => 0, 'clicks' => 0],
        'CLASSIFIEDS' => ['impressions' => 0, 'clicks' => 0],
        'ABOUT' => ['impressions' => 0, 'clicks' => 0],
        'ADMIN' => ['impressions' => 0, 'clicks' => 0],
        'POST AD' => ['impressions' => 0, 'clicks' => 0],
        'SIGN IN' => ['impressions' => 0, 'clicks' => 0]
    ],
    'history' => []
];

$needSave = false;

if ($data['events'] === null) {
    $data['events'] = [
        [
            "id" => "1",
            "title" => "Madras Carnatic & Heritage Cultural Festival 2026",
            "date" => date('Y-m-d', strtotime('+7 days')),
            "time" => "09:00 AM",
            "venue" => "Kalakshetra Foundation, Thiruvanmiyur",
            "organizer" => "Madras Cultural Academy",
            "bioText" => "Organized by Madras Cultural Academy",
            "eventFor" => 1,
            "hideEvent" => 1,
            "endDate" => date('Y-m-d', strtotime('+7 days')),
            "noOfDays" => 1,
            "eventvenueText" => "Kalakshetra Foundation, located in Thiruvanmiyur, Chennai, offers a comfortable and elegant space for hosting memorable events and celebrations.",
            "registrationUrl" => "https://www.kalakshetra.in/",
            "category" => "Live Concerts",
            "image" => "chennai_events.png",
            "description" => "The grand annual celebration of classical South Indian music, dance, and fine arts in Chennai. Featuring legendary vocalists and instrumental performances.",
            "price" => "₹200 onwards",
            "trending" => true,
            "attendees" => 3500,
            "sales" => 850,
            "summary" => "Grand classical music and arts festival in Chennai."
        ],
        [
            "id" => "2",
            "title" => "IIT Madras AI & DeepTech Summit 2026",
            "date" => date('Y-m-d', strtotime('+14 days')),
            "time" => "08:30 AM",
            "venue" => "IIT Madras Research Park, Taramani",
            "organizer" => "IITM Innovation Cell",
            "bioText" => "Organized by IITM Innovation Cell",
            "eventFor" => 1,
            "hideEvent" => 1,
            "endDate" => date('Y-m-d', strtotime('+14 days')),
            "noOfDays" => 1,
            "eventvenueText" => "IIT Madras Research Park, located in Taramani, Chennai, offers a modern and technology-driven space for hosting hackathons and tech summits.",
            "registrationUrl" => "https://www.iitm.ac.in/",
            "category" => "Workshops",
            "image" => "chennai_events.png",
            "description" => "A 24-hour hackathon and tech summit bringing together student developers, AI researchers, and tech founders across Chennai.",
            "price" => "Free",
            "trending" => true,
            "attendees" => 600,
            "sales" => 0,
            "summary" => "Premier AI & DeepTech developer summit in Chennai."
        ]
    ];
    $needSave = true;
}

if ($data['news'] === null) {
    $data['news'] = [
        [
            "id" => "1",
            "title" => "Chennai Metro Rail Phase 2 Route Approved by State Government",
            "date" => date('Y-m-d'),
            "source" => "The Hindu",
            "summary" => "The Tamil Nadu government has officially approved the detailed project report (DPR) for Phase 2 of the Chennai Metro Rail. The expansion spans three key corridors connecting Madhavaram to SIPCOT and Light House to Poonamallee.",
            "image" => "chennai_news.png",
            "url" => "https://www.thehindu.com/news/cities/chennai/",
            "trending" => true
        ],
        [
            "id" => "2",
            "title" => "Marina Beach Promenade Eco-Restoration Project Nearing Final Phase",
            "date" => date('Y-m-d'),
            "source" => "Times of India",
            "summary" => "Greater Chennai Corporation announced that 90% of the pedestrian promenade and eco-friendly green corridor along Marina Beach has been completed.",
            "image" => "chennai_news.png",
            "url" => "https://timesofindia.indiatimes.com/city/chennai",
            "trending" => false
        ],
        [
            "id" => "3",
            "title" => "TIDEL Park Pattabiram Hub Welcomes 5 New Global Tech Firms",
            "date" => date('Y-m-d'),
            "source" => "Madras News",
            "summary" => "Chennai's technology landscape receives a major boost as five global IT engineering companies open new regional offices in Pattabiram, creating 4,500+ tech jobs.",
            "image" => "chennai_news.png",
            "url" => "https://www.madras.city/",
            "trending" => true
        ]
    ];
    $needSave = true;
}

if ($data['facts'] === null) {
    $data['facts'] = [
        [
            "id" => "1",
            "title" => "Fort St. George - Birthplace of Modern Madras (1640)",
            "content" => "Founded in 1640 AD on the Coromandel Coast, Fort St. George stands as the historic nucleus of modern Madras, housing St. Mary's Church and the Tamil Nadu State Secretariat.",
            "category" => "History",
            "image" => "chennai_spotlight.png"
        ],
        [
            "id" => "2",
            "title" => "Marina Beach - Second Longest Natural Urban Beach in the World",
            "content" => "Stretching 13 kilometers from Fort St. George down to Besant Nagar, Marina Beach is the iconic coastline of Chennai, drawing thousands of visitors daily.",
            "category" => "Nature",
            "image" => "chennai_spotlight.png"
        ],
        [
            "id" => "3",
            "title" => "Detroit of South India - Automobile Manufacturing Giant",
            "content" => "Chennai manufactures over 40% of India's automobiles and 60% of auto component exports, earning its title as the Detroit of South Asia.",
            "category" => "Industry",
            "image" => "chennai_spotlight.png"
        ]
    ];
    $needSave = true;
}

if ($data['announcements'] === null) {
    $data['announcements'] = [
        [
            "id" => "1",
            "title" => "🗳️ Chennai Metro Phase 2: Priority Route Opinion Poll",
            "message" => "The Tamil Nadu Infrastructure Board invites Chennai residents to share their opinion. Which metro rail corridor expansion should receive top development priority in 2026?",
            "category" => "Public Poll",
            "image" => "https://images.unsplash.com/photo-1541535650810-10d26f5c2ab3?q=80&w=2069&auto=format&fit=crop",
            "date" => date('Y-m-d'),
            "status" => "active",
            "hasPoll" => true,
            "totalVotes" => 426,
            "options" => [
                [ "id" => "opt_1", "text" => "Madhavaram to SIPCOT IT Park Corridor", "votes" => 184 ],
                [ "id" => "opt_2", "text" => "Light House to Poonamallee Bypass", "votes" => 142 ],
                [ "id" => "opt_3", "text" => "ECR to OMR Elevated Link Road", "votes" => 100 ]
            ]
        ]
    ];
    $needSave = true;
}

if ($data['activities'] === null) {
    $data['activities'] = [];
    $needSave = true;
}

if ($data['ads'] === null) {
    $data['ads'] = [];
    $needSave = true;
}

if ($needSave) {
    save_data();
}

if (!isset($data['events']) || !is_array($data['events'])) { $data['events'] = []; }
if (!isset($data['activities']) || !is_array($data['activities'])) { $data['activities'] = []; }
if (!isset($data['users']) || !is_array($data['users'])) { $data['users'] = []; }
if (!isset($data['news']) || !is_array($data['news'])) { $data['news'] = []; }
if (!isset($data['facts']) || !is_array($data['facts'])) { $data['facts'] = []; }
if (!isset($data['ads']) || !is_array($data['ads'])) { $data['ads'] = []; }
if (!isset($data['announcements']) || !is_array($data['announcements'])) { $data['announcements'] = []; }
if (!isset($data['pending_posts']) || !is_array($data['pending_posts'])) { $data['pending_posts'] = []; }

// Ensure all loaded events strictly adhere to the requested schema
foreach ($data['events'] as &$event) {
    $event = sanitizeEvent($event);
}
unset($event);

// Ensure all loaded activities strictly adhere to the requested schema
foreach ($data['activities'] as &$act) {
    $act = sanitizeActivity($act);
}
unset($act);

// Ensure all loaded news strictly adhere to the requested schema
foreach ($data['news'] as &$nw) {
    $nw = sanitizeNews($nw);
}
unset($nw);

// Ensure all loaded facts strictly adhere to the requested schema
foreach ($data['facts'] as &$ft) {
    $ft = sanitizeFact($ft);
}
unset($ft);

// Ensure all loaded announcements strictly adhere to the requested schema
foreach ($data['announcements'] as &$anc) {
    $anc = sanitizeAnnouncement($anc);
}
unset($anc);

// Ensure all loaded ads strictly adhere to the requested schema
$adsModified = false;
foreach ($data['ads'] as &$ad) {
    $oldImg = isset($ad['image']) ? $ad['image'] : '';
    $ad = sanitizeAd($ad);
    if (($ad['image'] ?? '') !== $oldImg) {
        $adsModified = true;
    }
}
unset($ad);

if ($adsModified) {
    save_data();
}

$events = &$data['events'];
$activities = &$data['activities'];
$users = &$data['users'];
$news = &$data['news'];
$facts = &$data['facts'];
$ads = &$data['ads'];
$announcements = &$data['announcements'];
$pending_posts = &$data['pending_posts'];

if (!isset($data['auto_fetch_stopped'])) {
    $data['auto_fetch_stopped'] = false;
    save_data();
}

// ── Auto-cleanup: remove expired events/news/facts (runs at most once per hour) ──
$cleanupInterval = 3600; // 1 hour in seconds
$lastCleanup = isset($data['cleanup_last_run']) ? intval($data['cleanup_last_run']) : 0;
$shouldCleanup = (time() - $lastCleanup) > $cleanupInterval;

if ($shouldCleanup) {
    // Update the timestamp immediately in memory and save
    $data['cleanup_last_run'] = time();
    save_data();

    // Include cleanup.php in function mode (it returns early without output)
    require_once __DIR__ . '/cleanup.php';
    $cleanupResult = runCleanup(false);

    // If records were deleted, reload data from disk so this request sees fresh state
    if (($cleanupResult['total_deleted'] ?? 0) > 0) {
        $data['events'] = load_json_file($eventsFile, []);
        $data['news']   = load_json_file($newsFile, []);
        $data['facts']  = load_json_file($factsFile, []);
        $events = &$data['events'];
        $news = &$data['news'];
        $facts = &$data['facts'];
    }
}


function getIpLocation(string $ip): array {
    static $staticCache = [];
    $ipClean = trim($ip);
    $default = [
        'ip' => !empty($ipClean) ? $ipClean : '127.0.0.1',
        'country' => 'India',
        'state' => 'Tamil Nadu',
        'city' => 'Chennai',
        'postal' => '600001',
        'latitude' => 13.0827,
        'longitude' => 80.2707,
        'timezone' => 'Asia/Kolkata',
        'isp' => 'Localhost'
    ];

    // Instantly return default location for empty or local/private network IP ranges
    if (empty($ipClean) || $ipClean === '127.0.0.1' || $ipClean === '::1' || strpos($ipClean, '192.168.') === 0 || strpos($ipClean, '10.') === 0 || strpos($ipClean, '127.') === 0 || preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $ipClean)) {
        return $default;
    }

    if (isset($staticCache[$ipClean])) {
        return $staticCache[$ipClean];
    }

    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['ip_geo_' . $ipClean])) {
        $staticCache[$ipClean] = $_SESSION['ip_geo_' . $ipClean];
        return $_SESSION['ip_geo_' . $ipClean];
    }
    
    try {
        $ctx = stream_context_create(['http' => ['timeout' => 1]]);
        $res = @file_get_contents('http://ip-api.com/json/' . $ipClean . '?fields=status,country,regionName,city,zip,lat,lon,timezone,isp,query', false, $ctx);
        if ($res) {
            $geo = json_decode($res, true);
            if (isset($geo['status']) && $geo['status'] === 'success') {
                $loc = [
                    'ip' => $geo['query'] ?? $ipClean,
                    'country' => $geo['country'] ?? 'India',
                    'state' => $geo['regionName'] ?? 'Tamil Nadu',
                    'city' => $geo['city'] ?? 'Chennai',
                    'postal' => $geo['zip'] ?? '600001',
                    'latitude' => floatval($geo['lat'] ?? 13.0827),
                    'longitude' => floatval($geo['lon'] ?? 80.2707),
                    'timezone' => $geo['timezone'] ?? 'Asia/Kolkata',
                    'isp' => $geo['isp'] ?? 'Jio'
                ];
                $staticCache[$ipClean] = $loc;
                if (session_status() === PHP_SESSION_ACTIVE) {
                    $_SESSION['ip_geo_' . $ipClean] = $loc;
                }
                return $loc;
            }
        }
    } catch (Exception $e) {}
    
    $staticCache[$ipClean] = $default;
    return $default;
}

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Normalize URI
$requestUri = str_replace('/php_version/api.php', '/api', $requestUri);

$method = $_SERVER['REQUEST_METHOD'];
$geminiService = new GeminiService();

if (strpos($requestUri, '/api/drive-proxy') !== false && $method === 'GET') {
    $fileId = $_GET['id'] ?? '';
    if (empty($fileId) || !preg_match('/^[a-zA-Z0-9_-]+$/', $fileId)) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid file ID"]);
        exit();
    }
    
    $url = "https://drive.google.com/thumbnail?id=" . $fileId . "&sz=w1200";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    
    if ($httpCode === 200 && !empty($data)) {
        @header_remove('Pragma');
        @header_remove('Cache-Control');
        if (strpos($contentType, 'image/') !== false) {
            header("Content-Type: " . $contentType);
        } else {
            header("Content-Type: image/jpeg");
        }
        header("Cache-Control: public, max-age=604800, immutable");
        echo $data;
        exit();
    } else {
        http_response_code(502);
        echo json_encode(["message" => "Failed to fetch image from Google Drive"]);
        exit();
    }
}

if (strpos($requestUri, '/api/clear-all') !== false && $method === 'DELETE') {
    $data['events'] = [];
    $data['news'] = [];
    $data['facts'] = [];
    $data['ads'] = [];
    $events = &$data['events'];
    $news = &$data['news'];
    $facts = &$data['facts'];
    $ads = &$data['ads'];
    
    // Log activity
    logActivity("EVENT_DELETED", "Admin cleared all events, news updates, facts, and ads");
    
    save_data();
    echo json_encode(["message" => "All database tables cleared successfully"]);
    exit();
}

if (strpos($requestUri, '/api/organizer') !== false) {
    if ($method === 'GET') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $organizerName = $_SESSION['organizer_name'] ?? $_SESSION['username'] ?? null;
        if (!$organizerName) {
            if (!empty($users)) {
                foreach ($users as $u) {
                    if (($u['role'] ?? '') === 'admin' || !empty($u['organizer_name'])) {
                        $organizerName = $u['organizer_name'] ?? $u['name'] ?? null;
                        break;
                    }
                }
            }
        }
        if (!$organizerName) {
            $organizerName = 'IndieMa Admin';
        }
        echo json_encode(["organizer_name" => $organizerName]);
        exit();
    }
}

if (strpos($requestUri, '/api/auto-fetch-status') !== false) {
    if ($method === 'GET') {
        $autoFetchStopped = isset($data['auto_fetch_stopped']) ? (bool)$data['auto_fetch_stopped'] : false;
        echo json_encode(["auto_fetch_stopped" => $autoFetchStopped]);
        exit();
    } elseif ($method === 'POST' || $method === 'PATCH') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['auto_fetch_stopped'])) {
            $data['auto_fetch_stopped'] = (bool)$input['auto_fetch_stopped'];
            
            // Log activity
            $statusStr = $data['auto_fetch_stopped'] ? 'stopped' : 'started';
            logActivity("BOT_STATUS_CHANGED", "Admin " . $statusStr . " the Event Auto-Fetch Bot");
            
            save_data();
            echo json_encode(["message" => "Auto-Fetch status updated", "auto_fetch_stopped" => $data['auto_fetch_stopped']]);
            exit();
        }
    }
}

if (strpos($requestUri, '/api/settings') !== false) {
    if ($method === 'GET') {
        $singleViewBudget = isset($data['single_view_budget']) ? floatval($data['single_view_budget']) : 10000.0;
        $monthlyBudgets = isset($data['monthly_budgets']) ? $data['monthly_budgets'] : new stdClass();
        echo json_encode([
            "single_view_budget" => $singleViewBudget,
            "monthly_budgets" => $monthlyBudgets
        ]);
        exit();
    } elseif ($method === 'POST' || $method === 'PATCH') {
        $input = json_decode(file_get_contents('php://input'), true);
        $updated = false;
        if (isset($input['single_view_budget'])) {
            $data['single_view_budget'] = floatval($input['single_view_budget']);
            $updated = true;
        }
        if (isset($input['month']) && isset($input['budget'])) {
            if (!isset($data['monthly_budgets']) || !is_array($data['monthly_budgets'])) {
                $data['monthly_budgets'] = [];
            }
            $monthKey = trim(strval($input['month']));
            $data['monthly_budgets'][$monthKey] = floatval($input['budget']);
            $updated = true;
        }
        if ($updated) {
            save_data();
            $singleViewBudget = isset($data['single_view_budget']) ? floatval($data['single_view_budget']) : 10000.0;
            $monthlyBudgets = isset($data['monthly_budgets']) ? $data['monthly_budgets'] : new stdClass();
            echo json_encode([
                "message" => "Settings updated",
                "single_view_budget" => $singleViewBudget,
                "monthly_budgets" => $monthlyBudgets
            ]);
            exit();
        }
        http_response_code(400);
        echo json_encode(["message" => "Invalid settings payload"]);
        exit();
    }
}

$action = $_GET['action'] ?? null;

// --- BUTTON VISIBILITY API ENDPOINTS ---
if (strpos($requestUri, '/api/button-visibility') !== false || $action === 'get_button_visibility' || $action === 'update_button_visibility') {
    if (!isset($data['navbar_visibility']) || !is_array($data['navbar_visibility'])) {
        $data['navbar_visibility'] = [
            'home' => true,
            'events' => true,
            'city_news' => true,
            'new_in_cbe' => true,
            'classifieds' => true,
            'about' => true
        ];
    }
    if (!isset($data['explore_visibility']) || !is_array($data['explore_visibility'])) {
        $data['explore_visibility'] = [
            'events' => true,
            'city_news' => true,
            'new_in_cbe' => true,
            'classifieds' => true
        ];
    }

    if ($method === 'GET' || $action === 'get_button_visibility') {
        echo json_encode([
            "navbar" => $data['navbar_visibility'],
            "explore" => $data['explore_visibility']
        ]);
        exit();
    } elseif ($method === 'POST' || $method === 'PATCH' || $action === 'update_button_visibility') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        $updated = false;

        if (isset($input['type']) && isset($input['key']) && isset($input['visible'])) {
            $type = trim($input['type']);
            $key = trim($input['key']);
            $val = (bool)$input['visible'];

            if ($type === 'navbar') {
                $data['navbar_visibility'][$key] = $val;
                $updated = true;
            } elseif ($type === 'explore') {
                $data['explore_visibility'][$key] = $val;
                $updated = true;
            }
        }

        if (isset($input['navbar']) && is_array($input['navbar'])) {
            $data['navbar_visibility'] = array_merge($data['navbar_visibility'], $input['navbar']);
            $updated = true;
        }

        if (isset($input['explore']) && is_array($input['explore'])) {
            $data['explore_visibility'] = array_merge($data['explore_visibility'], $input['explore']);
            $updated = true;
        }

        if ($updated) {
            save_data();
            echo json_encode([
                "success" => true,
                "message" => "Button visibility updated successfully",
                "navbar" => $data['navbar_visibility'],
                "explore" => $data['explore_visibility']
            ]);
            exit();
        }

        http_response_code(400);
        echo json_encode(["message" => "Invalid visibility payload"]);
        exit();
    }
}

// --- ANALYTICS API ENDPOINTS ---
if (strpos($requestUri, '/api/analytics/reset') !== false || $action === 'reset_analytics') {
    $data['analytics'] = [
        'navbar' => [
            'HOME' => ['impressions' => 0, 'clicks' => 0],
            'EVENTS' => ['impressions' => 0, 'clicks' => 0],
            'CITY-NEWS' => ['impressions' => 0, 'clicks' => 0],
            'NEW-IN-CBE' => ['impressions' => 0, 'clicks' => 0],
            'CLASSIFIEDS' => ['impressions' => 0, 'clicks' => 0],
            'ABOUT' => ['impressions' => 0, 'clicks' => 0],
            'ADMIN' => ['impressions' => 0, 'clicks' => 0],
            'POST AD' => ['impressions' => 0, 'clicks' => 0],
            'SIGN IN' => ['impressions' => 0, 'clicks' => 0]
        ],
        'history' => []
    ];

    foreach (['events', 'news', 'facts', 'ads', 'announcements'] as $colKey) {
        if (isset($data[$colKey]) && is_array($data[$colKey])) {
            foreach ($data[$colKey] as &$item) {
                $item['views'] = 0;
                $item['clicks'] = 0;
                if ($colKey === 'ads') {
                    $item['totalViews'] = 0;
                    $item['history'] = [];
                    $item['locations'] = [];
                    $item['geo_locations'] = [];
                }
            }
            unset($item);
        }
    }

    save_data();
    echo json_encode(["success" => true, "message" => "All analytics data reset to 0."]);
    exit();
}

if (strpos($requestUri, '/api/analytics') !== false || $action === 'get_analytics' || $action === 'track_navbar' || $action === 'track_post') {
    if ($method === 'GET' && (strpos($requestUri, '/api/analytics') !== false || $action === 'get_analytics')) {
        $navbarDefaults = [
            'HOME' => ['impressions' => 0, 'clicks' => 0],
            'EVENTS' => ['impressions' => 0, 'clicks' => 0],
            'CITY-NEWS' => ['impressions' => 0, 'clicks' => 0],
            'NEW-IN-CBE' => ['impressions' => 0, 'clicks' => 0],
            'CLASSIFIEDS' => ['impressions' => 0, 'clicks' => 0],
            'ABOUT' => ['impressions' => 0, 'clicks' => 0],
            'ADMIN' => ['impressions' => 0, 'clicks' => 0],
            'POST AD' => ['impressions' => 0, 'clicks' => 0],
            'SIGN IN' => ['impressions' => 0, 'clicks' => 0]
        ];
        
        $navData = $data['analytics']['navbar'] ?? [];
        foreach ($navbarDefaults as $btn => $val) {
            if (!isset($navData[$btn])) {
                $navData[$btn] = $val;
            }
        }
        
        $navbarList = [];
        $totalNavImp = 0;
        $totalNavClk = 0;
        foreach ($navData as $btnName => $counts) {
            $imp = intval($counts['impressions'] ?? 0);
            $clk = intval($counts['clicks'] ?? 0);
            $ctr = $imp > 0 ? round(($clk / $imp) * 100, 1) : 0.0;
            $totalNavImp += $imp;
            $totalNavClk += $clk;
            $navbarList[] = [
                'button' => $btnName,
                'impressions' => $imp,
                'clicks' => $clk,
                'ctr' => $ctr
            ];
        }
        
        usort($navbarList, function($a, $b) {
            return $b['impressions'] - $a['impressions'];
        });
        
        $allPostsList = [];
        $totalPostImp = 0;
        $totalPostClk = 0;
        
        // Events
        foreach ($events as $ev) {
            $imp = intval($ev['views'] ?? 0);
            $clk = intval($ev['clicks'] ?? 0);
            $ctr = $imp > 0 ? round(($clk / $imp) * 100, 1) : 0.0;
            $totalPostImp += $imp;
            $totalPostClk += $clk;
            $allPostsList[] = [
                'id' => strval($ev['id'] ?? ''),
                'title' => $ev['title'] ?? 'Untitled Event',
                'type' => 'Events',
                'category' => $ev['category'] ?? 'Event',
                'image' => $ev['image'] ?? '',
                'date' => $ev['date'] ?? date('Y-m-d'),
                'impressions' => $imp,
                'clicks' => $clk,
                'ctr' => $ctr,
                'url' => 'event_details.php?id=' . ($ev['id'] ?? '')
            ];
        }
        
        // News
        foreach ($news as $nw) {
            $imp = intval($nw['views'] ?? 0);
            $clk = intval($nw['clicks'] ?? 0);
            $ctr = $imp > 0 ? round(($clk / $imp) * 100, 1) : 0.0;
            $totalPostImp += $imp;
            $totalPostClk += $clk;
            $allPostsList[] = [
                'id' => strval($nw['id'] ?? ''),
                'title' => $nw['title'] ?? 'Untitled News',
                'type' => 'City-News',
                'category' => $nw['category'] ?? 'News',
                'image' => $nw['image'] ?? '',
                'date' => $nw['date'] ?? date('Y-m-d'),
                'impressions' => $imp,
                'clicks' => $clk,
                'ctr' => $ctr,
                'url' => 'city_news_details.php?id=' . ($nw['id'] ?? '')
            ];
        }
        
        // Facts
        foreach ($facts as $ft) {
            $imp = intval($ft['views'] ?? 0);
            $clk = intval($ft['clicks'] ?? 0);
            $ctr = $imp > 0 ? round(($clk / $imp) * 100, 1) : 0.0;
            $totalPostImp += $imp;
            $totalPostClk += $clk;
            $allPostsList[] = [
                'id' => strval($ft['id'] ?? ''),
                'title' => $ft['title'] ?? 'Untitled Fact',
                'type' => 'New-In-Cbe',
                'category' => $ft['category'] ?? 'Spotlight',
                'image' => $ft['image'] ?? '',
                'date' => $ft['date'] ?? date('Y-m-d'),
                'impressions' => $imp,
                'clicks' => $clk,
                'ctr' => $ctr,
                'url' => 'new_in_cbe_details.php?id=' . ($ft['id'] ?? '')
            ];
        }
        
        // Ads
        foreach ($ads as $adItem) {
            $imp = intval($adItem['views'] ?? 0);
            $clk = intval($adItem['clicks'] ?? 0);
            $ctr = $imp > 0 ? round(($clk / $imp) * 100, 1) : 0.0;
            $totalPostImp += $imp;
            $totalPostClk += $clk;
            $allPostsList[] = [
                'id' => strval($adItem['id'] ?? ''),
                'title' => $adItem['companyName'] ?? 'Untitled Ad',
                'type' => 'Classified Ads',
                'category' => $adItem['category'] ?? 'Classifieds',
                'image' => $adItem['image'] ?? '',
                'date' => $adItem['date'] ?? date('Y-m-d'),
                'impressions' => $imp,
                'clicks' => $clk,
                'ctr' => $ctr,
                'url' => $adItem['url'] ?? '#'
            ];
        }

        // Announcements
        foreach ($announcements as $ancItem) {
            $imp = intval($ancItem['views'] ?? 0);
            $clk = intval($ancItem['totalVotes'] ?? 0);
            $ctr = $imp > 0 ? round(($clk / $imp) * 100, 1) : 0.0;
            $totalPostImp += $imp;
            $totalPostClk += $clk;
            $allPostsList[] = [
                'id' => strval($ancItem['id'] ?? ''),
                'title' => $ancItem['title'] ?? 'Untitled Poll',
                'type' => 'Announcements',
                'category' => $ancItem['category'] ?? 'Poll',
                'image' => $ancItem['image'] ?? '',
                'date' => $ancItem['date'] ?? date('Y-m-d'),
                'impressions' => $imp,
                'clicks' => $clk,
                'ctr' => $ctr,
                'url' => 'index.php#announcements'
            ];
        }
        
        usort($allPostsList, function($a, $b) {
            return $b['impressions'] - $a['impressions'];
        });
        
        $overallNavCtr = $totalNavImp > 0 ? round(($totalNavClk / $totalNavImp) * 100, 1) : 0.0;
        $overallPostCtr = $totalPostImp > 0 ? round(($totalPostClk / $totalPostImp) * 100, 1) : 0.0;
        
        echo json_encode([
            'summary' => [
                'total_navbar_impressions' => $totalNavImp,
                'total_navbar_clicks' => $totalNavClk,
                'navbar_ctr' => $overallNavCtr,
                'total_post_impressions' => $totalPostImp,
                'total_post_clicks' => $totalPostClk,
                'post_ctr' => $overallPostCtr,
                'total_posts_count' => count($allPostsList)
            ],
            'totalEvents' => count($events),
            'totalNews' => count($news),
            'totalFacts' => count($facts),
            'recentActivity' => array_values($activities ?? []),
            'navbar' => $navbarList,
            'posts' => $allPostsList,
            'history' => $data['analytics']['history'] ?? new stdClass()
        ]);
        exit();
    }

    if ($method === 'POST' || $action === 'track_navbar' || $action === 'track_post') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = $_REQUEST;
        }
        
        $today = date('Y-m-d');
        if (!isset($data['analytics']) || !is_array($data['analytics'])) {
            $data['analytics'] = ['navbar' => [], 'history' => []];
        }
        if (!isset($data['analytics']['history'][$today])) {
            $data['analytics']['history'][$today] = [
                'navbar_impressions' => 0,
                'navbar_clicks' => 0,
                'post_impressions' => 0,
                'post_clicks' => 0
            ];
        }

        $target = $input['target'] ?? ($action === 'track_navbar' ? 'navbar' : ($action === 'track_post' ? 'post' : 'navbar'));
        
        if ($target === 'navbar') {
            $btn = strtoupper(trim($input['button'] ?? 'HOME'));
            $type = strtolower(trim($input['type'] ?? 'impression'));
            
            if (!isset($data['analytics']['navbar'][$btn])) {
                $data['analytics']['navbar'][$btn] = ['impressions' => 0, 'clicks' => 0];
            }
            
            if ($type === 'click') {
                $data['analytics']['navbar'][$btn]['clicks']++;
                $data['analytics']['history'][$today]['navbar_clicks']++;
            } else {
                $data['analytics']['navbar'][$btn]['impressions']++;
                $data['analytics']['history'][$today]['navbar_impressions']++;
            }
            
            save_data();
            echo json_encode(["success" => true, "message" => "Navbar " . $type . " tracked", "data" => $data['analytics']['navbar'][$btn]]);
            exit();
        } else if ($target === 'post') {
            $postTypeRaw = strtolower(trim($input['post_type'] ?? 'events'));
            $postId = strval($input['post_id'] ?? '');
            $type = strtolower(trim($input['type'] ?? 'impression'));
            
            $postTypeKey = 'events';
            if (strpos($postTypeRaw, 'news') !== false) {
                $postTypeKey = 'news';
            } else if (strpos($postTypeRaw, 'new-in') !== false || strpos($postTypeRaw, 'cbe') !== false || strpos($postTypeRaw, 'fact') !== false) {
                $postTypeKey = 'facts';
            } else if (strpos($postTypeRaw, 'ad') !== false || strpos($postTypeRaw, 'classified') !== false) {
                $postTypeKey = 'ads';
            } else if (strpos($postTypeRaw, 'announc') !== false || strpos($postTypeRaw, 'poll') !== false) {
                $postTypeKey = 'announcements';
            } else {
                $postTypeKey = 'events';
            }
            
            if (isset($data[$postTypeKey]) && is_array($data[$postTypeKey])) {
                foreach ($data[$postTypeKey] as &$item) {
                    if (strval($item['id'] ?? '') === $postId) {
                        if ($type === 'click') {
                            $item['clicks'] = intval($item['clicks'] ?? 0) + 1;
                            $data['analytics']['history'][$today]['post_clicks'] = intval($data['analytics']['history'][$today]['post_clicks'] ?? 0) + 1;
                        } else {
                            $item['views'] = intval($item['views'] ?? 0) + 1;
                            $data['analytics']['history'][$today]['post_impressions'] = intval($data['analytics']['history'][$today]['post_impressions'] ?? 0) + 1;
                        }
                        break;
                    }
                }
                unset($item);
                save_data();
            }
            echo json_encode(["success" => true, "message" => "Post " . $type . " tracked"]);
            exit();
        }
    }
}

if (strpos($requestUri, '/api/discover') !== false && $method === 'POST') {
    @set_time_limit(180);
    $autoFetchStopped = isset($data['auto_fetch_stopped']) ? (bool)$data['auto_fetch_stopped'] : false;
    if ($autoFetchStopped) {
        http_response_code(403);
        echo json_encode(["message" => "Auto-Fetch is currently disabled by Admin.", "eventsCount" => 0, "error" => "auto_fetch_stopped"]);
        exit();
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    // Check what elements to fetch. Default to events = true if no body sent (backward compatibility)
    $fetchEvents = isset($input['events']) ? (bool)$input['events'] : (empty($input) ? true : false);
    $fetchNews = isset($input['news']) ? (bool)$input['news'] : false;
    $fetchFacts = isset($input['facts']) ? (bool)$input['facts'] : false;

    // Fallback checks for query parameters (just in case)
    if (empty($input)) {
        if (isset($_GET['events'])) $fetchEvents = ($_GET['events'] === 'true' || $_GET['events'] == 1);
        if (isset($_GET['news'])) $fetchNews = ($_GET['news'] === 'true' || $_GET['news'] == 1);
        if (isset($_GET['facts'])) $fetchFacts = ($_GET['facts'] === 'true' || $_GET['facts'] == 1);
    }

    $eventsAdded = 0;
    $newsAdded = 0;
    $factsAdded = 0;

    if ($fetchEvents) {
        $existingTitles = array_column($events, 'title');
        $newEvents = $geminiService->discoverNewEvents($existingTitles);
        
        if ($newEvents && is_array($newEvents) && count($newEvents) > 0) {
            foreach ($newEvents as $ev) {
                $ev['id'] = getNextEventId($events);
                $ev['attendees'] = rand(0, 500);
                $ev['sales'] = 0;
                $ev['summary'] = substr($ev['description'] ?? '', 0, 50) . "...";
                $org = trim($ev['organizer'] ?? 'Unknown Organizer');
                $ev['bioText'] = "Organized by " . $org;
                $ev['eventFor'] = 1;
                $ev['hideEvent'] = 1;
                $ev['endDate'] = $ev['date'] ?? date('Y-m-d');
                $ev['noOfDays'] = 1;
                
                // Auto generate eventvenueText and registrationUrl
                $ev['eventvenueText'] = generateVenueText($ev['venue'] ?? '', $ev['city'] ?? '', $ev['state'] ?? '');
                if (empty($ev['registrationUrl'])) {
                    $ev['registrationUrl'] = matchLocationUrl($ev['venue'] ?? '');
                }
                // Override with highly relevant curated image
                $ev['image'] = $geminiService->getRelevantImage($ev['title'] ?? '', $ev['description'] ?? '', $ev['category'] ?? '', 'event');
                
                $events[] = sanitizeEvent($ev);
                $eventsAdded++;
                
                logActivity('AI_DISCOVERY', "AI automatically fetched new event: " . $ev['title']);
            }
        }
    }

    if ($fetchNews) {
        $existingNewsTitles = array_column($news, 'title');
        $newNews = $geminiService->discoverNews($existingNewsTitles);
        
        if ($newNews && is_array($newNews) && count($newNews) > 0) {
            foreach ($newNews as $nw) {
                $nw['id'] = getNextNewsId($news);
                if (empty($nw['date'])) {
                    $nw['date'] = date('Y-m-d');
                }
                // Override with highly relevant curated image
                $nw['image'] = $geminiService->getRelevantImage($nw['title'] ?? '', $nw['summary'] ?? '', 'News', 'news');

                // Pre-generate AI explanation so details page shows it instantly
                try {
                    $aiPrompt = "You are a knowledgeable journalist writing for madras.city, a local news and events platform for Chennai / Madras, Tamil Nadu, India.\n\nA reader just clicked on the following news article from Chennai, Tamil Nadu:\n\nTitle: " . ($nw['title'] ?? '') . "\nSummary: " . ($nw['summary'] ?? '') . "\n\nWrite a detailed, engaging, and informative explanation of this story for the reader. Your explanation should:\n- Be 3 to 5 well-structured paragraphs\n- Provide relevant background context about the topic\n- Explain why this matters to the people of Chennai\n- Include any likely implications or what to expect next\n- Use clear, accessible language — no jargon\n- Do NOT repeat the title verbatim as the first sentence\n- Do NOT use markdown headings, bullet points, or formatting — plain flowing paragraphs only";
                    $nw['aiExplanation'] = $geminiService->generateText($aiPrompt);
                } catch (Exception $e) {
                    $nw['aiExplanation'] = '';
                }

                $news[] = sanitizeNews($nw);
                $newsAdded++;
                
                logActivity('AI_DISCOVERY', "AI automatically fetched new news: " . $nw['title']);
            }
        }
    }

    if ($fetchFacts) {
        $existingFacts = array_column($facts, 'title');
        $newFacts = $geminiService->discoverFacts($existingFacts);
        
        if ($newFacts && is_array($newFacts) && count($newFacts) > 0) {
            foreach ($newFacts as $ft) {
                $ft['id'] = getNextFactId($facts);
                // Override with highly relevant curated image
                $ft['image'] = $geminiService->getRelevantImage($ft['title'] ?? '', $ft['content'] ?? '', $ft['category'] ?? '', 'fact');
                $facts[] = sanitizeFact($ft);
                $factsAdded++;
                
                logActivity('AI_DISCOVERY', "AI automatically fetched new fact: " . $ft['title']);
            }
        }
    }

    if ($eventsAdded > 0 || $newsAdded > 0 || $factsAdded > 0) {
        save_data();
    }
    
    echo json_encode([
        "message" => "Discovery completed", 
        "eventsCount" => $eventsAdded,
        "newsCount" => $newsAdded,
        "factsCount" => $factsAdded
    ]);
    exit();
}

if (strpos($requestUri, '/api/generate-custom-post') !== false && $method === 'POST') {
    @set_time_limit(180);
    $input = json_decode(file_get_contents('php://input'), true);
    $userPrompt = trim($input['prompt'] ?? '');
    
    if (empty($userPrompt)) {
        http_response_code(400);
        echo json_encode(["message" => "Please enter a valid prompt or command."]);
        exit();
    }

    $generated = $geminiService->generateCustomPost($userPrompt);

    if (!$generated) {
        $postType = 'events';
        if (preg_match('/news|headline|metro|update|traffic|police|government/i', $userPrompt)) {
            $postType = 'news';
        } else if (preg_match('/spotlight|opening|restaurant|cafe|history|fact|nature|place/i', $userPrompt)) {
            $postType = 'facts';
        }

        $cleanTitle = ucwords(trim(preg_replace('/^(search for|create a post about|find|generate|add)\s+/i', '', $userPrompt)));
        if (strlen($cleanTitle) < 5) {
            $cleanTitle = "Chennai Local Update";
        }
        
        $imgPrompt = $cleanTitle . " Chennai";
        $seed = rand(1000, 999999);
        $imageUrl = "https://image.pollinations.ai/prompt/" . rawurlencode($imgPrompt) . "?width=800&height=600&nologo=true&seed=" . $seed;

        $generated = [
            "postType" => $postType,
            "title" => $cleanTitle,
            "description" => "Custom update generated based on command: \"$userPrompt\". Explore what's happening across Chennai.",
            "category" => "General",
            "date" => date('Y-m-d'),
            "time" => "10:00 AM",
            "venue" => "Chennai",
            "organizer" => "Madras.city Community",
            "price" => "Free",
            "url" => "https://www.madras.city",
            "source" => "Madras.city AI",
            "image" => $imageUrl
        ];
    }

    echo json_encode([
        "success" => true,
        "post" => $generated
    ]);
    exit();
}

if (strpos($requestUri, '/api/save-custom-post') !== false && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['post']) || !is_array($input['post'])) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid custom post payload."]);
        exit();
    }

    $postData = $input['post'];
    $postType = strtolower(trim($input['postType'] ?? ($postData['postType'] ?? 'events')));
    
    if (!in_array($postType, ['events', 'news', 'facts'])) {
        $postType = 'events';
    }

    $title = trim($postData['title'] ?? '');
    if (empty($title)) {
        http_response_code(400);
        echo json_encode(["message" => "Post title is required."]);
        exit();
    }

    if ($postType === 'events') {
        $ev = [
            'id' => getNextEventId($events),
            'title' => $title,
            'date' => !empty($postData['date']) ? $postData['date'] : date('Y-m-d'),
            'time' => !empty($postData['time']) ? $postData['time'] : '10:00 AM',
            'venue' => !empty($postData['venue']) ? $postData['venue'] : 'Chennai',
            'organizer' => !empty($postData['organizer']) ? $postData['organizer'] : 'Madras.city',
            'category' => !empty($postData['category']) ? $postData['category'] : 'Networking Events',
            'image' => !empty($postData['image']) ? $postData['image'] : 'chennai_events.png',
            'description' => !empty($postData['description']) ? $postData['description'] : '',
            'price' => !empty($postData['price']) ? $postData['price'] : 'Free',
            'registrationUrl' => !empty($postData['url']) ? $postData['url'] : matchLocationUrl($postData['venue'] ?? ''),
            'trending' => true,
            'attendees' => rand(10, 200),
            'sales' => 0,
            'summary' => mb_substr($postData['description'] ?? '', 0, 80, 'UTF-8') . '...',
            'bioText' => 'Organized by ' . (!empty($postData['organizer']) ? $postData['organizer'] : 'Madras.city'),
            'eventFor' => 1,
            'hideEvent' => 1,
            'endDate' => !empty($postData['date']) ? $postData['date'] : date('Y-m-d'),
            'noOfDays' => 1,
            'eventvenueText' => generateVenueText($postData['venue'] ?? 'Chennai', 'Chennai', 'Tamil Nadu')
        ];
        $events[] = sanitizeEvent($ev);
        logActivity('CUSTOM_AI_POST', "Admin published custom AI event: " . $title);
    } else if ($postType === 'news') {
        $nw = [
            'id' => getNextNewsId($news),
            'title' => $title,
            'postTitle' => $title,
            'date' => !empty($postData['date']) ? $postData['date'] : date('Y-m-d'),
            'source' => !empty($postData['source']) ? $postData['source'] : 'Madras.city AI',
            'summary' => !empty($postData['description']) ? $postData['description'] : '',
            'description' => !empty($postData['description']) ? $postData['description'] : '',
            'category' => !empty($postData['category']) ? $postData['category'] : 'General',
            'image' => !empty($postData['image']) ? $postData['image'] : 'chennai_news.png',
            'url' => !empty($postData['url']) ? $postData['url'] : '',
            'l1' => !empty($postData['url']) ? $postData['url'] : '',
            'trending' => true,
            'aiExplanation' => $postData['description'] ?? ''
        ];
        $news[] = sanitizeNews($nw);
        logActivity('CUSTOM_AI_POST', "Admin published custom AI news: " . $title);
    } else if ($postType === 'facts') {
        $ft = [
            'id' => getNextFactId($facts),
            'title' => $title,
            'postTitle' => $title,
            'content' => !empty($postData['description']) ? $postData['description'] : '',
            'description' => !empty($postData['description']) ? $postData['description'] : '',
            'category' => !empty($postData['category']) ? $postData['category'] : 'General',
            'image' => !empty($postData['image']) ? $postData['image'] : 'chennai_spotlight.png',
            'date' => !empty($postData['date']) ? $postData['date'] : date('Y-m-d'),
            'trending' => true
        ];
        $facts[] = sanitizeFact($ft);
        logActivity('CUSTOM_AI_POST', "Admin published custom AI spotlight: " . $title);
    }

    save_data();

    echo json_encode([
        "success" => true,
        "message" => "Post successfully published to Home Page!",
        "postType" => $postType
    ]);
    exit();
}

if (strpos($requestUri, '/api/announcements') !== false) {
    if (preg_match('/\/api\/announcements\/([^\/]+)\/vote$/', $requestUri, $matches) && $method === 'POST') {
        $id = $matches[1];
        $input = json_decode(file_get_contents('php://input'), true);
        $optionId = trim($input['optionId'] ?? '');

        $targetKey = null;
        foreach ($announcements as $k => $item) {
            if ($item['id'] === $id) {
                $targetKey = $k;
                break;
            }
        }

        if ($targetKey === null) {
            http_response_code(404);
            echo json_encode(["message" => "Announcement or poll not found."]);
            exit();
        }

        $foundOption = false;
        foreach ($announcements[$targetKey]['options'] as &$opt) {
            if ($opt['id'] === $optionId) {
                $opt['votes'] = intval($opt['votes'] ?? 0) + 1;
                $foundOption = true;
                break;
            }
        }
        unset($opt);

        if (!$foundOption) {
            http_response_code(400);
            echo json_encode(["message" => "Invalid poll option selected."]);
            exit();
        }

        $total = 0;
        foreach ($announcements[$targetKey]['options'] as $opt) {
            $total += intval($opt['votes'] ?? 0);
        }
        $announcements[$targetKey]['totalVotes'] = $total;

        save_data();

        $sanitized = sanitizeAnnouncement($announcements[$targetKey]);
        $tot = intval($sanitized['totalVotes'] ?? 0);
        foreach ($sanitized['options'] as &$opt) {
            $v = intval($opt['votes'] ?? 0);
            $opt['percent'] = $tot > 0 ? round(($v / $tot) * 100, 1) : 0;
        }
        unset($opt);

        echo json_encode([
            "success" => true,
            "announcement" => $sanitized
        ]);
        exit();
    }

    if (preg_match('/\/api\/announcements\/([^\/]+)$/', $requestUri, $matches) && $method === 'DELETE') {
        $id = $matches[1];
        $newAncs = [];
        $deleted = false;

        foreach ($announcements as $item) {
            if ($item['id'] === $id) {
                $deleted = true;
            } else {
                $newAncs[] = $item;
            }
        }

        if ($deleted) {
            $data['announcements'] = $newAncs;
            $announcements = &$data['announcements'];
            save_data();
            logActivity("ANNOUNCEMENT_DELETED", "Admin deleted announcement ID: " . $id);
            echo json_encode(["success" => true, "message" => "Announcement deleted successfully"]);
            exit();
        }

        http_response_code(404);
        echo json_encode(["message" => "Announcement not found."]);
        exit();
    }

    if ($method === 'GET') {
        $result = [];
        $reversed = array_reverse($announcements);
        foreach ($reversed as $anc) {
            $sanitized = sanitizeAnnouncement($anc);
            $tot = intval($sanitized['totalVotes'] ?? 0);
            foreach ($sanitized['options'] as &$opt) {
                $v = intval($opt['votes'] ?? 0);
                $opt['percent'] = $tot > 0 ? round(($v / $tot) * 100, 1) : 0;
            }
            unset($opt);
            $result[] = $sanitized;
        }
        echo json_encode($result);
        exit();
    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $title = trim($input['title'] ?? '');
        $message = trim($input['message'] ?? ($input['description'] ?? ''));

        if (empty($title) || empty($message)) {
            http_response_code(400);
            echo json_encode(["message" => "Title and announcement message are required."]);
            exit();
        }

        $hasPoll = isset($input['hasPoll']) ? (bool)$input['hasPoll'] : false;
        $rawOptions = isset($input['options']) && is_array($input['options']) ? $input['options'] : [];
        $formattedOptions = [];

        if ($hasPoll && count($rawOptions) > 0) {
            foreach ($rawOptions as $idx => $optText) {
                $optStr = is_array($optText) ? ($optText['text'] ?? '') : strval($optText);
                if (trim($optStr) !== '') {
                    $formattedOptions[] = [
                        "id" => "opt_" . ($idx + 1) . "_" . rand(100, 999),
                        "text" => trim($optStr),
                        "votes" => 0
                    ];
                }
            }
        }

        $newAnc = [
            "id" => getNextAnnouncementId($announcements),
            "title" => $title,
            "message" => $message,
            "category" => !empty($input['category']) ? trim($input['category']) : ($hasPoll ? "Public Poll" : "Announcement"),
            "image" => !empty($input['image']) ? convertGoogleDriveLink(trim($input['image'])) : "",
            "date" => date('Y-m-d'),
            "status" => "active",
            "hasPoll" => $hasPoll && count($formattedOptions) > 0,
            "totalVotes" => 0,
            "options" => $formattedOptions
        ];

        $announcements[] = sanitizeAnnouncement($newAnc);
        save_data();
        logActivity("ANNOUNCEMENT_CREATED", "Admin published new " . ($hasPoll ? "poll" : "announcement") . ": " . $title);

        $lastSanitized = sanitizeAnnouncement(end($announcements));
        $tot = intval($lastSanitized['totalVotes'] ?? 0);
        foreach ($lastSanitized['options'] as &$opt) {
            $v = intval($opt['votes'] ?? 0);
            $opt['percent'] = $tot > 0 ? round(($v / $tot) * 100, 1) : 0;
        }
        unset($opt);

        echo json_encode([
            "success" => true,
            "message" => "Announcement published successfully!",
            "announcement" => $lastSanitized
        ]);
        exit();
    }
}

if (strpos($requestUri, '/api/save-discovered') !== false && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['type']) || !isset($input['items']) || !is_array($input['items'])) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid save payload"]);
        exit();
    }

    $type = $input['type'];
    $items = $input['items'];
    $addedCount = 0;

    if ($type === 'events') {
        foreach ($items as $ev) {
            $ev['id'] = getNextEventId($events);
            $ev['attendees'] = rand(0, 500);
            $ev['sales'] = 0;
            $ev['summary'] = substr($ev['description'] ?? '', 0, 50) . "...";
            $org = trim($ev['organizer'] ?? 'Unknown Organizer');
            $ev['bioText'] = "Organized by " . $org;
            $ev['eventFor'] = 1;
            $ev['hideEvent'] = 1;
            $ev['endDate'] = $ev['date'] ?? date('Y-m-d');
            $ev['noOfDays'] = 1;
            
            // Auto generate eventvenueText and registrationUrl
            $ev['eventvenueText'] = generateVenueText($ev['venue'] ?? '', $ev['city'] ?? '', $ev['state'] ?? '');
            if (empty($ev['registrationUrl'])) {
                $ev['registrationUrl'] = matchLocationUrl($ev['venue'] ?? '');
            }
            // Override with highly relevant curated image
            $ev['image'] = $geminiService->getRelevantImage($ev['title'] ?? '', $ev['description'] ?? '', $ev['category'] ?? '', 'event');
            
            $events[] = sanitizeEvent($ev);
            $addedCount++;
            
            logActivity('AI_DISCOVERY', "AI automatically fetched new event: " . $ev['title']);
        }
    } elseif ($type === 'news') {
        foreach ($items as $nw) {
            $nw['id'] = getNextNewsId($news);
            if (empty($nw['date'])) {
                $nw['date'] = date('Y-m-d');
            }
            // Override with highly relevant curated image
            $nw['image'] = $geminiService->getRelevantImage($nw['title'] ?? '', $nw['summary'] ?? '', 'News', 'news');
            $news[] = sanitizeNews($nw);
            $addedCount++;
            
            logActivity('AI_DISCOVERY', "AI automatically fetched new news: " . $nw['title']);
        }
    } elseif ($type === 'facts') {
        foreach ($items as $ft) {
            $ft['id'] = getNextFactId($facts);
            // Override with highly relevant curated image
            $ft['image'] = $geminiService->getRelevantImage($ft['title'] ?? '', $ft['content'] ?? '', $ft['category'] ?? '', 'fact');
            $facts[] = sanitizeFact($ft);
            $addedCount++;
            
            logActivity('AI_DISCOVERY', "AI automatically fetched new fact: " . $ft['title']);
        }
    }

    if ($addedCount > 0) {
        save_data();
    }

    echo json_encode([
        "message" => "Save completed",
        "count" => $addedCount
    ]);
    exit();
}


// --- NEWS ENDPOINTS ---
if (strpos($requestUri, '/api/news') !== false || (isset($_GET['action']) && $_GET['action'] === 'get_news') || (isset($_GET['type']) && $_GET['type'] === 'news')) {
    $newsId = $_GET['id'] ?? null;
    if (!$newsId && preg_match('/\/api\/news\/([a-zA-Z0-9_-]+)/', $requestUri, $matches)) {
        $newsId = $matches[1];
    }
    
    if ($method === 'GET') {
        if ($newsId) {
            foreach ($news as &$item) {
                if ($item['id'] == $newsId) {
                    $item['clicks'] = intval($item['clicks'] ?? 0) + 1;
                    if (empty($item['image'])) {
                        $item['image'] = $geminiService->getRelevantImage($item['title'] ?? '', $item['summary'] ?? '', 'News', 'news');
                    }
                    save_data();
                    echo json_encode($item);
                    exit();
                }
            }
            unset($item);
            // Fallback for default news n1, n2, n3
            $defaultNews = [
                "1" => [
                    "id" => "1",
                    "title" => "Chennai Metro Rail Phase 2: Tunneling Operations Commence Across Key Corridors",
                    "date" => "2026-05-28",
                    "source" => "The Hindu",
                    "summary" => "The Tamil Nadu government and CMRL have accelerated Phase 2 operations spanning 118.9 km across 3 major corridors, expanding connectivity from Madhavaram to OMR IT corridor.",
                    "image" => "https://images.unsplash.com/photo-1541535650810-10d26f5c2ab3?q=80&w=2069&auto=format&fit=crop",
                    "url" => "https://www.thehindu.com/news/cities/chennai/",
                    "trending" => true
                ],
                "2" => [
                    "id" => "2",
                    "title" => "Parandur Greenfield Airport Project Enters Pre-Construction Phase",
                    "date" => "2026-05-25",
                    "source" => "Chennai Times",
                    "summary" => "The proposed second international airport at Parandur near Chennai receives crucial environmental clearances as land acquisition enters final administrative stages.",
                    "image" => "https://images.unsplash.com/photo-1436491865332-7a61a109cc05?q=80&w=2074&auto=format&fit=crop",
                    "url" => "https://timesofindia.indiatimes.com/city/chennai",
                    "trending" => false
                ],
                "3" => [
                    "id" => "3",
                    "title" => "OMR IT Corridor Expands with 5 New Global Tech Centers in Guindy & Navalur",
                    "date" => "2026-05-29",
                    "source" => "Madras Chronicle",
                    "summary" => "Chennai's premier IT corridor gets a major boost as multinational software and AI firms expand campus footprints, generating over 8,000 technology job opportunities.",
                    "image" => "https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?q=80&w=2070&auto=format&fit=crop",
                    "url" => "https://www.madras.city/",
                    "trending" => true
                ]
            ];
            if (isset($defaultNews[$newsId])) {
                $item = $defaultNews[$newsId];
                $item['image'] = $geminiService->getRelevantImage($item['title'] ?? '', $item['summary'] ?? '', 'News', 'news');
                echo json_encode($item);
                exit();
            }
            http_response_code(404);
            echo json_encode(["message" => "News item not found"]);
            exit();
        } else {
            $category = $_GET['category'] ?? null;
            $filteredNews = $news;
            if ($category && $category !== "All") {
                $filteredNews = array_filter($filteredNews, function($item) use ($category) {
                    return (isset($item['category']) && $item['category'] === $category) || 
                           (isset($item['source']) && $item['source'] === $category);
                });
            }
            usort($filteredNews, function($a, $b) {
                return strtotime($b['date'] ?? '') - strtotime($a['date'] ?? '');
            });

            $today = date('Y-m-d');
            foreach ($filteredNews as &$item) {
                $item['views'] = intval($item['views'] ?? 0) + 1;
                $data['analytics']['history'][$today]['post_impressions'] = intval($data['analytics']['history'][$today]['post_impressions'] ?? 0) + 1;
                if (empty($item['image'])) {
                    $item['image'] = $geminiService->getRelevantImage($item['title'] ?? '', $item['summary'] ?? '', 'News', 'news');
                }
            }
            unset($item);
            save_data();

            echo json_encode(array_values($filteredNews));
            exit();
        }
    }
    
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

        if ($isAdmin) {
            $input['id'] = getNextNewsId($news);
            if (empty($input['date'])) {
                $input['date'] = date('Y-m-d');
            }
            
            // Enforce defaults for bioText, eventFor, and hideEvent
            if (empty($input['bioText'])) {
                $input['bioText'] = "Organized by " . trim($input['source'] ?? 'IndieMa Admin');
            }
            $eventFor = intval($input['eventFor'] ?? 1);
            if ($eventFor <= 0) {
                $eventFor = 1;
            }
            $input['eventFor'] = $eventFor;

            $hideEvent = intval($input['hideEvent'] ?? 1);
            if ($hideEvent !== 1 && $hideEvent !== 0) {
                $hideEvent = 1;
            }
            $input['hideEvent'] = $hideEvent;

            $newsItem = sanitizeNews($input);
            $news[] = $newsItem;
            
            // Log activity
            logActivity("EVENT_CREATED", "Admin manually created news: " . ($newsItem['title'] ?? 'Untitled'));
            
            save_data();
            http_response_code(201);
            echo json_encode($newsItem);
            exit();
        } else {
            if (empty($input['date'])) {
                $input['date'] = date('Y-m-d');
            }
            if (empty($input['bioText'])) {
                $input['bioText'] = "Organized by " . trim($input['source'] ?? 'User Portal');
            }
            $input['eventFor'] = 1;
            $input['hideEvent'] = 1;

            $newsItem = sanitizeNews($input);
            if (!isset($data['pending_posts']) || !is_array($data['pending_posts'])) {
                $data['pending_posts'] = [];
            }

            $pendingPost = [
                "id" => uniqid('p'),
                "type" => "news",
                "data" => $newsItem,
                "submittedBy" => $_SESSION['username'] ?? 'User',
                "submittedEmail" => $_SESSION['email'] ?? '',
                "submittedAt" => date('Y-m-d H:i:s'),
                "status" => "pending"
            ];
            $data['pending_posts'][] = $pendingPost;
            logActivity("POST_SUBMITTED", "User submitted news for approval: " . ($newsItem['title'] ?? 'Untitled'));
            save_data();

            http_response_code(202);
            echo json_encode([
                "message" => "News post submitted for admin approval.",
                "status" => "pending",
                "post" => $pendingPost
            ]);
            exit();
        }
    }
    
    if ($method === 'PATCH') {
        if ($newsId) {
            $input = json_decode(file_get_contents('php://input'), true);
            foreach ($news as &$item) {
                if ($item['id'] == $newsId) {
                    if (isset($input['eventFor'])) {
                        $eventFor = intval($input['eventFor']);
                        if ($eventFor <= 0) {
                            $eventFor = 1;
                        }
                        $input['eventFor'] = $eventFor;
                    }
                    if (isset($input['hideEvent'])) {
                        $hideEvent = intval($input['hideEvent']);
                        if ($hideEvent !== 1 && $hideEvent !== 0) {
                            $hideEvent = 1;
                        }
                        $input['hideEvent'] = $hideEvent;
                    }
                    $item = sanitizeNews(array_merge($item, $input));
                    save_data();
                    echo json_encode($item);
                    exit();
                }
            }
            http_response_code(404);
            echo json_encode(["message" => "News not found"]);
            exit();
        }
    }
    
    if ($method === 'DELETE') {
        // Check if there is a batch delete payload
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['ids']) && is_array($input['ids'])) {
            $idsToDelete = $input['ids'];
            $deletedCount = 0;
            $news = array_filter($news, function($item) use ($idsToDelete, &$activities, &$deletedCount) {
                if (in_array($item['id'], $idsToDelete)) {
                    logActivity("EVENT_DELETED", "Admin bulk deleted news: " . ($item['title'] ?? 'Untitled'));
                    $deletedCount++;
                    return false;
                }
                return true;
            });
            $news = array_values($news);
            save_data();
            echo json_encode(["message" => "$deletedCount news articles deleted"]);
            exit();
        }

        if ($newsId) {
            foreach ($news as $key => $item) {
                if ($item['id'] == $newsId) {
                    $deletedTitle = $item['title'] ?? 'Untitled';
                    array_splice($news, $key, 1);
                    logActivity("EVENT_DELETED", "Admin deleted news: " . $deletedTitle);
                    save_data();
                    echo json_encode(["message" => "News deleted"]);
                    exit();
                }
            }
            http_response_code(404);
            echo json_encode(["message" => "News item not found"]);
            exit();
        } else {
            $news = [];
            logActivity("EVENT_DELETED", "Admin deleted all news articles");
            save_data();
            echo json_encode(["message" => "All news articles deleted"]);
            exit();
        }
    }
}

// --- FACTS ENDPOINTS ---
if (strpos($requestUri, '/api/facts') !== false || (isset($_GET['action']) && $_GET['action'] === 'get_facts') || (isset($_GET['type']) && $_GET['type'] === 'facts')) {
    $factId = $_GET['id'] ?? null;
    if (!$factId && preg_match('/\/api\/facts\/([a-zA-Z0-9_-]+)/', $requestUri, $matches)) {
        $factId = $matches[1];
    }
    
    if ($method === 'GET') {
        if ($factId) {
            foreach ($facts as &$item) {
                if ($item['id'] == $factId) {
                    $item['clicks'] = intval($item['clicks'] ?? 0) + 1;
                    if (empty($item['image'])) {
                        $item['image'] = $geminiService->getRelevantImage($item['title'] ?? '', $item['content'] ?? '', $item['category'] ?? '', 'fact');
                    }
                    save_data();
                    echo json_encode($item);
                    exit();
                }
            }
            unset($item);
            // Fallback for default facts f1, f2, f3
            $defaultFacts = [
                "1" => [
                    "id" => "1",
                    "title" => "Fort St. George - Birthplace of Modern Madras (1640)",
                    "content" => "Founded in 1640 AD on the Coromandel Coast, Fort St. George stands as the historic nucleus of modern Madras, housing St. Mary's Church and the Tamil Nadu State Secretariat.",
                    "category" => "History",
                    "image" => "chennai_spotlight.png"
                ],
                "2" => [
                    "id" => "2",
                    "title" => "Marina Beach - Second Longest Natural Urban Beach in the World",
                    "content" => "Stretching 13 kilometers from Fort St. George down to Besant Nagar, Marina Beach is the iconic coastline of Chennai, drawing thousands of visitors daily.",
                    "category" => "Nature",
                    "image" => "chennai_spotlight.png"
                ],
                "3" => [
                    "id" => "3",
                    "title" => "Detroit of South India - Automobile Manufacturing Giant",
                    "content" => "Chennai manufactures over 40% of India's automobiles and 60% of auto component exports, earning its title as the Detroit of South Asia.",
                    "category" => "Industry",
                    "image" => "chennai_spotlight.png"
                ]
            ];
            if (isset($defaultFacts[$factId])) {
                $item = $defaultFacts[$factId];
                if (empty($item['image'])) {
                    $item['image'] = $geminiService->getRelevantImage($item['title'] ?? '', $item['content'] ?? '', $item['category'] ?? '', 'fact');
                }
                echo json_encode($item);
                exit();
            }
            http_response_code(404);
            echo json_encode(["message" => "Fact not found"]);
            exit();
        } else {
            $category = $_GET['category'] ?? null;
            $filteredFacts = $facts;
            if ($category && $category !== "All") {
                $filteredFacts = array_filter($filteredFacts, function($item) use ($category) {
                    return isset($item['category']) && $item['category'] === $category;
                });
            }

            $today = date('Y-m-d');
            foreach ($filteredFacts as &$item) {
                $item['views'] = intval($item['views'] ?? 0) + 1;
                $data['analytics']['history'][$today]['post_impressions'] = intval($data['analytics']['history'][$today]['post_impressions'] ?? 0) + 1;
                if (empty($item['image'])) {
                    $item['image'] = $geminiService->getRelevantImage($item['title'] ?? '', $item['content'] ?? '', $item['category'] ?? '', 'fact');
                }
            }
            unset($item);
            save_data();

            echo json_encode(array_values($filteredFacts));
            exit();
        }
    }
    
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

        if ($isAdmin) {
            $input['id'] = getNextFactId($facts);
            
            if (empty($input['bioText'])) {
                $input['bioText'] = "Organized by IndieMa Admin";
            }
            $eventFor = intval($input['eventFor'] ?? 1);
            if ($eventFor <= 0) {
                $eventFor = 1;
            }
            $input['eventFor'] = $eventFor;

            $hideEvent = intval($input['hideEvent'] ?? 1);
            if ($hideEvent !== 1 && $hideEvent !== 0) {
                $hideEvent = 1;
            }
            $input['hideEvent'] = $hideEvent;

            $factItem = sanitizeFact($input);
            $facts[] = $factItem;
            
            logActivity("EVENT_CREATED", "Admin manually created fact: " . ($factItem['title'] ?? 'Untitled'));
            
            save_data();
            http_response_code(201);
            echo json_encode($factItem);
            exit();
        } else {
            if (empty($input['bioText'])) {
                $input['bioText'] = "Organized by User Portal";
            }
            $input['eventFor'] = 1;
            $input['hideEvent'] = 1;

            $factItem = sanitizeFact($input);
            if (!isset($data['pending_posts']) || !is_array($data['pending_posts'])) {
                $data['pending_posts'] = [];
            }

            $pendingPost = [
                "id" => uniqid('p'),
                "type" => "fact",
                "data" => $factItem,
                "submittedBy" => $_SESSION['username'] ?? 'User',
                "submittedEmail" => $_SESSION['email'] ?? '',
                "submittedAt" => date('Y-m-d H:i:s'),
                "status" => "pending"
            ];
            $data['pending_posts'][] = $pendingPost;
            logActivity("POST_SUBMITTED", "User submitted fact for approval: " . ($factItem['title'] ?? 'Untitled'));
            save_data();

            http_response_code(202);
            echo json_encode([
                "message" => "Fact post submitted for admin approval.",
                "status" => "pending",
                "post" => $pendingPost
            ]);
            exit();
        }
    }
    
    if ($method === 'PATCH') {
        if ($factId) {
            $input = json_decode(file_get_contents('php://input'), true);
            foreach ($facts as &$item) {
                if ($item['id'] == $factId) {
                    if (isset($input['eventFor'])) {
                        $eventFor = intval($input['eventFor']);
                        if ($eventFor <= 0) {
                            $eventFor = 1;
                        }
                        $input['eventFor'] = $eventFor;
                    }
                    if (isset($input['hideEvent'])) {
                        $hideEvent = intval($input['hideEvent']);
                        if ($hideEvent !== 1 && $hideEvent !== 0) {
                            $hideEvent = 1;
                        }
                        $input['hideEvent'] = $hideEvent;
                    }
                    $item = sanitizeFact(array_merge($item, $input));
                    save_data();
                    echo json_encode($item);
                    exit();
                }
            }
            http_response_code(404);
            echo json_encode(["message" => "Fact not found"]);
            exit();
        }
    }
    
    if ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['ids']) && is_array($input['ids'])) {
            $idsToDelete = $input['ids'];
            $deletedCount = 0;
            $facts = array_filter($facts, function($item) use ($idsToDelete, &$activities, &$deletedCount) {
                if (in_array($item['id'], $idsToDelete)) {
                    logActivity("EVENT_DELETED", "Admin bulk deleted fact: " . ($item['title'] ?? 'Untitled'));
                    $deletedCount++;
                    return false;
                }
                return true;
            });
            $facts = array_values($facts);
            save_data();
            echo json_encode(["message" => "$deletedCount facts deleted"]);
            exit();
        }

        if ($factId) {
            foreach ($facts as $key => $item) {
                if ($item['id'] == $factId) {
                    $deletedTitle = $item['title'] ?? 'Untitled';
                    array_splice($facts, $key, 1);
                    logActivity("EVENT_DELETED", "Admin deleted fact: " . $deletedTitle);
                    save_data();
                    echo json_encode(["message" => "Fact deleted"]);
                    exit();
                }
            }
            http_response_code(404);
            echo json_encode(["message" => "Fact not found"]);
            exit();
        } else {
            $facts = [];
            logActivity("EVENT_DELETED", "Admin deleted all facts");
            save_data();
            echo json_encode(["message" => "All facts deleted"]);
            exit();
        }
    }
}


// Handle fetching/editing by ID using query param or path
$eventId = $_GET['id'] ?? null;

if (strpos($requestUri, '/api/events') !== false || isset($_GET['action']) || isset($_GET['id'])) {
    if (!$eventId && preg_match('/\/api\/events\/([a-zA-Z0-9_-]+)/', $requestUri, $matches)) {
        $eventId = $matches[1];
    }

    if ($method === 'GET') {
        if ($eventId) {
            foreach ($events as &$event) {
                if ($event['id'] == $eventId) {
                    $event['clicks'] = intval($event['clicks'] ?? 0) + 1;
                    $event['bioText'] = "Organized by " . ($event['organizer'] ?? 'Unknown Organizer');
                    if (empty($event['image'])) {
                        $event['image'] = $geminiService->getRelevantImage($event['title'] ?? '', $event['description'] ?? '', $event['category'] ?? '', 'event');
                    }
                    save_data();
                    echo json_encode($event);
                    exit();
                }
            }
            unset($event);
            // Fallback for default events 1, 2
            $defaultEvents = [
                "1" => [
                    "id" => "1",
                    "title" => "Madras Carnatic & Heritage Cultural Festival 2026",
                    "date" => date('Y-m-d', strtotime('+7 days')),
                    "time" => "09:00 AM",
                    "venue" => "Kalakshetra Foundation, Thiruvanmiyur",
                    "organizer" => "Madras Cultural Academy",
                    "bioText" => "Organized by Madras Cultural Academy",
                    "eventFor" => 1,
                    "hideEvent" => 1,
                    "endDate" => date('Y-m-d', strtotime('+7 days')),
                    "noOfDays" => 1,
                    "eventvenueText" => "Kalakshetra Foundation, located in Thiruvanmiyur, Chennai, offers a comfortable and elegant space for hosting memorable events and celebrations.",
                    "registrationUrl" => "https://www.kalakshetra.in/",
                    "category" => "Live Concerts",
                    "image" => "chennai_events.png",
                    "description" => "The grand annual celebration of classical South Indian music, dance, and fine arts in Chennai. Featuring legendary vocalists and instrumental performances.",
                    "price" => "₹200 onwards",
                    "trending" => true,
                    "attendees" => 3500,
                    "sales" => 850,
                    "summary" => "Grand classical music and arts festival in Chennai."
                ],
                "2" => [
                    "id" => "2",
                    "title" => "IIT Madras AI & DeepTech Summit 2026",
                    "date" => date('Y-m-d', strtotime('+14 days')),
                    "time" => "08:30 AM",
                    "venue" => "IIT Madras Research Park, Taramani",
                    "organizer" => "IITM Innovation Cell",
                    "bioText" => "Organized by IITM Innovation Cell",
                    "eventFor" => 1,
                    "hideEvent" => 1,
                    "endDate" => date('Y-m-d', strtotime('+14 days')),
                    "noOfDays" => 1,
                    "eventvenueText" => "IIT Madras Research Park, located in Taramani, Chennai, offers a modern and technology-driven space for hosting hackathons and tech summits.",
                    "registrationUrl" => "https://www.iitm.ac.in/",
                    "category" => "Workshops",
                    "image" => "chennai_events.png",
                    "description" => "A 24-hour hackathon and tech summit bringing together student developers, AI researchers, and tech founders across Chennai.",
                    "price" => "Free",
                    "trending" => true,
                    "attendees" => 600,
                    "sales" => 0,
                    "summary" => "Premier AI & DeepTech developer summit in Chennai."
                ]
            ];
            if (isset($defaultEvents[$eventId])) {
                $event = $defaultEvents[$eventId];
                $event['bioText'] = "Organized by " . ($event['organizer'] ?? 'Unknown Organizer');
                if (empty($event['image'])) {
                    $event['image'] = $geminiService->getRelevantImage($event['title'] ?? '', $event['description'] ?? '', $event['category'] ?? '', 'event');
                }
                echo json_encode($event);
                exit();
            }
            http_response_code(404);
            echo json_encode(["message" => "Event not found", "requested_id" => $eventId]);
            exit();
        } else {
            // List events
            $category = $_GET['category'] ?? null;
            $search = $_GET['search'] ?? null;
            $sort = $_GET['sort'] ?? null;

            $filteredEvents = $events;

            // Exclude events that have already passed (endDate or date < today)
            $todayMidnight = strtotime(date('Y-m-d'));
            $filteredEvents = array_filter($filteredEvents, function($e) use ($todayMidnight) {
                $dateStr = !empty($e['endDate']) ? $e['endDate'] : ($e['date'] ?? '');
                if (!$dateStr || strtolower($dateStr) === 'tba') return true;
                $ts = strtotime($dateStr);
                return $ts === false || $ts >= $todayMidnight;
            });

            foreach ($filteredEvents as &$e) {
                $e['bioText'] = "Organized by " . ($e['organizer'] ?? 'Unknown Organizer');
                if (empty($e['image'])) {
                    $e['image'] = $geminiService->getRelevantImage($e['title'] ?? '', $e['description'] ?? '', $e['category'] ?? '', 'event');
                }
            }
            unset($e);

            if ($category && $category !== "All") {
                $filteredEvents = array_filter($filteredEvents, function($e) use ($category) {
                    return isset($e['category']) && $e['category'] === $category;
                });
            }

            if ($search) {
                $s = strtolower($search);
                $filteredEvents = array_filter($filteredEvents, function($e) use ($s) {
                    return (strpos(strtolower($e['title']), $s) !== false) || 
                           (strpos(strtolower($e['description']), $s) !== false);
                });
            }

            if ($sort === "trending") {
                usort($filteredEvents, function($a, $b) {
                    $aTrend = !empty($a['trending']) ? 1 : 0;
                    $bTrend = !empty($b['trending']) ? 1 : 0;
                    return $bTrend - $aTrend;
                });
            } else if ($sort === "latest") {
                usort($filteredEvents, function($a, $b) {
                    return strtotime($b['date']) - strtotime($a['date']);
                });
            }

            echo json_encode(array_values($filteredEvents));
            exit();
        }
    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

        // Backend validation and duplicate prevention for bioText
        $input['bioText'] = "Organized by " . trim($input['organizer'] ?? 'Unknown Organizer');
        
        // Ensure eventFor defaults to 0 (Public) and validation is applied
        if (isset($input['type'])) {
            $eventFor = (strtolower($input['type']) === 'public') ? 0 : 1;
        } else {
            $eventFor = intval($input['eventFor'] ?? 0);
        }
        if ($eventFor < 0) {
            $eventFor = 0;
        }
        $input['eventFor'] = $eventFor;
        
        // Ensure hideEvent defaults to 1 and validation is applied
        $hideEvent = intval($input['hideEvent'] ?? 1);
        if ($hideEvent !== 1 && $hideEvent !== 0) {
            $hideEvent = 1;
        }
        $input['hideEvent'] = $hideEvent;

        // Calculate totalDays timezone-safely on backend
        $startDateStr = $input['date'] ?? null;
        $endDateStr = $input['endDate'] ?? null;
        $totalDays = 1;
        if ($startDateStr) {
            if (!$endDateStr) {
                $endDateStr = $startDateStr;
            }
            try {
                $start = new DateTime($startDateStr, new DateTimeZone('UTC'));
                $end = new DateTime($endDateStr, new DateTimeZone('UTC'));
                if ($end >= $start) {
                    $diff = $start->diff($end);
                    $totalDays = $diff->days + 1;
                } else {
                    $endDateStr = $startDateStr;
                }
            } catch (Exception $e) {
                $totalDays = 1;
            }
        }
        $input['endDate'] = $endDateStr;
        $input['noOfDays'] = $totalDays;
        
        // Auto generate eventvenueText and registrationUrl
        $venue = trim($input['venue'] ?? '');
        $city = trim($input['city'] ?? '');
        $state = trim($input['state'] ?? '');
        $input['eventvenueText'] = generateVenueText($venue, $city, $state);
        
        $regUrl = trim($input['registrationUrl'] ?? '');
        if (empty($regUrl)) {
            $regUrl = matchLocationUrl($venue);
        }
        $input['registrationUrl'] = $regUrl;
        
        if ($isAdmin) {
            $input['id'] = getNextEventId($events);
            $events[] = sanitizeEvent($input);
            
            // Log activity
            logActivity("EVENT_CREATED", "Admin manually created event: " . ($input['title'] ?? 'Untitled Event'));
            
            save_data();
            http_response_code(201);
            echo json_encode($input);
            exit();
        } else {
            $eventItem = sanitizeEvent($input);
            if (!isset($data['pending_posts']) || !is_array($data['pending_posts'])) {
                $data['pending_posts'] = [];
            }

            $pendingPost = [
                "id" => uniqid('p'),
                "type" => "event",
                "data" => $eventItem,
                "submittedBy" => $_SESSION['username'] ?? 'User',
                "submittedEmail" => $_SESSION['email'] ?? '',
                "submittedAt" => date('Y-m-d H:i:s'),
                "status" => "pending"
            ];
            $data['pending_posts'][] = $pendingPost;
            logActivity("POST_SUBMITTED", "User submitted event for approval: " . ($eventItem['title'] ?? 'Untitled'));
            save_data();

            http_response_code(202);
            echo json_encode([
                "message" => "Event post submitted for admin approval.",
                "status" => "pending",
                "post" => $pendingPost
            ]);
            exit();
        }
    } elseif ($method === 'PATCH') {
        if ($eventId) {
            $input = json_decode(file_get_contents('php://input'), true);
            foreach ($events as &$event) {
                if ($event['id'] == $eventId) {
                    // Ensure eventFor defaults to 0 (Public) and validation is applied
                    if (isset($input['type'])) {
                        $eventFor = (strtolower($input['type']) === 'public') ? 0 : 1;
                    } else {
                        $eventFor = intval($input['eventFor'] ?? $event['eventFor'] ?? 0);
                    }
                    if ($eventFor < 0) {
                        $eventFor = 0;
                    }
                    $input['eventFor'] = $eventFor;
                    
                    // Ensure hideEvent defaults to 1 and validation is applied
                    $hideEvent = intval($input['hideEvent'] ?? $event['hideEvent'] ?? 1);
                    if ($hideEvent !== 1 && $hideEvent !== 0) {
                        $hideEvent = 1;
                    }
                    $input['hideEvent'] = $hideEvent;

                    // Calculate totalDays timezone-safely on backend
                    $startDateStr = $input['date'] ?? $event['date'] ?? null;
                    $endDateStr = $input['endDate'] ?? $event['endDate'] ?? null;
                    $totalDays = 1;
                    if ($startDateStr) {
                        if (!$endDateStr) {
                            $endDateStr = $startDateStr;
                        }
                        try {
                            $start = new DateTime($startDateStr, new DateTimeZone('UTC'));
                            $end = new DateTime($endDateStr, new DateTimeZone('UTC'));
                            if ($end >= $start) {
                                $diff = $start->diff($end);
                                $totalDays = $diff->days + 1;
                            } else {
                                $endDateStr = $startDateStr;
                            }
                        } catch (Exception $e) {
                            $totalDays = 1;
                        }
                    }
                    $input['endDate'] = $endDateStr;
                    $input['noOfDays'] = $totalDays;
                    // Auto generate eventvenueText and registrationUrl on PATCH
                    $venue = trim($input['venue'] ?? $event['venue'] ?? '');
                    $city = trim($input['city'] ?? $event['city'] ?? '');
                    $state = trim($input['state'] ?? $event['state'] ?? '');
                    if (!isset($input['eventvenueText']) || empty(trim($input['eventvenueText']))) {
                        $input['eventvenueText'] = generateVenueText($venue, $city, $state);
                    }
                    
                    $regUrl = trim($input['registrationUrl'] ?? $event['registrationUrl'] ?? '');
                    if (empty($regUrl)) {
                        $regUrl = matchLocationUrl($venue);
                    }
                    $input['registrationUrl'] = $regUrl;
                    
                    // Backend validation and duplicate prevention for bioText
                    $input['bioText'] = "Organized by " . trim($input['organizer'] ?? $event['organizer'] ?? 'Unknown Organizer');
                    
                    $event = sanitizeEvent(array_merge($event, $input));
                    save_data();
                    echo json_encode($event);
                    exit();
                }
            }
            http_response_code(404);
            echo json_encode(["message" => "Event not found"]);
            exit();
        }
    } elseif ($method === 'DELETE') {
        // Check if there is a batch delete payload
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['ids']) && is_array($input['ids'])) {
            $idsToDelete = $input['ids'];
            $deletedCount = 0;
            $events = array_filter($events, function($event) use ($idsToDelete, &$activities, &$deletedCount) {
                if (in_array($event['id'], $idsToDelete)) {
                    $deletedTitle = $event['title'] ?? 'Untitled Event';
                    logActivity("EVENT_DELETED", "Admin bulk deleted event: " . $deletedTitle);
                    $deletedCount++;
                    return false;
                }
                return true;
            });
            $events = array_values($events);
            save_data();
            echo json_encode(["message" => "$deletedCount events deleted"]);
            exit();
        }

        if ($eventId) {
            foreach ($events as $key => $event) {
                if ($event['id'] == $eventId) {
                    $deletedTitle = $event['title'] ?? 'Untitled Event';
                    array_splice($events, $key, 1);
                    
                    // Log activity
                    logActivity("EVENT_DELETED", "Admin deleted event: " . $deletedTitle);
                    
                    save_data();
                    echo json_encode(["message" => "Event deleted"]);
                    exit();
                }
            }
            http_response_code(404);
            echo json_encode(["message" => "Event not found"]);
            exit();
        } else {
            // Delete all events
            $events = [];
            
            // Log activity
            logActivity("EVENT_DELETED", "Admin deleted all events");
            
            save_data();
            echo json_encode(["message" => "All events deleted"]);
            exit();
        }
    }
}

if (strpos($requestUri, '/api/analytics') !== false && $method === 'GET') {
    $totalAttendees = 0;
    $categoryStats = [];
    foreach ($events as $e) {
        $totalAttendees += isset($e['attendees']) ? $e['attendees'] : 0;
        $cat = isset($e['category']) ? $e['category'] : 'Uncategorized';
        if (!isset($categoryStats[$cat])) $categoryStats[$cat] = 0;
        $categoryStats[$cat]++;
    }

    $stats = [
        "totalEvents" => count($events),
        "totalNews" => count($news),
        "totalFacts" => count($facts),
        "totalUsers" => count($users),
        "totalAttendees" => $totalAttendees,
        "categoryStats" => $categoryStats,
        "recentActivity" => $activities
    ];
    echo json_encode($stats);
    exit();
}

// --- PENDING POSTS ENDPOINTS (ADMIN ONLY) ---
if (strpos($requestUri, '/api/pending-posts') !== false) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(["message" => "Forbidden. Admin access required."]);
        exit();
    }

    $postId = null;
    $isApprove = false;
    $isReject = false;

    if (preg_match('/\/api\/pending-posts\/([a-zA-Z0-9_-]+)\/approve/', $requestUri, $matches)) {
        $postId = $matches[1];
        $isApprove = true;
    } else if (preg_match('/\/api\/pending-posts\/([a-zA-Z0-9_-]+)\/reject/', $requestUri, $matches)) {
        $postId = $matches[1];
        $isReject = true;
    } else if (preg_match('/\/api\/pending-posts\/([a-zA-Z0-9_-]+)/', $requestUri, $matches)) {
        $postId = $matches[1];
    }

    if (!isset($data['pending_posts']) || !is_array($data['pending_posts'])) {
        $data['pending_posts'] = [];
    }

    if ($method === 'GET') {
        if ($postId) {
            foreach ($data['pending_posts'] as $post) {
                if ($post['id'] == $postId) {
                    echo json_encode($post);
                    exit();
                }
            }
            http_response_code(404);
            echo json_encode(["message" => "Pending post not found"]);
            exit();
        } else {
            echo json_encode($data['pending_posts']);
            exit();
        }
    }

    if ($method === 'POST' || $method === 'PATCH') {
        if ($isApprove && $postId) {
            $foundIndex = -1;
            foreach ($data['pending_posts'] as $index => $post) {
                if ($post['id'] == $postId) {
                    $foundIndex = $index;
                    break;
                }
            }

            if ($foundIndex === -1) {
                http_response_code(404);
                echo json_encode(["message" => "Pending post not found"]);
                exit();
            }

            $pendingPost = $data['pending_posts'][$foundIndex];
            $type = $pendingPost['type'];
            $postData = $pendingPost['data'];

            // Save to the actual database table
            if ($type === 'event') {
                $postData['id'] = getNextEventId($events);
                $events[] = sanitizeEvent($postData);
                $title = $postData['title'] ?? 'Untitled Event';
                logActivity("EVENT_CREATED", "Admin approved event: " . $title);
            } else if ($type === 'news') {
                $postData['id'] = getNextNewsId($news);
                $news[] = sanitizeNews($postData);
                $title = $postData['title'] ?? 'Untitled News';
                logActivity("EVENT_CREATED", "Admin approved news: " . $title);
            } else if ($type === 'fact') {
                $postData['id'] = getNextFactId($facts);
                $facts[] = sanitizeFact($postData);
                $title = $postData['title'] ?? 'Untitled Fact';
                logActivity("EVENT_CREATED", "Admin approved fact: " . $title);
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Invalid post type: " . $type]);
                exit();
            }

            // Remove from pending_posts
            array_splice($data['pending_posts'], $foundIndex, 1);
            save_data();

            echo json_encode(["message" => "Post approved successfully", "type" => $type, "post" => $postData]);
            exit();
        }

        if ($isReject && $postId) {
            $foundIndex = -1;
            foreach ($data['pending_posts'] as $index => $post) {
                if ($post['id'] == $postId) {
                    $foundIndex = $index;
                    break;
                }
            }

            if ($foundIndex === -1) {
                http_response_code(404);
                echo json_encode(["message" => "Pending post not found"]);
                exit();
            }

            $pendingPost = $data['pending_posts'][$foundIndex];
            $type = $pendingPost['type'];
            $title = $pendingPost['data']['title'] ?? 'Untitled';

            // Remove from pending_posts
            array_splice($data['pending_posts'], $foundIndex, 1);
            logActivity("EVENT_DELETED", "Admin rejected " . $type . ": " . $title);
            save_data();

            echo json_encode(["message" => "Post rejected successfully"]);
            exit();
        }
    }

    if ($method === 'DELETE') {
        if ($postId) {
            foreach ($data['pending_posts'] as $key => $post) {
                if ($post['id'] == $postId) {
                    array_splice($data['pending_posts'], $key, 1);
                    save_data();
                    echo json_encode(["message" => "Pending post deleted"]);
                    exit();
                }
            }
            http_response_code(404);
            echo json_encode(["message" => "Pending post not found"]);
            exit();
        }
    }
}

// --- ADS / PROMOTIONS ENDPOINTS ---
if (strpos($requestUri, '/api/ads') !== false || strpos($requestUri, '/api/promotions') !== false) {
    if (strpos($requestUri, '/api/ads/booking-stats') !== false || strpos($requestUri, '/api/promotions/booking-stats') !== false) {
        $stats = [];
        foreach ($ads as $ad) {
            $status = strtolower($ad['status'] ?? 'approved');
            if ($status === 'approved') {
                $month = $ad['month'] ?? '';
                $category = $ad['category'] ?? '';
                $subCategory = $ad['subCategory'] ?? '';
                if ($month && $category) {
                    $key = $month . '|' . $category;
                    if (!isset($stats[$key])) {
                        $stats[$key] = 0;
                    }
                    $stats[$key]++;

                    if (!empty($subCategory)) {
                        $subKey = $month . '|' . $category . '|' . $subCategory;
                        if (!isset($stats[$subKey])) {
                            $stats[$subKey] = 0;
                        }
                        $stats[$subKey]++;
                    }
                }
            }
        }
        echo json_encode($stats);
        exit();
    }

    $adId = null;
    $isClick = false;
    $isView = false;
    if (preg_match('/\/api\/(?:ads|promotions)\/([a-zA-Z0-9_-]+)\/click/', $requestUri, $matches)) {
        $adId = $matches[1];
        $isClick = true;
    } else if (preg_match('/\/api\/(?:ads|promotions)\/([a-zA-Z0-9_-]+)\/view/', $requestUri, $matches)) {
        $adId = $matches[1];
        $isView = true;
    } else if (preg_match('/\/api\/(?:ads|promotions)\/([a-zA-Z0-9_-]+)/', $requestUri, $matches)) {
        $adId = $matches[1];
    }

    if ($method === 'GET') {
        if ($adId) {
            foreach ($ads as $ad) {
                if ($ad['id'] == $adId) {
                    $ad['status'] = $ad['status'] ?? 'approved';
                    $vCount = intval($ad['views'] ?? 0);
                    $cCount = intval($ad['clicks'] ?? 0);
                    $ad['avg_time_seconds'] = intval($ad['avg_time_seconds'] ?? ($vCount > 0 ? round(($vCount * 18 + $cCount * 12) / $vCount) : 0));

                    if (!isset($ad['devices']) || !is_array($ad['devices']) || empty($ad['devices'])) {
                        $ad['devices'] = [
                            'desktop' => ['views' => 0, 'clicks' => 0],
                            'mobile'  => ['views' => 0, 'clicks' => 0],
                            'tablet'  => ['views' => 0, 'clicks' => 0]
                        ];
                    }
                    echo json_encode($ad);
                    exit();
                }
            }
            http_response_code(404);
            echo json_encode(["message" => "Ad not found"]);
            exit();
        } else {
            $category = $_GET['category'] ?? null;
            $status = $_GET['status'] ?? null;
            $all = $_GET['all'] ?? null;
            
            // Build list of blocked user identifiers
            $blockedEmails = [];
            $blockedNames = [];
            $blockedIds = [];
            foreach ($users as $uCheck) {
                if (!empty($uCheck['is_blocked']) || (isset($uCheck['status']) && strtolower($uCheck['status']) === 'blocked')) {
                    if (!empty($uCheck['email'])) $blockedEmails[] = strtolower(trim($uCheck['email']));
                    if (!empty($uCheck['name'])) $blockedNames[] = strtolower(trim($uCheck['name']));
                    if (!empty($uCheck['username'])) $blockedNames[] = strtolower(trim($uCheck['username']));
                    if (!empty($uCheck['id'])) $blockedIds[] = strval($uCheck['id']);
                }
            }

            $isOwnerBlocked = function(array $adItem) use ($blockedEmails, $blockedNames, $blockedIds): bool {
                $adEmail = strtolower(trim($adItem['email'] ?? ''));
                $adPostedBy = strtolower(trim($adItem['postedBy'] ?? ''));
                $adUserId = strval($adItem['userId'] ?? '');
                
                if ($adEmail !== '' && in_array($adEmail, $blockedEmails, true)) return true;
                if ($adPostedBy !== '' && in_array($adPostedBy, $blockedNames, true)) return true;
                if ($adUserId !== '' && in_array($adUserId, $blockedIds, true)) return true;
                return false;
            };
            
            $filteredAds = [];
            foreach ($ads as $ad) {
                $ad['status'] = $ad['status'] ?? 'approved';
                // If fetching for public display (all != true), skip ads from blocked users
                if ($all !== 'true' && $isOwnerBlocked($ad)) {
                    continue;
                }
                $filteredAds[] = $ad;
            }

            if ($status) {
                $filteredAds = array_filter($filteredAds, function($item) use ($status) {
                    return strtolower($item['status'] ?? 'approved') === strtolower($status);
                });
            } else if ($all !== 'true') {
                $currentMonth = date('F Y');
                $filteredAds = array_filter($filteredAds, function($item) use ($currentMonth) {
                    $isApproved = strtolower($item['status'] ?? 'approved') === 'approved';
                    $hasContent = (!empty(trim($item['companyName'] ?? '')) || !empty(trim($item['description'] ?? '')) || !empty(trim($item['adTitle'] ?? '')));
                    $isMonthMatch = !empty($item['month']) ? (strtolower(trim($item['month'])) === strtolower(trim($currentMonth))) : true;
                    return $isApproved && $hasContent && $isMonthMatch;
                });
            }

            if ($category && $category !== "All") {
                if ($all !== 'true' && $status === null) {
                    $matchingAds = [];
                    $currentMonth = date('F Y'); // e.g. "August 2026"
                    $cleanCategory = str_replace(' page', '', strtolower($category));

                    $targetCategories = [$cleanCategory];
                    if (in_array($cleanCategory, ['new-in-che', 'new-in-cbe', 'spotlight', 'new in che', 'new in cbe'], true)) {
                        $targetCategories = ['new-in-che', 'new-in-cbe', 'spotlight', 'new in che', 'new in cbe'];
                    } elseif (in_array($cleanCategory, ['city-news', 'city news', 'news'], true)) {
                        $targetCategories = ['city-news', 'city news', 'news'];
                    } elseif (in_array($cleanCategory, ['events', 'event'], true)) {
                        $targetCategories = ['events', 'event'];
                    } elseif (in_array($cleanCategory, ['homepage', 'home'], true)) {
                        $targetCategories = ['homepage', 'home', 'homepage page'];
                    }

                    foreach ($ads as &$ad) {
                        if ($isOwnerBlocked($ad)) {
                            continue;
                        }
                        $adStatus = $ad['status'] ?? 'approved';
                        $adCat = $ad['category'] ?? '';
                        $adViews = isset($ad['views']) ? intval($ad['views']) : 0;
                        $hasContent = (!empty(trim($ad['companyName'] ?? '')) || !empty(trim($ad['description'] ?? '')) || !empty(trim($ad['adTitle'] ?? '')));
                        
                        $isMonthMatch = true;
                        if (!empty($ad['month'])) {
                            $isMonthMatch = (strtolower(trim($ad['month'])) === strtolower(trim($currentMonth)));
                        } else {
                            $adLimit = isset($ad['totalViews']) ? intval($ad['totalViews']) : (isset($ad['budget']) ? intval($ad['budget']) : 100);
                            $isMonthMatch = ($adViews < $adLimit);
                        }
                        
                        $cleanAdCat = str_replace(' page', '', strtolower($adCat));
                        if (strtolower($adStatus) === 'approved' && $hasContent && in_array($cleanAdCat, $targetCategories, true) && $isMonthMatch) {
                            $matchingAds[] = &$ad;
                        }
                    }
                    unset($ad);

                    // Fallback: If no category-specific ads match, serve any approved active ad so ads are always visible (EXCEPT for Homepage where only Homepage ads must be displayed)
                    if (empty($matchingAds) && !in_array($cleanCategory, ['homepage', 'home', 'homepage page'], true)) {
                        foreach ($ads as &$ad) {
                            if ($isOwnerBlocked($ad)) continue;
                            $adStatus = $ad['status'] ?? 'approved';
                            $hasContent = (!empty(trim($ad['companyName'] ?? '')) || !empty(trim($ad['description'] ?? '')) || !empty(trim($ad['adTitle'] ?? '')));
                            if (strtolower($adStatus) === 'approved' && $hasContent) {
                                $matchingAds[] = &$ad;
                            }
                        }
                        unset($ad);
                    }

                    // Enforce display limit of max 5 ads per subCategory per month for Classified page or max 5 for other pages
                    if (str_replace(' page', '', strtolower($category)) === 'classified') {
                        $groupedBySubCat = [];
                        $cappedMatchingAds = [];
                        foreach ($matchingAds as &$mAd) {
                            $subCatKey = strtolower($mAd['subCategory'] ?? 'general');
                            if (!isset($groupedBySubCat[$subCatKey])) {
                                $groupedBySubCat[$subCatKey] = 0;
                            }
                            if ($groupedBySubCat[$subCatKey] < 5) {
                                $groupedBySubCat[$subCatKey]++;
                                $cappedMatchingAds[] = &$mAd;
                            }
                        }
                        unset($mAd);
                        $matchingAds = $cappedMatchingAds;
                    } else if (count($matchingAds) > 5) {
                        $matchingAds = array_slice($matchingAds, 0, 5);
                    }

                    if (count($matchingAds) > 0) {
                        // Start session if not already started to track the last displayed ad ID for alternating rotation
                        if (session_status() === PHP_SESSION_NONE) {
                            session_start();
                        }
                        
                        $sessionKey = 'last_ad_id_' . $cleanCategory;
                        $lastAdId = $_SESSION[$sessionKey] ?? null;
                        
                        // Find the index of the last displayed ad in the current matching list
                        $lastIdx = -1;
                        foreach ($matchingAds as $idx => $mAd) {
                            if (isset($mAd['id']) && $mAd['id'] === $lastAdId) {
                                $lastIdx = $idx;
                                break;
                            }
                        }
                        
                        // Select the next ad in the sequence (alternating/round-robin)
                        $nextIdx = ($lastIdx + 1) % count($matchingAds);
                        $selectedAd = &$matchingAds[$nextIdx];
                        
                        // Store the selected ad ID in session for the next refresh
                        $_SESSION[$sessionKey] = $selectedAd['id'] ?? null;
                        
                        // Automatically record view for the served ad
                        recordAdView($selectedAd);
                        save_ads_data();
                        
                        echo json_encode([$selectedAd]);
                        exit();
                    } else {
                        echo json_encode([]);
                        exit();
                    }
                } else {
                    $filteredAds = array_filter($filteredAds, function($item) use ($category) {
                        if (!isset($item['category'])) return false;
                        $cleanItemCat = str_replace(' page', '', strtolower($item['category']));
                        $cleanCategory = str_replace(' page', '', strtolower($category));
                        return $cleanItemCat === $cleanCategory;
                    });
                }
            }
            // Automatically record view for served ads in list
            foreach ($filteredAds as &$fAd) {
                recordAdView($fAd);
            }
            unset($fAd);
            save_ads_data();

            echo json_encode(array_values($filteredAds));
            exit();
        }
    }

    if ($method === 'POST') {
        if ($isView && $adId) {
            foreach ($ads as &$ad) {
                if (strval($ad['id']) === strval($adId)) {
                    recordAdView($ad);
                    save_ads_data();
                    echo json_encode(["message" => "View tracked", "views" => $ad['views']]);
                    exit();
                }
            }
            http_response_code(404);
            echo json_encode(["message" => "Ad not found"]);
            exit();
        }

        if ($isClick && $adId) {
            foreach ($ads as &$ad) {
                if (strval($ad['id']) === strval($adId)) {
                    recordAdClick($ad);
                    save_ads_data();
                    echo json_encode(["message" => "Click tracked", "clicks" => $ad['clicks']]);
                    exit();
                }
            }
            http_response_code(404);
            echo json_encode(["message" => "Ad not found"]);
            exit();
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty(trim($input['companyName'] ?? '')) && empty(trim($input['description'] ?? '')) && empty(trim($input['adTitle'] ?? ''))) {
            http_response_code(400);
            echo json_encode(["message" => "Company Name and Ad Details are required."]);
            exit();
        }

        if (isset($input['description']) && mb_strlen(strval($input['description']), 'UTF-8') > 50) {
            http_response_code(400);
            echo json_encode(["message" => "Description must be 50 characters or less."]);
            exit();
        }

        $targetCategory = isset($input['category']) ? strval($input['category']) : "Homepage page";
        $targetMonth = isset($input['month']) ? strval($input['month']) : date('F Y');
        
        $approvedCount = 0;
        foreach ($ads as $existingAd) {
            if (isset($existingAd['status']) && strtolower($existingAd['status']) === 'approved') {
                $existingCat = $existingAd['category'] ?? '';
                $existingMonth = $existingAd['month'] ?? '';
                if (strtolower(trim($existingCat)) === strtolower(trim($targetCategory)) && strtolower(trim($existingMonth)) === strtolower(trim($targetMonth))) {
                    $approvedCount++;
                }
            }
        }
        
        if ($approvedCount >= 5) {
            http_response_code(400);
            echo json_encode(["message" => "Limit reached: Only 5 ads can be posted per month for each category."]);
            exit();
        }
        
        $maxId = 0;
        foreach ($ads as $item) {
            $cleanId = preg_replace('/[^0-9]/', '', $item['id'] ?? '');
            $idVal = intval($cleanId);
            if ($idVal > $maxId) {
                $maxId = $idVal;
            }
        }
        $input['id'] = strval($maxId + 1);

        $adItem = [
            "id" => $input['id'],
            "companyName" => isset($input['companyName']) ? strval($input['companyName']) : "",
            "postedBy" => isset($input['postedBy']) ? strval($input['postedBy']) : "",
            "contactNumber" => isset($input['contactNumber']) ? strval($input['contactNumber']) : "",
            "email" => isset($input['email']) ? strval($input['email']) : "",
            "image" => isset($input['image']) ? convertGoogleDriveLink(strval($input['image'])) : "",
            "description" => isset($input['description']) ? strval($input['description']) : "",
            "url" => isset($input['url']) ? strval($input['url']) : "",
            "ctaLabel" => isset($input['ctaLabel']) ? strval($input['ctaLabel']) : "visit site",
            "category" => isset($input['category']) ? strval($input['category']) : "Events",
            "month" => isset($input['month']) ? strval($input['month']) : date('F Y'),
            "totalViews" => 0,
            "budget" => (function() use ($data, $input) {
                $selectedMonth = isset($input['month']) ? trim(strval($input['month'])) : date('F Y');
                if (isset($data['monthly_budgets']) && is_array($data['monthly_budgets']) && isset($data['monthly_budgets'][$selectedMonth])) {
                    return floatval($data['monthly_budgets'][$selectedMonth]);
                }
                return isset($data['single_view_budget']) ? floatval($data['single_view_budget']) : 10000.0;
            })(),
            "views" => 0,
            "clicks" => 0,
            "history" => [],
            "locations" => [],
            "geo_locations" => [],
            "status" => "pending", // Default status is pending approval
            "date" => date('Y-m-d')
        ];

        // Check if posting user is blocked
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $sessionUserId = isset($_SESSION['userId']) ? strval($_SESSION['userId']) : null;
        $isAdminSession = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

        $isPostingUserBlocked = false;
        foreach ($users as $uCheck) {
            $uCheckEmail = strtolower(trim($uCheck['email'] ?? ''));
            $inputEmail = strtolower(trim($input['email'] ?? ''));
            if (($sessionUserId !== null && strval($uCheck['id']) === $sessionUserId) || (!empty($inputEmail) && $uCheckEmail === $inputEmail)) {
                if (!empty($uCheck['is_blocked']) || (isset($uCheck['status']) && strtolower($uCheck['status']) === 'blocked')) {
                    $isPostingUserBlocked = true;
                    break;
                }
            }
        }
        if ($isPostingUserBlocked) {
            http_response_code(403);
            echo json_encode(["message" => "Your account is blocked by administrator. You cannot post advertisements."]);
            exit();
        }
        
        $requiredCredits = floatval($adItem['budget'] ?? 10000.0);
        $userIndexForCredits = -1;
        if ($sessionUserId !== null) {
            foreach ($users as $uIdx => $uObj) {
                if (strval($uObj['id']) === $sessionUserId || (isset($uObj['email']) && !empty($input['email']) && strtolower($uObj['email']) === strtolower(trim($input['email'])))) {
                    $userIndexForCredits = $uIdx;
                    break;
                }
            }
        }
        
        if (!$isAdminSession && $userIndexForCredits !== -1) {
            $userCredits = floatval($users[$userIndexForCredits]['credits'] ?? 0);
            if ($userCredits < $requiredCredits) {
                http_response_code(400);
                echo json_encode([
                    "message" => "Insufficient ad credits to post this ad. Required: " . number_format($requiredCredits) . " Credits (₹" . number_format($requiredCredits) . "), Current Balance: " . number_format($userCredits) . " Credits. Please buy credits to post advertisements.",
                    "insufficient_credits" => true,
                    "required_credits" => $requiredCredits,
                    "current_credits" => $userCredits
                ]);
                exit();
            }
            
            // Deduct credits
            $users[$userIndexForCredits]['credits'] = max(0, $userCredits - $requiredCredits);
            if (!isset($users[$userIndexForCredits]['credit_history']) || !is_array($users[$userIndexForCredits]['credit_history'])) {
                $users[$userIndexForCredits]['credit_history'] = [];
            }
            array_unshift($users[$userIndexForCredits]['credit_history'], [
                "id" => "TXN_AD_" . time() . "_" . rand(100, 999),
                "rupees" => 0,
                "credits" => -$requiredCredits,
                "type" => "Ad Post Fee",
                "description" => "Ad Post: " . ($input['companyName'] ?? 'Ad Campaign'),
                "paymentMethod" => "Credits Balance",
                "date" => date('c'),
                "status" => "Deducted"
            ]);
        }

        // If user role is 'user', upgrade role to 'advertiser' upon posting an ad
        if ($userIndexForCredits !== -1 && isset($users[$userIndexForCredits])) {
            $currentRole = strtolower($users[$userIndexForCredits]['role'] ?? 'user');
            if ($currentRole === 'user') {
                $users[$userIndexForCredits]['role'] = 'advertiser';
                if (isset($_SESSION['userId']) && strval($_SESSION['userId']) === strval($users[$userIndexForCredits]['id'])) {
                    $_SESSION['user_role'] = 'advertiser';
                }
            }
        } else if ($sessionUserId !== null) {
            foreach ($users as &$uCheck) {
                if (strval($uCheck['id']) === $sessionUserId || (isset($uCheck['email']) && !empty($input['email']) && strtolower(trim($uCheck['email'])) === strtolower(trim($input['email'])))) {
                    $cRole = strtolower($uCheck['role'] ?? 'user');
                    if ($cRole === 'user') {
                        $uCheck['role'] = 'advertiser';
                        if (isset($_SESSION['userId']) && strval($_SESSION['userId']) === strval($uCheck['id'])) {
                            $_SESSION['user_role'] = 'advertiser';
                        }
                    }
                }
            }
            unset($uCheck);
        }

        $ads[] = $adItem;
        logActivity("EVENT_CREATED", "Ad submitted for company: " . $adItem['companyName'] . " (pending approval)");
        save_data();
        http_response_code(201);
        echo json_encode($adItem);
        exit();
    }

    if ($method === 'PATCH' || $method === 'PUT') {
        if ($adId) {
            $input = json_decode(file_get_contents('php://input'), true);
            foreach ($ads as &$ad) {
                if ($ad['id'] == $adId) {
                    $willBeApproved = false;
                    if (isset($input['status'])) {
                        $willBeApproved = (strtolower($input['status']) === 'approved');
                    } else {
                        $willBeApproved = (strtolower($ad['status'] ?? 'pending') === 'approved');
                    }

                    $isStatusChangingToApproved = isset($input['status']) && strtolower($input['status']) === 'approved' && strtolower($ad['status'] ?? 'pending') !== 'approved';
                    $isMonthOrCategoryChanging = (isset($input['month']) && strval($input['month']) !== ($ad['month'] ?? '')) || (isset($input['category']) && strval($input['category']) !== ($ad['category'] ?? ''));

                    if ($willBeApproved && ($isStatusChangingToApproved || $isMonthOrCategoryChanging)) {
                        $targetCategory = isset($input['category']) ? strval($input['category']) : ($ad['category'] ?? '');
                        $targetMonth = isset($input['month']) ? strval($input['month']) : ($ad['month'] ?? '');
                        
                        $approvedCount = 0;
                        foreach ($ads as $otherAd) {
                            // Exclude the current ad we are modifying from the count of other approved ads
                            if (isset($otherAd['id']) && strval($otherAd['id']) === strval($ad['id'])) {
                                continue;
                            }
                            if (isset($otherAd['status']) && strtolower($otherAd['status']) === 'approved') {
                                $otherCat = $otherAd['category'] ?? '';
                                $otherMonth = $otherAd['month'] ?? '';
                                if (strtolower(trim($otherCat)) === strtolower(trim($targetCategory)) && strtolower(trim($otherMonth)) === strtolower(trim($targetMonth))) {
                                    $approvedCount++;
                                }
                            }
                        }
                        
                        if ($approvedCount >= 5) {
                            http_response_code(400);
                            echo json_encode(["message" => "Limit reached: Only 5 ads can be approved/displayed per month for each category."]);
                            exit();
                        }
                    }

                    if (isset($input['companyName'])) {
                        $ad['companyName'] = strval($input['companyName']);
                    }
                    if (isset($input['postedBy'])) {
                        $ad['postedBy'] = strval($input['postedBy']);
                    }
                    if (isset($input['contactNumber'])) {
                        $ad['contactNumber'] = strval($input['contactNumber']);
                    }
                    if (isset($input['email'])) {
                        $ad['email'] = strval($input['email']);
                    }
                    if (isset($input['image']) && !empty($input['image'])) {
                        $ad['image'] = convertGoogleDriveLink(strval($input['image']));
                    }
                    if (isset($input['description'])) {
                        $ad['description'] = strval($input['description']);
                    }
                    if (isset($input['adTitle'])) {
                        $ad['adTitle'] = strval($input['adTitle']);
                    }
                    if (isset($input['detailedExplanation'])) {
                        $ad['detailedExplanation'] = strval($input['detailedExplanation']);
                    }
                    if (isset($input['url'])) {
                        $ad['url'] = strval($input['url']);
                    }
                    if (isset($input['ctaLabel'])) {
                        $ad['ctaLabel'] = strval($input['ctaLabel']);
                    }
                    if (isset($input['category'])) {
                        $ad['category'] = strval($input['category']);
                    }
                    if (isset($input['subCategory'])) {
                        $ad['subCategory'] = strval($input['subCategory']);
                    }
                    if (isset($input['status'])) {
                        $ad['status'] = strval($input['status']);
                        if ($ad['status'] === 'approved') {
                            logActivity("EVENT_CREATED", "Admin approved ad for company: " . ($ad['companyName'] ?? 'Untitled'));
                        } else if ($ad['status'] === 'pending') {
                            // Reset stats on reposting
                            $ad['views'] = 0;
                            $ad['clicks'] = 0;
                            $ad['history'] = [];
                            $ad['locations'] = [];
                            $ad['geo_locations'] = [];
                            $ad['rejectionReason'] = ''; // Clear rejection reason on resubmission
                        }
                    }
                    if (isset($input['views'])) {
                        $ad['views'] = intval($input['views']);
                    }
                    if (isset($input['clicks'])) {
                        $ad['clicks'] = intval($input['clicks']);
                    }
                    if (isset($input['totalViews'])) {
                        $ad['totalViews'] = intval($input['totalViews']);
                    }
                    if (isset($input['month'])) {
                        $ad['month'] = strval($input['month']);
                    }
                    if (isset($input['budget'])) {
                        $ad['budget'] = floatval($input['budget']);
                    }
                    if (isset($input['rejectionReason'])) {
                        $ad['rejectionReason'] = strval($input['rejectionReason']);
                    }
                    save_data();
                    echo json_encode($ad);
                    exit();
                }
            }
            http_response_code(404);
            echo json_encode(["message" => "Ad not found"]);
            exit();
        }
    }

    if ($method === 'DELETE') {
        // Check if there is a batch delete payload
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['ids']) && is_array($input['ids'])) {
            $idsToDelete = $input['ids'];
            $deletedCount = 0;
            $ads = array_filter($ads, function($item) use ($idsToDelete, &$activities, &$deletedCount) {
                if (in_array($item['id'], $idsToDelete)) {
                    $deletedComp = $item['companyName'] ?? 'Untitled';
                    logActivity("EVENT_DELETED", "Admin bulk deleted ad: " . $deletedComp);
                    $deletedCount++;
                    return false;
                }
                return true;
            });
            $ads = array_values($ads);
            save_data();
            echo json_encode(["message" => "$deletedCount ads deleted"]);
            exit();
        }

        if ($adId) {
            foreach ($ads as $key => $item) {
                if ($item['id'] == $adId) {
                    $deletedComp = $item['companyName'] ?? 'Untitled';
                    array_splice($ads, $key, 1);
                    logActivity("EVENT_DELETED", "Admin deleted ad: " . $deletedComp);
                    save_data();
                    echo json_encode(["message" => "Ad deleted"]);
                    exit();
                }
            }
            http_response_code(404);
            echo json_encode(["message" => "Ad not found"]);
            exit();
        } else {
            $ads = [];
            logActivity("EVENT_DELETED", "Admin deleted all ads");
            save_data();
            echo json_encode(["message" => "All ads deleted"]);
            exit();
        }
    }
}

// --- USER AUTHENTICATION & PROFILE ENDPOINTS ---
if (strpos($requestUri, '/api/auth/signup') !== false && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = isset($input['email']) ? trim($input['email']) : '';
    $mobile = isset($input['mobile']) ? trim($input['mobile']) : '';
    $password = isset($input['password']) ? $input['password'] : '';
    $name = isset($input['name']) ? trim($input['name']) : '';
    $username = isset($input['username']) ? trim($input['username']) : '';
    $bio = isset($input['bio']) ? trim($input['bio']) : '';

    if (empty($email) || empty($mobile) || empty($password)) {
        http_response_code(400);
        echo json_encode(["message" => "Email, mobile number, and password are required."]);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid email format."]);
        exit();
    }

    // Check if email already exists
    foreach ($users as $u) {
        if (isset($u['email']) && strtolower($u['email']) === strtolower($email)) {
            http_response_code(400);
            echo json_encode(["message" => "Email already registered."]);
            exit();
        }
    }

    // Check if username already exists
    if (!empty($username)) {
        foreach ($users as $u) {
            if (isset($u['username']) && strtolower($u['username']) === strtolower($username)) {
                http_response_code(400);
                echo json_encode(["message" => "Username already taken."]);
                exit();
            }
        }
    }

    // Generate unique ID
    $maxId = 0;
    foreach ($users as $u) {
        $idVal = intval($u['id'] ?? 0);
        if ($idVal > $maxId) {
            $maxId = $idVal;
        }
    }
    $newId = strval($maxId + 1);

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $newUser = [
        "id" => $newId,
        "email" => $email,
        "mobile" => $mobile,
        "password" => $hashedPassword,
        "name" => $name ? $name : ($username ? $username : explode('@', $email)[0]),
        "username" => $username ? $username : ($name ? $name : explode('@', $email)[0]),
        "bio" => $bio,
        "role" => "user",
        "created_at" => date('c')
    ];

    $users[] = $newUser;
    save_data();

    // Start session if not started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['userId'] = intval($newId);
    $_SESSION['username'] = $newUser['name'];
    $_SESSION['email'] = $newUser['email'];
    $_SESSION['user_role'] = 'user';

    echo json_encode([
        "message" => "Signup successful",
        "user" => [
            "id" => $newId,
            "email" => $email,
            "mobile" => $mobile,
            "name" => $newUser['name'],
            "bio" => $bio
        ]
    ]);
    exit();
}

if (strpos($requestUri, '/api/auth/login') !== false && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = isset($input['email']) ? trim($input['email']) : '';
    $password = isset($input['password']) ? $input['password'] : '';

    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(["message" => "Email and password are required."]);
        exit();
    }

    $foundUser = null;
    foreach ($users as $u) {
        if (isset($u['email']) && strtolower($u['email']) === strtolower($email)) {
            $foundUser = $u;
            break;
        }
    }

    if (!$foundUser || !password_verify($password, $foundUser['password'])) {
        http_response_code(401);
        echo json_encode(["message" => "Invalid email or password."]);
        exit();
    }

    if (!empty($foundUser['is_blocked']) || (isset($foundUser['status']) && strtolower($foundUser['status']) === 'blocked')) {
        http_response_code(403);
        echo json_encode(["message" => "Your account has been blocked by administrator. Please contact support."]);
        exit();
    }

    // Start session if not started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // Check if user has posted ads and upgrade role to advertiser if currently 'user'
    $userHasAdsOnLogin = false;
    $uEmailLogin = strtolower(trim($foundUser['email'] ?? ''));
    $uNameLogin = strtolower(trim($foundUser['name'] ?? ''));
    foreach ($ads as $adItem) {
        $adEmail = strtolower(trim($adItem['email'] ?? ''));
        $adPostedBy = strtolower(trim($adItem['postedBy'] ?? ''));
        if (($uEmailLogin !== '' && $adEmail === $uEmailLogin) || ($uNameLogin !== '' && $adPostedBy === $uNameLogin)) {
            $userHasAdsOnLogin = true;
            break;
        }
    }
    if ($userHasAdsOnLogin && strtolower($foundUser['role'] ?? 'user') === 'user') {
        $foundUser['role'] = 'advertiser';
        foreach ($users as &$uRef) {
            if (strval($uRef['id']) === strval($foundUser['id'])) {
                $uRef['role'] = 'advertiser';
                break;
            }
        }
        unset($uRef);
        save_data();
    }

    $_SESSION['userId'] = intval($foundUser['id']);
    $_SESSION['username'] = $foundUser['name'] ?? explode('@', $foundUser['email'])[0];
    $_SESSION['email'] = $foundUser['email'];
    $_SESSION['user_role'] = $foundUser['role'] ?? 'user';

    echo json_encode([
        "message" => "Login successful",
        "user" => [
            "id" => $foundUser['id'],
            "email" => $foundUser['email'],
            "mobile" => $foundUser['mobile'] ?? '',
            "name" => $foundUser['name'] ?? '',
            "bio" => $foundUser['bio'] ?? ''
        ]
    ]);
    exit();
}

if (strpos($requestUri, '/api/auth/logout') !== false && $method === 'POST') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_unset();
    session_destroy();
    echo json_encode(["message" => "Logged out successfully"]);
    exit();
}

if (strpos($requestUri, '/api/profile') !== false) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['userId'])) {
        http_response_code(401);
        echo json_encode(["message" => "Unauthorized. Please log in."]);
        exit();
    }

    $currentUserId = strval($_SESSION['userId']);
    
    if ($method === 'GET') {
        $foundUser = null;
        foreach ($users as $u) {
            if (strval($u['id']) === $currentUserId) {
                $foundUser = $u;
                break;
            }
        }

        if (!$foundUser) {
            http_response_code(404);
            echo json_encode(["message" => "User profile not found."]);
            exit();
        }

        echo json_encode([
            "id" => $foundUser['id'],
            "email" => $foundUser['email'],
            "mobile" => $foundUser['mobile'] ?? '',
            "name" => $foundUser['name'] ?? '',
            "bio" => $foundUser['bio'] ?? '',
            "role" => $foundUser['role'] ?? 'user',
            "credits" => floatval($foundUser['credits'] ?? 0),
            "credit_history" => $foundUser['credit_history'] ?? [],
            "created_at" => $foundUser['created_at'] ?? ''
        ]);
        exit();
    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $email = isset($input['email']) ? trim($input['email']) : '';
        $mobile = isset($input['mobile']) ? trim($input['mobile']) : '';
        $name = isset($input['name']) ? trim($input['name']) : '';
        $bio = isset($input['bio']) ? trim($input['bio']) : '';
        $newPassword = isset($input['newPassword']) ? $input['newPassword'] : '';

        if (empty($email) || empty($mobile)) {
            http_response_code(400);
            echo json_encode(["message" => "Email and mobile number are required."]);
            exit();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(["message" => "Invalid email format."]);
            exit();
        }

        // Find the user and check for email uniqueness
        $userIndex = -1;
        foreach ($users as $index => $u) {
            if (strval($u['id']) === $currentUserId) {
                $userIndex = $index;
            } else if (isset($u['email']) && strtolower($u['email']) === strtolower($email)) {
                http_response_code(400);
                echo json_encode(["message" => "Email is already in use by another user."]);
                exit();
            }
        }

        if ($userIndex === -1) {
            http_response_code(404);
            echo json_encode(["message" => "User profile not found."]);
            exit();
        }

        // Update user properties
        $users[$userIndex]['email'] = $email;
        $users[$userIndex]['mobile'] = $mobile;
        $users[$userIndex]['name'] = $name ? $name : explode('@', $email)[0];
        $users[$userIndex]['bio'] = $bio;

        if (!empty($newPassword)) {
            $users[$userIndex]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        // Update session username/email
        $_SESSION['username'] = $users[$userIndex]['name'];
        $_SESSION['email'] = $users[$userIndex]['email'];

        save_data();

        echo json_encode([
            "message" => "Profile updated successfully",
            "user" => [
                "id" => $users[$userIndex]['id'],
                "email" => $users[$userIndex]['email'],
                "mobile" => $users[$userIndex]['mobile'],
                "name" => $users[$userIndex]['name'],
                "bio" => $users[$userIndex]['bio'],
                "credits" => floatval($users[$userIndex]['credits'] ?? 0)
            ]
        ]);
        exit();
    }
}

// --- DELETE ACCOUNT ENDPOINT ---
if ((strpos($requestUri, '/api/profile/delete') !== false) && $method === 'DELETE') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['userId'])) {
        http_response_code(401);
        echo json_encode(["message" => "Unauthorized. Please log in."]);
        exit();
    }
    $currentUserId = strval($_SESSION['userId']);
    
    $deleted = false;
    foreach ($users as $index => $u) {
        if (strval($u['id']) === $currentUserId) {
            array_splice($users, $index, 1);
            $deleted = true;
            break;
        }
    }

    if ($deleted) {
        save_data();
        session_unset();
        session_destroy();
        echo json_encode(["message" => "Account deleted successfully."]);
        exit();
    } else {
        http_response_code(404);
        echo json_encode(["message" => "User account not found."]);
        exit();
    }
}

// --- BUY CREDITS ENDPOINT ---
if ((strpos($requestUri, '/api/profile/buy-credits') !== false || strpos($requestUri, '/api/credits/buy') !== false) && $method === 'POST') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['userId'])) {
        http_response_code(401);
        echo json_encode(["message" => "Unauthorized. Please log in to buy credits."]);
        exit();
    }
    $currentUserId = strval($_SESSION['userId']);
    $input = json_decode(file_get_contents('php://input'), true);
    $amount = floatval($input['amount'] ?? 0);
    $paymentMethod = isset($input['paymentMethod']) ? trim(strval($input['paymentMethod'])) : 'UPI';

    if ($amount <= 0) {
        http_response_code(400);
        echo json_encode(["message" => "Please enter a valid amount greater than ₹0."]);
        exit();
    }

    $userIndex = -1;
    foreach ($users as $index => $u) {
        if (strval($u['id']) === $currentUserId) {
            $userIndex = $index;
            break;
        }
    }

    if ($userIndex === -1) {
        http_response_code(404);
        echo json_encode(["message" => "User not found."]);
        exit();
    }

    // 1 Rupee = 1 Credit
    $creditsAdded = $amount;
    $currentCredits = floatval($users[$userIndex]['credits'] ?? 0);
    $newCredits = $currentCredits + $creditsAdded;
    $users[$userIndex]['credits'] = $newCredits;

    if (!isset($users[$userIndex]['credit_history']) || !is_array($users[$userIndex]['credit_history'])) {
        $users[$userIndex]['credit_history'] = [];
    }

    $txn = [
        "id" => "TXN_BUY_" . time() . "_" . rand(100, 999),
        "rupees" => $amount,
        "credits" => $creditsAdded,
        "type" => "Credit Purchase",
        "description" => "Purchased " . number_format($creditsAdded) . " Credits",
        "paymentMethod" => $paymentMethod,
        "date" => date('c'),
        "status" => "Completed"
    ];

    array_unshift($users[$userIndex]['credit_history'], $txn);
    logActivity("CREDITS_PURCHASED", "User " . ($users[$userIndex]['name'] ?? $currentUserId) . " bought " . $creditsAdded . " credits for ₹" . $amount);
    save_data();

    echo json_encode([
        "message" => "Successfully purchased " . number_format($creditsAdded) . " Credits!",
        "credits" => $newCredits,
        "transaction" => $txn,
        "user" => [
            "id" => $users[$userIndex]['id'],
            "name" => $users[$userIndex]['name'],
            "email" => $users[$userIndex]['email'],
            "credits" => $newCredits,
            "credit_history" => $users[$userIndex]['credit_history']
        ]
    ]);
    exit();
}

// --- ADMIN: GET USERS LIST ---
if (strpos($requestUri, '/api/admin/users') !== false && $method === 'GET') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $isAdmin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') || (isset($_SESSION['admin_unlocked']) && $_SESSION['admin_unlocked'] === true);
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(["message" => "Forbidden. Admin access required."]);
        exit();
    }
    $usersSummary = [];
    foreach ($users as $u) {
        $usersSummary[] = [
            "id" => $u['id'],
            "name" => $u['name'] ?? $u['username'] ?? explode('@', $u['email'])[0],
            "email" => $u['email'],
            "mobile" => $u['mobile'] ?? '',
            "credits" => floatval($u['credits'] ?? 0),
            "role" => $u['role'] ?? 'user'
        ];
    }
    echo json_encode($usersSummary);
    exit();
}

// --- ADMIN: ADD CREDITS FOR A SPECIFIC USER ---
if (strpos($requestUri, '/api/admin/add-credits') !== false && $method === 'POST') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $isAdmin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') || (isset($_SESSION['admin_unlocked']) && $_SESSION['admin_unlocked'] === true);
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(["message" => "Forbidden. Admin access required."]);
        exit();
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $userName = isset($input['name']) ? trim(strval($input['name'])) : '';
    $userEmail = isset($input['email']) ? strtolower(trim(strval($input['email']))) : '';
    $userMobile = isset($input['mobile']) ? trim(strval($input['mobile'])) : '';
    $creditsToAdd = floatval($input['credits'] ?? 0);

    if (empty($userEmail)) {
        http_response_code(400);
        echo json_encode(["message" => "User Mail ID is required."]);
        exit();
    }

    if ($creditsToAdd <= 0) {
        http_response_code(400);
        echo json_encode(["message" => "Please enter a valid credit amount greater than 0."]);
        exit();
    }

    $targetIndex = -1;
    foreach ($users as $index => $u) {
        $e = strtolower(trim($u['email'] ?? ''));
        $n = strtolower(trim($u['name'] ?? $u['username'] ?? ''));
        if (!empty($userEmail) && $e === $userEmail) {
            $targetIndex = $index;
            break;
        } else if (!empty($userName) && $n === strtolower(trim($userName))) {
            $targetIndex = $index;
            break;
        }
    }

    if ($targetIndex === -1) {
        // Create user entry if not found with guaranteed unique ID
        $maxId = 0;
        foreach ($users as $uCheck) {
            $idNum = intval($uCheck['id'] ?? 0);
            if ($idNum > $maxId) {
                $maxId = $idNum;
            }
        }
        $newId = strval($maxId + 1);
        $displayName = !empty($userName) ? $userName : explode('@', $userEmail)[0];
        $newUser = [
            "id" => $newId,
            "email" => $userEmail,
            "mobile" => $userMobile,
            "password" => password_hash("User@123", PASSWORD_DEFAULT),
            "name" => $displayName,
            "username" => $displayName,
            "bio" => "",
            "role" => "advertiser",
            "credits" => 0,
            "credit_history" => [],
            "created_at" => date('c')
        ];
        $users[] = $newUser;
        $targetIndex = count($users) - 1;
    }

    // Update user credits
    $currentCredits = floatval($users[$targetIndex]['credits'] ?? 0);
    $newCredits = $currentCredits + $creditsToAdd;
    $users[$targetIndex]['credits'] = $newCredits;

    if (!isset($users[$targetIndex]['role']) || strtolower($users[$targetIndex]['role']) === 'user') {
        $users[$targetIndex]['role'] = 'advertiser';
    }

    if (!empty($userName)) {
        $users[$targetIndex]['name'] = $userName;
    }

    if (!empty($userMobile)) {
        $users[$targetIndex]['mobile'] = $userMobile;
    }

    if (!isset($users[$targetIndex]['credit_history']) || !is_array($users[$targetIndex]['credit_history'])) {
        $users[$targetIndex]['credit_history'] = [];
    }

    $txn = [
        "id" => "TXN_ADMIN_" . time() . "_" . rand(100, 999),
        "rupees" => 0,
        "credits" => $creditsToAdd,
        "type" => "Admin Grant",
        "description" => "Granted " . number_format($creditsToAdd) . " Credits by Admin",
        "paymentMethod" => "Admin Grant",
        "date" => date('c'),
        "status" => "Completed"
    ];

    array_unshift($users[$targetIndex]['credit_history'], $txn);
    logActivity("ADMIN_CREDIT_ADD", "Admin added " . $creditsToAdd . " credits to user " . $users[$targetIndex]['email']);
    save_data();

    echo json_encode([
        "message" => "Successfully added " . number_format($creditsToAdd) . " credits for " . $users[$targetIndex]['name'] . " (" . $users[$targetIndex]['email'] . ")!",
        "user" => [
            "id" => $users[$targetIndex]['id'],
            "name" => $users[$targetIndex]['name'],
            "email" => $users[$targetIndex]['email'],
            "credits" => $newCredits,
            "role" => $users[$targetIndex]['role']
        ]
    ]);
    exit();
}

// --- ADMIN: GET ALL PROFILES WITH DETAILED DATA ---
if (strpos($requestUri, '/api/admin/profiles') !== false && $method === 'GET') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $isAdmin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') || (isset($_SESSION['admin_unlocked']) && $_SESSION['admin_unlocked'] === true);
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(["message" => "Forbidden. Admin access required."]);
        exit();
    }

    $profiles = [];
    foreach ($users as $u) {
        $uEmail = strtolower(trim($u['email'] ?? ''));
        $uName = strtolower(trim($u['name'] ?? ''));
        $uUsername = strtolower(trim($u['username'] ?? ''));

        // Match user's ads
        $userAds = [];
        if (isset($ads) && is_array($ads)) {
            foreach ($ads as $adItem) {
                $adEmail = strtolower(trim($adItem['email'] ?? ''));
                $adPostedBy = strtolower(trim($adItem['postedBy'] ?? ''));
                if (($uEmail !== '' && $adEmail === $uEmail) || ($uName !== '' && $adPostedBy === $uName) || ($uUsername !== '' && $adPostedBy === $uUsername)) {
                    $userAds[] = $adItem;
                }
            }
        }

        $isBlocked = !empty($u['is_blocked']) || (isset($u['status']) && strtolower($u['status']) === 'blocked');
        $profiles[] = [
            "id" => strval($u['id']),
            "name" => $u['name'] ?? $u['username'] ?? explode('@', $u['email'])[0],
            "username" => $u['username'] ?? '',
            "email" => $u['email'] ?? '',
            "mobile" => $u['mobile'] ?? '',
            "role" => $u['role'] ?? 'user',
            "credits" => floatval($u['credits'] ?? 0),
            "is_blocked" => $isBlocked,
            "status" => $isBlocked ? 'blocked' : 'active',
            "bio" => $u['bio'] ?? '',
            "created_at" => $u['created_at'] ?? '',
            "credit_history" => $u['credit_history'] ?? [],
            "ads" => $userAds
        ];
    }

    echo json_encode($profiles);
    exit();
}

// --- ADMIN: BLOCK / UNBLOCK USER PROFILE ---
if (strpos($requestUri, '/api/admin/toggle-block-user') !== false && $method === 'POST') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $isAdmin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') || (isset($_SESSION['admin_unlocked']) && $_SESSION['admin_unlocked'] === true);
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(["message" => "Forbidden. Admin access required."]);
        exit();
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $userId = isset($input['id']) ? strval($input['id']) : '';
    $userEmail = isset($input['email']) ? strtolower(trim(strval($input['email']))) : '';

    if (empty($userId) && empty($userEmail)) {
        http_response_code(400);
        echo json_encode(["message" => "User ID or Email is required."]);
        exit();
    }

    $targetIndex = -1;
    foreach ($users as $index => $u) {
        if (!empty($userId) && strval($u['id']) === $userId) {
            $targetIndex = $index;
            break;
        } else if (!empty($userEmail) && strtolower(trim($u['email'] ?? '')) === $userEmail) {
            $targetIndex = $index;
            break;
        }
    }

    if ($targetIndex === -1) {
        http_response_code(404);
        echo json_encode(["message" => "User profile not found."]);
        exit();
    }

    if (isset($input['is_blocked'])) {
        $isBlocked = (bool)$input['is_blocked'];
    } else if (isset($input['status'])) {
        $isBlocked = strtolower(trim($input['status'])) === 'blocked';
    } else {
        $currentlyBlocked = !empty($users[$targetIndex]['is_blocked']) || (isset($users[$targetIndex]['status']) && strtolower($users[$targetIndex]['status']) === 'blocked');
        $isBlocked = !$currentlyBlocked;
    }

    $users[$targetIndex]['is_blocked'] = $isBlocked;
    $users[$targetIndex]['status'] = $isBlocked ? 'blocked' : 'active';

    $emailStr = $users[$targetIndex]['email'] ?? '';
    $actionName = $isBlocked ? 'blocked' : 'unblocked';

    logActivity("ADMIN_USER_BLOCKED", "Admin " . $actionName . " user profile: " . $emailStr);
    save_data();

    echo json_encode([
        "message" => "User profile " . $actionName . " successfully.",
        "is_blocked" => $isBlocked,
        "status" => $isBlocked ? 'blocked' : 'active',
        "user" => [
            "id" => strval($users[$targetIndex]['id']),
            "name" => $users[$targetIndex]['name'] ?? '',
            "email" => $users[$targetIndex]['email'] ?? '',
            "is_blocked" => $isBlocked,
            "status" => $isBlocked ? 'blocked' : 'active'
        ]
    ]);
    exit();
}

// --- ADMIN: UPDATE USER PROFILE ---
if (strpos($requestUri, '/api/admin/update-profile') !== false && $method === 'POST') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $isAdmin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') || (isset($_SESSION['admin_unlocked']) && $_SESSION['admin_unlocked'] === true);
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(["message" => "Forbidden. Admin access required."]);
        exit();
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $userId = isset($input['id']) ? strval($input['id']) : '';
    $userEmail = isset($input['email']) ? strtolower(trim(strval($input['email']))) : '';
    $userName = isset($input['name']) ? trim(strval($input['name'])) : '';
    $userMobile = isset($input['mobile']) ? trim(strval($input['mobile'])) : '';
    $userRole = isset($input['role']) ? strtolower(trim(strval($input['role']))) : 'user';
    $userCredits = isset($input['credits']) ? floatval($input['credits']) : null;

    if (empty($userId) && empty($userEmail)) {
        http_response_code(400);
        echo json_encode(["message" => "User ID or Email is required."]);
        exit();
    }

    $targetIndex = -1;
    foreach ($users as $index => $u) {
        if (!empty($userId) && strval($u['id']) === $userId) {
            $targetIndex = $index;
            break;
        } else if (!empty($userEmail) && strtolower(trim($u['email'] ?? '')) === $userEmail) {
            $targetIndex = $index;
            break;
        }
    }

    if ($targetIndex === -1) {
        http_response_code(404);
        echo json_encode(["message" => "User profile not found."]);
        exit();
    }

    if (!empty($userName)) $users[$targetIndex]['name'] = $userName;
    if (!empty($userEmail)) $users[$targetIndex]['email'] = $userEmail;
    if (!empty($userMobile)) $users[$targetIndex]['mobile'] = $userMobile;
    if (in_array($userRole, ['user', 'advertiser', 'admin'])) $users[$targetIndex]['role'] = $userRole;
    if ($userCredits !== null && $userCredits >= 0) $users[$targetIndex]['credits'] = $userCredits;
    if (isset($input['is_blocked'])) {
        $users[$targetIndex]['is_blocked'] = (bool)$input['is_blocked'];
        $users[$targetIndex]['status'] = (bool)$input['is_blocked'] ? 'blocked' : 'active';
    } else if (isset($input['status']) && in_array(strtolower(trim($input['status'])), ['active', 'blocked'])) {
        $isB = strtolower(trim($input['status'])) === 'blocked';
        $users[$targetIndex]['is_blocked'] = $isB;
        $users[$targetIndex]['status'] = $isB ? 'blocked' : 'active';
    }

    logActivity("ADMIN_PROFILE_UPDATE", "Admin updated profile for user " . $users[$targetIndex]['email']);
    save_data();

    echo json_encode([
        "message" => "User profile updated successfully.",
        "user" => $users[$targetIndex]
    ]);
    exit();
}

// --- ADMIN: DELETE USER PROFILE ---
if (strpos($requestUri, '/api/admin/delete-user-profile') !== false && $method === 'POST') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $isAdmin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') || (isset($_SESSION['admin_unlocked']) && $_SESSION['admin_unlocked'] === true);
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(["message" => "Forbidden. Admin access required."]);
        exit();
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $userId = isset($input['id']) ? strval($input['id']) : '';

    if (empty($userId)) {
        http_response_code(400);
        echo json_encode(["message" => "User ID is required."]);
        exit();
    }

    $deleted = false;
    foreach ($users as $index => $u) {
        if (strval($u['id']) === $userId) {
            $delEmail = $u['email'] ?? '';
            array_splice($users, $index, 1);
            logActivity("ADMIN_USER_DELETED", "Admin deleted user profile: " . $delEmail);
            $deleted = true;
            break;
        }
    }

    if ($deleted) {
        save_data();
        echo json_encode(["message" => "User profile deleted successfully."]);
        exit();
    } else {
        http_response_code(404);
        echo json_encode(["message" => "User profile not found."]);
        exit();
    }
}
// AI EXPLAIN ENDPOINT
// POST /api/explain — Generates a detailed Gemini explanation
// =============================================
if (strpos($requestUri, '/api/explain') !== false && $method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    $title   = trim($body['title']   ?? '');
    $summary = trim($body['summary'] ?? '');
    $type    = trim($body['type']    ?? 'news'); // news | event | fact
    $itemId  = trim($body['id']      ?? '');     // optional: cache back to data.json

    if (empty($title) && empty($summary)) {
        http_response_code(400);
        echo json_encode(['error' => 'title or summary is required']);
        exit();
    }

    // If itemId provided, check if we already have a cached explanation
    if (!empty($itemId) && $type === 'news') {
        foreach ($data['news'] as $nw) {
            if (strval($nw['id']) === $itemId && !empty($nw['aiExplanation'])) {
                echo json_encode(['explanation' => $nw['aiExplanation'], 'cached' => true]);
                exit();
            }
        }
    }

    switch ($type) {
        case 'event':
            $typeLabel = 'event happening in Chennai';
            break;
        case 'fact':
            $typeLabel = 'interesting fact or update about Chennai';
            break;
        default:
            $typeLabel = 'news article from Chennai, Tamil Nadu';
            break;
    }

    $prompt = <<<PROMPT
You are a knowledgeable journalist writing for madras.city, a local news and events platform for Chennai / Madras, Tamil Nadu, India.

A reader just clicked on the following $typeLabel:

Title: $title
Summary: $summary

Write a detailed, engaging, and informative explanation of this story for the reader. Your explanation should:
- Be 3 to 5 well-structured paragraphs
- Provide relevant background context about the topic
- Explain why this matters to the people of Chennai
- Include any likely implications or what to expect next
- Use clear, accessible language — no jargon
- Do NOT repeat the title verbatim as the first sentence
- Do NOT use markdown headings, bullet points, or formatting — plain flowing paragraphs only
PROMPT;

    try {
        $explanation = $geminiService->generateText($prompt);

        // Cache back to data.json so next visit is instant
        if (!empty($itemId) && $type === 'news') {
            foreach ($data['news'] as &$nw) {
                if (strval($nw['id']) === $itemId) {
                    $nw['aiExplanation'] = $explanation;
                    break;
                }
            }
            unset($nw);
            save_data();
        }

        echo json_encode(['explanation' => $explanation, 'cached' => false]);
    } catch (Exception $e) {
        $fallbackExplanation = "Keeping a pulse on the rapidly evolving urban landscape of Chennai requires constant attention to municipal announcements, infrastructure progress, and community affairs. News updates from regional outlets bring critical local stories to light, serving as a vital link between civic administrative bodies and the residents of Madras.\n\n" . (!empty($summary) ? $summary : $title) . "\n\nFor the people of Chennai, remaining informed about these local stories directly affects daily routines, commute planning, and quality of life. Timely news updates allow residents to navigate around transit construction detours, prepare for monsoon preparedness schedules, and understand upcoming changes to public amenities. Active media reporting creates essential accountability for municipal authorities and local contractors, encouraging the timely completion of public works that support both residential neighborhoods and the city's vibrant economic sector.\n\nLooking ahead, residents can anticipate continued developments regarding metro rail corridors, coastal protection projects, and refined civic services as regional government bodies work toward planned expansion goals. As Chennai continues to grow both economically and demographically, staying tuned to reliable local reporting ensures that citizens remain well-equipped to navigate everyday changes and actively participate in the development of their city.";

        echo json_encode(['explanation' => $fallbackExplanation, 'cached' => false, 'fallback' => true]);
    }
    exit();
}

http_response_code(404);
echo json_encode(["message" => "Route not found"]);
