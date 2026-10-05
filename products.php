<?php
$pageTitle = "Medical & Surgical Products Catalogue | Apex Surgical";
$pageDescription = "Browse medical categories including Surgical Instruments, OT Equipment, ICU Monitors, Laparoscopy sets, Autoclaves, and Hospital Furniture.";
$hideFooter = true;
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/product-helpers.php';
require_once __DIR__ . '/includes/public-header.php';

// Dynamic Database Fetch
$allProducts = [];
$categories = [];

try {
    $pdo = getDbConnection();

    // Fetch all active products
    $stmt = $pdo->query("SELECT * FROM equipment_inventory WHERE status != 'retired' ORDER BY id DESC");
    $dbRows = $stmt->fetchAll();

    foreach ($dbRows as $row) {
        $notes = $row['notes'] ?? '';

        // Extract Material if formatted in notes, or fallback
        $material = 'Medical Grade German Stainless Steel / Certified Alloy';
        if (preg_match('/Material:\s*([^;\n]+)/i', $notes, $m)) {
            $material = trim($m[1]);
        }

        // Extract Brand if formatted in notes
        $brand = 'Apex Surgical';
        if (preg_match('/Brand:\s*([^;\n]+)/i', $notes, $m)) {
            $brand = trim($m[1]);
        }

        // Extract Specs if formatted in notes, or fallback
        $specs = [];
        if (preg_match('/Specs:\s*([^\n]+)/i', $notes, $m)) {
            $specs = array_map('trim', explode(',', $m[1]));
        } else {
            $specs = [
                'Status: ' . ucfirst(str_replace('_', ' ', $row['status'])),
                'Location: ' . ($row['current_location'] ?: 'Main Clinic Store'),
                'CE & ISO 13485 Certified',
                'Warranty: ' . ($row['warranty_expiry'] ? date('M Y', strtotime($row['warranty_expiry'])) : '2 Years Warranty'),
            ];
        }

        // Clean description
        $cleanDesc = trim(preg_replace('/(Material|Brand|Specs):[^\n]+/i', '', $notes));
        if ($cleanDesc === '') {
            $cleanDesc = 'Hospital-grade ' . strtolower($row['category']) . ' engineered for high clinical reliability, sterilization compatibility, and continuous operating performance.';
        }

        $sizeModel = $row['serial_number'] ?: ('Model: ' . $row['equipment_code']);
        $productImage = get_product_image_url($row['category'], $row['name'], $row['equipment_code'], $notes);

        $allProducts[] = [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'category' => $row['category'],
            'sku' => $row['equipment_code'],
            'material' => $material,
            'size_model' => $sizeModel,
            'desc' => $cleanDesc,
            'specs' => $specs,
            'brand' => $brand,
            'status' => $row['status'],
            'image' => $productImage,
        ];
    }

    // Fetch dynamic categories
    $catStmt = $pdo->query("SELECT DISTINCT category FROM equipment_inventory WHERE category != '' AND status != 'retired' ORDER BY category ASC");
    $categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    error_log("Products page DB error: " . $e->getMessage());
}

// Active category filter from GET
$activeCategory = $_GET['cat'] ?? 'all';
?>

