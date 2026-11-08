    <style>
    :root {
        --spacing-small: 8px;
        --spacing-medium: 16px;
        --spacing-large: 24px;
        --color-primary-bg: #2563eb;
        --color-primary-text: #ffffff;
        --color-primary-dark: #1d4ed8;
        --color-normal-bg: #ffffff;
        --color-normal-text: #1f2937;
        --color-gray-light: #f8fafc;
        --color-gray-border: #e2e8f0;
        --radius-medium: 10px;
        --radius-large: 16px;
        --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
        --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.12);
        --transition-fast: 0.2s ease;
        --transition-smooth: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Widget Container - Right aligned */
    .accessibility-widget {
        position: fixed;
        bottom: 200px;
        right: 0px;
        z-index: 1000;
    }

    /* Floating Button */
    .accessibility-widget .accessibility-float {
        width: 44px;
        height: 44px;
        background: #12ABA9;
        color: var(--color-primary-text);
        text-align: center;
        cursor: pointer;
        border-radius: 50%;
        justify-content: center;
        align-items: center;
        display: flex;
        box-shadow: var(--shadow-md);
        transition: all var(--transition-smooth);
        position: relative;
    }

    .accessibility-widget .accessibility-float:hover {
        transform: scale(1.08);
        box-shadow: var(--shadow-lg);
    }

    .accessibility-widget .accessibility-float:active {
        transform: scale(0.96);
    }

    /* Card Container - Opens to the LEFT side */
    .accessibility-widget .accessibility-card {
        background: var(--color-normal-bg);
        padding: 0;
        z-index: 10001;
        border-radius: var(--radius-large);
        width: 280px;
        max-width: calc(100vw - 40px);
        display: none;
        position: absolute;
        bottom: 50%;
        right: 60px;
        transform: translateY(50%) scale(0.95);
        margin-bottom: 0;
        box-shadow: var(--shadow-lg);
        overflow: hidden;
        opacity: 0;
        transition: opacity var(--transition-smooth), transform var(--transition-smooth);
        pointer-events: none;
        transform-origin: right center;
    }

    .accessibility-widget .accessibility-card-open {
        display: block;
        opacity: 1;
        transform: translateY(50%) scale(1);
        pointer-events: all;
    }


    /* Mobile responsive */
    @media (max-width: 640px) {
        .accessibility-widget {
            bottom: 350px;
            right: 16px;
            margin-right: -14px;
            width: 26px !important;
            height: 26px !important;
        }
        .accessibility-float{width: 26px !important;
            height: 26px !important;}
        .mobile-icon{font-size: 13px !important;}
        
        .accessibility-widget .accessibility-card {
            position: fixed;
            bottom: auto;
            top: 50%;
            left: 50%;
            right: auto;
            transform: translate(-50%, -50%) scale(0.96);
            width: 90%;
            max-width: 300px;
        }
        
        .accessibility-widget .accessibility-card-open {
            transform: translate(-50%, -50%) scale(1);
        }
    }

    @media (min-width: 641px) {
        .accessibility-widget .accessibility-card {
            right: 60px;
            margin-right: -14px;
        }
       
        
        @media (max-width: 768px) {
            .accessibility-widget .accessibility-card {
                right: 56px;
                margin-right: -14px;
                
            }
        }
    }

    /* Card Header */
    .accessibility-card-header {
        background: #12ABA9;
        color: white;
        padding: 12px 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .accessibility-card-header h3 {
        margin: 0;
        font-size: 15px;
        font-weight: 600;
    }

    #accessibility-close {
        cursor: pointer;
        font-size: 18px;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background var(--transition-fast);
    }

    #accessibility-close:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    .ph-x-circle::before {
        content: "✕";
        font-size: 14px;
    }

    /* Language Switcher */
    .language-switcher {
        display: flex;
        gap: 6px;
        padding: 8px 12px;
        background: var(--color-gray-light);
        border-bottom: 1px solid var(--color-gray-border);
    }

    .lang-btn {
        flex: 1;
        padding: 5px 8px;
        border: none;
        border-radius: 20px;
        cursor: pointer;
        font-weight: 500;
        font-size: 12px;
        transition: all var(--transition-fast);
        background: white;
        color: var(--color-normal-text);
        border: 1px solid var(--color-gray-border);
    }

    .lang-btn.active {
        background: #12ABA9;
        color: white;
        border-color: #28A4A2;
    }

    .lang-btn:hover:not(.active) {
        background: var(--color-gray-light);
    }

    /* Card Body */
    .accessibility-card-body {
        padding: 12px;
        max-height: 59vh;
        overflow-y: auto;
    }

    .accessibility-card-body::-webkit-scrollbar {
        width: 3px;
    }

    .accessibility-card-body::-webkit-scrollbar-track {
        background: var(--color-gray-light);
        border-radius: 3px;
    }

    .accessibility-card-body::-webkit-scrollbar-thumb {
        background: #12ABA9;
        border-radius: 3px;
    }

    /* Items */
    .accessibility-widget .item {
        padding: 8px 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid var(--color-gray-border);
    }

    .accessibility-widget .item:last-child {
        border-bottom: none;
    }

    .accessibility-widget .item label {
        display: inline-block;
        font-size: 13px;
        cursor: pointer;
        flex: 1;
    }

    .accessibility-widget .item button {
        color: var(--color-normal-text);
        background-color: var(--color-gray-light);
        border: none;
        outline: none;
        padding: 4px 12px;
        border-radius: 16px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 500;
        transition: all var(--transition-fast);
        margin-left: 4px;
    }

    .accessibility-widget .item button:hover {
        background-color: #12ABA9;
        color: white;
    }

    /* Reset Button */
    .reset-btn {
        width: 100%;
        margin-top: 10px;
        padding: 7px;
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
        border: none;
        border-radius: 20px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 500;
        transition: all var(--transition-fast);
    }

    .reset-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
    }

    /* Toggle Switch */
    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 40px;
        height: 20px;
        flex-shrink: 0;
    }

    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #cbd5e1;
        transition: 0.25s;
        border-radius: 20px;
    }

    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 2px;
        bottom: 2px;
        background-color: white;
        transition: 0.25s;
        border-radius: 50%;
    }

    input:checked + .toggle-slider {
        background-color: #12ABA9;
    }

    input:checked + .toggle-slider:before {
        transform: translateX(20px);
    }

    /* Global Accessibility Styles */
    html.monochrome {
        filter: grayscale(100%) contrast(110%);
    }

    html.inverted {
        filter: invert(85%) contrast(110%);
        background: #fff;
    }

    html.bigCursor,
    html.bigCursor * {
        cursor: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 28 28"><circle cx="14" cy="14" r="12" fill="black" stroke="white" stroke-width="2"/><circle cx="14" cy="14" r="6" fill="white"/></svg>') 14 14, auto !important;
    }

    html.highlightLinks a {
        color: #000 !important;
        background-color: #dce785 !important;
        text-decoration: underline !important;
    }

    html.highlightHeadings h1,
    html.highlightHeadings h2,
    html.highlightHeadings h3,
    html.highlightHeadings h4,
    html.highlightHeadings h5,
    html.highlightHeadings h6 {
        color: #000 !important;
        background-color: #aec00a !important;
        padding: 2px 6px;
        border-radius: 4px;
        display: inline-block;
    }

    html.accessibility_readingLine #readingGuide {
        opacity: 1;
        width: 100vw;
        height: 3px;
        z-index: 999999;
        position: fixed;
        left: 0;
        background-color: #d1df0e !important;
        pointer-events: none;
    }

    #readingGuide {
        display: none;
        position: fixed;
        left: 0;
        right: 0;
        height: 3px;
        background-color: #d1df0e;
        z-index: 999999;
        pointer-events: none;
    }

    /* Skip Link */
    .tab-menu {
        z-index: 99999;
        position: fixed;
        top: 10px;
        left: 50%;
        transform: translateX(-50%) translateY(-150%);
        transition: transform 0.2s 0.2s;
    }

    .tab-menu:focus-within {
        transform: translateX(-50%) translateY(0);
    }

    .skip-link {
        cursor: pointer;
        background: #12ABA9;
        color: white;
        border-radius: 30px;
        padding: 8px 16px;
        font-size: 12px;
        display: block;
        text-decoration: none;
        font-weight: 500;
    }

    /* IMPORTANT: Make all text responsive to root font size */
    * {
        font-size: inherit;
    }
    
    body {
        font-size: 1rem;
    }
    
    p, span, li, a, button, label, div:not(.accessibility-widget *) {
        
    }
    
    h1 { font-size: 2rem; }
    h2 { font-size: 1.75rem; }
    h3 { font-size: 1.5rem; }
    h4 { font-size: 1.25rem; }
    h5 { font-size: 1.1rem; }
    h6 { font-size: 1rem; }
