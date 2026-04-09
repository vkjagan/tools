<?php
/**
 * DPpic Ad Renderer
 */

function render_ad($slot)
{
    if (!$slot) return;

    $ads = [

        // ───────── Desktop Ads ─────────
        'DP_SLOT_ID_D1' => '<ins class="adsbygoogle" style="display:inline-block;width:728px;height:90px" data-ad-client="ca-pub-9611661876400656" data-ad-slot="3303906095"></ins>',

        'DP_SLOT_ID_D2' => '<ins class="adsbygoogle" style="display:inline-block;width:336px;height:280px" data-ad-client="ca-pub-9611661876400656" data-ad-slot="8119646324"></ins>',

        'DP_SLOT_ID_D3' => '<ins class="adsbygoogle" style="display:inline-block;width:300px;height:600px" data-ad-client="ca-pub-9611661876400656" data-ad-slot="5493482988"></ins>',

        'DP_SLOT_ID_D4' => '<ins class="adsbygoogle" style="display:block" data-ad-client="ca-pub-9611661876400656" data-ad-slot="2867319649" data-ad-format="auto" data-full-width-responsive="true"></ins>',

        'DP_SLOT_ID_D5' => '<ins class="adsbygoogle" style="display:inline-block;width:300px;height:250px" data-ad-client="ca-pub-9611661876400656" data-ad-slot="5301911296"></ins>',


        // ───────── Mobile Ads ─────────
        'DP_SLOT_ID_M1' => '<ins class="adsbygoogle" style="display:block" data-ad-client="ca-pub-9611661876400656" data-ad-slot="3575999728" data-ad-format="auto" data-full-width-responsive="true"></ins>',

        'DP_SLOT_ID_M2' => '<ins class="adsbygoogle" style="display:block" data-ad-client="ca-pub-9611661876400656" data-ad-slot="6203929422" data-ad-format="auto" data-full-width-responsive="true"></ins>',

        'DP_SLOT_ID_M3' => '<ins class="adsbygoogle" style="display:inline-block;width:300px;height:250px" data-ad-client="ca-pub-9611661876400656" data-ad-slot="8364661087"></ins>',

        'DP_SLOT_ID_M4' => '<ins class="adsbygoogle" style="display:inline-block;width:320px;height:50px" data-ad-client="ca-pub-9611661876400656" data-ad-slot="4425416070"></ins>',
    ];

    if (!isset($ads[$slot])) return;

    echo $ads[$slot];
    echo '<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>';
}