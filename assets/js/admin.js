// Admin Panel JavaScript Functions
document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm-delete') || 'Are you sure you want to delete this item?';
            if (!confirm(message)) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });
    });
    
    // Auto-resize textareas
    const textareas = document.querySelectorAll('textarea[data-auto-resize]');
    textareas.forEach(function(textarea) {
        textarea.addEventListener('input', autoResize, false);
        autoResize.call(textarea);
    });
    
    function autoResize() {
        this.style.height = 'auto';
        this.style.height = this.scrollHeight + 'px';
    }
    
    // Form validation helpers
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                
                // Focus on first invalid field
                const firstInvalid = form.querySelector(':invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                }
            }
            form.classList.add('was-validated');
        });
    });
    
    // Character counter for textareas with maxlength
    const textareasWithLimit = document.querySelectorAll('textarea[maxlength]');
    textareasWithLimit.forEach(function(textarea) {
        const maxLength = textarea.getAttribute('maxlength');
        const counter = document.createElement('small');
        counter.className = 'form-text text-muted character-counter';
        textarea.parentNode.appendChild(counter);
        
        function updateCounter() {
            const remaining = maxLength - textarea.value.length;
            counter.textContent = `${remaining} characters remaining`;
            counter.className = `form-text ${remaining < 50 ? 'text-warning' : 'text-muted'} character-counter`;
        }
        
        textarea.addEventListener('input', updateCounter);
        updateCounter();
    });
    
    // Image preview for file inputs
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    imageInputs.forEach(function(input) {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    let preview = input.parentNode.querySelector('.image-preview');
                    if (!preview) {
                        preview = document.createElement('img');
                        preview.className = 'image-preview img-thumbnail mt-2';
                        preview.style.maxWidth = '200px';
                        preview.style.maxHeight = '200px';
                        input.parentNode.appendChild(preview);
                    }
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    });
    
    // Bulk actions
    const selectAllCheckbox = document.querySelector('#selectAll');
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    const bulkActions = document.querySelector('#bulkActions');
    
    if (selectAllCheckbox && itemCheckboxes.length > 0) {
        selectAllCheckbox.addEventListener('change', function() {
            itemCheckboxes.forEach(function(checkbox) {
                checkbox.checked = selectAllCheckbox.checked;
            });
            toggleBulkActions();
        });
        
        itemCheckboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const checkedItems = document.querySelectorAll('.item-checkbox:checked');
                selectAllCheckbox.checked = checkedItems.length === itemCheckboxes.length;
                toggleBulkActions();
            });
        });
        
        function toggleBulkActions() {
            const checkedItems = document.querySelectorAll('.item-checkbox:checked');
            if (bulkActions) {
                bulkActions.style.display = checkedItems.length > 0 ? 'block' : 'none';
            }
        }
    }
    
    // Search functionality
    const searchInput = document.querySelector('#adminSearch');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                performSearch(searchInput.value);
            }, 300);
        });
    }
    
    function performSearch(query) {
        // This would typically make an AJAX request
        console.log('Searching for:', query);
    }
    
    // Dashboard stats animation
    const statNumbers = document.querySelectorAll('.stat-number');
    statNumbers.forEach(function(stat) {
        const finalValue = parseInt(stat.textContent.replace(/,/g, ''));
        animateNumber(stat, 0, finalValue, 1500);
    });
    
    function animateNumber(element, start, end, duration) {
        const range = end - start;
        const startTime = performance.now();
        
        function animate(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const value = Math.floor(start + range * easeOut(progress));
            element.textContent = value.toLocaleString();
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        }
        
        requestAnimationFrame(animate);
    }
    
    function easeOut(t) {
        return 1 - Math.pow(1 - t, 3);
    }
    
    // AJAX form submission
    const ajaxForms = document.querySelectorAll('.ajax-form');
    ajaxForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            submitAjaxForm(form);
        });
    });
    
    function submitAjaxForm(form) {
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        
        // Show loading state
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', data.message);
                if (data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                }
            } else {
                showAlert('error', data.message || 'An error occurred');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'An error occurred. Please try again.');
        })
        .finally(() => {
            // Reset button
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        });
    }
    
    // Show alert messages
    function showAlert(type, message) {
        const alertClass = type === 'error' ? 'alert-danger' : `alert-${type}`;
        const alertHTML = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        const alertContainer = document.querySelector('.alert-container') || document.querySelector('main');
        if (alertContainer) {
            alertContainer.insertAdjacentHTML('afterbegin', alertHTML);
            
            // Auto dismiss after 5 seconds
            setTimeout(() => {
                const alert = alertContainer.querySelector('.alert');
                if (alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 5000);
        }
    }
    
    // Sidebar toggle for mobile
    const sidebarToggle = document.querySelector('#sidebarToggle');
    const sidebar = document.querySelector('#sidebarMenu');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    }
    
    // Auto-save drafts
    const autoSaveForms = document.querySelectorAll('[data-auto-save]');
    autoSaveForms.forEach(function(form) {
        let autoSaveTimer;
        const inputs = form.querySelectorAll('input, textarea, select');
        
        inputs.forEach(function(input) {
            input.addEventListener('input', function() {
                clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(() => {
                    autoSaveDraft(form);
                }, 5000);
            });
        });
    });
    
    function autoSaveDraft(form) {
        const formData = new FormData(form);
        formData.append('auto_save', '1');
        
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showTempMessage('Draft saved automatically', 'success', 2000);
            }
        })
        .catch(error => {
            console.log('Auto-save failed:', error);
        });
    }
    
    function showTempMessage(message, type, duration = 3000) {
        const messageEl = document.createElement('div');
        messageEl.className = `alert alert-${type} auto-save-message`;
        messageEl.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1060;
            min-width: 250px;
        `;
        messageEl.textContent = message;
        
        document.body.appendChild(messageEl);
        
        setTimeout(() => {
            messageEl.remove();
        }, duration);
    }
    
    // Table sorting
    const sortableHeaders = document.querySelectorAll('.sortable');
    sortableHeaders.forEach(function(header) {
        header.addEventListener('click', function() {
            const table = this.closest('table');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const column = Array.from(this.parentNode.children).indexOf(this);
            const isAscending = !this.classList.contains('sort-asc');
            
            // Remove sort classes from all headers
            sortableHeaders.forEach(h => h.classList.remove('sort-asc', 'sort-desc'));
            
            // Add sort class to current header
            this.classList.add(isAscending ? 'sort-asc' : 'sort-desc');
            
            // Sort rows
            rows.sort((a, b) => {
                const aVal = a.cells[column].textContent.trim();
                const bVal = b.cells[column].textContent.trim();
                
                // Try to parse as numbers
                const aNum = parseFloat(aVal.replace(/[^0-9.-]/g, ''));
                const bNum = parseFloat(bVal.replace(/[^0-9.-]/g, ''));
                
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return isAscending ? aNum - bNum : bNum - aNum;
                }
                
                // Sort as strings
                return isAscending ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
            });
            
            // Reorder rows in DOM
            rows.forEach(row => tbody.appendChild(row));
        });
    });
    
});

// Utility functions
window.AdminUtils = {
    
    // Format numbers
    formatNumber: function(num) {
        return new Intl.NumberFormat().format(num);
    },
    
    // Format dates
    formatDate: function(date, options = {}) {
        const defaultOptions = {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        };
        return new Date(date).toLocaleDateString('en-US', {...defaultOptions, ...options});
    },
    
    // Truncate text
    truncateText: function(text, length = 100) {
        return text.length > length ? text.substring(0, length) + '...' : text;
    },
    
    // Copy to clipboard
    copyToClipboard: function(text) {
        navigator.clipboard.writeText(text).then(() => {
            this.showToast('Copied to clipboard!', 'success');
        }).catch(err => {
            console.error('Failed to copy: ', err);
            this.showToast('Failed to copy', 'error');
        });
    },
    
    // Show toast notification
    showToast: function(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        const toastContainer = document.querySelector('.toast-container') || this.createToastContainer();
        toastContainer.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    },
    
    createToastContainer: function() {
        const container = document.createElement('div');
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(container);
        return container;
    }
    
};

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AdminUtils;
}