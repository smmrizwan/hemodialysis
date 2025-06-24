/**
 * Form Management and Utilities for Dialysis Management System
 */

// Global form state management
const FormManager = {
    currentPatientId: null,
    unsavedChanges: false,
    
    // Initialize form
    init: function(patientId = null) {
        this.currentPatientId = patientId;
        this.setupFormValidation();
        this.setupUnsavedChangesWarning();
        this.setupAutosave();
    },
    
    // Set up form validation
    setupFormValidation: function() {
        const forms = document.querySelectorAll('form[data-validate="true"]');
        forms.forEach(form => {
            form.addEventListener('submit', this.validateForm.bind(this));
        });
    },
    
    // Set up unsaved changes warning
    setupUnsavedChangesWarning: function() {
        const inputs = document.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('change', () => {
                this.unsavedChanges = true;
            });
        });
        
        window.addEventListener('beforeunload', (e) => {
            if (this.unsavedChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    },
    
    // Setup autosave functionality
    setupAutosave: function() {
        if (this.currentPatientId) {
            setInterval(() => {
                if (this.unsavedChanges) {
                    this.autosaveForm();
                }
            }, 300000); // Autosave every 5 minutes
        }
    },
    
    // Validate form before submission
    validateForm: function(e) {
        const form = e.target;
        let isValid = true;
        
        // Check required fields
        const requiredFields = form.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                this.showFieldError(field, 'This field is required');
                isValid = false;
            } else {
                this.clearFieldError(field);
            }
        });
        
        // Validate specific field types
        const emailFields = form.querySelectorAll('input[type="email"]');
        emailFields.forEach(field => {
            if (field.value && !this.isValidEmail(field.value)) {
                this.showFieldError(field, 'Please enter a valid email address');
                isValid = false;
            }
        });
        
        const phoneFields = form.querySelectorAll('input[pattern*="[0-9]"]');
        phoneFields.forEach(field => {
            if (field.value && !field.value.match(field.pattern)) {
                this.showFieldError(field, 'Please enter a valid phone number');
                isValid = false;
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            this.scrollToFirstError();
        }
        
        return isValid;
    },
    
    // Show field error
    showFieldError: function(field, message) {
        field.classList.add('is-invalid');
        
        let feedback = field.parentNode.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            field.parentNode.appendChild(feedback);
        }
        feedback.textContent = message;
    },
    
    // Clear field error
    clearFieldError: function(field) {
        field.classList.remove('is-invalid');
        const feedback = field.parentNode.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.remove();
        }
    },
    
    // Scroll to first error
    scrollToFirstError: function() {
        const firstError = document.querySelector('.is-invalid');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstError.focus();
        }
    },
    
    // Validate email
    isValidEmail: function(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },
    
    // Autosave form data
    autosaveForm: function() {
        // Implementation for autosaving form data
        console.log('Autosaving form data...');
    },
    
    // Reset unsaved changes flag
    resetUnsavedChanges: function() {
        this.unsavedChanges = false;
    }
};

// Patient selection utilities
const PatientSelector = {
    // Load patient list
    loadPatients: function(selectElementId, selectedPatientId = null) {
        fetch('api/get_patient_data.php?action=list')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const select = document.getElementById(selectElementId);
                    if (select) {
                        select.innerHTML = '<option value="">Choose a patient...</option>';
                        data.patients.forEach(patient => {
                            const option = document.createElement('option');
                            option.value = patient.id;
                            option.textContent = `${patient.name_english} (${patient.file_number})`;
                            if (selectedPatientId && patient.id == selectedPatientId) {
                                option.selected = true;
                            }
                            select.appendChild(option);
                        });
                    }
                }
            })
            .catch(error => {
                console.error('Error loading patients:', error);
                this.showAlert('Error loading patient list', 'danger');
            });
    },
    
    // Get patient details
    getPatientDetails: function(patientId, callback) {
        if (!patientId) return;
        
        fetch(`api/get_patient_data.php?action=details&id=${patientId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && callback) {
                    callback(data.patient);
                }
            })
            .catch(error => {
                console.error('Error getting patient details:', error);
            });
    }
};

// Form submission utilities
const FormSubmitter = {
    // Submit form via AJAX
    submitForm: function(formElement, successCallback = null, errorCallback = null) {
        const formData = new FormData(formElement);
        const action = formElement.getAttribute('action');
        
        if (!action) {
            console.error('Form action not specified');
            return;
        }
        
        // Show loading state
        this.setLoadingState(formElement, true);
        
        fetch(action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            this.setLoadingState(formElement, false);
            
            if (data.success) {
                this.showAlert(data.message || 'Data saved successfully', 'success');
                FormManager.resetUnsavedChanges();
                if (successCallback) successCallback(data);
            } else {
                this.showAlert(data.message || 'An error occurred', 'danger');
                if (errorCallback) errorCallback(data);
            }
        })
        .catch(error => {
            this.setLoadingState(formElement, false);
            console.error('Form submission error:', error);
            this.showAlert('Network error occurred. Please try again.', 'danger');
            if (errorCallback) errorCallback(error);
        });
    },
    
    // Set loading state for form
    setLoadingState: function(formElement, isLoading) {
        const submitBtn = formElement.querySelector('button[type="submit"]');
        if (submitBtn) {
            if (isLoading) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
            } else {
                submitBtn.disabled = false;
                submitBtn.innerHTML = submitBtn.getAttribute('data-original-text') || 'Save';
            }
        }
    },
    
    // Show alert message
    showAlert: function(message, type = 'info') {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.form-alert');
        existingAlerts.forEach(alert => alert.remove());
        
        // Create new alert
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show form-alert`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Insert alert at the top of the main content
        const main = document.querySelector('main');
        if (main) {
            main.insertBefore(alert, main.firstChild);
        }
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }
};