</style>

<div class="accessibility-widget">
    <!-- Floating Button -->
    <div id="accessibility-btn" class="accessibility-float" aria-label="Accessibility options">
        <i class="fa fa-wheelchair mobile-icon" style="font-size:26px; text-align: center; margin-left: 3px"></i>
    </div>

    <!-- Accessibility Card -->
    <div id="accessibility-card" class="accessibility-card" role="dialog" aria-label="Accessibility settings">
        <div class="accessibility-card-header">
            <h3 id="card-title">Accessibility</h3>
            <div id="accessibility-close" class="ph-x-circle" aria-label="Close"></div>
        </div>

        <!-- Language Switcher -->
        <div class="language-switcher">
            <button class="lang-btn active" data-lang="en">EN</button>
            <button class="lang-btn" data-lang="bn">বাংলা</button>
        </div>

        <div class="accessibility-card-body">
            <div class="item">
                <label id="font-size-label">Font Size</label>
                <div>
                    <button id="font-decrease" aria-label="Decrease">A-</button>
                    <button id="font-increase" aria-label="Increase">A+</button>
                </div>
            </div>

            <div class="item">
                <label id="monochrome-label">Monochrome</label>
                <label class="toggle-switch">
                    <input type="checkbox" id="monochrome">
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="item">
                <label id="inverted-label">Invert Colors</label>
                <label class="toggle-switch">
                    <input type="checkbox" id="inverted">
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="item">
                <label id="cursor-label">Big Cursor</label>
                <label class="toggle-switch">
                    <input type="checkbox" id="bigCursor">
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="item">
                <label id="links-label">Highlight Links</label>
                <label class="toggle-switch">
                    <input type="checkbox" id="highlightLinks">
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="item">
                <label id="headings-label">Highlight Headings</label>
                <label class="toggle-switch">
                    <input type="checkbox" id="highlightHeadings">
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="item">
                <label id="reading-guide-label">Reading Guide</label>
                <label class="toggle-switch">
                    <input type="checkbox" id="readingGuideCheckbox">
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <button class="reset-btn" id="reset-label">Reset All</button>
        </div>
    </div>
