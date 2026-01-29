# Projeto Joana - Catálogo de Referências Bíblicas

Este projeto é um sistema web desenvolvido em Symfony para catalogar, gerenciar, revisar e aprovar referências bíblicas encontradas nas obras de Joanna de Angelis. O sistema permite o cruzamento de dados entre livros da autora e versículos bíblicos, com um fluxo robusto de auditoria e mensageria interna.

## 🛠 Stack Tecnológico

- **Backend**: PHP 8.2+, Symfony 6/7
- **Database**: Doctrine ORM (MySQL/PostgreSQL/SQLite)
- **Frontend**: Twig, Tailwind CSS, DaisyUI
- **Assets**: AssetMapper (sem necessidade de build complexo de node)
- **API**: Endpoints internos para busca dinâmica de textos bíblicos

## 🧩 Arquitetura e Módulos

### 1. Gestão de Referências (`JoannaReference`)
O núcleo do sistema. Conecta uma `JoannaWork` (Obra) a um `BibleBook` (Livro Bíblico).
- **Campos**: Capítulo da obra, Citação, Tipo (Direta, Indireta, Epígrafe), Intervalo de versículos.
- **Funcionalidades**:
    - Listagem filtrável (por Obra, Livro, Autor).
    - CRUD completo para Editores e Admins.
    - Integração via API para carregar o texto bíblico original dinamicamente na visualização.

### 2. Sistema de Aprovação (`ReferenceApproval`)
Implementa um fluxo de "Peer Review" (Revisão por Pares) para garantir a qualidade das referências cadastradas.
- **Entidade**: `ReferenceApproval` (User + Reference + DateTime).
- **Regras de Negócio**:
    1. **Autoridade**: Um usuário **não pode** aprovar sua própria referência.
    2. **Unicidade**: Um usuário só pode aprovar uma referência uma única vez.
    3. **Contagem**: O nível de confiança da referência é baseado no `count` de aprovações.
- **Feedback Visual**:
    - Extension Twig (`ApprovalExtension`) gera uma escala de calor baseada no número de aprovações.
    - **0 a 11+**: Varia de `bg-white` a `bg-green-950`.
    - Badges na listagem e detalhe indicam o status.

### 3. Sistema de Mensagens (`Message`)
Chat contextual atrelado a cada referência para discutir divergências ou correções.
- **Contexto**: Mensagens são linkadas a uma `JoannaReference`.
- **Integridade**: Configurado com `ON DELETE CASCADE`. Se a referência for apagada, todo o histórico de conversa associado é removido automaticamente.
- **Interface**: Chat estilo "messenger" disponível na visualização da referência.

### 4. Controle de Acesso (RBAC)
O sistema utiliza a hierarquia de segurança do Symfony:
- **ROLE_EDITOR**: Pode criar referências, editar **apenas** as suas, aprovar referências de terceiros.
- **ROLE_ADMIN**: Acesso total ao sistema, pode editar/apagar qualquer registro.
- **ROLE_DEV**: Acesso a ferramentas de desenvolvimento e configurações sensíveis.

## 💻 Comandos CLI (Console)

O projeto possui comandos personalizados para importação e manutenção de dados.

### Importação de Aprovações
Importa um CSV de histórico de aprovações (legado), realizando match fuzzy de livros e obras.

```bash
php bin/console app:import-approvals
```
*Lê o arquivo `docs/aprovados.csv` e insere aprovações para o usuário Denise Lino (ID 5), tratando erros de digitação (ex: "Filipensens") e buscas parciais.*

## 📂 Estrutura de Diretórios Importante

- `src/Entity/Joanna`: Entidades principais (`JoannaReference`, `JoannaWork`, `ReferenceApproval`).
- `src/Controller/Editor`: Controllers voltados para o fluxo de trabalho dos colaboradores.
  - `JoannaReferenceController`: Lógica de listagem, criaçao, aprovação e remoção.
- `src/Controller/Admin`: Controllers administrativos.
- `src/Twig`: Extensions personalizadas (ex: `ApprovalExtension.php` para lógica de cores).
- `templates/joanna_reference`: Views de listagem (`index`) e detalhe (`show`).
- `docs/`: Documentação e arquivos CSV para importação.

## 🚀 Como Rodar

1. **Dependências**:
   ```bash
   composer install
   ```

2. **Banco de Dados e Migrations**:
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

3. **Servidor Local**:
   ```bash
   symfony server:start
   # ou
   php -S 127.0.0.1:8000 -t public
   ```
