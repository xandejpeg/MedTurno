<?php

namespace Database\Seeders;

use App\Models\Tender;
use Illuminate\Database\Seeder;

class TenderSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedTr027();
        $this->seedCotacao68();
    }

    private function make(Tender $tender, string $category, array $items): void
    {
        foreach ($items as $i => $item) {
            [$title, $status, $desc] = array_pad($item, 3, null);
            $tender->requirements()->create([
                'category' => $category,
                'title' => $title,
                'description' => $desc,
                'status' => $status,
                'sort' => $i,
            ]);
        }
        $tender->recalcProgress();
    }

    private function seedTr027(): void
    {
        $tender = Tender::firstOrCreate(
            ['numero' => 'TR 027/2021'],
            [
                'title' => 'Sistema online de escalas médicas — Hospital Estadual Dr. Jayme Santos Neves',
                'orgao' => 'AEBES / Hospital Jayme Santos Neves',
                'status' => 'analise',
                'notes' => 'Edital completo (menor preço). Foco em escalas médicas hospitalares com check-in/out, conformidade e relatórios financeiros.',
            ]
        );
        $tender->requirements()->delete();

        $this->make($tender, 'Escalas e planejamento', [
            ['Construir e planejar escalas de trabalho', 'pronto'],
            ['Regras de repetição semanal, mensal, por dia do mês, diária e por semana do mês', 'pronto', 'Recorrências avançadas: semanal, quinzenal, mensal, por dia do mês, por intervalo de dias, por semana do mês.'],
            ['Grade de alocações customizável com cores por equipe', 'pronto', 'Cores por quadro na grade (border-left colorido por shift_boards.color).'],
            ['Calendário mensal e semanal', 'pronto', 'Alternância mensal ↔ semanal na escala-montar.'],
            ['Bloqueio/desbloqueio de vagas com hachura', 'pronto', 'Tabela shift_blocks com hachura visual.'],
            ['Filtros de equipe na grade', 'pronto', 'Filtro de equipe (select quando há múltiplos quadros).'],
            ['Turnos publicados com destaque visual', 'pronto'],
            ['Comentários nos plantões com destaque', 'pronto', 'Ícone 💬 no plantão quando tem note (hover mostra texto).'],
            ['Trocas com destaque visual na grade', 'pronto', 'Ícone 🔄 quando há troca em andamento.'],
            ['Divisão de um turno entre dois plantonistas', 'faltando'],
            ['Saldo de horas por profissional', 'pronto', 'GridService::balancesForSchedule() com saldo em tempo real.'],
            ['Plantões de sobreaviso com identificação', 'faltando'],
            ['Anúncio em lote de vagas', 'faltando'],
            ['Impressão/exportação da grade (CRM, especialidade, não publicados)', 'pronto', 'Relatório mensal em PDF + exportação xlsx.'],
            ['Fixar número de vagas', 'faltando'],
        ]);

        $this->make($tender, 'Gestão de profissionais', [
            ['Dados cadastrais completos (nome, apelido, e-mail, CPF, celular, sexo, CBO, conselho, UF, ID interno, especialidade, ingresso, TAGs)', 'pronto', 'Todos os campos no perfil: apelido, CBO, tipo de conselho, matrícula, data de ingresso, TAGs.'],
            ['Gestão de ausências com justificativa e tratamento em turnos publicados', 'pronto', 'Registro de ausências com tratamento automático (substituir ou anunciar cobertura).'],
            ['Bloqueio de alocação em dia com ausência', 'pronto'],
        ]);

        $this->make($tender, 'Limites e conformidade', [
            ['Limite de horas por profissional (mensal/semanal, vigência, tratativa)', 'pronto'],
            ['Regras de conformidade (tempo máximo de turno, descanso, conflitos de agenda)', 'pronto'],
        ]);

        $this->make($tender, 'Check-in / Check-out', [
            ['Painel de tratamento de check-in/check-out por escala e por profissional', 'pronto', 'Painel de presenças com restaurar e consolidar horários.'],
            ['Ajuste, restaurar e consolidar horários de check-in/out', 'pronto'],
            ['Check-in com geolocalização e regras de tolerância', 'pronto'],
        ]);

        $this->make($tender, 'Negociações (trocas)', [
            ['Permissões para trocar ou anunciar plantões', 'pronto', 'Temos toggle de aprovação de troca na escala.'],
            ['Mediar trocas com aceite do organizador, com e-mail e notificação', 'pronto', 'Notificação ao gestor e admins no app, e-mail e WhatsApp.'],
            ['Bloquear trocas de turnos de duração diferente', 'faltando'],
            ['Histórico de negociações, anúncios e substituições', 'pronto', 'Central de trocas + fluxo dedicado de substituição com registro.'],
            ['Aprovar/recusar negociações', 'pronto'],
        ]);

        $this->make($tender, 'Dashboard e financeiro', [
            ['Dashboard com visão geral de escalas, alocação, negociações e alertas', 'pronto', 'Dashboard executivo com visão de alocação e alertas de conformidade.'],
            ['Relatórios financeiros por profissional, equipe e turno', 'pronto', 'Extrato consolidado por médico, equipe e turno com filtros.'],
            ['Relatórios personalizados (Metabase)', 'pronto', 'Metabase embutido na página Dashboards.'],
            ['Extrato financeiro com bônus e filtros por TAG', 'pronto', 'Bônus por plantão e filtros por TAG implementados.'],
        ]);

        $this->make($tender, 'Administração e integração', [
            ['Personalização com cores e logotipo da instituição', 'pronto', 'White-label por hospital (cores e logotipo).'],
            ['Ativação/desativação de usuários e escalas', 'pronto', 'Ativação de usuários e escalas.'],
            ['Inclusão de usuários em lote (importação)', 'faltando'],
            ['Gestão de TAGs globais', 'faltando'],
            ['Configurar fuso, horário noturno e fim de semana', 'faltando'],
            ['APIs abertas para integração (turnos, profissionais, escalas, check-in/out)', 'pronto', 'API pública /api/v1 com token por hospital.'],
        ]);

        $this->make($tender, 'Aplicativo e agenda', [
            ['Aplicativo na App Store e Google Play', 'parcial', 'PWA com atalhos; documentação pronta para publicação (TWA/Capacitor).'],
            ['Integração da escala com Google/Apple Calendar', 'pronto', 'Feed iCal da escala do médico.'],
            ['Notificações de plantão em tempo real (lembretes, trocas, publicação)', 'pronto', 'Lembretes programáveis (12h/24h + check-in/out 30min), trocas e publicação.'],
            ['Apoio à decisão com conflito da agenda pessoal', 'faltando'],
            ['Check-in/out offline com sincronização', 'faltando'],
            ['Mural de recados para a escala', 'pronto'],
        ]);
    }

    private function seedCotacao68(): void
    {
        $tender = Tender::firstOrCreate(
            ['numero' => 'Cotação 68/2025'],
            [
                'title' => 'Software de Controle e Gerenciamento de Escala Médica — Atenção Primária (AgSUS)',
                'orgao' => 'AgSUS — Núcleo de Saúde Digital',
                'status' => 'analise',
                'notes' => 'Contratação direta. 30 usuários, 12 meses. Foco em APS, múltiplas escalas, check-in GPS/QR e dashboards.',
            ]
        );
        $tender->requirements()->delete();

        $this->make($tender, 'Escalas e visão', [
            ['Visão mensal agrupando turnos vagos por UBS, município, turnos ou horários', 'pronto', 'Unidades (UBS) e painel escala do dia por UBS.'],
            ['Planejar, alocar, criar regras de recorrência, administrar ausências e anunciar vagas', 'pronto', 'Ausências, recorrências avançadas e anúncio de vagas implementados.'],
            ['Interface simples para planejar turnos e alocar profissionais', 'pronto'],
            ['Múltiplas escalas para diferentes equipes e turnos', 'pronto', 'Múltiplos quadros (ShiftBoards) por hospital.'],
            ['Regras de repetição personalizadas', 'pronto'],
            ['Distribuição de horas por profissional por período', 'pronto', 'GridService::balancesForSchedule() com saldo em tempo real.'],
        ]);

        $this->make($tender, 'Acesso e perfis', [
            ['Perfis de acesso com permissões personalizadas (administrativo, operacional, financeiro)', 'pronto', 'Gestor, médico, admin e financeiro.'],
            ['Ativação, inativação e atualização de usuários', 'pronto'],
            ['Controle de acesso ágil e seguro', 'pronto'],
        ]);

        $this->make($tender, 'Agenda e notificações', [
            ['Integração com agenda pessoal (Google, Outlook)', 'pronto', 'Feed iCal da escala.'],
            ['Lembretes e notificações para equipes sobre alterações', 'pronto', 'Lembretes programáveis (12h/24h + check-in/out 30min) e notificações de alterações.'],
            ['Mural de recados', 'pronto'],
            ['Notificação de check-in/out próximo do início/fim do turno', 'pronto', 'Lembrete de check-in/out 30min antes.'],
        ]);

        $this->make($tender, 'Check-in / Check-out', [
            ['Check-in/check-out via sistema ou dispositivo móvel', 'pronto'],
            ['Check-in via GPS ou QR Code', 'pronto'],
            ['Registro de tempo dedicado à gestão', 'faltando'],
        ]);

        $this->make($tender, 'Negociações e gestão', [
            ['Troca de plantão via aplicativo', 'pronto'],
            ['Anúncio de plantões e passagem de turno', 'pronto'],
            ['Aprovar ou recusar negociações', 'pronto'],
            ['Substituição de profissionais pelo organizador', 'pronto', 'Fluxo dedicado de substituição com notificações.'],
            ['Feedback e gestão de conflito de escalas', 'pronto', 'Regras de conformidade com alerta/bloqueio de conflito.'],
            ['Limite de horas por profissional (mensal)', 'pronto'],
            ['Regras de negociação customizáveis (autonomia)', 'pronto', 'Toggle de aprovação de troca.'],
            ['Interface do gestor para substituir, gerenciar negociações e anunciar coberturas (mobile)', 'pronto', 'Interface do gestor otimizada para mobile.'],
            ['Painel geral da escala do dia (quem trabalha, UBS, contato)', 'pronto', 'Painel escala do dia por UBS.'],
            ['Acesso do gestor municipal à escala semanal e notificações de alteração', 'pronto', 'Escala semanal e perfil gestor municipal.'],
        ]);

        $this->make($tender, 'Relatórios e financeiro', [
            ['Relatórios/extrato consolidado/dashboards de escalas, horas e performance', 'pronto', 'Extrato consolidado e Metabase embutido.'],
            ['Gestão financeira de turnos, plantões e horas', 'pronto', 'Extrato financeiro por médico, equipe e turno.'],
            ['Valores distintos por escala, profissional, turno e plantão', 'pronto', 'Valor por médico, turno e plantão.'],
            ['Alertas de conformidade (regras e leis trabalhistas)', 'pronto', 'Regras de conformidade implementadas.'],
        ]);

        $this->make($tender, 'Integração e segurança', [
            ['Integração por API com outros softwares', 'pronto', 'API pública /api/v1.'],
            ['Segurança, privacidade e conformidade (LGPD), auditável', 'pronto', 'Página de Privacidade/LGPD publicada.'],
        ]);
    }
}
