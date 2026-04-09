<?php
/**
 * DPpic Ad Configuration
 * Replaces AdSense slot config with internal slot mapping
 */

return [
    'ads' => [
        'enabled' => true,

        // ── Mobile Slots ─────────────────────────────
        'mobile' => [
            'header' => 'DP_SLOT_ID_M1',
            'in_content' => 'DP_SLOT_ID_M2',
            'mid_article' => 'DP_SLOT_ID_M3',
            'sticky_footer' => 'DP_SLOT_ID_M4',
        ],

        // ── Desktop Slots ────────────────────────────
        'desktop' => [
            'top' => 'DP_SLOT_ID_D1',
            'below_title' => 'DP_SLOT_ID_D2',
            'sidebar' => 'DP_SLOT_ID_D3',
            'in_content' => 'DP_SLOT_ID_D4',
            'sticky_sidebar' => 'DP_SLOT_ID_D5',
        ],
    ],
];
