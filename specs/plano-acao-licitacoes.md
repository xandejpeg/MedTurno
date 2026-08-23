# Plano de Ação Unificado — Aderência aos Dois Editais

**Objetivo:** adaptar o DoctorTurn para ficar **100% elegível e competitivo** nos dois processos, do início ao fim.
**Referências detalhadas:**
- [Roadmap TR 027/2021](roadmap-licitacao-tr-027-2021.md) — AEBES / Hospital Jayme Santos Neves (Serra/ES)
- [Roadmap Cotação 68/2025](roadmap-licitacao-cotacao-68-2025.md) — AgSUS / Atenção Primária à Saúde (Brasília/DF)

---

## 1. Verificação: os dois são realmente editais? Análise foi calculada errada?

Re-li os dois documentos originais em `C:\Users\xandao\Downloads\licitacoes ES` para confirmar a natureza de cada um. **A análise inicial precisava de um ajuste importante:**

### 1.1 TR 027/2021 — é um EDITAL formal de licitação ✅
- É um **Termo de Referência** completo, de uma **licitação pública** (tipo "Menor Preço").
- Exige **habilitação jurídica completa** (CNPJ, contrato social, certidões TCU/CEIS, etc.).
- Tem critério de julgamento (menor valor global) e de desempate (mais hospitais implantados).
- Vigência de 36 meses.
- **Conclusão:** é um edital de licitação pública tradicional, o mais burocrático dos dois.

### 1.2 Cotação 68/2025 — NÃO é licitação pública, mas EXIGE habilitação ⚠️ (correção da análise)
- É uma **Requisição de Proposta Comercial** de **"CONTRATAÇÃO DIRETA"** — ou seja, **não é um edital de licitação pública** no sentido estrito.
- **MAS**, na minha análise inicial, eu disse que era "mais simples, sem burocracia" — **isso estava parcialmente errado**. Re-lendo o documento, a Cotação 68 **também exige**:
  - **Qualificação Técnica:** comprovar experiência prévia em saúde (UBS, hospitais, planos, laboratórios) por atestados.
  - **Qualificação Econômico-Financeira:** balanços financeiros e capacidade de cumprir o contrato.
  - **Casos de Sucesso:** comprovar experiência em projetos similares de controle de escalas/plantões.
  - Proposta assinada pelo representante legal, com validade mínima de 60 dias.
- **Conclusão corrigida:** a Cotação 68 é **contratação direta** (processo mais rápido e sem disputa pública ampla), **mas não é "sem burocracia"** — exige comprovação de experiência e capacidade financeira, assim como o TR 027. A diferença é que **não há competição por menor preço** nem habilitação jurídica tão extensa.

### 1.3 Resumo da verificação
| Aspecto | TR 027/2021 | Cotação 68/2025 |
|---|---|---|
| É edital de licitação pública? | **Sim** | **Não** (contratação direta) |
| Exige habilitação jurídica extensa? | **Sim** | Parcial (qualificação técnica + econômica) |
| Exige comprovação de experiência? | Sim (e é critério de desempate) | **Sim** (casos de sucesso) |
| Competição por menor preço? | Sim | Não |
| Burocracia | Alta | Média |

**Portanto, minha análise anterior superestimou a "facilidade" da Cotação 68.** Ambos exigem experiência comprovada — o que reforça a estratégia de **construir cases primeiro** (piloto no Hospital Santa Maria).

---

## 2. Análise de conflitos: ser apto para um atrapalha o outro?

Verifiquei **ponto por ponto** se existe alguma exigência em um edital que **conflite** com a do outro. **Conclusão: NÃO há conflitos técnicos ou funcionais relevantes.** As exigências são **complementares e se sobrepõem em ~70%**. Veja a análise:

### 2.1 Requisitos comuns (construir uma vez, atende os dois)
- Escalas, planejamento e alocação de profissionais.
- Trocas e anúncios de plantão com aprovação do gestor.
- Check-in/check-out (um exige GPS, outro aceita GPS **ou** QR Code).
- Múltiplas escalas/equipes.
- Relatórios e dashboards.
- Gestão financeira e valores.
- Integração por API.
- Notificações e lembretes.
- LGPD e segurança.

