<script>
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.getAttribute('data-password-toggle'));

            if (! input) {
                return;
            }

            const show = input.type === 'password';

            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-pressed', show ? 'true' : 'false');
            button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');

            const open = button.querySelector('[data-eye-open]');
            const shut = button.querySelector('[data-eye-shut]');

            if (open) {
                open.hidden = show;
            }

            if (shut) {
                shut.hidden = ! show;
            }
        });
    });
</script>
