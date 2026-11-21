<?php
/**
 * Zuitch contact CTA helpers (publisher fields + product WhatsApp).
 */

if (!function_exists('zuitch_normalize_whatsapp')) {
    /**
     * @param string $raw
     * @return string digits only, or empty if invalid
     */
    function zuitch_normalize_whatsapp($raw)
    {
        $wa = preg_replace('/\D+/', '', (string) $raw);
        if (strlen($wa) >= 8 && strlen($wa) <= 15) {
            return $wa;
        }
        return '';
    }
}

if (!function_exists('zuitch_parse_post_cta_from_request')) {
    /**
     * Parse optional contact CTA fields from $_POST (post_cta_*).
     * Returns keys ready for posts table / Wo_RegisterPost / Wo_UpdatePostData.
     *
     * @param array|null $src defaults to $_POST
     * @return array
     */
    function zuitch_parse_post_cta_from_request($src = null)
    {
        if ($src === null) {
            $src = $_POST;
        }
        $out = array(
            'post_whatsapp' => '',
            'post_email' => '',
            'post_phone' => '',
            'show_cta_whatsapp' => 0,
            'show_cta_email' => 0,
            'show_cta_call' => 0,
        );
        if (!empty($src['post_cta_whatsapp'])) {
            $wa = zuitch_normalize_whatsapp($src['post_cta_whatsapp']);
            if ($wa !== '') {
                $out['post_whatsapp'] = function_exists('Wo_Secure') ? Wo_Secure($wa) : $wa;
                $out['show_cta_whatsapp'] = 1;
            }
        }
        if (!empty($src['post_cta_email']) && filter_var(trim($src['post_cta_email']), FILTER_VALIDATE_EMAIL)) {
            $em = trim($src['post_cta_email']);
            $out['post_email'] = function_exists('Wo_Secure') ? Wo_Secure($em) : $em;
            $out['show_cta_email'] = 1;
        }
        if (!empty($src['post_cta_phone'])) {
            $tel = preg_replace('/[^\d+]/', '', $src['post_cta_phone']);
            $tel_digits = preg_replace('/\D+/', '', $tel);
            if (strlen($tel_digits) >= 7 && strlen($tel_digits) <= 15) {
                $out['post_phone'] = function_exists('Wo_Secure') ? Wo_Secure($tel) : $tel;
                $out['show_cta_call'] = 1;
            }
        }
        return $out;
    }
}

if (!function_exists('zuitch_product_whatsapp')) {
    /**
     * Resolve WhatsApp digits for a product: post_whatsapp, then page whatsapp.
     *
     * @param array $product Wo_GetProduct / list product row (needs post_id or id)
     * @param array|null $story optional story with post_whatsapp / page publisher
     * @return string digits or ''
     */
    function zuitch_product_whatsapp($product, $story = null)
    {
        global $sqlConnect, $db;

        if (is_array($story)) {
            if (!empty($story['post_whatsapp'])) {
                $wa = zuitch_normalize_whatsapp($story['post_whatsapp']);
                if ($wa !== '') {
                    return $wa;
                }
            }
            if (!empty($story['page_id']) && (int) $story['page_id'] > 0) {
                $page = array();
                if (!empty($story['publisher']) && !empty($story['publisher']['page_id'])) {
                    $page = $story['publisher'];
                } elseif (function_exists('Wo_PageData')) {
                    $page = Wo_PageData($story['page_id']);
                }
                if (!empty($page['whatsapp'])) {
                    $wa = zuitch_normalize_whatsapp($page['whatsapp']);
                    if ($wa !== '') {
                        return $wa;
                    }
                }
            }
        }

        if (!is_array($product) || empty($product)) {
            return '';
        }

        $post_id = 0;
        if (!empty($product['post_id']) && is_numeric($product['post_id'])) {
            $post_id = (int) $product['post_id'];
        } elseif (!empty($product['id']) && function_exists('Wo_GetPostIDFromProdcutID')) {
            $post_id = (int) Wo_GetPostIDFromProdcutID($product['id']);
        }

        if ($post_id > 0 && !empty($sqlConnect)) {
            $post_id_s = Wo_Secure($post_id);
            $q = mysqli_query($sqlConnect, "SELECT `post_whatsapp`, `page_id` FROM " . T_POSTS . " WHERE `id` = '{$post_id_s}' LIMIT 1");
            if ($q && mysqli_num_rows($q)) {
                $row = mysqli_fetch_assoc($q);
                if (!empty($row['post_whatsapp'])) {
                    $wa = zuitch_normalize_whatsapp($row['post_whatsapp']);
                    if ($wa !== '') {
                        return $wa;
                    }
                }
                if (!empty($row['page_id']) && (int) $row['page_id'] > 0 && function_exists('Wo_PageData')) {
                    $page = Wo_PageData($row['page_id']);
                    if (!empty($page['whatsapp'])) {
                        $wa = zuitch_normalize_whatsapp($page['whatsapp']);
                        if ($wa !== '') {
                            return $wa;
                        }
                    }
                }
            }
        }

        return '';
    }
}

if (!function_exists('zuitch_whatsapp_icon_svg')) {
    function zuitch_whatsapp_icon_svg($size = 24)
    {
        $s = (int) $size;
        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91C2.13 13.66 2.59 15.36 3.45 16.86L2.05 22L7.3 20.62C8.75 21.41 10.38 21.83 12.04 21.83C17.5 21.83 21.95 17.38 21.95 11.92C21.95 9.27 20.92 6.78 19.05 4.91C17.18 3.03 14.69 2 12.04 2M12.05 20.15C10.59 20.15 9.17 19.76 7.89 19L7.57 18.82L4.43 19.65L5.28 16.61L5.08 16.27C4.25 14.94 3.81 13.42 3.81 11.91C3.81 7.37 7.5 3.68 12.05 3.68C14.25 3.68 16.31 4.53 17.87 6.09C19.42 7.65 20.28 9.71 20.28 11.92C20.28 16.46 16.58 20.15 12.05 20.15M16.56 14.4C16.31 14.28 15.09 13.68 14.87 13.6C14.64 13.52 14.5 13.48 14.31 13.73C14.15 13.98 13.67 14.56 13.53 14.71C13.39 14.87 13.24 14.89 13 14.76C12.75 14.63 11.96 14.38 11.03 13.55C10.31 12.91 9.83 12.12 9.68 11.87C9.54 11.62 9.67 11.49 9.8 11.35C9.91 11.24 10.05 11.05 10.17 10.91C10.3 10.77 10.34 10.67 10.42 10.51C10.5 10.35 10.46 10.21 10.4 10.09C10.34 9.96 9.86 8.78 9.66 8.28C9.46 7.8 9.26 7.87 9.11 7.86C8.97 7.85 8.82 7.85 8.68 7.85C8.53 7.85 8.3 7.94 8.1 8.19C7.9 8.44 7.33 8.98 7.33 10.07C7.33 11.16 8.12 12.21 8.24 12.37C8.37 12.53 9.81 14.76 12.01 15.7C12.55 15.94 12.96 16.07 13.28 16.17C13.8 16.33 14.28 16.31 14.66 16.25C15.08 16.19 16.07 15.66 16.28 15.08C16.48 14.5 16.48 14.01 16.41 13.9C16.35 13.79 16.2 13.74 15.95 13.61"/></svg>';
    }
}
