<?php
/**
 * Product Helper Functions for Apex Surgical Equipment
 * Maps equipment directly from the /assets/images/photos/ folder based on exact product name.
 */

if (!function_exists('get_product_image_url')) {
    function get_product_image_url($category = '', $name = '', $sku = '', $notes = '') {
        $photosDir = __DIR__ . '/../assets/images/photos';
        $productsDir = __DIR__ . '/../assets/images/products';

        // 1. Explicit image path defined in notes
        if (!empty($notes) && preg_match('/Image:\s*([^\n;]+)/i', $notes, $m)) {
            $imgPath = trim($m[1]);
            if (file_exists(__DIR__ . '/../' . ltrim($imgPath, '/')) || file_exists($imgPath)) {
                return $imgPath;
            }
        }

        // 2. Primary: Exact Product Name matching in /assets/images/photos/
        if (!empty($name) && is_dir($photosDir)) {
            // Direct file extension check
            foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
                $candidate = $name . '.' . $ext;
                if (file_exists($photosDir . '/' . $candidate)) {
                    return '/surgical_equipment/assets/images/photos/' . rawurlencode($candidate);
                }
            }

            // Stripped quotes (e.g. Metzenbaum Dissecting Scissors 7" -> 7.jpg)
            $cleanName = str_replace(['"', "'"], '', $name);
            foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
                $candidate = $cleanName . '.' . $ext;
                if (file_exists($photosDir . '/' . $candidate)) {
                    return '/surgical_equipment/assets/images/photos/' . rawurlencode($candidate);
                }
            }

            // Normalized alphanumeric scan against all files in /photos/
            static $photosList = null;
            if ($photosList === null) {
                $photosList = scandir($photosDir);
            }

            $needle = strtolower(preg_replace('/[^a-z0-9]/i', '', $name));
            foreach ($photosList as $f) {
                if ($f === '.' || $f === '..') continue;
                $fileBase = pathinfo($f, PATHINFO_FILENAME);
                $fileClean = strtolower(preg_replace('/[^a-z0-9]/i', '', $fileBase));
                if ($needle === $fileClean) {
                    return '/surgical_equipment/assets/images/photos/' . rawurlencode($f);
                }
            }
        }

        // 3. Secondary: SKU-based matching in /assets/images/products/
        if (!empty($sku)) {
            $cleanSku = strtolower(preg_replace('/[^a-z0-9_-]/i', '', $sku));
            foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
                if (file_exists($productsDir . '/' . $cleanSku . '.' . $ext)) {
                    return '/surgical_equipment/assets/images/products/' . $cleanSku . '.' . $ext;
                }
            }
        }

        // 4. Fallback Default
        return '/surgical_equipment/assets/images/products/default-product.jpg';
    }
}
