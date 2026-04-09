<?php
/**
 * Album static generator ad configuration.
 * Fill your AdSense client + slot IDs and set enabled=true.
 */
return [
    'enabled' => true,
    'adsense_client' => 'ca-pub-9611661876400656',
    'slots' => [
        // Grid pages (category/home indexes)
        // Desktop: D1 (728x90 leaderboard), D4 (auto responsive)
        // Mobile: M1/M2 responsive blocks
        'grid_top_desktop' => '3303906095',     // DP_SLOT_ID_D1
        'grid_top_mobile' => '3575999728',      // DP_SLOT_ID_M1
        'grid_bottom_desktop' => '2867319649',  // DP_SLOT_ID_D4
        'grid_bottom_mobile' => '6203929422',   // DP_SLOT_ID_M2

        // Album pages
        // Desktop: D2 (336x280), D3 (300x600), D5 (300x250)
        // Mobile: M2/M3/M4
        'album_top_desktop' => '8119646324',    // DP_SLOT_ID_D2
        'album_top_mobile' => '6203929422',     // DP_SLOT_ID_M2
        'album_mid_desktop' => '5493482988',    // DP_SLOT_ID_D3
        'album_mid_mobile' => '8364661087',     // DP_SLOT_ID_M3
        'album_footer_desktop' => '5301911296', // DP_SLOT_ID_D5
        'album_footer_mobile' => '4425416070',  // DP_SLOT_ID_M4
    ],
];
