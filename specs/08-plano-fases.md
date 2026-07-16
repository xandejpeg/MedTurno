# 08 — Plano de fases

Fases sequenciais. Cada uma só começa quando a anterior está **em pé e testável**.

Sem estimativa em dias — depende do tempo que você mete no projeto. Cada fase é um "checkpoint" natural pra parar, testar com o Thallys, ajustar.

---

## Fase 0 — Kickoff e infraestrutura
**Objetivo:** projeto instalado, rodando local.

- [x] Node.js instalado (build do Tailwind/Vite)
- [ ] Instalar PHP 8.3 + Composer (via **Laravel Herd** pra Windows — mais simples)
- [ ] `laravel new medturno` (ou `composer create-project`)
- [ ] Instalar **Breeze stack Livewire** (auth scaffolding + Tailwind)
- [ ] SQLite em dev (`DB_CONNECTION=sqlite`)
- [ ] Configurar Pint (format) + Larastan (estática) + Pest (testes)
- [ ] Mailpit ou driver `log` pra email em dev
- [ ] `.env.example` com todas as vars futuras documentadas
- [ ] Repo Git organizado (app Laravel na raiz ou em `app/` — decidir no kickoff)

**Definition of Done:** `php artisan serve` abre a tela de login do Breeze. `php artisan test` roda verde.

---

## Fase 1 — Autenticação e usuários
**Objetivo:** dá pra criar usuário, logar, sair, redefinir senha.

- [ ] Ajustar migration `users` do Breeze (telefone, CRM, especialidade — ver [04](04-modelo-dados.md))
- [ ] Seed: cria 1 gestor de teste (`thallys@teste.com` / senha `mudar123`)
- [ ] Login/logout/reset de senha — **já vem do Breeze**, só traduzir e estilizar
- [ ] Middleware de proteção de rota (grupo `auth`)
- [ ] Layout base (header + sidebar gestor / bottom-nav médico)
- [ ] Testes Pest: login, reset, rota protegida

**DoD:** Thallys consegue logar e ver uma tela "olá, Thallys". Reset de senha funciona.

---

## Fase 2 — Hospitais e multi-tenancy
**Objetivo:** gestor cadastra hospitais, alterna entre eles, e o app inteiro respeita esse escopo.

- [ ] Schema: `Hospital`, `HospitalMembership`
- [ ] Migration + seed com 2 hospitais fictícios vinculados ao Thallys como gestor
- [ ] Página `/gestor/hospitais` (listar, criar, editar) — componentes Livewire
- [ ] Seletor de hospital no header (persiste em session)
- [ ] Helper `currentHospital()` + middleware usado em toda query
- [ ] `HospitalPolicy` (gestor só acessa hospital que gerencia)
- [ ] Painel `/gestor` mostrando cards vazios do hospital selecionado
- [ ] Testes Pest: gestor NÃO vê hospital de outro gestor (mesmo mudando ID na URL)

**DoD:** Thallys vê Santa Maria e São Gabriel, alterna, cada um mostra dados isolados (ainda vazios).

---

## Fase 3 — Equipe médica e convite
**Objetivo:** gestor convida médico por email, médico aceita e loga.

- [ ] Schema: `Invitation`
- [ ] Página `/gestor/equipe` (listar + filtrar)
- [ ] Formulário `/gestor/equipe/convidar`
- [ ] Serviço: cria user (ou reusa se email existe) + vínculo + convite + envia email
- [ ] Página `/convite/aceitar?token=` (2 fluxos: user novo vs existente)
- [ ] Reenvio de convite
- [ ] Desativar médico do hospital
- [ ] Mailable `ConviteMedico` em fila + integração Resend/Brevo real via SMTP

**DoD:** você convida um email de teste seu, recebe, aceita, loga como médico e vê um dashboard vazio.

---

## Fase 4 — Quadros e templates
**Objetivo:** gestor define a estrutura da escala.

