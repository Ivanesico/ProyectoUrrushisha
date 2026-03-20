<footer class="bg-body-tertiary border-top border-secondary-subtle py-3 w-100">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center">
            <span class="text-secondary small">BlaBlaPark &copy; {{ date('Y') }}</span>

            <div class="d-flex gap-3">
                <a href="#" class="text-secondary text-decoration-none small footer-link">Privacidad</a>
                <a href="#" class="text-secondary text-decoration-none small footer-link">Términos</a>
                <a href="#" class="text-secondary text-decoration-none small footer-link">Contacto</a>
            </div>
        </div>
    </div>
</footer>

<style>
    .footer-link {
        transition: color 0.2s ease-in-out;
    }

    .footer-link:hover {
        color: var(--bs-emphasis-color) !important;
        text-decoration: underline !important;
    }
</style>
