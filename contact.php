<?php
$pageTitle = "Contact Us | Apex Surgical & Medical Equipment";
$pageDescription = "Connect directly with Apex Surgical for hospital equipment quotations, biomedical engineering support, and AMC inquiries in Gujarat, India.";
require_once __DIR__ . '/includes/public-header.php';

$formData = $_SESSION['form_data'] ?? [];
$flashSuccess = $_SESSION['pub_success'] ?? null;
$flashError = $_SESSION['pub_error'] ?? null;
unset($_SESSION['pub_success'], $_SESSION['pub_error']);

// Dynamic Context Pre-fills
$reqProduct = trim($_GET['product'] ?? '');
$reqSku     = trim($_GET['sku'] ?? '');
$reqService = trim($_GET['service'] ?? '');
$reqDoc     = trim($_GET['doc'] ?? '');
$reqBrand   = trim($_GET['brand'] ?? '');
$reqCat     = trim($_GET['cat'] ?? '');

$defaultInquiryType = $formData['inquiry_type'] ?? 'Request a Price Quote';
$defaultCategory    = $formData['equipment_category'] ?? '';
$defaultMessage     = $formData['message'] ?? '';
$contextBadge       = '';

if (empty($formData['message'])) {
    if (!empty($reqProduct)) {
        $defaultInquiryType = 'Request a Price Quote';
        $defaultMessage = "Please send official price quotation and delivery timeline for:\n• Product: " . $reqProduct . (!empty($reqSku) ? " (SKU: " . $reqSku . ")" : "") . "\n• Required Quantity: 1 set / batch\n• Hospital / Delivery Location: ";
        $contextBadge = "Inquiry for: " . $reqProduct;
    } elseif (!empty($reqService)) {
        $defaultInquiryType = ($reqService === 'AMC') ? 'Annual Maintenance Contract (AMC / CMC)' : (($reqService === 'Sharpening') ? 'Equipment Repair & Sharpening' : 'General Inquiry');
        $defaultMessage = "I would like to request technical details and a proposal for " . $reqService . " services.\n• Facility Type / Number of Beds: \n• Specific Equipment Models: ";
        $contextBadge = "Service Inquiry: " . $reqService;
    } elseif (!empty($reqDoc)) {
        $defaultInquiryType = 'Product Availability & Technical Specs';
        $defaultMessage = "Please furnish verified compliance documentation and master test certificates for " . $reqDoc . ".\n• Organization / Tender Authority: ";
        $contextBadge = "Compliance Docs: " . $reqDoc;
    } elseif (!empty($reqBrand)) {
        $defaultInquiryType = 'Request a Price Quote';
        $defaultMessage = "Please provide catalog specifications and official institution pricing for " . $reqBrand . " medical systems.\n• Required Quantities / Timeline: ";
        $contextBadge = "OEM Brand: " . $reqBrand;
    } elseif (!empty($reqCat)) {
        $defaultCategory = $reqCat;
        $defaultMessage = "Please provide product catalog and quotation for " . $reqCat . ".";
        $contextBadge = "Category: " . $reqCat;
    }
}
?>

