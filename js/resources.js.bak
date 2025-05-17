document.addEventListener('DOMContentLoaded', () => {
    // DOM Element Selections
    const uploadMaterialModal = document.getElementById('uploadMaterialModal');
    const uploadMaterialBtn = document.querySelector('.upload-material-btn');
    const closeModalBtn = uploadMaterialModal.querySelector('.close-modal');
    const uploadForm = document.getElementById('uploadForm');
    const searchInput = document.getElementById('searchInput');
    const searchButton = document.getElementById('searchButton');
    const applyFiltersButton = document.querySelector('.apply-filters');
    const materialsGrid = document.querySelector('.materials-grid');

    // Base URL for API calls
    const BASE_URL = window.location.origin + '/BRACULA';

    // Add console log to verify initialization
    console.log('Resources page initialized');

    // Load materials on page load with debug logging
    async function loadMaterials(filters = {}) {
        try {
            console.log('Fetching materials with filters:', filters);
            const queryParams = new URLSearchParams(filters).toString();
            const url = `${BASE_URL}/api/get_materials.php${queryParams ? '?' + queryParams : ''}`;
            console.log('Fetching from URL:', url);

            const response = await fetch(url);
            const data = await response.json();
            console.log('Received data:', data);

            if (data.status === 'success') {
                console.log('Rendering materials:', data.data);
                renderMaterials(data.data);
            } else {
                throw new Error(data.message || 'Failed to load materials');
            }
        } catch (error) {
            console.error('Error loading materials:', error);
            materialsGrid.innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    Error loading materials. Please try again later.
                </div>
            `;
        }
    }

    // Render Materials Function
    function renderMaterials(materials) {
        console.log('Starting render with materials:', materials);
        materialsGrid.innerHTML = ''; // Clear existing materials

        if (!Array.isArray(materials) || materials.length === 0) {
            console.log('No materials to display');
            materialsGrid.innerHTML = '<div class="no-materials">No materials found</div>';
            return;
        }

        materials.forEach((material, index) => {
            console.log(`Rendering material ${index + 1}:`, material);
            const materialCard = document.createElement('div');
            materialCard.classList.add('material-card');
            
            // Get file type icon based on material type
            const fileIcon = getFileTypeIcon(material.fileType);
            
            materialCard.innerHTML = `
                <div class="material-info">
                    <h4>${material.courseCode || 'No Course Code'}</h4>
                    <p><i class="${fileIcon}"></i> ${material.fileName || 'Unnamed File'}</p>
                    <p><i class="fas fa-calendar"></i> ${material.semester || 'No Semester'}</p>
                    <p><i class="fas fa-download"></i> ${material.downloads || 0} downloads</p>
                    <p><i class="fas fa-user"></i> ${material.uploaderName || 'Anonymous'}</p>
                    <p><i class="fas fa-clock"></i> ${formatDate(material.uploadDate)}</p>
                </div>
                <div class="material-actions">
                    <button class="download-button" data-id="${material.id}">
                        <i class="fas fa-download"></i> Download
                    </button>
                </div>
            `;
            materialsGrid.appendChild(materialCard);
        });

        console.log('Finished rendering materials');
    }

    // Helper function to get appropriate file icon
    function getFileTypeIcon(fileType) {
        switch(fileType.toLowerCase()) {
            case 'pdf':
                return 'fas fa-file-pdf';
            case 'slides':
                return 'fas fa-file-powerpoint';
            case 'notes':
                return 'fas fa-file-alt';
            case 'past_paper':
                return 'fas fa-file-contract';
            default:
                return 'fas fa-file';
        }
    }

    // Helper function to format date
    function formatDate(dateString) {
        if (!dateString) return 'No date';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        } catch (error) {
            console.error('Error formatting date:', error);
            return 'Invalid date';
        }
    }

    // Add event delegation for download buttons
    materialsGrid.addEventListener('click', (e) => {
        const downloadButton = e.target.closest('.download-button');
        if (downloadButton) {
            handleDownload(e);
        }
    });

    // Download Material Handler
    function handleDownload(event) {
        const materialId = event.target.closest('.download-button').dataset.id;
        
        // Create a temporary anchor element
        const downloadLink = document.createElement('a');
        downloadLink.href = `${BASE_URL}/api/download_material.php?id=${materialId}`;
        downloadLink.target = '_blank'; // Optional: open in new tab
        
        // Append to body, click, and remove
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    }

    // Modal Functionality
    function openUploadModal() {
        uploadMaterialModal.style.display = 'block';
    }

    function closeUploadModal() {
        uploadMaterialModal.style.display = 'none';
    }

    // Upload Material Handler
    async function handleUploadMaterial(e) {
        e.preventDefault();
        
        const file = document.getElementById('file').files[0];
        const courseCode = document.getElementById('courseCodeUpload').value;
        const semester = document.getElementById('semesterUpload').value;
        const materialType = document.getElementById('materialType').value;

        if (file) {
            try {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('courseCode', courseCode);
                formData.append('semester', semester);
                formData.append('materialType', materialType);
                
                // Get user ID from localStorage
                const userData = JSON.parse(localStorage.getItem('user'));
                if (!userData || !userData.user_id) {
                    throw new Error('Please log in to upload materials');
                }
                formData.append('userId', userData.user_id);

                const response = await fetch(`${BASE_URL}/api/upload_material.php`, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (data.status === 'success') {
                    // Close modal and reset form
                    closeUploadModal();
                    uploadForm.reset();

                    // Show success message
                    alert('Material uploaded successfully!');

                    // Reload materials
                    loadMaterials();
                } else {
                    throw new Error(data.message || 'Failed to upload material');
                }
            } catch (error) {
                alert(error.message);
            }
        }
    }

    // Initial load with debug message
    console.log('Initiating initial materials load');
    loadMaterials();

    // Filter Materials
    function applyFilters() {
        const courseCode = document.getElementById('courseCode').value;
        const semester = document.getElementById('semester').value;
        const fileType = document.getElementById('fileType').value;

        const filters = {};
        if (courseCode) filters.courseCode = courseCode;
        if (semester) filters.semester = semester;
        if (fileType) filters.fileType = fileType;

        loadMaterials(filters);
    }

    // Search Materials
    function searchMaterials() {
        const searchTerm = searchInput.value;
        if (searchTerm) {
            loadMaterials({ courseCode: searchTerm });
        } else {
            loadMaterials();
        }
    }

    // Event Listeners
    uploadMaterialBtn.addEventListener('click', openUploadModal);
    closeModalBtn.addEventListener('click', closeUploadModal);
    uploadForm.addEventListener('submit', handleUploadMaterial);
    
    // Close modal when clicking outside
    window.addEventListener('click', (event) => {
        if (event.target === uploadMaterialModal) {
            closeUploadModal();
        }
    });

    // Search and Filter Event Listeners
    searchButton.addEventListener('click', searchMaterials);
    applyFiltersButton.addEventListener('click', applyFilters);
});