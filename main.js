// ============================================
// GECC - Main JavaScript
// Scroll animations and form handling
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    // Scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe feature cards
    document.querySelectorAll('.feature-card').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'all 0.6s ease';
        observer.observe(el);
    });

    document.querySelectorAll('.curriculum-item').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'all 0.6s ease';
        observer.observe(el);
    });

    document.querySelectorAll('.benefit-item').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'all 0.6s ease';
        observer.observe(el);
    });

    // Form handling
    const form = document.querySelector('#applicationForm');
    if (form) {
        const fileInput = form.querySelector('input[type="file"]');
        const uploadLabel = form.querySelector('.upload-label');

        // File upload click
        if (uploadLabel && fileInput) {
            uploadLabel.addEventListener('click', () => fileInput.click());

            fileInput.addEventListener('change', (e) => {
                if (e.target.files[0]) {
                    const fileName = e.target.files[0].name;
                    const fileSize = (e.target.files[0].size / 1024).toFixed(2);
                    uploadLabel.innerHTML = `
                        <span class="upload-text">✓ ${fileName}</span>
                        <span class="upload-info">${fileSize} KB</span>
                    `;
                    uploadLabel.style.borderColor = '#28a745';
                    uploadLabel.style.background = 'rgba(40,167,69,0.05)';
                }
            });
        }

        // Form submission
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            
            if (!fileInput || !fileInput.files.length) {
                alert('Please upload your CV or Resume');
                return;
            }

            // Create FormData to send files and form data
            const formData = new FormData(form);
            
            // Determine correct API path based on environment
            let apiPath;
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                apiPath = '/gecc/save-application-mysql.php';
            } else {
                // On Railway, use the full protocol and host
                apiPath = window.location.protocol + '//' + window.location.host + '/save-application-mysql.php';
            }
            
            console.log('Form submission to:', apiPath);
            
            // Submit to backend
            fetch(apiPath, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error(`HTTP ${response.status}: ${text}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    alert('Thank you for your application! We will review your CV and contact you soon.');
                    form.reset();
                    uploadLabel.innerHTML = `
                        <span class="upload-text">Upload CV or Resume</span>
                        <span class="upload-info">(PDF, DOC, DOCX)</span>
                    `;
                    uploadLabel.style.borderColor = '';
                    uploadLabel.style.background = '';
                } else {
                    alert('Error: ' + (data.message || 'Failed to submit application'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error submitting application: ' + error.message);
            });
        });
    }

    // Smooth scroll for links
    document.querySelectorAll('a[href^="#"]').forEach(link => {
        link.addEventListener('click', (e) => {
            const href = link.getAttribute('href');
            if (href !== '#' && document.querySelector(href)) {
                e.preventDefault();
                document.querySelector(href).scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    console.log('GECC website loaded successfully');
});
