# ESPECIFICAÇÃO TÉCNICA COMPLETA

## Plataforma SaaS Multi-Tenant de Gestão de Plantões Médicos

---

# 1. VISÃO GERAL DO PRODUTO
Desenvolver uma plataforma web SaaS para gestão de escalas e plantões médicos, capaz de atender múltiplos hospitais dentro do mesmo sistema.

A plataforma deverá permitir que gestores hospitalares:

- cadastrem hospitais;
- criem diferentes quadros de plantão;
- adicionem médicos;
- definam horários e divisões de plantão;
- montem escalas;
- publiquem escalas;
- acompanhem confirmações;
- recebam solicitações de transferência ou disponibilização de plantões;
- monitorem todas as alterações realizadas.

Os médicos deverão conseguir:

- acessar os hospitais e quadros aos quais foram vinculados;
- visualizar a escala completa;
- identificar seus próprios plantões;
- confirmar presença;
- informar que não poderão cumprir determinado plantão;
- disponibilizar um plantão para outros médicos;
- demonstrar interesse em assumir um plantão disponível;
- acompanhar notificações e alterações relacionadas aos seus plantões.

A plataforma deverá ser construída com arquitetura multi-tenant, garantindo separação lógica completa entre hospitais, gestores, médicos, escalas, quadros e atividades.

---

# 2. PRINCÍPIOS DO SISTEMA
O sistema deverá seguir os seguintes princípios:

1. Um mesmo sistema deverá atender vários hospitais.
2. Cada hospital deverá funcionar como uma organização isolada dentro da plataforma.
3. Um usuário administrador geral poderá visualizar toda a plataforma.
4. Um gestor hospitalar somente poderá acessar os hospitais aos quais estiver vinculado.
5. Um médico somente poderá acessar hospitais e quadros dos quais faça parte.
6. Todos os participantes de um quadro poderão visualizar a mesma escala.
7. Apenas gestores poderão modificar a estrutura e a escala do quadro.
8. Médicos poderão interagir apenas com os plantões atribuídos a eles ou disponibilizados por outros médicos.
9. Toda alteração importante deverá gerar histórico de auditoria.
10. Integrações externas deverão ser desacopladas da regra principal do sistema.

---

# 3. ESCOPO INICIAL DO MVP
O MVP deverá incluir:

- autenticação;
- multi-tenancy;
- cadastro de usuários;
- perfis e permissões;
- cadastro de hospitais;
- cadastro e convite de médicos;
- criação de quadros de plantão;
- configuração manual dos horários;
- aplicação de grades automáticas;
- criação de escalas;
- atribuição de médicos aos plantões;
- publicação de escalas;
- confirmação de plantão;
- disponibilização de plantão;
- manifestação de interesse;
- notificações internas;
- estrutura preparada para WhatsApp;
- estrutura preparada para Serpro;
- painel do administrador geral;
- painel do gestor;
- painel do médico;
- histórico de ações;
- logs de segurança;
- sistema responsivo para desktop e celular.

Não implementar inicialmente integrações reais com:

- API do Serpro;
- API oficial do WhatsApp;
- bots;
- gateways de SMS;
- assinatura eletrônica;
- pagamentos.

Essas integrações deverão ser representadas inicialmente por serviços mockados ou abstrações internas.

---

# 4. ARQUITETURA MULTI-TENANT

## 4.1 Conceito de tenant
Cada hospital deverá ser tratado como um tenant ou organização isolada.

Entretanto, como um mesmo gestor poderá administrar mais de um hospital, recomenda-se modelar:

- organização;
- hospital;
- vínculo do usuário com o hospital;
- permissões dentro do hospital.

Para o MVP, o hospital poderá funcionar diretamente como unidade principal do tenant.

## 4.2 Isolamento de dados
Todas as entidades relacionadas ao hospital deverão possuir referência obrigatória ao hospital ou organização correspondente.

Exemplos:

- usuários vinculados;
- quadros;
- escalas;
- plantões;
- convites;
- notificações;
- interesses;
- histórico de alterações.

Nenhuma consulta deve retornar dados de outro hospital sem autorização explícita.

## 4.3 Regra obrigatória
Toda consulta de dados internos deverá ser escopada pelo hospital atual.

Exemplo conceitual:

```
current_hospital.shift_boards
current_hospital.doctors
current_hospital.schedules
```
Nunca buscar entidades globais sem escopo quando o usuário estiver dentro do ambiente hospitalar.

## 4.4 Usuários em múltiplos hospitais
Um mesmo usuário poderá estar vinculado a vários hospitais.

Exemplo:

- um médico trabalha no Hospital A e no Hospital B;
- um gestor administra Hospital A e Hospital C.

O sistema deverá permitir alternância entre hospitais.

---

# 5. TIPOS DE USUÁRIOS

## 5.1 Administrador geral da plataforma
Perfil interno responsável pela administração completa do SaaS.

Permissões:

- visualizar todos os hospitais;
- visualizar todos os usuários;
- visualizar todos os gestores;
- visualizar todos os médicos;
- acessar qualquer quadro;
- acessar qualquer escala;
- visualizar atividades;
- visualizar auditorias;
- bloquear hospitais;
- bloquear usuários;
- ativar ou desativar contas;
- consultar métricas globais;
- acompanhar quantidade de hospitais;
- acompanhar quantidade de médicos;
- acompanhar quantidade de gestores;
- acompanhar escalas publicadas;
- acompanhar plantões confirmados;
- acompanhar plantões disponibilizados;
- acompanhar interesses em plantões;
- visualizar erros e falhas de integrações.

Esse perfil deverá possuir um dashboard exclusivo.