<div class="figma-page-wrap">

    <!-- 1. EDITORIAL HERO SECTION -->
    <section class="figma-products-hero" style="padding-bottom: 20px;">
        <h1 class="figma-products-hero-title">Connect with our clinical desk.</h1>
        <p class="figma-products-hero-subtitle">
            Direct technical consultation, hospital setup proposals, annual maintenance inquiries, and immediate quote requests.
        </p>
    </section>

    <!-- 3. MAIN CONTACT & INQUIRY SPLIT SECTION -->
    <section style="width: 100%; max-width: 1380px; margin: 0 auto 70px auto; padding: 0 24px;">
        <div style="display: grid; grid-template-columns: 1fr 1.3fr; gap: 32px; align-items: start;">
            
            <!-- LEFT COLUMN: DIRECT CONTACT CHANNELS -->
            <div style="display: flex; flex-direction: column; gap: 20px;">
                
                <!-- Office Card -->
                <div class="figma-product-card" style="padding: 28px;">
                    <div class="figma-card-top-meta">
                        <span class="figma-category-badge" style="background: var(--fg-lime-tint); color: var(--fg-olive);">Headquarters</span>
                        <span class="figma-sku-badge">EST. 2012</span>
                    </div>
                    <h3 style="font-family: var(--fg-serif); font-size: 22px; color: #0d110d; margin: 0 0 10px 0;">
                        Corporate Office & Experience Center
                    </h3>
                    <p style="font-size: 14px; color: var(--fg-muted); line-height: 1.6; margin: 0 0 12px 0;">
                        Near post office, Khodu Road, Dudhrej, Surendranagar, Gujarat 363040, India.
                    </p>
                    <div style="font-size: 12px; font-weight: 700; color: var(--fg-olive); text-transform: uppercase; letter-spacing: 0.04em;">
                        Working Hours: Mon – Sat (9:00 AM – 7:30 PM IST)
                    </div>
                </div>

                <!-- Contact Person 1: Heer Jani -->
                <div class="figma-product-card" style="padding: 24px;">
                    <div class="figma-card-top-meta">
                        <span class="figma-category-badge">Institutional Relations</span>
                        <span class="figma-sku-badge">DIRECT DESK</span>
                    </div>
                    <h3 style="font-family: var(--fg-serif); font-size: 22px; color: #0d110d; margin: 0 0 2px 0;">
                        Heer Jani
                    </h3>
                    <div style="font-size: 13px; font-weight: 600; color: var(--fg-olive); margin-bottom: 12px;">
                        Sales &amp; Hospital Relations Desk
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 6px; font-size: 13.5px; color: #0d110d; margin-bottom: 18px;">
                        <div>
                            <span style="color: var(--fg-muted); font-size: 12px; display: block;">Phone / WhatsApp:</span>
                            <a href="tel:+919106595469" style="font-weight: 700; color: var(--fg-olive); text-decoration: none;">+91 91065 95469</a>
                        </div>
                        <div>
                            <span style="color: var(--fg-muted); font-size: 12px; display: block;">Email:</span>
                            <a href="mailto:heerujani0501@gmail.com" style="font-weight: 600; color: #0d110d; text-decoration: none;">heerujani0501@gmail.com</a>
                        </div>
                    </div>

                    <div class="figma-card-actions">
                        <a href="tel:+919106595469" class="figma-card-btn-quote" style="text-align: center; font-size: 12px; padding: 10px 14px;">
                            <span>Call Heer Jani ↗</span>
                        </a>
                        <a href="https://wa.me/919106595469?text=Hello%20Heer%20Jani%20(Apex%20Surgical)%2C%20I%20would%20like%20to%20request%20a%20quotation." target="_blank" rel="noopener noreferrer" class="figma-card-btn-wa" title="WhatsApp Heer Jani">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2zm5.78 14.15c-.24.67-1.39 1.29-1.93 1.34-.51.05-1.17.07-3.79-.98-3.15-1.26-5.18-4.45-5.34-4.66-.16-.21-1.28-1.7-1.28-3.25 0-1.55.81-2.31 1.1-2.62.29-.31.64-.39.85-.39.21 0 .43 0 .61.01.2.01.46-.07.72.55.27.64.91 2.22.99 2.38.08.16.13.35.03.56-.11.21-.16.35-.32.53-.16.19-.34.42-.49.56-.16.16-.33.34-.14.66.19.32.84 1.38 1.8 2.24 1.24 1.1 2.28 1.45 2.6 1.61.32.16.51.13.7-.08.19-.21.8-.93 1.01-1.25.21-.32.43-.27.72-.16.29.11 1.86.88 2.18 1.04.32.16.53.24.61.37.08.13.08.77-.16 1.44z"/></svg>
                        </a>
                    </div>
                </div>

                <!-- Contact Person 2: Sweta Zala -->
                <div class="figma-product-card" style="padding: 24px;">
                    <div class="figma-card-top-meta">
                        <span class="figma-category-badge">Quotations &amp; Tenders</span>
                        <span class="figma-sku-badge">DIRECT DESK</span>
                    </div>
                    <h3 style="font-family: var(--fg-serif); font-size: 22px; color: #0d110d; margin: 0 0 2px 0;">
                        Sweta Zala
                    </h3>
                    <div style="font-size: 13px; font-weight: 600; color: var(--fg-olive); margin-bottom: 12px;">
                        Commercial &amp; Quotations Lead
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 6px; font-size: 13.5px; color: #0d110d; margin-bottom: 18px;">
                        <div>
                            <span style="color: var(--fg-muted); font-size: 12px; display: block;">Phone / WhatsApp:</span>
                            <a href="tel:+917863884678" style="font-weight: 700; color: var(--fg-olive); text-decoration: none;">+91 78638 84678</a>
                        </div>
                        <div>
                            <span style="color: var(--fg-muted); font-size: 12px; display: block;">Email:</span>
                            <a href="mailto:swetazala05@gmail.com" style="font-weight: 600; color: #0d110d; text-decoration: none;">swetazala05@gmail.com</a>
                        </div>
                    </div>

                    <div class="figma-card-actions">
                        <a href="tel:+917863884678" class="figma-card-btn-quote" style="text-align: center; font-size: 12px; padding: 10px 14px;">
                            <span>Call Sweta Zala ↗</span>
                        </a>
                        <a href="https://wa.me/917863884678?text=Hello%20Sweta%20Zala%20(Apex%20Surgical)%2C%20I%20would%20like%20to%20request%20a%20quotation." target="_blank" rel="noopener noreferrer" class="figma-card-btn-wa" title="WhatsApp Sweta Zala">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2zm5.78 14.15c-.24.67-1.39 1.29-1.93 1.34-.51.05-1.17.07-3.79-.98-3.15-1.26-5.18-4.45-5.34-4.66-.16-.21-1.28-1.7-1.28-3.25 0-1.55.81-2.31 1.1-2.62.29-.31.64-.39.85-.39.21 0 .43 0 .61.01.2.01.46-.07.72.55.27.64.91 2.22.99 2.38.08.16.13.35.03.56-.11.21-.16.35-.32.53-.16.19-.34.42-.49.56-.16.16-.33.34-.14.66.19.32.84 1.38 1.8 2.24 1.24 1.1 2.28 1.45 2.6 1.61.32.16.51.13.7-.08.19-.21.8-.93 1.01-1.25.21-.32.43-.27.72-.16.29.11 1.86.88 2.18 1.04.32.16.53.24.61.37.08.13.08.77-.16 1.44z"/></svg>
                        </a>
                    </div>
                </div>

                <!-- Biomedical Support Card -->
                <div class="figma-product-card" style="padding: 24px;">
                    <div class="figma-card-top-meta">
                        <span class="figma-category-badge" style="background: var(--fg-lime-tint); color: var(--fg-olive);">Biomedical Engineering</span>
                        <span class="figma-sku-badge">24/7 SUPPORT</span>
                    </div>
                    <h3 style="font-family: var(--fg-serif); font-size: 22px; color: #0d110d; margin: 0 0 8px 0;">
                        Technical AMC &amp; Emergency Service
                    </h3>
                    <p style="font-size: 13.5px; color: var(--fg-muted); line-height: 1.5; margin: 0 0 16px 0;">
                        Immediate on-site biomedical repair, preventive maintenance, calibration validation, and surgical scissor sharpening.
                    </p>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <a href="tel:+919106595469" class="figma-card-btn-specs" style="text-align: center; font-weight: 700; font-size: 12.5px; padding: 10px 14px;">
                            <span>Helpline 1: +91 91065 95469</span>
                        </a>
                        <a href="tel:+917863884678" class="figma-card-btn-specs" style="text-align: center; font-weight: 700; font-size: 12.5px; padding: 10px 14px;">
                            <span>Helpline 2: +91 78638 84678</span>
                        </a>
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN: INQUIRY FORM & EXPERIENCE CENTER MAP -->
            <div style="display: flex; flex-direction: column; gap: 24px;">
                
                <div id="contact-form" style="background: #ffffff; border: 1px solid var(--fg-border); padding: 36px;">
                
                    <div style="margin-bottom: 20px; border-bottom: 1px solid var(--fg-border-light); padding-bottom: 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 8px;">
                            <span class="figma-category-badge" style="display: inline-block;">Commercial &amp; Technical Inquiry</span>
                            <?php if (!empty($contextBadge)): ?>
                                <span style="background: var(--fg-lime-tint); color: var(--fg-olive); font-size: 11.5px; font-weight: 700; padding: 3px 10px; border: 1px solid var(--fg-sage); display: inline-flex; align-items: center; gap: 6px;">
                                    <span><?= htmlspecialchars($contextBadge) ?></span>
                                    <a href="/surgical_equipment/contact.php#contact-form" style="color: var(--fg-olive); text-decoration: none; font-size: 13px;" title="Clear Context">✕</a>
                                </span>
                            <?php endif; ?>
                        </div>
                        <h2 style="font-family: var(--fg-serif); font-size: 28px; color: #0d110d; margin: 0 0 6px 0;">Request a Formal Quote</h2>
                        <p style="font-size: 13.5px; color: var(--fg-muted); margin: 0; line-height: 1.5;">
                            Complete the requirements below. Our biomedical sales engineers provide itemized proforma invoices with full compliance datasheets.
                        </p>
                    </div>

                    <?php if ($flashSuccess): ?>
                        <div style="background: rgba(62, 83, 27, 0.08); border-left: 3px solid var(--fg-olive); padding: 14px 18px; margin-bottom: 20px; font-size: 14px; color: var(--fg-olive); font-weight: 600;">
                            <?= htmlspecialchars($flashSuccess) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($flashError): ?>
                        <div style="background: rgba(220, 38, 38, 0.08); border-left: 3px solid #dc2626; padding: 14px 18px; margin-bottom: 20px; font-size: 14px; color: #dc2626; font-weight: 600;">
                            <?= htmlspecialchars($flashError) ?>
                        </div>
                    <?php endif; ?>

                    <form id="quoteDynamicForm" action="/surgical_equipment/process-inquiry.php" method="POST">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                            
                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                <label style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #0d110d;" for="full_name">
                                    Your Full Name <span style="color: #dc2626;">*</span>
                                </label>
                                <input type="text" id="full_name" name="full_name" required placeholder="e.g. Dr. Rajesh Patel" value="<?= htmlspecialchars($formData['full_name'] ?? '') ?>" style="width: 100%; border: 1px solid var(--fg-border); padding: 11px 14px; font-family: var(--fg-sans); font-size: 14px; background: #ffffff; border-radius: 0px; outline: none;">
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                <label style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #0d110d;" for="phone">
                                    Phone / WhatsApp <span style="color: #dc2626;">*</span>
                                </label>
                                <input type="tel" id="phone" name="phone" required placeholder="e.g. +91 98765 01234" value="<?= htmlspecialchars($formData['phone'] ?? '') ?>" style="width: 100%; border: 1px solid var(--fg-border); padding: 11px 14px; font-family: var(--fg-sans); font-size: 14px; background: #ffffff; border-radius: 0px; outline: none;">
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                <label style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #0d110d;" for="email">
                                    Email Address <span style="color: #dc2626;">*</span>
                                </label>
                                <input type="email" id="email" name="email" required placeholder="e.g. purchase@hospital.org" value="<?= htmlspecialchars($formData['email'] ?? '') ?>" style="width: 100%; border: 1px solid var(--fg-border); padding: 11px 14px; font-family: var(--fg-sans); font-size: 14px; background: #ffffff; border-radius: 0px; outline: none;">
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                <label style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #0d110d;" for="hospital_name">
                                    Hospital / Clinic / Lab
                                </label>
                                <input type="text" id="hospital_name" name="hospital_name" placeholder="e.g. Sterling Multi-Specialty Hospital" value="<?= htmlspecialchars($formData['hospital_name'] ?? '') ?>" style="width: 100%; border: 1px solid var(--fg-border); padding: 11px 14px; font-family: var(--fg-sans); font-size: 14px; background: #ffffff; border-radius: 0px; outline: none;">
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                <label style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #0d110d;" for="inquiry_type">
                                    Inquiry Purpose
                                </label>
                                <select id="inquiry_type" name="inquiry_type" style="width: 100%; border: 1px solid var(--fg-border); padding: 11px 14px; font-family: var(--fg-sans); font-size: 14px; background: #ffffff; border-radius: 0px; outline: none;">
                                    <option value="Request a Price Quote" <?= $defaultInquiryType === 'Request a Price Quote' ? 'selected' : '' ?>>Request a Price Quote</option>
                                    <option value="Product Availability & Technical Specs" <?= $defaultInquiryType === 'Product Availability & Technical Specs' ? 'selected' : '' ?>>Product Availability & Technical Specs</option>
                                    <option value="Annual Maintenance Contract (AMC / CMC)" <?= $defaultInquiryType === 'Annual Maintenance Contract (AMC / CMC)' ? 'selected' : '' ?>>Annual Maintenance Contract (AMC / CMC)</option>
                                    <option value="Equipment Repair & Sharpening" <?= $defaultInquiryType === 'Equipment Repair & Sharpening' ? 'selected' : '' ?>>Equipment Repair & Sharpening</option>
                                    <option value="Bulk Hospital Setup / Tender Supply" <?= $defaultInquiryType === 'Bulk Hospital Setup / Tender Supply' ? 'selected' : '' ?>>Bulk Hospital Setup / Tender Supply</option>
                                    <option value="General Inquiry" <?= $defaultInquiryType === 'General Inquiry' ? 'selected' : '' ?>>General Inquiry</option>
                                </select>
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                <label style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #0d110d;" for="equipment_category">
                                    Equipment Category
                                </label>
                                <select id="equipment_category" name="equipment_category" style="width: 100%; border: 1px solid var(--fg-border); padding: 11px 14px; font-family: var(--fg-sans); font-size: 14px; background: #ffffff; border-radius: 0px; outline: none;">
                                    <option value="">-- Select Category (Optional) --</option>
                                    <option value="Surgical Instruments" <?= $defaultCategory === 'Surgical Instruments' ? 'selected' : '' ?>>Surgical Instruments</option>
                                    <option value="OT Equipment" <?= $defaultCategory === 'OT Equipment' ? 'selected' : '' ?>>OT Equipment</option>
                                    <option value="Hospital Furniture" <?= $defaultCategory === 'Hospital Furniture' ? 'selected' : '' ?>>Hospital Furniture</option>
                                    <option value="ICU Equipment" <?= $defaultCategory === 'ICU Equipment' ? 'selected' : '' ?>>ICU Equipment</option>
                                    <option value="Laparoscopy Instruments" <?= $defaultCategory === 'Laparoscopy Instruments' ? 'selected' : '' ?>>Laparoscopy Instruments</option>
                                    <option value="Orthopedic Instruments" <?= $defaultCategory === 'Orthopedic Instruments' ? 'selected' : '' ?>>Orthopedic Instruments</option>
                                    <option value="Anaesthesia Equipment" <?= $defaultCategory === 'Anaesthesia Equipment' ? 'selected' : '' ?>>Anaesthesia Equipment</option>
                                    <option value="Autoclave & Sterilizers" <?= $defaultCategory === 'Autoclave & Sterilizers' ? 'selected' : '' ?>>Autoclave & Sterilizers</option>
                                    <option value="Suction Machines" <?= $defaultCategory === 'Suction Machines' ? 'selected' : '' ?>>Suction Machines</option>
                                    <option value="Surgical Disposables" <?= $defaultCategory === 'Surgical Disposables' ? 'selected' : '' ?>>Surgical Disposables</option>
                                </select>
                            </div>

                            <!-- Quick Requirement Tag Pills -->
                            <div style="grid-column: 1 / -1;">
                                <span style="font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--fg-muted); display: block; margin-bottom: 6px;">
                                    Quick Specifications (Click to Add):
                                </span>
                                <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                    <button type="button" class="quick-spec-pill" data-append="• Requirement: Delivery timeline needed in < 48 hours.">Urgent (&lt; 48 hrs)</button>
                                    <button type="button" class="quick-spec-pill" data-append="• Requirement: Please attach CE 0123 & CDSCO certificates.">CE &amp; CDSCO Docs</button>
                                    <button type="button" class="quick-spec-pill" data-append="• Requirement: Requesting on-site demonstration at hospital.">On-site Demo</button>
                                    <button type="button" class="quick-spec-pill" data-append="• Requirement: Inquiring about bulk institution discount tier.">Bulk Tier Discount</button>
                                    <button type="button" class="quick-spec-pill" data-append="• Requirement: Please provide itemized GST Proforma Invoice.">Proforma Invoice</button>
                                    <button type="button" class="quick-spec-pill" data-append="• Requirement: Include 1-Year Comprehensive AMC option.">Include AMC Option</button>
                                </div>
                            </div>

                            <div style="grid-column: 1 / -1; display: flex; flex-direction: column; gap: 6px;">
                                <label style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #0d110d;" for="message">
                                    Requirement Details / Message <span style="color: #dc2626;">*</span>
                                </label>
                                <textarea id="message" name="message" required rows="4" placeholder="Specify product models, instrument quantities, required delivery timeline, or hospital tender specs..." style="width: 100%; border: 1px solid var(--fg-border); padding: 12px 14px; font-family: var(--fg-sans); font-size: 14px; background: #ffffff; border-radius: 0px; outline: none; resize: vertical; line-height: 1.5;"><?= htmlspecialchars($defaultMessage) ?></textarea>
                            </div>

                        </div>

                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <button type="submit" class="figma-card-btn-quote" style="width: 100%; padding: 14px 24px; font-size: 13px; font-weight: 700; letter-spacing: 0.04em; text-align: center; border: none; cursor: pointer;">
                                <span>Submit Formal Quotation Request ↗</span>
                            </button>
                            <button type="button" id="btnInstantWhatsApp" class="figma-card-btn-specs" style="width: 100%; padding: 12px 24px; font-size: 12.5px; font-weight: 700; text-align: center; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                <span>Send Direct to Heer Jani on WhatsApp ↗</span>
                            </button>
                        </div>
                    </form>

                </div>

                <!-- GOOGLE MAP EXPERIENCE CENTER (SQUARED CONTAINER) -->
                <div style="background: #ffffff; border: 1px solid var(--fg-border); padding: 32px;">
                    <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 14px;">
                        <div>
                            <span class="figma-category-badge" style="margin-bottom: 6px; display: inline-block;">Physical Showroom</span>
                            <h3 style="font-family: var(--fg-serif); font-size: 24px; color: #0d110d; margin: 0;">Visit Our Healthcare Experience Center</h3>
                            <p style="font-size: 13.5px; color: var(--fg-muted); margin: 4px 0 0 0;">
                                Near post office, Khodu Road, Dudhrej, Gujarat 363040, India
                            </p>
                        </div>
                        <a href="https://maps.google.com/?q=Near+post+office+khodu+road+Dudhrej+363040" target="_blank" rel="noopener noreferrer" class="figma-card-btn-specs" style="padding: 8px 16px; font-size: 12.5px; font-weight: 700;">
                            <span>Open in Google Maps ↗</span>
                        </a>
                    </div>
                    
                    <div style="width: 100%; height: 320px; border: 1px solid var(--fg-border-light); overflow: hidden;">
                        <iframe 
                            title="Apex Surgical Location Map"
                            src="https://maps.google.com/maps?q=Near+post+office+khodu+road+Dudhrej+363040&t=&z=14&ie=UTF8&iwloc=&output=embed" 
                            width="100%"
                            height="100%"
                            style="border: 0;"
                            allowfullscreen="" 
                            loading="lazy" 
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- 5. CENTERED BORDERLESS CTA SECTION -->
    <section style="width: 100%; max-width: 1100px; margin: 0 auto 100px auto; padding: 40px 24px; text-align: center;">
        <div style="margin-bottom: 14px;">
            <span class="figma-category-badge" style="display: inline-block;">Direct Hotline</span>
        </div>
        
        <h2 style="font-family: var(--fg-serif); font-size: clamp(38px, 5.2vw, 68px); font-weight: 400; line-height: 1.05; letter-spacing: -0.025em; color: #0d110d; margin: 0 auto 16px auto; max-width: 900px;">
            Need Immediate On-Site Biomedical Assistance?
        </h2>
        
        <p style="color: var(--fg-muted); font-size: 16px; line-height: 1.6; max-width: 620px; margin: 0 auto 36px auto;">
            Our biomedical engineers and sales directors are standing by to assist with emergency OT equipment support, tenders, and bulk orders.
        </p>
        
        <div style="display: flex; justify-content: center; align-items: center; gap: 14px; flex-wrap: wrap;">
            <a href="tel:+919106595469" class="figma-card-btn-quote" style="flex: initial; font-size: 13px; font-weight: 700; padding: 13px 24px;">
                <span>Call Heer Jani: +91 91065 95469</span>
            </a>
            <a href="tel:+917863884678" class="figma-card-btn-quote" style="flex: initial; font-size: 13px; font-weight: 700; padding: 13px 24px;">
                <span>Call Sweta Zala: +91 78638 84678</span>
            </a>
            <a href="https://wa.me/919106595469?text=Hello%20Apex%20Surgical%20Team%2C%20I%20need%20immediate%20assistance." target="_blank" rel="noopener noreferrer" class="figma-card-btn-specs" style="font-size: 13px; font-weight: 700; padding: 13px 24px;">
                <span>WhatsApp Instant Desk</span>
            </a>
        </div>
    </section>

    <!-- 6. THANK YOU SIGN-OFF BY APEX -->
    <section style="width: 100%; max-width: 1100px; margin: 0 auto 80px auto; padding: 48px 24px; text-align: center; border-top: 1px solid rgba(0, 0, 0, 0.08);">
        <div style="margin-bottom: 12px;">
            <span class="figma-category-badge" style="background: var(--fg-lime-tint); color: var(--fg-olive); display: inline-block;">Message from Apex</span>
        </div>
        <h2 style="font-family: var(--fg-serif); font-size: clamp(32px, 4vw, 48px); font-weight: 400; color: #0d110d; margin: 0 auto 14px auto; line-height: 1.15;">
            Thank you for choosing Apex Surgical.
        </h2>
        <p style="color: var(--fg-muted); font-size: 15px; line-height: 1.7; max-width: 680px; margin: 0 auto 20px auto;">
            We are honored to partner with healthcare professionals, surgical centers, and healthcare institutions. Every instrument we deliver and every system we service is backed by our unwavering commitment to precision, patient safety, and clinical excellence.
        </p>
        <div style="font-family: var(--fg-serif); font-size: 20px; font-style: italic; color: var(--fg-olive); margin-top: 10px;">
            — The Team at Apex Surgical &amp; Medical Equipment
        </div>
        <div style="font-size: 12px; color: var(--fg-muted); margin-top: 8px; letter-spacing: 0.04em; text-transform: uppercase;">
            Dudhrej, Gujarat • Serving Healthcare Nationwide
        </div>
    </section>

    <style>
    .quick-spec-pill {
        background: #f4f4f0;
        border: 1px solid var(--fg-border);
        color: #0d110d;
        font-size: 11.5px;
        font-weight: 600;
        padding: 6px 12px;
        border-radius: 0px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        font-family: var(--fg-sans);
        user-select: none;
    }
    .quick-spec-pill:hover {
        background: var(--fg-lime-tint);
        border-color: var(--fg-olive);
        color: var(--fg-olive);
    }
    .quick-spec-pill.active {
        background: var(--fg-olive) !important;
        border-color: var(--fg-olive) !important;
        color: #ffffff !important;
    }
    .quick-spec-pill:active {
        transform: scale(0.97);
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const msgTextarea = document.getElementById('message');
        const pills = document.querySelectorAll('.quick-spec-pill');
        const btnInstantWhatsApp = document.getElementById('btnInstantWhatsApp');
        let currentSpecText = '';

        // Clean single-select and toggle for quick spec pills
        pills.forEach(pill => {
            pill.addEventListener('click', function() {
                const newSpec = this.getAttribute('data-append');
                
                if (this.classList.contains('active')) {
                    // Deselect and remove previous text
                    this.classList.remove('active');
                    if (currentSpecText && msgTextarea) {
                        msgTextarea.value = msgTextarea.value
                            .split('\n')
                            .filter(line => line.trim() !== currentSpecText.trim())
                            .join('\n')
                            .trim();
                    }
                    currentSpecText = '';
                } else {
                    // Remove previous active spec line if exists
                    if (currentSpecText && msgTextarea) {
                        msgTextarea.value = msgTextarea.value
                            .split('\n')
                            .filter(line => line.trim() !== currentSpecText.trim())
                            .join('\n')
                            .trim();
                    }
                    
                    pills.forEach(p => p.classList.remove('active'));
                    this.classList.add('active');
                    currentSpecText = newSpec;
                    
                    if (msgTextarea) {
                        const currentVal = msgTextarea.value.trim();
                        if (currentVal.length > 0) {
                            msgTextarea.value = currentVal + '\n' + currentSpecText;
                        } else {
                            msgTextarea.value = currentSpecText;
                        }
                    }
                }
                if (msgTextarea) msgTextarea.focus();
            });
        });

        // Instant WhatsApp compilation
        if (btnInstantWhatsApp) {
            btnInstantWhatsApp.addEventListener('click', function() {
                const name = document.getElementById('full_name')?.value || 'Valued Customer';
                const phone = document.getElementById('phone')?.value || '';
                const hospital = document.getElementById('hospital_name')?.value || '';
                const purpose = document.getElementById('inquiry_type')?.value || 'Quotation Request';
                const cat = document.getElementById('equipment_category')?.value || '';
                const msg = document.getElementById('message')?.value || '';

                let waText = `Hello Heer Jani (Apex Surgical), I am requesting a formal quotation:%0A%0A`;
                waText += `*Name:* ${encodeURIComponent(name)}%0A`;
                if (phone) waText += `*Phone:* ${encodeURIComponent(phone)}%0A`;
                if (hospital) waText += `*Hospital/Org:* ${encodeURIComponent(hospital)}%0A`;
                waText += `*Purpose:* ${encodeURIComponent(purpose)}%0A`;
                if (cat) waText += `*Category:* ${encodeURIComponent(cat)}%0A`;
                if (msg) waText += `*Details:*%0A${encodeURIComponent(msg)}%0A`;

                window.open(`https://wa.me/919106595469?text=${waText}`, '_blank');
            });
        }
    });
    </script>

</div>

<?php 
$hideFooter = true;
require_once __DIR__ . '/includes/public-footer.php'; 
?>
