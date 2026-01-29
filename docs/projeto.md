# Projeto: Referências Bíblicas na Obra de Joanna de Angelis

Este documento descreve o projeto de desenvolvimento de um sistema para catalogar, gerenciar e pesquisar referências bíblicas encontradas nas obras da autora espiritual Joanna de Angelis.

## 1. Visão Geral
O sistema tem como objetivo centralizar o conhecimento sobre como Joanna de Angelis utiliza a Bíblia em suas obras, permitindo análises estatísticas, pesquisas cruzadas e o estudo aprofundado dessas citações.

## 2. Estrutura de Dados

### 2.1 Dados Bíblicos (Legado)
O sistema importará e manterá uma base de dados bíblica padrão contendo:
*   **Versões da Bíblia**: Traduções disponíveis.
*   **Testamentos**: Antigo e Novo.
*   **Livros**: Gênesis a Apocalipse.
*   **Versículos**: O texto bíblico em si.

### 2.2 Dados da Autora (Joanna de Angelis)
*   **Obras**: Livros publicados pela autora (Título, Ano). Fonte: `docs/obras.csv`.
*   **Referências**: O cruzamento entre a obra de Joanna e a Bíblia. Fonte: `docs/referencias.csv`.
    *   **Obra & Capítulo**: Onde a citação ocorre no livro de Joanna.
    *   **Referência Bíblica**: Livro, Capítulo e Versículos (Início/Fim) citados.
    *   **Tipo de Referência**: Classificação da citação (ex: Direta, Indireta, Alusão).
    *   **Citação**: O texto exato ou contexto utilizado por Joanna.

## 3. Perfis e Permissões de Usuário

O sistema contará com controle de acesso baseado em papéis (RBAC):

### 3.1 Desenvolvedor (`ROLE_DEV`)
*   **Acesso Total (Admin)**.
*   Gerencia Usuários.
*   Gerencia Dados Mestres (Livros, Versículos, Obras).
*   Visualiza e edita qualquer referência.
*   Executa comandos de Importação/Exportação.

### 3.2 Cadastrador/Corretor (`ROLE_EDITOR`)
*   **Foco**: Inserção e Revisão de Referências.
*   **Permissões**:
    *   **Criar**: Pode adicionar novas referências.
    *   **Editar (Próprios)**: Pode editar apenas as referências que ele mesmo criou.
    *   **Visualizar (Todos)**: Pode ver referências de outros usuários.
    *   **Comentar**: Se encontrar erro em referência de outro usuário, pode enviar comentário/notificação (não pode editar diretamente).

## 4. Funcionalidades do Sistema

### 4.1 Importação de Dados
Ferramentas de linha de comando (CLI) para:
*   Importar estrutura bíblica de banco legado.
*   Importar Obras via CSV.
*   Importar Referências iniciais via CSV, realizando a "higienização" e vínculo com os livros bíblicos.

### 4.2 Dashboards
*   **Home**: Apresentação pública do projeto.
*   **Dashboard Admin**: Visão geral de usuários, total de referências, logs de sistema.
*   **Dashboard Usuário**: "Minhas Contribuições", Últimas referências cadastradas, Notificações de correções.

### 4.3 Interface (UI)
*   Login seguro.
*   Formulários padronizados para CRUD (Create, Read, Update, Delete).
*   Todas as listagens (CRUDs) utilizarão **DataTables** para ordenação, filtro e paginação.
*   Design limpo e funcional.

## 5. Plano de Execução

1.  **Modelagem**: Criação das Entidades (User, Book, JoannaWork, JoannaReference).
2.  **Migração**: Scripts para puxar dados do banco legado e CSVs.
3.  **Segurança**: Implementação do sistema de Login e Voters para permissões de edição.
4.  **Interface**: Desenvolvimento das telas e dashboards.
