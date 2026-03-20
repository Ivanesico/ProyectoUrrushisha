<script>
    const alertDiv = document.getElementById('alert');
    const submitBtn = document.getElementById('submitBtn');
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const registerForm = document.getElementById('registerForm');

    function showAlert(message, isError = true) {
        if (!message) {
            alertDiv.classList.add('d-none');
            return;
        }

        alertDiv.textContent = message;
        alertDiv.classList.remove('d-none', 'alert-success', 'alert-danger');
        alertDiv.classList.add(isError ? 'alert-danger' : 'alert-success');
    }

    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        showAlert(null);
        submitBtn.disabled = true;

        try {
            const formData = new FormData(registerForm);

            const res = await fetch('{{ url("/auth/register") }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: formData,
                credentials: 'same-origin'
            });

            const text = await res.text();
            console.log('STATUS:', res.status);
            console.log('RESPUESTA BRUTA:', text);

            let data = {};
            try {
                data = JSON.parse(text);
            } catch (e) {
                throw new Error('La respuesta no es JSON. Seguramente Laravel está devolviendo un error HTML.');
            }

            if (!res.ok) {
                submitBtn.disabled = false;
                const msg = data.errors
                        ? Object.values(data.errors).flat().join(' ')
                        : (data.message || 'Error al registrarse');
                showAlert(msg, true);
                return;
            }

            showAlert(data.message || 'Cuenta creada correctamente', false);

            setTimeout(() => {
                window.location.href = '{{ url("/") }}';
            }, 800);

        } catch (err) {
            submitBtn.disabled = false;
            console.error(err);
            showAlert(err.message || 'Error de conexión.', true);
        }
    });
</script>
