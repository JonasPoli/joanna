# Documentação Funcional: Áreas de Acesso, Usuários e Mensageria

Este documento detalha o funcionamento atual do sistema, cobrindo hierarquia de usuários, permissões, fluxos de trabalho , sistema de aprovações visual e mensageria.

---

## 1. Usuários e Hierarquia (Grupos de Trabalho)

O sistema utiliza um campo ROLES na entidade Usuário para definir permissões e papéis. Não há roles complexas do Symfony além de `ROLE_EDITOR` e `ROLE_ADMIN`

### Grupo 0: Administrador (Root)
*   **Permissão:** Acesso irrestrito a todo o sistema.
*   **Visibilidade:** Pode ver todos os usuários, todas as mensagens, todas referências
*   **Painel:** Visualiza estatísticas globais e todas as conversas do sistema.

### Grupo 3: Autor de Referências
*   **Responsabilidade:** Criar conteúdo das referências, revisar conteúdo das referências criada por outros autores.
*   **Acesso:**
    *   CRUD de Referências.
    *   Dashboard exibe "Minhas Referências" (filtrado pelo autor).
    Possui 2 comportamentos:
    #### Autor: Quem criou a referência
    Pode editar
    Não pode revisar, nem marcar como revisada
    Não pode enviar mensagem para o autor
    
    #### Revisor: Quando revisa a referência que não é sua
    Pode revisar
    Pode marcar como revisada
    Não pode editar
    Pode enviar mensagem para o autor
    
    

---

## 2. Áreas do Sistema e Navegação

### 2.1 Dashboard (`/admin`)
Ponto de entrada do sistema. Exibe widgets contextuais baseados no grupo:
*   **Usuários:** Contagem de usuaários
*   **Referências:** Contagem de referências com sub contagem das que estão revisadas e das que não estão revisadas
*   **Mensagens:** Widget de mensagens não lidas ou lista de conversas ativas (para Admin/Translator).

### 2.4 Notificações (Sineta)
*   **Localização:** Canto superior direito do cabeçalho (Header).
*   **Funcionalidade:**
    *   Exibe um ícone de sino (`sl-icon-button name="bell"`).
    *   Possui um **Badge Vermelho** (`sl-badge`) indicando o número total de mensagens **não lidas**.
    *   Ao clicar, redireciona para a lista de mensagens (`/admin/message/`), já filtrando as pendências.
*   **Visibilidade:** Visível em todas as páginas da área administrativa (`/admin/*`).



---

## 3. Sistema de Aprovação e Cores (Visual Heatmap)

O sistema visualiza o nível de confiabilidade/Referência de uma referência através de uma escala de cores de fundo (background color). Quanto mais revisores aprovarem uma referência, mais escura a cor verde se torna.

### Lógica de Aprovação
*   Um usuário só pode aprovar uma referência uma única vez.
*   A contagem (`reviewCounts`) soma todas as aprovações únicas naquele referência.

### Escala de Cores (CSS Classes Tailwind)

| Qtd. Aprovações | Cor Visual | Classe CSS | Descrição |
| :--- | :--- | :--- | :--- |
| **0** | Branco/Transparente | `bg-white` ou nula | Nenhuma Referência. |
| **1** | Verde Muito Claro | `bg-green-50` | Início do processo. |
| **2** | Verde Claro | `bg-green-100` | |
| **3** | Verde Suave | `bg-green-200` | |
| **4** | Verde Médio-Claro | `bg-green-300` | |
| **5** | Verde Médio | `bg-green-400` | |
| **6** | Verde Vibrante | `bg-green-500` | Ponta da escala média. |
| **7** | Verde Escuro Suave | `bg-green-600` | |
| **8** | Verde Escuro | `bg-green-700` | |
| **9** | Verde Muito Escuro | `bg-green-800` | |
| **10** | Verde Intenso | `bg-green-900` | Alta confiabilidade. |
| **11+** | Verde Quase Preto | `bg-green-950` | Consenso absoluto (Máximo). |

> **Nota UI:** No layout de "livro", o texto do versículo recebe essa cor de fundo. Se for o primeiro versículo do bloco, o número do capítulo também recebe a cor.

---

## 4. Sistema de Mensageria (Estilo WhatsApp)

