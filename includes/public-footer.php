    <?php if (empty($hideFooter)): ?>
    <!-- FIGMA / ANTIGRAVITY MINIMALIST EDITORIAL FOOTER -->
    <footer class="figma-footer">
        <div class="figma-footer-inner">
            <!-- Top Row: Tagline + Multi-column Links -->
            <div class="figma-footer-top">
                <div class="figma-footer-tagline">
                    Experience precision
                </div>

                <div class="figma-footer-nav-cols">
                    <!-- Column 1: Products -->
                    <div class="figma-footer-col">
                        <div class="figma-footer-col-title">Product</div>
                        <ul class="figma-footer-links-list">
                            <li><a href="/surgical_equipment/products.php?cat=Surgical+Instruments">Surgical Instruments</a></li>
                            <li><a href="/surgical_equipment/products.php?cat=OT+Equipment">OT Equipment</a></li>
                            <li><a href="/surgical_equipment/products.php?cat=ICU+Equipment">ICU Systems</a></li>
                            <li><a href="/surgical_equipment/products.php?cat=Autoclave+%26+Sterilizers">Sterilization Suites</a></li>
                            <li><a href="/surgical_equipment/products.php?cat=Laparoscopy+Instruments">Laparoscopy Tools</a></li>
                            <li><a href="/surgical_equipment/products.php">All Products</a></li>
                        </ul>
                    </div>

                    <!-- Column 2: Resources & Company -->
                    <div class="figma-footer-col">
                        <div class="figma-footer-col-title">Resources</div>
                        <ul class="figma-footer-links-list">
                            <li><a href="/surgical_equipment/about.php">About Apex</a></li>
                            <li><a href="/surgical_equipment/services.php">Biomedical AMC</a></li>
                            <li><a href="/surgical_equipment/certifications.php">Quality & Compliance</a></li>
                            <li><a href="/surgical_equipment/brands.php">Authorized OEMs</a></li>
                            <li><a href="/surgical_equipment/contact.php">Contact Specialists</a></li>
                            <li><a href="/surgical_equipment/auth/login.php">Internal Portal</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Massive Display Wordmark -->
            <div class="figma-footer-brand-huge">
                Apex
            </div>

            <!-- Bottom Row: Sublinks and Copyright -->
            <div class="figma-footer-bottom">
                <div class="figma-footer-bottom-brand">
                    Apex Surgical
                </div>
                <div class="figma-footer-bottom-links">
                    <a href="/surgical_equipment/about.php">About Apex</a>
                    <a href="/surgical_equipment/products.php">Products</a>
                    <a href="/surgical_equipment/certifications.php">Quality & Certs</a>
                    <a href="/surgical_equipment/contact.php">Privacy & Terms</a>
                </div>
            </div>
        </div>
    </footer>
    <?php endif; ?>

    <!-- FLOATING WHATSAPP & CALL BUTTONS -->
    <div class="pub-floating-ctas">
        <!-- Direct Call Button -->
        <a href="tel:+919106595469" class="pub-float-btn pub-float-call" title="Call Now: +91 91065 95469" aria-label="Direct Phone Call">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
            <span class="pub-float-tooltip">Call Now</span>
        </a>

        <!-- WhatsApp Button -->
        <a href="https://wa.me/919106595469?text=Hello%20Heer%20Jani%20(Apex%20Surgical)%2C%20I%20am%20interested%20in%20requesting%20a%20quotation%20and%20product%20details." target="_blank" rel="noopener noreferrer" class="pub-float-btn pub-float-wa" title="Chat on WhatsApp" aria-label="WhatsApp Inquiry">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2zm5.78 14.15c-.24.67-1.39 1.29-1.93 1.34-.51.05-1.17.07-3.79-.98-3.15-1.26-5.18-4.45-5.34-4.66-.16-.21-1.28-1.7-1.28-3.25 0-1.55.81-2.31 1.1-2.62.29-.31.64-.39.85-.39.21 0 .43 0 .61.01.2.01.46-.07.72.55.27.64.91 2.22.99 2.38.08.16.13.35.03.56-.11.21-.16.35-.32.53-.16.19-.34.42-.49.56-.16.16-.33.34-.14.66.19.32.84 1.38 1.8 2.24 1.24 1.1 2.28 1.45 2.6 1.61.32.16.51.13.7-.08.19-.21.8-.93 1.01-1.25.21-.32.43-.27.72-.16.29.11 1.86.88 2.18 1.04.32.16.53.24.61.37.08.13.08.77-.16 1.44z"/>
            </svg>
            <span class="pub-float-tooltip">Chat on WhatsApp</span>
            <span class="pub-float-pulse"></span>
        </a>
    </div>

    <!-- Interactive Navigation Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Scroll transition for navbar -> small glass capsule
            const navbar = document.getElementById('figmaNavbarMain');
            if (navbar) {
                const handleScroll = () => {
                    if (window.scrollY > 40) {
                        navbar.classList.add('is-scrolled');
                    } else {
                        navbar.classList.remove('is-scrolled');
                    }
                };
                window.addEventListener('scroll', handleScroll, { passive: true });
                handleScroll(); // Initial check on page load
            }

            // Figma drawer toggle
            const figmaToggle = document.getElementById('figmaMobileToggle');
            const figmaDrawer = document.getElementById('figmaMobileDrawer');
            const figmaClose = document.getElementById('figmaDrawerClose');
            
            if (figmaToggle && figmaDrawer) {
                figmaToggle.addEventListener('click', function() {
                    figmaDrawer.classList.toggle('open');
                });
            }
            if (figmaClose && figmaDrawer) {
                figmaClose.addEventListener('click', function() {
                    figmaDrawer.classList.remove('open');
                });
            }

            // Legacy menu support
            const toggle = document.getElementById('pubMobileToggle');
            const menu = document.getElementById('pubNavMenu');
            if (toggle && menu) {
                toggle.addEventListener('click', function() {
                    menu.classList.toggle('active');
                    toggle.classList.toggle('open');
                });
            }
        });
    </script>
</body>
</html>
