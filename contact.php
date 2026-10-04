<?php
$pageTitle = "Contact Us | Apex Surgical & Medical Equipment";
$pageDescription = "Get in touch with Apex Surgical for inquiries, quotations, hospital supply, and equipment AMC support. Reach out via WhatsApp, phone, or online form.";
require_once __DIR__ . '/includes/public-header.php';

$formData = $_SESSION['form_data'] ?? [];
$flashSuccess = $_SESSION['pub_success'] ?? null;
$flashError = $_SESSION['pub_error'] ?? null;
unset($_SESSION['pub_success'], $_SESSION['pub_error']);
?>

<!-- PAGE BANNER -->
<section class="pub-page-header">
    <div class="pub-container pub-page-header-content">
        <div class="pub-breadcrumb">
            <a href="/surgical_equipment/index.php">Home</a>
            <span>/</span>
            <span>Contact Us</span>
        </div>
        <h1 class="pub-page-title">Contact & Technical Inquiry Desk</h1>
        <p class="pub-page-subtitle">
            Need pricing, equipment specifications, AMC contracts, or tender quotation? Connect directly with our biomedical specialists and sales executives.
        </p>
    </div>
</section>

<!-- MAIN CONTACT SECTION -->
<section class="pub-contact-section">
    <div class="pub-container">
        
        <div class="pub-contact-grid">
            
            <!-- LEFT COLUMN: CONTACT DETAILS & DIRECT TRIGGERS -->
            <div class="pub-contact-info-wrap">
                
                <!-- Card 1: Office & Showroom -->
                <div class="pub-info-card">
                    <div class="pub-info-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    </div>
                    <div class="pub-info-content">
                        <h3>Corporate Office & Experience Center</h3>
                        <p>Apex MedTech Plaza, 4th Floor, Ring Road, Healthcare Zone, Ahmedabad, Gujarat 380015, India.</p>
                        <span class="pub-info-highlight">Open: Mon – Sat (9:00 AM – 7:30 PM)</span>
                    </div>
                </div>

                <!-- Card 2: Sales & Quotations -->
                <div class="pub-info-card">
                    <div class="pub-info-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                    </div>
                    <div class="pub-info-content">
                        <h3>Sales & Tender Quotations</h3>
                        <p>Direct Phone: <a href="tel:+919876543210" class="pub-info-highlight">+91 98765 43210</a></p>
                        <p>Landline: <a href="tel:+917926501234">+91 79 2650 1234</a></p>
                        <p>Email: <a href="mailto:sales@apexsurgical.com">sales@apexsurgical.com</a></p>
                    </div>
                </div>

                <!-- Card 3: AMC & Technical Support -->
                <div class="pub-info-card">
                    <div class="pub-info-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                    </div>
                    <div class="pub-info-content">
                        <h3>Biomedical Engineering & AMC Support</h3>
                        <p>24/7 Breakdown Help: <a href="tel:+919876543211" class="pub-info-highlight">+91 98765 43211</a></p>
                        <p>Support Email: <a href="mailto:service@apexsurgical.com">service@apexsurgical.com</a></p>
                    </div>
                </div>

                <!-- Card 4: Quick Action Triggers (WhatsApp & Direct Call) -->
                <div class="pub-quick-triggers">
                    <a href="https://wa.me/919876543210?text=Hello%20Apex%20Surgical%20Team%2C%20I%20want%20to%20inquire%20about%20surgical%20equipment." target="_blank" rel="noopener noreferrer" class="pub-quick-btn pub-quick-wa">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2zm5.78 14.15c-.24.67-1.39 1.29-1.93 1.34-.51.05-1.17.07-3.79-.98-3.15-1.26-5.18-4.45-5.34-4.66-.16-.21-1.28-1.7-1.28-3.25 0-1.55.81-2.31 1.1-2.62.29-.31.64-.39.85-.39.21 0 .43 0 .61.01.2.01.46-.07.72.55.27.64.91 2.22.99 2.38.08.16.13.35.03.56-.11.21-.16.35-.32.53-.16.19-.34.42-.49.56-.16.16-.33.34-.14.66.19.32.84 1.38 1.8 2.24 1.24 1.1 2.28 1.45 2.6 1.61.32.16.51.13.7-.08.19-.21.8-.93 1.01-1.25.21-.32.43-.27.72-.16.29.11 1.86.88 2.18 1.04.32.16.53.24.61.37.08.13.08.77-.16 1.44z"/></svg>
                        <span>WhatsApp Us</span>
                    </a>
                    <a href="tel:+919876543210" class="pub-quick-btn pub-quick-call">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                        <span>Call Sales Now</span>
                    </a>
                </div>

            </div>

            <!-- RIGHT COLUMN: ENQUIRY & QUOTE FORM -->
            <div class="pub-form-card" id="contact-form">
                <div class="pub-form-header">
                    <span class="pub-form-tag">Fast Response Guaranteed</span>
                    <h2 class="pub-form-title">Request a Quote / Enquire Now</h2>
                    <p class="pub-form-subtitle">Fill out the form below and our medical equipment representative will get back to you with pricing, brochures, and availability.</p>
                </div>

                <?php if ($flashSuccess): ?>
                    <div class="pub-alert pub-alert-success">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        <span><?= $flashSuccess ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($flashError): ?>
                    <div class="pub-alert pub-alert-error">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        <span><?= $flashError ?></span>
                    </div>
                <?php endif; ?>

                <form action="/surgical_equipment/process-inquiry.php" method="POST">
                    <div class="pub-form-grid">
                        
                        <div class="pub-form-group">
                            <label class="pub-label" for="full_name">Your Name <span>*</span></label>
                            <input type="text" id="full_name" name="full_name" class="pub-input" placeholder="e.g. Dr. Rajesh Patel / Hospital Purchase Manager" value="<?= htmlspecialchars($formData['full_name'] ?? '') ?>" required>
                        </div>

                        <div class="pub-form-group">
                            <label class="pub-label" for="phone">Phone / WhatsApp Number <span>*</span></label>
                            <input type="tel" id="phone" name="phone" class="pub-input" placeholder="e.g. +91 98765 01234" value="<?= htmlspecialchars($formData['phone'] ?? '') ?>" required>
                        </div>

                        <div class="pub-form-group">
                            <label class="pub-label" for="email">Email Address <span>*</span></label>
                            <input type="email" id="email" name="email" class="pub-input" placeholder="e.g. contact@hospital.org" value="<?= htmlspecialchars($formData['email'] ?? '') ?>" required>
                        </div>

                        <div class="pub-form-group">
                            <label class="pub-label" for="hospital_name">Hospital / Clinic / Company</label>
                            <input type="text" id="hospital_name" name="hospital_name" class="pub-input" placeholder="e.g. Sterling Multi-Specialty Hospital" value="<?= htmlspecialchars($formData['hospital_name'] ?? '') ?>">
                        </div>

                        <div class="pub-form-group">
                            <label class="pub-label" for="inquiry_type">Inquiry Purpose</label>
                            <select id="inquiry_type" name="inquiry_type" class="pub-select">
                                <option value="Request a Price Quote">Request a Price Quote</option>
                                <option value="Product Availability & Technical Specs">Product Availability & Technical Specs</option>
                                <option value="Annual Maintenance Contract (AMC / CMC)">Annual Maintenance Contract (AMC / CMC)</option>
                                <option value="Equipment Repair & Sharpening">Equipment Repair & Sharpening</option>
                                <option value="Bulk Hospital Setup / Tender Supply">Bulk Hospital Setup / Tender Supply</option>
                                <option value="General Inquiry">General Inquiry</option>
                            </select>
                        </div>

                        <div class="pub-form-group">
                            <label class="pub-label" for="equipment_category">Equipment Category</label>
                            <select id="equipment_category" name="equipment_category" class="pub-select">
                                <option value="">-- Select Category (Optional) --</option>
                                <option value="Surgical Instruments">Surgical Instruments</option>
                                <option value="OT Equipment">OT Equipment</option>
                                <option value="Hospital Furniture">Hospital Furniture</option>
                                <option value="ICU Equipment">ICU Equipment</option>
                                <option value="Laparoscopy Instruments">Laparoscopy Instruments</option>
                                <option value="Orthopedic Instruments">Orthopedic Instruments</option>
                                <option value="Anaesthesia Equipment">Anaesthesia Equipment</option>
                                <option value="Autoclave & Sterilizers">Autoclave & Sterilizers</option>
                                <option value="Suction Machines">Suction Machines</option>
                                <option value="Surgical Disposables">Surgical Disposables</option>
                            </select>
                        </div>

                        <div class="pub-form-group pub-full-col">
                            <label class="pub-label" for="message">Requirement Details / Message <span>*</span></label>
                            <textarea id="message" name="message" class="pub-textarea" placeholder="Please specify product models, quantities, required delivery timeline, or any custom specifications..." required><?= htmlspecialchars($formData['message'] ?? '') ?></textarea>
                        </div>

                    </div>

                    <button type="submit" class="pub-btn pub-btn-primary pub-btn-block" style="padding:14px; font-size:15px;">
                        <span>Submit Quote Request</span>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                    </button>
                </form>
            </div>

        </div>

        <!-- GOOGLE MAP EMBED (100% FREE, NO API KEY REQUIRED) -->
        <div class="pub-map-wrapper">
            <div class="pub-map-header">
                <div>
                    <h3>Visit Our Healthcare Experience Center</h3>
                    <p class="text-muted" style="font-size:13.5px; margin-top:2px;">Ahmedabad, Gujarat, India (View on Google Maps)</p>
                </div>
                <a href="https://maps.google.com/?q=Ahmedabad+Healthcare+Zone" target="_blank" rel="noopener noreferrer" class="pub-btn pub-btn-outline" style="font-size:12.5px; padding:6px 14px;">
                    <span>Open in Google Maps App</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                </a>
            </div>
            <div class="pub-map-frame-container">
                <iframe 
                    title="Apex Surgical Location Map"
                    src="https://maps.google.com/maps?q=Ahmedabad,Gujarat,India&t=&z=13&ie=UTF8&iwloc=&output=embed" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/includes/public-footer.php'; ?>