O sistema possui um chat interno contextual para comunicação entre revisores e autores.

### 4.1 Estrutura da Mensagem (`Message` Entity)
*   **Remetente/Destinatário:** Usuários do sistema.
*   **Contexto:** Mensagens podem ser vinculadas a:
    *   `translation` (Versículo específico)
    *   `paratext` (Paratexto específico)
*   **Status de Leitura:** `unread`, `read`, `ignored`, `replied`, `resolved`.
*   **Threading:** Suporta respostas (Replies). O sistema exibe a conversa linearmente ordenada por data.

### 4.2 Interface de Usuário (Chat UI)
A interface imita aplicativos de mensagem modernos (como WhatsApp/Telegram) para familiaridade.

#### Mensagens Enviadas (Eu)
*   **Alinhamento:** Direita (`justify-end`).
*   **Estilo:** Balão Azul (`bg-blue-600`), Texto Branco.
*   **Borda:** Arredondada, ponta superior direita reta (`rounded-tr-none`).
*   **Indicadores de Status:**
    *   Icone `check` (Cinza): Enviada.
    *   Icone `check-all` (Azul/Colorido): Lida, Respondida ou Resolvida.

#### Mensagens Recebidas (Outros)
*   **Alinhamento:** Esquerda (`justify-start`).
*   **Estilo:** Balão Branco (`bg-white`), Texto Escuro (`text-slate-800`), Borda Cinza.
*   **Borda:** Arredondada, ponta superior esquerda reta (`rounded-tl-none`).
*   **Cabeçalho:** Nome do remetente em destaque.

### 4.3 Fluxo de Interação
1.  **Iniciar:** Usuário clica no ícone de chat em um versículo. O modal abre já com o contexto preenchido.
2.  **Responder:** No rodapé do modal de leitura, há um campo de texto para resposta rápida.
3.  **Encadeamento:** Respostas são tecnicamente "filhas" da mensagem original, mas visualmente apresentadas em flat-list cronológica para fluidez de leitura.

### 4.4 Gerenciamento de Mensagens (Painel Admin)
*   **Acesso:** Rota `/admin/message/`.
*   **Filtros Disponíveis:**
    *   **Não Resolvidos:** (Padrão) Exibe mensagens `unread`, `read`, `replied` ou `ignored`.
    *   **Resolvida:** Exibe apenas mensagens com status `resolved`.
    *   **Todos:** Exibe todo o histórico de mensagens.
*   **Lista de Conversas:**
    *   Agrupa mensagens por "thread" (conversa).
    *   Exibe contador de mensagens não lidas (Badge verde).
    *   Botão rápido para marcar conversa como **Resolvida**.

### 4.5 Status da Mensagem (`Message` Entity)
Os status controlam o fluxo de vida de uma mensagem:
1.  **unread:** Status inicial ao enviar.
2.  **read:** Quando o destinatário visualiza a mensagem.
3.  **replied:** Quando o destinatário responde.
4.  **ignored:** Pode ser usado para arquivar sem resolver (funcionalidade opcional).

---

## 6. Gestão de Referência 

Módulo dedicado ao conteúdo das revisões

### 6.2 Visualização e Listagem
A listagem de revisões segue o mesmo padrão visual de **Heatmap de Aprovações** da tradução:
*   **Linhas da Tabela:** A linha inteira do registro muda de cor (escala verde) conforme o número de aprovações (`reviewCounts`).
*   isso permite identificar rapidamente quais conteúdos já estão maduros/revisados e quais precisam de atenção.
*   **Colunas:** ID, Quantidade de Aprovações, Obra, Tipo (Badge), Referência Bíblica (Livro/Cap/Ver), Autor e Ações.

### 6.3 Fluxo de Criação e Referência
1.  **Criação (Autor ):**
    *   Preenche os campos necessários
    *   Pode vincular a um Livro, Capítulo e Versículo específico.
2.  **Referência (Revisor):**
    *   Visualiza o conteúdo em modo de leitura.
    *   Pode enviar mensagem direta ao Autor para solicitar correções.
    *   Pode marcar como "Revisado" (Aprovar), incrementando o contador e alterando a cor na lista.
    *   Quando enviar uma mensagem ao autor, o sistems precisa identificar de qual referência está enviando a mensagem.

