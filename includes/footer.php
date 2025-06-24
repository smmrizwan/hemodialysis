    </main>

    <!-- Footer -->
    <footer class="bg-light text-center text-muted py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <p class="mb-2">
                        <i class="fas fa-heartbeat text-primary me-2"></i>
                        Dialysis Patient Management System
                    </p>
                    <p class="mb-0">
                        <small>
                            &copy; <?php echo date('Y'); ?> - Comprehensive Patient Care Management
                            <span class="ms-3">
                                <i class="fas fa-shield-alt text-success me-1"></i>
                                Secure & Confidential
                            </span>
                        </small>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="assets/js/calculations.js"></script>
    <script src="assets/js/forms.js"></script>
    
    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <script>
    // Global search function
    function searchPatients() {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Search Patients</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Search by File Number or Name</label>
                            <input type="text" class="form-control" id="globalSearchInput" placeholder="Enter file number or patient name">
                        </div>
                        <div id="globalSearchResults"></div>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        
        // Search functionality
        document.getElementById('globalSearchInput').addEventListener('input', function() {
            const query = this.value;
            if (query.length > 2) {
                fetch(`api/get_patient_data.php?search=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        const resultsDiv = document.getElementById('globalSearchResults');
                        if (data.success && data.patients.length > 0) {
                            resultsDiv.innerHTML = data.patients.map(patient => `
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">${patient.name_english}</h6>
                                        <small class="text-muted">File: ${patient.file_number}</small>
                                    </div>
                                    <div class="btn-group">
                                        <a href="patient_report.php?id=${patient.id}" class="btn btn-sm btn-primary">View</a>
                                        <a href="patient_form.php?edit=${patient.id}" class="btn btn-sm btn-secondary">Edit</a>
                                    </div>
                                </div>
                            `).join('');
                        } else {
                            resultsDiv.innerHTML = '<p class="text-muted">No patients found</p>';
                        }
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        document.getElementById('globalSearchResults').innerHTML = '<p class="text-danger">Error searching patients</p>';
                    });
            } else {
                document.getElementById('globalSearchResults').innerHTML = '';
            }
        });
        
        // Remove modal from DOM when closed
        modal.addEventListener('hidden.bs.modal', function() {
            document.body.removeChild(modal);
        });
    }

    // Global error handler
    window.addEventListener('error', function(e) {
        console.error('Global error:', e.error);
    });

    // Show loading spinner for form submissions
    function showLoading(show = true) {
        let spinner = document.getElementById('loadingSpinner');
        if (show && !spinner) {
            spinner = document.createElement('div');
            spinner.id = 'loadingSpinner';
            spinner.className = 'loading-overlay';
            spinner.innerHTML = `
                <div class="spinner-border loading-spinner" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            `;
            document.body.appendChild(spinner);
        } else if (!show && spinner) {
            document.body.removeChild(spinner);
        }
    }
    </script>
</body>
</html>