// Dynamic form utilities
const DynamicForm = {
    // Add new row to a dynamic table
    addTableRow: function(tableId, template) {
        const table = document.getElementById(tableId);
        if (!table) return;
        
        const tbody = table.querySelector('tbody');
        if (!tbody) return;
        
        const rowCount = tbody.children.length;
        const newRow = document.createElement('tr');
        newRow.innerHTML = template.replace(/\{index\}/g, rowCount);
        
        tbody.appendChild(newRow);
        
        // Initialize any new form elements
        this.initializeFormElements(newRow);
    },
    
    // Remove table row
    removeTableRow: function(button) {
        const row = button.closest('tr');
        if (row) {
            row.remove();
        }
    },
    
    // Initialize form elements in a container
    initializeFormElements: function(container) {
        // Initialize select2, datepickers, etc.
        const selects = container.querySelectorAll('select');
        selects.forEach(select => {
            // Add any initialization logic for selects
        });
        
        const dateInputs = container.querySelectorAll('input[type="date"]');
        dateInputs.forEach(input => {
            // Add any initialization logic for date inputs
        });
    },
    
    // Clone form section
    cloneFormSection: function(sectionId, containerId) {
        const section = document.getElementById(sectionId);
        const container = document.getElementById(containerId);
        
        if (!section || !container) return;
        
        const clone = section.cloneNode(true);
        clone.id = sectionId + '_' + Date.now();
        
        // Clear values in cloned section
        const inputs = clone.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            if (input.type === 'checkbox' || input.type === 'radio') {
                input.checked = false;
            } else {
                input.value = '';
            }
        });
        
        container.appendChild(clone);
        this.initializeFormElements(clone);
    }
};

// Data validation utilities
const DataValidator = {
    // Validate file number format
    validateFileNumber: function(fileNumber) {
        const pattern = /^[0-9]{5,11}$/;
        return pattern.test(fileNumber);
    },
    
    // Validate contact number
    validateContactNumber: function(contactNumber) {
        const pattern = /^[0-9]{10}$/;
        return pattern.test(contactNumber);
    },
    
    // Validate date range
    validateDateRange: function(startDate, endDate) {
        if (!startDate || !endDate) return true;
        return new Date(startDate) <= new Date(endDate);
    },
    
    // Validate numerical range
    validateNumberRange: function(value, min, max) {
        const num = parseFloat(value);
        return !isNaN(num) && num >= min && num <= max;
    },
    
    // Validate required fields in a group
    validateRequiredGroup: function(container) {
        const requiredFields = container.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                FormSubmitter.showFieldError(field, 'This field is required');
                isValid = false;
            }
        });
        
        return isValid;
    }
};

// Local storage utilities for form data
const FormStorage = {
    // Save form data to local storage
    saveFormData: function(formId, data) {
        try {
            localStorage.setItem(`form_${formId}`, JSON.stringify(data));
        } catch (error) {
            console.error('Error saving form data to localStorage:', error);
        }
    },
    
    // Load form data from local storage
    loadFormData: function(formId) {
        try {
            const data = localStorage.getItem(`form_${formId}`);
            return data ? JSON.parse(data) : null;
        } catch (error) {
            console.error('Error loading form data from localStorage:', error);
            return null;
        }
    },
    
    // Clear form data from local storage
    clearFormData: function(formId) {
        try {
            localStorage.removeItem(`form_${formId}`);
        } catch (error) {
            console.error('Error clearing form data from localStorage:', error);
        }
    },
    
    // Auto-save form data
    enableAutoSave: function(formId, interval = 30000) {
        const form = document.getElementById(formId);
        if (!form) return;
        
        setInterval(() => {
            const formData = new FormData(form);
            const data = {};
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            this.saveFormData(formId, data);
        }, interval);
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize form manager
    const patientId = new URLSearchParams(window.location.search).get('patient_id') || 
                     new URLSearchParams(window.location.search).get('edit');
    FormManager.init(patientId);
    
    // Setup form submission handlers
    const forms = document.querySelectorAll('form[data-ajax="true"]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            FormSubmitter.submitForm(this);
        });
    });
    
    // Setup patient selectors
    const patientSelects = document.querySelectorAll('.patient-selector');
    patientSelects.forEach(select => {
        PatientSelector.loadPatients(select.id);
    });
    
    // Setup dynamic form handlers
    document.addEventListener('click', function(e) {
        // Handle add row buttons
        if (e.target.classList.contains('add-row-btn')) {
            e.preventDefault();
            const tableId = e.target.getAttribute('data-table');
            const template = e.target.getAttribute('data-template');
            if (tableId && template) {
                DynamicForm.addTableRow(tableId, template);
            }
        }
        
        // Handle remove row buttons
        if (e.target.classList.contains('remove-row-btn')) {
            e.preventDefault();
            DynamicForm.removeTableRow(e.target);
        }
    });
});

// Export utilities for global use
window.FormManager = FormManager;
window.PatientSelector = PatientSelector;
window.FormSubmitter = FormSubmitter;
window.DynamicForm = DynamicForm;
window.DataValidator = DataValidator;
window.FormStorage = FormStorage;