Nenhum gestor ou médico deverá visualizar esse dashboard.

---

## 5.2 Gestor de plantões
Perfil responsável pela administração de um ou mais hospitais.

Permissões:

- cadastrar hospital;
- editar dados do hospital;
- adicionar médicos;
- remover médicos;
- reenviar convites;
- criar quadros;
- editar quadros;
- excluir quadros sem escala publicada;
- configurar horários;
- aplicar grades automáticas;
- criar escalas;
- atribuir médicos;
- alterar médicos escalados;
- publicar escalas;
- cancelar escalas;
- visualizar confirmações;
- visualizar disponibilizações;
- visualizar interesses;
- aprovar ou rejeitar trocas futuras;
- visualizar histórico;
- enviar notificações;
- acessar relatórios.

Somente o gestor poderá modificar:

- estrutura do quadro;
- divisões de horário;
- médicos escalados;
- status geral da escala;
- configurações do quadro.

---

## 5.3 Médico
Perfil operacional vinculado a um ou mais hospitais.

Permissões:

- visualizar hospitais vinculados;
- visualizar quadros vinculados;
- visualizar escala completa;
- visualizar plantões próprios;
- confirmar plantão;
- disponibilizar plantão;
- cancelar disponibilização, quando permitido;
- demonstrar interesse em um plantão disponível;
- retirar interesse, quando permitido;
- visualizar notificações;
- editar perfil;
- adicionar foto;
- alterar senha;
- atualizar telefone;
- visualizar histórico pessoal.

O médico não poderá:

- alterar divisão do quadro;
- criar horários;
- editar escala;
- trocar outro médico;
- excluir plantões;
- adicionar médicos;
- publicar escalas;
- editar dados do hospital.

---

# 6. MODELO DE PERMISSÕES
O sistema deverá usar controle de acesso baseado em papéis.

Sugestão:

```
platform_admin
hospital_manager
doctor
```
Além do papel global, deverá existir vínculo contextual com o hospital.

Exemplo:

```
User
HospitalMembership
Role
Hospital
```
Um mesmo usuário poderá ser:

- médico no Hospital A;
- gestor no Hospital B;
- médico no Hospital C.

A permissão deverá ser verificada com base no hospital selecionado.

---

# 7. FLUXO DO ADMINISTRADOR GERAL

## 7.1 Dashboard geral
O dashboard deverá apresentar:

- total de hospitais ativos;
- total de hospitais bloqueados;
- total de gestores;
- total de médicos;
- total de quadros;
- total de escalas;
- escalas publicadas no mês;
- plantões pendentes;
- plantões confirmados;
- plantões disponibilizados;
- interesses abertos;
- convites pendentes;
- falhas de notificações;
- últimas atividades;
- novos hospitais cadastrados;
- crescimento de usuários;
- filtros por período.

## 7.2 Gestão de hospitais
O administrador poderá:

- listar hospitais;
- buscar por nome;
- buscar por CNPJ;
- visualizar gestores;
- visualizar médicos;
- visualizar quadros;
- acessar detalhes;
- ativar;
- suspender;
- bloquear;
- adicionar observação interna.

## 7.3 Gestão de usuários
O administrador poderá:

- listar usuários;
- filtrar por tipo;
- buscar por nome;
- buscar por CPF;
- buscar por telefone;
- verificar hospitais vinculados;
- verificar último acesso;
- bloquear;
- desbloquear;
- redefinir status da conta.

---

# 8. FLUXO INICIAL DO GESTOR

## 8.1 Primeiro acesso
Quando o gestor acessar o sistema sem nenhum hospital cadastrado ou vinculado, deverá visualizar uma tela de boas-vindas.

Conteúdo sugerido:

```
Bem-vindo à plataforma de gestão de plantões.

Para começar, adicione o primeiro hospital que você administra.
```
Botão principal:

```
Adicionar hospital
```

## 8.2 Cadastro do hospital
O fluxo deverá solicitar inicialmente:

- CNPJ;
- nome fantasia;
- razão social;
- telefone;
- e-mail;
- endereço;
- cidade;
- estado;
- CEP;
- complemento;
- nome do responsável;
- observações.

No MVP, o CNPJ poderá ser inserido manualmente.

O sistema deverá possuir um botão ou estrutura futura:

```
Consultar dados pelo CNPJ
```
Inicialmente, esse botão poderá usar um serviço mockado.

## 8.3 Estrutura futura do Serpro
Criar uma interface de serviço:

```
CompanyDataProvider
```
Método esperado:

```
lookup_by_cnpj(cnpj)
```
Retorno esperado:

```
legal_name
trade_name
registration_status
address
city
state
postal_code
phone
email
```
Inicialmente implementar:

```
MockCompanyDataProvider
```
Depois substituir por:

```
SerproCompanyDataProvider
```
A regra de negócio não deve depender diretamente do Serpro.

---

# 9. CADASTRO E CONVITE DE MÉDICOS

## 9.1 Cadastro pelo gestor
Dentro do hospital, o gestor deverá acessar:

```
Equipe médica
```
Botão:

```
Adicionar médico
```
Campos iniciais:

- CPF;
- nome;
- telefone;
- e-mail opcional;
- especialidade opcional;
- CRM opcional;
- estado do CRM opcional;
- observação interna.

No futuro, nome e outros dados poderão ser preenchidos pelo Serpro ou outra fonte autorizada.

## 9.2 CPF
O CPF será usado como identificador de convite e associação.

No MVP:

- validar apenas formato;
- remover caracteres especiais;
- impedir duplicidade incorreta;
- permitir que o mesmo CPF esteja em vários hospitais;
- impedir dois usuários diferentes com o mesmo CPF.

