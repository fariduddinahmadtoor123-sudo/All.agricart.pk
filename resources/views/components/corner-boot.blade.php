<script>
    (() => {
        const saved = localStorage.getItem('desk-corners');

        if (saved === 'soft' || saved === 'square' || saved === 'smooth' || saved === 'pill') {
            document.documentElement.dataset.corners = saved;
        }
    })();
</script>
