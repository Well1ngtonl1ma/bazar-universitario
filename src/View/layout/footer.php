    </div><!-- /.container (aberto no header) -->
</main>

<footer class="py-3 mt-auto rodape-bazar">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center text-white small">
        <span>&copy; <?= e(date('Y')) ?> Bazar <span class="brand-destaque">Universitário</span></span>
        <span>Projeto acadêmico &middot; PHP 8 + MVC + POO</span>
    </div>
</footer>

<!-- Bootstrap 5 JS (inclui Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Scripts do projeto em arquivo próprio: a CSP bloqueia <script> inline -->
<script src="<?= url('/assets/js/app.js') ?>"></script>
</body>
</html>
