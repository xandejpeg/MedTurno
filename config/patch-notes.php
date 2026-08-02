<?php

return [
    [
        'version' => '1.2.0',
        'released_at' => '02/08/2026',
        'title' => 'Preparação para licitações',
        'summary' => 'Pacote completo de conformidade e operação hospitalar que prepara o DoctorTurn para disputar licitações de sistemas de escalas médicas.',
        'highlights' => [
            [
                'title' => 'Conformidade e ausências',
                'description' => 'Gestão de ausências, limite de horas por médico e regras de conformidade (tempo máximo de turno, descanso e conflito de agenda).',
            ],
            [
                'title' => 'Check-in e presença',
                'description' => 'Registro de entrada e saída por GPS e QR Code, com painel de presenças para o gestor.',
            ],
            [
                'title' => 'Escalas avançadas e API',
                'description' => 'Recorrências avançadas, TAGs, UBS, agenda pessoal, mural de recados e API pública para integração.',
            ],
        ],
        'sections' => [
            [
                'title' => 'Conformidade e operação',
                'items' => [
                    'Gestão de ausências com bloqueio de alocação e de troca em dia de ausência.',
                    'Limite de horas por médico (mensal ou semanal), com opção de bloquear ou apenas alertar.',
                    'Regras de conformidade: tempo máximo de turno, descanso mínimo entre plantões (com reforço noturno) e conflito de agenda.',
                ],
            ],
            [
                'title' => 'Check-in e presença',
                'items' => [
                    'Check-in e check-out por geolocalização (GPS) e por QR Code.',
                    'Painel de presenças do gestor com entrada, saída e status de cada plantão.',
                ],
            ],
            [
                'title' => 'Escalas e integração',
                'items' => [
                    'Regras de recorrência avançadas: semanal, quinzenal, mensal, por dia do mês, por intervalo de dias e por semana do mês.',
                    'Sistema de TAGs para médicos, plantões, escalas e quadros.',
                    'Unidades (UBS) e painel "escala do dia" por unidade, com dados de contato.',
                    'Feed de calendário (iCal) para assinar a escala no Google, Apple ou Outlook.',
                    'Mural de recados do gestor para os médicos, com notificação no app.',
                    'Perfis financeiro e gestor municipal.',
                    'API pública (escalas, plantões, profissionais e check-ins) e valores por médico e por turno.',
                    'Página de Privacidade e LGPD.',
                ],
            ],
        ],
    ],
    [
        'version' => '1.1.0',
        'released_at' => '01/08/2026',
        'title' => 'Central de Controle e notificações de troca',
        'summary' => 'Nova Central de Controle no painel do administrador, com acompanhamento da comunicação com os médicos, plantões publicados por gestor, central de trocas e notificações de troca pendente por e-mail e WhatsApp.',
        'highlights' => [
            [
                'title' => 'Central de Controle',
                'description' => 'Nova área no painel do administrador reunindo comunicação com médicos, plantões por gestor e central de trocas em um só lugar.',
            ],
            [
                'title' => 'Comunicação com Médicos',
                'description' => 'Visualize os modelos de e-mail e WhatsApp programados, a simulação de cada mensagem e o histórico de envios por médico.',
            ],
            [
                'title' => 'Trocas com aprovação',
                'description' => 'Gestores e administradores passam a ser avisados no app, por e-mail e por WhatsApp quando uma troca fica aguardando aprovação.',
            ],
        ],
        'sections' => [
            [
                'title' => 'Central de Controle',
                'items' => [
                    'Comunicação com Médicos: catálogo de e-mails programados com assunto e corpo de cada modelo.',
                    'WhatsApp programado: template ativo, status e simulação de conversa da mensagem.',
                    'Painel individual por médico com o histórico de mensagens em formato de conversa.',
                    'Plantões Publicados por Gestor: publicações separadas por hospital com calendário real e métricas (total, preenchidos, sem médico e número de médicos).',
                    'Central de Trocas: visão das trocas ativas, aprovadas e recusadas.',
                ],
            ],
            [
                'title' => 'Trocas de plantão',
                'items' => [
                    'Novo controle na montagem da escala para exigir ou não a aprovação do gestor nas trocas entre médicos.',
                    'Notificação de troca aguardando aprovação para o gestor e para os administradores, no app, por e-mail e por WhatsApp.',
                    'Novo modelo de WhatsApp de troca pendente (em aprovação na Meta).',
                ],
            ],
            [
                'title' => 'Melhorias e correções',
                'items' => [
                    'Correção do erro ao abrir o dashboard e o detalhe do plantão em escalas sem quadro.',
                    'Registro automático dos envios de e-mail e WhatsApp na publicação de escalas.',
                ],
            ],
        ],
    ],
    [
        'version' => '1.0.2',
        'released_at' => '30/07/2026',
        'title' => 'WhatsApp configurado e testado',
        'summary' => 'O canal de WhatsApp do DoctorTurn está configurado, validado em ambiente de teste e com os envios automáticos ativos.',
        'highlights' => [
            [
                'title' => 'WhatsApp configurado',
                'description' => 'A integração com a API oficial do WhatsApp (Meta) está autenticada e pronta para os disparos automáticos.',
            ],
            [
                'title' => 'Testado em ambiente de teste',
                'description' => 'Os envios foram validados em ambiente controlado antes da liberação para produção.',
            ],
            [
                'title' => 'Envios automáticos ativos',
                'description' => 'Ao publicar uma escala, os médicos envolvidos passam a receber também a notificação por WhatsApp, além do e-mail.',
            ],
        ],
        'sections' => [
            [
                'title' => 'Disponível agora',
                'items' => [
                    'Envio automático de WhatsApp após a publicação de uma escala.',
                    'Notificação por WhatsApp dos médicos que possuem plantões na escala publicada.',
                    'Modelo de mensagem aprovado pela Meta (escala_publicada_v2) em português (BR).',
                    'Perfil comercial do WhatsApp configurado com nome e foto do DoctorTurn.',
                    'Envio em segundo plano com tentativas automáticas em caso de falha.',
                ],
            ],
            [
                'title' => 'Como funciona',
                'items' => [
                    'O gestor publica a escala normalmente pela plataforma.',
                    'Cada médico com plantão recebe uma mensagem por e-mail e por WhatsApp.',
                    'Médicos sem plantão na escala não recebem notificação.',
                    'O envio por WhatsApp usa o número de celular cadastrado de cada médico.',
                ],
            ],
        ],
    ],
    [
        'version' => '1.0.1',
        'released_at' => '28/07/2026',
        'title' => 'Notificações por e-mail prontas',
        'summary' => 'O canal de e-mail do DoctorTurn está configurado para acompanhar a publicação das escalas e manter os responsáveis informados.',
        'highlights' => [
            [
                'title' => 'E-mail configurado',
                'description' => 'O serviço de e-mail está autenticado e pronto para os disparos automáticos do DoctorTurn.',
            ],
            [
                'title' => 'Avisos na publicação',
                'description' => 'Ao publicar uma escala, os médicos envolvidos e o canal administrativo configurado recebem a notificação.',
            ],
            [
                'title' => 'WhatsApp em preparação',
                'description' => 'A integração ainda está desabilitada, com previsão de ativação até 29/07/2026 às 18:00.',
            ],
        ],
        'sections' => [
            [
                'title' => 'Disponível agora',
                'items' => [
                    'Envio automático de e-mail após a publicação de uma escala.',
                    'Notificação dos médicos que possuem plantões na escala publicada.',
                    'Cópia enviada ao endereço administrativo configurado para administradores e gestores responsáveis.',
                    'Identidade visual DoctorTurn aplicada às mensagens.',
                ],
            ],
            [
                'title' => 'Próxima etapa',
                'items' => [
                    'Ativação do canal de WhatsApp prevista para 29/07/2026 até as 18:00.',
                    'Após a ativação, o canal será validado em ambiente controlado.',
                    'O primeiro disparo real para todos os envolvidos ocorrerá somente quando o gestor criar e publicar a primeira escala.',
                    'A entrega do WhatsApp em funcionamento será registrada na versão 1.0.2.',
                ],
            ],
        ],
    ],
    [
        'version' => '1.0.0',
        'released_at' => '28/07/2026',
        'title' => 'Primeira versão do DoctorTurn',
        'summary' => 'A base completa para organizar equipes, montar escalas e acompanhar a rotina médica em um só lugar.',
        'highlights' => [
            [
                'title' => 'Gestão centralizada',
                'description' => 'Hospitais, equipes, convites e quadros de escala organizados em um único painel.',
            ],
            [
                'title' => 'Escalas mais ágeis',
                'description' => 'Criação, montagem, replicação mensal e publicação de escalas com menos trabalho repetitivo.',
            ],
            [
                'title' => 'Comunicação integrada',
                'description' => 'Notificações internas e por e-mail para manter gestores e médicos atualizados.',
            ],
        ],
        'sections' => [
            [
                'title' => 'Gestão de escalas',
                'items' => [
                    'Criação e organização de escalas por hospital e quadro.',
                    'Replicação de uma escala para outros meses.',
                    'Publicação com identificação de versão.',
                    'Visualização dos plantões atribuídos a cada médico.',
                ],
            ],
            [
                'title' => 'Equipe e operação',
                'items' => [
                    'Cadastro de hospitais e gestão de vínculos da equipe.',
                    'Convites individuais e por link para entrada de médicos.',
                    'Fluxo de trocas, interesses e acompanhamento de plantões.',
                    'Painel administrativo para acompanhamento da plataforma.',
                ],
            ],
            [
                'title' => 'Notificações',
                'items' => [
                    'Avisos internos sobre os principais eventos da operação.',
                    'E-mails de escala publicada com a identidade DoctorTurn.',
                    'Estrutura preparada para notificações por WhatsApp.',
                ],
            ],
        ],
    ],
];