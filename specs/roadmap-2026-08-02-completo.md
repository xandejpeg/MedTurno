# Roadmap Completo — DoctorTurn (a partir de 02/08/2026)

> **Documento mestre de planejamento.** Lista **tudo que já temos** e **tudo que ainda precisamos** para o DoctorTurn ficar 100% apto e competitivo nas licitações **TR 027/2021** (AEBES/Hospital Jayme Santos Neves) e **Cotação 68/2025** (AgSUS/Atenção Primária), incluindo a parte **financeira/fiscal** confirmada nos editais.
>
> Estratégia: **desenvolver e homologar em ambiente de teste primeiro, e só depois promover para o build do VPS (produção).**

---

## ÍNDICE

- [PARTE A — Como vamos trabalhar (ambiente de teste → produção)](#parte-a)
- [PARTE B — O que JÁ TEMOS (inventário completo)](#parte-b)
- [PARTE C — O que FALTA (backlog completo por área)](#parte-c)
- [PARTE D — Parte FINANCEIRA e FISCAL (confirmada nos editais)](#parte-d)
- [PARTE E — Aderência por edital (o que falta em cada um)](#parte-e)
- [PARTE F — Gerador de relatórios (PDF + PowerPoint)](#parte-f)
- [PARTE G — Habilitação e documentos (não-técnico)](#parte-g)
- [PARTE H — Cronograma de execução](#parte-h)

---

<a name="parte-a"></a>
## PARTE A — Como vamos trabalhar (ambiente de teste → produção)

### A.1 Princípio
Toda nova funcionalidade é desenvolvida e **homologada em ambiente de teste** antes de ir para produção. Nada vai direto para o VPS sem passar por validação.

### A.2 Ambientes

| Ambiente | Onde | Banco | Objetivo |
|---|---|---|---|
| **Local (dev)** | máquina do desenvolvedor | SQLite/MySQL local | desenvolver e rodar testes |
| **Homologação (staging)** | subdomínio ou instância separada no VPS | banco de homologação (cópia) | validar com dados reais sem afetar produção |
| **Produção (VPS)** | `doctorturn.com.br` | banco de produção | uso real |

### A.3 Fluxo de promoção (dev → homologação → produção)

1. **Desenvolver local** com testes automatizados (Pest) cobrindo a feature.
2. **Subir para homologação** (branch ou instância de staging) e **homologar manualmente** com dados reais.
3. **Checklist de homologação** (ver abaixo) aprovado.
4. **Promover para produção** via `git pull` + `migrate` + `optimize` + restart do worker no VPS.
5. **Smoke test** em produção (site 200, fluxos críticos).

### A.4 Checklist de homologação (obrigatório antes de cada deploy)

- [ ] Todos os testes automatizados passando (`php artisan test`).
- [ ] Build do frontend sem erros (`npm run build`).
- [ ] Migrations testadas em banco de homologação (sem perda de dados).
- [ ] Fluxo principal da feature validado manualmente em homologação.
- [ ] Sem erros no log de homologação.
- [ ] Rollback planejado (como reverter se der problema).

### A.5 Regras de segurança para deploy

- [ ] Nunca expor tokens/senhas em logs, telas ou commits.
- [ ] Sempre rodar `optimize:clear` + `optimize` como `www-data`.
- [ ] Sempre reiniciar o worker da fila após mudanças em jobs.
- [ ] Backup do `.env` e do banco antes de migrations destrutivas.

---

<a name="parte-b"></a>
## PARTE B — O que JÁ TEMOS (inventário completo)

### B.1 Núcleo de escalas
- [x] Criação de escala mensal por hospital (turnos dia 07–19h e noite 19–07h).
- [x] Montagem da escala com atribuição de médicos (arrastar e soltar + botão).
- [x] Preenchimento inteligente (médico + dias da semana + turnos).
- [x] Publicação de escala com versionamento.
- [x] Replicação de escala para outro mês.
- [x] Múltiplos quadros (ShiftBoards) por hospital.
- [x] Escalas simples (sem quadro) e por quadro.

### B.2 Trocas e anúncios
- [x] Troca direta entre médicos (com aceite do colega).
- [x] Anúncio de plantão no mural (interessados se candidatam).
- [x] Aprovação/recusa de troca pelo gestor.
- [x] Toggle: troca livre vs. troca com aprovação do gestor.
- [x] Central de trocas (admin) com status.

### B.3 Notificações e comunicação
- [x] Notificação interna (no app).
- [x] E-mail de escala publicada (com layout DoctorTurn).
- [x] WhatsApp de escala publicada (template aprovado na Meta, v3 com negrito + link).
- [x] Notificação de troca pendente (app + e-mail + WhatsApp) para gestor e admins.
- [x] Saudação com Dr./Dra. + primeiro nome.
- [x] Log de comunicação por usuário/canal (Central de Controle).
- [x] Perfil comercial do WhatsApp configurado (nome + foto).

### B.4 Ausências, limites e conformidade
- [x] Gestão de ausências (registro, escopo hospital/todas, motivo).
- [x] Bloqueio de alocação e de troca em dia de ausência.
- [x] Limite de horas por médico (mensal/semanal, vigência, bloquear ou alertar).
- [x] Regras de conformidade: tempo máximo de turno, descanso mínimo (com reforço noturno), conflito de agenda (alerta/bloqueio/desligado).

### B.5 Check-in / Check-out
- [x] Check-in/check-out manual.
- [x] Check-in por GPS (com raio e endereço configuráveis).
- [x] Check-in por QR Code (payload assinado).
- [x] Janelas de tolerância (antes/depois do plantão).
- [x] Painel de presenças do gestor (entrada, saída, status por plantão).

### B.6 Recorrências e TAGs
- [x] Recorrências: semanal, quinzenal, mensal, por dia do mês, por intervalo de dias, por semana do mês.
- [x] Sistema de TAGs (médicos, plantões, escalas, quadros).

### B.7 UBS, agenda e mural
- [x] Unidades (UBS) por hospital.
- [x] Painel "escala do dia" por UBS (quem trabalha, contato).
- [x] Feed iCal da escala do médico (Google/Apple/Outlook).
- [x] Mural de recados (gestor publica, médicos recebem).

### B.8 Perfis e admin
- [x] Perfis: gestor, médico, admin, financeiro, gestor municipal.
- [x] Central de Controle do admin (comunicação, plantões por gestor, trocas).
- [x] Central de Licitações (requisitos e aderência por edital).
- [x] Patch Notes (versões 1.0.0 → 1.2.0).

### B.9 API e valores
- [x] API pública `/api/v1` com token por hospital (escalas, plantões, profissionais, check-ins).
- [x] Valores por médico, por tipo de turno e padrão por hospital.
- [x] Faturamento mensal básico por médico.

### B.10 PWA e LGPD
- [x] PWA instalável com atalhos (Meus plantões, Minha escala).
- [x] Página de Privacidade e LGPD.

### B.11 Qualidade e operação
- [x] 182 testes automatizados (Pest).
- [x] Deploy automatizado no VPS com migrations e restart do worker.
- [x] Logs de produção monitoráveis.

---

<a name="parte-c"></a>
## PARTE C — O que FALTA (backlog completo por área)

### C.1 Grade de alocações avançada (foco TR 027)
- [ ] Cores configuráveis por equipe/quadro na grade.
- [ ] Alternância de visualização mensal ↔ semanal.
- [ ] Bloqueio/desbloqueio de vagas por dia da semana (com hachura).
- [ ] Filtro de equipe na grade.
- [ ] Ícone de comentário no plantão (hover mostra a observação).
- [ ] Destaque visual de trocas na grade (envolvidos no hover).
- [ ] Contorno laranja para anúncios feitos pelo gestor.
- [ ] Divisão de um turno entre dois plantonistas.
- [ ] Saldo de horas por profissional em tempo real (instituição vs. escala).
- [ ] Tipo de plantão "sobreaviso" com identificação visual.
- [ ] Anúncio em lote (filtros por equipe, data, tipo).
- [ ] Exportação da grade completa (CRM, especialidade, não publicados, lista de profissionais).
- [ ] Fixar número de vagas por plantão.

### C.2 Dados cadastrais completos (foco TR 027)
- [ ] Campo apelido.
- [ ] Campo ocupação (CBO).
- [ ] Campo tipo de conselho (CRM, COREN, CRO etc.).
- [ ] Campo identificação interna (matrícula).
- [ ] Campo data de ingresso.
- [ ] Uso de TAGs como filtro e em relatórios.

### C.3 Gestão de ausências avançada (foco TR 027)
- [ ] Tratamento automático de ausência em turnos já publicados (substituir pelo mais adequado ou anunciar cobertura).
- [ ] Destaque visual de ausências na lista de profissionais e na grade.
- [ ] Notificação ao gestor municipal de ausências/faltas.

### C.4 Painel de tratamento de check-in/out (foco TR 027)
- [ ] Listagem por escala/mês e por profissional.
- [ ] Ajuste de horário de check-in/check-out.
- [ ] Restaurar horário planejado (multi-seleção).
- [ ] Consolidação oficial com data máxima.

### C.5 Lembretes programáveis (os dois)
- [ ] Lembretes de plantão configuráveis (12h, 24h etc.).
- [ ] Notificação de check-in/out próximo do início/fim do turno.
- [ ] Avisos de atualização do app.

### C.6 Apoio à decisão e conflito de agenda pessoal (foco TR 027)
- [ ] Importar eventos da agenda pessoal e alertar conflitos ao aceitar troca/anúncio.

### C.7 Perfis e gestão (os dois)
- [ ] Fluxo dedicado de substituição de profissional pelo gestor (com registro).
- [ ] Perfil de gestor municipal completo (visão semanal + notificações de alterações).
- [ ] Permissões granulares por perfil.
- [ ] Ativação/desativação de escalas.
- [ ] Inclusão de usuários em lote (importação por planilha).

### C.8 Dashboards executivos (os dois)
- [ ] Visão geral: nº de escalas, profissionais, organizadores, plantões.
- [ ] Visão de alocação (acima/abaixo/conforme o planejado).
- [ ] Dias com mais negociações.
- [ ] Alertas de conformidade no dashboard.
- [ ] Detalhe de horas por profissional com exportação xlsx e filtro de período.

### C.9 Administração e white-label (foco TR 027)
- [ ] Personalização com cores e logotipo da instituição.
- [ ] Gestão e auditoria de TAGs globais.
- [ ] Configurar fuso-horário, horário noturno e fim de semana.
- [ ] Relatório de engajamento de colaboradores.

### C.10 Aplicativo nativo (foco TR 027)
- [ ] App na App Store e Google Play.
- [ ] Notificações push nativas.
- [ ] Check-in/out offline com sincronização.

### C.11 Tempo de gestão (foco Cotação 68)
- [ ] Registro do tempo dedicado à gestão de escalas/turnos.

---

<a name="parte-d"></a>
## PARTE D — Parte FINANCEIRA e FISCAL (confirmada nos editais)

> **Confirmação:** sim, os editais pedem parte financeira. Encontrado nos documentos:
> - **TR 027:** "Extrato Financeiro" completo (por profissional, equipe, turno, bônus, filtros por TAG) + "Relatórios personalizados Metabase" + cláusula contratual de **Nota Fiscal de Serviços** (o pagamento é feito após emissão de NFS).
> - **Cotação 68:** gestão financeira, valores por escala/profissional/turno/plantão, relatórios/extrato consolidado.

### D.1 Extrato financeiro (os dois)
- [ ] Relatório financeiro consolidado **por profissional** (quanto pagar a cada médico).
- [ ] Relatório financeiro consolidado **por equipe** (por hora ou por alocação).
- [ ] Relatório **analítico por turno** (detalhe de cada turno trabalhado).
- [ ] Opção de **contabilizar ou não bônus** no extrato.
- [ ] Filtros por **escala**, **equipe**, **profissional** e **TAGs**.
- [ ] Exportação para **xlsx** com seleção de período e de colunas.

### D.2 Valores e regras de cálculo (os dois)
- [x] Valor por plantão e padrão por hospital (já temos).
- [x] Valor por médico (já temos).
- [x] Valor por tipo de turno (já temos).
- [ ] Valor por **escala** (configurável).
- [ ] **Bônus** por plantão (noturno, fim de semana, sobreaviso).
- [ ] Regras de cálculo por período noturno e fim de semana.

### D.3 Nota Fiscal de Serviços (foco TR 027)
> O TR 027 exige que a CONTRATADA emita **Nota Fiscal de Serviços** para receber o pagamento. Isso é uma obrigação **fiscal da empresa fornecedora**, não necessariamente uma feature do sistema. Mas podemos agregar valor gerando os dados base.

- [ ] **Emissão de NFS-e** (integração com prefeitura ou provedor de NFS-e).
- [ ] Geração do **relatório de faturamento** que serve de base para a NFS (itens, valores, período, tomador).
- [ ] Exportação dos dados fiscais (XML/CSV) para contabilidade.
- [ ] Registro de NFS emitidas (número, data, valor, status).

### D.4 Repasse a médicos (opcional, agrega valor)
- [ ] Demonstrativo de repasse por médico (o que cada um tem a receber).
- [ ] Exportação do demonstrativo em PDF.
- [ ] Controle de repasses pagos/pendentes.

### D.5 Relatórios personalizados (Metabase) (foco TR 027)
- [ ] Integração com **Metabase** (ou ferramenta de BI equivalente) para relatórios personalizados.
- [ ] Dashboards embutidos no sistema via Metabase.

---

<a name="parte-e"></a>
## PARTE E — Aderência por edital (o que falta em cada um)

### E.1 TR 027/2021 (AEBES) — o mais exigente
**Estimativa atual:** ~55% de aderência técnica.

**Falta (priorizado):**
1. Grade de alocações avançada (C.1).
2. Dados cadastrais completos + TAGs em relatórios (C.2).
3. Tratamento automático de ausências (C.3).
4. Painel de tratamento de check-in/out (C.4).
5. Relatórios financeiros avançados + Metabase (D.1, D.5).
6. Nota Fiscal de Serviços (D.3).
7. App nativo nas lojas (C.10).
8. Apoio à decisão com agenda pessoal (C.6).
9. White-label e administração avançada (C.9).
10. Habilitação jurídica completa (PARTE G).

### E.2 Cotação 68/2025 (AgSUS) — a mais acessível
**Estimativa atual:** ~70% de aderência técnica.

**Falta (priorizado):**
1. Lembretes programáveis (C.5).
2. Registro de tempo de gestão (C.11).
3. Perfil de gestor municipal completo (C.7).
4. Fluxo dedicado de substituição (C.7).
5. Dashboards executivos (C.8).
6. Gestão financeira e valores completos (D.1, D.2).
7. Qualificação técnica e econômico-financeira (PARTE G).
8. Cronograma de implantação e suporte (PARTE G).

---

<a name="parte-f"></a>
## PARTE F — Gerador de relatórios (PDF + PowerPoint)

> Ferramenta para gerar relatórios do DoctorTurn em **PDF** e **PowerPoint**, para entendermos e apresentarmos o sistema (interno e para propostas).

### F.1 Tipos de relatório a gerar
- [ ] **Relatório de escala** (PDF): calendário do mês com médicos por plantão.
- [ ] **Relatório financeiro** (PDF): consolidado por profissional/equipe/turno.
- [ ] **Relatório de presença** (PDF): check-in/out por médico e período.
- [ ] **Relatório de aderência a licitação** (PDF + PowerPoint): status de cada requisito por edital.
- [ ] **Relatório executivo do sistema** (PowerPoint): visão geral do produto, funcionalidades e cases (para propostas comerciais).

### F.2 Tecnologia sugerida
- **PDF:** `barryvdh/laravel-dompdf` (Blade → PDF) ou `spatie/laravel-pdf` (Puppeteer/Browsershot para fidelidade visual).
- **PowerPoint:** `PHPOffice/PHPPresentation` (gera .pptx).
- Exportação xlsx: `maatwebsite/excel` (PhpSpreadsheet).

### F.3 Estrutura a construir
- [ ] Instalar e configurar a lib de PDF.
- [ ] Instalar e configurar a lib de PowerPoint.
- [ ] Criar o serviço `ReportGenerator` (monta os dados).
- [ ] Criar os templates Blade de cada relatório.
- [ ] Criar a tela "Relatórios" no gestor/admin com os botões de exportação (PDF, xlsx, pptx).
- [ ] Testes de geração de cada formato.

---

<a name="parte-g"></a>
## PARTE G — Habilitação e documentos (não-técnico)

> A parte que **não depende de código** e é **eliminatória**. Detalhada em [habilitacao-licitacoes.md](habilitacao-licitacoes.md).

- [ ] CNPJ e contrato social registrados.
- [ ] Certidões negativas (TCU, CEIS, FGTS, CNDT, Federal, Estadual/Municipal).
- [ ] Atestados de experiência em saúde.
- [ ] Case formal do Hospital Santa Maria (carta do hospital).
- [ ] Balanços financeiros e comprovação de capacidade.
- [ ] Declarações (não condenação CADE, sem restrição CEIS).
- [ ] Cronograma de implantação e plano de suporte (Cotação 68).
- [ ] Propostas comerciais (Cotação 68 com dados obrigatórios; TR 027 com menor preço).

---

<a name="parte-h"></a>
## PARTE H — Cronograma de execução

### Sprint 1 — Financeiro base (valor imediato nos dois)
1. Extrato financeiro por profissional, equipe e turno.
2. Filtros por escala/equipe/profissional/TAG.
3. Exportação xlsx.
4. Bônus por plantão.

### Sprint 2 — Gerador de relatórios
5. Lib de PDF + primeiro relatório (escala em PDF).
6. Relatório financeiro em PDF.
7. Lib de PowerPoint + relatório executivo.
8. Tela de exportações.

### Sprint 3 — Grade e conformidade avançada (TR 027)
9. Grade rica (cores por equipe, semanal, saldo de horas).
10. Tratamento automático de ausências.
11. Painel de tratamento de check-in/out.

### Sprint 4 — Lembretes e perfis (os dois)
12. Lembretes programáveis.
13. Perfil de gestor municipal completo.
14. Fluxo de substituição.

### Sprint 5 — Fiscal e BI
15. Relatório base para NFS-e.
16. Integração Metabase.

### Sprint 6 — App nativo e polish
17. App nas lojas.
18. Check-in/out offline.

### Contínuo — Habilitação (em paralelo, não-técnico)
19. Documentos jurídicos, atestados, cases, propostas.

---

<a name="parte-i"></a>
## PARTE I — Especificação técnica detalhada (por item)

> Detalhamento técnico de cada item do backlog: o que construir, onde, campos de banco, endpoints, telas, regras de negócio e critérios de aceite. Serve de guia direto para implementação.

---

### I.1 Extrato financeiro consolidado (Sprint 1)

**Objetivo:** relatório financeiro por profissional, por equipe e por turno, com filtros e exportação.

**Banco de dados:**
- Reaproveitar `shifts.amount` (já existe) e `hospital_memberships.shift_amount` (já existe).
- Nova tabela `shift_bonuses` (opcional): `id, shift_id, type (noturno|fim_semana|sobreaviso), amount, created_at, updated_at`.

**Backend:**
- `app/Services/FinancialReportService.php`:
  - `consolidatedByDoctor(Hospital $h, Carbon $from, Carbon $to, array $filters): Collection`
  - `consolidatedByTeam(Hospital $h, Carbon $from, Carbon $to, array $filters): Collection`
  - `analyticByShift(Hospital $h, Carbon $from, Carbon $to, array $filters): Collection`
  - Filtros: `schedule_id`, `board_id`, `user_id`, `tag`, `include_bonus` (bool).
- Regra: soma de `shifts.amount` por médico no período, agrupando por equipe (quadro) e por turno (período/horário).

**Endpoints/telas:**
- `GET gestor/financeiro` (nova página) com filtros e tabela.
- `GET gestor/financeiro/export?format=xlsx` (exportação).

**Critérios de aceite:**
- [ ] Consolida por médico com total e quantidade de plantões.
- [ ] Consolida por equipe (quadro) com total.
- [ ] Analítico lista cada turno com data, horário, médico e valor.
- [ ] Filtro por TAG funciona.
- [ ] Exporta xlsx com as colunas selecionadas.
- [ ] Opção de incluir/excluir bônus altera o total.

---

### I.2 Bônus por plantão (Sprint 1)

**Objetivo:** valor adicional por plantão noturno, fim de semana ou sobreaviso.

**Banco de dados:**
- `shift_bonuses` (ver I.1) ou campo `shifts.bonus_amount` (decimal, default 0).

**Regra de negócio:**
- Ao calcular o valor de um plantão, soma `amount + bonus_amount`.
- Bônus configurável por tipo (noturno, fim de semana, sobreaviso) por hospital.

**Critérios de aceite:**
- [ ] Plantão noturno recebe bônus configurado.
- [ ] Plantão de fim de semana recebe bônus configurado.
- [ ] Extrato pode incluir ou excluir bônus.

---

### I.3 Exportação xlsx (Sprint 1)

**Objetivo:** exportar relatórios para Excel.

**Tecnologia:** `maatwebsite/excel` (PhpSpreadsheet).

**Backend:**
- `app/Exports/FinancialReportExport.php` (implementa `FromCollection`, `WithHeadings`, `WithMapping`).

**Critérios de aceite:**
- [ ] Gera xlsx válido com cabeçalho.
- [ ] Respeita os filtros aplicados.
- [ ] Permite escolher colunas.

---

### I.4 Gerador de relatórios PDF (Sprint 2)

**Objetivo:** gerar relatórios em PDF (escala, financeiro, presença, aderência).

**Tecnologia:** `barryvdh/laravel-dompdf` (Blade → PDF) ou `spatie/laravel-pdf` (Browsershot para fidelidade).

**Backend:**
- `app/Services/ReportGenerator.php`:
  - `schedulePdf(Schedule $schedule): Response`
  - `financialPdf(Hospital $h, $from, $to, $filters): Response`
  - `presencePdf(Hospital $h, $from, $to): Response`
  - `tenderAdherencePdf(Tender $tender): Response`
- Templates Blade em `resources/views/reports/pdf/`.

**Critérios de aceite:**
- [ ] PDF da escala mostra o calendário com médicos.
- [ ] PDF financeiro mostra o consolidado.
- [ ] PDF de presença mostra check-in/out.
- [ ] PDF de aderência mostra requisitos e status.

---

### I.5 Gerador de PowerPoint (Sprint 2)

**Objetivo:** gerar apresentações .pptx (executivo do sistema, aderência a licitação).

**Tecnologia:** `PHPOffice/PHPPresentation`.

**Backend:**
- `app/Services/PresentationGenerator.php`:
  - `executiveSummary(): Response` (visão do produto, funcionalidades, cases).
  - `tenderAdherence(Tender $tender): Response` (requisitos e status por edital).

**Critérios de aceite:**
- [ ] Gera .pptx válido que abre no PowerPoint/Google Slides.
- [ ] Slides com título, bullets e métricas.
- [ ] Identidade visual DoctorTurn.

---

### I.6 Grade de alocações avançada (Sprint 3)

**Objetivo:** grade rica com cores por equipe, visão semanal, saldo de horas.

**Banco de dados:**
- `shift_boards.color` (já existe).
- Nova tabela `shift_blocks`: `id, hospital_id, weekday, period, reason, created_at` (bloqueio de vagas).

**Backend:**
- `app/Services/GridService.php`:
  - `weeklyGrid(Schedule $schedule, Carbon $weekStart): array`
  - `doctorHourBalance(User $doctor, Schedule $schedule): array` (horas na escala vs. instituição).

**Telas:**
- Alternância mensal/semanal na montagem.
- Cores por quadro na grade.
- Saldo de horas ao lado de cada médico.

**Critérios de aceite:**
- [ ] Visão semanal funciona.
- [ ] Cores por equipe aplicadas.
- [ ] Bloqueio de vagas com hachura.
- [ ] Saldo de horas em tempo real.

---

### I.7 Tratamento automático de ausências (Sprint 3)

**Objetivo:** ao registrar ausência, tratar plantões já publicados.

**Backend:**
- `app/Services/AbsenceService.php`:
  - `handlePublishedShifts(Absence $absence): void`
  - Opções: `substitute` (sugerir substituto) ou `announce` (anunciar cobertura).

**Critérios de aceite:**
- [ ] Ao registrar ausência, plantões afetados são listados.
- [ ] Gestor escolhe substituir ou anunciar.
- [ ] Substituição sugere o médico mais adequado (menos horas, sem conflito).

---

### I.8 Painel de tratamento de check-in/out (Sprint 3)

**Objetivo:** ajustar, restaurar e consolidar horários de check-in/out.

**Backend:**
- `app/Services/CheckinTreatmentService.php`:
  - `adjust(Checkin $checkin, Carbon $newTime): void`
  - `restorePlanned(Shift $shift): void`
  - `consolidate(Shift $shift): void`

**Critérios de aceite:**
- [ ] Gestor ajusta horário de um check-in/out.
- [ ] Restaura o horário planejado (multi-seleção).
- [ ] Consolida (oficializa) e impede nova alteração.

---

### I.9 Lembretes programáveis (Sprint 4)

**Objetivo:** notificar médicos X horas antes do plantão e de check-in/out.

**Backend:**
- `app/Jobs/SendShiftReminder.php` (agendado).
- `app/Console/Commands/SendReminders.php` (roda a cada hora via scheduler).
- Config por hospital: `reminder_hours_before` (array, ex.: [12, 24]).

**Critérios de aceite:**
- [ ] Médico recebe lembrete X horas antes do plantão.
- [ ] Lembrete de check-in próximo do início.
- [ ] Configurável por hospital.

---

### I.10 Perfil de gestor municipal (Sprint 4)

**Objetivo:** gestor municipal vê a escala semanal e recebe notificações de alterações.

**Backend:**
- Middleware/permissão `gestor_municipal`.
- Página `gestor-municipal/escala-semanal`.
- Notificação de alterações (faltas, atestados) para o gestor municipal.

**Critérios de aceite:**
- [ ] Gestor municipal acessa a escala semanal.
- [ ] Recebe notificação de faltas/atestados.

---

### I.11 Fluxo de substituição (Sprint 4)

**Objetivo:** gestor substitui um médico já alocado, com registro.

**Backend:**
- `app/Services/SubstitutionService.php`:
  - `substitute(Shift $shift, User $newDoctor, User $byManager, ?string $reason): Shift`
- Registro em `shift_substitutions`: `id, shift_id, from_user_id, to_user_id, by_user_id, reason, created_at`.

**Critérios de aceite:**
- [ ] Gestor substitui o médico de um plantão.
- [ ] Substituição fica registrada com motivo e autor.
- [ ] Médico anterior e novo são notificados.

---

### I.12 Nota Fiscal de Serviços (Sprint 5)

**Objetivo:** gerar a base para emissão de NFS-e e registrar NFS emitidas.

**Banco de dados:**
- `invoices`: `id, hospital_id, number, issue_date, period_start, period_end, amount, status, xml_path, created_at`.

**Backend:**
- `app/Services/InvoiceService.php`:
  - `generateBaseData(Hospital $h, $from, $to): array` (itens, valores, tomador).
  - `registerInvoice(Hospital $h, array $data): Invoice`
- Integração futura com provedor de NFS-e (API da prefeitura ou serviço terceiro).

**Critérios de aceite:**
- [ ] Gera os dados base da NFS (itens, valores, período, tomador).
- [ ] Registra NFS emitidas (número, data, valor).
- [ ] Exporta XML/CSV para contabilidade.

---

### I.13 Integração Metabase (Sprint 5)

**Objetivo:** relatórios personalizados via Metabase.

**Backend:**
- Instalar Metabase (Docker) ou usar Metabase Cloud.
- Conectar o Metabase ao banco do DoctorTurn (read-only).
- Criar dashboards no Metabase e embutir via iframe assinado.

**Critérios de aceite:**
- [ ] Metabase conectado ao banco.
- [ ] Dashboards de escalas/financeiro criados.
- [ ] Embed seguro no sistema.

---

### I.14 App nativo (Sprint 6)

**Objetivo:** app na App Store e Google Play.

**Opções:**
- **Wrapper PWA** (Capacitor/TWA) — mais rápido.
- **Nativo** (React Native/Flutter) — mais trabalho.

**Critérios de aceite:**
- [ ] App instalável nas lojas.
- [ ] Notificações push.
- [ ] Check-in/out offline com sincronização.

---

<a name="parte-j"></a>
## PARTE J — Estimativa de esforço por sprint

| Sprint | Escopo | Estimativa | Prioridade |
|---|---|---|---|
| 1 | Financeiro base (extrato, bônus, xlsx) | 3–5 dias | Alta |
| 2 | Gerador PDF + PowerPoint | 3–4 dias | Alta |
| 3 | Grade rica + ausências + check-in | 5–7 dias | Alta (TR 027) |
| 4 | Lembretes + gestor municipal + substituição | 3–4 dias | Média |
| 5 | NFS-e + Metabase | 4–6 dias | Média |
| 6 | App nativo + offline | 7–10 dias | Baixa |
| — | Habilitação (documentos) | contínuo | Alta (paralelo) |

---

*Roadmap completo e detalhado do DoctorTurn a partir de 02/08/2026, com especificação técnica de cada item. Estratégia: desenvolver e homologar em ambiente de teste antes de promover para produção.*
