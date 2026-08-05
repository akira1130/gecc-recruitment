// ============================================
// GECC Admin Dashboard
// ============================================

let applicants = [];
let filteredApplicants = [];
let currentApplicant = null;

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadApplicants();
    setupEventListeners();
    updateStats();
    renderTable();
});

// Load applicants from server (MySQL)
function loadApplicants() {
    const apiPath = window.location.hostname === 'localhost' ? '/gecc/save-application-mysql.php' : '/save-application-mysql.php';
    fetch(apiPath)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                applicants = data.applications || [];
                filteredApplicants = [...applicants].sort((a, b) => new Date(b.appliedAt) - new Date(a.appliedAt));
                updateStats();
                renderTable();
            }
        })
        .catch(error => console.error('Error loading applications:', error));
}

// Setup Event Listeners
function setupEventListeners() {
    // Sidebar filters
    document.querySelectorAll('.menu-item').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelectorAll('.menu-item').forEach(m => m.classList.remove('active'));
            this.classList.add('active');
            const filter = this.dataset.filter;
            filterApplicants(filter);
        });
    });

    // Search
    document.getElementById('searchInput').addEventListener('input', function() {
        searchApplicants(this.value);
    });

    // Sort buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const sort = this.dataset.sort;
            sortApplicants(sort);
        });
    });

    // Modal close buttons
    document.querySelector('.modal-close').addEventListener('click', closeModal);
    document.getElementById('closeDetailBtn').addEventListener('click', closeModal);

    // Approve button
    document.getElementById('approveBtn').addEventListener('click', function() {
        if (currentApplicant) {
            currentApplicant.status = 'approved';
            currentApplicant.reviewedAt = new Date().toISOString();
            saveAndRefresh();
            closeModal();
            alert('Applicant approved successfully!');
        }
    });

    // Reject button
    document.getElementById('rejectBtn').addEventListener('click', function() {
        document.getElementById('rejectModal').classList.add('show');
    });

    // Confirm reject
    document.getElementById('confirmRejectBtn').addEventListener('click', function() {
        if (currentApplicant) {
            const reason = document.getElementById('rejectionReason').value;
            if (!reason) {
                alert('Please enter a reason for rejection');
                return;
            }
            currentApplicant.status = 'rejected';
            currentApplicant.rejectionReason = reason;
            currentApplicant.reviewedAt = new Date().toISOString();
            saveAndRefresh();
            closeAllModals();
            alert('Applicant rejected successfully!');
        }
    });

    document.getElementById('cancelRejectBtn').addEventListener('click', function() {
        document.getElementById('rejectModal').classList.remove('show');
    });

    document.querySelector('#rejectModal .modal-close').addEventListener('click', function() {
        document.getElementById('rejectModal').classList.remove('show');
    });
}

// Filter applicants
function filterApplicants(filter) {
    if (filter === 'all') {
        filteredApplicants = [...applicants];
    } else {
        filteredApplicants = applicants.filter(app => app.status === filter);
    }
    renderTable();
}

// Search applicants
function searchApplicants(query) {
    const searchTerm = query.toLowerCase();
    filteredApplicants = applicants.filter(app =>
        app.fullName.toLowerCase().includes(searchTerm) ||
        app.email.toLowerCase().includes(searchTerm) ||
        app.phone.includes(searchTerm)
    );
    renderTable();
}

// Sort applicants
function sortApplicants(sort) {
    if (sort === 'newest') {
        filteredApplicants.sort((a, b) => new Date(b.appliedAt) - new Date(a.appliedAt));
    } else {
        filteredApplicants.sort((a, b) => new Date(a.appliedAt) - new Date(b.appliedAt));
    }
    renderTable();
}

// Update statistics
function updateStats() {
    document.getElementById('totalApplicants').textContent = applicants.length;
    document.getElementById('pendingCount').textContent = applicants.filter(a => a.status === 'pending').length;
    document.getElementById('approvedCount').textContent = applicants.filter(a => a.status === 'approved').length;
}

// Render table
function renderTable() {
    const tbody = document.getElementById('applicantsTableBody');
    const emptyState = document.getElementById('emptyState');

    if (filteredApplicants.length === 0) {
        tbody.innerHTML = '';
        emptyState.classList.add('show');
        return;
    }

    emptyState.classList.remove('show');
    tbody.innerHTML = filteredApplicants.map(app => `
        <tr>
            <td><strong>${app.fullName}</strong></td>
            <td>${app.email}</td>
            <td>${app.phone}</td>
            <td>${getExperienceLabel(app.experience)}</td>
            <td>
                ${app.resume ? '<span class="resume-badge">📄 Yes</span>' : '<span class="resume-badge empty">No</span>'}
            </td>
            <td>
                <span class="status-badge status-${app.status}">
                    ${getStatusLabel(app.status)}
                </span>
            </td>
            <td>${formatDate(app.appliedAt)}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn-small btn-view" onclick="viewApplicant('${app.email}')">View</button>
                    ${app.resume ? `<button class="btn-small btn-download" onclick="downloadResume('${app.email}')">Resume</button>` : ''}
                </div>
            </td>
        </tr>
    `).join('');
}

