// School and department data
const schoolData = [
    {
        "school": "School of Engineering",
        "departments": ["Computer Science and Engineering (CSE)", "Electrical and Electronic Engineering (EEE)"]
    },
    {
        "school": "School of Business",
        "departments": ["BRAC Business School (BBS)"]
    },
    {
        "school": "School of Law",
        "departments": ["Law"]
    },
    {
        "school": "School of Humanities and Social Sciences",
        "departments": ["English and Humanities (ENH)", "Economics and Social Sciences (ESS)"]
    },
    {
        "school": "School of Data and Sciences",
        "departments": ["Mathematics and Natural Sciences (MNS)", "Pharmacy"]
    },
    {
        "school": "James P. Grant School of Public Health",
        "departments": ["Public Health"]
    },
    {
        "school": "BRAC Institute of Governance and Development (BIGD)",
        "departments": ["Governance and Development Studies"]
    },
    {
        "school": "BRAC Institute of Languages (BIL)",
        "departments": ["Language and Communication Studies"]
    },
    {
        "school": "School of Architecture and Design",
        "departments": ["Architecture (ARCH)"]
    }
];

// Initialize schools dropdown
function initializeSchools() {
    const schoolSelect = document.getElementById('school');
    if (!schoolSelect) {
        console.error('School select element not found');
        return;
    }
    
    // Clear existing options
    schoolSelect.innerHTML = '<option value="">Select School</option>';
    
    // Add school options
    schoolData.forEach(school => {
        const option = document.createElement('option');
        option.value = school.school;
        option.textContent = school.school;
        schoolSelect.appendChild(option);
    });
}

// Update departments based on selected school
function updateDepartments() {
    const schoolSelect = document.getElementById('school');
    const departmentSelect = document.getElementById('department');
    
    if (!schoolSelect || !departmentSelect) {
        console.error('School or department select elements not found');
        return;
    }
    
    const selectedSchool = schoolSelect.value;
    console.log('Selected school:', selectedSchool);

    // Clear existing options
    departmentSelect.innerHTML = '<option value="">Select Department</option>';

    // Find selected school data
    const school = schoolData.find(s => s.school === selectedSchool);
    if (school) {
        school.departments.forEach(dept => {
            const option = document.createElement('option');
            option.value = dept;
            option.textContent = dept;
            departmentSelect.appendChild(option);
        });
        console.log('Departments loaded:', school.departments);
    }
}

// Toggle password visibility
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.nextElementSibling;
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    }
}

// Handle form submission
document.getElementById('registerForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    if (password !== confirmPassword) {
        alert('Passwords do not match!');
        return;
    }

    const formData = {
        full_name: document.getElementById('fullName').value,
        student_id: document.getElementById('studentId').value,
        email: document.getElementById('email').value,
        password: password,
        department: document.getElementById('department').value,
        avatar_url: 'https://avatar.iran.liara.run/public',
        bio: ''
    };

    try {
        const response = await fetch('api/auth/register.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (data.status === 'success') {
            alert('Registration successful! Please login.');
            window.location.href = 'login.html';
        } else {
            alert(data.message || 'Registration failed. Please try again.');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again later.');
    }
});

// Initialize schools when the page loads
document.addEventListener('DOMContentLoaded', () => {
    console.log('Initializing registration form...');
    initializeSchools();
    // Add initial call to updateDepartments to ensure department dropdown is in sync
    updateDepartments();
}); 