document.addEventListener('DOMContentLoaded', () => {
    // Get DOM elements
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirmPassword');
    const requirements = document.getElementById('passwordRequirements');
    const signupForm = document.getElementById('signupForm');
    const confirmPasswordError = document.getElementById('confirmPasswordError');

    // Show password requirements when password field is focused
    password.addEventListener('focus', () => {
        requirements.style.display = 'block';
    });

    // Validate password as user types
    password.addEventListener('input', validatePassword);
    
    // Check if passwords match as user types in confirm password
    confirmPassword.addEventListener('input', () => {
        if (password.value !== confirmPassword.value) {
            confirmPasswordError.style.display = 'block';
        } else {
            confirmPasswordError.style.display = 'none';
        }
    });

    // Handle form submission
    signupForm.addEventListener('submit', async function(event) {
        event.preventDefault();

        // Validate password match
        if (password.value !== confirmPassword.value) {
            alert('Please ensure that your passwords match.');
            return;
        }

        // Validate email format
        const email = document.getElementById('email').value;
        if (!email.endsWith('@g.bracu.ac.bd')) {
            alert('Please use your BRACU G Suite email (@g.bracu.ac.bd)');
            return;
        }

        // Get form data
        const formData = {
            full_name: document.getElementById('fullName').value,
            student_id: document.getElementById('studentID').value,
            email: email,
            password: password.value,
            department: document.getElementById('department').value,
            avatar_url: 'https://avatar.iran.liara.run/public', // Default avatar
            bio: ''
        };

        try {
            // Direct URL to the API endpoint
            const response = await fetch('api/register.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            let data;
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            } else {
                const responseText = await response.text();
                console.error('Server response:', responseText);
                throw new Error('Server response was not JSON');
            }

            if (response.ok) {
                alert('Registration successful! Please login.');
                window.location.href = 'login.html';
            } else {
                // Handle specific error messages
                const errorMessage = data.message || 'Registration failed. Please try again.';
                if (data.required_fields) {
                    alert(`${errorMessage}\nRequired fields: ${data.required_fields.join(', ')}`);
                } else {
                    alert(errorMessage);
                }
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while connecting to the server. Please try again later. Error: ' + error.message);
        }
    });
});

// Password validation function
function validatePassword() {
    const passwordValue = password.value;
    const requirements = {
        length: document.getElementById('length'),
        uppercase: document.getElementById('uppercase'),
        lowercase: document.getElementById('lowercase'),
        number: document.getElementById('number'),
        special: document.getElementById('special')
    };

    // Check each requirement
    updateRequirement(requirements.length, passwordValue.length >= 8);
    updateRequirement(requirements.uppercase, /[A-Z]/.test(passwordValue));
    updateRequirement(requirements.lowercase, /[a-z]/.test(passwordValue));
    updateRequirement(requirements.number, /[0-9]/.test(passwordValue));
    updateRequirement(requirements.special, /[\W_]/.test(passwordValue));

    // Return true if all requirements are met
    return Object.values(requirements).every(req => 
        req.classList.contains('met')
    );
}

// Helper function to update requirement status
function updateRequirement(element, isValid) {
    if (element) {
        element.innerHTML = `<i class="fas fa-${isValid ? 'check' : 'times'}"></i>${element.textContent.slice(1)}`;
        element.classList.toggle('met', isValid);
    }
}

// Toggle password visibility
function togglePassword(id) {
    const passwordField = document.getElementById(id);
    const toggleIcon = passwordField.nextElementSibling;
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    } else {
        passwordField.type = 'password';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    }
}