<div class="figma-page-wrap">

    <!-- 1. EDITORIAL HERO SECTION -->
    <section class="figma-products-hero">
        <h1 class="figma-products-hero-title">Precision in every cut.</h1>
        <p class="figma-products-hero-subtitle">
            Explore our comprehensive medical catalogue engineered with German metallurgy, CE & ISO 13485 compliance, and full biomedical calibration.
        </p>
    </section>

    <!-- 2. SEARCH & CATEGORY FILTER CONTROLS -->
    <section class="figma-catalog-controls">
        <div class="figma-controls-box">
            
            <div class="figma-search-row">
                <!-- Search input -->
                <div class="figma-search-input-wrap">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" id="prodSearchInput" class="figma-search-input" placeholder="Search product name, SKU, alloy, or department (e.g. Scissors, Autoclave, 316L, ICU)...">
                </div>

                <!-- Stats summary -->
                <div class="figma-results-count">
                    Showing <strong id="visibleCount" style="color: var(--fg-olive);"><?= count($allProducts) ?></strong> equipment models
                </div>
            </div>

            <!-- Dynamic Category Pills from DB -->
            <div class="figma-pills-row" id="categoryFilterGroup">
                <button class="figma-pill-btn <?= ($activeCategory === 'all') ? 'active' : '' ?>" data-cat="all">All Categories</button>
                <?php foreach ($categories as $cat): ?>
                    <button class="figma-pill-btn <?= ($activeCategory === $cat) ? 'active' : '' ?>" data-cat="<?= htmlspecialchars($cat) ?>">
                        <?= htmlspecialchars($cat) ?>
                    </button>
                <?php endforeach; ?>
            </div>

        </div>
    </section>

    <!-- 3. PRODUCTS GRID -->
    <section class="figma-products-section">
        <div id="productsGrid" class="figma-products-grid">
            <?php foreach ($allProducts as $p): ?>
                <div class="figma-product-card" data-category="<?= htmlspecialchars($p['category']) ?>" data-search="<?= strtolower(htmlspecialchars($p['name'] . ' ' . $p['sku'] . ' ' . $p['material'] . ' ' . $p['desc'] . ' ' . $p['category'])) ?>">
                    
                    <!-- Product Image Container -->
                    <div class="figma-card-img-wrap">
                        <a href="/surgical_equipment/product-details.php?id=<?= $p['id'] ?>" style="display:block; width:100%; height:100%;">
                            <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
                        </a>
                        <div class="figma-card-floating-badge">
                            <span>ISO 13485 / CE</span>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="figma-card-body">
                        <div>
                            <!-- Category & SKU Tag -->
                            <div class="figma-card-top-meta">
                                <span class="figma-category-badge"><?= htmlspecialchars($p['category']) ?></span>
                                <span class="figma-sku-badge"><?= htmlspecialchars($p['sku']) ?></span>
                            </div>

                            <!-- Product Name -->
                            <h3 class="figma-product-name">
                                <a href="/surgical_equipment/product-details.php?id=<?= $p['id'] ?>" style="color: inherit; text-decoration: none;">
                                    <?= htmlspecialchars($p['name']) ?>
                                </a>
                            </h3>

                            <!-- Model / Size -->
                            <div class="figma-product-model">
                                Model/Size: <?= htmlspecialchars($p['size_model']) ?>
                            </div>

                            <!-- Description -->
                            <p class="figma-product-desc">
                                <?= htmlspecialchars($p['desc']) ?>
                            </p>

                            <!-- Material Pill -->
                            <div class="figma-product-material">
                                <strong>Material:</strong> <?= htmlspecialchars($p['material']) ?>
                            </div>

                            <!-- Technical Specs Bullets -->
                            <div style="margin-bottom: 16px;">
                                <span style="font-size: 11.5px; font-weight: 700; text-transform: uppercase; color: var(--fg-muted); letter-spacing: 0.05em; display: block; margin-bottom: 8px;">Key Highlights:</span>
                                <ul class="figma-product-specs-list">
                                    <?php foreach ($p['specs'] as $spec): ?>
                                        <li>
                                            <span class="figma-spec-check">✓</span> <?= htmlspecialchars($spec) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="figma-card-actions">
                            <a href="/surgical_equipment/product-details.php?id=<?= $p['id'] ?>" class="figma-card-btn-specs" title="View Technical Specifications">
                                <span>Specs</span>
                            </a>
                            <a href="/surgical_equipment/contact.php?product=<?= urlencode($p['name']) ?>&sku=<?= urlencode($p['sku']) ?>#contact-form" class="figma-card-btn-quote">
                                <span>Get Quote ↗</span>
                            </a>
                            <a href="https://wa.me/919106595469?text=<?= urlencode('Hello Heer Jani (Apex Surgical), I want a price quote & technical datasheet for ' . $p['name'] . ' (' . $p['sku'] . ')') ?>" target="_blank" rel="noopener noreferrer" class="figma-card-btn-wa" title="Inquire on WhatsApp">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2zm5.78 14.15c-.24.67-1.39 1.29-1.93 1.34-.51.05-1.17.07-3.79-.98-3.15-1.26-5.18-4.45-5.34-4.66-.16-.21-1.28-1.7-1.28-3.25 0-1.55.81-2.31 1.1-2.62.29-.31.64-.39.85-.39.21 0 .43 0 .61.01.2.01.46-.07.72.55.27.64.91 2.22.99 2.38.08.16.13.35.03.56-.11.21-.16.35-.32.53-.16.19-.34.42-.49.56-.16.16-.33.34-.14.66.19.32.84 1.38 1.8 2.24 1.24 1.1 2.28 1.45 2.6 1.61.32.16.51.13.7-.08.19-.21.8-.93 1.01-1.25.21-.32.43-.27.72-.16.29.11 1.86.88 2.18 1.04.32.16.53.24.61.37.08.13.08.77-.16 1.44z"/></svg>
                            </a>
                        </div>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

        <!-- No Results Fallback -->
        <div id="noResultsMsg" class="figma-no-results" style="display: none;">
            <div style="font-size: 40px; margin-bottom: 12px;">🔍</div>
            <h3 style="font-family: var(--fg-serif); font-size: 28px; color: var(--fg-dark); margin-bottom: 8px;">No matching equipment found</h3>
            <p style="color: var(--fg-muted); font-size: 15px; margin-bottom: 22px;">Try searching for a different keyword or view all product categories.</p>
            <button class="figma-pill-btn active" onclick="resetFilters()">Reset All Filters</button>
        </div>
    </section>

</div>

<!-- Instant Search & Category Filter Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('prodSearchInput');
    const pills = document.querySelectorAll('.figma-pill-btn');
    const cards = document.querySelectorAll('.figma-product-card');
    const visibleCount = document.getElementById('visibleCount');
    const noResults = document.getElementById('noResultsMsg');

    let currentCategory = '<?= htmlspecialchars($activeCategory) ?>';

    function filterProducts() {
        const query = searchInput.value.toLowerCase().trim();
        let matched = 0;

        cards.forEach(card => {
            const cardCat = card.getAttribute('data-category');
            const searchData = card.getAttribute('data-search');

            const matchCat = (currentCategory === 'all' || cardCat === currentCategory);
            const matchSearch = (query === '' || searchData.includes(query));

            if (matchCat && matchSearch) {
                card.style.display = 'flex';
                matched++;
            } else {
                card.style.display = 'none';
            }
        });

        if (visibleCount) visibleCount.textContent = matched;
        if (noResults) noResults.style.display = (matched === 0) ? 'block' : 'none';
    }

    // Category click handler
    pills.forEach(pill => {
        pill.addEventListener('click', function() {
            pills.forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            currentCategory = this.getAttribute('data-cat');
            filterProducts();
        });
    });

    // Search input handler
    if (searchInput) {
        searchInput.addEventListener('input', filterProducts);
    }

    // Initial run
    filterProducts();

    window.resetFilters = function() {
        if (searchInput) searchInput.value = '';
        currentCategory = 'all';
        pills.forEach(p => p.classList.remove('active'));
        if (pills[0]) pills[0].classList.add('active');
        filterProducts();
    };
});
</script>

<?php require_once __DIR__ . '/includes/public-footer.php'; ?>