### 2.2 Requisitos exclusivos do TR 027 (mais profundos)
- Regras de repetição avançadas (5 tipos).
- Gestão de ausências completa.
- Limites de horas e conformidade trabalhista detalhada.
- Grade de alocações rica (cores, semanal, sobreaviso, anúncio em lote).
- TAGs extensivas.
- App nativo nas lojas.
- Habilitação jurídica completa.

### 2.3 Requisitos exclusivos da Cotação 68 (foco APS)
- Visão por UBS/Município e painel "escala do dia" por UBS.
- Perfil de gestor municipal.
- Check-in por QR Code (o TR 027 foca em geolocalização).
- Cronograma de implantação e suporte contratual específico.

### 2.4 Pontos que PARECEM conflito, mas não são
- **Check-in GPS vs. QR Code:** um pede GPS, o outro aceita GPS **ou** QR. Solução: implementar **os dois métodos** — atende ambos sem conflito.
- **App nativo (TR 027) vs. PWA (atual):** o TR 027 pede app nas lojas; a Cotação 68 aceita "sistema ou aplicativo". Solução: publicar o app nas lojas atende os dois; o PWA continua funcionando.
- **Habilitação jurídica extensa (TR 027) vs. qualificação (Cotação 68):** são cumulativas, não conflitantes. Preparar a documentação completa atende os dois.

### 2.5 Conclusão sobre conflitos
**Não existe nenhuma exigência de um edital que impeça ou atrapalhe a outra.** Tudo que é mais profundo no TR 027 **agrega** valor ao produto e, consequentemente, também atende (e supera) a Cotação 68. A estratégia correta é **construir para o edital mais exigente (TR 027)** e, com isso, ficar automaticamente apto à Cotação 68 — não o contrário.

---

## 3. Plano de ação unificado — do início ao fim

Como não há conflito, o plano é **sequencial e único**, construindo para o edital mais completo. Cada fase entrega valor para os dois.

### FASE 0 — Fundação e case piloto (agora)
> Objetivo: ter um produto funcionando em produção com um caso real comprovado.

- [x] Sistema de escalas, trocas, publicação e notificações (já pronto).
- [ ] Implantar e estabilizar o piloto no **Hospital Santa Maria**.
- [ ] Documentar o piloto como **case de sucesso** (atestado/carta do hospital).
- [ ] Levantar e organizar a **documentação jurídica** da empresa (CNPJ, contrato social, certidões).

### FASE 1 — Conformidade, ausências e limites (bloqueadores técnicos)
> Atende: TR 027 (ausências, limites, conformidade) + Cotação 68 (limites, conformidade).

- [ ] **Gestão de ausências:** modelo, registro, justificativa, tratamento em turnos publicados, bloqueio de alocação em ausência.
- [ ] **Limite de horas por profissional:** configuração (mensal/semanal), bloqueio/alerta, consumo no app.
- [ ] **Regras de conformidade:** tempo máximo de turno, descanso entre plantões (com reforço noturno), detecção de conflito de agenda com alerta/bloqueio.
- [ ] **Painel de alertas de conformidade.**

### FASE 2 — Check-in / Check-out (GPS + QR Code)
> Atende: TR 027 (check-in GPS, painel de tratamento) + Cotação 68 (GPS ou QR Code).

- [ ] Registro de check-in/check-out por plantão.
- [ ] Check-in por **geolocalização (GPS)** com raio e endereço configuráveis.
- [ ] Check-in por **QR Code**.
- [ ] Janelas de tolerância e funcionamento offline com sincronização.
- [ ] Painel de tratamento (ajuste, restaurar, consolidar horários).

### FASE 3 — Grade rica e escalas avançadas
> Atende: TR 027 (recorrências, grade completa, TAGs) + Cotação 68 (múltiplas escalas, visão por UBS).