- [ ] Schema: `ShiftBoard`, `ShiftBoardMembership`, `ShiftTemplate`
- [ ] Página `/gestor/quadros` (CRUD)
- [ ] Página `/gestor/quadros/:id` com 2 abas: Estrutura + Participantes
- [ ] Modal "novo template" (dia, horário, vagas, valor R$, atravessa MN)
- [ ] Botão "aplicar grade automática" (6h, 12h, 24h)
- [ ] Vincular médicos ao quadro (checkboxes)
- [ ] Validação: sobreposição de templates no mesmo dia

**DoD:** Thallys cria "Diurno UTI Santa Maria" com 1 vaga 07-19 seg-dom, e "Noturno" 19-07. Vincula 5 médicos.

---

## Fase 5 — Recorrências e geração de escala
**Objetivo:** gerar escala mensal automaticamente a partir das recorrências.

- [ ] Schema: `Recurrence`, `Schedule`, `Shift` (só criação)
- [ ] Página `/gestor/recorrencias` CRUD
- [ ] Página `/gestor/escalas/nova` — escolhe hospital + quadro + mês
- [ ] Serviço `ScheduleService::createDraft()` que popula shifts a partir de recorrências
- [ ] Listagem `/gestor/escalas` com filtros
- [ ] Testes: geração respeita quinzenal (paridade da data_referencia)

**DoD:** Thallys cria escala de agosto/2026 e vê 60 plantões já pré-preenchidos por recorrência + vagos.

---

## Fase 6 — Editor de escala (a tela mais crítica)
**Objetivo:** gestor arrasta médicos, edita plantões, publica.

- [ ] Página `/gestor/escalas/:id` — calendário mensal (Livewire)
- [ ] Componente `MonthGrid` desktop + `DayList` mobile
- [ ] Modal "atribuir médico" (dropdown + detecção de conflito no lado do servidor)
- [ ] Modal "editar plantão" (mudar médico, remover, observação)
- [ ] Botão "Publicar" (transição rascunho → publicada, gera notificações + emails)
- [ ] Botão "Nova versão" (edição pós-publicação)
- [ ] Testes: publicar duas vezes = versão 2

**DoD:** Thallys termina a escala e publica. 5 médicos recebem email "sua escala de agosto está publicada".

---

## Fase 7 — Painel e escala do médico
**Objetivo:** médico enxerga o próprio dia-a-dia.

- [ ] Página `/medico` (painel com próximo plantão + pendentes)
- [ ] Página `/medico/escala` calendário só com plantões do médico
- [ ] Filtro por hospital (se tem >1)
- [ ] Bottom-nav mobile
- [ ] Página `/medico/plantoes/:id` (detalhe)
- [ ] Ação "Confirmar" (transição pendente → confirmado)
- [ ] Notificação interna pro gestor ao confirmar

**DoD:** médico vê seus plantões e confirma um. Thallys recebe notificação.

---

## Fase 8 — Troca direta
**Objetivo:** médico passa plantão pra colega específico, colega aceita, gestor aprova.

- [ ] Schema: `ShiftTransfer` (tipo=direta)
- [ ] Modal "Passar para colega" no detalhe do plantão
- [ ] Página `/medico/trocas` (recebidas + enviadas)
- [ ] Página `/gestor/trocas` (pendentes)
- [ ] Serviço `TransferService`: `requestDirect()`, `acceptByReceiver()`, `rejectByReceiver()`, `approve()`, `reject()`
- [ ] Notificações + emails em cada etapa
- [ ] Testes: máquina de estados completa

**DoD:** Dr. A passa plantão pro Dr. B, Dr. B aceita no app, Thallys aprova, plantão é do Dr. B (pendente).

---

## Fase 9 — Mural (anúncio)
**Objetivo:** médico anuncia plantão, colegas manifestam interesse, gestor escolhe.

- [ ] Schema: `ShiftInterest`
- [ ] Ação "Anunciar" no detalhe do plantão
- [ ] Página `/medico/mural` (lista de plantões disponíveis do quadro)
- [ ] Ação "Tenho interesse" / "Retirar interesse"
- [ ] Gestor: lista de interessados no plantão + aprovar/rejeitar
- [ ] Rejeição automática dos outros interessados quando um é aprovado
- [ ] Cancelar anúncio (dono)

