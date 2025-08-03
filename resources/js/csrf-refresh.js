// Auto refresh CSRF token setiap 30 menit
setInterval(function() {
    fetch('/csrf-token', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        // Update all CSRF tokens on the page
        document.querySelectorAll('input[name="_token"]').forEach(input => {
            input.value = data.csrf_token;
        });
        
        // Update meta tag
        document.querySelector('meta[name="csrf-token"]').setAttribute('content', data.csrf_token);
        
        // Update axios default header if using axios
        if (window.axios) {
            window.axios.defaults.headers.common['X-CSRF-TOKEN'] = data.csrf_token;
        }
    })
    .catch(error => {
        console.log('Failed to refresh CSRF token:', error);
    });
}, 30 * 60 * 1000); // 30 minutes

// Handle CSRF token mismatch for AJAX requests
document.addEventListener('DOMContentLoaded', function() {
    // Intercept all form submissions
    document.addEventListener('submit', function(e) {
        const form = e.target;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        if (csrfToken) {
            let tokenInput = form.querySelector('input[name="_token"]');
            if (!tokenInput) {
                tokenInput = document.createElement('input');
                tokenInput.type = 'hidden';
                tokenInput.name = '_token';
                form.appendChild(tokenInput);
            }
            tokenInput.value = csrfToken;
        }
    });
});
