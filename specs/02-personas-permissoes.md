# 02 — Personas e permissões

## Personas (v1)

### 1. Gestor
- Pessoa que hoje monta escala no Excel
- Ex: Thallys
- Pode gerir **1 ou mais hospitais**
- Usa mais no desktop (monta escala), mas também precisa aprovar troca no celular

### 2. Médico
- Plantonista que aparece na escala
- Trabalha em **1 ou mais hospitais** (comum: circula entre 2-3)
- Usa **quase 100% no celular**
- Quer ver a agenda dele, confirmar, trocar, pegar plantão do mural

### 3. (Fora do MVP) Admin da plataforma
- Só existirá quando o produto virar SaaS multi-cliente
- No v1, "admin" = quem tem acesso ao banco de dados. Ponto.

---

## Matriz de permissões (v1)

| Ação | Gestor | Médico |
|---|:-:|:-:|
| **Hospitais** | | |
| Cadastrar hospital | ✅ | ❌ |
| Editar hospital | ✅ | ❌ |
| Alternar entre hospitais que gerencia | ✅ | — |
| Ver hospitais em que atua | ✅ | ✅ |
| **Equipe médica** | | |
| Convidar médico | ✅ | ❌ |
| Editar dados internos do médico | ✅ | ❌ |
| Desativar médico no hospital | ✅ | ❌ |
| Ver lista de colegas do mesmo hospital | ❌ | ✅ (só nome+especialidade, pra troca direta) |
| **Quadros e templates** | | |
| Criar/editar quadro | ✅ | ❌ |
| Definir templates de turno (hora, vagas) | ✅ | ❌ |
| Aplicar grade automática | ✅ | ❌ |
| **Recorrências** | | |
| Cadastrar recorrência de médico | ✅ | ❌ |
| Ver própria recorrência | — | ✅ |
| **Escala** | | |
| Criar escala mensal (rascunho) | ✅ | ❌ |
| Atribuir médico ao plantão | ✅ | ❌ |
| Publicar escala | ✅ | ❌ |
| Ver escala em rascunho | ✅ | ❌ |
| Ver escala publicada | ✅ | ✅ (só a do quadro que participa) |
| **Plantão** | | |
| Confirmar próprio plantão | ❌ | ✅ |
| Pedir troca direta com colega específico | ❌ | ✅ (só do próprio plantão) |
| Aceitar/recusar troca direta recebida | ❌ | ✅ |
| Anunciar próprio plantão no mural | ❌ | ✅ |
| Cancelar anúncio (se ainda não aprovado) | ❌ | ✅ |
| Manifestar interesse em plantão do mural | ❌ | ✅ (só do mesmo quadro em que atua) |
| Aprovar/rejeitar troca ou interesse | ✅ | ❌ |
| Ver histórico do plantão | ✅ | ✅ (só dos próprios) |
| **Faturamento** | | |
| Definir valor padrão do template | ✅ | ❌ |
| Editar valor de plantão individual | ✅ | ❌ |
| Ver relatório mensal de todos os médicos | ✅ | ❌ |
| Ver próprios valores e total do mês | — | ✅ |
| **Notificações** | | |
| Receber notificação de ações que envolvem | ✅ | ✅ |
| Ver caixa de notificações | ✅ | ✅ |

## Regras de escopo (o que NUNCA pode)

1. Gestor **não vê** dados de hospital que não gerencia
2. Médico **não vê** escala de quadro em que não participa
3. Médico **não confirma** plantão de outro médico
4. Médico **não pede** troca de plantão que não é dele
5. Médico **não manifesta interesse** no próprio plantão anunciado
6. Médico **não interfere** em plantão já concluído (data passada)
7. Nenhum papel **apaga histórico**
8. Nenhum papel **apaga escala publicada** (só arquiva/cancela)
9. Médico **não vê valor** de plantão de colega — só os próprios

## Onde as regras são aplicadas
- **Backend sempre** (API valida em toda rota, mesmo que a UI esconda o botão)
- **UI opcional** (esconder botão indevido é bom UX, mas nunca é segurança)
