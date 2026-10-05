# Bazar Universitário

Plataforma web para estudantes da UNAERP **doarem e trocarem** livros, eletrônicos, materiais de estudo e móveis. O aluno anuncia um item (com foto opcional), os colegas manifestam interesse e o dono vê o nome e o e-mail dos interessados para combinar a entrega. Desenvolvido em **PHP 8 puro com arquitetura MVC** (sem frameworks), **orientação a objetos** (classe abstrata `Item` com as subclasses `ItemDoacao` e `ItemTroca`), **MySQL via PDO** e **Bootstrap 5** nas cores da UNAERP.

 **Aplicação em produção:** http://bazar-universitario.infinityfree.me

---

## Como rodar localmente (XAMPP)

1. **Copiar o projeto** para `C:\xampp\htdocs\bazar-universitario\` (a pasta `public` deve ficar em `htdocs\bazar-universitario\public`).
2. **Iniciar** o Apache e o MySQL no XAMPP Control Panel.
3. **Criar o banco** em http://localhost/phpmyadmin → aba **Importar**:
   - `sql/schema.sql` (cria o banco `bazar_universitario`, as tabelas e as categorias);
   - `sql/dados_demo.sql` (opcional: contas e itens de exemplo).
4. **Acessar** http://localhost/bazar-universitario/public/

As credenciais do banco ficam em `config/database.php` e já vêm com o padrão do XAMPP (usuário `root`, sem senha).

---

## Deploy na nuvem (InfinityFree)

A aplicação está publicada no **InfinityFree**, uma hospedagem gratuita que já fornece Apache, PHP e MySQL.

- **Arquivos:** o projeto foi enviado inteiro para a pasta `htdocs/` da hospedagem.
- **Pasta pública:** apenas `public/` é acessível pela web. O `.htaccess` da raiz encaminha os acessos para `public/` e bloqueia (erro 403) as pastas internas `config/`, `src/` e `sql/`. O `public/.htaccess` direciona todas as rotas para o `index.php` (Front Controller).
- **Banco de dados:** criado no painel do InfinityFree. As tabelas foram importadas pelo phpMyAdmin a partir de `sql/schema.sql`, sem as linhas `CREATE DATABASE` e `USE`, porque a hospedagem define o nome do banco. Os dados de conexão do servidor foram ajustados em `config/database.php`.
- **URLs:** o sistema calcula o endereço base sozinho (`BASE_URL`), então o mesmo código funciona no XAMPP e na nuvem sem alterar nenhuma rota.

---

## Contas de demonstração

Criadas pelo script `sql/dados_demo.sql`. A senha de todas é **`bazar123`**.

| E-mail | Perfil |
|---|---|
| `ana@unaerp.br` | Anunciante de doações e trocas, já com interessados |
| `bruno@unaerp.br` | Interessado em itens da Ana |
| `carla@unaerp.br` | Anunciante e interessada |