O CPF deverá ser armazenado de forma segura.

Recomendação:

- criptografar o campo;
- manter hash separado para busca e unicidade;
- nunca exibir o CPF completo para usuários sem permissão.

Exibição mascarada:

```
***.***.***-12
```

## 9.3 Telefone
O telefone será usado futuramente para WhatsApp.

Armazenar em formato internacional padronizado.

Exemplo:

```
+5511999999999
```
Normalizar o número antes de salvar.

## 9.4 Criação do convite
Ao adicionar o médico, o sistema deverá criar um registro de convite.

O convite deverá conter:

- hospital;
- CPF;
- telefone;
- e-mail opcional;
- gestor responsável;
- token único;
- data de criação;
- data de expiração;
- status;
- data de aceite;
- usuário criado ou vinculado.

Status possíveis:

```
pending
sent
opened
accepted
expired
cancelled
failed
```

## 9.5 Link de convite
Estrutura sugerida:

```
/app/invitations/accept?token=TOKEN
```
O token deverá:

- ser aleatório;
- ser longo;
- não ser previsível;
- expirar;
- só poder ser usado uma vez;
- ser armazenado preferencialmente como hash.

## 9.6 Usuário já existente
Caso o CPF informado já pertença a um usuário existente:

- não criar uma segunda conta;
- criar apenas vínculo com o novo hospital;
- enviar convite para aceitar participação no hospital;
- após aceite, o hospital aparecerá na conta existente.

## 9.7 Usuário novo
Caso o CPF ainda não exista:

O link deverá direcionar para criação de conta.

Campos:

- CPF previamente vinculado e bloqueado;
- nome completo;
- telefone;
- e-mail;
- senha;
- confirmação de senha;
- foto de perfil opcional;
- CRM opcional;
- estado do CRM;
- especialidade;
- aceite dos termos;
- aceite da política de privacidade.

Após cadastro:

- ativar a conta;
- aceitar o convite;
- criar vínculo com o hospital;
- permitir acesso ao quadro.

---

# 10. INTEGRAÇÃO FUTURA COM WHATSAPP
Não implementar envio real inicialmente.

Criar uma abstração:

```
NotificationProvider
```
Métodos:

```
send_invitation
send_schedule_published
send_shift_assigned
send_shift_updated
send_shift_available
send_interest_received
send_interest_accepted
send_interest_rejected
send_shift_reminder
```
Implementação inicial:

```
MockWhatsAppNotificationProvider
```
Essa implementação deverá:

- registrar a mensagem no banco;
- marcar como simulada;
- permitir visualizar o conteúdo no ambiente de desenvolvimento;
- não fazer chamada externa.

Implementação futura:

```
WhatsAppBusinessNotificationProvider
```
Possíveis fornecedores futuros:

- Meta WhatsApp Cloud API;
- Twilio;
- Zenvia;
- Infobip;
- outro provedor homologado.

A regra do sistema não deverá depender de fornecedor específico.

---

# 11. EQUIPE MÉDICA DO HOSPITAL
O gestor deverá ter uma tela com:

- médicos ativos;
- médicos convidados;
- convites pendentes;
- convites expirados;
- médicos bloqueados;
- médicos removidos.

Colunas sugeridas:

- foto;
- nome;
- CPF mascarado;
- telefone;
- especialidade;
- CRM;
- status da conta;
- status do convite;
- quadros vinculados;
- último acesso;
- ações.

Ações:

- visualizar perfil;
- editar informações internas;
- reenviar convite;
- cancelar convite;
- adicionar a quadro;
- remover de quadro;
- bloquear no hospital;
- remover do hospital.

Remover do hospital não deverá excluir o usuário globalmente.

---

# 12. QUADROS DE PLANTÃO

## 12.1 Conceito
Um hospital poderá possuir vários quadros de plantão.

Exemplos:

- Pronto-socorro;
- UTI Adulto;
- UTI Pediátrica;
- Clínica Médica;
- Pediatria;
- Cardiologia;
- Centro Cirúrgico;
- Unidade Noturna;
- Retaguarda;
- Ambulatório.

## 12.2 Campos do quadro
Cada quadro deverá possuir:

- hospital;
- nome;
- descrição;
- setor;
- unidade;
- cor identificadora opcional;
- status;
- quadro principal ou secundário;
- gestor responsável;
- participantes;
- configuração de horários;
- data de criação;
- data de atualização.

Status:

```
draft
active
inactive
archived
```

## 12.3 Participantes do quadro
O gestor deverá poder selecionar quais médicos do hospital participam daquele quadro.

Um médico só poderá:

- visualizar o quadro;
- receber plantões;
- demonstrar interesse;

se estiver vinculado ao quadro.

## 12.4 Quadro principal
O hospital poderá possuir um quadro definido como principal.

Somente um quadro principal por hospital.

---

# 13. ESTRUTURA VISUAL DO QUADRO

## 13.1 Estrutura inicial
O quadro deverá começar vazio, mostrando apenas os sete dias:

- segunda-feira;
- terça-feira;
- quarta-feira;
- quinta-feira;
- sexta-feira;
- sábado;
- domingo.

Inicialmente, não haverá divisões internas de horário.

## 13.2 Controle exclusivo do gestor
Somente o gestor poderá:

- adicionar divisões;
- remover divisões;
- editar intervalos;
- aplicar modelos;
- reorganizar horários;
- duplicar estrutura;
- limpar estrutura.

## 13.3 Divisão manual
O gestor deverá conseguir adicionar um intervalo manualmente.

Campos:

