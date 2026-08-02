# Roadmap — Licitação TR 027/2021

**Edital:** Termo de Referência Nº 027/2021
**Órgão:** AEBES — Hospital Estadual Dr. Jayme Santos Neves (Serra/ES)
**Objeto:** Sistema online de escalas médicas
**Vigência:** 36 meses
**Critério de julgamento:** Menor preço global
**Modalidade:** Licitação pública (habilitação jurídica obrigatória)

> Este documento é o plano completo para o DoctorTurn ficar **100% apto** a disputar e vencer este edital. Cada item indica o que o edital exige, o que temos hoje e o que falta construir.

---

## 0. Resumo executivo

O TR 027/2021 é o edital **mais completo e exigente** dos dois. Cobre praticamente tudo que um sistema profissional de escalas médicas hospitalares precisa ter. Para vencer, precisamos de: escalas avançadas (recorrências e grade rica), gestão de ausências, limites de horas, conformidade trabalhista, check-in/check-out com geolocalização, relatórios financeiros robustos, app nas lojas, integração com agenda pessoal, API aberta e toda a documentação de habilitação jurídica.

**Status geral estimado:** ~35% de aderência. Base sólida (escalas, trocas, publicação, notificações), mas falta a camada "enterprise" (conformidade, ausências, limites, check-in, financeiro avançado, API, app nativo).

---

## 1. Escalas e planejamento

### 1.1 Construir e planejar escalas — ✅ PRONTO
- Já criamos e planejamos escalas mensais com turnos dia/noite e atribuição de médicos.

### 1.2 Regras de repetição — 🟡 PARCIAL
**O edital exige:**
- (a) Repetição semanal (ex.: semana sim, semana não).
- (b) Repetição mensal.
- (c) Repetição por dia do mês.
- (d) Repetição diária com intervalos configuráveis (dia sim/não, a cada 2 dias, 2x2, etc.).
- (e) Repetição por semana do mês (ex.: todas as quartas da 5ª semana).

**O que temos:** replicação mensal de escala e recorrência por dia da semana.

**O que falta construir:**
- [ ] Motor de regras de recorrência com os 5 tipos acima.
- [ ] Repetição "semana sim / semana não" (paridade de semana).
- [ ] Repetição por intervalo de dias (a cada N dias, padrão XxY).
- [ ] Repetição por dia específico do mês (ex.: todo dia 10).
- [ ] Repetição por semana do mês (1ª, 2ª, ... última semana).
- [ ] Interface no gestor para configurar cada regra.
- [ ] Aplicação das regras na geração automática da escala (1 a 2 anos à frente).

### 1.3 Grade de alocações — 🟡 PARCIAL
**O edital exige:**
- (a) Diferenciação visual das equipes por cores.
- (b) Calendário mensal **e** semanal.
- (c) Bloqueio/desbloqueio de vagas com hachura.
- (d) Filtros de equipe na grade.
- (e) Turnos publicados com destaque visual.
- (f) Comentários nos plantões com destaque (ícone + hover).
- (g) Trocas com destaque visual na grade (envolvidos no hover).
- (h) Anúncio do gestor com contorno laranja.
- (i) Divisão de um turno entre dois plantonistas.
- (j) Saldo de horas por profissional em tempo real.
- (k) Plantões de sobreaviso com identificação diferenciada.
- (l) Anúncio em lote (filtros por equipe, data, tipo).
- (m) Impressão/exportação da grade (CRM, especialidade, não publicados, lista de profissionais).
- (n) Fixar número de vagas.

**O que temos:** grade mensal com cores por turno, publicação com destaque, relatório mensal.

**O que falta construir:**
- [ ] Cores configuráveis por equipe/quadro na grade.
- [ ] Alternância de visualização mensal ↔ semanal.
- [ ] Bloqueio de vagas por dia da semana com hachura visual.
- [ ] Filtro de equipe na grade de alocação.
- [ ] Ícone de comentário no plantão com exibição em hover.
- [ ] Destaque visual de trocas na grade (com envolvidos em hover).
- [ ] Contorno laranja para anúncios feitos pelo gestor.
- [ ] Divisão de um turno em dois (plantonista complementar).
- [ ] Saldo de horas por profissional com total na instituição vs. escala.
- [ ] Tipo de plantão "sobreaviso" com identificação visual própria.
- [ ] Anúncio em lote com filtros (equipe, intervalo de datas, tipo).
- [ ] Exportação da grade completa (planilha) com CRM, especialidade, não publicados e lista de profissionais.
- [ ] Fixar/limitar número de vagas por plantão.

---

## 2. Gestão de profissionais

### 2.1 Dados cadastrais — 🟡 PARCIAL
**O edital exige:** nome, apelido, e-mail, CPF, celular, sexo, ocupação (CBO), conselho, UF do conselho, identificação interna, especialidade, data de ingresso, TAGs.

