# 07 — Stack técnica

Toda decisão aqui tem 3 partes: **escolha · por que · alternativa rejeitada**.

> **Decisão 13/07/2026:** stack trocada de Next.js para **Laravel** — o domínio é 80% CRUD + regras + notificações + e-mail, tudo que o Laravel entrega pronto (Policies, Notifications database channel, Mail, Queues). Next.js foi considerado e rejeitado (registro abaixo).

## Contexto que empurra as decisões
- **~100 médicos**, ~800 plantões/mês → banco de verdade, não JSON
- **Multi-hospital desde dia 1** → schema relacional bem modelado
- **Usuário no celular 90% do tempo** (médico) → UI responsiva rápida
- **Time = 1 dev** → stack coesa, poucas partes móveis
- **Domínio = CRUD + máquina de estados + notificação** → framework batteries-included ganha

---

## Stack escolhida

### Framework: **Laravel 11 (PHP 8.3+)**
- **Por quê:** Eloquent + migrations + validação + Policies + Notifications + Mail + Queues + Scheduler nativos. Cada um desses itens é uma semana economizada vs montar na mão.
- **Mapeamento direto com nossas specs:**
  - Matriz de permissões ([02](02-personas-permissoes.md)) → **Policies** (1 policy por model)
  - Tabela `notifications` ([04](04-modelo-dados.md)) → **`php artisan notifications:table`** (vem pronta!)
  - E-mails de convite/troca → **Mailable + queue**
  - Job diário de `concluido`/`nao_cumprido` → **Scheduler** (`schedule:work`)
  - Máquinas de estado ([06](06-regras-negocio.md)) → Enums PHP 8.1 + services
- **Alternativa rejeitada:** Next.js 15 + Prisma + Auth.js — ótima stack, mas monta na mão o que o Laravel dá de graça (notificações, filas, policies). Deploy grátis na Vercel era o único ganho real.

### Frontend: **Livewire 3 + Alpine.js** (stack TALL)
- **Por quê:** UI reativa sem API separada e sem build de SPA. Componentes server-driven cobrem 95% das telas (listas, formulários, modais, calendário). Alpine cobre interatividade local (dropdowns, toggles).
- **Editor de escala** (tela mais rica): Livewire + Alpine dá conta de clique-pra-atribuir; drag-and-drop de verdade fica pra v2 se fizer falta (lib `@alpinejs/sort` como opção).
- **Alternativa rejeitada:** Inertia + React/Vue — bom, mas adiciona camada JS inteira pra um app onde formulário e lista dominam.
- **Alternativa rejeitada:** Blade puro + fetch — muito manual pra interatividade dos modais.

### Estilo: **Tailwind CSS 4 + componentes Blade próprios**
- **Por quê:** mesmo racional de antes (consistência, velocidade). Livewire + Tailwind é o par padrão da comunidade.
- Kit de partida: **Laravel Breeze (stack Livewire)** — auth scaffolding + layout base prontos.
- Componentes ricos quando precisar: **daisyUI** ou copiar padrões do **Flowbite** (grátis).

### Banco de dados: **MySQL 8** (ou MariaDB) em prod · **SQLite** em dev
- **Por quê:** SQLite local = zero setup (`DB_CONNECTION=sqlite` e pronto). MySQL em prod porque toda hospedagem PHP barata tem, e o Eloquent abstrai a diferença.
- **PostgreSQL** também serve se a hospedagem escolhida oferecer — nada no schema exige um ou outro.
- **Alternativa rejeitada:** MongoDB → domínio 100% relacional.

### Autenticação: **Laravel Breeze (Livewire stack)**
- **Por quê:** login, registro, reset de senha, verificação de e-mail — tudo scaffolded em 1 comando, código no projeto (editável).
- Papéis (gestor/médico) via `hospital_memberships.papel` + **Policies** — não precisa de pacote de roles (Spatie Permission seria overkill pra 2 papéis contextuais).
- Sessão em cookie httpOnly nativo, CSRF automático.

### Hash de senha: **bcrypt** (padrão Laravel) — ou argon2id via config
- 1 linha no `config/hashing.php` se quiser argon2id. Sem drama.

### E-mail transacional: **Resend** (driver SMTP) ou **Brevo**
- **Por quê:** Resend tem 3k emails/mês grátis e funciona via SMTP com o Mail do Laravel sem pacote extra. Brevo (ex-Sendinblue) dá 300/dia grátis como alternativa.
- Em dev: **Mailpit** (caixa de e-mail local) ou driver `log`.

### Validação: **Form Requests** nativos
- Toda rota de escrita tem seu FormRequest com `rules()` + `authorize()`.

### Datas/timezone: **Carbon** (nativo do Laravel)
- Tudo em UTC no banco, `America/Recife` na exibição (config `app.timezone` + cast).

### Filas: **database driver** (v1) → Redis se crescer
- **Por quê:** `QUEUE_CONNECTION=database` não exige serviço extra. E-mails e notificações saem da requisição web.
- Worker: `php artisan queue:work` como serviço no servidor.

### Agendador: **Laravel Scheduler**
- Job diário: transição de plantões pra `concluido`/`nao_cumprido`, expiração de convites.
- 1 linha de cron no servidor: `* * * * * php artisan schedule:run`.

### Testes: **Pest 3**
- **Por quê:** sintaxe limpa, padrão da comunidade Laravel moderna.
- Foco: máquinas de estado, policies (autorização), fluxos de troca/transferência, faturamento (snapshot).
- Feature tests com `RefreshDatabase` + SQLite in-memory = rápidos.

