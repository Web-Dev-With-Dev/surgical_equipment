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
        'desc' => 'Motorized universal surgical operating table equipped with high-precision micro-hydraulic actuators, remote control handset, 360-degree C-Arm radiotranslucent table top, and emergency manual override pedals.',
        'features' => [
            'Full C-Arm access with 300mm longitudinal table slide.',
            'Ultra-quiet hydraulic elevation from 680mm to 1000mm.',
            'Integrated rechargeable battery pack (up to 80 surgical cycles).',
            'Heavy-duty stainless steel base with central floor locking braking system.',
        ],
        'specs' => [
            'Patient Weight Capacity' => '280 kg (617 lbs)',
            'Table Top Dimensions' => '2100 mm x 520 mm',
            'Trendelenburg / Reverse' => '± 25° Motorized',
            'Lateral Tilt (L/R)' => '± 20° Motorized',
            'Back Plate Adjustment' => '+75° to -20° Electric',
            'Kidney Bridge Elevation' => '120 mm Motorized',
            'Certifications' => 'ISO 13485 / CE / IEC 60601-1',
        ]
    ],
    8 => [
        'name' => '12.1-Inch Multi-Parameter ICU Patient Monitor',
        'category' => 'ICU Equipment',
        'sku' => 'EQ-ICU-401',
        'material' => 'Antimicrobial Polycarbonate Housing',
        'size_model' => 'Model LifeGuard-1200 (6-Para Standard)',
        'brand' => 'Mindray / Apex OEM',
        'warranty' => '2 Years Warranty + 5 Years Spare Support',
        'desc' => 'Modular bedside patient monitor for ICU, CCU, and recovery rooms. Features simultaneous 8-waveform display, ST segment analysis, arrhythmia detection, and rechargeable lithium battery backup.',
        'features' => [
            '12.1-inch color touchscreen with night-mode display.',
            'Standard 6 Parameters: ECG, SpO2, NIBP, Respiration, 2-Channel Temp, Pulse.',
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
        <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 28px; align-items: start; margin-bottom: 50px;">
            
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

                <!-- Brochure PDF Trigger -->
                <div style="padding: 18px; background: var(--fg-lime-tint); border: 1px dashed var(--fg-sage); border-radius: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
                    <div>
                        <strong style="font-size: 14px; color: var(--fg-olive); display: block; margin-bottom: 2px;">Technical Datasheet & Brochure (PDF)</strong>
                        <span style="font-size: 12.5px; color: var(--fg-muted);">Includes dimensional blueprints & CE certificates</span>
                    </div>
                    <a href="https://wa.me/919106595469?text=<?= urlencode('Hello Heer Jani, please send technical brochure PDF for ' . $product['name'] . ' (' . $product['sku'] . ')') ?>" target="_blank" class="figma-card-btn-quote" style="font-size: 12px; padding: 10px 18px; border-radius: 0;">
                        <span>Request PDF Brochure ↗</span>
                    </a>
                </div>

            </div>

            <!-- RIGHT: INSTANT QUOTE & SPECS TABLE -->
            <div style="display: flex; flex-direction: column; gap: 24px;">
                
                <!-- Instant Quote Card -->
                <div style="background: #ffffff; border: 1px solid var(--fg-border); border-radius: 0; padding: 28px; box-shadow: none;">
                    <span class="figma-category-badge" style="margin-bottom: 12px; display: inline-block;">Direct Factory Price</span>
                    <h3 style="font-family: var(--fg-serif); font-size: 26px; margin-bottom: 8px; color: #0d110d;">Request Official Quotation</h3>
                    <p style="color: var(--fg-muted); font-size: 13.5px; margin-bottom: 20px; line-height: 1.5;">
                        Receive official proforma invoice, bulk hospital discount, and dispatch timeline.
                    </p>

                    <form action="/surgical_equipment/process-inquiry.php" method="POST">
                        <input type="hidden" name="inquiry_type" value="Price Quote Request">
                        <input type="hidden" name="equipment_category" value="<?= htmlspecialchars($product['category']) ?>">
                        
                        <div style="display: flex; flex-direction: column; gap: 14px; margin-bottom: 18px;">
                            <div>
                                <label style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--fg-dark); display: block; margin-bottom: 6px;">Your Name <span style="color: #ef4444;">*</span></label>
                                <input type="text" name="full_name" class="figma-search-input" style="padding: 11px 14px; border-radius: 0;" placeholder="e.g. Dr. Ramesh Shah / Purchase Manager" required>
                            </div>
                            <div>
                                <label style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--fg-dark); display: block; margin-bottom: 6px;">Phone / WhatsApp <span style="color: #ef4444;">*</span></label>
                                <input type="tel" name="phone" class="figma-search-input" style="padding: 11px 14px; border-radius: 0;" placeholder="+91 91065 95469" required>
                            </div>
                            <div>
                                <label style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--fg-dark); display: block; margin-bottom: 6px;">Hospital / City</label>
                                <input type="text" name="hospital_name" class="figma-search-input" style="padding: 11px 14px; border-radius: 0;" placeholder="e.g. City Hospital, Ahmedabad">
                            </div>
                            <div>
                                <label style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--fg-dark); display: block; margin-bottom: 6px;">Requirement / Quantity</label>
                                <textarea name="message" class="figma-search-input" style="min-height: 80px; padding: 11px 14px; border-radius: 0; resize: vertical;" required>Please send quotation for <?= htmlspecialchars($product['name']) ?> (SKU: <?= htmlspecialchars($product['sku']) ?>). Required Quantity: 1 set.</textarea>
                            </div>
                        </div>

                        <button type="submit" class="figma-card-btn-quote" style="width: 100%; padding: 13px; font-size: 13px; border-radius: 0;">
                            <span>Submit Quote Request ↗</span>
                        </button>
                    </form>

                    <!-- WhatsApp Button -->
                    <div style="margin-top: 10px; text-align: center;">
                        <a href="https://wa.me/919106595469?text=<?= urlencode('Hello Heer Jani (Apex Surgical), I am looking for a quick price quote for ' . $product['name'] . ' (Model: ' . $product['sku'] . ')') ?>" target="_blank" rel="noopener noreferrer" class="figma-btn-wa-full" style="display: flex; align-items: center; justify-content: center; width: 100%; padding: 11px; font-size: 12.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; gap: 8px; border-radius: 0; background: #25D366; color: #ffffff; text-decoration: none;">
                            <span style="width: 22px; height: 22px; background: rgba(255,255,255,0.2); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2zm5.78 14.15c-.24.67-1.39 1.29-1.93 1.34-.51.05-1.17.07-3.79-.98-3.15-1.26-5.18-4.45-5.34-4.66-.16-.21-1.28-1.7-1.28-3.25 0-1.55.81-2.31 1.1-2.62.29-.31.64-.39.85-.39.21 0 .43 0 .61.01.2.01.46-.07.72.55.27.64.91 2.22.99 2.38.08.16.13.35.03.56-.11.21-.16.35-.32.53-.16.19-.34.42-.49.56-.16.16-.33.34-.14.66.19.32.84 1.38 1.8 2.24 1.24 1.1 2.28 1.45 2.6 1.61.32.16.51.13.7-.08.19-.21.8-.93 1.01-1.25.21-.32.43-.27.72-.16.29.11 1.86.88 2.18 1.04.32.16.53.24.61.37.08.13.08.77-.16 1.44z"/></svg>
                            </span>
                            <span>Chat on WhatsApp Now</span>
                        </a>
                    </div>
                </div>

                <!-- Technical Specs Table Card -->
                <div style="background: #ffffff; border: 1px solid var(--fg-border); border-radius: 0; padding: 28px; box-shadow: none;">
                    <h3 style="font-family: var(--fg-serif); font-size: 22px; margin-bottom: 16px; color: #0d110d;">Technical Specifications</h3>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php foreach ($product['specs'] as $label => $val): ?>
                            <div style="display: flex; justify-content: space-between; font-size: 13px; padding-bottom: 8px; border-bottom: 1px solid var(--fg-border-light);">
                                <span style="color: var(--fg-muted);"><?= htmlspecialchars($label) ?></span>
                                <strong style="color: #0d110d; text-align: right; max-width: 55%;"><?= htmlspecialchars($val) ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>

        </div>

    </section>

</div>

<?php require_once __DIR__ . '/includes/public-footer.php'; ?>