**O que temos:** nome, e-mail, CPF, celular, sexo (gênero), CRM, UF do CRM, especialidade.

**O que falta construir:**
- [ ] Campo **apelido**.
- [ ] Campo **ocupação (CBO)**.
- [ ] Campo **conselho** (tipo: CRM, COREN, CRO etc.).
- [ ] Campo **identificação interna** (matrícula).
- [ ] Campo **data de ingresso**.
- [ ] Sistema de **TAGs** por profissional (e em escalas, equipes e plantões).
- [ ] Uso de TAGs como filtro e em relatórios.

### 2.2 Gestão de ausências — 🔴 FALTANDO
**O edital exige:**
- Adição de ausência com justificativa (em uma escala ou em todas).
- Tratamento de ausência em turnos já publicados (substituir pelo mais adequado ou anunciar cobertura).
- Destaque visual de ausências no planejamento.
- Bloqueio de alocação em dia com ausência.

**O que falta construir:**
- [ ] Modelo de ausências (profissional, período, justificativa, escopo).
- [ ] Interface para registrar/editar ausências.
- [ ] Tratamento automático: ao registrar ausência, tratar plantões afetados (substituir ou anunciar).
- [ ] Destaque visual das ausências na lista de profissionais e na grade.
- [ ] Bloqueio de auto-alocação/troca/anúncio em período de ausência.

---

## 3. Limites e conformidade

### 3.1 Limite de horas — 🔴 FALTANDO
**O edital exige:**
- Limite individual por profissional (mensal ou semanal, com vigência).
- Tratativa ao atingir limite via trocas e via anúncios (bloquear ou alertar).
- Acompanhamento do consumo do limite no aplicativo.

**O que falta construir:**
- [ ] Configuração de limite de horas por profissional.
- [ ] Regra de bloqueio/alerta ao exceder em trocas e anúncios.
- [ ] Resumo do consumo de horas contratuais no app do profissional.

### 3.2 Regras de conformidade — 🔴 FALTANDO
**O edital exige:**
- Tempo máximo de turno sequencial.
- Descanso mínimo entre plantões (com regra extra para noturnos).
- Conflitos de agenda (interseção de horários) — informativo ou impedido, configurável.

**O que falta construir:**
- [ ] Configuração de tempo máximo de turno.
- [ ] Regra de descanso mínimo entre plantões (e reforço noturno).
- [ ] Detecção de conflito de horários entre escalas.
- [ ] Configuração de tratar conflito como alerta ou bloqueio.
- [ ] Painel de alertas de conformidade.

---

## 4. Check-in / Check-out

### 4.1 Marcação de entrada/saída — 🔴 FALTANDO
**O edital exige:**
- Check-in e check-out (com e sem geolocalização).
- Janela de check-in e de check-out configurável.
- Raio de geolocalização e endereço associado.
- Check-in offline com sincronização.

**O que falta construir:**
- [ ] Registro de check-in/check-out por plantão.
- [ ] Geolocalização com raio configurável e endereço da escala.
- [ ] Janelas de tolerância (tempo antes/depois do plantão).
- [ ] Funcionamento offline com sincronização posterior.

### 4.2 Painel de tratamento — 🔴 FALTANDO
**O edital exige:**
- Listagem por escala/mês e por profissional.
- Ajuste de horário de check-in/out.
- Restaurar horário planejado (multi-seleção).
- Consolidação (oficialização) com data máxima.

**O que falta construir:**
- [ ] Painel de tratamento de check-in/out por escala e por profissional.
- [ ] Edição e restauração de horários (com multi-seleção).
- [ ] Consolidação oficial e data máxima de consolidação automática.

---

## 5. Negociações (trocas e anúncios)

### 5.1 Configuração de negociações — ✅ PRONTO (parcialmente avançado)
- Permissão para trocar/anunciar, mediação com aceite do organizador, e-mail e notificação — **já implementado** (toggle + notificações).

### 5.2 Regras adicionais — 🟡 PARCIAL
**O que falta construir:**
- [ ] Bloquear trocas de turnos de duração diferente.
- [ ] Histórico completo de negociações, anúncios do organizador e substituições.
- [ ] Notificação de troca por e-mail com aprovação direta no e-mail.

---

## 6. Dashboard e relatórios financeiros

### 6.1 Dashboard — 🟡 PARCIAL
**O que falta construir:**
- [ ] Visão geral: nº de escalas, profissionais, organizadores, plantões.
- [ ] Visão de alocação (acima/abaixo/conforme o planejado).
- [ ] Dias com mais negociações.
- [ ] Alertas de conformidade no dashboard.

