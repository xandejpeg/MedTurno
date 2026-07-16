# 05 — Telas

Mapa completo de telas da v1. Nomenclatura de URL só sugestão.

Formato de cada tela: **URL · Quem acessa · O quê mostra · Ações**.

---

## Públicas

### `/` — Landing/redirecionamento
Se logado → redireciona pro dashboard do papel principal. Se não → `/login`.

### `/login`
- Campo email + senha
- Link "Esqueci minha senha"
- Botão "Entrar"

### `/senha/esqueci`
- Campo email
- "Enviamos um link se o email existir"

### `/senha/redefinir?token=XXX`
- Campo nova senha + confirmação
- Salvar → login

### `/convite/aceitar?token=XXX`
- Mostra email + hospital que convidou
- Se usuário novo: pede nome + senha
- Se email já existe: pede login e vincula
- Aceitar → dashboard

---

## Gestor

Layout tem sempre no topo:
- Logo
- **Seletor de hospital** (dropdown com hospitais que gerencia — "Santa Maria" / "São Gabriel")
- Sino de notificações
- Menu usuário (perfil, sair)

Sidebar/menu:
- Painel
- Equipe médica
- Quadros
- Escalas
- Trocas & Solicitações
- Faturamento

### `/gestor` — Painel
Cards resumo do hospital selecionado:
- Escala do mês atual (rascunho / publicada / faltando)
- Plantões pendentes de confirmação
- Trocas aguardando aprovação
- Interesses no mural
- Últimas atividades (feed simples)

### `/gestor/hospitais`
- Lista de hospitais que gerencia
- Botão "+ Novo hospital"
- Editar / desativar

### `/gestor/hospitais/novo` e `/gestor/hospitais/:id/editar`
Formulário: nome, CNPJ opcional, endereço, telefone.

### `/gestor/equipe`
Lista de médicos do hospital atual:
- Filtro: ativos / pendentes / inativos
- Colunas: nome, especialidade, CRM, quadros, status
- Ações: reenviar convite, remover do hospital, adicionar a quadro

### `/gestor/equipe/convidar`
Formulário: nome, email, telefone, CRM opcional, especialidade opcional.
Ao salvar → cria convite + envia email.

### `/gestor/quadros`
Lista de quadros do hospital atual + botão "+ Novo quadro".

### `/gestor/quadros/:id`
Editor do quadro. Duas abas:
1. **Estrutura** (templates de turno): grade semanal 7 dias × faixas horárias. Botões "aplicar grade automática (6h / 12h / 24h)" e "adicionar turno manual".
2. **Participantes**: lista de médicos do hospital com checkbox pra participar.

### `/gestor/quadros/:id/templates/novo`
Modal: dias da semana, hora início, hora fim, nº vagas, **valor do plantão (R$)**, rótulo opcional.

### `/gestor/recorrencias`
Lista de recorrências por médico. "+ Nova recorrência":
- Escolhe médico
- Escolhe template
- Semanal ou quinzenal
- Data de referência

### `/gestor/escalas`
Lista de escalas (todas as versões, todos os quadros do hospital atual).
- Filtro por mês/ano, quadro, status
- Botão "+ Nova escala"

### `/gestor/escalas/nova`
- Escolhe quadro + mês/ano
- Sistema pré-preenche baseado nas recorrências
- Redireciona pro editor

### `/gestor/escalas/:id`
**Tela mais crítica do app.** Calendário mensal:
- No desktop: grade 7 colunas × ~5 linhas, cada célula tem os plantões do dia
- No celular: lista/dia com navegação semanal
- Cada célula do plantão mostra: turno + nome do médico (ou "vago")
- Clicar em plantão vazio → modal atribuir médico
- Clicar em plantão preenchido → modal detalhes (mudar médico, remover, notas)
- Botão "Publicar" (só aparece se status = rascunho)
- Botão "Nova versão" (se já publicada — cria v2)

### `/gestor/trocas`
Duas listas:
1. **Trocas diretas** aguardando aprovação (receptor já aceitou)
2. **Mural** — plantões anunciados com nº de interessados

Ações: Aprovar / Rejeitar / Ver detalhe.

### `/gestor/trocas/:id`
Detalhe da troca: quem, quando, motivo, histórico.

### `/gestor/faturamento`
Relatório mensal de valores a pagar:
- Filtro: mês/ano + hospital (ou consolidado "todos")
- Tabela por médico: nome, nº de plantões, total R$ do mês
- Expandir linha → detalhe de cada plantão (data, turno, hospital, valor)
- Total geral do mês no rodapé
- (v2: botão exportar PDF/CSV)

### `/gestor/notificacoes`
Lista de notificações recebidas.

