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

*Roadmap completo do DoctorTurn a partir de 02/08/2026, cobrindo os dois editais e a parte financeira/fiscal confirmada. Estratégia: desenvolver e homologar em ambiente de teste antes de promover para produção.*
