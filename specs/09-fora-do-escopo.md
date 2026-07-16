# 09 — Fora do escopo (v2 e além)

Lista **explícita** do que **não** entra na v1. Cada item vem com **motivo** de estar fora e **quando** considerar.

Se algo aqui aparecer como "urgente" durante o dev do v1 → **rejeitar** e apontar pra este arquivo.

---

## v2 (próxima iteração, depois do MVP em produção estabilizar)

### Financeiro avançado (pagamento real, recibo, NF, descontos)
> ⚠️ **Atualização 13/07/2026:** o faturamento **básico** (valor por plantão + relatório mensal por médico) **entrou no MVP** por pedido do Thallys. Ver [01-escopo-mvp.md](01-escopo-mvp.md). O que segue fora:

**Fora porque:** emitir recibo/RPA/NF, calcular descontos (INSS, ISS, IR) e pagar de verdade (Pix/transferência bancária) é território de contabilidade — risco alto, exige contador/advogado.
**Entra quando:** o relatório do MVP virar base oficial de pagamento e o cliente pedir.
**Escopo esperado:** exportação PDF/CSV do fechamento, recibo por médico, campo regime de contratação (CLT vs PJ), integração bancária.

### WhatsApp automático
**Fora porque:** integração via Meta Cloud API ou Twilio custa dinheiro, exige aprovação de template, e o grupo do WhatsApp deles continua funcionando enquanto isso.
**Entra quando:** app estiver em produção com uso real e Thallys quiser matar o grupo do zap.
**Escopo esperado:** provider abstrato (já modelado assim no v1 via `NotificationService`), implementação real com fallback silencioso.

### Sync bidirecional com Google Calendar
**Fora porque:** OAuth do Google + sync bidirecional é 1 semana de dev sozinho.
**Entra quando:** médicos pedirem depois de usar por 1-2 meses.
**Escopo esperado:** feed `.ics` read-only por médico (pode entrar antes) → OAuth + push depois.

### Auditoria completa
**Fora porque:** o `Notification` + `ShiftTransfer.decidido_por/em` já cobrem 80% do que importa no v1. Auditoria com IP, user-agent, before/after JSON é overkill agora.
**Entra quando:** aparecer disputa real ("quem apagou meu plantão?") ou compliance formal.
**Escopo esperado:** tabela `audit_logs` genérica + middleware que loga toda mutação de shift/schedule/transfer.

### Admin geral da plataforma
**Fora porque:** só faz sentido se virar SaaS multi-cliente.
**Entra quando:** decidir monetizar / abrir pra outros hospitais.
**Escopo esperado:** perfil `platform_admin`, dashboard cross-hospital, bloqueio de conta, faturamento.

### Recuperação automática (esqueci senha) do médico
**Fora porque:** já está no v1! Escrito errado no início. Ver [08-plano-fases.md](08-plano-fases.md) fase 1.

### Notificação push mobile (PWA / web push)
**Fora porque:** email + interna cobrem. Push exige service worker configurado, ícones, aprovação do usuário.
**Entra quando:** médicos reclamarem que perdem email.

### Relatórios PDF / Excel
**Fora porque:** o `Excel` que a gente tá substituindo. Ironicamente, pode voltar como export secundário.
**Entra quando:** RH do hospital pedir formato.
**Escopo esperado:** exportação da escala do mês em PDF (imprimir) e CSV (importar em outro sistema).

### Regras de carga horária máxima
**Fora porque:** cliente confirmou "não temos limite".
**Entra quando:** algum hospital novo cliente exigir.
**Escopo esperado:** config por hospital (X horas/mês) + alerta no gestor + bloqueio opcional.

### Templates de quadro reutilizáveis
**Fora porque:** com 2 hospitais e ~5 quadros, cria manual.
**Entra quando:** virar SaaS com dezenas de hospitais.
**Escopo esperado:** "Modelo UTI 12x12", "Modelo PS 6h" — biblioteca compartilhada.

### Log de "vou chegar atrasado" / "check-in"
**Fora porque:** não é uso descrito.
**Entra quando:** houver pedido explícito.

---

## v3+ (só se o produto crescer)

### Integração com Serpro (dados de empresa/CRM)
**Fora porque:** cadastro manual funciona. Serpro tem custo por consulta e complexidade de contrato.
**Entra quando:** virar SaaS pago e escala de cadastro justificar.

### Integração com sistema hospitalar (Tasy, MV, Soul MV)
**Fora porque:** cada hospital tem sistema diferente, cada integração é projeto próprio.
**Entra quando:** cliente enterprise pedir e pagar.

### Assinatura digital de escala (gov.br / ICP-Brasil)
**Fora porque:** não é requisito. Custo alto, valor duvidoso.
**Entra quando:** algum órgão fiscalizador exigir escala assinada.

### Prontuário / paciente / consulta ambulatorial
**Fora porque:** produto diferente. Sai do escopo de "gestão de plantão".
**Entra quando:** nunca (é outro produto).

### App mobile nativo (iOS/Android)
**Fora porque:** PWA responsiva atende. Custo de manter app nativo = 3x web.
**Entra quando:** métricas mostrarem necessidade (push nativa, cámera, etc.).

### Multi-idioma
**Fora porque:** só PT-BR precisa.
**Entra quando:** vender pra fora do Brasil.

### Multi-fuso horário complexo
**Fora porque:** Brasil tem 4 fusos, mas cada hospital fica em 1. Config no cadastro do hospital resolve.
**Entra quando:** hospital multi-cidade real aparecer.

---

## Compliance e legal (obrigatório antes de operar com dados reais em produção — mas fora do escopo de DEV do MVP)

### LGPD formal
**Fora do dev porque:** exige advogado, política redigida, DPO, DPIA. Isso é **antes de subir pra produção com dados reais**, não durante o dev.
**Quem faz:** cliente (Thallys ou consultor jurídico dele).
**Quando:** entre fase 11 (produção) e fase 12 (piloto).

**Precisa antes de piloto real:**
- Termos de uso
- Política de privacidade publicada
- Aceite registrado no cadastro
- Contato do DPO ou responsável

**v2:**
- Exportação de dados do médico (JSON/CSV)
- Direito ao esquecimento (anonimização, não delete físico)
- Registro de tratamento de dados

### CFM / normativos de escala médica
**Verificar** se algum normativo do Conselho Federal de Medicina exige formato específico de escala ou registro. **Tarefa do Thallys** confirmar. Se exigir, entra pra v2/v3.

---

## Regra de decisão

Quando aparecer "vamos fazer X também no MVP?":

1. X já está em [01-escopo-mvp.md](01-escopo-mvp.md)? → sim, faz.
2. X está aqui neste arquivo? → **não**, aponta pra cá.
3. X é novo? → discutir explicitamente com o cliente **antes** de codar. Adicionar aqui OU em 01 conforme decisão.

Escopo criativo é o inimigo do MVP.
