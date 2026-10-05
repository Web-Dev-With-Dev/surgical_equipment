<?php
$productId = (int)($_GET['id'] ?? 1);

// Catalog data
$catalog = [
    1 => [
        'name' => 'Metzenbaum Dissecting Scissors (Curved)',
        'category' => 'Surgical Instruments',
        'sku' => 'EQ-SI-101',
        'material' => 'German Stainless Steel (AISI 420) with Tungsten Carbide (TC) Inlay',
        'size_model' => '7 Inch (180mm) / Gold Ring Handle',
        'brand' => 'Aesculap / Apex OEM',
        'warranty' => '2 Years OEM Replacement Guarantee',
        'desc' => 'High-precision Metzenbaum dissecting scissors engineered with micro-serrated Tungsten Carbide insert cutting edges for delicate anatomical tissue dissection. Featuring ergonomic gold-plated finger rings denoting TC grade craftsmanship and a box-lock joint ensuring friction-free blade alignment.',
        'features' => [
            'Tungsten Carbide (TC) micro-welded jaws for long-lasting sharpness.',
            'Tested for over 500 autoclave cycles at 134°C without corrosion.',
            'Electrolytically passivated satin finish eliminates operating room light reflection.',
            'Atraumatic curved tips tailored for thoracic, vascular, and general surgery.',
        ],
        'specs' => [
            'Overall Length' => '180 mm (7 Inches)',
            'Tip Configuration' => 'Curved Blunt / Blunt',
            'Blade Inlay' => 'Tungsten Carbide (TC) Hardness > 60 HRC',
            'Handle Type' => 'Dual Ring Handle (Gold Plated)',
            'Sterilization Method' => 'Steam Autoclave (134°C), ETO, Plasma',
            'Regulatory Approval' => 'CE 0123 / CDSCO Compliant',
        ]
    ],
    2 => [
        'name' => 'Mayo-Hegar Needle Holder (TC Jaws)',
        'category' => 'Surgical Instruments',
        'sku' => 'EQ-SI-102',
        'material' => 'AISI 420 Surgical Stainless Steel with Tungsten Carbide Inserts',
        'size_model' => '6 Inch (150mm) / 8 Inch (200mm)',
        'brand' => 'Apex Surgical',
        'warranty' => '2 Years Guarantee',
        'desc' => 'Cross-serrated Tungsten Carbide jaws with pyramid teeth providing maximum needle retention without slippage during deep wound closure and microsurgery suturing.',
        'features' => [
            'Precision pyramid serration 0.4mm pitch for needle stabilization.',
            'Multi-step positive locking ratchet mechanism.',
            'Box-lock joint preventing suture entanglement.',
            '100% Autoclave & ultrasonic cleaning compatible.',
        ],
        'specs' => [
            'Overall Length' => '160 mm / 200 mm',
            'Jaw Profile' => 'Straight Cross-Serrated TC Inset',
            'Recommended Suture Sizes' => '2-0, 3-0, 4-0, 5-0',
            'Handle Type' => 'Gold-Plated Ergonomic Finger Loops',
            'Sterilization Method' => 'Steam Autoclave (134°C), Gas Plasma',
            'Regulatory Approval' => 'CE / CDSCO Class B',
        ]
    ],
    4 => [
        'name' => 'Electro-Hydraulic Operating Table (Universal)',
        'category' => 'OT Equipment',
        'sku' => 'EQ-OT-201',
        'material' => 'SS 304 Surgical Grade Stainless Steel & Seamless PU Memory Mattress',
        'size_model' => 'Model OT-Pro 9000 (Universal C-Arm Compatible)',
        'brand' => 'Apex OEM',
        'warranty' => '3 Years Comprehensive Warranty',
        'desc' => 'State-of-the-art electro-hydraulic universal surgical table engineered for general surgery, orthopedic, cardiac, neuro, and laparoscopic procedures with full C-Arm radiotranslucency and motorized multi-axis articulation.',
        'features' => [
            'Motorized height, Trendelenburg, lateral tilt, flex-reflex, and backrest elevation.',
            'Built-in kidney bridge elevation up to 120mm.',
            'Corded hand control pendant with emergency override safety toggle.',
            'Heavy-duty base with hydraulic central braking castors.',
        ],
        'specs' => [
            'Maximum Patient Weight' => '280 kg (617 lbs)',
            'Height Adjustment Range' => '680 mm to 1000 mm',
            'Trendelenburg / Reverse' => '±25° / ±25°',
            'Lateral Tilt (Left / Right)' => '±20° / ±20°',
            'Power Supply' => '220V / 50Hz with 4-Hour Battery Backup',
            'Mattress Type' => 'Antimicrobial, Anti-Static PU Memory Foam (60mm)',
        ]
    ],
    8 => [
        'name' => '12.1-Inch Multi-Parameter ICU Patient Monitor',
        'category' => 'ICU Equipment',
        'sku' => 'EQ-ICU-401',
        'material' => 'Antimicrobial Polycarbonate Housing',
        'size_model' => 'Model LifeGuard-1200 (6-Para Standard)',
        'brand' => 'Mindray / Apex OEM',
        'warranty' => '2 Years Warranty with Annual Calibration',
        'desc' => 'High-resolution modular patient monitoring system designed for adult, pediatric, and neonatal ICU and OT surveillance. Displays real-time ECG waveforms, blood oxygen saturation, non-invasive blood pressure, and core temperature.',
        'features' => [
            '12.1-inch color anti-glare TFT touch display with customizable layouts.',
            'Comprehensive arrhythmia analysis and ST segment elevation tracking.',
            '120-hour graphic and tabular trend memory review.',
            'Central nursing station networkable via Wi-Fi or Ethernet LAN.',
        ],
        'specs' => [
            'Parameters Measured' => 'ECG, SpO2, NIBP, Respiration, 2-Temp, Pulse Rate',
            'Display Resolution' => '800 x 600 High-Contrast Pixels',
            'Battery Autonomy' => 'Rechargeable Lithium-ion (> 4 Hours Continuous)',
            'Alarm Modes' => '3-Level Visual Flashing & Audible Smart Alerts',
            'Data Interfaces' => 'VGA Output, USB, RJ45 LAN, Nurse Call Socket',
            'Certification' => 'IEC 60601-1 / CE / CDSCO',
        ]
    ],
    14 => [
        'name' => 'Horizontal High-Pressure Steam Autoclave (Class B)',
        'category' => 'Autoclave & Sterilizers',
        'sku' => 'EQ-ST-801',
        'material' => 'SS 316L Inner Pressure Chamber & SS 304 Outer Enclosure',
        'size_model' => 'Capacities: 75 Litres to 500 Litres Horizontal',
        'brand' => 'Apex SteriTech',
        'warranty' => '3 Years Chamber Warranty + 1 Year Electrical AMC',
        'desc' => 'Fully automatic hospital central sterile supply department (CSSD) steam sterilizer with fractional pre-vacuum air removal and pulse vacuum post-drying cycle for wrapped and hollow surgical loads.',
        'features' => [
            'Microprocessor PID temperature control with dual safety pressure relief valves.',
            'Heavy-duty radial locking door mechanism with silicone gasket seal.',
            'Built-in thermal micro-printer for cycle time, temperature, and pressure recording.',
            'Chemical & biological indicator validation compliant.',
        ],
        'specs' => [
            'Working Temperatures' => '121°C (15 psi) and 134°C (30 psi)',
            'Chamber Material' => 'Heavy Gauge SS 316L (Tested up to 60 psi)',
            'Vacuum System' => 'Water Ring Vacuum Pump with Bio-Filter',
            'Safety Interlocks' => 'Door Lock Under Pressure, Over-Temp Cutoff',
            'Power Rating' => '3-Phase 415V / 50Hz (9kW to 18kW)',
            'Compliance' => 'EN 285 / IS 3829 / CE Approved',
        ]
    ]
];

