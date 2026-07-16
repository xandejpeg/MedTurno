# 03 — Fluxos

Fluxos passo-a-passo com diagramas. Todo caminho crítico da v1.

---

## 1. Onboarding — Primeiro acesso do gestor

```mermaid
flowchart TD
  A[Thallys recebe conta<br/>criada manualmente por nós] --> B[Login com email + senha]
  B --> C{Já tem hospital?}
  C -- Não --> D[Tela de boas-vindas<br/>'Cadastre seu primeiro hospital']
  D --> E[Cadastra Hospital Santa Maria]
  E --> F[Cadastra Hospital São Gabriel]
  F --> G[Cria quadro 'Diurno' e 'Noturno' por hospital]
  G --> H[Define templates de turno<br/>com nº vagas certo por hospital]
  H --> I[Convida médicos por email]
  I --> J[Cadastra recorrências dos médicos fixos]
  J --> K[Gera escala do próximo mês em rascunho]
```

---

## 2. Convite de médico

```mermaid
sequenceDiagram
  participant G as Gestor
  participant S as Sistema
  participant M as Médico (email)
  G->>S: Cadastra médico (nome, email, telefone)
  S->>S: Cria user (papel=medico, ativo=false)
  S->>S: Cria vínculo user↔hospital
  S->>S: Gera token de convite (uuid, expira em 7d)
  S->>M: Email com link /convite/aceitar?token=XXX
  M->>S: Abre link
  S->>M: Tela 'Defina sua senha'
  M->>S: Envia senha
  S->>S: Marca user.ativo=true, invalida token
  S->>M: Redireciona pra /medico (dashboard)
```

**Regras:**
- Se email já existir na base, **não cria user novo** — só cria vínculo do user existente com o novo hospital
- Token válido por 7 dias, uso único
- Reenvio de convite gera novo token e invalida o anterior

---

## 3. Publicar escala mensal

```mermaid
flowchart TD
  A[Gestor entra na aba Escalas] --> B[Clica 'Nova escala']
  B --> C[Escolhe hospital + quadro + mês]
  C --> D[Sistema pré-preenche<br/>com base em recorrências]
  D --> E[Escala fica em RASCUNHO]
  E --> F[Gestor arrasta médicos<br/>pros plantões vazios]
  F --> G{Falta plantão sem médico?}
  G -- Sim --> F
  G -- Não --> H[Clica 'Publicar']
  H --> I[Sistema muda status para PUBLICADA]
  I --> J[Cria plantões com status=pendente]
  J --> K[Envia notificação interna + email<br/>pra cada médico afetado]
```

**Detalhes:**
- Publicar é **irreversível** (não volta pra rascunho). Se precisa desfazer → cria nova versão.
- Cada plantão publicado nasce como `pendente` (amarelo) esperando confirmação do médico.
- Republicar (edição pós-publicação) só notifica os médicos afetados, não todos.

---

## 4. Médico confirma plantão

```mermaid
sequenceDiagram
  participant M as Médico
  participant S as Sistema
  participant G as Gestor
  M->>S: Abre app, vê plantão amarelo
  M->>S: Clica no plantão → tela de detalhe
  M->>S: Clica 'Confirmar plantão'
  S->>S: shift.status = confirmado (verde)
  S->>S: Registra confirmed_at, confirmed_by
  S->>G: Notificação interna 'Dr. X confirmou plantão Y'
  S->>M: 'Confirmado ✓'
```

**Regras:**
- Confirmação é **ação explícita** — só visualizar NÃO confirma
- Pode confirmar até o início do plantão
- Depois de confirmado, pode disponibilizar/trocar (vai pro fluxo 5 ou 6)

---

## 5. Troca direta (médico → colega específico)