### Lint/format: **Laravel Pint** (nativo) + **Larastan** (análise estática)

### Admin/local dev: **Laravel Herd** (Windows) ou `php artisan serve`
- Herd = PHP + nginx + composer prontos no Windows sem Docker.

---

## Deploy

### Opção recomendada: **VPS barato + Laravel Forge-style manual**
- **Hostinger VPS** (~R$ 25-30/mês) ou **Contabo** (~€4,50/mês): PHP 8.3 + MySQL + nginx
- Deploy via `git pull` + script (ou **Ploi**/**RunCloud** free tier pra gerenciar)
- HTTPS grátis com Let's Encrypt (certbot)

### Opção alternativa: **Railway** (~US$ 5/mês)
- Suporta PHP via Nixpacks, MySQL plugin, deploy por push
- Mais simples, um pouco mais caro

### Custo total esperado: **R$ 25-40/mês** (vs R$0 do Next na Vercel — trade-off aceito conscientemente; cliente com 2 hospitais banca)

### E-mail: Resend/Brevo free tier · Domínio: ~R$ 40/ano (.com.br)

---

## Estrutura de pastas (Laravel padrão + organização de domínio)

```
MedTurno/
├── specs/                      ← contrato do produto (esta pasta)
├── md importante/              ← breafings originais
├── app/
│   ├── Enums/                  ← ShiftStatus, TransferStatus, ScheduleStatus…
│   ├── Models/                 ← User, Hospital, ShiftBoard, Shift, …
│   ├── Policies/               ← 1 por model (autorização contextual)
│   ├── Services/               ← regras que orquestram (Transfers, Schedules…)
│   │   ├── ScheduleService.php     ← criar rascunho, publicar, versionar
│   │   ├── ShiftService.php        ← atribuir, confirmar, snapshot de valor
│   │   ├── TransferService.php     ← troca direta + mural (máq. de estados)
│   │   └── BillingService.php      ← agregação de faturamento mensal
│   ├── Livewire/               ← componentes de tela
│   │   ├── Gestor/             ← Escalas, Equipe, Quadros, Trocas, Faturamento
│   │   └── Medico/             ← MinhaEscala, Mural, Trocas, Painel
│   ├── Notifications/          ← EscalaPublicada, TrocaPendente, … (database+mail)
│   ├── Mail/                   ← ConviteMedico, …
│   ├── Jobs/                   ← (se precisar de job custom além de notifications)
│   └── Console/Commands/       ← FecharPlantoesVencidos (scheduler)
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── resources/views/            ← Blade + layouts
├── routes/web.php              ← rotas (gestor.* e medico.* com middleware)
├── tests/
│   ├── Feature/                ← fluxos + policies
│   └── Unit/                   ← enums, regras puras
├── .env.example
└── composer.json
```

---

## Convenções de código
- **Idioma:** código/tabelas em inglês (`Shift`, `Schedule`), UI e comentários em PT-BR (igual spec anterior)
- **Nomes de tabela:** convenção Laravel (plural snake_case: `shifts`, `hospital_memberships`)
- **Enums PHP** para todo status — nunca string solta
- **Services** para toda mutação com regra (controller/Livewire chama service, service usa `DB::transaction`)
- **Policies em toda rota** — `$this->authorize()` sempre, mesmo com botão escondido na UI
- **Form Request** em todo POST/PUT/DELETE
- **Notifications do Laravel** com channels `database` (+ `mail` quando aplicável) — nossa tabela `notifications` da spec 04 é a nativa do framework

---

## Mapeamento specs → Laravel

| Item da spec | Implementação Laravel |
|---|---|
| `notifications` ([04](04-modelo-dados.md)) | `php artisan notifications:table` (nativa) |
| `password_resets` ([04](04-modelo-dados.md)) | `password_reset_tokens` (nativa do Breeze) |
| Máquina de estados do plantão ([06](06-regras-negocio.md)) | `App\Enums\ShiftStatus` + `ShiftService` valida transições |
| Matriz de permissões ([02](02-personas-permissoes.md)) | `HospitalPolicy`, `ShiftPolicy`, `SchedulePolicy`, … |
| Job diário concluído/não cumprido ([06](06-regras-negocio.md)) | Command + Scheduler |
| Convite com token hasheado ([03](03-fluxos.md)) | Model `Invitation` + `hash('sha256', $token)` + signed URL opcional |
| E-mails ([01](01-escopo-mvp.md)) | Mailables em fila (`ShouldQueue`) |
| Snapshot de valor ([06](06-regras-negocio.md) regras 30-35) | `ShiftService::assign()` copia `template->valor` pro `shift->valor` |
| Multi-tenancy por hospital ([02](02-personas-permissoes.md)) | Global scope OU escopo explícito via `currentHospital()` helper + middleware |

---

## O que NÃO vai ter no v1
- Redis (fila database resolve)
- Docker (Herd no dev, VPS puro em prod)
- Octane/Swoole (performance não é problema com 100 usuários)
- Spatie Permission (2 papéis contextuais = Policies simples)
- API REST pública / mobile nativo (Livewire serve HTML; app nativo é v3)
- WebSocket/Reverb (polling do Livewire `wire:poll.30s` resolve o mural e o sino)
- Inertia/React (decisão consciente — reavaliar só se o editor de escala provar que precisa)
