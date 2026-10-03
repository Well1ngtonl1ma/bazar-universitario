<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Upload seguro de imagens.
 *
 * Por que tantas checagens? Um atacante pode renomear "shell.php" para "foto.jpg"
 * ou mandar um arquivo com "Content-Type: image/png" falso. Por isso NÃO confiamos
 * em nada que vem do navegador ($_FILES['type'] e o nome original):
 *
 *   1. erro do PHP no envio         5. MIME REAL lido dos bytes (finfo)
 *   2. veio mesmo de um upload HTTP 6. extensão bate com o conteúdo
 *   3. tamanho (máx. 2 MB)          7. getimagesize(): é uma imagem de verdade
 *   4. extensão na lista permitida  8. nome NOVO e aleatório, extensão escolhida por nós
 *
 * Além disso, public/uploads/.htaccess impede que qualquer script rode naquela pasta.
 */
final class Upload
{
    /** 2 MB em bytes. */
    public const TAMANHO_MAXIMO = 2 * 1024 * 1024;

    /** MIME real permitido => extensão com que o arquivo será salvo. */
    private const MIMES_PERMITIDOS = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    /** Extensões aceitas no nome original => MIME que o conteúdo precisa ter. */
    private const EXTENSOES_PERMITIDAS = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
    ];

    /** Limite de dimensões, evita imagens gigantes que travam o navegador. */
    private const LADO_MAXIMO_PX = 6000;

    /** Valor para o atributo accept="" do <input type="file">. */
    public const ACCEPT = '.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp';

    public function __construct(private string $pasta)
    {
    }

    /** Pasta padrão do projeto: public/uploads */
    public static function pastaPadrao(): self
    {
        return new self(dirname(__DIR__, 2) . '/public/uploads');
    }

    /**
     * O usuário escolheu algum arquivo? (campo vazio = não é erro, só não há foto)
     *
     * @param array<string, mixed>|null $arquivo item de $_FILES
     */
    public static function foiEnviado(?array $arquivo): bool
    {
        return is_array($arquivo)
            && isset($arquivo['error'])
            && $arquivo['error'] !== UPLOAD_ERR_NO_FILE;
    }

    /**
     * Valida e grava a imagem.
     *
     * @param array<string, mixed> $arquivo item de $_FILES
     * @return string Nome do arquivo salvo (ex.: "a3f9...e1.jpg"), que vai para o banco
     * @throws RuntimeException com mensagem amigável quando algo for inválido
     */
    public function salvar(array $arquivo): string
    {
        // 1) Erros do próprio PHP
        $erro = (int) ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($erro !== UPLOAD_ERR_OK) {
            throw new RuntimeException($this->mensagemErroPhp($erro));
        }

        $tmp = (string) ($arquivo['tmp_name'] ?? '');

        // 2) Garante que o arquivo veio de um upload HTTP (e não de um caminho forjado)
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('Envio de arquivo inválido.');
        }

        // 3) Tamanho medido no disco (não confiamos em $arquivo['size'])
        $tamanho = (int) filesize($tmp);
        if ($tamanho <= 0) {
            throw new RuntimeException('O arquivo enviado está vazio.');
        }
        if ($tamanho > self::TAMANHO_MAXIMO) {
            throw new RuntimeException('A foto deve ter no máximo 2 MB.');
        }

        // 4) Extensão do nome original na lista permitida
        $extensaoOriginal = strtolower(pathinfo((string) ($arquivo['name'] ?? ''), PATHINFO_EXTENSION));
        if (!isset(self::EXTENSOES_PERMITIDAS[$extensaoOriginal])) {
            throw new RuntimeException('Formato não permitido. Envie JPG, PNG ou WEBP.');
        }

        // 5) MIME REAL, descoberto pelos primeiros bytes do arquivo (assinatura)
        $mime = $this->detectarMime($tmp);
        if (!isset(self::MIMES_PERMITIDOS[$mime])) {
            throw new RuntimeException('O conteúdo do arquivo não é uma imagem JPG, PNG ou WEBP.');
        }

        // 6) A extensão precisa corresponder ao conteúdo (ex.: .png com conteúdo de PHP é barrado)
        if (self::EXTENSOES_PERMITIDAS[$extensaoOriginal] !== $mime) {
            throw new RuntimeException('A extensão do arquivo não corresponde ao seu conteúdo.');
        }

        // 7) getimagesize() lê o cabeçalho da imagem: script disfarçado falha aqui
        $info = @getimagesize($tmp);
        if ($info === false || ($info['mime'] ?? '') !== $mime) {
            throw new RuntimeException('A imagem está corrompida ou não é válida.');
        }
        if ($info[0] > self::LADO_MAXIMO_PX || $info[1] > self::LADO_MAXIMO_PX) {
            throw new RuntimeException('A imagem é grande demais (máximo de 6000 px por lado).');
        }

        // 8) Nome aleatório e imprevisível + extensão definida por NÓS, pelo MIME real
        $this->garantirPasta();
        $nomeFinal = bin2hex(random_bytes(16)) . '.' . self::MIMES_PERMITIDOS[$mime];
        $destino   = $this->pasta . '/' . $nomeFinal;

        if (!move_uploaded_file($tmp, $destino)) {
            throw new RuntimeException('Não foi possível salvar a foto. Verifique a permissão da pasta public/uploads.');
        }
        @chmod($destino, 0644); // leitura para o Apache, sem permissão de execução

        return $nomeFinal;
    }

    /**
     * Apaga uma foto antiga. Aceita só nomes no formato gerado por salvar(),
     * o que impede apagar arquivos fora da pasta (ex.: "../../config/database.php").
     */
    public function remover(?string $nomeArquivo): void
    {
        if ($nomeArquivo === null || !preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $nomeArquivo)) {
            return;
        }
        $caminho = $this->pasta . '/' . $nomeArquivo;
        if (is_file($caminho)) {
            @unlink($caminho);
        }
    }

    // ---------------------------------------------------------
    // Auxiliares
    // ---------------------------------------------------------

    private function detectarMime(string $caminho): string
    {
        // finfo (extensão fileinfo) é o método mais confiável
        if (class_exists(\finfo::class)) {
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($caminho);
            return is_string($mime) ? $mime : '';
        }
        // Plano B: getimagesize também lê a assinatura do arquivo
        $info = @getimagesize($caminho);
        return $info['mime'] ?? '';
    }

    private function garantirPasta(): void
    {
        if (!is_dir($this->pasta) && !mkdir($this->pasta, 0755, true) && !is_dir($this->pasta)) {
            throw new RuntimeException('A pasta de uploads não existe e não pôde ser criada.');
        }
        if (!is_writable($this->pasta)) {
            throw new RuntimeException('Sem permissão de escrita em public/uploads.');
        }
    }

    private function mensagemErroPhp(int $codigo): string
    {
        return match ($codigo) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'A foto deve ter no máximo 2 MB.',
            UPLOAD_ERR_PARTIAL    => 'O envio da foto foi interrompido. Tente novamente.',
            UPLOAD_ERR_NO_FILE    => 'Nenhuma foto foi enviada.',
            UPLOAD_ERR_NO_TMP_DIR,
            UPLOAD_ERR_CANT_WRITE => 'O servidor não conseguiu gravar o arquivo temporário.',
            UPLOAD_ERR_EXTENSION  => 'O envio foi bloqueado por uma extensão do PHP.',
            default               => 'Erro desconhecido no envio da foto.',
        };
    }
}
