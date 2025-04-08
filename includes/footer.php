</main>

    <!-- Footer -->
    <footer class="bg-white shadow-inner mt-auto">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-500">
                    &copy; <?php echo date('Y'); ?> Platformă B2B. Toate drepturile rezervate.
                </div>
                <div class="text-sm text-gray-500">
                    <a href="#" class="text-gray-500 hover:text-blue-600">Termeni și condiții</a> | 
                    <a href="#" class="text-gray-500 hover:text-blue-600">Politica de confidențialitate</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- jQuery and custom scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Toggle mobile menu
        $('.mobile-menu-button').on('click', function() {
            $('#mobile-menu').toggleClass('hidden');
            $('.mobile-menu-button svg').toggleClass('hidden');
        });

        // Toggle user dropdown
        $('.dropdown-toggle').on('click', function() {
            $('.dropdown-menu').toggleClass('hidden');
        });

        // Hide dropdown when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.dropdown-toggle').length && !$(e.target).closest('.dropdown-menu').length) {
                $('.dropdown-menu').addClass('hidden');
            }
        });
    </script>
    <script src="<?php echo $basePath; ?>assets/js/main.js"></script>
</body>
</html>