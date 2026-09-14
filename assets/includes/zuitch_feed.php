<?php
/**
 * HTML del área principal del feed vertical /zuitch (vídeo, imagen, embeds, texto).
 */
if (!function_exists('Zuitch_Feed_BuildMediaHtml')) {
    function Zuitch_Feed_BuildMediaHtml($story)
    {
        global $wo;
        $prev = array_key_exists('story', $wo) ? $wo['story'] : null;
        $wo['story'] = $story;
        $html = '';
        try {
            if (!empty($story['postFile']) && ifVideoPost($story['postFile'])) {
                $media = array(
                    'type' => 'post',
                    'storyId' => $story['id'],
                    'filename' => $story['postFile'],
                    'name' => $story['postFileName'],
                    'postFileThumb' => $story['postFileThumb'],
                );
                $html = Wo_DisplaySharedFile($media, '', $story['cache']);
            } elseif (!empty($story['postYoutube'])) {
                $yid = htmlspecialchars($story['postYoutube'], ENT_QUOTES, 'UTF-8');
                $html = '<div class="zuitch-feed-embed zuitch-feed-youtube"><iframe src="https://www.youtube.com/embed/' . $yid . '?playsinline=1&rel=0" allowfullscreen allow="autoplay; encrypted-media; picture-in-picture" frameborder="0"></iframe></div>';
            } elseif (!empty($story['postVimeo'])) {
                $vid = htmlspecialchars($story['postVimeo'], ENT_QUOTES, 'UTF-8');
                $html = '<div class="zuitch-feed-embed zuitch-feed-vimeo"><iframe src="https://player.vimeo.com/video/' . $vid . '" allowfullscreen frameborder="0"></iframe></div>';
            } elseif (!empty($story['postDailymotion'])) {
                $vid = htmlspecialchars($story['postDailymotion'], ENT_QUOTES, 'UTF-8');
                $html = '<div class="zuitch-feed-embed zuitch-feed-daily"><iframe src="https://www.dailymotion.com/embed/video/' . $vid . '" allowfullscreen frameborder="0"></iframe></div>';
            } elseif (!empty($story['postFacebook'])) {
                $fb = htmlspecialchars($story['postFacebook'], ENT_QUOTES, 'UTF-8');
                $href = 'https://www.facebook.com/' . $fb;
                $html = '<div class="zuitch-feed-embed zuitch-feed-fb"><iframe src="https://www.facebook.com/plugins/video.php?href=' . rawurlencode($href) . '&show_text=0" allowfullscreen frameborder="0"></iframe></div>';
            } elseif (!empty($story['postPlaytube'])) {
                $pid = htmlspecialchars($story['postPlaytube'], ENT_QUOTES, 'UTF-8');
                $url = $wo['config']['site_url'] . '/watch/' . $pid;
                $html = '<div class="zuitch-feed-link zuitch-feed-playtube-card"><a class="btn btn-mat main" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">PlayTube</a></div>';
            } elseif (!empty($story['postSoundCloud'])) {
                $u = htmlspecialchars($story['postSoundCloud'], ENT_QUOTES, 'UTF-8');
                $html = '<div class="zuitch-feed-embed zuitch-feed-soundcloud"><iframe src="https://w.soundcloud.com/player/?url=' . rawurlencode($u) . '&amp;auto_play=false" frameborder="0"></iframe></div>';
            } elseif (!empty($story['multi_image']) && (int) $story['multi_image'] === 1 && !empty($story['photo_multi'][0]['image'])) {
                $src = htmlspecialchars($story['photo_multi'][0]['image'], ENT_QUOTES, 'UTF-8');
                $html = '<div class="zuitch-feed-image"><img src="' . $src . '" alt="" class="zuitch-feed-cover-img" onclick="Wo_OpenLightBox(' . (int) $story['id'] . ');"></div>';
            } elseif (!empty($story['photo_album'][0]['image'])) {
                $src = htmlspecialchars($story['photo_album'][0]['image'], ENT_QUOTES, 'UTF-8');
                $html = '<div class="zuitch-feed-image"><img src="' . $src . '" alt="" class="zuitch-feed-cover-img" onclick="Wo_OpenLightBox(' . (int) $story['id'] . ');"></div>';
            } elseif (!empty($story['postFile'])) {
                $media = array(
                    'type' => 'post',
                    'storyId' => $story['id'],
                    'filename' => $story['postFile'],
                    'name' => $story['postFileName'],
                    'postFileThumb' => $story['postFileThumb'],
                );
                $html = Wo_DisplaySharedFile($media, '', $story['cache']);
            } elseif (!empty($story['postLink']) && !empty($story['postLinkImage'])) {
                $img = htmlspecialchars($story['postLinkImage'], ENT_QUOTES, 'UTF-8');
                $link = htmlspecialchars($story['postLink'], ENT_QUOTES, 'UTF-8');
                $html = '<div class="zuitch-feed-link"><a href="' . $link . '" target="_blank" rel="noopener noreferrer"><img src="' . $img . '" alt="" class="zuitch-feed-cover-img"></a></div>';
            } elseif (!empty($story['postText'])) {
                $txt = nl2br(htmlspecialchars($story['postText'], ENT_QUOTES, 'UTF-8'));
                $html = '<div class="zuitch-feed-text-only"><div class="zuitch-feed-text-inner">' . $txt . '</div></div>';
            } else {
                $html = '<div class="zuitch-feed-fallback"><p>' . htmlspecialchars($wo['lang']['no_posts'] ?? '…', ENT_QUOTES, 'UTF-8') . '</p></div>';
            }
        } finally {
            if ($prev !== null) {
                $wo['story'] = $prev;
            } else {
                unset($wo['story']);
            }
        }
        return $html;
    }
}

if (!function_exists('Zuitch_Feed_FilterPosts')) {
    function Zuitch_Feed_FilterPosts($posts)
    {
        if (empty($posts) || !is_array($posts)) {
            return array();
        }
        $out = array();
        foreach ($posts as $p) {
            if (!is_array($p) || empty($p['id'])) {
                continue;
            }
            if (!empty($p['postType']) && $p['postType'] === 'ad') {
                continue;
            }
            $out[] = $p;
        }
        return $out;
    }
}
