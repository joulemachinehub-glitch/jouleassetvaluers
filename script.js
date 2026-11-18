/* ==========================================================================
   Joule Asset Valuers - Chartered Engineer Website JavaScript File
   ========================================================================== */

// 0. Static Version Constant (Zero auto-reloads to prevent infinite reload loops)
(function() {
    const CURRENT_VERSION = '20261118_v134';
    try {
        localStorage.setItem('site_v', CURRENT_VERSION);
    } catch(e) {}
})();
// 1. Immediate Theme Detection (Runs immediately to prevent theme flash)
(function() {
    const storedTheme = localStorage.getItem('theme') || 'dark';
    if (storedTheme === 'light') {
        document.documentElement.setAttribute('data-theme', 'light');
    } else {
        document.documentElement.setAttribute('data-theme', 'dark');
    }
})();

document.addEventListener('DOMContentLoaded', () => {
    
    // ==========================================
    // 2. Light / Dark Theme Button Bindings
    // ==========================================
    const themeToggleButtons = document.querySelectorAll('.theme-toggle-btn');
    const currentTheme = document.documentElement.getAttribute('data-theme');
    
    updateToggleIcons(currentTheme);

    themeToggleButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const activeTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = activeTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateToggleIcons(newTheme);
        });
    });

    function updateToggleIcons(theme) {
        themeToggleButtons.forEach(btn => {
            const icon = btn.querySelector('i');
            if (icon) {
                if (theme === 'dark') {
                    icon.className = 'fa-solid fa-sun';
                } else {
                    icon.className = 'fa-solid fa-moon';
                }
            }
        });
    }

    // ==========================================
    // 3. Sticky Navigation Header
    // ==========================================
    const navbar = document.querySelector('.navbar');
    
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.classList.add('sticky');
        } else {
            navbar.classList.remove('sticky');
        }
    });

    // Mobile Menu Toggle Event Listener (Phones & Tablets)
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const navMenu = document.querySelector('.nav-menu');
    if (mobileMenuToggle && navMenu) {
        mobileMenuToggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            navMenu.classList.toggle('open');
            const icon = mobileMenuToggle.querySelector('i');
            if (icon) {
                if (navMenu.classList.contains('open')) {
                    icon.className = 'fa-solid fa-xmark';
                } else {
                    icon.className = 'fa-solid fa-bars';
                }
            }
        });

        // Close menu when clicking any nav link inside mobile menu
        const navLinks = navMenu.querySelectorAll('a');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('open');
                const icon = mobileMenuToggle.querySelector('i');
                if (icon) icon.className = 'fa-solid fa-bars';
            });
        });

        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!mobileMenuToggle.contains(e.target) && !navMenu.contains(e.target)) {
                navMenu.classList.remove('open');
                const icon = mobileMenuToggle.querySelector('i');
                if (icon) icon.className = 'fa-solid fa-bars';
            }
        });
    }

    // ==========================================
    // 5. Gallery Dynamically Filter Items
    // ==========================================
    const filterButtons = document.querySelectorAll('.filter-btn');
    const galleryItems = document.querySelectorAll('.gallery-item');

    if (filterButtons.length > 0 && galleryItems.length > 0) {
        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Change active button style
                filterButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');

                const filterValue = button.getAttribute('data-filter');

                galleryItems.forEach(item => {
                    const itemCategory = item.getAttribute('data-category');
                    
                    if (filterValue === 'all' || itemCategory === filterValue) {
                        item.style.display = 'inline-block';
                        // Add fade-in animation
                        item.style.opacity = '0';
                        setTimeout(() => {
                            item.style.opacity = '1';
                            item.style.transition = 'opacity 0.4s ease';
                        }, 50);
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
    }

    // ==========================================
    // 6. Interactive Lightbox Modal for Gallery
    // ==========================================
    const lightboxModal = document.querySelector('.lightbox-modal');
    const lightboxImage = document.querySelector('.lightbox-image');
    const lightboxCaption = document.querySelector('.lightbox-caption');
    const lightboxClose = document.querySelector('.lightbox-close');

    if (galleryItems.length > 0 && lightboxModal) {
        galleryItems.forEach(item => {
            item.addEventListener('click', () => {
                const imgUrl = item.querySelector('img').src;
                const imgTitle = item.querySelector('.gallery-title').textContent;
                const imgCategory = item.querySelector('.gallery-category').textContent;

                if (lightboxImage && lightboxCaption) {
                    lightboxImage.src = imgUrl;
                    lightboxCaption.textContent = `${imgTitle} - ${imgCategory}`;
                }
                
                lightboxModal.classList.add('open');
                document.body.style.overflow = 'hidden';
            });
        });

        function closeLightbox() {
            lightboxModal.classList.remove('open');
            document.body.style.overflow = 'auto';
            setTimeout(() => {
                if (lightboxImage) lightboxImage.src = '';
            }, 300);
        }

        if (lightboxClose) lightboxClose.addEventListener('click', closeLightbox);
        lightboxModal.addEventListener('click', (e) => {
            if (e.target === lightboxModal) {
                closeLightbox();
            }
        });
    }

    // ==========================================
    // 7. Anti-Spam Filter, Math Captcha & Form Submission
    // ==========================================
    const SPAM_KEYWORDS = [
        "seo packages",
        "seo package",
        "seo services",
        "seo service",
        "seo solutions",
        "improve your website's online visibility",
        "improve your websites online visibility",
        "improve your website online visibility",
        "online visibility",
        "digital marketing services",
        "digital marketing service",
        "digital marketing",
        "marketing packages",
        "marketing package",
        "website promotion",
        "link building",
        "backlink services",
        "backlink service",
        "backlinks",
        "backlink",
        "guest posting",
        "guest post",
        "guest posts",
        "ranking guarantee",
        "guaranteed ranking",
        "increase website traffic",
        "website traffic",
        "google indexing spam",
        "search index",
        "google search results",
        "feature your website",
        "searchregister.live",
        "searchregister",
        "submit your website today",
        "submit your website",
        "indexing service",
        "indexing services",
        "get indexed today",
        "get indexed",
        "seo and marketing spam",
        "unsolicited promotional",
        "rank on google",
        "first page of google",
        "boost your ranking",
        "boost google ranking"
    ];

    // Helper: Show Spam Warning Modal Popup
    function showSpamWarningModal(detectedKeyword) {
        let modalOverlay = document.getElementById('spamWarningModal');
        if (!modalOverlay) {
            modalOverlay = document.createElement('div');
            modalOverlay.id = 'spamWarningModal';
            modalOverlay.className = 'spam-warning-modal-overlay';
            modalOverlay.innerHTML = `
                <div class="spam-warning-modal-card" role="dialog" aria-modal="true" aria-labelledby="spamModalTitle">
                    <button type="button" class="spam-warning-modal-close-icon" aria-label="Close Warning">&times;</button>
                    <div style="width: 64px; height: 64px; border-radius: 50%; background: rgba(245, 158, 11, 0.15); border: 2px solid #f59e0b; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; color: #f59e0b; font-size: 28px;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <h3 id="spamModalTitle" style="color: #facc15; font-size: 1.35rem; font-weight: 700; margin-bottom: 8px;">Submission Blocked</h3>
                    <p style="color: #f87171; font-weight: 600; font-size: 0.95rem; margin-bottom: 16px;">
                        <i class="fa-solid fa-ban"></i> Promotional or Spam Content Detected
                    </p>
                    <div style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 8px; padding: 14px 16px; margin-bottom: 22px; text-align: left; font-size: 0.88rem; color: #cbd5e1; line-height: 1.6;">
                        Your inquiry contains prohibited marketing or SEO keywords: <strong id="spamDetectedTerm" style="color: #facc15; text-decoration: underline;"></strong>.<br><br>
                        Our contact forms are strictly reserved for genuine <strong>Chartered Engineer, machinery survey, and industrial valuation</strong> inquiries. Unsolicited promotional proposals are automatically rejected and will <strong>not</strong> be delivered to our email or saved in our database.
                    </div>
                    <button type="button" class="btn btn-accent spam-modal-close-btn" style="width: 100%; padding: 12px 20px; font-weight: 700; font-size: 1rem; cursor: pointer;">
                        <i class="fa-solid fa-pen-to-square"></i> I Understand &amp; Edit Inquiry
                    </button>
                </div>
            `;
            document.body.appendChild(modalOverlay);

            // Close handlers
            const closeBtn = modalOverlay.querySelector('.spam-modal-close-btn');
            const closeIcon = modalOverlay.querySelector('.spam-warning-modal-close-icon');
            const closeModal = () => {
                modalOverlay.classList.remove('active');
                const messageBox = document.querySelector('textarea#message, textarea[name="message"]');
                if (messageBox) messageBox.focus();
            };

            closeBtn.addEventListener('click', closeModal);
            closeIcon.addEventListener('click', closeModal);
            modalOverlay.addEventListener('click', (e) => {
                if (e.target === modalOverlay) closeModal();
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && modalOverlay.classList.contains('active')) closeModal();
            });
        }

        const termEl = modalOverlay.querySelector('#spamDetectedTerm');
        if (termEl) {
            termEl.textContent = detectedKeyword ? `"${detectedKeyword}"` : 'Promotional Keywords';
        }
        modalOverlay.classList.add('active');
    }

    // Helper: Initialize Math Captcha on a Form
    function setupFormMathCaptcha(form) {
        let container = form.querySelector('.captcha-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'form-group form-group-full captcha-container';
            container.innerHTML = `
                <label class="form-label" style="display: flex; align-items: center; justify-content: space-between; font-weight: 600; font-size: 0.9rem;">
                    <span><i class="fa-solid fa-shield-halved" style="color: var(--color-accent); margin-right: 6px;"></i> Security Verification</span>
                    <span class="captcha-badge" style="font-size: 0.78rem; color: #94a3b8; background: rgba(255, 255, 255, 0.05); padding: 2px 8px; border-radius: 4px;">Anti-Spam Check</span>
                </label>
                <div class="captcha-box">
                    <div class="captcha-question-wrap">
                        <i class="fa-solid fa-calculator" style="color: var(--color-accent); font-size: 0.9rem;"></i>
                        <span class="captcha-math-text">-- + -- = ?</span>
                    </div>
                    <input type="number" name="captcha_answer" class="form-input captcha-input" placeholder="Answer" required>
                    <input type="hidden" name="captcha_hash" class="captcha-hash-input" value="">
                    <button type="button" class="btn btn-secondary captcha-refresh-btn" title="Get a different question" aria-label="Refresh Security Question">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </button>
                    <span class="captcha-hint">Solve the simple math to verify you are human</span>
                </div>
                <div class="captcha-error-msg" style="display: none;">
                    <i class="fa-solid fa-circle-exclamation"></i> Incorrect answer. Please enter the correct sum.
                </div>
            `;
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                form.insertBefore(container, submitBtn);
            } else {
                form.appendChild(container);
            }
        }

        const mathText = container.querySelector('.captcha-math-text');
        const hashInput = container.querySelector('.captcha-hash-input');
        const answerInput = container.querySelector('.captcha-input');
        const refreshBtn = container.querySelector('.captcha-refresh-btn');
        const errorMsg = container.querySelector('.captcha-error-msg');

        const generateProblem = () => {
            const a = Math.floor(Math.random() * 8) + 2; // 2 to 9
            const b = Math.floor(Math.random() * 8) + 1; // 1 to 8
            const sum = a + b;
            if (mathText) mathText.textContent = `${a} + ${b} = ?`;
            form.dataset.captchaSum = String(sum);
            if (hashInput) hashInput.value = btoa(sum + '_salt_joule_2026');
            if (answerInput) {
                answerInput.value = '';
                answerInput.style.borderColor = 'var(--color-border)';
            }
            if (errorMsg) errorMsg.style.display = 'none';
        };

        form._generateCaptcha = generateProblem;

        if (answerInput && !answerInput.dataset.bound) {
            answerInput.dataset.bound = 'true';
            answerInput.addEventListener('input', () => {
                if (errorMsg) errorMsg.style.display = 'none';
                answerInput.style.borderColor = 'var(--color-border)';
            });
        }

        if (refreshBtn && !refreshBtn.dataset.bound) {
            refreshBtn.dataset.bound = 'true';
            refreshBtn.addEventListener('click', (e) => {
                e.preventDefault();
                generateProblem();
                if (answerInput) answerInput.focus();
            });
        }

        generateProblem();
    }

    // Attach to All Contact Forms
    const contactForms = document.querySelectorAll('form#contactForm, form.quote-form');
    
    if (contactForms.length > 0) {
        contactForms.forEach(form => {
            // Setup math captcha for this form
            setupFormMathCaptcha(form);

            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                // Clear any previous response messages inside this form
                const existingMsg = form.querySelector('.form-response-msg');
                if (existingMsg) existingMsg.remove();

                const nameInput = form.querySelector('#name') || form.querySelector('input[name="name"]');
                const emailInput = form.querySelector('#email') || form.querySelector('input[name="email"]');
                const phoneInput = form.querySelector('#phone') || form.querySelector('input[name="phone"]');
                const locationInput = form.querySelector('#address') || form.querySelector('#location') || form.querySelector('input[name="location"]');
                const serviceInput = form.querySelector('#service') || form.querySelector('select[name="service"]') || form.querySelector('input[name="service"]');
                const messageInput = form.querySelector('#message') || form.querySelector('textarea[name="message"]');
                const captchaAnswerInput = form.querySelector('input[name="captcha_answer"]') || form.querySelector('.captcha-input');
                const captchaHashInput = form.querySelector('input[name="captcha_hash"]') || form.querySelector('.captcha-hash-input');
                const captchaErrorMsg = form.querySelector('.captcha-error-msg');
                const submitBtn = form.querySelector('button[type="submit"]');

                const validationErrors = [];
                let firstInvalidElement = null;

                // 1. Full Name Validation
                if (!nameInput || nameInput.value.trim().length < 2) {
                    validationErrors.push('Please enter your full name (minimum 2 characters).');
                    if (nameInput) {
                        nameInput.style.borderColor = '#ef4444';
                        if (!firstInvalidElement) firstInvalidElement = nameInput;
                    }
                } else if (nameInput) {
                    nameInput.style.borderColor = 'var(--color-border)';
                }

                // 2. Phone Number Validation (At least 7 digits, supports +91, spaces, hyphens)
                const rawPhone = phoneInput ? phoneInput.value.trim() : '';
                const cleanDigits = rawPhone.replace(/\D/g, '');
                if (!phoneInput || cleanDigits.length < 7 || cleanDigits.length > 15) {
                    validationErrors.push('Please enter a valid phone number (at least 7 to 10 digits).');
                    if (phoneInput) {
                        phoneInput.style.borderColor = '#ef4444';
                        if (!firstInvalidElement) firstInvalidElement = phoneInput;
                    }
                } else if (phoneInput) {
                    phoneInput.style.borderColor = 'var(--color-border)';
                }

                // 3. Email Address Validation
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailInput || !emailRegex.test(emailInput.value.trim())) {
                    validationErrors.push('Please enter a valid email address (e.g. name@domain.com).');
                    if (emailInput) {
                        emailInput.style.borderColor = '#ef4444';
                        if (!firstInvalidElement) firstInvalidElement = emailInput;
                    }
                } else if (emailInput) {
                    emailInput.style.borderColor = 'var(--color-border)';
                }

                // 4. Service Selection (Auto-select first service if unselected)
                if (serviceInput && (!serviceInput.value || serviceInput.value.trim() === '')) {
                    if (serviceInput.tagName === 'SELECT') {
                        // Automatically select the primary service so lead is never lost
                        serviceInput.value = 'Chartered Engineer Service for Customs';
                    }
                }
                if (serviceInput) {
                    serviceInput.style.borderColor = 'var(--color-border)';
                }

                // 5. Message (Auto-fill default if user left it blank)
                if (messageInput && messageInput.value.trim() === '') {
                    const chosenSvc = (serviceInput && serviceInput.value) ? serviceInput.value : 'Chartered Engineer consultation';
                    messageInput.value = 'Inquiry request for ' + chosenSvc + '.';
                }
                if (messageInput) {
                    messageInput.style.borderColor = 'var(--color-border)';
                }

                // 6. Security Math Verification
                const expectedSum = form.dataset.captchaSum;
                const userSum = captchaAnswerInput ? captchaAnswerInput.value.trim() : '';

                if (!userSum || userSum !== expectedSum) {
                    validationErrors.push('Incorrect security math answer. Please enter the correct sum.');
                    if (captchaAnswerInput) {
                        captchaAnswerInput.style.borderColor = '#ef4444';
                        if (!firstInvalidElement) firstInvalidElement = captchaAnswerInput;
                    }
                    if (captchaErrorMsg) {
                        captchaErrorMsg.style.display = 'block';
                    }
                } else {
                    if (captchaAnswerInput) captchaAnswerInput.style.borderColor = 'var(--color-border)';
                    if (captchaErrorMsg) captchaErrorMsg.style.display = 'none';
                }

                // Input listeners to auto-clear red borders on typing
                [nameInput, emailInput, phoneInput, serviceInput, messageInput, captchaAnswerInput].forEach(inp => {
                    if (inp && !inp.dataset.clearBound) {
                        inp.dataset.clearBound = 'true';
                        const evtType = inp.tagName === 'SELECT' ? 'change' : 'input';
                        inp.addEventListener(evtType, () => {
                            inp.style.borderColor = 'var(--color-border)';
                            const currentErr = form.querySelector('.form-response-msg.form-error');
                            if (currentErr) currentErr.remove();
                        });
                    }
                });

                // Display Errors if any validation failed
                const responseContainer = document.createElement('div');
                responseContainer.className = 'form-response-msg';
                responseContainer.style.marginTop = '20px';
                responseContainer.style.padding = '14px 20px';
                responseContainer.style.borderRadius = 'var(--radius-sm)';
                responseContainer.style.fontWeight = '600';
                responseContainer.style.fontSize = '0.95rem';

                if (validationErrors.length > 0) {
                    responseContainer.className = 'form-response-msg form-error';
                    responseContainer.style.setProperty('background-color', '#991b1b', 'important');
                    responseContainer.style.setProperty('color', '#ffffff', 'important');
                    responseContainer.style.setProperty('border', '1.5px solid #f87171', 'important');
                    responseContainer.style.setProperty('padding', '14px 18px', 'important');
                    responseContainer.style.setProperty('border-radius', '8px', 'important');
                    responseContainer.style.setProperty('margin-top', '18px', 'important');
                    responseContainer.style.setProperty('display', 'block', 'important');
                    responseContainer.style.setProperty('box-sizing', 'border-box', 'important');
                    responseContainer.style.setProperty('width', '100%', 'important');

                    let errListHtml = '<div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">'
                        + '<i class="fa-solid fa-circle-exclamation" style="color: #fef08a !important; font-size: 1.25rem;"></i>'
                        + '<strong style="color: #ffffff !important; font-size: 0.95rem;">Please check the following:</strong></div>'
                        + '<ul style="margin: 0; padding-left: 24px; color: #fee2e2; font-size: 0.9rem; line-height: 1.6;">';
                    
                    validationErrors.forEach(err => {
                        errListHtml += '<li>' + err + '</li>';
                    });
                    errListHtml += '</ul>';

                    responseContainer.innerHTML = errListHtml;
                    form.appendChild(responseContainer);

                    if (firstInvalidElement) {
                        firstInvalidElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        try { firstInvalidElement.focus(); } catch (e) {}
                    }
                    return;
                }

                // ==========================================
                // Anti-Spam Keyword Filter (Client-Side Check)
                // ==========================================
                const combinedText = [
                    nameInput ? nameInput.value : '',
                    emailInput ? emailInput.value : '',
                    phoneInput ? phoneInput.value : '',
                    locationInput ? locationInput.value : '',
                    serviceInput ? serviceInput.value : '',
                    messageInput ? messageInput.value : ''
                ].join(' ').toLowerCase();

                let detectedSpamTerm = null;
                for (let i = 0; i < SPAM_KEYWORDS.length; i++) {
                    const kw = SPAM_KEYWORDS[i];
                    if (combinedText.includes(kw)) {
                        detectedSpamTerm = kw;
                        break;
                    }
                }

                if (detectedSpamTerm) {
                    if (messageInput) messageInput.style.borderColor = '#ef4444';
                    showSpamWarningModal(detectedSpamTerm);
                    return; // Block submission completely
                }

                // Prepare Payload
                const payload = {
                    name: nameInput.value.trim(),
                    email: emailInput.value.trim(),
                    phone: phoneInput.value.trim(),
                    location: locationInput ? locationInput.value.trim() : '',
                    service: serviceInput ? serviceInput.value.trim() : (document.title || 'General Consultation'),
                    message: messageInput.value.trim(),
                    captcha_answer: userSum,
                    captcha_hash: captchaHashInput ? captchaHashInput.value : '',
                    page_source: window.location.pathname + window.location.search
                };

                // Show loading state
                const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting Inquiry...';
                }

                try {
                    const response = await fetch('submit_form.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    const result = await response.json().catch(() => null);

                    if (response.ok && result && result.success) {
                        // Push conversion event to Google Tag Manager dataLayer
                        if (window.dataLayer) {
                            window.dataLayer.push({
                                event: 'form_submission_success',
                                inquiry_id: result.inquiry_id || '',
                                service: payload.service || '',
                                page_source: payload.page_source || ''
                            });
                        }

                        // Immediately redirect to Thank You page
                        const redirectUrl = result.redirect || 'thank-you.html';
                        window.location.href = redirectUrl;
                        return;
                    } else if (result && result.blocked) {
                        // Server also intercepted promotional / spam content
                        showSpamWarningModal(result.detected || 'Promotional Keywords');
                    } else {
                        throw new Error((result && result.message) ? result.message : 'Unable to submit inquiry at this moment. Please call or WhatsApp us at +91 6374191927.');
                    }
                } catch (err) {
                    console.error('Submission error:', err);
                    responseContainer.className = 'form-response-msg form-warning';
                    responseContainer.style.setProperty('background-color', '#78350f', 'important');
                    responseContainer.style.setProperty('color', '#ffffff', 'important');
                    responseContainer.style.setProperty('border', '1.5px solid #f59e0b', 'important');
                    responseContainer.style.setProperty('padding', '14px 18px', 'important');
                    responseContainer.style.setProperty('border-radius', '8px', 'important');
                    responseContainer.style.setProperty('margin-top', '18px', 'important');
                    responseContainer.style.setProperty('display', 'flex', 'important');
                    responseContainer.style.setProperty('align-items', 'center', 'important');
                    responseContainer.style.setProperty('gap', '12px', 'important');
                    responseContainer.style.setProperty('font-size', '0.95rem', 'important');
                    responseContainer.style.setProperty('font-weight', '600', 'important');
                    responseContainer.style.setProperty('line-height', '1.5', 'important');
                    responseContainer.style.setProperty('box-sizing', 'border-box', 'important');
                    responseContainer.style.setProperty('width', '100%', 'important');
                    responseContainer.innerHTML = '<i class="fa-solid fa-triangle-exclamation" style="color: #fde047 !important; font-size: 1.3rem !important; flex-shrink: 0; margin-right: 4px;"></i> <span style="color: #ffffff !important; font-weight: 600; font-size: 0.95rem; line-height: 1.5;">' + (err.message || 'Unable to submit inquiry. Please call or WhatsApp us at +91 6374191927.') + '</span>';
                    form.appendChild(responseContainer);
                    if (form._generateCaptcha) {
                        form._generateCaptcha();
                    }
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnHtml;
                    }
                }
            });
        });
    }

    // ==========================================
    // 8. Scroll Reveal Animation Observer
    // ==========================================
    const revealElements = document.querySelectorAll('.reveal');
    
    if (revealElements.length > 0) {
        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.08,
            rootMargin: '0px 0px -40px 0px'
        });
        
        revealElements.forEach(el => revealObserver.observe(el));
    }

    // ==========================================
    // 9. Stats Counter Animation (Animated Number Counting)
    // ==========================================
    const statsSection = document.querySelector('.stats-section');
    const statNumbers = document.querySelectorAll('.stat-number');
    
    if (statsSection && statNumbers.length > 0) {
        let countStarted = false;
        
        const countUp = () => {
            statNumbers.forEach(stat => {
                const target = parseInt(stat.getAttribute('data-target'), 10);
                const targetStr = stat.getAttribute('data-target');
                const hasPercent = targetStr === '100';
                const hasPlus = targetStr !== '100';
                const hasComma = targetStr === '1200' || targetStr === '1000';
                
                const duration = 2000; // 2 seconds
                const frameRate = 1000 / 60; // 60 fps
                const totalFrames = Math.round(duration / frameRate);
                let frame = 0;
                
                const timer = setInterval(() => {
                    frame++;
                    const progress = frame / totalFrames;
                    // Ease out cubic
                    const easeProgress = 1 - Math.pow(1 - progress, 3);
                    const currentVal = Math.floor(easeProgress * target);
                    
                    let formattedVal = currentVal;
                    if (hasComma && currentVal >= 1000) {
                        formattedVal = currentVal.toLocaleString('en-IN');
                    }
                    
                    if (hasPercent) {
                        stat.textContent = formattedVal + '%';
                    } else if (hasPlus) {
                        stat.textContent = formattedVal + '+';
                    } else {
                        stat.textContent = formattedVal;
                    }
                    
                    if (frame === totalFrames) {
                        clearInterval(timer);
                        // Ensure final value matches target format
                        let finalFormatted = target;
                        if (hasComma) {
                            finalFormatted = target.toLocaleString('en-IN');
                        }
                        if (hasPercent) {
                            stat.textContent = finalFormatted + '%';
                        } else if (hasPlus) {
                            stat.textContent = finalFormatted + '+';
                        } else {
                            stat.textContent = finalFormatted;
                        }
                    }
                }, frameRate);
            });
        };
        
        const counterObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !countStarted) {
                    countStarted = true;
                    countUp();
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.15
        });
        
        counterObserver.observe(statsSection);
    }

    // ==========================================
    // 10. FAQ Accordion Toggle
    // ==========================================
    const faqButtons = document.querySelectorAll('.faq-question, .blog-faq-btn');
    
    if (faqButtons.length > 0) {
        const uniqueFaqButtons = Array.from(new Set(faqButtons));
        
        uniqueFaqButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const currentBtn = e.currentTarget;
                const faqItem = currentBtn.closest('.faq-item, .blog-faq-card');
                if (!faqItem) return;
                
                const faqAnswer = faqItem.querySelector('.faq-answer, .blog-faq-answer');
                const isCurrentlyActive = faqItem.classList.contains('active');
                
                // Find parent container or accordion scope
                const container = faqItem.closest('.faq-accordion, .faq-container, .blog-faq-accordion, .blog-faq-wrapper') || faqItem.parentElement;
                
                // Close all other sibling items in the same container
                const siblingItems = container ? container.querySelectorAll('.faq-item, .blog-faq-card') : document.querySelectorAll('.faq-item, .blog-faq-card');
                siblingItems.forEach(item => {
                    if (item !== faqItem) {
                        item.classList.remove('active');
                        const otherBtn = item.querySelector('.faq-question, .blog-faq-btn');
                        if (otherBtn) otherBtn.setAttribute('aria-expanded', 'false');
                        const otherAnswer = item.querySelector('.faq-answer, .blog-faq-answer');
                        if (otherAnswer) {
                            otherAnswer.style.maxHeight = null;
                        }
                    }
                });
                
                // Toggle clicked item
                if (isCurrentlyActive) {
                    faqItem.classList.remove('active');
                    currentBtn.setAttribute('aria-expanded', 'false');
                    if (faqAnswer) {
                        faqAnswer.style.maxHeight = null;
                        faqAnswer.style.display = 'none';
                    }
                } else {
                    faqItem.classList.add('active');
                    currentBtn.setAttribute('aria-expanded', 'true');
                    if (faqAnswer) {
                        faqAnswer.style.maxHeight = 'none';
                        faqAnswer.style.display = 'block';
                    }
                }
            });
        });
        
        // Ensure any pre-active item on page load has its height properly set
        document.querySelectorAll('.faq-item.active, .blog-faq-card.active').forEach(activeItem => {
            const answer = activeItem.querySelector('.faq-answer, .blog-faq-answer');
            const btn = activeItem.querySelector('.faq-question, .blog-faq-btn');
            if (btn) btn.setAttribute('aria-expanded', 'true');
            if (answer && (!answer.style.maxHeight || answer.style.maxHeight === '0px')) {
                answer.style.maxHeight = (answer.scrollHeight + 40) + 'px';
            }
        });

        // Recalculate heights on resize/orientation change for mobile devices
        window.addEventListener('resize', () => {
            document.querySelectorAll('.faq-item.active, .blog-faq-card.active').forEach(activeItem => {
                const answer = activeItem.querySelector('.faq-answer, .blog-faq-answer');
                if (answer && answer.style.maxHeight) {
                    answer.style.maxHeight = (answer.scrollHeight + 40) + 'px';
                }
            });
        });
    }

    // ==========================================
    // 11. Dynamic Rotating Light Effect for Images
    // ==========================================
    const addImageEffects = () => {
        const style = document.createElement('style');
        style.textContent = `
            @property --light-angle {
                syntax: '<angle>';
                initial-value: 0deg;
                inherits: false;
            }
            .light-effect-wrapper {
                position: relative;
            }
            .light-effect-wrapper::after {
                content: '';
                position: absolute;
                inset: 0;
                border-radius: inherit;
                padding: 4px; /* Thickness of the rotating light */
                background: conic-gradient(from var(--light-angle), transparent 30%, var(--color-accent, #3b82f6) 50%, transparent 70%);
                -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
                -webkit-mask-composite: xor;
                mask-composite: exclude;
                animation: spinLight 3s linear infinite;
                pointer-events: none;
                z-index: 10;
            }
            @keyframes spinLight {
                100% { --light-angle: 360deg; }
            }
        `;
        document.head.appendChild(style);

        const targetClasses = [
            '.gallery-item', '.service-image-box', '.blog-image-box', 
            '.blog-large-image', '.about-intro-image-frame', 
            '.hero-image-frame', '.lightbox-content'
        ];
        
        const containers = document.querySelectorAll(targetClasses.join(', '));
        containers.forEach(container => {
            container.classList.add('light-effect-wrapper');
            // Provide a smooth fallback border-radius if one isn't explicitly set
            const cs = window.getComputedStyle(container);
            if (cs.borderRadius === '0px' || cs.borderRadius === '0') {
                container.style.borderRadius = '12px';
            }
        });
    };
    
    addImageEffects();

    // ==========================================
    // ==========================================
    // 12. Seamless 5-Video Ping-Pong Playlist Manager (0ms Gap & Instant Startup)
    // ==========================================
    const desktopVideos = [
        "Video 1 - Desktop.mp4",
        "Video 2 - Desktop.mp4",
        "Video 3 - Desktop.mp4",
        "Video 4 - Desktop.mp4",
        "Video 5 - Desktop.mp4"
    ];

    const mobileVideos = [
        "Video 1 - Mobile.mp4",
        "Video 2 - Mobile.mp4",
        "Video 3 - Mobile.mp4",
        "Video 4 - Mobile.mp4",
        "Video 5 - Mobile.mp4"
    ];

    function initSeamlessVideoPlaylist(activeId, hiddenId, playlist) {
        const playerA = document.getElementById(activeId);
        const playerB = document.getElementById(hiddenId);
        if (!playerA || !playerB) return;

        let activePlayer = playerA;
        let hiddenPlayer = playerB;
        let activeIndex = 0;
        let isTransitioning = false;

        // Ensure active player is playing initial video immediately
        activePlayer.muted = true;
        activePlayer.play().catch(function() {
            activePlayer.muted = true;
            activePlayer.play();
        });

        // Preload next video into hidden player
        function prepareHiddenPlayer(nextIdx) {
            hiddenPlayer.src = encodeURI(playlist[nextIdx]);
            hiddenPlayer.muted = true;
            hiddenPlayer.preload = "auto";
            hiddenPlayer.load();
        }

        // Initially prepare Video 2 in hidden player
        prepareHiddenPlayer((activeIndex + 1) % playlist.length);

        function triggerTransition() {
            if (isTransitioning) return;
            isTransitioning = true;

            const nextIndex = (activeIndex + 1) % playlist.length;

            // Start playing preloaded hidden player
            hiddenPlayer.muted = true;
            const playPromise = hiddenPlayer.play();

            const doSwap = function() {
                // Instantly swap classes
                hiddenPlayer.classList.remove('video-hidden');
                hiddenPlayer.classList.add('video-active');

                activePlayer.classList.remove('video-active');
                activePlayer.classList.add('video-hidden');

                // Swap references
                const temp = activePlayer;
                activePlayer = hiddenPlayer;
                hiddenPlayer = temp;

                activeIndex = nextIndex;
                isTransitioning = false;

                // Preload the following video into the new hidden player
                prepareHiddenPlayer((activeIndex + 1) % playlist.length);

                // Attach event listener to new active player
                attachActiveListeners(activePlayer);
            };

            if (playPromise !== undefined) {
                playPromise.then(doSwap).catch(function() {
                    hiddenPlayer.muted = true;
                    hiddenPlayer.play().then(doSwap).catch(doSwap);
                });
            } else {
                doSwap();
            }
        }

        function attachActiveListeners(player) {
            player.onended = function() {
                triggerTransition();
            };
            
            // Fail-safe check near end
            player.ontimeupdate = function() {
                if (player.duration && player.currentTime >= player.duration - 0.08) {
                    triggerTransition();
                }
            };
        }

        attachActiveListeners(activePlayer);

        activePlayer.onerror = function() {
            triggerTransition();
        };
        hiddenPlayer.onerror = function() {
            prepareHiddenPlayer((activeIndex + 1) % playlist.length);
        };
    }

    if (window.innerWidth > 768) {
        initSeamlessVideoPlaylist('heroDesktopVideoA', 'heroDesktopVideoB', desktopVideos);
    } else {
        initSeamlessVideoPlaylist('heroMobileVideoA', 'heroMobileVideoB', mobileVideos);
    }



    // ==============================================================================
    // 13. Smart Video Lazy Loader (Zero Initial Bandwidth, Full Original HD Quality)
    // ==============================================================================
    (function initSmartVideoLazyLoading() {
        const isMobile = window.innerWidth <= 768;

        // A. Footer Background Videos
        const footerVideos = document.querySelectorAll('video.footer-bg-video');
        if (footerVideos.length > 0) {
            const footerObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        const video = entry.target;
                        const isDesktopVid = video.classList.contains('footer-bg-video-desktop');
                        const isMobileVid = video.classList.contains('footer-bg-video-mobile');

                        // Only load matching device video
                        if ((isDesktopVid && isMobile) || (isMobileVid && !isMobile)) {
                            observer.unobserve(video);
                            return;
                        }

                        // Populate sources with data-src
                        const sources = video.querySelectorAll('source[data-src]');
                        if (sources.length > 0) {
                            sources.forEach(function(srcEl) {
                                srcEl.src = srcEl.getAttribute('data-src');
                                srcEl.removeAttribute('data-src');
                            });
                            video.load();
                        } else if (video.hasAttribute('data-src')) {
                            video.src = video.getAttribute('data-src');
                            video.removeAttribute('data-src');
                            video.load();
                        }

                        video.muted = true;
                        const p = video.play();
                        if (p !== undefined) {
                            p.catch(function() {
                                video.muted = true;
                                video.play();
                            });
                        }
                        observer.unobserve(video);
                    }
                });
            }, { rootMargin: '400px 0px' });

            footerVideos.forEach(function(v) {
                footerObserver.observe(v);
            });
        }

        // B. Regional Hero Videos (Chennai, Bangalore, Kochi, Tuticorin)
        const regionalVideos = document.querySelectorAll('section.regional-hero video.hero-bg-video');
        regionalVideos.forEach(function(video) {
            const isDesktopVid = video.classList.contains('hero-bg-video-desktop');
            const isMobileVid = video.classList.contains('hero-bg-video-mobile');
            if ((isDesktopVid && isMobile) || (isMobileVid && !isMobile)) {
                video.pause();
                video.removeAttribute('src');
                const sources = video.querySelectorAll('source');
                sources.forEach(function(s) {
                    s.removeAttribute('src');
                });
            } else {
                video.muted = true;
                const p = video.play();
                if (p !== undefined) {
                    p.catch(function() { video.muted = true; video.play(); });
                }
            }
        });
    })();

});