- [ ] **Regras de repetição** (semanal, mensal, por dia do mês, intervalo, semana do mês).
- [ ] **Grade de alocações completa:** cores por equipe, visão semanal, bloqueio de vagas, saldo de horas, sobreaviso, anúncio em lote, divisão de turno, fixar vagas.
- [ ] **Dados cadastrais completos** + sistema de **TAGs**.
- [ ] **Múltiplas escalas por equipe** e visão consolidada.
- [ ] **Visão por UBS/Município** e painel "escala do dia" por UBS.

### FASE 4 — Perfis, agenda e notificações
> Atende: TR 027 (agenda, lembretes) + Cotação 68 (perfis, gestor municipal, mural).

- [ ] **Integração Google/Apple/Outlook Calendar** (exportação e sincronização).
- [ ] **Lembretes programáveis** de plantão e de check-in/out.
- [ ] **Mural de recados** por escala/equipe.
- [ ] **Perfil financeiro** (só leitura de relatórios).
- [ ] **Perfil de gestor municipal** (visão semanal + notificações de alterações).
- [ ] Interface do gestor **otimizada para mobile**.

### FASE 5 — Relatórios, financeiro e API
> Atende: TR 027 (financeiro avançado, Metabase, API) + Cotação 68 (dashboards, API).

- [ ] Relatórios financeiros por profissional, equipe e turno (com bônus e filtros por TAG).
- [ ] Detalhe de horas por profissional com exportação xlsx.
- [ ] Dashboards executivos comparativos.
- [ ] **API REST pública documentada** (escalas, plantões, profissionais, check-in/out) com autenticação por token.

### FASE 6 — Aplicativo nas lojas e LGPD
> Atende: TR 027 (app nativo) + Cotação 68 (usabilidade, segurança).

- [ ] Publicar app na **App Store e Google Play** (wrapper do PWA ou nativo).
- [ ] Notificações push nativas.
- [ ] Revisão de **LGPD** (termos, consentimento, trilha de auditoria).

### FASE 7 — Habilitação, proposta e comercial
> Atende: TR 027 (habilitação jurídica) + Cotação 68 (qualificação técnica/econômica).

- [ ] Documentação jurídica completa (CNPJ, contrato social, certidões TCU/CEIS, atestados).
- [ ] **Qualificação técnica:** atestados de experiência em saúde.
- [ ] **Qualificação econômico-financeira:** balanços.
- [ ] **Casos de sucesso:** documentar implantações (piloto + outros).
- [ ] Cronograma de implantação e plano de suporte (para a Cotação 68).
- [ ] Elaborar e enviar as propostas.

---

## 4. Estratégia de entrada (ordem recomendada)

Como **não há conflito**, a ordem é por **custo/benefício e velocidade de receita**:

1. **Primeiro a Cotação 68/2025 (AgSUS):** contratação direta, 30 usuários, processo mais rápido. Usamos o piloto do Santa Maria como case. É a porta de entrada para gerar receita e um case formal em órgão público.
2. **Depois o TR 027/2021 (AEBES):** com o case da AgSUS + Santa Maria, ganhamos força no critério de desempate (mais hospitais implantados) e chegamos com o produto já completo das fases 1–6.

**Resumo:** construímos para o edital mais exigente (TR 027) nas Fases 1–6, o que nos deixa automaticamente aptos aos dois. Entramos primeiro na mais rápida (Cotação 68), usamos o case para fortalecer a segunda (TR 027).

---

## 5. Checklist final de elegibilidade (os dois)

- [ ] Produto com todas as funcionalidades das Fases 1–6.
- [ ] Piloto documentado (Hospital Santa Maria).
- [ ] Documentação jurídica completa.
- [ ] Atestados de experiência em saúde.
- [ ] Balanços financeiros.
- [ ] Casos de sucesso comprovados.
- [ ] Propostas elaboradas e enviadas nos prazos.

---

*Plano unificado de aderência do DoctorTurn aos dois editais. Verificação de natureza e conflitos realizada em 02/08/2026.*
