# Pendências para Licitações

**Atualizado em:** 04/08/2026  
**Produto:** DoctorTurn  
**Processos analisados:** TR 027/2021 (AEBES) e Cotação 68/2025 (AgSUS)

## 1. Situação real dos processos

Os dois processos originais já estão encerrados:

- **TR 027/2021:** recebimento de propostas encerrado em 28/04/2021 às 17h.
- **Cotação 68/2025:** recebimento de propostas encerrado em 25/03/2025 às 23h59.

Esses documentos devem ser usados como referência para preparar o DoctorTurn para republicações, renovações ou contratações equivalentes. Antes de enviar qualquer proposta, é necessário confirmar uma oportunidade atual com a AEBES, AgSUS ou outro órgão.

## 2. Aderência técnica interna

Os percentuais abaixo são calculados pelo painel interno a partir dos requisitos cadastrados em `TenderSeeder.php`. Eles medem implementação técnica e não representam probabilidade de vitória, habilitação jurídica ou parecer de uma comissão.

| Processo | Aderência técnica interna | Situação |
|---|---:|---|
| TR 027/2021 | 76% | Ainda possui lacunas técnicas específicas e exige habilitação jurídica completa |
| Cotação 68/2025 | 97% | Tecnicamente próxima de ficar demonstrável; falta registro de tempo de gestão |

## 3. Funcionalidades compartilhadas já implementadas

- Criação, montagem, publicação e visualização de escalas.
- Calendários mensal e semanal.
- Recorrências e múltiplas equipes/quadros.
- Gestão de ausências e substituições.
- Limites de horas e regras de conformidade.
- Trocas, anúncios e aprovação do gestor.
- Check-in e check-out por GPS e QR Code.
- Painel de presenças e tratamento de horários.
- Visão por UBS e painel da escala do dia.
- Perfis de administrador, gestor, médico, financeiro e gestor municipal.
- Relatórios financeiros por profissional, equipe e turno.
- Exportação em PDF e XLSX.
- Dashboards e integração Metabase presente no código.
- API autenticada para integração.
- Feed iCal para Google, Apple e Outlook Calendar.
- Notificações, lembretes e mural de recados.
- Política de privacidade/LGPD e controles de acesso.
- PWA responsiva para dispositivos móveis.

## 4. Pendências técnicas da Cotação 68/2025

### 4.1 Funcionalidade ausente

- [ ] **Registro do tempo dedicado à gestão das escalas e turnos.**
  - Criar registro automático ou manual de sessões de trabalho do gestor.
  - Salvar início, fim, duração, usuário, hospital e atividade.
  - Exibir relatório por período, gestor e unidade.
  - Permitir exportação e trilha de auditoria.

### 4.2 Evidências que precisam ser preparadas

- [ ] Demonstrar funcionamento mobile sem depender de desktop.
- [ ] Documentar os endpoints e a autenticação da API.
- [ ] Demonstrar check-in GPS e QR Code em ambiente real.
- [ ] Preparar relatório com saldos de horas, produtividade e performance.
- [ ] Preparar roteiro de demonstração da escala semanal do gestor municipal.
- [ ] Formalizar SLA de correção de falhas em até 24 horas.
- [ ] Formalizar suporte por chat, e-mail e telefone.
- [ ] Preparar cronograma de implantação e treinamento.

## 5. Pendências técnicas do TR 027/2021

- [ ] Divisão de um turno entre dois profissionais.
- [ ] Plantão de sobreaviso com identificação visual diferenciada.
- [ ] Anúncio de vagas em lote com filtros por equipe, período e tipo.
- [ ] Fixação do número de vagas por plantão.
- [ ] Bloqueio de trocas entre turnos com durações incompatíveis.
- [ ] Importação de usuários em lote por CSV/XLSX.
- [ ] Gestão global e auditoria de TAGs.
- [ ] Configuração de fuso horário, período noturno e finais de semana.
- [ ] Importação da agenda pessoal para identificar conflitos externos.
- [ ] Check-in offline com sincronização posterior.
- [ ] Aplicativo publicado na Google Play e App Store.
- [ ] Notificações push nativas.

## 6. O que o responsável pela empresa precisa fazer

### 6.1 Confirmar oportunidades atuais

- [ ] Contatar a AEBES e perguntar sobre nova contratação, republicação ou cadastro de fornecedores.
- [ ] Contatar a AgSUS e perguntar sobre nova cotação, renovação ou cadastro de fornecedores.
- [ ] Monitorar portais oficiais e sistemas de compras públicas.
- [ ] Confirmar número, prazo, canal de envio e versão atual do termo antes de preparar proposta.

### 6.2 Habilitação jurídica e fiscal

