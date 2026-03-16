    </div><!-- /#main -->

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sb-overlay').classList.toggle('open');
        }
        function closeSidebar() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('sb-overlay').classList.remove('open');
        }

        // Conferma eliminazione
        document.querySelectorAll('.confirm-delete').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Sei sicuro di voler eliminare questo elemento? L\'operazione non è reversibile.')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