**DoD:** Dr. A anuncia, Drs. B e C manifestam interesse, Thallys escolhe B, C é notificado que perdeu, B confirma.

---

## Fase 10 — Faturamento
**Objetivo:** gestor define valores, app calcula quanto cada médico recebe no mês.

- [ ] Campo `valor` em `ShiftTemplate` (padrão) e `Shift` (snapshot congelado)
- [ ] Snapshot do valor ao atribuir médico ao plantão
- [ ] Edição de valor de plantão individual (gestor, no modal de detalhe)
- [ ] Página `/gestor/faturamento` — relatório mensal por médico (filtro mês/hospital, expandir detalhe por plantão, total geral)
- [ ] Card "total a receber no mês" no painel do médico
- [ ] Valor visível no detalhe do plantão (só pro dono e gestor)
- [ ] Testes: snapshot não muda ao editar template; transferência preserva valor; médico não vê valor de colega

**DoD:** Thallys define diurno R$ 1.200 / noturno R$ 1.400 por hospital, fecha o mês e vê quanto cada médico recebe. Cada médico vê o próprio total.

---

## Fase 11 — Notificações e polimento
**Objetivo:** sistema de notificações interno completo + polimento visual.

- [ ] `php artisan notifications:table` (tabela nativa do Laravel)
- [ ] Classes `Notification` (database + mail) pra cada evento da spec [06](06-regras-negocio.md)
- [ ] Página `/*/notificacoes`
- [ ] Sino no header com contador de não-lidas (`wire:poll.30s`)
- [ ] Marca como lida ao abrir
- [ ] Templates Blade de email (markdown mailables) pra todos os emails
- [ ] Ícones de status com legenda (acessibilidade)
- [ ] Loading states, skeletons, empty states

**DoD:** experiência inteira coesa, sem gambiarra visual.

---

## Fase 12 — Segurança e produção
**Objetivo:** subir pra VPS (ou Railway), endurecer.

- [ ] Contratar VPS (Hostinger/Contabo) ou Railway — PHP 8.3 + MySQL + nginx
- [ ] Migrar dev DB pra MySQL (mesmas migrations)
- [ ] `.env` de produção (APP_ENV=production, APP_DEBUG=false)
- [ ] HTTPS com Let's Encrypt (certbot)
- [ ] Headers de segurança (CSP, X-Frame-Options, HSTS) via middleware
- [ ] Rate limit nas rotas de auth e convite (`RateLimiter` nativo)
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] Worker de fila como serviço (systemd) + cron do Scheduler
- [ ] Command `FecharPlantoesVencidos` no Scheduler diário (`concluido` / `nao_cumprido`)
- [ ] Backup diário do MySQL (mysqldump + cron, reter 30 dias)
- [ ] Domínio custom (`medturno.com.br` ou similar)
- [ ] Documentação de deploy no README

**DoD:** app rodando em produção, HTTPS, Thallys usa de verdade.

---

## Fase 13 — Piloto com o Thallys
**Objetivo:** cliente usa em paralelo com o Excel por 1 mês.

- [ ] Onboarding presencial/call (30-60 min)
- [ ] Cadastro real dos 2 hospitais
- [ ] Cadastro real dos ~100 médicos
- [ ] Cadastro das recorrências reais
- [ ] Gerar escala do próximo mês no app
- [ ] Coleta de feedback diário/semanal
- [ ] Ajustes de UX prioritários
- [ ] Métrica: quantos médicos logaram na 1ª semana, quantas trocas ocorreram no app vs WhatsApp

**DoD:** Thallys assina embaixo "posso jogar fora o Excel".

---

## O que vem depois (v2)
Listado em [09-fora-do-escopo.md](09-fora-do-escopo.md).

Prioridades prováveis pós-MVP (com base no feedback):
1. Notificação WhatsApp (mata o grupo do zap)
2. Exportação do faturamento em PDF/CSV (fechamento pro RH)
3. Sync Google Calendar (feed .ics)
4. Auditoria completa
5. Admin geral (se decidir virar SaaS)