- horário inicial;
- horário final;
- dias aos quais será aplicado;
- nome opcional do período;
- quantidade de vagas;
- observação opcional.

Exemplo:

```
Início: 00:00
Fim: 06:00
Dias: segunda a domingo
Nome: Madrugada
```

## 13.4 Regras de intervalo
O sistema deverá:

- impedir intervalo com início igual ao fim, salvo plantão de 24 horas explicitamente definido;
- impedir sobreposição dentro do mesmo dia;
- permitir plantão atravessando a meia-noite;
- validar horários;
- permitir intervalos diferentes por dia;
- permitir quantidade variável de vagas por bloco;
- permitir múltiplos médicos no mesmo horário, quando configurado.

## 13.5 Plantões atravessando a meia-noite
Exemplo:

```
19:00 até 07:00
```
Esse plantão deverá ser tratado como um único plantão que começa em um dia e termina no dia seguinte.

A interface deverá exibir isso claramente.

## 13.6 Grades predefinidas
O gestor poderá aplicar grades automáticas.

Opções iniciais:

- 1 hora;
- 2 horas;
- 4 horas;
- 6 horas;
- 8 horas;
- 12 horas;
- 24 horas.

Exemplo de divisão em 6 horas:

```
00:00–06:00
06:00–12:00
12:00–18:00
18:00–00:00
```
Ao aplicar uma grade:

- criar divisões em todos os dias selecionados;
- não atribuir médicos;
- não publicar automaticamente;
- permitir edição posterior;
- solicitar confirmação antes de substituir estrutura existente.

## 13.7 Modelos personalizados
O gestor poderá futuramente salvar um modelo de quadro.

Exemplo:

```
Modelo UTI 12x12
Modelo Pronto-Socorro 6h
Modelo Final de Semana
```
No MVP, essa funcionalidade poderá ficar preparada, mas não necessariamente ativa.

---

# 14. ESCALA

## 14.1 Diferença entre quadro e escala
O quadro representa a estrutura.

A escala representa o preenchimento daquela estrutura dentro de um período específico.

Exemplo:

```
Quadro: UTI Adulto
Escala: Agosto de 2026
```

## 14.2 Campos da escala

- hospital;
- quadro;
- nome;
- período inicial;
- período final;
- status;
- versão;
- criador;
- data de publicação;
- data de fechamento;
- observações.

Status:

```
draft
published
closed
cancelled
archived
```

## 14.3 Escala em rascunho
Enquanto estiver em rascunho:

- somente gestores visualizam;
- médicos não recebem notificação;
- plantões podem ser editados livremente;
- horários podem ser ajustados;
- médicos podem ser substituídos.

## 14.4 Publicação
Quando o gestor publicar:

- a escala ficará visível para os médicos;
- os plantões atribuídos aparecerão como pendentes;
- notificações internas serão geradas;
- notificações de WhatsApp serão enfileiradas de forma simulada;
- será criada uma versão imutável para auditoria;
- alterações futuras deverão ser registradas.

## 14.5 Nova versão
Caso uma escala publicada seja alterada:

- aumentar número da versão;
- registrar quem alterou;
- registrar o que mudou;
- notificar somente os afetados quando possível;
- notificar todos quando a alteração for geral;
- preservar histórico anterior.

---

# 15. PLANTÃO INDIVIDUAL
Cada plantão deverá possuir:

- hospital;
- quadro;
- escala;
- data;
- início;
- fim;
- médico atribuído;
- status;
- quantidade de vagas;
- observações;
- criador;
- data de criação;
- data de confirmação;
- data de disponibilização;
- versão.

## 15.1 Status do plantão
Status sugeridos:

```
unassigned
pending_confirmation
confirmed
available
interest_pending
transfer_pending
transferred
cancelled
completed
missed
```

## 15.2 Cores da interface

### Cinza
Plantão ainda sem médico.

```
unassigned
```

### Amarelo
Plantão atribuído, aguardando confirmação.

```
pending_confirmation
```

### Verde
Plantão confirmado pelo médico.

```
confirmed
```

### Vermelho
Plantão disponibilizado pelo médico.

```
available
```

### Azul ou roxo
Plantão com manifestação de interesse aguardando decisão.

```
interest_pending
```
As cores não deverão ser a única forma de identificação.

Também deverá existir:

- texto;
- ícone;
- legenda;
- etiqueta de status.

Isso é importante para acessibilidade.

---

# 16. ATRIBUIÇÃO DE MÉDICOS
O gestor deverá conseguir clicar em um bloco e selecionar um médico.

Informações exibidas:

- nome;
- foto;
- especialidade;
- disponibilidade futura;
- quantidade de plantões na escala;
- possíveis conflitos.

Ao atribuir:

- o plantão fica amarelo;
- status fica `pending_confirmation`;
- médico recebe notificação;
- atividade é registrada.

## 16.1 Conflitos
O sistema deverá detectar:

- plantões simultâneos no mesmo hospital;
- plantões simultâneos em hospitais diferentes;
- intervalos sobrepostos;
- carga horária excessiva configurável;
- descanso insuficiente configurável.

Inicialmente, o sistema poderá apenas alertar, sem bloquear.

Exemplo:

```
Atenção: este médico já possui um plantão entre 18:00 e 06:00.
```

---

# 17. VISÃO DO MÉDICO

## 17.1 Dashboard do médico
O médico deverá visualizar:

- próximo plantão;
- plantões pendentes de confirmação;
- plantões confirmados;
- plantões disponibilizados;
- interesses enviados;
- notificações recentes;
- hospitais vinculados;
- calendário resumido.

