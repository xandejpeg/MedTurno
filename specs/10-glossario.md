# 10 — Glossário

Vocabulário do domínio. Se aparecer palavra fora daqui = padronizar ou explicar.

## Domínio médico

**Plantão**
Turno de trabalho médico em hospital, com hora início e fim. Ex: "plantão diurno de 12h no PS".
No código: `Shift`.

**Escala**
Documento que lista todos os plantões de um período (geralmente mensal) de um quadro específico. É o preenchimento concreto da estrutura.
No código: `Schedule`.

**Quadro (de plantão)**
Estrutura + equipe de médicos de uma área específica do hospital. Ex: "Quadro Diurno UTI Santa Maria".
No código: `ShiftBoard`.

**Template de turno**
Regra que define quando um plantão acontece dentro do quadro. Ex: "Todo sábado 07-19, 2 vagas".
No código: `ShiftTemplate`.

**Turno**
Sinônimo de plantão, mas às vezes se refere só ao intervalo de tempo (ex: "turno diurno" = 07-19). No app, tratamos como equivalente pra evitar confusão. Preferir "plantão" ao falar de uma instância concreta.

**Vaga**
Cada posição disponível num template de turno. Santa Maria tem 1 vaga/turno, São Gabriel 2. 1 vaga = 1 médico escalado.

**Recorrência**
Padrão fixo de plantão de um médico. Ex: "Dr. X todo terça noite, semanal" ou "Dr. Y sábados alternados, quinzenal".
No código: `Recurrence`.

**Escalado / escalar**
Verbo. "Escalar um médico num plantão" = atribuir esse médico àquela vaga.

**Plantonista**
Médico que faz plantão (que a gente chama só de "médico" no app, já que só médico existe).

**Diurno / Noturno**
Divisões clássicas: diurno geralmente 07-19, noturno 19-07 (atravessa meia-noite).

**Sobreaviso**
Médico "de prontidão" que não fica no hospital mas atende se chamado. **Não modelado no v1** — se aparecer, vira campo `tipo` no `ShiftTemplate`.

---

## Domínio do app

**Gestor**
Perfil que administra escalas de um ou mais hospitais. Ex: Thallys.
No código: `HospitalMembership.papel = 'gestor'`.

**Médico**
Perfil que aparece na escala e usa o app pra confirmar/trocar.
No código: `HospitalMembership.papel = 'medico'`.

**Convite**
Registro que representa o gestor querendo adicionar um médico ao hospital. Vira `User` quando aceito.
No código: `Invitation`.

**Rascunho (de escala)**
Escala em edição, só o gestor vê. Não gera notificação nem plantão real ainda.
Status: `rascunho`.

**Publicada (escala)**
Escala já divulgada, médicos veem, plantões ativos.
Status: `publicada`.

**Confirmar (plantão)**
Ação do médico dizendo "sim, vou fazer". Muda `pending` → `confirmed`.

**Passar / Repassar (plantão)**
Ação do médico de transferir seu plantão pra outro colega específico. Cria `ShiftTransfer` do tipo `direta`.

**Anunciar (plantão)**
Ação do médico de disponibilizar seu plantão no mural pra qualquer colega do quadro pegar. Cria `ShiftTransfer` do tipo `mural`.

**Mural**
Tela onde ficam listados os plantões `disponivel` do quadro. Local onde o médico manifesta interesse.
No código: rota `/medico/mural`.

**Manifestar interesse**
Ação do médico de dizer "eu topo pegar esse plantão do mural". Cria `ShiftInterest`.

**Trocar / Troca**
Termo guarda-chuva pra qualquer transferência de plantão entre médicos. Cobre "passar direto" e "aprovar interesse do mural". No código, `ShiftTransfer` unifica os dois.

**Valor do plantão**
Quanto o médico recebe por aquele plantão (R$). Definido pelo gestor no template (padrão) e **congelado (snapshot)** no plantão na hora da atribuição. Override individual permitido (ex: feriado).

**Faturamento**
Relatório mensal de quanto cada médico tem a receber (soma dos valores dos plantões `confirmado`/`concluido` do mês). Só cálculo e relatório — o pagamento em si acontece fora do app.

---

## Estados do plantão (cores)

| Status | Cor | Significa |
|---|---|---|
| `sem_medico` | Cinza | Vaga aberta, gestor ainda não escalou ninguém |
| `pendente` | Amarelo | Médico escalado, aguardando confirmar |
| `confirmado` | Verde | Médico confirmou, tudo OK |
| `em_troca` | Azul | Troca direta em curso (aguardando receptor ou gestor) |
| `disponivel` | Vermelho | Anunciado no mural, aguardando alguém pegar |
| `concluido` | Cinza escuro | Plantão já foi feito (passou a data) |
| `nao_cumprido` | Vermelho listrado | Plantão passou sem médico ou sem confirmação |
| `cancelado` | Riscado | Gestor cancelou manualmente |

Sempre acompanhar cor com **texto** e **ícone** (acessibilidade — [06-regras-negocio.md](06-regras-negocio.md) regra 13 do bloco final).

---

## Estados da troca (`ShiftTransfer`)

| Status | Significa |
|---|---|
| `aguardando_receptor` | Direta pedida, receptor ainda não respondeu |
| `aguardando_gestor` | Receptor aceitou (direta) ou é do mural aprovado, faltando gestor |
| `aprovada` | Gestor OK, plantão trocou de dono |
| `recusada` | Receptor ou gestor negou |
| `cancelada` | Dono desistiu antes da decisão |
| `expirada` | (v2) timeout sem resposta |

---

## Estados do interesse (`ShiftInterest`)

| Status | Significa |
|---|---|
| `pendente` | Médico manifestou, gestor não decidiu |
| `aprovado` | Gestor escolheu esse — vira `ShiftTransfer` aprovada |
| `rejeitado` | Gestor recusou explicitamente |
| `rejeitado_auto` | Outro interessado foi aprovado, esse cai automático |
| `retirado` | Médico desistiu antes da decisão |
| `cancelado_auto` | Dono do plantão cancelou o anúncio |

---

## Siglas comuns

- **CRM** — Conselho Regional de Medicina. Registro profissional do médico. Ex: "CRM/PE 12345"
- **UF** — Unidade Federativa (estado). Usado com CRM.
- **CPF** — Cadastro de Pessoa Física. **Não coletamos no v1** (só nome + email).
- **CNPJ** — Cadastro Nacional de PJ. Do hospital, opcional no v1.
- **UTI** — Unidade de Terapia Intensiva
- **PS** — Pronto-Socorro
- **PA** — Pronto-Atendimento
- **CC** — Centro Cirúrgico
- **DPO** — Data Protection Officer (LGPD)
- **LGPD** — Lei Geral de Proteção de Dados
- **MVP** — Minimum Viable Product (v1)