- [ ] Cartão CNPJ atualizado.
- [ ] Contrato social e alterações consolidadas.
- [ ] Documentos do representante legal.
- [ ] Certidão conjunta Federal/PGFN.
- [ ] Certidão Estadual.
- [ ] Certidão Municipal.
- [ ] Certificado de Regularidade do FGTS.
- [ ] Certidão Negativa de Débitos Trabalhistas (CNDT).
- [ ] Consulta/certidão TCU.
- [ ] Consulta/certidão CEIS.
- [ ] Certidão negativa de falência ou recuperação judicial.
- [ ] Declarações exigidas pelo processo, assinadas pelo representante legal.

As certidões têm validade limitada. Devem ser emitidas ou renovadas perto da data de envio da proposta.

### 6.3 Qualificação econômico-financeira

- [ ] Solicitar ao contador os balanços dos exercícios exigidos.
- [ ] Obter demonstrações contábeis e índices solicitados.
- [ ] Comprovar capacidade financeira para cumprir o contrato.
- [ ] Validar regime tributário e incidência de impostos sobre o preço.

### 6.4 Qualificação técnica e cases

- [ ] Solicitar ao Hospital Santa Maria carta em papel timbrado.
- [ ] Obter assinatura de responsável autorizado pelo hospital.
- [ ] Informar período de utilização e quantidade de profissionais atendidos.
- [ ] Descrever funcionalidades efetivamente utilizadas.
- [ ] Obter atestado de capacidade técnica.
- [ ] Reunir outros clientes ou hospitais que possam comprovar experiência.
- [ ] Produzir portfólio de implantações com evidências verificáveis.

### 6.5 Decisões comerciais

- [ ] Definir preço por usuário/mês.
- [ ] Definir valor mínimo aceitável para negociação.
- [ ] Calcular implantação, treinamento, suporte, infraestrutura, impostos e margem.
- [ ] Definir política de reajuste.
- [ ] Definir condições de suporte após a garantia.
- [ ] Nomear responsável pelo atendimento do SLA.
- [ ] Confirmar capacidade operacional para contratos de 12 e 36 meses.

### 6.6 Publicação dos aplicativos

- [ ] Criar conta Google Play Console.
- [ ] Criar conta Apple Developer.
- [ ] Disponibilizar um Mac para compilação e publicação iOS.
- [ ] Aprovar nome, descrição, screenshots e textos das lojas.
- [ ] Aprovar política de privacidade e declarações de uso de dados.

## 7. O que pode ser executado no projeto

### Prioridade imediata

- [ ] Implementar registro de tempo dedicado à gestão.
- [ ] Criar testes de aceitação vinculados aos requisitos dos editais.
- [ ] Preparar ambiente de demonstração com dados fictícios consistentes.
- [ ] Criar matriz requisito -> tela -> teste -> evidência.
- [ ] Atualizar os roadmaps antigos para refletir o código atual.
- [ ] Documentar formalmente a API.
- [ ] Preparar manual de implantação, treinamento e suporte.
- [ ] Gerar proposta e cronograma em PDF após receber dados e preços da empresa.

### Segunda etapa

- [ ] Importação de usuários por CSV/XLSX.
- [ ] Gestão global e auditoria de TAGs.
- [ ] Divisão de turno e sobreaviso.
- [ ] Vagas fixas e anúncio em lote.
- [ ] Bloqueio de trocas incompatíveis.
- [ ] Configurações de fuso e horários especiais.

### Terceira etapa

- [ ] Check-in offline com sincronização.
- [ ] Integração OAuth com agendas pessoais.
- [ ] Empacotamento e publicação Android/iOS.
- [ ] Notificações push nativas.
- [ ] Pacote completo de evidências para avaliação técnica.

## 8. Ordem recomendada

### Semana 1

1. Confirmar oportunidades atuais.
2. Solicitar documentos ao contador e jurídico.
3. Solicitar atestado ao Hospital Santa Maria.
4. Implementar registro de tempo de gestão.

### Semana 2

1. Montar ambiente de demonstração.
2. Criar testes e matriz de evidências.
3. Documentar API, implantação, treinamento e suporte.
4. Definir preço e condições comerciais.

### Semanas 3 e 4

1. Fechar lacunas menores do TR 027.
2. Preparar aplicativo Android.
3. Iniciar processo de publicação nas lojas.
4. Revisar LGPD, segurança e documentação jurídica.

## 9. Modelos existentes

Os modelos estão na pasta `specs/habilitacao/`:

- `checklist-certidoes.md`
- `modelo-proposta-cotacao-68.md`
- `modelo-cronograma-implantacao.md`
- `modelo-carta-case-hospital.md`
- `modelo-atestado-experiencia.md`
- `modelo-declaracoes.md`

## 10. Critério para considerar o DoctorTurn pronto

O produto estará pronto para uma oportunidade equivalente quando:

- Todos os requisitos obrigatórios estiverem implementados ou formalmente cobertos.
- Cada requisito possuir tela, teste e evidência demonstrável.
- O ambiente de demonstração estiver estável.
- A documentação jurídica, fiscal, técnica e financeira estiver válida.
- Existir ao menos um atestado de capacidade técnica verificável.
- A proposta estiver precificada, revisada e assinada.
- O prazo e o canal de envio da oportunidade atual estiverem confirmados.