## 17.2 Visualização do quadro
Todos os médicos vinculados ao quadro deverão visualizar a mesma escala.

Porém, os próprios plantões deverão ter destaque visual.

Exemplo:

- borda mais forte;
- etiqueta “Seu plantão”;
- filtro “Mostrar apenas meus plantões”.

## 17.3 Filtros

- todos;
- meus plantões;
- pendentes;
- confirmados;
- disponíveis;
- por semana;
- por mês;
- por hospital;
- por quadro.

---

# 18. CONFIRMAÇÃO DE PLANTÃO
Quando o médico receber um plantão amarelo, poderá abrir os detalhes.

Botões:

```
Confirmar plantão
Não posso cumprir
```
Ao confirmar:

- status muda para `confirmed`;
- cor muda para verde;
- registrar data e hora;
- registrar usuário;
- notificar gestor;
- atualizar histórico.

Confirmação deve exigir uma ação explícita.

Não confirmar automaticamente ao visualizar.

---

# 19. DISPONIBILIZAÇÃO DE PLANTÃO
Caso o médico não possa ou não queira realizar o plantão, deverá poder disponibilizá-lo.

Botão:

```
Disponibilizar plantão
```
O sistema poderá solicitar:

- motivo opcional;
- observação opcional;
- urgência;
- confirmação da ação.

Após confirmar:

- status muda para `available`;
- cor muda para vermelho;
- outros médicos do mesmo quadro podem visualizar;
- outros médicos podem demonstrar interesse;
- gestor recebe notificação;
- atividade é registrada.

## 19.1 Restrições
O médico só poderá disponibilizar:

- plantões atribuídos a ele;
- plantões ainda não iniciados;
- plantões não cancelados;
- plantões que não estejam concluídos;
- plantões dentro das regras do hospital.

## 19.2 Plantão confirmado e depois disponibilizado
Deverá ser permitido, desde que:

- ainda esteja dentro do prazo;
- o hospital permita;
- fique registrado que houve confirmação anterior;
- o gestor seja notificado.

---

# 20. MANIFESTAÇÃO DE INTERESSE
Médicos do mesmo quadro poderão visualizar plantões vermelhos.

Botão:

```
Tenho interesse
```
Ao clicar:

- criar manifestação de interesse;
- registrar médico;
- registrar data;
- notificar gestor;
- notificar o médico original;
- alterar indicação visual.

## 20.1 Campos do interesse

- plantão;
- médico interessado;
- status;
- observação;
- data de criação;
- data de decisão;
- responsável pela decisão.

Status:

```
pending
accepted
rejected
withdrawn
expired
```

## 20.2 Múltiplos interessados
O mesmo plantão poderá receber vários interessados.

A lista deverá mostrar:

- nome;
- foto;
- especialidade;
- horário da manifestação;
- possíveis conflitos;
- carga horária na semana.

## 20.3 Regra inicial de aprovação
Para segurança, a transferência não deverá ocorrer automaticamente no MVP.

O gestor deverá aceitar um dos interessados.

Após aprovação:

- médico anterior deixa de ser responsável;
- novo médico passa a ser responsável;
- novo plantão fica amarelo aguardando confirmação do novo médico;
- demais interesses são rejeitados automaticamente;
- todos os envolvidos são notificados;
- histórico é preservado.

## 20.4 Confirmação do novo médico
Depois de aprovado pelo gestor, o novo médico deverá confirmar.

Até a confirmação:

```
transfer_pending
```
Após confirmação:

```
transferred
confirmed
```

---

# 21. CANCELAMENTO DA DISPONIBILIZAÇÃO
O médico original poderá retirar o plantão da disponibilidade se:

- nenhum interesse tiver sido aceito;
- o gestor ainda não tiver aprovado transferência;
- o plantão ainda não tiver iniciado.

Ao cancelar:

- retornar ao estado anterior;
- se estava confirmado, retornar para confirmado;
- se estava pendente, retornar para pendente;
- notificar interessados;
- registrar histórico.

---

# 22. NOTIFICAÇÕES

## 22.1 Canais
Inicialmente:

- notificações internas;
- e-mail opcional;
- fila mockada para WhatsApp.

Futuramente:

- WhatsApp;
- push notification;
- SMS.

## 22.2 Eventos que geram notificação

### Para todos do quadro

- nova escala publicada;
- escala cancelada;
- alteração estrutural importante;
- nova versão da escala, quando geral.

### Para médico específico

- convite recebido;
- adicionado a hospital;
- adicionado a quadro;
- plantão atribuído;
- plantão alterado;
- horário alterado;
- plantão cancelado;
- interesse aceito;
- interesse rejeitado;
- transferência aprovada;
- lembrete de plantão.

### Para gestor

- médico confirmou;
- médico disponibilizou plantão;
- médico demonstrou interesse;
- convite aceito;
- convite expirado;
- falha de notificação.

## 22.3 Notificação interna
Campos:

- destinatário;
- hospital;
- tipo;
- título;
- mensagem;
- link;
- lida ou não;
- data de criação;
- data de leitura;
- prioridade.

## 22.4 Fila de envio
Todas as notificações externas deverão ser processadas em background.

Criar estrutura de jobs.

Exemplo:

```
SendInvitationJob
SendSchedulePublishedNotificationJob
SendShiftChangedNotificationJob
SendShiftReminderJob
```
Evitar envio externo durante uma requisição web.

---

# 23. HISTÓRICO E AUDITORIA
Toda ação importante deverá ser registrada.

Exemplos:

