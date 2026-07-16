# 01 — Escopo do MVP (v1)

Esta é a lista **fechada** do que a v1 entrega. Se algo não estiver aqui, é **v2 ou v3** ([09-fora-do-escopo.md](09-fora-do-escopo.md)).

## Regra de ouro
Se dá pra viver sem no dia 1 e o cliente já sobrevive hoje com Excel + WhatsApp → **não é MVP**.

---

## ✅ Entra no MVP

### Multi-hospital (obrigatório desde o dia 1)
Thallys já opera 2 hospitais e quer suportar mais. O app já nasce multi-hospital.
- Cadastro de hospitais
- Seletor de hospital no topo da tela do gestor
- Médico vê escala consolidada de todos os hospitais em que atua

### Usuários e autenticação
- 2 papéis: **gestor** e **medico**
- Um usuário pode ser gestor em um hospital e médico em outro (raro, mas suportado)
- Login por e-mail + senha
- Recuperação de senha por e-mail
- Convite de médico via link único enviado por e-mail

### Equipe médica
- Gestor cadastra médico (nome, e-mail, telefone, CRM opcional, especialidade opcional)
- Sistema envia convite por e-mail com link único e senha temporária ou set-password
- Gestor pode desativar médico no hospital (sem apagar histórico)

### Quadros de plantão (estrutura da escala)
- Um hospital tem um ou mais **quadros** (ex: "Diurno UTI", "Noturno PS")
- Cada quadro tem **templates de turno**: dia da semana + hora início + hora fim + nº de vagas
- Templates com **plantão atravessando meia-noite** (ex: 19:00 → 07:00 do dia seguinte)
- **Vagas por template** (Santa Maria = 1, São Gabriel = 2) — múltiplos médicos no mesmo turno
- Aplicação de **grade automática** (ex: gerar 4 blocos de 6h) OU divisão manual

### Recorrência de escala
- Médicos podem ter padrão **semanal** (todo terça noite) ou **quinzenal** (a cada 15 dias no sábado)
- Recorrências geram os plantões automaticamente quando o gestor cria a escala mensal
- Gestor pode editar/remover plantões gerados por recorrência

### Escala mensal
- Escala pertence a um hospital + quadro + mês/ano
- Status: `rascunho` → `publicada`
- Rascunho: só gestor vê, edita livremente
- Publicada: médicos veem, recebem notificação, mudanças geram nova versão
- Interface visual: **calendário mensal** com plantões distribuídos por dia

### Plantão individual
- Cada plantão pertence a uma escala + template + data + médico + status
- Status: `sem_medico` → `pendente` → `confirmado` → (`disponivel` ou `em_troca`) → `transferido`
- Cores na UI: cinza / amarelo / verde / vermelho / azul (mais texto + ícone, nunca só cor)

### Confirmação de plantão
- Médico recebe plantão pendente (amarelo)
- Botão explícito **Confirmar** → verde
- Botão **Não posso cumprir** → dispara fluxo de troca

### Troca direta (médico → colega específico)
1. Médico A escolhe o plantão + escolhe Médico B (do mesmo hospital)
2. Médico B recebe notificação e aceita/recusa **no app**
3. Se aceita → gestor recebe pra aprovar
4. Gestor aprovado → plantão passa pro Médico B (fica pendente pra ele confirmar)
5. Todos os envolvidos são notificados a cada passo

### Anúncio no mural (médico → qualquer colega)
1. Médico anuncia plantão → fica vermelho, some da agenda dele como confirmado, aparece no mural
2. Outros médicos do mesmo quadro veem no mural e clicam "Tenho interesse"
3. Gestor escolhe entre os interessados (pode haver mais de 1) e aprova
4. Plantão vai pro novo médico (pendente de confirmação dele)
5. Demais interessados são notificados que o plantão foi para outra pessoa

### Faturamento (valores de plantão)
- Cada **template de turno tem valor padrão** em R$ definido pelo gestor (ex: diurno R$ 1.200, noturno R$ 1.400 — pode variar por hospital/quadro)
- Gestor pode **sobrescrever o valor de um plantão específico** (ex: feriado paga mais)
- O valor é **congelado (snapshot) no plantão** no momento da atribuição do médico — editar o template depois **não** muda plantões já criados
- **Transferência leva o valor junto**: o novo médico recebe pelo valor congelado (salvo edição manual do gestor)
- **Relatório mensal de faturamento**: gestor vê quanto cada médico tem a receber no mês, por hospital e consolidado, com detalhe por plantão
- **Médico vê só o próprio**: valor de cada plantão dele + total do mês
- É **somente cálculo e relatório** — o app não paga ninguém, não emite recibo/NF, não calcula descontos (isso é v2)

### Notificações
- **Internas** (badge/lista dentro do app): tudo
- **Por e-mail**: convite, plantão atribuído, plantão trocado, aprovação/rejeição de troca, publicação de escala
- **WhatsApp**: **NÃO** no v1 (grupo de WhatsApp deles continua existindo enquanto isso — não bloqueia)

### Histórico (rastreabilidade mínima)
- Cada plantão registra: quem criou, quando, quem foi trocando, quando foi aprovado
- Log simples por plantão (não é auditoria completa de sistema)

### Responsividade
- Precisa funcionar bem em **celular** (médico vai usar no celular 90% do tempo)
- Gestor pode usar desktop OU celular
- Calendário mensal: no celular, vira lista/dia (não força grade de 7 colunas em tela pequena)

---

## ❌ Não entra no MVP (fica pra v2 ou v3)

Confirmados pelo Thallys como "pra depois":
- **Pagamento real / recibo / RPA / NF / descontos (INSS, ISS, IR)** — o app calcula o faturamento, mas o pagamento continua fora
- **WhatsApp** (integração automática — grupo continua manual)
- **Admin geral da plataforma** (SaaS multi-cliente)
- **Integração Serpro** (CNPJ/CRM automáticos)
- **Assinatura digital / gov.br**
- **Relatórios PDF/Excel**
- **Auditoria completa (IP, user-agent, before/after JSON)**
- **LGPD formal** (política, exportação de dados, direito ao esquecimento) — precisa antes de operar com dados reais em produção, mas não bloqueia dev do MVP
- **Sync com Google Calendar** (feed `.ics` pode entrar num v1.5 se sobrar tempo)
- **Notificação push mobile / SMS**
- **Real-time (WebSocket)** — polling de 30s resolve

Detalhes em [09-fora-do-escopo.md](09-fora-do-escopo.md).

---

## Critérios de aceite da v1
A v1 está pronta quando o Thallys consegue **abandonar a planilha Excel** e fazer tudo abaixo no app:

1. Cadastrar os 2 hospitais dele
2. Cadastrar quadros de plantão (Diurno/Noturno) com vagas certas por hospital
3. Convidar os ~100 médicos por e-mail
4. Cadastrar recorrências dos médicos com plantão fixo semanal/quinzenal
5. Gerar a escala do mês seguinte em rascunho
6. Preencher os plantões restantes arrastando médicos
7. Publicar a escala → todos os médicos recebem e-mail
8. Um médico confirmar plantão (verde)
9. Um médico pedir troca direta pra colega → colega aceita no app → Thallys aprova
10. Um médico anunciar plantão no mural → outro pega → Thallys aprova
11. Ver histórico de tudo isso no plantão
12. Definir valores dos plantões (diurno/noturno por hospital) e ver o relatório de faturamento do mês por médico
13. Cada médico ver o próprio total a receber no mês
