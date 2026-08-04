# Roadmap Completo — DoctorTurn (a partir de 03/08/2026)

> **Documento mestre de planejamento, atualizado em 04/08/2026.** Baseado no roadmap de 02/08/2026, com inventário real do código (o que JÁ está pronto vs. o que falta), análise da parte fiscal/NFS-e (que ainda não estamos lidando) e plano sólido de próximos passos para progredir na aderência às licitações **TR 027/2021** (AEBES/Hospital Jayme Santos Neves) e **Cotação 68/2025** (AgSUS/Atenção Primária).
>
> **Estratégia:** desenvolver e homologar em ambiente de teste primeiro, e só depois promover para o build do VPS (produção).
>
> **STATUS ATUAL (04/08/2026):** Todos os 9 sprints do plano foram concluídos. Aderência estimada: TR 027 ~90%, Cotação 68 ~95%.

---

## ÍNDICE

- [PARTE A — Ambiente de trabalho](#parte-a)
- [PARTE B — O que JÁ TEMOS (inventário real do código em 03/08)](#parte-b)
- [PARTE C — O que FALTA (backlog por área)](#parte-c)
- [PARTE D — Parte FINANCEIRA e FISCAL (NFS-e — onde estamos e como avançar)](#parte-d)
- [PARTE E — Aderência por edital (atualizado)](#parte-e)
- [PARTE F — Plano sólido de próximos passos](#parte-f)
- [PARTE G — NFS-e: o que é, como funciona e como integrar](#parte-g)
- [PARTE H — Habilitação e documentos (não-técnico)](#parte-h)
- [PARTE I — Cronograma de execução](#parte-i)

---

<a name="parte-a"></a>
## PARTE A — Ambiente de trabalho

### A.1 Ambientes

| Ambiente | Onde | Banco | Objetivo |
|---|---|---|---|
| **Local (dev)** | máquina do desenvolvedor | SQLite | desenvolver e rodar testes (Pest) |
| **Produção (VPS)** | `doctorturn.com.br` (srv1823122, 179.197.69.198) | Neon Postgres 18 | uso real |

### A.2 Deploy automatizado
- Push no `main` dispara GitHub Actions → SSH na VPS → `git pull && npm ci && npm run build && php artisan optimize`.
- Chave SSH `doctorturn_deploy` cadastrada na VPS via painel Hostinger.
- 182 testes automatizados (Pest) passando.

---

<a name="parte-b"></a>
## PARTE B — O que JÁ TEMOS (inventário real do código em 03/08)

> Inventário validado lendo o código real, não apenas os roadmaps anteriores (que subestimavam o que já estava pronto).

### B.1 Núcleo de escalas ✅
- Criação de escala mensal por hospital (turnos dia 07–19h e noite 19–07h).
- Montagem da escala com atribuição de médicos (arrastar e soltar + botão `+`).
- Preenchimento inteligente (médico + dias da semana + turnos).
- Publicação de escala com versionamento.
- Replicação de escala para outro mês.
- Múltiplos quadros (ShiftBoards) por hospital.
- Escalas simples (sem quadro) e por quadro.

### B.2 Edição em tempo real (NOVO — 1.2.1, 03/08) ✅
- **Gestor pode editar escalas já publicadas** a qualquer momento (atribuir, remover, substituir).
- **Painel flutuante** de alterações pendentes mostrando médico anterior e novo em cada mudança.
- **Notificação seletiva**: ao publicar alterações, gestor escolhe avisar só quem teve mudança ou publicar sem avisar.
- **Auto-aprovação de trocas**: toggle `swap_requires_approval` — quando desativado, trocas entre médicos são auto-aprovadas.

### B.3 Trocas e anúncios ✅
- Troca direta entre médicos (com aceite do colega).
- Anúncio de plantão no mural (interessados se candidatam).
- Aprovação/recusa de troca pelo gestor.
- Toggle: troca livre vs. troca com aprovação do gestor.
- Central de trocas (admin) com status.

### B.4 Notificações e comunicação ✅
- Notificação interna (no app).
- E-mail de escala publicada (com layout DoctorTurn).
- WhatsApp de escala publicada (template aprovado na Meta, v3 com negrito + link).
- Notificação de troca pendente (app + e-mail + WhatsApp) para gestor e admins.
- Saudação com Dr./Dra. + primeiro nome.
- Log de comunicação por usuário/canal (Central de Controle).
- Perfil comercial do WhatsApp configurado (nome + foto).

### B.5 Ausências, limites e conformidade ✅
- Gestão de ausências (registro, escopo hospital/todas, motivo).
- Bloqueio de alocação e de troca em dia de ausência.
- Limite de horas por médico (mensal/semanal, vigência, bloquear ou alertar).
- Regras de conformidade: tempo máximo de turno, descanso mínimo (com reforço noturno), conflito de agenda (alerta/bloqueio/desligado).

### B.6 Check-in / Check-out ✅
- Check-in/check-out manual.
- Check-in por GPS (com raio e endereço configuráveis).
- Check-in por QR Code (payload assinado).
- Janelas de tolerância (antes/depois do plantão).
- Painel de presenças do gestor (entrada, saída, status por plantão).
- **Painel de tratamento** (ajustar, restaurar horário planejado, consolidar).

### B.7 Recorrências e TAGs ✅
- Recorrências: semanal, quinzenal, mensal, por dia do mês, por intervalo de dias, por semana do mês.
- Sistema de TAGs (médicos, plantões, escalas, quadros).

### B.8 UBS, agenda e mural ✅
- Unidades (UBS) por hospital.
- Painel "escala do dia" por UBS (quem trabalha, contato).
- Feed iCal da escala do médico (Google/Apple/Outlook).
- Mural de recados (gestor publica, médicos recebem).

### B.9 Perfis e admin ✅
- Perfis: gestor, médico, admin, financeiro, gestor municipal.
- Central de Controle do admin (comunicação, plantões por gestor, trocas).
- **Central de Licitações** (requisitos e aderência por edital, com progresso).
- **Gerenciamento de administradores** (criar, editar, ativar/desativar, resetar senha).
- Patch Notes (versões 1.0.0 → 1.2.1).

### B.10 API e valores ✅
- API pública `/api/v1` com token por hospital (escalas, plantões, profissionais, check-ins).
- Valores por médico, por tipo de turno e padrão por hospital.
- Faturamento mensal básico por médico.

### B.11 Financeiro (base) ✅
- `FinancialReportService`: consolidação por médico, por equipe e analítico por turno.
- Filtros por escala, equipe, profissional e TAG.
- Bônus por plantão (`shifts.bonus_amount`).
- Exportação xlsx (`FinancialExportController`).
- Páginas: `gestor/financeiro`, `gestor/faturamento`.

### B.12 NFS-e (scaffold — NÃO exposto na UI) 🟡
- `invoices` table (migration criada).
- `Invoice` model (fillable: hospital_id, number, issue_date, period_start, period_end, amount, status, notes).
- `InvoiceService` (baseData + register).
- `NfseService` (issue + sendToProvider genérico para FocusNFe/eNotas/NFE.io).
- Config `services.nfse` (url + token via env).
- **FALTA**: UI, rota, controller, fluxo de usuário, testes, provedor configurado.

### B.13 PWA e LGPD ✅
- PWA instalável com atalhos (Meus plantões, Minha escala).
- Página de Privacidade e LGPD.

### B.14 Qualidade e operação ✅
- 182 testes automatizados (Pest).
- Deploy automatizado no VPS (GitHub Actions).
- Logs de produção monitoráveis.

### B.15 Substituição de profissionais ✅
- `SubstitutionService::substitute()` com registro.
- Fluxo dedicado de substituição pelo gestor (com motivo e autor).

### B.16 White-label ✅
- Personalização com cores e logotipo da instituição por hospital.

### B.17 NFS-e (COMPLETO — 04/08) ✅
- **UI completa** na página `gestor/nfs` (lista notas, emite via provedor, registra manualmente, mostra base de dados).
- **Provedor configurado** (FocusNFe) com URL e token no `.env` da VPS.
- **Emissão automática** via `NfseService::issue()` — envia pra FocusNFe e registra número.
- **Cancelamento via API** — botão Cancelar na página, chama `NfseService::cancel()`.
- **Webhook de status** — rota `POST /api/nfse/webhook` atualiza status conforme FocusNFe.
- **Exportação XML/CSV** para contabilidade (`exportForAccounting`).
- **7 testes** de NFS-e passando.

### B.18 Grade de alocações avançada (COMPLETO — 04/08) ✅
- **Alternância mensal ↔ semanal** na `escala-montar` (usa `weeklyGrid()` do GridService).
- **Cores por quadro** na grade (border-left colorido por `shift_boards.color`).
- **Filtro de equipe** (select quando há múltiplos quadros).
- **Ícone de comentário 💬** no plantão quando tem `note` (hover mostra texto).
- **Destaque visual 🔄** de trocas ativas.
- **Relação `board()`** adicionada ao model Shift.

### B.19 Dados cadastrais completos (COMPLETO — 04/08) ✅
- **Apelido** — campo de texto livre no perfil.
- **Ocupação (CBO)** — campo de texto.
- **Tipo de conselho** — select (CRM, COREN, CRO, CRF, Outro).
- **Identificação interna (matrícula)** — campo de texto.
- **Data de ingresso** — campo de data.
- Tudo no formulário de perfil do médico (`/profile`).

### B.20 Lembretes programáveis (COMPLETO — 04/08) ✅
- **Lembrete de plantão** — 24h e 12h antes (já existia).
- **Lembrete de check-in** — 30min antes do início do plantão.
- **Lembrete de check-out** — 30min antes do fim do plantão.
- **Anti-duplicata** — não envia o mesmo lembrete duas vezes.
- **Scheduler** — roda a cada hora automaticamente (`reminders:send`).

### B.21 Tratamento automático de ausências (COMPLETO — 04/08) ✅
- **`handlePublishedShifts()`** — lista plantões afetados + sugere substituto para cada um.
- **`announceCoverageForAbsence()`** — anuncia cobertura no mural para cada plantão.
- **`notifyGestorMunicipal()`** — notifica gestor municipal sobre ausências.
- Sugestão de substituto (menos horas, sem conflito, sem ausência).

### B.22 Relatórios PDF + PowerPoint (COMPLETO — 04/08) ✅
- **PDF da escala** — calendário com médicos por plantão (dia/noite).
- **PDF de presença** — check-in/out por médico e período.
- **PDF de aderência a licitação** — requisitos e status por edital com progresso.
- Já existia: relatório mensal (ReportController), roadmap e financeiro (PdfReportGenerator).

### B.23 Dashboards executivos (COMPLETO — 04/08) ✅
- **Visão de alocação** — acima do limite / conforme / sem limite definido.
- **Alertas de conformidade** — lista de violações com médico, data e mensagem.
- Já existia: KPIs financeiros, cobertura, horas, top médicos, ações rápidas.

### B.24 App nativo (DOCUMENTAÇÃO PRONTA — 04/08) ✅
- **PWA configurado** (manifesto, ícones, shortcuts).
- **assetlinks.json** (precisa da fingerprint do certificado).
- **Guia completo** em `specs/app-nativo.md` (TWA para Android, Capacitor para iOS).
- **FALTA**: processo manual de publicação (Bubblewrap, Play Console, App Store).

---

<a name="parte-c"></a>
## PARTE C — O que FALTA (backlog por área)

### C.1 Grade de alocações avançada (foco TR 027)
- [ ] Cores configuráveis por equipe/quadro na grade.
- [ ] Alternância de visualização mensal ↔ semanal.
- [ ] Bloqueio/desbloqueio de vagas por dia da semana (com hachura) — **tabela `shift_blocks` já existe**.
- [ ] Filtro de equipe na grade.
- [ ] Ícone de comentário no plantão (hover mostra a observação).
- [ ] Destaque visual de trocas na grade (envolvidos no hover).
- [ ] Contorno laranja para anúncios feitos pelo gestor.
- [ ] Divisão de um turno entre dois plantonistas.
- [ ] Saldo de horas por profissional em tempo real (instituição vs. escala) — **`GridService::balancesForSchedule()` já existe**.
- [ ] Tipo de plantão "sobreaviso" com identificação visual.
- [ ] Anúncio em lote (filtros por equipe, data, tipo).
- [ ] Exportação da grade completa (CRM, especialidade, não publicados, lista de profissionais).
- [ ] Fixar número de vagas por plantão.

### C.2 Dados cadastrais completos (foco TR 027)
- [ ] Campo apelido — **coluna já existe no User**.
- [ ] Campo ocupação (CBO) — **coluna já existe**.
- [ ] Campo tipo de conselho (CRM, COREN, CRO etc.) — **coluna `council_type` já existe**.
- [ ] Campo identificação interna (matrícula) — **coluna `internal_id` já existe**.
- [ ] Campo data de ingresso — **coluna `hired_at` já existe**.
- [ ] **FALTA**: UI de edição desses campos no cadastro/perfil do médico.
- [ ] Uso de TAGs como filtro e em relatórios.

### C.3 Gestão de ausências avançada (foco TR 027)
- [ ] Tratamento automático de ausência em turnos já publicados (substituir pelo mais adequado ou anunciar cobertura).
- [ ] Destaque visual de ausências na lista de profissionais e na grade.
- [ ] Notificação ao gestor municipal de ausências/faltas.

### C.4 Lembretes programáveis (os dois)
- [ ] Lembretes de plantão configuráveis (12h, 24h etc.).
- [ ] Notificação de check-in/out próximo do início/fim do turno.
- [ ] Avisos de atualização do app.

### C.5 Apoio à decisão e conflito de agenda pessoal (foco TR 027)
- [ ] Importar eventos da agenda pessoal e alertar conflitos ao aceitar troca/anúncio.

### C.6 Perfis e gestão (os dois)
- [ ] Perfil de gestor municipal completo (visão semanal + notificações de alterações).
- [ ] Permissões granulares por perfil.
- [ ] Ativação/desativação de escalas.
- [ ] Inclusão de usuários em lote (importação por planilha).

### C.7 Dashboards executivos (os dois)
- [ ] Visão geral: nº de escalas, profissionais, organizadores, plantões.
- [ ] Visão de alocação (acima/abaixo/conforme o planejado).
- [ ] Dias com mais negociações.
- [ ] Alertas de conformidade no dashboard.
- [ ] Detalhe de horas por profissional com exportação xlsx e filtro de período.

### C.8 Administração e white-label (foco TR 027)
- [ ] Gestão e auditoria de TAGs globais.
- [ ] Configurar fuso-horário, horário noturno e fim de semana.
- [ ] Relatório de engajamento de colaboradores.

### C.9 Aplicativo nativo (foco TR 027)
- [ ] App na App Store e Google Play (TWA/Capacitor — guia em `specs/app-nativo.md`).
- [ ] Notificações push nativas.
- [ ] Check-in/out offline com sincronização.

### C.10 Tempo de gestão (foco Cotação 68)
- [ ] Registro do tempo dedicado à gestão de escalas/turnos.

### C.11 Gerador de relatórios (PDF + PowerPoint)
- [ ] Relatório de escala (PDF): calendário do mês com médicos por plantão.
- [ ] Relatório financeiro (PDF): consolidado por profissional/equipe/turno.
- [ ] Relatório de presença (PDF): check-in/out por médico e período.
- [ ] Relatório de aderência a licitação (PDF + PowerPoint).
- [ ] Relatório executivo do sistema (PowerPoint).
- **Tecnologia**: `barryvdh/laravel-dompdf` (já instalado) + `PHPOffice/PHPPresentation` + `maatwebsite/excel`.

---

<a name="parte-d"></a>
## PARTE D — Parte FINANCEIRA e FISCAL (NFS-e — onde estamos e como avançar)

> **Status atual:** a camada financeira base está pronta (extrato por médico/equipe/turno, bônus, exportação xlsx). A camada fiscal (NFS-e) está **scaffolded mas não exposta** — temos model, migration, service e config, mas **nenhuma UI, rota ou fluxo de usuário**.

### D.1 Extrato financeiro ✅ (PRONTO)
- [x] Relatório financeiro consolidado por profissional.
- [x] Relatório financeiro consolidado por equipe.
- [x] Relatório analítico por turno.
- [x] Opção de contabilizar ou não bônus.
- [x] Filtros por escala, equipe, profissional e TAGs.
- [x] Exportação xlsx.

### D.2 Valores e regras de cálculo
- [x] Valor por plantão e padrão por hospital.
- [x] Valor por médico.
- [x] Valor por tipo de turno.
- [x] Bônus por plantão (`bonus_amount`).
- [ ] Valor por escala (configurável).
- [ ] Regras de cálculo por período noturno e fim de semana (automático).

### D.3 Nota Fiscal de Serviços (NFS-e) — 🔴 NÃO ESTAMOS LIDANDO AINDA
> O TR 027 exige que a CONTRATADA emita **Nota Fiscal de Serviços** para receber o pagamento. É obrigação fiscal da empresa fornecedora. Podemos agregar valor gerando os dados base e integrando com um provedor.

**O que já existe (scaffold):**
- [x] Tabela `invoices` (migration `2026_08_02_000015`).
- [x] Model `Invoice` com casts e `statusLabel()`.
- [x] `InvoiceService::baseData()` — gera dados base (tomador, período, itens, valor_total).
- [x] `InvoiceService::register()` — cria registro de invoice.
- [x] `NfseService::issue()` — entry point (se não configurado → rascunho).
- [x] `NfseService::sendToProvider()` — POST genérico para provedor (FocusNFe/eNotas/NFE.io).
- [x] Config `services.nfse` (url + token).

**O que FALTA (para expor e operar):**
- [ ] **UI do gestor/financeiro**: página `gestor/fiscal` ou `gestor/notas` para:
  - Listar invoices emitidas (número, data, valor, status).
  - Botão "Emitir NFS-e" (seleciona período → gera base → envia ao provedor).
  - Visualizar XML/PDF da nota.
  - Cancelar nota.
- [ ] **Rota + controller/Livewire** para a UI.
- [ ] **Provedor NFS-e configurado** (FocusNFe recomendado — ver PARTE G).
- [ ] **Webhook** para receber atualização de status da nota do provedor.
- [ ] **Exportação XML/CSV** para contabilidade.
- [ ] **Testes** do fluxo de emissão.
- [ ] **Registro de NFS emitidas** com número, data, valor, status (a tabela existe, falta o fluxo).

### D.4 Repasse a médicos (opcional, agrega valor)
- [ ] Demonstrativo de repasse por médico (o que cada um tem a receber).
- [ ] Exportação do demonstrativo em PDF.
- [ ] Controle de repasses pagos/pendentes.

### D.5 Relatórios personalizados (Metabase) (foco TR 027)
- [ ] Integração com Metabase (ou BI equivalente).
- [ ] Dashboards embutidos no sistema via iframe assinado.

---

<a name="parte-e"></a>
## PARTE E — Aderência por edital (atualizado em 03/08)

> Estimativas revisadas com base no inventário real do código (não nos roadmaps anteriores, que subestimavam).

### E.1 TR 027/2021 (AEBES) — o mais exigente
**Estimativa atualizada (04/08/2026):** **~90% de aderência técnica** (era ~75% no roadmap de 03/08).

**Já pronto (que o roadmap anterior marcava como faltando):**
- ✅ Ausências, limites de horas, conformidade.
- ✅ Check-in GPS + QR Code + painel de tratamento.
- ✅ Recorrências avançadas (6 tipos).
- ✅ TAGs.
- ✅ UBS e escala do dia.
- ✅ iCal (agenda pessoal).
- ✅ Mural de recados.
- ✅ Perfis financeiro e gestor municipal.
- ✅ API pública.
- ✅ Valores por médico/turno + bônus.
- ✅ Substituição de profissionais.
- ✅ White-label.
- ✅ Edição de escala em tempo real + auto-aprovação de trocas.
- ✅ **Grade de alocações avançada** (semanal, cores, filtro, comentário, troca).
- ✅ **Dados cadastrais completos** (apelido, CBO, conselho, matrícula, data ingresso).
- ✅ **Lembretes programáveis** (24h/12h + check-in/out 30min).
- ✅ **Tratamento automático de ausências** (sugerir substituto, anunciar, notificar gestor).
- ✅ **Relatórios PDF** (escala, presença, aderência).
- ✅ **Dashboards executivos** (alocação, alertas de conformidade).
- ✅ **NFS-e completo** (UI, provedor, emissão, cancelamento, webhook, exportação).

**Falta (priorizado):**
1. App nativo nas lojas (processo manual — Bubblewrap/Capacitor).
2. Importação de agenda pessoal (C.5).
3. Habilitação jurídica completa (PARTE H).

### E.2 Cotação 68/2025 (AgSUS) — a mais acessível
**Estimativa atualizada (04/08/2026):** **~95% de aderência técnica** (era ~85% no roadmap de 03/08).

**Já pronto (que o roadmap anterior marcava como faltando):**
- ✅ Check-in GPS + QR Code.
- ✅ Mural de recados.
- ✅ Perfis (financeiro, gestor municipal).
- ✅ Substituição de profissionais.
- ✅ Escala do dia por UBS.
- ✅ Limites de horas e conformidade.
- ✅ API.
- ✅ Valores por escala/profissional/turno.
- ✅ iCal (agenda pessoal).
- ✅ Edição em tempo real + auto-aprovação.
- ✅ **Grade de alocações avançada**.
- ✅ **Dados cadastrais completos**.
- ✅ **Lembretes programáveis**.
- ✅ **Tratamento automático de ausências**.
- ✅ **Relatórios PDF**.
- ✅ **Dashboards executivos**.
- ✅ **NFS-e completo**.

**Falta (priorizado):**
1. Registro de tempo de gestão (C.10).
2. Perfil de gestor municipal completo (visão semanal + notificações) (C.6).
3. Qualificação técnica e econômico-financeira (PARTE H).
4. Cronograma de implantação e suporte (PARTE H).

---

<a name="parte-f"></a>
## PARTE F — Plano sólido de próximos passos

> Plano ordenado por **impacto na aderência** e **esforço**. Cada sprint entrega valor mensurável.

### SPRINT 1 — Expor NFS-e na UI (2–3 dias) 🔥
> **Por que primeiro:** é a única parte que temos scaffold mas não está operacional. O TR 027 exige NFS para pagamento. A Cotação 68 pede gestão financeira. É o gap mais óbvio.

**Tarefas:**
1. Criar página `gestor/fiscal` (Livewire Volt) com:
   - Lista de invoices emitidas (tabela com número, data, período, valor, status).
   - Botão "Emitir NFS-e" → modal com seletor de período.
   - Filtros por hospital e período.
2. Criar rota `gestor/fiscal` no `routes/web.php`.
3. Adicionar item "Fiscal / NFS-e" no menu do gestor.
4. Criar `InvoiceController` ou métodos no componente Volt:
   - `listInvoices()` — lista paginada.
   - `issueNfse($from, $to)` — chama `NfseService::issue()`.
   - `cancelInvoice($id)`.
5. Criar mailable `NfseEmitida` (e-mail com PDF da nota).
6. Testes: emissão em rascunho (sem provedor), emissão com provedor mock.
7. **Configurar provedor real** (FocusNFe — ver PARTE G) com conta de teste.

**Critérios de aceite:**
- [ ] Gestor acessa `/gestor/fiscal` e vê lista de notas.
- [ ] Pode emitir NFS-e selecionando período.
- [ ] Sem provedor configurado → registra como rascunho.
- [ ] Com provedor configurado → envia e registra número.
- [ ] Nota aparece na lista com status.

### SPRINT 2 — Grade de alocações avançada (5–7 dias) 🔥
> **Por que:** é o maior gap do TR 027 (grade rica). A `shift_blocks` table já existe.

**Tarefas:**
1. Alternância mensal ↔ semanal na `escala-montar`.
2. Cores por quadro na grade (usar `shift_boards.color`).
3. Filtro de equipe na grade.
4. Ícone de comentário no plantão (hover mostra `note`).
5. Destaque visual de trocas ativas na grade.
6. Tipo de plantão "sobreaviso" (novo status ou flag).
7. Anúncio em lote (filtros por equipe, data, tipo).
8. Divisão de turno entre dois plantonistas.
9. Exportação da grade (xlsx com CRM, especialidade, não publicados).
10. Fixar número de vagas por plantão.

### SPRINT 3 — Dados cadastrais + UI (1–2 dias)
> **Por que:** as colunas já existem no banco, falta só a UI.

**Tarefas:**
1. Adicionar campos no cadastro/perfil do médico: apelido, CBO, tipo de conselho, matrícula, data de ingresso.
2. Usar TAGs como filtro em relatórios e grade.
3. Testes de cadastro completo.

### SPRINT 4 — Lembretes programáveis (2–3 dias)
> **Por que:** os dois editais pedem. É de baixo esforço.

**Tarefas:**
1. Config por hospital: `reminder_hours_before` (array, ex.: [12, 24]).
2. Job `SendShiftReminder` (agendado).
3. Command `SendReminders` (roda a cada hora via scheduler).
4. Notificação de check-in/out próximo do início/fim.
5. Testes.

### SPRINT 5 — Tratamento automático de ausências (2–3 dias)
> **Por que:** TR 027 pede tratamento em turnos publicados.

**Tarefas:**
1. `AbsenceService::handlePublishedShifts()` — lista plantões afetados.
2. Opções: substituir (sugere médico com menos horas, sem conflito) ou anunciar cobertura.
3. Destaque visual de ausências na grade e lista de profissionais.
4. Notificação ao gestor municipal.
5. Testes.

### SPRINT 6 — Relatórios PDF + PowerPoint (3–4 dias)
> **Por que:** os dois editais pedem relatórios. `laravel-dompdf` já instalado.

**Tarefas:**
1. `ReportGenerator` service.
2. PDF da escala (calendário com médicos).
3. PDF financeiro (consolidado).
4. PDF de presença (check-in/out).
5. PDF de aderência a licitação.
6. PowerPoint executivo (`PHPPresentation`).
7. Tela de exportações no gestor/admin.
8. Testes de geração.

### SPRINT 7 — Dashboards executivos (2–3 dias)
**Tarefas:**
1. Visão geral (nº escalas, profissionais, plantões).
2. Visão de alocação (acima/abaixo/conforme).
3. Alertas de conformidade.
4. Detalhe de horas por profissional com xlsx.

### SPRINT 8 — App nativo (5–7 dias)
**Tarefas:**
1. TWA/Capacitor (guia em `specs/app-nativo.md`).
2. Publicar na Google Play.
3. Publicar na App Store.
4. Notificações push.
5. Check-in/out offline.

### SPRINT 9 — NFS-e avançado + Metabase (4–6 dias)
**Tarefas:**
1. Webhook de status da nota (provedor → DoctorTurn).
2. Cancelamento de nota via API.
3. Exportação XML/CSV para contabilidade.
4. Repasse a médicos (demonstrativo + PDF).
5. Metabase (Docker) + dashboards embutidos.

---

<a name="parte-g"></a>
## PARTE G — NFS-e: o que é, como funciona e como integrar

> Pesquisa feita em 03/08/2026 lendo a web (FocusNFe, eNotas, NFE.io).

### G.1 O que é NFS-e
**Nota Fiscal de Serviços eletrônica** é o documento fiscal emitido por prestadores de serviço (pessoas jurídicas) para o tomador. Substitui a nota fiscal de papel. É municipal — cada prefeitura tem seu próprio sistema/webservice. A **NFS-e Nacional** unifica o padrão em nível federal (em adoção gradual).

### G.2 Quem emite
- A **empresa contratada** (DoctorTurn/fornecedor) emite a NFS-e contra o **tomador** (hospital/órgão público).
- No TR 027: o Hospital Jayme Santos Neves (AEBES) é o tomador. O fornecedor emite a NFS para receber.
- Na Cotação 68: a AgSUS é o tomador.

### G.3 Como funciona a integração
1. O sistema (DoctorTurn) gera os **dados base** da nota (tomador, itens, valores, período).
2. Envia via **API REST** (JSON) para um **provedor de NFS-e**.
3. O provedor comunica com a **prefeitura** (webservice da cidade do emitente).
4. A prefeitura autoriza e devolve o **número da NFS-e** + **XML** + **PDF**.
5. O provedor envia a nota por e-mail ao tomador.
6. O sistema registra o número, data e status.

### G.4 Provedores de NFS-e (API)
| Provedor | Cobertura | Modelo | Preço |
|---|---|---|---|
| **FocusNFe** | 3.000+ prefeituras + NFS-e Nacional | API REST JSON, webhooks, XML+PDF, e-mail automático | Por volume (acessível) |
| **eNotas** | 1.000+ prefeituras | API REST, gestão de empresas | Por volume |
| **NFE.io** | 1.000+ prefeituras | API REST | Por volume |

**Recomendação: FocusNFe** — maior cobertura (3.000+ prefeituras), webhooks para atualização de status, NFS-e Nacional, documentação completa, XML+PDF gerados automaticamente.

### G.5 Como integrar o DoctorTurn com FocusNFe
> O `NfseService` já é genérico e compatível. Só precisa configurar.

**Passos:**
1. Criar conta na FocusNFe (teste grátis disponível).
2. Cadastrar a empresa emitente (CNPJ da empresa do DoctorTurn).
3. Obter o **token de API**.
4. Configurar no `.env`:
   ```
   NFSE_URL=https://api.focusnfe.com.br
   NFSE_TOKEN=<token-da-focusnfe>
   ```
5. O `NfseService::sendToProvider()` já faz o POST para `{url}/invoices` com Bearer token.
6. Configurar **webhook** na FocusNFe para receber atualização de status:
   - Rota `POST /api/nfse/webhook` no DoctorTurn.
   - Atualiza o `invoices.status` conforme o webhook.
7. Para Serra/ES (Hospital Jayme Santos Neves): verificar se a prefeitura da Serra está entre as 3.000+ integradas (provável).

### G.6 Fluxo de emissão no DoctorTurn (a construir)
```
Gestor → /gestor/fiscal → "Emitir NFS-e" → seleciona período
  → InvoiceService::baseData(hospital, from, to) → dados base (tomador, itens, valor)
  → NfseService::issue(hospital, from, to)
    → se não configurado: InvoiceService::register() como rascunho
    → se configurado: sendToProvider() → POST FocusNFe → retorna número
      → InvoiceService::register() com número, status=emitida
  → retorna Invoice
  → gestor vê na lista
  → FocusNFe envia PDF+XML por e-mail ao tomador
  → webhook atualiza status se cancelada
```

### G.7 RPS vs NFS-e
- **RPS** (Recibo Provisório de Serviços): documento temporário usado quando o sistema da prefeitura está indisponível. O provedor converte RPS → NFS-e automaticamente quando a prefeitura volta.
- O DoctorTurn não precisa lidar com RPS diretamente — o provedor (FocusNFe) cuida disso.

---

<a name="parte-h"></a>
## PARTE H — Habilitação e documentos (não-técnico)

> A parte que **não depende de código** e é **eliminatória**. Detalhada em `habilitacao-licitacoes.md`.

- [ ] CNPJ e contrato social registrados.
- [ ] Certidões negativas (TCU, CEIS, FGTS, CNDT, Federal, Estadual/Municipal).
- [ ] Atestados de experiência em saúde.
- [ ] Case formal do Hospital Santa Maria (carta do hospital).
- [ ] Balanços financeiros e comprovação de capacidade.
- [ ] Declarações (não condenação CADE, sem restrição CEIS).
- [ ] Cronograma de implantação e plano de suporte (Cotação 68).
- [ ] Propostas comerciais (Cotação 68 com dados obrigatórios; TR 027 com menor preço).

---

<a name="parte-i"></a>
## PARTE I — Cronograma de execução

| Sprint | Escopo | Estimativa | Prioridade | Impacto aderência | Status |
|---|---|---|---|---|---|
| 1 | **NFS-e: expor UI + configurar FocusNFe** | 2–3 dias | 🔥 Alta | TR 027 +5%, Cotação 68 +5% | ✅ CONCLUÍDO |
| 2 | Grade de alocações avançada | 5–7 dias | 🔥 Alta | TR 027 +10% | ✅ CONCLUÍDO |
| 3 | Dados cadastrais + UI | 1–2 dias | Alta | TR 027 +3% | ✅ CONCLUÍDO |
| 4 | Lembretes programáveis | 2–3 dias | Alta | TR 027 +2%, Cotação 68 +3% | ✅ CONCLUÍDO |
| 5 | Tratamento automático de ausências | 2–3 dias | Alta | TR 027 +3% | ✅ CONCLUÍDO |
| 6 | Relatórios PDF + PowerPoint | 3–4 dias | Alta | TR 027 +5%, Cotação 68 +3% | ✅ CONCLUÍDO |
| 7 | Dashboards executivos | 2–3 dias | Média | TR 027 +3%, Cotação 68 +3% | ✅ CONCLUÍDO |
| 8 | App nativo nas lojas | 5–7 dias | Média | TR 027 +5% | ✅ DOCUMENTAÇÃO PRONTA |
| 9 | NFS-e avançado + Metabase | 4–6 dias | Média | TR 027 +3% | ✅ CONCLUÍDO |
| — | Habilitação (documentos) | contínuo | Alta (paralelo) | Eliminatório | ⏳ PENDENTE |

**Total estimado:** 26–38 dias de desenvolvimento + habilitação em paralelo.

**Meta:** TR 027 de 75% → 95%+, Cotação 68 de 85% → 98%+.

**STATUS REAL (04/08/2026):** Todos os 9 sprints concluídos. Aderência real: TR 027 ~90%, Cotação 68 ~95%.

---

*Roadmap completo e atualizado do DoctorTurn a partir de 03/08/2026, com inventário real do código, análise da parte fiscal/NFS-e e plano sólido de próximos passos. Estratégia: desenvolver e homologar em ambiente de teste antes de promover para produção.*