/**
 * Scripts do Bazar Universitário.
 * Ficam em arquivo separado porque a Content-Security-Policy (index.php)
 * bloqueia JavaScript inline: um <script> injetado por XSS não executaria.
 *
 * Importante: estas checagens são só CONFORTO para o usuário.
 * A validação que vale de verdade está no servidor (src/Core/Upload.php).
 */
document.addEventListener('DOMContentLoaded', () => {
    const TAMANHO_MAXIMO = 2 * 1024 * 1024; // 2 MB
    const TIPOS_ACEITOS = ['image/jpeg', 'image/png', 'image/webp'];

    // -------------------------------------------------------------
    // Pré-visualização da foto escolhida no formulário de item
    // -------------------------------------------------------------
    const input = document.getElementById('imagem');
    const preview = document.getElementById('preview-imagem');
    const aviso = document.getElementById('aviso-imagem');
    const removerFoto = document.getElementById('remover_imagem');

    if (input && preview) {
        const srcOriginal = preview.getAttribute('src');
        let urlTemporaria = null;

        const mostrarAviso = (mensagem) => {
            if (!aviso) return;
            aviso.textContent = mensagem;
            aviso.classList.toggle('d-none', mensagem === '');
        };

        input.addEventListener('change', () => {
            if (urlTemporaria) {
                URL.revokeObjectURL(urlTemporaria); // libera a memória da prévia anterior
                urlTemporaria = null;
            }

            const arquivo = input.files && input.files[0];
            if (!arquivo) {
                preview.src = srcOriginal;
                mostrarAviso('');
                return;
            }

            if (!TIPOS_ACEITOS.includes(arquivo.type)) {
                mostrarAviso('Formato não permitido. Escolha JPG, PNG ou WEBP.');
                input.value = '';
                preview.src = srcOriginal;
                return;
            }
            if (arquivo.size > TAMANHO_MAXIMO) {
                mostrarAviso('A foto tem ' + (arquivo.size / 1024 / 1024).toFixed(1) + ' MB. O máximo é 2 MB.');
                input.value = '';
                preview.src = srcOriginal;
                return;
            }

            mostrarAviso('');
            urlTemporaria = URL.createObjectURL(arquivo);
            preview.src = urlTemporaria;
            if (removerFoto) removerFoto.checked = false; // escolheu foto nova: não faz sentido remover
        });
    }

    // -------------------------------------------------------------
    // Confirmação antes de ações irreversíveis (data-confirmar="mensagem")
    // -------------------------------------------------------------
    document.querySelectorAll('form[data-confirmar]').forEach((form) => {
        form.addEventListener('submit', (evento) => {
            if (!window.confirm(form.dataset.confirmar)) {
                evento.preventDefault();
            }
        });
    });
});
