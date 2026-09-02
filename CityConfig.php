<?php
// CityConfig.php — Central Configuration for Madras.city

class CityConfig {
    public const DOMAIN = 'madras.city';
    public const CANONICAL_BASE = 'https://www.madras.city';
    public const NAME = 'Madras';
    public const DISPLAY_NAME = 'Madras.city';
    public const CITY = 'Chennai';
    public const STATE = 'Tamil Nadu';
    public const COUNTRY = 'India';
    public const SLUG = 'chennai';

    public const SEO_TITLE_SUFFIX = 'Madras.city — Chennai Events, News & Local Guide';
    public const SEO_DEFAULT_DESCRIPTION = 'Discover upcoming events, city news, local business ads, and spot guides across Chennai with Madras.city.';

    public const NEWS_SOURCES = [
        [
            'url' => 'https://www.dinamalar.com/news/tamil-nadu-district-news-chennai',
            'source' => 'Dinamalar'
        ],
        [
            'url' => 'https://timesofindia.indiatimes.com/city/chennai',
            'source' => 'Times of India'
        ],
        [
            'url' => 'https://www.thehindu.com/news/cities/chennai/',
            'source' => 'The Hindu'
        ]
    ];

    public const VALIDATION_KEYWORDS = [
        'chennai', 'madras', 'tamil nadu', 'mylapore', 't. nagar', 't nagar', 'marina',
        'ecr', 'omr', 'velachery', 'guindy', 'anna salai', 'adyar', 'besant nagar',
        'nungambakkam', 'porur', 'tambaram', 'chromepet', 'tidel park', 'egmore',
        'royapettah', 'triplicane', 'thiruvanmiyur', 'kodambakkam', 'ashok nagar',
        'kk nagar', 'ambattur', 'avadi', 'sholinganallur', 'siruseri'
    ];

    public const REJECT_KEYWORDS = [
        'coimbatore', 'kovai', 'peelamedu', 'saravanampatti', 'gandhipuram', 'singanallur',
        'pollachi', 'tiruppur', 'erode', 'codissia', 'siruthuli', 'noyyal'
    ];
}
