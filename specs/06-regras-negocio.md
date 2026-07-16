# 06 — Regras de negócio

## Máquina de estados do plantão (`shifts.status`)

Enum SQL:
```
sem_medico
pendente
confirmado
em_troca
disponivel
concluido
nao_cumprido
cancelado
```

### Transições permitidas

| De | Para | Quem dispara | Condição |
|---|---|---|---|
| `sem_medico` | `pendente` | Gestor | atribui médico |
| `sem_medico` | `nao_cumprido` | Sistema (job) | passou a data sem médico |
| `sem_medico` | `cancelado` | Gestor | remove plantão da escala |
| `pendente` | `confirmado` | Médico | clica "Confirmar" |
| `pendente` | `em_troca` | Médico | pede troca direta |
| `pendente` | `disponivel` | Médico | anuncia no mural |
| `pendente` | `sem_medico` | Gestor | tira o médico |
| `pendente` | `nao_cumprido` | Sistema (job) | passou a data sem confirmar |
| `pendente` | `cancelado` | Gestor | remove |
| `confirmado` | `em_troca` | Médico | pede troca direta |
| `confirmado` | `disponivel` | Médico | anuncia no mural |
| `confirmado` | `concluido` | Sistema (job) | passou a data |
| `confirmado` | `cancelado` | Gestor | remove com justificativa |
| `em_troca` | `pendente` | Gestor | aprova (novo médico precisa confirmar) |
| `em_troca` | `confirmado` | Médico A | receptor recusou, plantão volta como estava (se estava confirmado) |
| `em_troca` | `pendente` | Médico A | receptor recusou, plantão volta como estava (se estava pendente) |
| `em_troca` | `pendente`/`confirmado` | Médico A | cancela a proposta (volta ao anterior) |
| `disponivel` | `pendente` | Gestor | aprova interessado |
| `disponivel` | `pendente`/`confirmado` | Médico A | cancela anúncio |

### Transições **proibidas** (rejeitar no serviço)
- `concluido` → qualquer coisa
- `nao_cumprido` → qualquer coisa
- `cancelado` → qualquer coisa
- Qualquer estado → `sem_medico` fora de "gestor tirou" ou "escala em rascunho"

---

## Máquina de estados da escala (`schedules.status`)

```
rascunho → publicada
publicada → cancelada
publicada → arquivada
cancelada → arquivada
```

Proibido:
- `publicada` → `rascunho` (crie **nova versão** se precisar editar)
- `arquivada` → qualquer

---

## Máquina de estados da troca (`shift_transfers.status`)

```
aguardando_receptor → aguardando_gestor (receptor aceita)
aguardando_receptor → recusada (receptor recusa)
aguardando_receptor → cancelada (dono cancela)
aguardando_gestor → aprovada (gestor OK)
aguardando_gestor → recusada (gestor NÃO)
aguardando_receptor → expirada (timeout — v2, no v1 fica indefinido)
```

Ao entrar em `aprovada`:
- `shifts.medico_id` = `para_medico_id`
- `shifts.status` = `pendente` (o novo médico precisa confirmar)
- Se tipo `mural`: demais `shift_interests` viram `rejeitado_auto`

---

## Máquina de estados do interesse (`shift_interests.status`)

```
pendente → aprovado (gestor aprova)
pendente → rejeitado (gestor rejeita)
pendente → retirado (médico desiste)
pendente → rejeitado_auto (outro interessado foi aprovado)
pendente → cancelado_auto (dono cancelou anúncio)
```

---

## Regras invariantes (nunca podem ser violadas)

### Autorização (backend valida em toda rota)
1. Gestor só opera dentro de hospitais em que tem `hospital_memberships.papel='gestor'`
2. Médico só vê/opera plantões dos quadros em que tem `shift_board_memberships`
3. Médico só confirma/troca/anuncia plantão em que `shifts.medico_id = próprio user`
4. Médico não pode manifestar interesse no próprio plantão
5. Médico só manifesta interesse em plantão `disponivel` e do quadro em que participa

### Consistência de dados
6. Um `shift` publicado (escala publicada) **não pode ser apagado fisicamente** — só cancelado
7. Uma `schedule` publicada não pode voltar pra rascunho — cria nova versão
8. Não existem dois `hospital_memberships` iguais (unicidade `user+hospital+papel`)
9. Um médico não pode participar do mesmo quadro 2x
10. Uma `recurrence` só pode apontar pra template do quadro que o médico participa
11. Uma `shift_template` não pode ter `vagas < 1`
12. Uma `shift_template` com `atravessa_meia_noite=true` deve ter `hora_fim < hora_inicio`
13. Só pode existir **1 `shift_transfer` ativo** por `shift_id` (status em `aguardando_*`)
14. Só pode existir **1 `shift_interest` por (shift_id, medico_id)**

### Alertas (não bloqueiam, mas avisam o gestor)
15. Médico com plantão sobreposto em outro hospital → avisar
16. Médico com plantão < 6h de intervalo (descanso) → avisar
17. Médico com > X horas na semana (configurável no futuro; v1 não bloqueia)

### E-mail
18. Toda notificação por e-mail deve conter link direto pra ação no app
19. Nenhum e-mail carrega senha em texto puro
20. E-mails de convite têm token de uso único, hasheado no DB

### Notificações
21. Ao publicar escala → email + notificação interna pra todos os médicos afetados
22. Ao republicar (edição) → só afetados
23. Ao pedir troca direta → notif pro receptor
24. Ao aceitar/recusar troca → notif pro gestor + dono original
25. Ao aprovar/rejeitar troca → notif pra dono, receptor, e (se mural) interessados perdedores
26. Ao anunciar plantão → notif pro gestor
27. Ao manifestar interesse → notif pro gestor + dono original

### Anti-concorrência
28. Ao aprovar troca/interesse, o serviço usa transação SQL — se dois cliques simultâneos, só um vence
29. Confirmação de plantão é idempotente (2 cliques = 1 confirmação)

### Faturamento
30. Só gestor define/edita valores (do template e do plantão individual)
31. O valor do plantão é **congelado (snapshot)** no momento da atribuição do médico — editar o template depois **não** altera plantões existentes
32. Médico vê **somente os próprios valores** — nunca os dos colegas
33. Valor nunca negativo; plantão sem valor definido entra como R$ 0,00 no relatório, com aviso visual pro gestor
34. Transferência de plantão **leva o valor junto** — o novo médico herda o valor congelado (gestor pode editar manualmente depois)
35. O relatório mensal considera plantões `confirmado` e `concluido`; `cancelado` e `nao_cumprido` ficam de fora (exibidos à parte)

---

## Regras "moles" (pode configurar por hospital no futuro, v1 hardcoded)

- Convite expira em **7 dias**
- Reset de senha expira em **1 hora**
- Anúncio no mural fica aberto **até o gestor decidir ou até a data do plantão**
- Escala nova é gerada com plantões oriundos de recorrências ativas
- Plantão passa pra `concluido` **na primeira meia-noite após `termina_em`**
- Plantão passa pra `nao_cumprido` **se `inicia_em < now()` e status ainda `pendente` ou `sem_medico`**

---

## Cálculo de conflito (função pura reutilizável)

Dois plantões `A` e `B` conflitam se:
```
A.medico_id = B.medico_id
E  A.inicia_em < B.termina_em
E  B.inicia_em < A.termina_em
```
Independente de hospital. Usado ao:
- Atribuir médico a plantão (gestor)
- Aprovar troca (gestor)
- Aprovar interesse (gestor)
- Alertar (não bloquear no v1)
