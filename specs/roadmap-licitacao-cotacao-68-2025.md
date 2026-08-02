# Roadmap — Licitação Cotação 68/2025

**Edital:** Requisição de Proposta Comercial — Cotação de Preço Nº 68/2025
**Órgão:** AgSUS — Núcleo de Saúde Digital (Brasília/DF)
**Objeto:** Licença de software de Controle e Gerenciamento de Escala Médica para Atenção Primária à Saúde (APS)
**Contratação:** Direta
**Licenças:** 30 usuários, 12 meses (prorrogável)
**Foco:** Atenção Primária à Saúde / Equipes de Saúde da Família (eSF)

> Este documento é o plano completo para o DoctorTurn ficar **100% apto** a disputar e vencer esta cotação. Cada item indica o que a cotação exige, o que temos hoje e o que falta construir.

---

## 0. Resumo executivo

A Cotação 68/2025 é **mais enxuta e mais próxima do nosso estágio atual** que o TR 027. O foco é APS: distribuir médicos por equipes de Saúde da Família, múltiplas escalas, visão executiva, check-in/check-out (GPS/QR), dashboards, financeiro e integração por API. É **contratação direta** (sem a burocracia de habilitação do TR 027), o que a torna **a oportunidade mais acessível no curto prazo**.

**Status geral estimado:** ~50% de aderência. Temos a base de escalas, trocas, publicação, múltiplos quadros, notificações e relatórios. Falta principalmente: check-in GPS/QR, integração com agenda pessoal, visão por UBS/município, limites de horas, conformidade, API e mural de recados.

---

## 1. Visão e escalas

### 1.1 Visão mensal agrupada — 🟡 PARCIAL
**A cotação exige (1.3.2):** visão mensal que agrupe os turnos vagos por **Unidade Básica de Saúde (UBS)**, **Município**, turnos ou horários.

**O que temos:** visão mensal por hospital/quadro.

**O que falta construir:**
- [ ] Agrupamento de turnos vagos por UBS.
- [ ] Agrupamento por Município.
- [ ] Agrupamento por turno/horário.
- [ ] Visão consolidada multi-unidade (várias UBS em uma tela).

### 1.2 Planejamento e alocação — 🟡 PARCIAL
**A cotação exige (1.3.3, 1.3.4):** planejar, alocar profissionais, criar regras de recorrência, administrar ausências, anunciar vagas, com interface simples.

**O que temos:** planejamento e alocação com interface simples, replicação mensal, anúncio de vagas.

**O que falta construir:**
- [ ] Gestão de ausências (administrar ausências dos médicos).
- [ ] Regras de recorrência mais flexíveis.

### 1.3 Múltiplas escalas — 🟡 PARCIAL
**A cotação exige:** gestão de múltiplas escalas para diferentes equipes e turnos.

**O que temos:** múltiplos quadros (ShiftBoards) por hospital.

**O que falta construir:**
- [ ] Múltiplas escalas simultâneas por equipe (cada eSF com sua escala).
- [ ] Visão consolidada de todas as escalas/equipes.

### 1.4 Regras de repetição personalizadas — 🟡 PARCIAL
**O que falta construir:**
- [ ] Regras de repetição configuráveis pelo usuário (semanal, mensal, intervalo).

### 1.5 Distribuição de horas por profissional — 🟡 PARCIAL
**O que falta construir:**
- [ ] Distribuição e visualização de horas por profissional por período (semana/mês).

---

## 2. Perfis e acesso

### 2.1 Perfis de acesso — 🟡 PARCIAL
**A cotação exige (1.3.16.1):** perfis com permissões personalizadas (administrativo, operacional, financeiro etc.).

**O que temos:** gestor, médico, administrador.

**O que falta construir:**
- [ ] Perfil **financeiro** (acesso a relatórios e faturamento, sem editar escalas).
- [ ] Permissões granulares por perfil.

### 2.2 Controle de acesso — ✅ PRONTO
- Ativação, inativação e atualização de usuários — já implementado.

### 2.3 Visibilidade de agenda e grupos — 🟡 PARCIAL
**O que falta construir:**
- [ ] Visualização de grupos de profissionais responsáveis por cada atendimento (eSF).
- [ ] Filtros de busca e relatórios por grupo.