// Fallback to first product if ID not found
$product = $catalog[$productId] ?? $catalog[1];

$pageTitle = $product['name'] . " | Apex Surgical Products";
$pageDescription = "Technical specifications, material grade, and quotation inquiry for " . $product['name'] . " from Apex Surgical.";
require_once __DIR__ . '/includes/public-header.php';
?>

<!-- PAGE BANNER -->
<section class="pub-page-header">
    <div class="pub-container pub-page-header-content">
        <div class="pub-breadcrumb">
            <a href="/surgical_equipment/index.php">Home</a>
            <span>/</span>
            <a href="/surgical_equipment/products.php">Products</a>
            <span>/</span>
            <span><?= htmlspecialchars($product['name']) ?></span>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 12px;">
            <span class="pub-badge-cert" style="background: rgba(255,255,255,0.15); color: #ffffff; border: 1px solid rgba(255,255,255,0.3);"><?= htmlspecialchars($product['category']) ?></span>
            <span style="color: #cbd5e1; font-family: monospace; font-size: 13px;">SKU: <?= htmlspecialchars($product['sku']) ?></span>
        </div>
        <h1 class="pub-page-title"><?= htmlspecialchars($product['name']) ?></h1>
    </div>
</section>

<!-- MAIN PRODUCT DETAIL -->
<section style="padding: 60px 0 80px 0; background: var(--pub-bg);">
    <div class="pub-container">
        
        <div style="display: grid; grid-template-columns: 1.15fr 1fr; gap: 40px; align-items: start; margin-bottom: 50px;">
            
            <!-- LEFT: PRODUCT HIGHLIGHTS & OVERVIEW -->
            <div style="background: #ffffff; border: 1px solid var(--pub-border); border-radius: var(--pub-radius-xl); padding: 36px; box-shadow: var(--pub-shadow-sm);">
                
                <h2 style="font-size: 24px; margin-bottom: 14px; color: var(--pub-primary);">Product Overview</h2>
                <p style="color: var(--pub-text-muted); font-size: 15px; line-height: 1.7; margin-bottom: 24px;">
                    <?= htmlspecialchars($product['desc']) ?>
                </p>

                <!-- Key Value Badges -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 28px;">
                    <div style="background: var(--pub-surface-alt); padding: 14px; border-radius: var(--pub-radius-md);">
                        <span class="text-muted" style="font-size: 12px; display: block;">Material Metallurgy</span>
                        <strong style="font-size: 13.5px; color: var(--pub-primary);"><?= htmlspecialchars($product['material']) ?></strong>
                    </div>
                    <div style="background: var(--pub-surface-alt); padding: 14px; border-radius: var(--pub-radius-md);">
                        <span class="text-muted" style="font-size: 12px; display: block;">Model / Dimension</span>
                        <strong style="font-size: 13.5px; color: var(--pub-primary);"><?= htmlspecialchars($product['size_model']) ?></strong>
                    </div>
                    <div style="background: var(--pub-surface-alt); padding: 14px; border-radius: var(--pub-radius-md);">
                        <span class="text-muted" style="font-size: 12px; display: block;">Brand / OEM Origin</span>
                        <strong style="font-size: 13.5px; color: var(--pub-primary);"><?= htmlspecialchars($product['brand']) ?></strong>
                    </div>
                    <div style="background: var(--pub-surface-alt); padding: 14px; border-radius: var(--pub-radius-md);">
                        <span class="text-muted" style="font-size: 12px; display: block;">Warranty Guarantee</span>
                        <strong style="font-size: 13.5px; color: #16a34a;"><?= htmlspecialchars($product['warranty']) ?></strong>
                    </div>
                </div>

                <!-- Engineering Features -->
                <h3 style="font-size: 18px; margin-bottom: 12px; color: var(--pub-primary);">Key Performance Features</h3>
                <ul style="list-style: none; display: flex; flex-direction: column; gap: 10px; margin-bottom: 28px;">
                    <?php foreach ($product['features'] as $feat): ?>
                        <li style="display: flex; gap: 10px; font-size: 14px; color: var(--pub-text-muted); line-height: 1.5;">
                            <span style="color: #16a34a; font-weight: 700; flex-shrink: 0;">✓</span>
                            <span><?= htmlspecialchars($feat) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <!-- Brochure PDF Trigger -->
                <div style="padding: 18px; background: #e0f2fe; border: 1px dashed #0284c7; border-radius: var(--pub-radius-md); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <strong style="font-size: 14px; color: #0369a1; display: block;">Technical Datasheet & Brochure (PDF)</strong>
                        <span style="font-size: 12.5px; color: #0284c7;">Includes dimensional blueprints & CE certificates</span>
                    </div>
                    <a href="https://wa.me/917863884678?text=<?= urlencode('Please send technical brochure PDF for ' . $product['name'] . ' (' . $product['sku'] . ')') ?>" target="_blank" class="pub-btn pub-btn-primary" style="font-size: 13px; padding: 8px 16px;">
                        <span>Request PDF Brochure</span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    </a>
                </div>

            </div>

            <!-- RIGHT: INSTANT QUOTE & SPECS TABLE -->
            <div style="display: flex; flex-direction: column; gap: 24px;">
                
                <!-- Instant Quote Card -->
                <div class="pub-form-card" style="padding: 30px;">
                    <span class="pub-form-tag">Direct Factory Price</span>
                    <h3 style="font-size: 22px; margin-bottom: 8px;">Request Official Quotation</h3>
                    <p style="color: var(--pub-text-muted); font-size: 13.5px; margin-bottom: 20px;">
                        Receive official proforma invoice, bulk hospital discount, and dispatch timeline.
                    </p>

                    <form action="/surgical_equipment/process-inquiry.php" method="POST">
                        <input type="hidden" name="inquiry_type" value="Price Quote Request">
                        <input type="hidden" name="equipment_category" value="<?= htmlspecialchars($product['category']) ?>">
                        
                        <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px;">
                            <div>
                                <label class="pub-label">Your Name <span>*</span></label>
                                <input type="text" name="full_name" class="pub-input" placeholder="e.g. Dr. Ramesh Shah / Purchase Manager" required>
                            </div>
                            <div>
                                <label class="pub-label">Phone / WhatsApp <span>*</span></label>
                                <input type="tel" name="phone" class="pub-input" placeholder="+91 78638 84678" required>
                            </div>
                            <div>
                                <label class="pub-label">Hospital / City</label>
                                <input type="text" name="hospital_name" class="pub-input" placeholder="e.g. City Hospital, Ahmedabad">
                            </div>
                            <div>
                                <label class="pub-label">Requirement / Quantity</label>
                                <textarea name="message" class="pub-textarea" style="min-height: 80px;" required>Please send quotation for <?= htmlspecialchars($product['name']) ?> (SKU: <?= htmlspecialchars($product['sku']) ?>). Required Quantity: 1 set.</textarea>
                            </div>
                        </div>

                        <button type="submit" class="pub-btn pub-btn-primary pub-btn-block" style="padding: 12px; font-size: 14.5px;">
                            <span>Submit Quote Request</span>
                        </button>
                    </form>

                    <!-- WhatsApp Button -->
                    <div style="margin-top: 14px; text-align: center;">
                        <a href="https://wa.me/917863884678?text=<?= urlencode('Hello, I am looking for a quick price quote for ' . $product['name'] . ' (Model: ' . $product['sku'] . ')') ?>" target="_blank" rel="noopener noreferrer" class="pub-btn pub-quick-wa pub-btn-block" style="padding: 12px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2zm5.78 14.15c-.24.67-1.39 1.29-1.93 1.34-.51.05-1.17.07-3.79-.98-3.15-1.26-5.18-4.45-5.34-4.66-.16-.21-1.28-1.7-1.28-3.25 0-1.55.81-2.31 1.1-2.62.29-.31.64-.39.85-.39.21 0 .43 0 .61.01.2.01.46-.07.72.55.27.64.91 2.22.99 2.38.08.16.13.35.03.56-.11.21-.16.35-.32.53-.16.19-.34.42-.49.56-.16.16-.33.34-.14.66.19.32.84 1.38 1.8 2.24 1.24 1.1 2.28 1.45 2.6 1.61.32.16.51.13.7-.08.19-.21.8-.93 1.01-1.25.21-.32.43-.27.72-.16.29.11 1.86.88 2.18 1.04.32.16.53.24.61.37.08.13.08.77-.16 1.44z"/></svg>
                            <span>Chat on WhatsApp Now</span>
                        </a>
                    </div>
                </div>

                <!-- Technical Specs Table Card -->
                <div style="background: #ffffff; border: 1px solid var(--pub-border); border-radius: var(--pub-radius-xl); padding: 26px;">
                    <h3 style="font-size: 18px; margin-bottom: 14px; color: var(--pub-primary);">Technical Specifications</h3>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <?php foreach ($product['specs'] as $label => $val): ?>
                            <div style="display: flex; justify-content: space-between; font-size: 13px; padding-bottom: 8px; border-bottom: 1px solid var(--pub-border);">
                                <span class="text-muted"><?= htmlspecialchars($label) ?></span>
                                <strong style="color: var(--pub-primary); text-align: right; max-width: 55%;"><?= htmlspecialchars($val) ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>

        </div>

    </div>
</section>

<?php require_once __DIR__ . '/includes/public-footer.php'; ?>
