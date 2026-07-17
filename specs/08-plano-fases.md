# 08 — Plano de fases

Fases sequenciais. Cada uma só começa quando a anterior está **em pé e testável**.

Sem estimativa em dias — depende do tempo que você mete no projeto. Cada fase é um "checkpoint" natural pra parar, testar com o Thallys, ajustar.

---

## Status atual (2026-07-16)

**Fases 0–11 concluídas** ✅ — todo o produto funcional está implementado e coberto por testes.

**Fase 12 (produção)** — tudo que é código já está pronto ✅ (security headers, rate limit,
health check, scheduler, `.env.example` documentado, guia de deploy no README). Falta só a
**infra do Thallys** ⏳: contratar VPS/domínio, rodar o deploy e ligar HTTPS + provedor de e-mail.

**Fase 13 (piloto)** — depende do deploy estar no ar.

Benchmarks de qualidade (todos verdes):
- `php artisan test` → **112/112 testes**, 308 asserts
- `phpstan analyse` (Larastan) → **0 erros**
- `pint --test` (code style) → **passou**

---

## Fase 0 — Kickoff e infraestrutura
**Objetivo:** projeto instalado, rodando local.

- [x] Node.js instalado (build do Tailwind/Vite)
- [x] Instalar PHP 8.3 + Composer (feito via winget — PHP 8.3.32 + Composer 2.10.2)
- [x] `laravel new medturno` (ou `composer create-project`)
- [x] Instalar **Breeze stack Livewire** (auth scaffolding + Tailwind)
- [x] SQLite em dev (`DB_CONNECTION=sqlite`)
- [x] Configurar Pint (format) + Larastan (estática) + Pest (testes)
- [x] Mailpit ou driver `log` pra email em dev
- [x] `.env.example` com todas as vars futuras documentadas
- [x] Repo Git organizado (app Laravel na raiz ou em `app/` — decidir no kickoff)

**Definition of Done:** `php artisan serve` abre a tela de login do Breeze. `php artisan test` roda verde.

---

## Fase 1 — Autenticação e usuários
**Objetivo:** dá pra criar usuário, logar, sair, redefinir senha.

- [x] Ajustar migration `users` do Breeze (telefone, CRM, especialidade — ver [04](04-modelo-dados.md))
- [x] Seed: cria 1 gestor de teste (`thallys@teste.com` / senha `mudar123`)
- [x] Login/logout/reset de senha — **já vem do Breeze**, só traduzir e estilizar
- [x] Middleware de proteção de rota (grupo `auth`)
- [x] Layout base (header + sidebar gestor / bottom-nav médico)
- [x] Testes Pest: login, reset, rota protegida

**DoD:** Thallys consegue logar e ver uma tela "olá, Thallys". Reset de senha funciona.

---

## Fase 2 — Hospitais e multi-tenancy
**Objetivo:** gestor cadastra hospitais, alterna entre eles, e o app inteiro respeita esse escopo.

- [x] Schema: `Hospital`, `HospitalMembership`
- [x] Migration + seed com 2 hospitais fictícios vinculados ao Thallys como gestor
- [x] Página `/gestor/hospitais` (listar, criar, editar) — componentes Livewire
- [x] Seletor de hospital no header (persiste em session)
- [x] Helper `currentHospital()` + middleware usado em toda query
- [x] `HospitalPolicy` (gestor só acessa hospital que gerencia)
- [x] Painel `/gestor` mostrando cards vazios do hospital selecionado
- [x] Testes Pest: gestor NÃO vê hospital de outro gestor (mesmo mudando ID na URL)

**DoD:** Thallys vê Santa Maria e São Gabriel, alterna, cada um mostra dados isolados (ainda vazios).

---

## Fase 3 — Equipe médica e convite
**Objetivo:** gestor convida médico por email, médico aceita e loga.