- hospital criado;
- hospital atualizado;
- médico adicionado;
- convite enviado;
- convite aceito;
- quadro criado;
- horário criado;
- horário removido;
- escala criada;
- escala publicada;
- médico atribuído;
- plantão confirmado;
- plantão disponibilizado;
- interesse criado;
- interesse aprovado;
- plantão transferido;
- escala cancelada.

Campos:

- usuário;
- hospital;
- ação;
- entidade;
- identificador da entidade;
- dados anteriores;
- dados posteriores;
- IP;
- user agent;
- data;
- origem.

O histórico não deverá ser editável por usuários comuns.

---

# 24. MODELO DE DADOS SUGERIDO

## 24.1 users
Campos:

```
id
name
email
encrypted_password
cpf_encrypted
cpf_hash
phone
avatar_url
crm
crm_state
specialty
global_role
active
last_sign_in_at
created_at
updated_at
```

## 24.2 hospitals

```
id
legal_name
trade_name
cnpj_encrypted
cnpj_hash
phone
email
address_line
address_number
address_complement
district
city
state
postal_code
status
created_by_id
created_at
updated_at
```

## 24.3 hospital_memberships

```
id
hospital_id
user_id
role
status
joined_at
invited_by_id
created_at
updated_at
```
Roles:

```
manager
doctor
```

## 24.4 invitations

```
id
hospital_id
cpf_encrypted
cpf_hash
phone
email
role
token_digest
status
expires_at
accepted_at
invited_by_id
user_id
created_at
updated_at
```

## 24.5 shift_boards

```
id
hospital_id
name
description
department
unit_name
primary
status
created_by_id
created_at
updated_at
```

## 24.6 shift_board_memberships

```
id
shift_board_id
user_id
status
added_by_id
created_at
updated_at
```

## 24.7 shift_templates
Representa a estrutura de horários do quadro.

```
id
shift_board_id
weekday
start_time
end_time
crosses_midnight
label
positions_count
sort_order
active
created_at
updated_at
```

## 24.8 schedules

```
id
hospital_id
shift_board_id
name
start_date
end_date
status
version
published_at
closed_at
created_by_id
updated_by_id
created_at
updated_at
```

## 24.9 shifts

```
id
hospital_id
shift_board_id
schedule_id
shift_template_id
date
starts_at
ends_at
assigned_user_id
status
positions_count
confirmed_at
available_at
notes
created_by_id
updated_by_id
created_at
updated_at
```

## 24.10 shift_interests

```
id
shift_id
user_id
status
note
decided_by_id
decided_at
created_at
updated_at
```

## 24.11 notifications

```
id
user_id
hospital_id
notification_type
title
body
target_url
read_at
priority
created_at
updated_at
```

## 24.12 notification_deliveries

```
id
notification_id
channel
provider
status
external_id
attempts
error_message
sent_at
created_at
updated_at
```

## 24.13 audit_logs

```
id
hospital_id
actor_id
action
auditable_type
auditable_id
before_data
after_data
ip_address
user_agent
created_at
```

---

# 25. REGRAS DE NEGÓCIO ESSENCIAIS

1. Somente gestor pode alterar quadro.
2. Somente gestor pode publicar escala.
3. Médico só pode confirmar o próprio plantão.
4. Médico só pode disponibilizar o próprio plantão.
5. Médico só pode demonstrar interesse em quadros dos quais participa.
6. Médico não pode demonstrar interesse no próprio plantão.
7. Médico não pode demonstrar interesse em plantão já encerrado.
8. Plantão disponível continua pertencendo ao médico original até aprovação.
9. Transferência exige aprovação do gestor no MVP.
10. Novo médico deve confirmar após transferência.
11. Toda publicação gera notificações.
12. Toda alteração envolvendo um médico gera notificação para ele.
13. Toda troca preserva histórico.
14. Exclusão física de escalas publicadas deve ser proibida.
15. Dados publicados devem ser arquivados, não apagados.
16. Usuário removido do hospital mantém conta global.
17. Hospital suspenso bloqueia operações internas.
18. Um quadro pode possuir vários médicos.
19. Um médico pode participar de vários quadros.
20. Um hospital pode possuir vários gestores.

---

# 26. ESTADOS E TRANSIÇÕES

## 26.1 Escala

```
draft -> published
published -> closed
published -> cancelled
closed -> archived
cancelled -> archived
```
Não permitir:

```
closed -> draft
archived -> published
```

## 26.2 Plantão

```
unassigned -> pending_confirmation
pending_confirmation -> confirmed
pending_confirmation -> available
confirmed -> available
available -> interest_pending
interest_pending -> transfer_pending
transfer_pending -> confirmed
available -> confirmed
pending_confirmation -> cancelled
confirmed -> cancelled
```
Todas as transições deverão ser validadas por serviço próprio.

---

# 27. TELAS DO SISTEMA

## 27.1 Públicas

- login;
- recuperação de senha;
- aceite de convite;
- criação de conta;
- termos;
- privacidade.

## 27.2 Administrador geral

- dashboard geral;
- hospitais;
- detalhes do hospital;
- usuários;
- gestores;
- médicos;
- atividades;
- falhas de notificação;
- configurações gerais.

## 27.3 Gestor

- dashboard do hospital;
- seleção de hospital;
- equipe médica;
- convites;
- quadros;
- criação de quadro;
- edição da estrutura;
- escalas;
- criação da escala;
- preenchimento;
- publicação;
- detalhes do plantão;
- interesses;
- notificações;
- histórico;
- configurações do hospital.

## 27.4 Médico

- dashboard;
- meus hospitais;
- quadros;
- escala;
- meus plantões;
- plantões disponíveis;
- interesses enviados;
- notificações;
- perfil;
- segurança.

---