</div>

<!-- Skip to content -->
<div class="tab-menu">
    <a href="#main-content" class="skip-link" id="skip-link">Skip to content</a>
</div>

<script>
    (function(window, document) {
        // Wait for DOM and also ensure Font Awesome is loaded
        function init() {
            console.log("Accessibility Widget Initializing...");
            
            // DOM Elements
            const elements = {
                btn: document.getElementById("accessibility-btn"),
                close: document.getElementById("accessibility-close"),
                card: document.getElementById("accessibility-card"),
                fontIncrease: document.getElementById("font-increase"),
                fontDecrease: document.getElementById("font-decrease"),
                monochrome: document.getElementById("monochrome"),
                inverted: document.getElementById("inverted"),
                bigCursor: document.getElementById("bigCursor"),
                highlightLinks: document.getElementById("highlightLinks"),
                highlightHeadings: document.getElementById("highlightHeadings"),
                readingGuide: document.getElementById("readingGuideCheckbox"),
                resetBtn: document.querySelector(".reset-btn")
            };

            // Verify elements exist
            console.log("Font Increase Button exists:", !!elements.fontIncrease);
            console.log("Font Decrease Button exists:", !!elements.fontDecrease);

            // Language translations
            const translations = {
                en: {
                    title: "Accessibility",
                    fontSize: "Font Size",
                    monochrome: "Monochrome",
                    inverted: "Invert Colors",
                    bigCursor: "Big Cursor",
                    highlightLinks: "Highlight Links",
                    highlightHeadings: "Highlight Headings",
                    readingGuide: "Reading Guide",
                    reset: "Reset All",
                    skipLink: "Skip to content"
                },
                bn: {
                    title: "অ্যাক্সেসিবিলিটি",
                    fontSize: "ফন্ট সাইজ",
                    monochrome: "একরঙা",
                    inverted: "উল্টো রং",
                    bigCursor: "বড় কার্সর",
                    highlightLinks: "লিংক হাইলাইট",
                    highlightHeadings: "হেডিং হাইলাইট",
                    readingGuide: "রিডিং গাইড",
                    reset: "সব রিসেট",
                    skipLink: "কন্টেন্টে যান"
                }
            };

            let currentLang = localStorage.getItem("accessibility_lang") || "en";

            function applyLanguage(lang) {
                currentLang = lang;
                localStorage.setItem("accessibility_lang", lang);
                
                const titleEl = document.getElementById("card-title");
                const fontSizeLabel = document.getElementById("font-size-label");
                const monochromeLabel = document.getElementById("monochrome-label");
                const invertedLabel = document.getElementById("inverted-label");
                const cursorLabel = document.getElementById("cursor-label");
                const linksLabel = document.getElementById("links-label");
                const headingsLabel = document.getElementById("headings-label");
                const readingGuideLabel = document.getElementById("reading-guide-label");
                const resetLabel = document.getElementById("reset-label");
                const skipLinkEl = document.getElementById("skip-link");
                
                if (titleEl) titleEl.textContent = translations[lang].title;
                if (fontSizeLabel) fontSizeLabel.textContent = translations[lang].fontSize;
                if (monochromeLabel) monochromeLabel.textContent = translations[lang].monochrome;
                if (invertedLabel) invertedLabel.textContent = translations[lang].inverted;
                if (cursorLabel) cursorLabel.textContent = translations[lang].bigCursor;
                if (linksLabel) linksLabel.textContent = translations[lang].highlightLinks;
                if (headingsLabel) headingsLabel.textContent = translations[lang].highlightHeadings;
                if (readingGuideLabel) readingGuideLabel.textContent = translations[lang].readingGuide;
                if (resetLabel) resetLabel.textContent = translations[lang].reset;
                if (skipLinkEl) skipLinkEl.textContent = translations[lang].skipLink;
                
                document.querySelectorAll(".lang-btn").forEach(btn => {
                    if (btn.dataset.lang === lang) {
                        btn.classList.add("active");
                    } else {
                        btn.classList.remove("active");
                    }
                });
            }

            // Language switcher
            document.querySelectorAll(".lang-btn").forEach(btn => {
                btn.addEventListener("click", () => applyLanguage(btn.dataset.lang));
            });

            // Create reading guide
            const readingGuide = document.createElement("div");
            readingGuide.id = "readingGuide";

            function toggleCard() {
                if (elements.card) {
                    elements.card.classList.toggle("accessibility-card-open");
                }
            }

            function updateReadingGuidePosition(e) {
                readingGuide.style.top = e.pageY + "px";
            }

            function setPreference(key, value, callback) {
                if (callback) callback(value);
                localStorage.setItem(key, value);
            }

            // FONT SIZE FUNCTIONS - FIXED with multiple approaches
            function getCurrentRootFontSize() {
                return parseFloat(getComputedStyle(document.documentElement).fontSize);
            }

            function setRootFontSize(sizeInPx) {
                if (sizeInPx >= 8 && sizeInPx <= 48) {
                    document.documentElement.style.fontSize = sizeInPx + "px";
                    localStorage.setItem("fontSize", sizeInPx + "px");
                    console.log("Font size changed to:", sizeInPx + "px");
                    
                    // Force a repaint for better compatibility
                    document.body.style.display = 'none';
                    document.body.offsetHeight; // force reflow
                    document.body.style.display = '';
                    
                    return true;
                }
                return false;
            }

            function increaseFontSize() {
                let currentSize = getCurrentRootFontSize();
                console.log("Current font size before increase:", currentSize);
                
                if (currentSize < 40) {
                    let newSize = Math.min(currentSize + 2, 40);
                    setRootFontSize(newSize);
                } else {
                    console.log("Maximum font size reached (40px)");
                }
            }

            function decreaseFontSize() {
                let currentSize = getCurrentRootFontSize();
                console.log("Current font size before decrease:", currentSize);
                
                if (currentSize > 10) {
                    let newSize = Math.max(currentSize - 2, 10);
                    setRootFontSize(newSize);
                } else {
                    console.log("Minimum font size reached (10px)");
                }
            }

            // Test function to verify buttons work
            function testFontButtons() {
                console.log("Testing font buttons...");
                if (elements.fontIncrease) {
                    console.log("Increase button found, adding event listeners");
                    elements.fontIncrease.removeEventListener("click", increaseFontSize);
                    elements.fontIncrease.addEventListener("click", increaseFontSize);
                    elements.fontIncrease.removeEventListener("touchstart", increaseFontSize);
                    elements.fontIncrease.addEventListener("touchstart", increaseFontSize);
                }
                if (elements.fontDecrease) {
                    console.log("Decrease button found, adding event listeners");
                    elements.fontDecrease.removeEventListener("click", decreaseFontSize);
                    elements.fontDecrease.addEventListener("click", decreaseFontSize);
                    elements.fontDecrease.removeEventListener("touchstart", decreaseFontSize);
                    elements.fontDecrease.addEventListener("touchstart", decreaseFontSize);
                }
            }

            // Toggle functions
            window.toggleMonochrome = function(enabled) {
                document.documentElement.classList.toggle("monochrome", enabled);
                if (enabled && elements.inverted) {
                    elements.inverted.checked = false;
                    document.documentElement.classList.remove("inverted");
                    localStorage.setItem("inverted", "false");
                }
            };

            window.toggleInverted = function(enabled) {
                document.documentElement.classList.toggle("inverted", enabled);
                if (enabled && elements.monochrome) {
                    elements.monochrome.checked = false;
                    document.documentElement.classList.remove("monochrome");
                    localStorage.setItem("monochrome", "false");
                }
            };

            window.toggleBigCursor = function(enabled) {
                document.documentElement.classList.toggle("bigCursor", enabled);
            };

            window.toggleHighlightLinks = function(enabled) {
                document.documentElement.classList.toggle("highlightLinks", enabled);
            };

            window.toggleHighlightHeadings = function(enabled) {
                document.documentElement.classList.toggle("highlightHeadings", enabled);
            };

            window.toggleReadingGuide = function(enabled) {
                if (enabled) {
                    document.body.appendChild(readingGuide);
                    readingGuide.style.display = "block";
                    document.addEventListener('mousemove', updateReadingGuidePosition);
                } else {
                    readingGuide.style.display = "none";
                    document.removeEventListener("mousemove", updateReadingGuidePosition);
                }
                document.documentElement.classList.toggle("accessibility_readingLine", enabled);
            };

            // Event listeners
            if (elements.btn) {
                elements.btn.addEventListener("click", toggleCard);
            }
            
            if (elements.close) {
                elements.close.addEventListener("click", () => {
                    if (elements.card) elements.card.classList.remove("accessibility-card-open");
                });
            }

            // Apply font button listeners with multiple attempts
            testFontButtons();

            // Also add click handlers directly as backup
            if (elements.fontIncrease) {
                elements.fontIncrease.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    increaseFontSize();
                    return false;
                };
            }
            
            if (elements.fontDecrease) {
                elements.fontDecrease.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    decreaseFontSize();
                    return false;
                };
            }

            if (elements.monochrome) {
                elements.monochrome.addEventListener("change", (e) => {
                    setPreference("monochrome", e.target.checked, window.toggleMonochrome);
                });
            }

            if (elements.inverted) {
                elements.inverted.addEventListener("change", (e) => {
                    setPreference("inverted", e.target.checked, window.toggleInverted);
                });
            }

            if (elements.bigCursor) {
                elements.bigCursor.addEventListener("change", (e) => {
                    setPreference("bigCursor", e.target.checked, window.toggleBigCursor);
                });
            }

            if (elements.highlightLinks) {
                elements.highlightLinks.addEventListener("change", (e) => {
                    setPreference("highlightLinks", e.target.checked, window.toggleHighlightLinks);
                });
            }

            if (elements.highlightHeadings) {
                elements.highlightHeadings.addEventListener("change", (e) => {
                    setPreference("highlightHeadings", e.target.checked, window.toggleHighlightHeadings);
                });
            }

            if (elements.readingGuide) {
                elements.readingGuide.addEventListener("change", (e) => {
                    setPreference("readingGuideCheckbox", e.target.checked, window.toggleReadingGuide);
                });
            }

            if (elements.resetBtn) {
                elements.resetBtn.addEventListener("click", () => {
                    if (elements.monochrome) elements.monochrome.checked = false;
                    if (elements.inverted) elements.inverted.checked = false;
                    if (elements.bigCursor) elements.bigCursor.checked = false;
                    if (elements.highlightLinks) elements.highlightLinks.checked = false;
                    if (elements.highlightHeadings) elements.highlightHeadings.checked = false;
                    if (elements.readingGuide) elements.readingGuide.checked = false;

                    document.documentElement.classList.remove("monochrome", "inverted", "bigCursor", "highlightLinks", "highlightHeadings", "accessibility_readingLine");
                    readingGuide.style.display = "none";
                    document.documentElement.style.fontSize = "";
                    localStorage.clear();
                    document.removeEventListener("mousemove", updateReadingGuidePosition);
                    applyLanguage(currentLang);
                    
                    console.log("All settings reset");
                });
            }

            // Load saved preferences
            const savedItems = ["monochrome", "inverted", "bigCursor", "highlightLinks", "highlightHeadings", "readingGuideCheckbox"];
            savedItems.forEach(item => {
                const isChecked = localStorage.getItem(item) === "true";
                const element = document.getElementById(item);
                if (element) {
                    element.checked = isChecked;
                    const toggleFn = window["toggle" + item.charAt(0).toUpperCase() + item.slice(1)];
                    if (typeof toggleFn === "function") toggleFn(isChecked);
                }
            });

            // Load saved font size
            const savedFont = localStorage.getItem("fontSize");
            if (savedFont) {
                let fontSizeValue = parseFloat(savedFont);
                if (!isNaN(fontSizeValue) && fontSizeValue >= 8 && fontSizeValue <= 48) {
                    document.documentElement.style.fontSize = savedFont;
                    console.log("Loaded saved font size:", savedFont);
                }
            } else {
                // Set default font size if none saved
                document.documentElement.style.fontSize = "16px";
            }

            // Apply saved language
            applyLanguage(currentLang);

            // Setup skip link
            const tabMenu = document.querySelector(".tab-menu");
            if (tabMenu) document.body.prepend(tabMenu);

            let mainContent = document.querySelector('[data-section-type="body"], main, #app, .container');
            if (!mainContent) mainContent = document.body;
            mainContent.setAttribute("id", "main-content");

            const skipLink = document.getElementById("skip-link");
            if (skipLink) {
                skipLink.addEventListener("click", (e) => {
                    e.preventDefault();
                    if (elements.card) elements.card.classList.remove("accessibility-card-open");
                    const target = document.getElementById("main-content");
                    if (target) {
                        target.setAttribute("tabindex", "-1");
                        target.focus();
                        target.removeAttribute("tabindex");
                    }
                });
            }

            // Close card on outside click
            document.addEventListener("click", (e) => {
                if (elements.card && elements.card.classList.contains("accessibility-card-open")) {
                    if (elements.btn && !elements.card.contains(e.target) && !elements.btn.contains(e.target)) {
                        elements.card.classList.remove("accessibility-card-open");
                    }
                }
            });
            
            console.log("Accessibility Widget Initialized Successfully!");
        }

        // Run init when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener("DOMContentLoaded", init);
        } else {
            init();
        }
    })(window, document);
</script>