### 6.2 Relatórios financeiros — 🟡 PARCIAL
**O edital exige:**
- Consolidado por profissional e por equipe (por hora ou por alocação).
- Analítico por turno.
- Bônus (contabilizar ou não).
- Filtros por escala, equipe, profissional e TAGs.

**O que falta construir:**
- [ ] Relatório financeiro por equipe.
- [ ] Relatório analítico por turno.
- [ ] Opção de contabilizar bônus.
- [ ] Filtros por TAGs.
- [ ] Relatórios personalizados (Metabase ou equivalente).
- [ ] Detalhe de horas por profissional com exportação xlsx e filtro de período.

---

## 7. Administração e integração

### 7.1 Administração — 🟡 PARCIAL
**O que falta construir:**
- [ ] Personalização com cores e logotipo da instituição (white-label).
- [ ] Ativação/desativação de escalas.
- [ ] Inclusão de usuários em lote (importação por planilha).
- [ ] Gestão e auditoria de TAGs globais.
- [ ] Configurar fuso-horário, horário noturno e fim de semana.
- [ ] Relatório de engajamento de colaboradores.

### 7.2 API — 🔴 FALTANDO
**O edital exige:** APIs abertas para turnos/plantões, profissionais (ativar/inativar/atualizar), inclusão de escalas, registros de check-in/out.

**O que falta construir:**
- [ ] API REST pública documentada.
- [ ] Endpoints de plantões, profissionais, escalas e check-in/out.
- [ ] Autenticação por token/chave de API.
- [ ] Captura de TAGs via API para segmentação.

---

## 8. Aplicativo e agenda

### 8.1 Aplicativo — 🟡 PARCIAL
**O edital exige:** app na App Store e Google Play.

**O que temos:** PWA instalável.

**O que falta construir:**
- [ ] Aplicativo nativo (ou wrapper) publicado nas lojas.
- [ ] Notificações push nativas.

### 8.2 Agenda pessoal — 🔴 FALTANDO
**O edital exige:** integração em tempo real com Google e Apple Calendar.

**O que falta construir:**
- [ ] Exportação da escala para Google Calendar (iCal/API).
- [ ] Exportação para Apple Calendar (assinatura iCal).
- [ ] Atualização automática ao mudar a escala.

### 8.3 Notificações e apoio à decisão — 🟡 PARCIAL
**O que falta construir:**
- [ ] Lembretes de turno programáveis (12h, 24h etc.).
- [ ] Apoio à decisão: importar eventos da agenda pessoal e alertar conflitos.
- [ ] Avisos de atualização do app.

---

## 9. Habilitação jurídica (obrigatória para disputar)

> Sem esta documentação, a empresa é **eliminada** antes de qualquer avaliação técnica.

- [ ] Prova de inscrição no CNPJ.
- [ ] Registro comercial (empresa individual) ou ato constitutivo/estatuto/contrato social registrado.
- [ ] Documentos de eleição dos administradores (se sociedade por ações).
- [ ] Prova de eleição da diretoria (se sociedade civil).
- [ ] Decreto de autorização (se empresa estrangeira).
- [ ] Certidão do Sistema de Inabilitados e Inidôneos do TCU.
- [ ] Certidão negativa CEIS (Cadastro de Empresas Inidôneas e Suspensas).
- [ ] Não ter condenação no CADE ou judicial por violação anticorrupção.
- [ ] Comprovante de experiência: **maior número de hospitais de médio/grande porte com a solução implantada** (critério de desempate).

---

## 10. Prioridades sugeridas (ordem de execução)

**Fase 1 — Fundamentos exigidos (bloqueadores de nota técnica):**
1. Gestão de ausências (completa).
2. Limites de horas e regras de conformidade.
3. Check-in/check-out (base + painel de tratamento).
4. Regras de repetição avançadas.

**Fase 2 — Experiência e grade rica:**
5. Grade de alocações completa (cores, semanal, bloqueios, saldo de horas, sobreaviso, anúncio em lote).
6. Dados cadastrais completos + TAGs.
7. Dashboard comparativo e relatórios financeiros avançados.

**Fase 3 — Integração e escala:**
8. API aberta documentada.
9. Integração Google/Apple Calendar.
10. App nativo nas lojas.

**Fase 4 — Jurídico e comercial:**
11. Documentação de habilitação.
12. Portfólio de hospitais implantados (critério de desempate).

---

## 11. Critério de desempate — estratégia

O desempate é "**maior número de hospitais de médio/grande porte com a solução implantada com sucesso**".

- [ ] Implantar o DoctorTurn no Hospital Santa Maria (piloto) com sucesso documentado.
- [ ] Produzir cases/relatos de uso em hospitais reais.
- [ ] Coletar cartas de recomendação/atestados de implantação.

---

*Documento gerado para planejamento da aderência do DoctorTurn ao TR 027/2021. Atualizado em 02/08/2026.*