### `/gestor/perfil`
Nome, telefone, alterar senha.

---

## Médico

Layout tem sempre no topo:
- Logo
- **Seletor de hospital** (só se atua em >1) — ou "Todos os hospitais"
- Sino de notificações
- Menu usuário

Menu bottom-nav no celular:
- Escala
- Mural
- Trocas
- Perfil

### `/medico` — Painel
- **Próximo plantão** (card destacado com contagem regressiva)
- **Total a receber no mês** (card com soma dos plantões do mês corrente)
- Plantões pendentes de confirmação
- Trocas aguardando resposta
- Notificações recentes

### `/medico/escala`
Calendário mensal com **os plantões do médico** (todos os hospitais em que atua, ou filtrado).
- Cores: amarelo (pendente), verde (confirmado), vermelho (anunciado), azul (em troca)
- Filtro por hospital / quadro
- Toggle "Ver escala completa do meu quadro" (mostra colegas também)

### `/medico/plantoes/:id`
Detalhe do plantão — mostra data, horário, hospital, quadro, **valor (R$)** e status. Ações disponíveis dependem do status:
- Se pendente: **Confirmar** / **Não posso cumprir** (abre modal com opções: passar direto OU anunciar)
- Se confirmado: **Passar para colega** / **Anunciar no mural**
- Se anunciado: mostra interessados (informativo) + botão **Cancelar anúncio**
- Se em troca: mostra status da troca + botão **Cancelar** (se ainda pode)

Modal "Passar para colega":
- Dropdown de médicos do mesmo hospital
- Campo motivo (opcional)
- Confirmar

Modal "Anunciar":
- Campo motivo (opcional)
- Confirmar

### `/medico/mural`
Plantões `disponivel` dos quadros em que participa.
- Filtro: hospital, quadro, período
- Cada card: data, horário, hospital, quadro, quem anunciou, motivo
- Botão **Tenho interesse** / **Retirar interesse** (se já demonstrou)

### `/medico/trocas`
Duas listas:
1. **Recebidas** (troca direta que outro médico pediu pra mim) — botões Aceito/Recuso
2. **Enviadas** (que eu pedi) — mostra status

### `/medico/interesses`
Meus interesses ativos + histórico.

### `/medico/notificacoes`
Lista de notificações.

### `/medico/perfil`
Nome, telefone, foto, alterar senha, hospitais em que atua.

---

## Wireframes textuais das telas críticas

### Editor de escala do gestor (desktop)

```
┌─────────────────────────────────────────────────────────────────────┐
│ [Hospital Santa Maria ▼]        Escala: UTI Diurno · Agosto 2026    │
│ Status: RASCUNHO       [Publicar] [Nova versão]                     │
├─────────────────────────────────────────────────────────────────────┤
│ Seg   Ter   Qua   Qui   Sex   Sáb   Dom                             │
│                                            1     2     3            │
│                                          Dr.X  vago  Dr.Y           │
│                                          07-19 07-19 07-19          │
│ 4     5     6     7     8     9     10                              │
│ Dr.A  Dr.B  Dr.A  Dr.C  Dr.B  vago  Dr.A                            │
│ ...                                                                 │
│                                                                     │
│ Legenda: [ ] vago  [🟡] pendente  [🟢] confirmado                   │
└─────────────────────────────────────────────────────────────────────┘
```

### Painel médico (celular)

```
┌────────────────────┐
│ Olá, Dr. Silva  🔔 │
│ [Santa Maria ▼]    │
├────────────────────┤
│ Próximo plantão    │
│ ┌────────────────┐ │
│ │ Sáb 15/ago     │ │
│ │ 07:00 - 19:00  │ │
│ │ UTI Santa Maria│ │
│ │ 🟢 CONFIRMADO  │ │
│ └────────────────┘ │
│                    │
│ Pendente (2)       │
│ • Ter 18 07-19  🟡 │
│ • Qui 20 19-07  🟡 │
│                    │
│ Trocas             │
│ • Dr. João quer    │
│   te passar 22/ago │
│                    │
├────────────────────┤
│ 📅   📢   🔄   👤  │
│Esc  Mur  Tro  Perf│
└────────────────────┘
```

### Detalhe de plantão (médico, celular)

```
┌────────────────────┐
│ < Voltar           │
│ Plantão            │
├────────────────────┤
│ 🟡 PENDENTE        │
│ Sáb 15 de agosto   │
│ 07:00 → 19:00      │
│ Hospital Santa     │
│ Maria — UTI        │
│                    │
│ [ CONFIRMAR ]      │
│ [ Não posso ]      │
│                    │
│ Histórico          │
│ • Escalado por     │
│   Thallys 01/ago   │
└────────────────────┘
```
