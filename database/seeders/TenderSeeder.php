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
            ['Regras de repetição semanal, mensal, por dia do mês, diária e por semana do mês', 'parcial', 'Temos replicação mensal e recorrência por dia da semana; faltam regras avançadas (dia sim/não, semana do mês, intervalos).'],
            ['Grade de alocações customizável com cores por equipe', 'parcial', 'Temos cores por turno (dia/noite); falta cor por equipe.'],
            ['Calendário mensal e semanal', 'parcial', 'Temos mensal; falta visão semanal.'],
            ['Bloqueio/desbloqueio de vagas com hachura', 'faltando'],
            ['Filtros de equipe na grade', 'faltando'],
            ['Turnos publicados com destaque visual', 'pronto'],
            ['Comentários nos plantões com destaque', 'parcial', 'Existe campo observação; falta destaque visual com ícone.'],
            ['Trocas com destaque visual na grade', 'faltando'],
            ['Divisão de um turno entre dois plantonistas', 'faltando'],
            ['Saldo de horas por profissional', 'parcial', 'Temos previsão de recebimento; falta saldo de horas em tempo real.'],
            ['Plantões de sobreaviso com identificação', 'faltando'],
            ['Anúncio em lote de vagas', 'faltando'],
            ['Impressão/exportação da grade (CRM, especialidade, não publicados)', 'parcial', 'Temos relatório mensal; falta exportação completa da grade.'],
            ['Fixar número de vagas', 'faltando'],
        ]);

        $this->make($tender, 'Gestão de profissionais', [
            ['Dados cadastrais completos (nome, apelido, e-mail, CPF, celular, sexo, CBO, conselho, UF, ID interno, especialidade, ingresso, TAGs)', 'parcial', 'Temos nome, e-mail, CPF, celular, CRM, UF, especialidade; faltam apelido, sexo, CBO, ID interno, data de ingresso, TAGs.'],
            ['Gestão de ausências com justificativa e tratamento em turnos publicados', 'pronto', 'Registro de ausências com escopo e bloqueio de alocação; falta tratamento automático em turnos já publicados.'],
            ['Bloqueio de alocação em dia com ausência', 'pronto'],
        ]);

        $this->make($tender, 'Limites e conformidade', [
            ['Limite de horas por profissional (mensal/semanal, vigência, tratativa)', 'pronto'],
            ['Regras de conformidade (tempo máximo de turno, descanso, conflitos de agenda)', 'pronto'],
        ]);

        $this->make($tender, 'Check-in / Check-out', [
            ['Painel de tratamento de check-in/check-out por escala e por profissional', 'pronto', 'Painel de presenças do gestor; falta ajuste/consolidação de horários.'],
            ['Ajuste, restaurar e consolidar horários de check-in/out', 'faltando'],
            ['Check-in com geolocalização e regras de tolerância', 'pronto'],
        ]);

        $this->make($tender, 'Negociações (trocas)', [
            ['Permissões para trocar ou anunciar plantões', 'pronto', 'Temos toggle de aprovação de troca na escala.'],
            ['Mediar trocas com aceite do organizador, com e-mail e notificação', 'pronto', 'Notificação ao gestor e admins no app, e-mail e WhatsApp.'],
            ['Bloquear trocas de turnos de duração diferente', 'faltando'],
            ['Histórico de negociações, anúncios e substituições', 'parcial', 'Temos central de trocas; falta histórico de substituições.'],
            ['Aprovar/recusar negociações', 'pronto'],
        ]);

        $this->make($tender, 'Dashboard e financeiro', [
            ['Dashboard com visão geral de escalas, alocação, negociações e alertas', 'parcial', 'Temos dashboard; falta visão comparativa completa.'],
            ['Relatórios financeiros por profissional, equipe e turno', 'parcial', 'Temos faturamento; falta por equipe/turno e filtros por TAG.'],
            ['Relatórios personalizados (Metabase)', 'faltando'],
            ['Extrato financeiro com bônus e filtros por TAG', 'faltando'],
        ]);

        $this->make($tender, 'Administração e integração', [
            ['Personalização com cores e logotipo da instituição', 'parcial', 'Identidade DoctorTurn; falta white-label por instituição.'],
            ['Ativação/desativação de usuários e escalas', 'parcial', 'Temos ativação de usuários; falta de escalas.'],
            ['Inclusão de usuários em lote (importação)', 'faltando'],
            ['Gestão de TAGs globais', 'faltando'],
            ['Configurar fuso, horário noturno e fim de semana', 'faltando'],
            ['APIs abertas para integração (turnos, profissionais, escalas, check-in/out)', 'pronto', 'API pública /api/v1 com token por hospital.'],
        ]);

        $this->make($tender, 'Aplicativo e agenda', [
            ['Aplicativo na App Store e Google Play', 'parcial', 'Temos PWA com atalhos; falta app nativo nas lojas.'],
            ['Integração da escala com Google/Apple Calendar', 'pronto', 'Feed iCal da escala do médico.'],
            ['Notificações de plantão em tempo real (lembretes, trocas, publicação)', 'parcial', 'Temos publicação e trocas; falta lembretes programáveis.'],
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
            ['Múltiplas escalas para diferentes equipes e turnos', 'parcial', 'Temos múltiplos quadros; falta múltiplas escalas por equipe.'],
            ['Regras de repetição personalizadas', 'pronto'],
            ['Distribuição de horas por profissional por período', 'parcial'],
        ]);

        $this->make($tender, 'Acesso e perfis', [
            ['Perfis de acesso com permissões personalizadas (administrativo, operacional, financeiro)', 'pronto', 'Gestor, médico, admin e financeiro.'],
            ['Ativação, inativação e atualização de usuários', 'pronto'],
            ['Controle de acesso ágil e seguro', 'pronto'],
        ]);

        $this->make($tender, 'Agenda e notificações', [
            ['Integração com agenda pessoal (Google, Outlook)', 'pronto', 'Feed iCal da escala.'],
            ['Lembretes e notificações para equipes sobre alterações', 'parcial', 'Temos notificações; falta lembretes programáveis.'],
            ['Mural de recados', 'pronto'],
            ['Notificação de check-in/out próximo do início/fim do turno', 'faltando'],
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
            ['Substituição de profissionais pelo organizador', 'parcial', 'Gestor pode atribuir; falta fluxo de substituição dedicado.'],
            ['Feedback e gestão de conflito de escalas', 'faltando'],
            ['Limite de horas por profissional (mensal)', 'pronto'],
            ['Regras de negociação customizáveis (autonomia)', 'pronto', 'Toggle de aprovação de troca.'],
            ['Interface do gestor para substituir, gerenciar negociações e anunciar coberturas (mobile)', 'parcial'],
            ['Painel geral da escala do dia (quem trabalha, UBS, contato)', 'pronto', 'Painel escala do dia por UBS.'],
            ['Acesso do gestor municipal à escala semanal e notificações de alteração', 'parcial', 'Perfil gestor municipal criado; falta visão semanal dedicada.'],
        ]);

        $this->make($tender, 'Relatórios e financeiro', [
            ['Relatórios/extrato consolidado/dashboards de escalas, horas e performance', 'parcial'],
            ['Gestão financeira de turnos, plantões e horas', 'parcial'],
            ['Valores distintos por escala, profissional, turno e plantão', 'parcial', 'Temos valor por plantão e padrão por hospital.'],
            ['Alertas de conformidade (regras e leis trabalhistas)', 'faltando'],
        ]);

        $this->make($tender, 'Integração e segurança', [
            ['Integração por API com outros softwares', 'pronto', 'API pública /api/v1.'],
            ['Segurança, privacidade e conformidade (LGPD), auditável', 'pronto', 'Página de Privacidade/LGPD publicada.'],
        ]);
    }
}