// View applicant details
function viewApplicant(email) {
    const app = applicants.find(a => a.email === email);
    if (!app) return;

    currentApplicant = app;

    document.getElementById('modalName').textContent = app.fullName;
    document.getElementById('modalFullName').textContent = app.fullName;
    document.getElementById('modalEmail').textContent = app.email;
    document.getElementById('modalPhone').textContent = app.phone;
    document.getElementById('modalExperience').textContent = getExperienceLabel(app.experience);
    document.getElementById('modalBackground').textContent = app.background;
    document.getElementById('modalTerms').textContent = app.terms ? '✓ Yes' : '✗ No';

    const resumeDiv = document.getElementById('modalResume');
    if (app.resume) {
        // If it's a file path (from PHP), create a download link
        if (app.resume.startsWith('uploads/')) {
            resumeDiv.innerHTML = `<a href="${app.resume}" download class="resume-link">📄 Download Resume</a>`;
        } else {
            // If it's a data URL (from localStorage), create a download link
            resumeDiv.innerHTML = `<a href="${app.resume}" download class="resume-link">📄 Download Resume</a>`;
        }
    } else {
        resumeDiv.innerHTML = '<p style="color: #ef4444;">No resume uploaded</p>';
    }

    document.getElementById('detailModal').classList.add('show');
}

// Download resume
function downloadResume(email) {
    const app = applicants.find(a => a.email === email);
    if (app && app.resume) {
        // If it's a file path (from PHP), download directly
        if (app.resume.startsWith('uploads/')) {
            const link = document.createElement('a');
            link.href = app.resume;
            link.download = `${app.email}_resume`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        } else {
            // If it's a data URL (from localStorage), open in new tab
            window.open(app.resume, '_blank');
        }
    }
}

// Close modal
function closeModal() {
    document.getElementById('detailModal').classList.remove('show');
    currentApplicant = null;
}

// Close all modals
function closeAllModals() {
    document.getElementById('detailModal').classList.remove('show');
    document.getElementById('rejectModal').classList.remove('show');
    currentApplicant = null;
}

// Save and refresh
function saveAndRefresh() {
    const apiPath = window.location.hostname === 'localhost' ? '/gecc/save-application-mysql.php' : '/save-application-mysql.php';
    const applicationId = currentApplicant.id;
    const status = currentApplicant.status;
    const rejectionReason = currentApplicant.rejectionReason || '';
    const fullName = currentApplicant.fullName;
    const email = currentApplicant.email;

    fetch(apiPath, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${applicationId}&status=${status}&rejectionReason=${encodeURIComponent(rejectionReason)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Send email notification
            sendEmailNotification(status, fullName, email, rejectionReason);
            loadApplicants();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update application');
    });
}

// Send email notification
function sendEmailNotification(status, fullName, email, reason = '') {
    const apiPath = window.location.hostname === 'localhost' ? '/gecc/send-notification.php' : '/send-notification.php';
    fetch(apiPath, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            status: status,
            fullName: fullName,
            email: email,
            reason: reason
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Email sent successfully to: ' + email);
        } else {
            console.log('Email notification: ' + (data.message || 'Unable to send email'));
        }
    })
    .catch(error => {
        console.error('Email Error:', error);
    });
}

// Helper functions
function getExperienceLabel(exp) {
    const labels = {
        '0-1': 'Beginner (0-1 years)',
        '1-3': 'Intermediate (1-3 years)',
        '3-5': 'Experienced (3-5 years)',
        '5+': 'Expert (5+ years)'
    };
    return labels[exp] || exp;
}

function getStatusLabel(status) {
    const labels = {
        'pending': '⏳ Pending',
        'approved': '✅ Approved',
        'rejected': '❌ Rejected'
    };
    return labels[status] || status;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Logout
document.querySelector('.admin-logout').addEventListener('click', function() {
    if (confirm('Are you sure you want to logout?')) {
        // Clear any admin session data here
        window.location.href = 'index.html';
    }
});

// Auto-load on page load
window.addEventListener('load', function() {
    loadApplicants();
    filteredApplicants = [...applicants].sort((a, b) => new Date(b.appliedAt) - new Date(a.appliedAt));
    updateStats();
    renderTable();
});
