<?php

return [
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
                    'Após a ativação, será realizado um teste controlado da publicação de escala.',
                    'O teste validará o disparo automático de notificações para todos os envolvidos.',
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