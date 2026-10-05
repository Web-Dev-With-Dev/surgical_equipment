<?php
require_once __DIR__ . '/config/db.php';

$productId = (int)($_GET['id'] ?? 0);
$skuQuery  = trim($_GET['sku'] ?? '');

$product = null;

try {
    $pdo = getDbConnection();

    if ($productId > 0) {
        $stmt = $pdo->prepare("SELECT * FROM equipment_inventory WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $productId]);
        $row = $stmt->fetch();
    } elseif ($skuQuery !== '') {
        $stmt = $pdo->prepare("SELECT * FROM equipment_inventory WHERE equipment_code = :sku LIMIT 1");
        $stmt->execute([':sku' => $skuQuery]);
        $row = $stmt->fetch();
    } else {
        $stmt = $pdo->query("SELECT * FROM equipment_inventory WHERE status != 'retired' ORDER BY id DESC LIMIT 1");
        $row = $stmt->fetch();
    }

    if ($row) {
        $notes = $row['notes'] ?? '';

        // Material
        $material = 'Medical Grade German Stainless Steel (AISI 420 / 316L)';
        if (preg_match('/Material:\s*([^;\n]+)/i', $notes, $m)) {
            $material = trim($m[1]);
        }

        // Brand
        $brand = 'Apex Surgical / OEM Certified';
        if (preg_match('/Brand:\s*([^;\n]+)/i', $notes, $m)) {
            $brand = trim($m[1]);
        }

        // Features list
        $features = [];
        if (preg_match('/Specs:\s*([^\n]+)/i', $notes, $m)) {
            $features = array_map('trim', explode(',', $m[1]));
        } else {
            $features = [
                'Engineered to stringent ISO 13485 & CE medical device directives.',
                'Full biocompatibility and autoclave resistance tested at 134°C.',
                'Electrolytically passivated surface finish reduces surgical light glare.',
                'Biomedical quality checked with certificate of conformance.',
            ];
        }

        // Technical specs key-value
        $warrantyText = $row['warranty_expiry'] 
            ? date('F Y', strtotime($row['warranty_expiry'])) . ' (' . ($row['warranty_expiry'] >= date('Y-m-d') ? 'Active' : 'Expired') . ')'
            : '2 Years Manufacturer Replacement Guarantee';

        $specs = [
            'Equipment SKU / Code'  => $row['equipment_code'],
            'Clinical Department'   => $row['category'],
            'Operating Status'      => ucfirst(str_replace('_', ' ', $row['status'])),
            'Current Location'      => $row['current_location'] ?: 'Central Medical Store',
            'Serial / Model'        => $row['serial_number'] ?: 'Standard Hospital Series',
            'Sterilization Cycle'   => 'Steam Autoclave (134°C), ETO Gas & Plasma Safe',
            'Regulatory Standard'   => 'CE Mark, CDSCO Class B/C, ISO 13485 Certified',
            'Warranty Coverage'     => $warrantyText,
        ];

        // Clean description
        $cleanDesc = trim(preg_replace('/(Material|Brand|Specs):[^\n]+/i', '', $notes));
        if ($cleanDesc === '') {
            $cleanDesc = "High-precision certified " . htmlspecialchars($row['name']) . " engineered for hospital operating theatres, clinical departments, and intensive care units with full surgical traceability and biomedical compliance.";
        }

        $sizeModel = $row['serial_number'] ?: ('Model ' . $row['equipment_code']);

        $product = [
            'id'         => (int) $row['id'],
            'name'       => $row['name'],
            'category'   => $row['category'],
            'sku'        => $row['equipment_code'],
            'material'   => $material,
            'size_model' => $sizeModel,
            'brand'      => $brand,
            'warranty'   => $warrantyText,
            'desc'       => $cleanDesc,
            'features'   => $features,
            'specs'      => $specs,
            'status'     => $row['status'],
        ];

        // Related products in same category
        $relStmt = $pdo->prepare("
            SELECT id, name, category, equipment_code, notes 
            FROM equipment_inventory 
            WHERE category = :cat AND id != :id AND status != 'retired' 
            LIMIT 3
        ");
        $relStmt->execute([':cat' => $row['category'], ':id' => $row['id']]);
        $relatedProducts = $relStmt->fetchAll();

    } else {
        header('Location: /surgical_equipment/products.php');
        exit;
    }

} catch (PDOException $e) {
    error_log("Product details error: " . $e->getMessage());
    header('Location: /surgical_equipment/products.php');
    exit;
}

$pageTitle = $product['name'] . " | Apex Surgical Products";
$pageDescription = "Technical specifications, material metallurgy, and quotation inquiry for " . $product['name'] . " from Apex Surgical.";
$hideFooter = true;
require_once __DIR__ . '/includes/public-header.php';
?>

<div class="figma-page-wrap">

    <!-- PAGE BANNER / HEADER -->
    <section class="figma-products-hero" style="padding-bottom: 24px;">
        <div style="display: flex; gap: 8px; align-items: center; justify-content: center; margin-bottom: 16px; font-size: 13.5px; color: var(--fg-muted);">
            <a href="/surgical_equipment/index.php" style="color: var(--fg-muted); text-decoration: none;">Home</a>
            <span>/</span>
            <a href="/surgical_equipment/products.php" style="color: var(--fg-muted); text-decoration: none;">Products</a>
            <span>/</span>
            <span style="color: var(--fg-olive); font-weight: 600;"><?= htmlspecialchars($product['category']) ?></span>
        </div>

        <div style="display: flex; gap: 10px; align-items: center; justify-content: center; margin-bottom: 16px;">
            <span class="figma-category-badge"><?= htmlspecialchars($product['category']) ?></span>
            <span class="figma-sku-badge">SKU: <?= htmlspecialchars($product['sku']) ?></span>
        </div>

        <h1 class="figma-products-hero-title" style="font-size: clamp(38px, 5.5vw, 68px);">
            <?= htmlspecialchars($product['name']) ?>
        </h1>
    </section>

    <!-- MAIN PRODUCT DETAIL -->
    <section class="figma-products-section" style="padding-top: 10px;">
        <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 28px; align-items: start; margin-bottom: 50px;" class="prod-detail-grid">
            
            <!-- LEFT: PRODUCT HIGHLIGHTS & OVERVIEW -->
            <div style="background: #ffffff; border: 1px solid var(--fg-border); border-radius: 0; padding: 32px; box-shadow: none;">
                
                <h2 style="font-family: var(--fg-serif); font-size: 28px; margin-bottom: 14px; color: #0d110d;">Product Overview</h2>
                <p style="color: var(--fg-muted); font-size: 15px; line-height: 1.7; margin-bottom: 24px;">
                    <?= htmlspecialchars($product['desc']) ?>
                </p>

                <!-- Key Value Badges -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 28px;">
                    <div style="background: #F7F9F5; border: 1px solid var(--fg-border-light); padding: 14px; border-radius: 0;">
                        <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--fg-muted); display: block; margin-bottom: 4px;">Material Metallurgy</span>
                        <strong style="font-size: 13.5px; color: #0d110d;"><?= htmlspecialchars($product['material']) ?></strong>
                    </div>
                    <div style="background: #F7F9F5; border: 1px solid var(--fg-border-light); padding: 14px; border-radius: 0;">
                        <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--fg-muted); display: block; margin-bottom: 4px;">Model / Dimension</span>
                        <strong style="font-size: 13.5px; color: #0d110d;"><?= htmlspecialchars($product['size_model']) ?></strong>
                    </div>
                    <div style="background: #F7F9F5; border: 1px solid var(--fg-border-light); padding: 14px; border-radius: 0;">
                        <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--fg-muted); display: block; margin-bottom: 4px;">Brand / OEM Origin</span>
                        <strong style="font-size: 13.5px; color: #0d110d;"><?= htmlspecialchars($product['brand']) ?></strong>
                    </div>
                    <div style="background: #F7F9F5; border: 1px solid var(--fg-border-light); padding: 14px; border-radius: 0;">
                        <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--fg-muted); display: block; margin-bottom: 4px;">Warranty Guarantee</span>
                        <strong style="font-size: 13.5px; color: var(--fg-olive);"><?= htmlspecialchars($product['warranty']) ?></strong>
                    </div>
                </div>

                <!-- Engineering Features -->
                <h3 style="font-family: var(--fg-serif); font-size: 24px; margin-bottom: 14px; color: #0d110d;">Key Performance Features</h3>
                <ul class="figma-product-specs-list" style="margin-bottom: 28px; gap: 10px;">
                    <?php foreach ($product['features'] as $feat): ?>
                        <li style="font-size: 14px; line-height: 1.5; color: #384236;">
                            <span class="figma-spec-check">✓</span>
                            <span><?= htmlspecialchars($feat) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <!-- Technical Specification Table -->
                <h3 style="font-family: var(--fg-serif); font-size: 24px; margin-bottom: 14px; color: #0d110d;">Technical Specifications</h3>
                <div style="border: 1px solid var(--fg-border); border-radius: 0; overflow: hidden;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13.5px;">
                        <tbody>
                            <?php foreach ($product['specs'] as $label => $val): ?>
                                <tr style="border-bottom: 1px solid var(--fg-border-light);">
                                    <td style="padding: 12px 16px; background: #FAF8F5; font-weight: 600; color: #0d110d; width: 40%;"><?= htmlspecialchars($label) ?></td>
                                    <td style="padding: 12px 16px; background: #ffffff; color: var(--fg-muted);"><?= htmlspecialchars($val) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>

            <!-- RIGHT: QUOTATION & DISPATCH CARD -->
            <div style="display: flex; flex-direction: column; gap: 24px; position: sticky; top: 20px;">
                
                <div style="background: #ffffff; border: 1px solid var(--fg-border); border-radius: 0; padding: 32px; box-shadow: none;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                        <span style="font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--fg-olive); background: var(--fg-lime); padding: 4px 10px;">Direct Factory Procurement</span>
                        <span style="font-size: 12px; color: var(--fg-muted);">In Stock / Dispatched in 24h</span>
                    </div>

                    <h3 style="font-family: var(--fg-serif); font-size: 28px; color: #0d110d; margin-bottom: 10px;">Institutional Price Request</h3>
                    <p style="font-size: 14px; color: var(--fg-muted); line-height: 1.6; margin-bottom: 24px;">
                        Submit an instant RFP inquiry with your required quantity, hospital credentials, and delivery requirements. We provide official GST invoices and OEM technical calibration dossiers.
                    </p>

                    <!-- CTAs -->
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <a href="/surgical_equipment/contact.php?product=<?= urlencode($product['name']) ?>&sku=<?= urlencode($product['sku']) ?>#contact-form" class="figma-btn-primary" style="text-align: center; text-decoration: none; padding: 14px 20px; font-size: 14px; display: block;">
                            Request Official Quote &amp; Tender Specs ↗
                        </a>

                        <a href="https://wa.me/919106595469?text=<?= urlencode('Hello Heer Jani (Apex Surgical), I would like to get pricing & tender specifications for ' . $product['name'] . ' (SKU: ' . $product['sku'] . ')') ?>" target="_blank" rel="noopener noreferrer" style="display: flex; align-items: center; justify-content: center; gap: 8px; background: #25D366; color: #ffffff; text-decoration: none; padding: 14px 20px; font-weight: 700; font-size: 14px; letter-spacing: 0.02em;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2zm5.78 14.15c-.24.67-1.39 1.29-1.93 1.34-.51.05-1.17.07-3.79-.98-3.15-1.26-5.18-4.45-5.34-4.66-.16-.21-1.28-1.7-1.28-3.25 0-1.55.81-2.31 1.1-2.62.29-.31.64-.39.85-.39.21 0 .43 0 .61.01.2.01.46-.07.72.55.27.64.91 2.22.99 2.38.08.16.13.35.03.56-.11.21-.16.35-.32.53-.16.19-.34.42-.49.56-.16.16-.33.34-.14.66.19.32.84 1.38 1.8 2.24 1.24 1.1 2.28 1.45 2.6 1.61.32.16.51.13.7-.08.19-.21.8-.93 1.01-1.25.21-.32.43-.27.72-.16.29.11 1.86.88 2.18 1.04.32.16.53.24.61.37.08.13.08.77-.16 1.44z"/></svg>
                            <span>WhatsApp Procurement Desk (+91 91065 95469)</span>
                        </a>

                        <a href="/surgical_equipment/products.php" class="figma-btn-ghost" style="text-align: center; text-decoration: none; padding: 12px 20px; font-size: 13.5px; border: 1px solid var(--fg-border); color: #0d110d; display: block;">
                            ← Back to Full Catalogue
                        </a>
                    </div>

                    <!-- Trust indicators -->
                    <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--fg-border-light); display: flex; flex-direction: column; gap: 8px; font-size: 12.5px; color: var(--fg-muted);">
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <span style="color: var(--fg-olive); font-weight: bold;">✔</span> ISO 13485 &amp; CE 0123 Certified Quality
                        </div>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <span style="color: var(--fg-olive); font-weight: bold;">✔</span> Biomaterial Traceability &amp; Batch Reports
                        </div>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <span style="color: var(--fg-olive); font-weight: bold;">✔</span> Nationwide Express Hospital Delivery
                        </div>
                    </div>
                </div>

            </div>

        </div>

        <!-- RELATED PRODUCTS IN CATEGORY -->
        <?php if (!empty($relatedProducts)): ?>
            <div style="margin-top: 40px; padding-top: 30px; border-top: 1px solid var(--fg-border);">
                <h3 style="font-family: var(--fg-serif); font-size: 28px; color: #0d110d; margin-bottom: 20px;">Related Equipment in <?= htmlspecialchars($product['category']) ?></h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                    <?php foreach ($relatedProducts as $rel): ?>
                        <div style="background: #ffffff; border: 1px solid var(--fg-border); padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <span class="figma-sku-badge" style="margin-bottom: 8px; display: inline-block;"><?= htmlspecialchars($rel['equipment_code']) ?></span>
                                <h4 style="font-family: var(--fg-serif); font-size: 20px; color: #0d110d; margin-bottom: 8px;"><?= htmlspecialchars($rel['name']) ?></h4>
                            </div>
                            <div style="margin-top: 16px; display: flex; gap: 8px;">
                                <a href="/surgical_equipment/product-details.php?id=<?= $rel['id'] ?>" class="figma-card-btn-specs" style="flex: 1; text-align: center; text-decoration: none;">View Specs</a>
                                <a href="/surgical_equipment/contact.php?product=<?= urlencode($rel['name']) ?>&sku=<?= urlencode($rel['equipment_code']) ?>#contact-form" class="figma-card-btn-quote" style="flex: 1; text-align: center; text-decoration: none;">Quote ↗</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </section>

</div>

<style>
@media (max-width: 900px) {
    .prod-detail-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/public-footer.php'; ?>