- [x] Schema: `Invitation`
- [x] Página `/gestor/equipe` (listar + filtrar)
- [x] Formulário `/gestor/equipe/convidar`
- [x] Serviço: cria user (ou reusa se email existe) + vínculo + convite + envia email
- [x] Página `/convite/aceitar?token=` (2 fluxos: user novo vs existente)
- [x] Reenvio de convite
- [x] Desativar médico do hospital
- [x] Mailable `ConviteMedico` em fila (SMTP real Resend/Brevo fica pra Fase 12)

**DoD:** você convida um email de teste seu, recebe, aceita, loga como médico e vê um dashboard vazio.

---

## Fase 4 — Quadros e templates
**Objetivo:** gestor define a estrutura da escala.

- [x] Schema: `ShiftBoard`, `ShiftBoardMembership`, `ShiftTemplate`
- [x] Página `/gestor/quadros` (CRUD)
- [x] Página `/gestor/quadros/:id` com 2 abas: Estrutura + Participantes
- [x] Modal "novo template" (dia, horário, vagas, valor R$, atravessa MN)
- [x] Botão "aplicar grade automática" (6h, 12h, 24h)
- [x] Vincular médicos ao quadro (checkboxes)
- [x] Validação: sobreposição de templates no mesmo dia

**DoD:** Thallys cria "Diurno UTI Santa Maria" com 1 vaga 07-19 seg-dom, e "Noturno" 19-07. Vincula 5 médicos.

---

## Fase 5 — Recorrências e geração de escala
**Objetivo:** gerar escala mensal automaticamente a partir das recorrências.

- [x] Schema: `Recurrence`, `Schedule`, `Shift` (só criação)
- [x] Página `/gestor/recorrencias` CRUD
- [x] Página `/gestor/escalas/nova` — escolhe hospital + quadro + mês
- [x] Serviço `ScheduleService::createDraft()` que popula shifts a partir de recorrências
- [x] Listagem `/gestor/escalas` com filtros
- [x] Testes: geração respeita quinzenal (paridade da data_referencia)

**DoD:** Thallys cria escala de agosto/2026 e vê 60 plantões já pré-preenchidos por recorrência + vagos.

---

## Fase 6 — Editor de escala (a tela mais crítica)
**Objetivo:** gestor arrasta médicos, edita plantões, publica.

- [x] Página `/gestor/escalas/:id` — calendário mensal (Livewire)
- [x] Componente `MonthGrid` desktop + `DayList` mobile
- [x] Modal "atribuir médico" (dropdown + detecção de conflito no lado do servidor)
- [x] Modal "editar plantão" (mudar médico, remover, observação)
- [x] Botão "Publicar" (transição rascunho → publicada, gera notificações + emails)
- [x] Botão "Nova versão" (edição pós-publicação)
- [x] Testes: publicar duas vezes = versão 2

**DoD:** Thallys termina a escala e publica. 5 médicos recebem email "sua escala de agosto está publicada".

---

## Fase 7 — Painel e escala do médico
**Objetivo:** médico enxerga o próprio dia-a-dia.

- [x] Página `/medico` (painel com próximo plantão + pendentes)
- [x] Página `/medico/escala` calendário só com plantões do médico
- [x] Filtro por hospital (se tem >1)
- [x] Bottom-nav mobile
- [x] Página `/medico/plantoes/:id` (detalhe)
- [x] Ação "Confirmar" (transição pendente → confirmado)
- [x] Notificação interna pro gestor ao confirmar

**DoD:** médico vê seus plantões e confirma um. Thallys recebe notificação.

---

## Fase 8 — Troca direta
**Objetivo:** médico passa plantão pra colega específico, colega aceita, gestor aprova.

- [x] Schema: `ShiftTransfer` (tipo=direta)
- [x] Modal "Passar para colega" no detalhe do plantão
- [x] Página `/medico/trocas` (recebidas + enviadas)
- [x] Página `/gestor/trocas` (pendentes)
- [x] Serviço `TransferService`: `requestDirect()`, `acceptByReceiver()`, `rejectByReceiver()`, `approve()`, `reject()`
- [x] Notificações + emails em cada etapa
- [x] Testes: máquina de estados completa