---

## 3. Agenda pessoal e notificações

### 3.1 Integração com agenda pessoal — 🔴 FALTANDO
**A cotação exige (1.3.6, 1.3.16.1):** integração com Google Calendar, Outlook e calendários pessoais.

**O que falta construir:**
- [ ] Exportação da escala para Google Calendar.
- [ ] Exportação para Outlook/Apple Calendar.
- [ ] Sincronização automática ao alterar a escala.

### 3.2 Lembretes e notificações — 🟡 PARCIAL
**A cotação exige:** lembretes e notificações sobre alterações na escala, plantões e compromissos.

**O que temos:** notificações de publicação e de troca (app, e-mail, WhatsApp).

**O que falta construir:**
- [ ] Lembretes programáveis de plantão (ex.: 12h, 24h antes).
- [ ] Notificação de check-in/out próximo do início/fim do turno.

### 3.3 Mural de recados — 🔴 FALTANDO
**A cotação exige (1.3.16.1):** mural de recados/mensagens importantes.

**O que falta construir:**
- [ ] Mural de recados por escala/equipe.
- [ ] Envio de recado para uma escala ou para todas.

---

## 4. Check-in / Check-out

### 4.1 Registro de entrada/saída — 🔴 FALTANDO
**A cotação exige (1.3.14, 1.3.16.1):** check-in/check-out via sistema ou dispositivo móvel, por **GPS ou QR Code**.

**O que falta construir:**
- [ ] Check-in/check-out por plantão.
- [ ] Check-in por **geolocalização (GPS)**.
- [ ] Check-in por **QR Code**.
- [ ] Notificação de lembrete de check-in/out.

### 4.2 Tempo de gestão — 🔴 FALTANDO
**A cotação exige (1.3.16.1):** calcular e registrar o tempo dedicado à gestão das escalas.

**O que falta construir:**
- [ ] Registro de tempo gasto na gestão de escalas/turnos.

---

## 5. Negociações e gestão pelo organizador

### 5.1 Trocas e anúncios — ✅ PRONTO
- Troca de plantão via aplicativo, anúncio e passagem de turno — já implementado.

### 5.2 Aprovação de negociações — ✅ PRONTO
- Aprovar/recusar negociações (toggle de aprovação + notificações ao gestor) — já implementado.

### 5.3 Regras de negociação customizáveis — ✅ PRONTO
- Customizar o nível de autonomia (toggle troca livre vs. com aprovação) — já implementado.

### 5.4 Substituição de profissionais — 🟡 PARCIAL
**A cotação exige (1.3.11, 1.3.16.1):** gestor substitui profissionais diretamente, inclusive via mobile.

**O que temos:** gestor atribui médicos aos plantões.

**O que falta construir:**
- [ ] Fluxo dedicado de **substituição** de um profissional já alocado (com registro).

### 5.5 Interface exclusiva do gestor (mobile) — 🟡 PARCIAL
**A cotação exige (1.3.11):** gestor substitui, gerencia negociações e cria anúncios de cobertura **sem desktop**.

**O que falta construir:**
- [ ] Interface do gestor otimizada para mobile (substituir, negociar, anunciar coberturas).

### 5.6 Painel "escala do dia" — 🟡 PARCIAL
**A cotação exige (1.3.12):** painel geral da escala do dia (quem trabalha, em qual UBS, dados de contato).

**O que temos:** escala do dia por hospital.

**O que falta construir:**
- [ ] Painel da escala do dia por UBS, com dados de contato.

### 5.7 Acesso do gestor municipal — 🔴 FALTANDO
**A cotação exige (1.3.13):** gestor municipal acessa a escala semanal, recebe notificação de alterações (faltas, atestados).

**O que falta construir:**
- [ ] Perfil de gestor municipal com visão semanal.
- [ ] Notificação de alterações (faltas/atestados) para o gestor municipal.

---

## 6. Relatórios e financeiro

### 6.1 Relatórios e dashboards — 🟡 PARCIAL
**A cotação exige:** relatórios detalhados de escalas, horas trabalhadas e performance, com dashboards.

**O que temos:** faturamento mensal e dashboard básico.