```mermaid
sequenceDiagram
  participant A as Médico A (dono do plantão)
  participant S as Sistema
  participant B as Médico B (recebe)
  participant G as Gestor
  A->>S: Abre plantão, clica 'Passar para colega'
  A->>S: Escolhe Médico B na lista de colegas
  A->>S: (opcional) escreve motivo
  S->>S: Cria shift_transfer(tipo=direta, status=aguardando_receptor)
  S->>S: shift.status = em_troca
  S->>B: Notificação 'Dr. A quer te passar o plantão de DD/MM'
  B->>S: Abre a proposta
  alt Aceita
    B->>S: Clica 'Aceito'
    S->>S: transfer.status = aguardando_gestor
    S->>G: Notificação 'Troca aguardando aprovação'
    G->>S: Aprova
    S->>S: transfer.status = aprovada
    S->>S: shift.medico_id = B, shift.status = pendente (B precisa confirmar)
    S->>A: Notificação 'Troca aprovada'
    S->>B: Notificação 'Plantão é seu, confirme'
  else Recusa
    B->>S: Clica 'Recuso'
    S->>S: transfer.status = recusada
    S->>S: shift.status = volta pro estado anterior (confirmado ou pendente)
    S->>A: Notificação 'Dr. B recusou'
  end
```

**Regras:**
- Médico A **só pode escolher** um Médico B que também atue naquele hospital
- Médico A pode **cancelar** a proposta antes de B responder
- Gestor pode **rejeitar** mesmo com B tendo aceitado (última palavra é dele)
- Rejeição do gestor: plantão volta pro A, B é notificado

---

## 6. Anúncio no mural (médico → qualquer)

```mermaid
sequenceDiagram
  participant A as Médico A (dono)
  participant S as Sistema
  participant Q as Médicos do quadro
  participant G as Gestor
  A->>S: Abre plantão, clica 'Anunciar no mural'
  A->>S: (opcional) escreve motivo
  S->>S: shift.status = disponivel (vermelho)
  S->>S: shift aparece no mural do quadro
  S->>G: Notificação 'Dr. A anunciou plantão de DD/MM'
  loop Vários interessados
    Q->>S: Clica 'Tenho interesse'
    S->>S: Cria shift_interest(medico_id=X, status=pendente)
    S->>G: Notificação 'Dr. X interessado no plantão de DD/MM'
    S->>A: Notificação 'Dr. X interessado'
  end
  G->>S: Abre lista de interessados
  G->>S: Escolhe Dr. Y e aprova
  S->>S: interest[Y].status = aprovado
  S->>S: shift.medico_id = Y, shift.status = pendente
  S->>S: Demais interests: status = rejeitado_automaticamente
  S->>Y: 'Plantão é seu, confirme'
  S->>A: 'Seu plantão foi para Dr. Y'
  S->>OutrosInteressados: 'Plantão foi para outro colega'
```

**Regras:**
- Médico A **pode cancelar o anúncio** enquanto NENHUM interesse tiver sido aprovado
- Ao cancelar: plantão volta pro status anterior (confirmado ou pendente), interessados são notificados
- Múltiplos interessados é permitido — gestor decide
- Regra futura opcional: "primeiro que clicar leva sem gestor" — **NÃO no v1**

---

## 7. Ciclo de vida do plantão (visão macro)

```mermaid
stateDiagram-v2
  [*] --> sem_medico: gestor cria escala vazia
  sem_medico --> pendente: gestor atribui médico
  pendente --> confirmado: médico confirma
  pendente --> em_troca: médico pede troca direta
  pendente --> disponivel: médico anuncia no mural
  confirmado --> em_troca: médico pede troca direta
  confirmado --> disponivel: médico anuncia no mural
  em_troca --> pendente: gestor aprova (vira do novo médico)
  em_troca --> confirmado: receptor recusa (volta ao anterior)
  em_troca --> pendente: receptor recusa (volta ao anterior)
  disponivel --> pendente: gestor aprova interessado
  disponivel --> confirmado: dono cancela anúncio
  disponivel --> pendente: dono cancela anúncio
  confirmado --> concluido: data do plantão passou
  pendente --> nao_cumprido: data passou sem confirmar
  sem_medico --> nao_cumprido: data passou sem médico
```

**Estados finais** (não voltam):
- `concluido` — plantão feito
- `nao_cumprido` — deu ruim, precisa relatório

Formalizado em [06-regras-negocio.md](06-regras-negocio.md).