# 28. EXPERIÊNCIA DO QUADRO
O quadro deverá funcionar bem em desktop e celular.

## 28.1 Desktop
Visualização em grade:

- dias nas colunas;
- horários nas linhas;
- cartões de plantão dentro da grade.

## 28.2 Celular
Não tentar comprimir sete colunas em uma tela pequena.

Usar:

- visualização por dia;
- abas;
- navegação horizontal;
- lista cronológica;
- calendário semanal adaptado.

## 28.3 Cartão do plantão
Exibir:

- horário;
- nome do médico;
- foto;
- status;
- especialidade opcional;
- indicação de próprio plantão;
- quantidade de interessados;
- ações permitidas.

---

# 29. ACESSIBILIDADE
A plataforma deverá:

- possuir contraste adequado;
- não depender somente de cor;
- permitir navegação por teclado;
- usar labels nos formulários;
- mostrar mensagens de erro claras;
- utilizar atributos ARIA;
- funcionar com leitores de tela;
- usar botões com textos objetivos.

---

# 30. SEGURANÇA

## 30.1 Autenticação

- senha com hash seguro;
- recuperação de senha;
- bloqueio após tentativas excessivas;
- sessões revogáveis;
- logout de todos os dispositivos;
- confirmação de e-mail opcional no MVP.

## 30.2 Autorização
Toda ação deverá passar por política de autorização.

Não confiar apenas na interface.

Mesmo que um botão não apareça, o backend deverá impedir a ação.

## 30.3 Dados pessoais
CPF, CNPJ e telefone são dados sensíveis para o contexto da aplicação.

Aplicar:

- criptografia;
- mascaramento;
- acesso restrito;
- logs;
- política de retenção;
- proteção contra vazamento.

## 30.4 LGPD
Preparar:

- termos de uso;
- política de privacidade;
- aceite registrado;
- consentimento quando necessário;
- exportação de dados;
- solicitação de exclusão;
- anonimização;
- registro de tratamento.

## 30.5 Proteções técnicas

- CSRF;
- XSS;
- SQL Injection;
- rate limiting;
- headers de segurança;
- cookies seguros;
- HTTPS obrigatório;
- validação de upload;
- antivírus futuro para arquivos;
- logs sem CPF e senha em texto puro.

---

# 31. SERVIÇOS DE DOMÍNIO
Evitar regras complexas diretamente em controllers.

Criar serviços como:

```
Hospitals::Create
Doctors::Invite
Invitations::Accept
ShiftBoards::Create
ShiftBoards::ApplyGrid
Schedules::Create
Schedules::Publish
Shifts::AssignDoctor
Shifts::Confirm
Shifts::MakeAvailable
ShiftInterests::Create
ShiftInterests::Approve
ShiftInterests::Reject
Notifications::Dispatch
```
Cada serviço deverá:

- validar permissões;
- validar regras;
- usar transação;
- registrar auditoria;
- gerar notificações;
- retornar resultado padronizado.

---

# 32. EVENTOS INTERNOS
Recomenda-se utilizar eventos internos.

Exemplos:

```
hospital.created
doctor.invited
invitation.accepted
schedule.published
shift.assigned
shift.confirmed
shift.made_available
shift_interest.created
shift_interest.approved
shift.transferred
```
Esses eventos poderão acionar:

- notificações;
- auditoria;
- atualizações em tempo real;
- integrações futuras.

---

# 33. ATUALIZAÇÃO EM TEMPO REAL
Quando possível, utilizar atualização em tempo real.

Exemplos:

- médico confirma e gestor vê sem atualizar página;
- plantão fica disponível e demais médicos veem;
- interesse aparece para o gestor;
- status muda no quadro.

Se a stack suportar WebSocket:

- usar canal por hospital;
- usar canal por quadro;
- validar autorização ao entrar no canal.

---

# 34. PROCESSAMENTO EM BACKGROUND
Usar filas para:

- notificações;
- lembretes;
- expiração de convites;
- geração de relatórios;
- integração com Serpro;
- integração com WhatsApp;
- processamento de e-mails;
- auditorias pesadas.

Jobs devem ser idempotentes.

Ou seja, executar duas vezes não pode gerar duplicidade indevida.

---

# 35. LEMBRETES
Estrutura futura:

- lembrete 24 horas antes;
- lembrete 12 horas antes;
- lembrete 2 horas antes;
- lembrete personalizado pelo hospital.

O MVP poderá ter configuração global simples.

---

# 36. RELATÓRIOS FUTUROS
Preparar o domínio para relatórios como:

- quantidade de plantões por médico;
- horas trabalhadas;
- plantões confirmados;
- plantões disponibilizados;
- trocas realizadas;
- taxa de confirmação;
- médicos com mais plantões;
- plantões sem cobertura;
- atrasos de confirmação;
- histórico mensal;
- exportação PDF;
- exportação Excel;
- exportação CSV.

Não é necessário implementar todos agora, mas o banco não deve impedir isso.

---

# 37. TESTES

## 37.1 Testes unitários
Cobrir:

- estados;
- validações;
- permissões;
- cálculos de horários;
- sobreposição;
- CPF;
- CNPJ;
- telefone;
- expiração de convite.

## 37.2 Testes de serviço
Cobrir:

- convite;
- aceite;
- criação de quadro;
- aplicação de grade;
- publicação;
- confirmação;
- disponibilização;
- interesse;
- transferência.

## 37.3 Testes de autorização
Testar que:

- médico não edita quadro;
- médico não publica;
- gestor não acessa hospital alheio;
- médico não confirma plantão alheio;
- médico não demonstra interesse fora do quadro;
- administrador geral acessa tudo.

