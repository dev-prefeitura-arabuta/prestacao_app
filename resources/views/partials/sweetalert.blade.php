<?php
$flashSuccess = session('success');
$flashError   = session('error');
$flashErrors  = session('errors_list');
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if (! empty($flashSuccess) || ! empty($flashError) || (! empty($flashErrors) && is_array($flashErrors))): ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof Swal === 'undefined') return;

        <?php if (! empty($flashSuccess)): ?>
            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: <?= json_encode((string) $flashSuccess, JSON_UNESCAPED_UNICODE) ?>,
                confirmButtonColor: '#dd4814',
                timer: 3200,
                timerProgressBar: true
            });
        <?php elseif (! empty($flashError)): ?>
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: <?= json_encode((string) $flashError, JSON_UNESCAPED_UNICODE) ?>,
                confirmButtonColor: '#dd4814'
            });
        <?php elseif (! empty($flashErrors) && is_array($flashErrors)): ?>
            Swal.fire({
                icon: 'error',
                title: 'Verifique os dados informados',
                html: <?= json_encode('<ul style="text-align:left;margin-left:1.2rem;line-height:1.6;"><li>' . implode('</li><li>', array_map('esc', $flashErrors)) . '</li></ul>', JSON_UNESCAPED_UNICODE) ?>,
                confirmButtonColor: '#dd4814'
            });
        <?php endif; ?>
    });
</script>
<?php endif; ?>