**DoD:** Dr. A passa plantão pro Dr. B, Dr. B aceita no app, Thallys aprova, plantão é do Dr. B (pendente).

---

## Fase 9 — Mural (anúncio)
**Objetivo:** médico anuncia plantão, colegas manifestam interesse, gestor escolhe.

- [x] Schema: `ShiftInterest`
- [x] Ação "Anunciar" no detalhe do plantão
- [x] Página `/medico/mural` (lista de plantões disponíveis do quadro)
- [x] Ação "Tenho interesse" / "Retirar interesse"
- [x] Gestor: lista de interessados no plantão + aprovar/rejeitar
- [x] Rejeição automática dos outros interessados quando um é aprovado
- [x] Cancelar anúncio (dono)

**DoD:** Dr. A anuncia, Drs. B e C manifestam interesse, Thallys escolhe B, C é notificado que perdeu, B confirma.

---

## Fase 10 — Faturamento
**Objetivo:** gestor define valores, app calcula quanto cada médico recebe no mês.

- [x] Campo `valor` em `ShiftTemplate` (padrão) e `Shift` (snapshot congelado)
- [x] Snapshot do valor ao atribuir médico ao plantão
- [x] Edição de valor de plantão individual (gestor, no modal de detalhe)
- [x] Página `/gestor/faturamento` — relatório mensal por médico (filtro mês/hospital, expandir detalhe por plantão, total geral)
- [x] Card "total a receber no mês" no painel do médico
- [x] Valor visível no detalhe do plantão (só pro dono e gestor)
- [x] Testes: snapshot não muda ao editar template; transferência preserva valor; médico não vê valor de colega

**DoD:** Thallys define diurno R$ 1.200 / noturno R$ 1.400 por hospital, fecha o mês e vê quanto cada médico recebe. Cada médico vê o próprio total.

---

## Fase 11 — Notificações e polimento
**Objetivo:** sistema de notificações interno completo + polimento visual.

- [x] `php artisan notifications:table` (tabela nativa do Laravel)
- [x] Classes `Notification` (database + mail) pra cada evento da spec [06](06-regras-negocio.md)
- [x] Página `/*/notificacoes`
- [x] Sino no header com contador de não-lidas (`wire:poll`)
- [x] Marca como lida ao abrir
- [x] Templates Blade de email (markdown mailables) pra todos os emails
- [x] Ícones de status com legenda (acessibilidade)
- [x] Loading states, skeletons, empty states

**DoD:** experiência inteira coesa, sem gambiarra visual.

---

## Fase 12 — Segurança e produção
**Objetivo:** subir pra VPS (ou Railway), endurecer.

Preparado no código (não depende de servidor) ✅ · Falta a infra do Thallys ⏳

- [ ] Contratar VPS (Hostinger/Contabo) ou Railway — PHP 8.3 + MySQL + nginx ⏳ (precisa do Thallys)
- [ ] Migrar dev DB pra MySQL (mesmas migrations) ⏳ (roda no deploy)
- [x] `.env` de produção (APP_ENV=production, APP_DEBUG=false) — documentado no `.env.example` + README
- [ ] HTTPS com Let's Encrypt (certbot) ⏳ (passo no README, roda no servidor)
- [x] Headers de segurança (CSP, X-Frame-Options, HSTS) via middleware — `App\Http\Middleware\SecurityHeaders`
- [x] Rate limit nas rotas de auth e convite (`RateLimiter` nativo) — login 5x, verify 6/min, convite 20/min
- [x] `SESSION_SECURE_COOKIE=true` — documentado no `.env.example` (ligar em produção)
- [x] Worker de fila como serviço (systemd) + cron do Scheduler — unit + cron documentados no README
- [x] Command `FecharPlantoesVencidos` no Scheduler diário (`concluido` / `nao_cumprido`)
- [x] Backup diário do MySQL (mysqldump + cron, reter 30 dias) — cron documentado no README
- [ ] Domínio custom (`medturno.com.br` ou similar) ⏳ (precisa do Thallys)
- [x] Documentação de deploy no README

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