## 37.4 Testes de fluxo
Fluxo completo:

1. gestor cria hospital;
2. gestor convida médico;
3. médico cria conta;
4. gestor cria quadro;
5. gestor cria horários;
6. gestor cria escala;
7. gestor atribui médico;
8. gestor publica;
9. médico confirma;
10. médico disponibiliza;
11. outro médico demonstra interesse;
12. gestor aprova;
13. novo médico confirma.

---

# 38. DADOS DE DESENVOLVIMENTO
Criar seeds com:

- um administrador geral;
- dois hospitais;
- dois gestores;
- cinco médicos;
- três quadros;
- duas escalas;
- plantões em todos os estados;
- convites pendentes;
- interesses pendentes.

Não usar dados reais.

---

# 39. ETAPAS DE DESENVOLVIMENTO

## Fase 1 — Fundação

- iniciar projeto;
- configurar banco;
- autenticação;
- usuários;
- papéis;
- multi-tenancy;
- autorização;
- layout;
- auditoria básica.

## Fase 2 — Hospitais

- cadastro;
- edição;
- seleção;
- vínculos;
- dashboard inicial;
- mock do Serpro.

## Fase 3 — Médicos e convites

- equipe;
- convite;
- aceite;
- criação de conta;
- vínculo;
- mock do WhatsApp.

## Fase 4 — Quadros

- criação;
- participantes;
- horários;
- divisão manual;
- grades;
- validação de sobreposição.

## Fase 5 — Escalas

- criação;
- preenchimento;
- atribuição;
- rascunho;
- publicação;
- versões.

## Fase 6 — Médico

- dashboard;
- visualização;
- confirmação;
- disponibilização;
- notificações.

## Fase 7 — Interesse e transferência

- manifestação;
- múltiplos interessados;
- aprovação;
- rejeição;
- transferência;
- confirmação do novo médico.

## Fase 8 — Administração geral

- dashboard;
- hospitais;
- usuários;
- auditorias;
- falhas.

## Fase 9 — Qualidade

- responsividade;
- acessibilidade;
- testes;
- segurança;
- otimização;
- logs;
- documentação.

---

# 40. INTEGRAÇÕES QUE NÃO DEVEM BLOQUEAR O PROJETO

## Serpro
Criar interface e mock.

Não depender de chave real.

Permitir cadastro manual.

## WhatsApp
Criar provider e mock.

Registrar mensagens simuladas.

Não depender de número, bot ou token.

## E-mail
Pode ser implementado com adaptador local ou ambiente de teste.

Em desenvolvimento, usar caixa simulada.

---

# 41. CRITÉRIOS DE ACEITE DO MVP
O MVP estará funcional quando for possível:

1. Criar administrador geral.
2. Criar gestor.
3. Gestor cadastrar hospital manualmente.
4. Gestor adicionar médico por CPF e telefone.
5. Sistema gerar convite.
6. Médico criar conta pelo convite.
7. Médico acessar hospital.
8. Gestor criar quadro.
9. Gestor adicionar médicos ao quadro.
10. Gestor criar divisões manuais.
11. Gestor aplicar grade automática.
12. Gestor criar escala.
13. Gestor atribuir médicos.
14. Gestor publicar escala.
15. Todos do quadro visualizar a escala.
16. Médico ver seus plantões amarelos.
17. Médico confirmar e plantão ficar verde.
18. Médico disponibilizar e plantão ficar vermelho.
19. Outro médico demonstrar interesse.
20. Gestor aprovar interessado.
21. Novo médico confirmar.
22. Sistema registrar histórico.
23. Sistema gerar notificações internas.
24. Sistema registrar notificações simuladas de WhatsApp.
25. Administrador geral visualizar todo o sistema.

---

# 42. REGRAS PARA O FABLE 5 DURANTE O DESENVOLVIMENTO

1. Não implementar integração real com Serpro agora.
2. Não solicitar chaves do Serpro agora.
3. Não implementar envio real de WhatsApp agora.
4. Não solicitar número de WhatsApp agora.
5. Não acoplar regras a fornecedores externos.
6. Criar providers e mocks.
7. Não pular autenticação.
8. Não pular autorização.
9. Não misturar dados de hospitais.
10. Não excluir histórico.
11. Não permitir edição de quadro por médico.
12. Não implementar troca automática sem aprovação do gestor.
13. Não usar cores como único indicador.
14. Criar testes desde o início.
15. Documentar cada módulo.
16. Criar migrations pequenas e reversíveis.
17. Não armazenar CPF, CNPJ, senha ou token em texto puro.
18. Usar transações nas operações críticas.
19. Registrar auditoria.
20. Criar código modular e preparado para expansão.

---

# 43. INSTRUÇÃO FINAL PARA EXECUÇÃO
Construa a aplicação de maneira incremental.

Antes de cada fase:

- analisar o estado atual do projeto;
- listar arquivos que serão alterados;
- descrever migrations;
- descrever regras;
- implementar;
- executar testes;
- corrigir erros;
- documentar o que foi realizado.

Não avançar deixando testes quebrados.

Não criar integrações externas reais até que as credenciais sejam fornecidas.

Quando chegar à parte do Serpro ou WhatsApp, manter o mock funcionando e registrar claramente quais variáveis de ambiente serão necessárias futuramente.

A prioridade é entregar primeiro um núcleo sólido de:

```
multi-tenancy
usuários
permissões
hospitais
quadros
escalas
plantões
confirmações
disponibilizações
interesses
transferências
notificações
auditoria
```
A aplicação deverá permanecer utilizável mesmo sem Serpro e sem WhatsApp, utilizando cadastro manual e notificações internas.
