document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    
    console.log('Login script loaded');
    
    loginForm.addEventListener('submit', async function(event) {
        event.preventDefault();
        console.log('Login form submitted');

        // Get form data
        const formData = {
            email: document.getElementById('email').value,
            password: document.getElementById('password').value
        };
        
        console.log('Submitting login with email:', formData.email);

        try {
            console.log('Sending request to:', '../api/auth/login.php');
            const response = await fetch('../api/auth/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData),
                credentials: 'include' // Include cookies for session handling
            });
            
            console.log('Response status:', response.status);
            
            // Check if the response is JSON
            const contentType = response.headers.get('content-type');
            console.log('Response content type:', contentType);
            
            if (contentType && contentType.includes('application/json')) {
                const data = await response.json();
                console.log('Response data:', data);
                
                if (response.ok) {
                    // Store user data in localStorage
                    localStorage.setItem('user', JSON.stringify(data.user));
                    
                    console.log('Login successful, session established');
                    
                    // Redirect to feed page
                    window.location.href = 'feed.html';
                } else {
                    alert(data.message || 'Login failed. Please try again.');
                }
            } else {
                const text = await response.text();
                console.error('Received non-JSON response:', text);
                alert('Server returned an unexpected response format. Please try again later.');
            }
        } catch (error) {
            console.error('Error during login:', error);
            alert('An error occurred during login. Please try again later.');
        }
    });

    // Toggle password visibility
    const togglePassword = document.querySelector('.toggle-password');
    const passwordInput = document.getElementById('password');

    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Toggle icon
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }
}); 