<script>
    // Máscara de telefone brasileiro: (49) 99999-9999 ou (49) 3333-4444.
    // A validação definitiva é feita no servidor (App\Rules\BrazilianPhone).
    (function () {
        function formatPhone(value) {
            let d = value.replace(/\D/g, '');
            if (d.length > 11 && d.startsWith('55')) d = d.slice(2);
            d = d.slice(0, 11);
            if (d.length <= 2) return d.length ? '(' + d : '';
            if (d.length <= 6) return '(' + d.slice(0, 2) + ') ' + d.slice(2);
            if (d.length <= 10) return '(' + d.slice(0, 2) + ') ' + d.slice(2, 6) + '-' + d.slice(6);
            return '(' + d.slice(0, 2) + ') ' + d.slice(2, 7) + '-' + d.slice(7);
        }

        document.querySelectorAll('input[data-phone-mask]').forEach(function (input) {
            if (input.value) input.value = formatPhone(input.value);
            input.addEventListener('input', function () {
                this.value = formatPhone(this.value);
            });
        });
    })();
</script>
