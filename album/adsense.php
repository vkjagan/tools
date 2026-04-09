<?php
/**
 * Thirukoil CMS — AdSense Configuration
 * All ad slots for mobile and desktop placements.
 * Set enabled = false to hide all ads globally.
 */
return [
    'adsense' => [
        'enabled'       => true,
        'publisher_id'  => 'ca-pub-9611661876400656',

        // ── Mobile Slots (2–3 ads per page) ─────────────────────────────────
        'mobile' => [
            'header' => [
                'slot'   => '9691979808',
                'size'   => '320x100',
                'format' => 'auto',
                'label'  => 'Header — Above the fold',
            ],
            'in_content' => [
                'slot'   => '7981749215',
                'size'   => 'responsive',
                'format' => 'fluid',
                'label'  => 'After 1st paragraph',
            ],
            'mid_article' => [
                'slot'   => '4600704142',
                'size'   => '300x250',
                'format' => 'auto',
                'label'  => 'Mid article — Strong RPM',
            ],
            'sticky_footer' => [
                'slot'   => '1918700484',
                'size'   => '320x50',
                'format' => 'auto',
                'label'  => 'Sticky footer — Continuous revenue',
                'sticky' => true,
            ],
            'multiplex' => [
                'slot'   => '2480538347',
                'format' => 'autorelaxed',
                'label'  => 'Grid Ads — Bottom of page',
            ],
            'in_article' => [
                'slot'   => '5331331888',
                'format' => 'fluid',
                'layout' => 'in-article',
                'label'  => 'Gallery/Article Native',
            ],
            'in_feed' => [
                'slot'   => '8662803311',
                'format' => 'fluid',
                'layout_key' => '-ef+6k-30-ac+ty',
                'label'  => 'Temple List Native',
            ],
        ],

        // ── Desktop Slots (3–4 ads per page) ────────────────────────────────
        'desktop' => [
            'top' => [
                'slot'   => '5722214129',
                'size'   => '728x90',
                'format' => 'auto',
                'label'  => 'Top banner — Classic high CTR',
            ],
            'below_title' => [
                'slot'   => '1782969112',
                'size'   => '336x280',
                'format' => 'auto',
                'label'  => 'Below title — Best performing',
            ],
            'sidebar' => [
                'slot'   => '1068653244',
                'size'   => '300x600',
                'format' => 'auto',
                'label'  => 'Sidebar — High RPM',
                'sticky' => true,
            ],
            'in_content' => [
                'slot'   => '2904479090',
                'size'   => 'responsive',
                'format' => 'fluid',
                'label'  => 'In-content — Must have',
            ],
            'sticky_sidebar' => [
                'slot'   => '2672110031',
                'size'   => '300x250',
                'format' => 'auto',
                'label'  => 'Sticky sidebar — Continuous visibility',
                'sticky' => true,
            ],
            'multiplex' => [
                'slot'   => '2480538347',
                'format' => 'autorelaxed',
                'label'  => 'Grid Ads — Bottom of page',
            ],
            'in_article' => [
                'slot'   => '5331331888',
                'format' => 'fluid',
                'layout' => 'in-article',
                'label'  => 'Gallery/Article Native',
            ],
            'in_feed' => [
                'slot'   => '8662803311',
                'format' => 'fluid',
                'layout_key' => '-ef+6k-30-ac+ty',
                'label'  => 'Temple List Native',
            ],
        ],

        // ── Global Ad Settings ────────────────────────────────────────────────
        'settings' => [
            'responsive'         => true,       // data-full-width-responsive="true"
            'format'             => 'auto',      // data-ad-format="auto"
            'lazy_load'          => true,        // data-adsbygoogle-status
            'auto_ads'           => false,       // Let CMS control placements
            'mobile_ads_per_page'   => 3,
            'desktop_ads_per_page'  => 4,
        ],
    ],
];
