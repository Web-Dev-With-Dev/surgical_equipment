<?php
$pageTitle = "Medical & Surgical Products Catalogue | Apex Surgical";
$pageDescription = "Browse 10 medical categories including Surgical Instruments, OT Equipment, ICU Monitors, Laparoscopy sets, Autoclaves, and Hospital Furniture.";
require_once __DIR__ . '/includes/public-header.php';

// Comprehensive catalog database of 10 categories
$allProducts = [
    // 1. Surgical Instruments
    [
        'id' => 1,
        'name' => 'Metzenbaum Dissecting Scissors (Curved)',
        'category' => 'Surgical Instruments',
        'sku' => 'EQ-SI-101',
        'material' => 'German Stainless Steel (Tungsten Carbide Inset)',
        'size_model' => '7 Inch (180mm) / Gold Ring Handle',
        'desc' => 'High-precision dissecting scissors with micro-serrated tungsten carbide blades for delicate soft tissue dissection.',
        'specs' => ['Autoclavable at 134°C', 'Gold Ring Handle (TC)', 'Non-glare satin finish', 'Passivated against pitting'],
        'brand' => 'Aesculap / Apex OEM',
    ],
    [
        'id' => 2,
        'name' => 'Mayo-Hegar Needle Holder (TC Jaws)',
        'category' => 'Surgical Instruments',
        'sku' => 'EQ-SI-102',
        'material' => 'AISI 420 Surgical Stainless Steel',
        'size_model' => '6 Inch (150mm) / 8 Inch (200mm)',
        'desc' => 'Cross-serrated Tungsten Carbide jaws ensuring non-slip grip on curved surgical suture needles.',
        'specs' => ['Pyramid serrations 0.4mm pitch', 'Ergonomic finger rings', 'Box-lock joint design', 'CE Approved'],
        'brand' => 'Apex Surgical',
    ],
    [
        'id' => 3,
        'name' => 'Babcock Atraumatic Tissue Grasping Forceps',
        'category' => 'Surgical Instruments',
        'sku' => 'EQ-SI-103',
        'material' => 'Medical Grade Stainless Steel',
        'size_model' => '6.5 Inch (160mm) & 8 Inch (200mm)',
        'desc' => 'Loop-shaped jaws designed for holding delicate tubular organs and bowel without tissue ischemia.',
        'specs' => ['Non-traumatic grip', 'Multi-step ratchet lock', 'Ultra-lightweight balance', '100% Autoclave Safe'],
        'brand' => 'Aesculap',
    ],

    // 2. OT Equipment
    [
        'id' => 4,
        'name' => 'Electro-Hydraulic Operating Table (Universal)',
        'category' => 'OT Equipment',
        'sku' => 'EQ-OT-201',
        'material' => 'SS 304 Stainless Steel Chassis & PU Memory Foam',
        'size_model' => 'Model OT-Pro 9000 (C-Arm Compatible)',
        'desc' => 'Multi-position universal surgical table with motorized Trendelenburg, lateral tilt, flex-reflex, and kidney bridge.',
        'specs' => ['Weight Capacity: 280 kg', 'Full C-Arm Radiotranslucent top', 'Built-in battery backup', 'Emergency manual override'],
        'brand' => 'Apex OEM',
    ],
    [
        'id' => 5,
        'name' => 'Shadowless Twin LED OT Ceiling Surgical Light',
        'category' => 'OT Equipment',
        'sku' => 'EQ-OT-202',
        'material' => 'Aviation Aluminum Head with German OSRAM LEDs',
        'size_model' => 'Twin Dome 700mm + 500mm',
        'desc' => 'High-intensity cool illumination up to 160,000 Lux with adjustable color temperature and deep-cavity endo-mode.',
        'specs' => ['Illuminance: 160,000 + 120,000 Lux', 'Color Temp: 3800K - 5000K', 'LED Lifespan > 60,000 hours', 'Endoscopy mode button'],
        'brand' => 'Apex Surgical',
    ],

    // 3. Hospital Furniture
    [
        'id' => 6,
        'name' => '5-Function Motorized ICU Hospital Bed',
        'category' => 'Hospital Furniture',
        'sku' => 'EQ-HF-301',
        'material' => 'Mild Steel Epoxy Powder Coated & ABS Panels',
        'size_model' => 'Model ICU-Elite 5M with Nurse Control Panel',
        'desc' => 'Advanced motorized patient bed with backrest, knee-rest, height adjustment, Trendelenburg, and reverse Trendelenburg.',
        'specs' => ['Linak Danish linear actuators', 'Tente 125mm central locking wheels', 'One-touch cardiac chair position', 'CPR emergency quick release'],
        'brand' => 'Apex MedTech',
    ],
    [
        'id' => 7,
        'name' => 'Stainless Steel Emergency Crash Cart Trolley',
        'category' => 'Hospital Furniture',
        'sku' => 'EQ-HF-302',
        'material' => 'Heavy Gauge SS 304 Grade',
        'size_model' => '5 Drawers with Defibrillator Shelf & IV Pole',
        'desc' => 'Mobile resuscitation cart with central locking, oxygen cylinder holder, cardiac CPR board, and modular drawer dividers.',
        'specs' => ['5 Central lockable sliding drawers', 'Defibrillator tray 360° swivel', 'Heavy duty anti-static castors', 'Built-in power extension socket'],
        'brand' => 'Apex MedTech',
    ],

    // 4. ICU Equipment
    [
        'id' => 8,
        'name' => '12.1-Inch Multi-Parameter ICU Patient Monitor',
        'category' => 'ICU Equipment',
        'sku' => 'EQ-ICU-401',
        'material' => 'Antimicrobial ABS Housing',
        'size_model' => 'Model LifeGuard-1200 (6-Para Standard)',
        'desc' => 'Displays ECG (3/5 Lead), SpO2, NIBP, Respiration, 2-Temp, and Pulse Rate with optional EtCO2 and Dual IBP modules.',
        'specs' => ['12.1" Color TFT touch screen', '120-hour graphic & tabular trends', 'Lithium battery backup (4+ hours)', 'Central nursing station connectivity'],
        'brand' => 'Mindray / Apex OEM',
    ],
    [
        'id' => 9,
        'name' => 'Microprocessor Syringe Infusion Pump',
        'category' => 'ICU Equipment',
        'sku' => 'EQ-ICU-402',
        'material' => 'High-Impact Engineering Polycarbonate',
        'size_model' => 'Dual Channel & Single Channel Models',
        'desc' => 'Accurate drug delivery system compatible with all standard 5ml, 10ml, 20ml, 30ml, and 50/60ml syringes.',
        'specs' => ['Flow rate: 0.1 to 1500 ml/hr', 'Accuracy: ±2%', 'Anti-bolus & occlusion sensing', 'Audio-visual multi alarms'],
        'brand' => 'Apex MedTech',
    ],

    // 5. Laparoscopy Instruments
    [
        'id' => 10,
        'name' => 'Laparoscopic Bipolar Maryland Grasper (5mm)',
        'category' => 'Laparoscopy Instruments',
        'sku' => 'EQ-LAP-501',
        'material' => 'Medical Stainless Steel with PTFE Insulated Shaft',
        'size_model' => '5mm Diameter x 330mm Working Length',
        'desc' => 'Rotatable 360-degree curved Maryland dissecting grasper with bipolar electro-cautery coagulation connection.',
        'specs' => ['360° Finger rotation wheel', 'High-dielectric insulation sleeve', 'Detachable 3-piece cleaning design', 'Autoclavable'],
        'brand' => 'Karl Storz / Apex OEM',
    ],
    [
        'id' => 11,
        'name' => 'Reusable Safety Trocar & Cannula Set',
        'category' => 'Laparoscopy Instruments',
        'sku' => 'EQ-LAP-502',
        'material' => 'Stainless Steel with Silicone Sealing Valve',
        'size_model' => '5mm & 10mm Pyramidal Tip Cannulas',
        'desc' => 'Abdominal port access set with magnetic flapper valve and stopcock for CO2 insufflation tubing.',
        'specs' => ['Zero gas leakage silicone seals', 'Threaded anchor sleeve option', 'Sharp pyramidal / blunt tips', 'Corrosion-free alloy'],
        'brand' => 'Apex Surgical',
    ],

    // 6. Orthopedic Instruments
    [
        'id' => 12,
        'name' => 'Orthopedic Bone Drill & Sagittal Saw System',
        'category' => 'Orthopedic Instruments',
        'sku' => 'EQ-ORT-601',
        'material' => 'Aircraft Grade Aluminum & Stainless Steel Collet',
        'size_model' => 'Cordless Lithium Battery Driven (System 8 Type)',
        'desc' => 'High-torque bone drill with cannulated chuck (0.8–8mm) and oscillating sagittal saw for joint replacement and trauma.',
        'specs' => ['Speed: 0-1200 RPM stepless', 'Full handpiece 134°C autoclavable', 'Dual 14.4V rechargeable battery packs', 'Keyless AO quick coupling'],
        'brand' => 'Stryker / Apex OEM',
    ],

    // 7. Anaesthesia Equipment
    [
        'id' => 13,
        'name' => 'Integrated Anaesthesia Workstation with Ventilator',
        'category' => 'Anaesthesia Equipment',
        'sku' => 'EQ-ANS-701',
        'material' => 'SS Frame with Antimicrobial Coated Alloy',
        'size_model' => 'Dual Vaporizer Station (Isoflurane / Sevoflurane)',
        'desc' => 'Pneumatically driven electronic anaesthesia ventilator with integrated Circle Absorber, O2/N2O/Air flowmeters, and hypoxic guard.',
        'specs' => ['Tidal Volume: 20 to 1500 ml', 'Ventilation Modes: VCV, PCV, SIMV, PSV', 'Selectatec compatible manifold', 'Battery backup 3 hours'],
        'brand' => 'Mindray / Apex OEM',
    ],

    // 8. Autoclave & Sterilizers
    [
        'id' => 14,
        'name' => 'Horizontal High-Pressure Steam Autoclave (Class B)',
        'category' => 'Autoclave & Sterilizers',
        'sku' => 'EQ-ST-801',
        'material' => 'SS 316L Inner Chamber & SS 304 Outer Body',
        'size_model' => 'Capacities: 75 Litres to 500 Litres',
        'desc' => 'Heavy-duty automatic steam sterilizer with fractional vacuum cycles, digital microprocessor temperature/pressure controller, and printer.',
        'specs' => ['Working Temp: 121°C & 134°C', 'Radial locking safety door', 'Automatic water filling & vacuum drying', 'Digital cycle printout'],
        'brand' => 'Apex SteriTech',
    ],

    // 9. Suction Machines
    [
        'id' => 15,
        'name' => 'Heavy-Duty Electric Surgical Suction Unit (Twin Jar)',
        'category' => 'Suction Machines',
        'sku' => 'EQ-SUC-901',
        'material' => 'SS 304 Trolley Body with Polycarbonate Jars',
        'size_model' => 'Capacity: 2 x 4000ml (4 Litre Jars)',
        'desc' => 'Oil-free piston pump suction apparatus delivering high vacuum up to -0.09 MPa with overflow safety protection.',
        'specs' => ['Suction Capacity: > 60 Litres/min', 'Max Vacuum: -680 mmHg (-0.09MPa)', 'Shatter-proof autoclavable jars', 'Foot pedal & hand switch operation'],
        'brand' => 'Apex MedTech',
    ],

    // 10. Surgical Disposables
    [
        'id' => 16,
        'name' => 'Sterile Electrosurgical Cautery Pencil (Hand Switch)',
        'category' => 'Surgical Disposables',
        'sku' => 'EQ-DSP-1001',
        'material' => 'Medical Grade PVC with Stainless Steel Blade Electrode',
        'size_model' => '3-Pin Standard Plug with 3-Meter Cable',
        'desc' => 'Disposable cut & coag push-button electrosurgery pencil with hex-lock non-turn blade tip.',
        'specs' => ['EO Gas Pre-Sterilized in peel pouch', 'Push-button tactile feedback', 'Universal 3-prong generator fit', 'Pack of 50 / 100 pcs'],
        'brand' => 'Apex Disposable',
    ]
];

