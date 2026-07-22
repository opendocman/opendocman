// Bootstrap 5 Modern UI — app.js
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss alerts after 5 seconds
    var alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Initialize Tom Select on all multi-selects
    document.querySelectorAll('select[multiple]').forEach(function(el) {
        new TomSelect(el, { plugins: ['remove_button'] });
    });
});
