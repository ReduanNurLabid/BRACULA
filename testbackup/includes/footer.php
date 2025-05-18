    </div><!-- End container -->
    
    <footer class="bg-light py-3 mt-5">
        <div class="container">
            <p class="text-center text-muted">BRACULA Test Environment</p>
        </div>
    </footer>

    <!-- Bootstrap and JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Activate the current page in the navbar
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname;
            const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
            
            navLinks.forEach(link => {
                if (currentPage.includes(link.getAttribute('href').split('/').pop())) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html> 