**O que falta construir:**
- [ ] Relatórios de produtividade e pendências.
- [ ] Dashboards executivos de alocação, trocas e saldos em tempo real.

### 6.2 Gestão financeira — 🟡 PARCIAL
**A cotação exige:** gestão financeira de turnos, plantões e horas trabalhadas.

**O que falta construir:**
- [ ] Consolidação financeira por período e por equipe.

### 6.3 Valores distintos — 🟡 PARCIAL
**A cotação exige:** valores distintos por escala, profissional, turno e plantão.

**O que temos:** valor por plantão e valor padrão por hospital.

**O que falta construir:**
- [ ] Valor por profissional.
- [ ] Valor por turno/tipo de plantão.

### 6.4 Alertas de conformidade — 🔴 FALTANDO
**A cotação exige:** alertas de conformidade com regras e leis trabalhistas.

**O que falta construir:**
- [ ] Regras de conformidade (tempo máximo, descanso) com alertas.

---

## 7. Limites e conformidade

### 7.1 Limite de horas — 🔴 FALTANDO
**A cotação exige (1.3.16.1):** limites mensais de horas trabalhadas por profissional.

**O que falta construir:**
- [ ] Limite mensal de horas por profissional com bloqueio/alerta.

### 7.2 Feedback de conflito de escalas — 🔴 FALTANDO
**A cotação exige (1.3.16.1):** gestão de conflitos com alertas e feedback.

**O que falta construir:**
- [ ] Detecção de conflito de horário entre escalas com alerta.

---

## 8. Integração e segurança

### 8.1 API — 🔴 FALTANDO
**A cotação exige (1.3.10, 1.3.16.1):** integração por API com outros softwares.

**O que falta construir:**
- [ ] API REST pública documentada (escalas, plantões, profissionais).
- [ ] Autenticação por token.

### 8.2 Segurança e conformidade — 🟡 PARCIAL
**A cotação exige (1.3.20):** segurança, LGPD, auditável.

**O que falta construir:**
- [ ] Revisão de conformidade LGPD (termos, consentimento).
- [ ] Trilha de auditoria das ações.

---

## 9. Implantação e suporte (exigências contratuais)

> Itens do cronograma de entrega e suporte que precisamos garantir como fornecedor.

- [ ] **Inicialização:** reunião de início do projeto.
- [ ] **Criação do ambiente:** envio dos dados de usuários e modelos de escala.
- [ ] **Treinamento:** 1–2 horas com os usuários.
- [ ] **Em funcionamento:** implementação final e uso regular.
- [ ] **Plantão de dúvidas:** canal contínuo (chat, e-mail, telefone) nos primeiros dias.
- [ ] **Correção de falhas em até 24h** durante a garantia.
- [ ] **Manutenção corretiva e evolutiva** por 12 meses.
- [ ] Condições de manutenção/suporte pós-garantia.

---

## 10. Prioridades sugeridas (ordem de execução)

**Fase 1 — Diferenciais bloqueadores (curto prazo):**
1. Check-in/check-out por GPS e QR Code.
2. Integração Google/Outlook Calendar.
3. Visão por UBS/município e painel "escala do dia".
4. Mural de recados.

**Fase 2 — Gestão e conformidade:**
5. Gestão de ausências.
6. Limite de horas e alertas de conformidade.
7. Perfil financeiro e perfil de gestor municipal.
8. Valores por profissional e por turno.

**Fase 3 — Integração e escala:**
9. API pública documentada.
10. Dashboards executivos e relatórios de produtividade.
11. Interface do gestor otimizada para mobile.

**Fase 4 — Comercial:**
12. Cronograma de implantação e plano de suporte.
13. Case piloto (Hospital Santa Maria) documentado.

---

## 11. Por que esta cotação é a melhor porta de entrada

- **Contratação direta** (sem licitação pública complexa).
- Escopo focado em **APS**, mais próximo do nosso produto atual.
- Apenas **30 usuários** — piloto de baixo risco para provar valor.
- Serve como **case comprovado** para disputar licitações maiores (como o TR 027, que usa "hospitais implantados" como desempate).

---

*Documento gerado para planejamento da aderência do DoctorTurn à Cotação 68/2025. Atualizado em 02/08/2026.*
