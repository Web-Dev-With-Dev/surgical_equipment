<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/product-helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require customer / registered login
if (empty($_SESSION['user_id'])) {
    header('Location: /surgical_equipment/auth/login.php');
    exit;
}

$pageTitle = "Surgical Equipment Shop & Procurement";

$search       = trim($_GET['search'] ?? '');
$superFilter  = (int) ($_GET['super_id'] ?? 0);
$catFilter    = (int) ($_GET['category_id'] ?? 0);
$subCatFilter = (int) ($_GET['sub_id'] ?? 0);
$sortBy       = trim($_GET['sort'] ?? 'featured');

try {
    $pdo = getDbConnection();

    // Fetch Super Categories for filtering
    $superStmt = $pdo->query("SELECT id, name FROM super_categories WHERE status = 'active' ORDER BY sort_order ASC, name ASC");
    $superCategories = $superStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Categories for filtering
    $catStmt = $pdo->query("SELECT id, super_category_id, name FROM categories WHERE status = 'active' ORDER BY sort_order ASC, name ASC");
    $allCategories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Sub Categories for filtering
    $subStmt = $pdo->query("SELECT id, category_id, name FROM sub_categories WHERE status = 'active' ORDER BY sort_order ASC, name ASC");
    $allSubCategories = $subStmt->fetchAll(PDO::FETCH_ASSOC);

    // Build query for products
    $whereParts = ["e.status != 'retired'"];
    $params = [];

    if ($search !== '') {
        $whereParts[] = "(e.name LIKE :s OR e.equipment_code LIKE :s OR e.category LIKE :s OR e.notes LIKE :s)";
        $params[':s'] = "%$search%";
    }

    if ($superFilter > 0) {
        $whereParts[] = "e.super_category_id = :sup";
        $params[':sup'] = $superFilter;
    }

    if ($catFilter > 0) {
        $whereParts[] = "e.category_id = :cat";
        $params[':cat'] = $catFilter;
    }

    if ($subCatFilter > 0) {
        $whereParts[] = "e.sub_category_id = :sub";
        $params[':sub'] = $subCatFilter;
    }

    $whereClause = 'WHERE ' . implode(' AND ', $whereParts);

    $orderBy = "e.id ASC";
    if ($sortBy === 'price_asc') {
        $orderBy = "e.unit_price ASC";
    } elseif ($sortBy === 'price_desc') {
        $orderBy = "e.unit_price DESC";
    } elseif ($sortBy === 'name_asc') {
        $orderBy = "e.name ASC";
    }

    $prodStmt = $pdo->prepare("
        SELECT e.*,
               sc.name AS super_category_name,
               c.name AS category_name,
               sub.name AS sub_category_name
        FROM equipment_inventory e
        LEFT JOIN super_categories sc ON e.super_category_id = sc.id
        LEFT JOIN categories c ON e.category_id = c.id
        LEFT JOIN sub_categories sub ON e.sub_category_id = sub.id
        $whereClause
        ORDER BY $orderBy
    ");
    $prodStmt->execute($params);
    $products = $prodStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Shop DB error: " . $e->getMessage());
    $superCategories = [];
    $allCategories = [];
    $allSubCategories = [];
    $products = [];
}

require_once __DIR__ . '/../includes/user-header.php';
?>

<div style="display:flex; flex-direction:column; gap:24px;">

    <!-- SHOP HEADER -->
    <div style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:16px;">
        <div>
            <h1 style="font-family:'Fraunces', Georgia, serif; font-size:2rem; font-weight:700; margin:0 0 6px; color:var(--user-dark);">
                Hospital Surgical Catalogue &amp; Shop
            </h1>
            <p style="margin:0; font-size:0.875rem; color:#64748b;">
                Certified CE &amp; ISO 13485 surgical tools, electrosurgical instruments, and theater equipment.
            </p>
        </div>

        <button type="button" class="btn btn-primary" onclick="openCartDrawer()" style="padding:10px 20px; font-weight:700;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
            <span>View Procurement Cart</span>
        </button>
    </div>

    <!-- FILTER & SEARCH CONTROLS -->
    <div class="card p-4" style="border-radius:14px;">
        <form method="get" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:12px; align-items:flex-end;">
            <div>
                <label style="display:block; font-size:0.6875rem; font-weight:700; text-transform:uppercase; margin-bottom:4px; color:#64748b;">Search Catalogue</label>
                <input type="text" name="search" class="form-control" placeholder="Search by name, SKU, or specs..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div>
                <label style="display:block; font-size:0.6875rem; font-weight:700; text-transform:uppercase; margin-bottom:4px; color:#64748b;">Super Category (L1)</label>
                <select name="super_id" class="form-control" onchange="this.form.submit()">
                    <option value="0">All Super Categories</option>
                    <?php foreach ($superCategories as $sc): ?>
                        <option value="<?= (int)$sc['id'] ?>" <?= $superFilter === (int)$sc['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sc['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="display:block; font-size:0.6875rem; font-weight:700; text-transform:uppercase; margin-bottom:4px; color:#64748b;">Category (L2)</label>
                <select name="category_id" class="form-control" onchange="this.form.submit()">
                    <option value="0">All Categories</option>
                    <?php foreach ($allCategories as $c): ?>
                        <?php if ($superFilter === 0 || (int)$c['super_category_id'] === $superFilter): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= $catFilter === (int)$c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="display:block; font-size:0.6875rem; font-weight:700; text-transform:uppercase; margin-bottom:4px; color:#64748b;">Sort Order</label>
                <select name="sort" class="form-control" onchange="this.form.submit()">
                    <option value="featured" <?= $sortBy === 'featured' ? 'selected' : '' ?>>Featured (Recommended)</option>
                    <option value="price_asc" <?= $sortBy === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                    <option value="price_desc" <?= $sortBy === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                    <option value="name_asc" <?= $sortBy === 'name_asc' ? 'selected' : '' ?>>Name: A to Z</option>
                </select>
            </div>

            <div style="display:flex; gap:8px;">
                <button type="submit" class="btn btn-primary btn-block" style="font-weight:700;">Filter</button>
                <?php if ($search !== '' || $superFilter > 0 || $catFilter > 0 || $subCatFilter > 0 || $sortBy !== 'featured'): ?>
                    <a href="/surgical_equipment/user/shop.php" class="btn btn-secondary" style="white-space:nowrap;">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- PRODUCTS GRID -->
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <span style="font-size:0.875rem; font-weight:700; color:#64748b;">
            Showing <strong><?= count($products) ?></strong> surgical items
        </span>
    </div>

    <?php if (empty($products)): ?>
        <div class="card text-center py-5">
            <h3 style="font-size:1.125rem; font-weight:700; color:#475569;">No surgical instruments found matching criteria.</h3>
            <p style="font-size:0.875rem; color:#94a3b8;">Try clearing the search filters or choosing another category.</p>
            <div style="margin-top:14px;">
                <a href="/surgical_equipment/user/shop.php" class="btn btn-primary btn-sm">Reset Filters</a>
            </div>
        </div>
    <?php else: ?>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:22px;">
            <?php foreach ($products as $prod): 
                $imgUrl = get_product_image_url($prod['category'], $prod['name'], $prod['equipment_code'], $prod['notes']);
            ?>
                <div class="card p-0" style="border-radius:16px; overflow:hidden; display:flex; flex-direction:column; justify-content:space-between; transition:transform 0.2s, box-shadow 0.2s; box-shadow:0 4px 15px -4px rgba(0,0,0,0.06);">
                    
                    <!-- Product Image & Badges -->
                    <div style="position:relative; background:#f1f5f9; height:190px; overflow:hidden; display:flex; align-items:center; justify-content:center;">
                        <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($prod['name']) ?>" style="width:100%; height:100%; object-fit:cover;" onerror="this.src='/surgical_equipment/assets/images/hero-landscape.jpg'">
                        
                        <div style="position:absolute; top:12px; left:12px; display:flex; gap:6px; flex-wrap:wrap;">
                            <span style="background:rgba(255,255,255,0.92); backdrop-filter:blur(4px); color:var(--user-primary); font-size:0.6875rem; font-weight:800; padding:3px 8px; border-radius:6px; text-transform:uppercase; letter-spacing:0.04em;">
                                <?= htmlspecialchars($prod['category']) ?>
                            </span>
                        </div>

                        <div style="position:absolute; bottom:10px; right:12px;">
                            <span style="background:rgba(30,40,13,0.85); color:#ffffff; font-family:monospace; font-size:0.6875rem; padding:2px 7px; border-radius:4px;">
                                <?= htmlspecialchars($prod['equipment_code']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Product Body Content -->
                    <div style="padding:18px 20px; flex:1; display:flex; flex-direction:column; justify-content:space-between;">
                        <div>
                            <?php if (!empty($prod['super_category_name'])): ?>
                                <div style="font-size:0.6875rem; font-weight:700; color:#0284c7; margin-bottom:4px; text-transform:uppercase;">
                                    <?= htmlspecialchars($prod['super_category_name']) ?> &rarr; <?= htmlspecialchars($prod['category_name'] ?? '') ?>
                                </div>
                            <?php endif; ?>

                            <h3 style="font-size:1.0625rem; font-weight:700; margin:0 0 8px; color:var(--user-dark); line-height:1.3;">
                                <?= htmlspecialchars($prod['name']) ?>
                            </h3>

                            <p style="font-size:0.8125rem; color:#64748b; margin:0 0 14px; line-height:1.45; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                                <?= htmlspecialchars($prod['notes'] ?: 'High precision surgical instrument manufactured from medical grade German alloy.') ?>
                            </p>

                            <!-- Specs Chips -->
                            <div style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:16px;">
                                <span style="font-size:0.6875rem; background:#f8faf5; border:1px solid #e0e7dc; color:#3E531B; padding:2px 6px; border-radius:4px; font-weight:600;">
                                    CE Certified
                                </span>
                                <span style="font-size:0.6875rem; background:#f8faf5; border:1px solid #e0e7dc; color:#3E531B; padding:2px 6px; border-radius:4px; font-weight:600;">
                                    ISO 13485
                                </span>
                                <span style="font-size:0.6875rem; background:#ecfdf5; color:#047857; padding:2px 6px; border-radius:4px; font-weight:700;">
                                    In Stock (<?= (int)$prod['stock_quantity'] ?> units)
                                </span>
                            </div>
                        </div>

                        <!-- Pricing & Order Action -->
                        <div style="border-top:1px solid #f1f5f9; padding-top:14px; display:flex; align-items:center; justify-content:space-between; gap:12px;">
                            <div>
                                <span style="font-size:0.6875rem; color:#94a3b8; text-transform:uppercase; font-weight:700; display:block;">Unit Price</span>
                                <span style="font-size:1.25rem; font-weight:800; color:var(--user-primary);">$<?= number_format((float)$prod['unit_price'], 2) ?></span>
                            </div>

                            <div style="display:flex; align-items:center; gap:8px;">
                                <div style="display:flex; align-items:center; border:1px solid #cbd5e1; border-radius:6px; background:#ffffff; overflow:hidden;">
                                    <button type="button" onclick="adjustCardQty(<?= (int)$prod['id'] ?>, -1)" style="padding:4px 8px; border:none; background:none; cursor:pointer; font-weight:700; font-size:0.875rem;">-</button>
                                    <input type="number" id="qty_<?= (int)$prod['id'] ?>" value="1" min="1" max="99" style="width:36px; text-align:center; border:none; font-weight:700; font-size:0.875rem; outline:none;" readonly>
                                    <button type="button" onclick="adjustCardQty(<?= (int)$prod['id'] ?>, 1)" style="padding:4px 8px; border:none; background:none; cursor:pointer; font-weight:700; font-size:0.875rem;">+</button>
                                </div>

                                <button type="button" class="btn btn-primary btn-sm" onclick="addCustomQtyToCart(<?= (int)$prod['id'] ?>, '<?= addslashes($prod['name']) ?>', '<?= addslashes($prod['equipment_code']) ?>', <?= (float)$prod['unit_price'] ?>, '<?= addslashes($imgUrl) ?>')" style="padding:8px 14px; font-weight:700; font-size:0.8125rem;">
                                    + Add
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<script>
function adjustCardQty(id, delta) {
    const input = document.getElementById('qty_' + id);
    if (!input) return;
    let val = parseInt(input.value) || 1;
    val += delta;
    if (val < 1) val = 1;
    if (val > 99) val = 99;
    input.value = val;
}

function addCustomQtyToCart(id, name, sku, price, image) {
    const input = document.getElementById('qty_' + id);
    const qty = input ? (parseInt(input.value) || 1) : 1;
    addToCart({ id, name, sku, price, image, qty });
}
</script>

<?php require_once __DIR__ . '/../includes/user-footer.php'; ?>