// Active category filter from GET
$activeCategory = $_GET['cat'] ?? 'all';
?>

<!-- PAGE BANNER -->
<section class="pub-page-header">
    <div class="pub-container pub-page-header-content">
        <div class="pub-breadcrumb">
            <a href="/surgical_equipment/index.php">Home</a>
            <span>/</span>
            <span>Products Catalogue</span>
        </div>
        <h1 class="pub-page-title">Surgical & Medical Equipment Catalogue</h1>
        <p class="pub-page-subtitle">
            Explore our complete hospital equipment portfolio across 10 specialized departments with technical specifications, German-grade metallurgy, and instant quote requests.
        </p>
    </div>
</section>

<!-- PRODUCTS SECTION -->
<section style="padding: 60px 0 80px 0; background: var(--pub-bg);">
    <div class="pub-container">
        
        <!-- SEARCH & FILTER TOOLBAR -->
        <div style="background: #ffffff; border: 1px solid var(--pub-border); border-radius: var(--pub-radius-lg); padding: 24px; margin-bottom: 36px; box-shadow: var(--pub-shadow-sm);">
            
            <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 18px; margin-bottom: 20px; align-items: center;">
                <!-- Live Search Bar -->
                <div style="position: relative;">
                    <input type="text" id="prodSearchInput" class="pub-input" placeholder="🔍 Search product name, SKU, material, or keyword (e.g. scissors, autoclave, monitor)..." style="padding-left: 16px; font-size: 15px;">
                </div>

                <!-- Stats summary -->
                <div style="text-align: right; color: var(--pub-text-muted); font-size: 14px;">
                    Showing <strong id="visibleCount" style="color: var(--pub-primary);"><?= count($allProducts) ?></strong> equipment models
                </div>
            </div>

            <!-- Category Filter Tabs -->
            <div style="display: flex; gap: 8px; flex-wrap: wrap;" id="categoryFilterGroup">
                <button class="pub-btn pub-cat-pill <?= ($activeCategory === 'all') ? 'active' : '' ?>" data-cat="all">All Products</button>
                <button class="pub-btn pub-cat-pill <?= ($activeCategory === 'Surgical Instruments') ? 'active' : '' ?>" data-cat="Surgical Instruments">Surgical Instruments</button>
                <button class="pub-btn pub-cat-pill <?= ($activeCategory === 'OT Equipment') ? 'active' : '' ?>" data-cat="OT Equipment">OT Equipment</button>
                <button class="pub-btn pub-cat-pill <?= ($activeCategory === 'Hospital Furniture') ? 'active' : '' ?>" data-cat="Hospital Furniture">Hospital Furniture</button>
                <button class="pub-btn pub-cat-pill <?= ($activeCategory === 'ICU Equipment') ? 'active' : '' ?>" data-cat="ICU Equipment">ICU Equipment</button>
                <button class="pub-btn pub-cat-pill <?= ($activeCategory === 'Laparoscopy Instruments') ? 'active' : '' ?>" data-cat="Laparoscopy Instruments">Laparoscopy Instruments</button>
                <button class="pub-btn pub-cat-pill <?= ($activeCategory === 'Orthopedic Instruments') ? 'active' : '' ?>" data-cat="Orthopedic Instruments">Orthopedic Instruments</button>
                <button class="pub-btn pub-cat-pill <?= ($activeCategory === 'Anaesthesia Equipment') ? 'active' : '' ?>" data-cat="Anaesthesia Equipment">Anaesthesia Equipment</button>
                <button class="pub-btn pub-cat-pill <?= ($activeCategory === 'Autoclave & Sterilizers') ? 'active' : '' ?>" data-cat="Autoclave & Sterilizers">Autoclave & Sterilizers</button>
                <button class="pub-btn pub-cat-pill <?= ($activeCategory === 'Suction Machines') ? 'active' : '' ?>" data-cat="Suction Machines">Suction Machines</button>
                <button class="pub-btn pub-cat-pill <?= ($activeCategory === 'Surgical Disposables') ? 'active' : '' ?>" data-cat="Surgical Disposables">Surgical Disposables</button>
            </div>

        </div>

        <!-- PRODUCTS GRID -->
        <div id="productsGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 28px;">
            <?php foreach ($allProducts as $p): ?>
                <div class="pub-product-card" data-category="<?= htmlspecialchars($p['category']) ?>" data-search="<?= strtolower(htmlspecialchars($p['name'] . ' ' . $p['sku'] . ' ' . $p['material'] . ' ' . $p['desc'] . ' ' . $p['category'])) ?>" style="background: #ffffff; border: 1px solid var(--pub-border); border-radius: var(--pub-radius-lg); padding: 26px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: var(--pub-shadow-sm); transition: var(--pub-transition);">
                    
                    <div>
                        <!-- Category & SKU Tag -->
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <span class="pub-badge-cert" style="font-size: 11.5px; background: #e0f2fe; color: #0284c7; border: none; font-weight: 700;"><?= htmlspecialchars($p['category']) ?></span>
                            <span class="text-muted" style="font-size: 12px; font-family: monospace; font-weight: 600;"><?= htmlspecialchars($p['sku']) ?></span>
                        </div>

                        <!-- Product Name -->
                        <h3 style="font-size: 19px; margin-bottom: 8px; color: var(--pub-primary); line-height: 1.35;">
                            <?= htmlspecialchars($p['name']) ?>
                        </h3>

                        <!-- Model / Size -->
                        <div style="font-size: 13px; color: var(--pub-secondary); font-weight: 600; margin-bottom: 10px;">
                            Model/Size: <?= htmlspecialchars($p['size_model']) ?>
                        </div>

                        <!-- Description -->
                        <p style="color: var(--pub-text-muted); font-size: 13.5px; line-height: 1.6; margin-bottom: 14px;">
                            <?= htmlspecialchars($p['desc']) ?>
                        </p>

                        <!-- Material Pill -->
                        <div style="background: var(--pub-surface-alt); padding: 8px 12px; border-radius: var(--pub-radius-sm); font-size: 12.5px; color: #334155; margin-bottom: 14px;">
                            <strong>Material:</strong> <?= htmlspecialchars($p['material']) ?>
                        </div>

                        <!-- Technical Specs Bullets -->
                        <div style="margin-bottom: 16px;">
                            <span style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--pub-text-muted); letter-spacing: 0.5px; display: block; margin-bottom: 6px;">Key Highlights:</span>
                            <ul style="list-style: none; font-size: 12.5px; color: var(--pub-text-muted); display: flex; flex-direction: column; gap: 4px;">
                                <?php foreach ($p['specs'] as $spec): ?>
                                    <li style="display: flex; align-items: center; gap: 6px;">
                                        <span style="color: #16a34a; font-weight: 700;">✓</span> <?= htmlspecialchars($spec) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--pub-border); display: flex; gap: 8px;">
                        <a href="/surgical_equipment/product-details.php?id=<?= $p['id'] ?>" class="pub-btn pub-btn-outline" style="font-size: 12.5px; padding: 8px 12px;" title="View Technical Specifications">
                            <span>Specs</span>
                        </a>
                        <a href="/surgical_equipment/contact.php?product=<?= urlencode($p['name']) ?>&sku=<?= urlencode($p['sku']) ?>#contact-form" class="pub-btn pub-btn-primary" style="flex: 1; font-size: 13px; padding: 8px 12px;">
                            <span>Get Quote</span>
                        </a>
                        <a href="https://wa.me/917863884678?text=<?= urlencode('Hello Apex Surgical, I want a price quote & technical datasheet for ' . $p['name'] . ' (' . $p['sku'] . ')') ?>" target="_blank" rel="noopener noreferrer" class="pub-btn pub-quick-wa" style="padding: 8px 12px;" title="Inquire on WhatsApp">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2zm5.78 14.15c-.24.67-1.39 1.29-1.93 1.34-.51.05-1.17.07-3.79-.98-3.15-1.26-5.18-4.45-5.34-4.66-.16-.21-1.28-1.7-1.28-3.25 0-1.55.81-2.31 1.1-2.62.29-.31.64-.39.85-.39.21 0 .43 0 .61.01.2.01.46-.07.72.55.27.64.91 2.22.99 2.38.08.16.13.35.03.56-.11.21-.16.35-.32.53-.16.19-.34.42-.49.56-.16.16-.33.34-.14.66.19.32.84 1.38 1.8 2.24 1.24 1.1 2.28 1.45 2.6 1.61.32.16.51.13.7-.08.19-.21.8-.93 1.01-1.25.21-.32.43-.27.72-.16.29.11 1.86.88 2.18 1.04.32.16.53.24.61.37.08.13.08.77-.16 1.44z"/></svg>
                        </a>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

        <!-- No Results Fallback -->
        <div id="noResultsMsg" style="display: none; text-align: center; padding: 60px 20px; background: #ffffff; border-radius: var(--pub-radius-lg); border: 1px solid var(--pub-border); margin-top: 20px;">
            <div style="font-size: 40px; margin-bottom: 12px;">🔍</div>
            <h3 style="font-size: 20px; color: var(--pub-primary); margin-bottom: 8px;">No matching equipment found</h3>
            <p class="text-muted" style="font-size: 14px; margin-bottom: 18px;">Try searching for a different keyword or view all product categories.</p>
            <button class="pub-btn pub-btn-outline" onclick="resetFilters()">Reset All Filters</button>
        </div>

    </div>
</section>

<!-- Custom Styles for Filter Pills -->
<style>
.pub-cat-pill {
    background: var(--pub-surface-alt);
    color: var(--pub-text-main);
    border: 1px solid var(--pub-border);
    padding: 8px 16px;
    font-size: 13px;
    border-radius: var(--pub-radius-full);
}
.pub-cat-pill:hover,
.pub-cat-pill.active {
    background: var(--pub-secondary);
    color: #ffffff;
    border-color: var(--pub-secondary);
}
.pub-product-card:hover {
    border-color: var(--pub-secondary);
    transform: translateY(-4px);
    box-shadow: var(--pub-shadow-lg);
}
</style>

<!-- Instant Search & Category Filter Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('prodSearchInput');
    const pills = document.querySelectorAll('.pub-cat-pill');
    const cards = document.querySelectorAll('.pub-product-card');
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

        visibleCount.textContent = matched;
        noResults.style.display = (matched === 0) ? 'block' : 'none';
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
