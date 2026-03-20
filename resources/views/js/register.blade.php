<script>
    const alertDiv = document.getElementById('alert');
    const submitBtn = document.getElementById('submitBtn');
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const registerForm = document.getElementById('registerForm');

    function showAlert(message, isError = true) {
        if (message == null) {
            alertDiv.classList.add('d-none')
            return;
        };

        alertDiv.textContent = message;
        alertDiv.classList.remove('d-none', 'alert-success', 'alert-danger');
        alertDiv.classList.add(isError ? 'alert-danger' : 'alert-success');
    }

    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        showAlert(null);
        submitBtn.disabled = true;

        try {
            const res = await fetch('{{ url("/auth/register") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({
                    name: document.getElementById('name').value.trim(),
                    email: document.getElementById('email').value.trim(),
                    password: document.getElementById('password').value,
                    password_confirmation: document.getElementById('password_confirmation').value
                }),
                credentials: 'include'
            });

            const data = await res.json()

            if (!res.ok) {
                submitBtn.disabled = false;
                const msg = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Error al registrarse');
                showAlert(msg, true);
                return;
            }

            showAlert(data.message || 'Cuenta creada correctamente', false);
            setTimeout(() => {
                window.location.href = '{{ url("/") }}';
            }, 800);
        } catch (err) {
            submitBtn.disabled = false;
            showAlert('Error de conexión.', true);
        }
    });
</script>
