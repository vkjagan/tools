# High-RPM AdSense Integration Plan (CMS: temple/article/gallery)

This plan assumes your existing system is:
- `ads.php` (slot config map)
- `ads_renderer.php` (`render_ad($config['ads'][...])`)
- `ads_header.php` (global AdSense script loaded once)

---

## 1) Recommended page layout + exact ad zones

### Canonical content page order
1. `header.php`
2. Hero/title block
3. Intro paragraphs + featured image
4. Main content body
5. Related/internal links
6. Footer

### High-performing ad zones
- **ATF banner (top content)**: immediately below title/meta block (high viewability, still clean).
- **In-content #1**: after paragraph 1 or 2.
- **In-content #2**: middle of article (around 45–60% scroll depth).
- **Desktop sticky sidebar**: persistent but not intrusive.
- **Footer multiplex**: monetizes exit traffic.

---

## 2) Slot strategy (mobile vs desktop)

### Mobile (max 2–3 units)
- 1 × top in-content (after title block)
- 1 × mid in-content (after paragraph 3–4)
- 1 × footer/multiplex (optional if content is long)

### Desktop (max 3–4 units)
- 1 × top content unit
- 1 × in-content after paragraph 2
- 1 × in-content mid body
- 1 × sticky sidebar
- 1 × footer multiplex (only if page length supports it)

---

## 3) Production-ready template snippets

## `header.php`
```php
<?php
require_once __DIR__ . '/ads_header.php'; // loads script once
require_once __DIR__ . '/ads_renderer.php';
require_once __DIR__ . '/ads.php';

$isMobile = preg_match('/Mobile|Android|iPhone|iPad/i', $_SERVER['HTTP_USER_AGENT'] ?? '') === 1;
?>

<header class="site-header">
  <!-- logo/nav -->
</header>
```

## Article / temple page template (exact placements)
```php
<?php
/** @var array $config */
/** @var string $title */
/** @var string $htmlContent */ // content already sanitized server-side
?>

<main class="page-wrap">
  <article class="content-main">
    <h1><?= htmlspecialchars($title) ?></h1>

    <?php // ATF: below title, very high viewability ?>
    <div class="ad ad--top-content">
      <?= $isMobile
          ? render_ad($config['ads']['mobile_top_content'])
          : render_ad($config['ads']['desktop_top_content']) ?>
    </div>

    <?php
    // In-content injection helper (after paragraph 2 + mid-content)
    function inject_incontent_ads(string $html, array $adsToInsert): string {
        $parts = preg_split('/(<\/p>)/i', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (!$parts || count($parts) < 4) return $html; // too short, skip

        $paragraphCount = 0;
        $out = '';
        $midTarget = max(4, (int) floor((substr_count(strtolower($html), '</p>')) * 0.55));

        for ($i = 0; $i < count($parts); $i += 2) {
            $chunk = $parts[$i] ?? '';
            $close = $parts[$i + 1] ?? '';
            $out .= $chunk . $close;

            if (stripos($close, '</p>') !== false) {
                $paragraphCount++;

                if ($paragraphCount === 2 && !empty($adsToInsert['after_p2'])) {
                    $out .= '<div class="ad ad--incontent ad--after-p2">' . $adsToInsert['after_p2'] . '</div>';
                }

                if ($paragraphCount === $midTarget && !empty($adsToInsert['mid_content'])) {
                    $out .= '<div class="ad ad--incontent ad--mid">' . $adsToInsert['mid_content'] . '</div>';
                }
            }
        }

        return $out;
    }

    $inContentAds = [
      'after_p2' => $isMobile
        ? render_ad($config['ads']['mobile_incontent_1'])
        : render_ad($config['ads']['desktop_incontent_1']),
      'mid_content' => $isMobile
        ? render_ad($config['ads']['mobile_incontent_2'])
        : render_ad($config['ads']['desktop_incontent_2']),
    ];

    echo inject_incontent_ads($htmlContent, $inContentAds);
    ?>
  </article>

  <?php include __DIR__ . '/sidebar.php'; ?>
</main>
```

## `sidebar.php` (desktop sticky only)
```php
<aside class="content-sidebar">
  <?php if (!$isMobile): ?>
    <div class="ad ad--sidebar-sticky">
      <?= render_ad($config['ads']['desktop_sidebar_sticky']) ?>
    </div>
  <?php endif; ?>

  <!-- related posts / temple links -->
</aside>
```

## `footer.php`
```php
<footer class="site-footer">
  <!-- footer links -->

  <div class="ad ad--footer-multiplex">
    <?= $isMobile
        ? render_ad($config['ads']['mobile_footer_multiplex'])
        : render_ad($config['ads']['desktop_footer_multiplex']) ?>
  </div>
</footer>
```

---

## 4) Minimal CSS for UX-safe ad spacing + sticky behavior

```css
.ad { margin: 20px 0; }
.ad--top-content { margin-top: 12px; margin-bottom: 24px; }
.ad--incontent { margin: 28px 0; }
.ad--sidebar-sticky { position: sticky; top: 88px; }
.ad--footer-multiplex { margin-top: 24px; }

@media (max-width: 991px) {
  .ad { margin: 16px 0; }
  .ad--sidebar-sticky { position: static; }
}
```

---

## 5) Sticky ad strategy (safe + practical)
- **Desktop**: sticky sidebar ad only (best viewability, low annoyance).
- **Mobile**: avoid aggressive sticky overlays by default. If tested, use one small bottom anchor with clear close affordance and strict CLS control.

---

## 6) A/B tests (highest ROI first)
1. **ATF location**: below title vs below featured image.
2. **In-content trigger**: after paragraph 1 vs 2.
3. **Mid-content depth**: 45% vs 60% paragraph depth.
4. **Footer ad type**: multiplex vs display.
5. **Desktop sidebar width**: 300 vs 336 variants.

Track: RPM, viewability, CTR, average engagement time, bounce rate.

---

## 7) Policy + UX guardrails
- Never stack ads tightly around headings/images.
- Keep strong visual separation (`ad` container spacing + neutral background).
- Do not exceed ad density on short pages.
- Skip mid-content ads when paragraph count is low.
- Prioritize readability first; revenue follows sustained session depth.
