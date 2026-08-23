<?php

namespace App\Services;

use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\Style\Color;
use PhpOffice\PhpPresentation\Style\Alignment;

class PresentationGenerator
{
    private const TEAL = 'FF0F766E';
    private const DARK = 'FF073F3D';
    private const GRAY = 'FF6B7280';
    private const WHITE = 'FFFFFFFF';

    /**
     * Gera a apresentação executiva do roadmap do DoctorTurn.
     */
    public function roadmap(): string
    {
        $p = new PhpPresentation;
        $p->getDocumentProperties()->setCreator('DoctorTurn')->setTitle('Roadmap DoctorTurn');

        $this->slideTitle($p, 'DoctorTurn', 'Roadmap de preparação para licitações — a partir de 02/08/2026');

        $this->slideBullets($p, 'O que já temos', [
            'Escalas mensais com turnos dia/noite e montagem visual',
            'Trocas e anúncios com aprovação do gestor',
            'Notificações por e-mail e WhatsApp (template aprovado)',
            'Ausências, limite de horas e regras de conformidade',
            'Check-in/check-out por GPS e QR Code + painel de presenças',
            'Recorrências avançadas e sistema de TAGs',
            'UBS e painel "escala do dia" por unidade',
            'Agenda pessoal (iCal) e mural de recados',
            'API pública e valores por médico/turno',
            'PWA instalável e página de LGPD',
        ]);

        $this->slideBullets($p, 'O que falta construir', [
            'Grade de alocações avançada (cores por equipe, visão semanal, saldo de horas)',
            'Dados cadastrais completos (CBO, conselho, matrícula, ingresso)',
            'Tratamento automático de ausências em turnos publicados',
            'Painel de tratamento de check-in/check-out',
            'Lembretes programáveis de plantão',
            'Perfil de gestor municipal e fluxo de substituição',
            'Dashboards executivos e relatórios avançados',
            'Aplicativo nativo nas lojas',
        ]);

        $this->slideBullets($p, 'Parte financeira e fiscal', [
            'Extrato financeiro por profissional, equipe e turno',
            'Bônus por plantão (noturno, fim de semana, sobreaviso)',
            'Filtros por escala, equipe, profissional e TAGs',
            'Exportação para Excel (xlsx)',
            'Base para emissão de Nota Fiscal de Serviços (NFS-e)',
            'Relatórios personalizados (Metabase)',
        ]);

        $this->slideBullets($p, 'Aderência aos editais', [
            'TR 027/2021 (AEBES): ~55% — o mais exigente',
            '  • Falta: grade rica, ausência automática, check-in, financeiro, NFS-e, app nativo',
            'Cotação 68/2025 (AgSUS): ~70% — a mais acessível',
            '  • Falta: lembretes, gestor municipal, substituição, dashboards, qualificação',
            'Sem conflitos entre os dois: construir para o mais exigente atende ambos',
        ]);

        $this->slideBullets($p, 'Como vamos trabalhar', [
            'Desenvolver e homologar em ambiente de teste primeiro',
            'Só depois promover para o build do VPS (produção)',
            'Checklist de homologação obrigatório antes de cada deploy',
            'Testes automatizados + validação manual em homologação',
        ]);

        $this->slideBullets($p, 'Cronograma (sprints)', [
            'Sprint 1: Financeiro base (extrato, bônus, xlsx)',
            'Sprint 2: Gerador de relatórios PDF + PowerPoint',
            'Sprint 3: Grade rica, ausências, check-in',
            'Sprint 4: Lembretes, gestor municipal, substituição',
            'Sprint 5: NFS-e e Metabase',
            'Sprint 6: App nativo e offline',
            'Contínuo: habilitação e documentos (paralelo)',
        ]);

        return $this->save($p, 'roadmap-doctorturn.pptx');
    }

    /**
     * Gera a apresentação focada na parte financeira.
     */
    public function financeiro(): string
    {
        $p = new PhpPresentation;
        $p->getDocumentProperties()->setCreator('DoctorTurn')->setTitle('Parte Financeira — DoctorTurn');

        $this->slideTitle($p, 'Parte Financeira', 'O que precisamos construir e contratar — DoctorTurn');

        $this->slideBullets($p, 'O que os editais pedem (confirmado)', [
            'TR 027: Extrato Financeiro completo (profissional, equipe, turno, bônus, TAGs)',
            'TR 027: Relatórios personalizados (Metabase)',
            'TR 027: Nota Fiscal de Serviços (pagamento após emissão da NFS)',
            'Cotação 68: gestão financeira, valores por escala/profissional/turno/plantão',
            'Cotação 68: relatórios e extrato consolidado',
        ]);

        $this->slideBullets($p, 'O que já temos', [
            'Valor por plantão e valor padrão por hospital',
            'Valor por médico (por vínculo)',
            'Valor por tipo de turno',
            'Faturamento mensal básico por médico',
        ]);

        $this->slideBullets($p, 'O que precisamos CONSTRUIR', [
            'Extrato financeiro consolidado por profissional, equipe e turno',
            'Bônus por plantão (noturno, fim de semana, sobreaviso)',
            'Filtros por escala, equipe, profissional e TAGs',
            'Exportação para Excel (xlsx)',
            'Relatório base para emissão de NFS-e',
            'Registro de NFS emitidas (número, data, valor)',
            'Demonstrativo de repasse por médico (PDF)',
            'Integração com Metabase para relatórios personalizados',
        ]);

        $this->slideBullets($p, 'O que precisamos CONTRATAR / fazer na vida real', [
            'Provedor de NFS-e (API da prefeitura ou serviço terceiro, ex.: eNotas, NFE.io)',
            'Metabase (hospedagem própria via Docker ou Metabase Cloud)',
            'Contador para emissão e envio das NFS-e aos tomadores',
            'Certificado digital (se exigido pelo provedor de NFS-e)',
            'Conta bancária PJ e dados bancários para as propostas',
            'Balanços financeiros atualizados (qualificação econômico-financeira)',
        ]);

        $this->slideBullets($p, 'Ordem de execução (financeiro)', [
            '1. Extrato financeiro consolidado + filtros + xlsx',
            '2. Bônus por plantão',
            '3. Relatório base para NFS-e + registro de NFS',
            '4. Demonstrativo de repasse por médico (PDF)',
            '5. Integração Metabase',
            '6. Contratar provedor de NFS-e e contador (paralelo)',
        ]);

        return $this->save($p, 'financeiro-doctorturn.pptx');
    }

    private function slideTitle(PhpPresentation $p, string $title, string $subtitle): void
    {
        $slide = $p->createSlide();

        $bg = $slide->createRichTextShape()->setHeight(600)->setWidth(960)->setOffsetX(0)->setOffsetY(0);
        $bg->getFill()->setFillType(\PhpOffice\PhpPresentation\Style\Fill::FILL_SOLID)->setStartColor(new Color(self::DARK));

        $shape = $slide->createRichTextShape()->setHeight(200)->setWidth(900)->setOffsetX(40)->setOffsetY(220);
        $shape->createTextRun($title)->getFont()->setBold(true)->setSize(48)->setColor(new Color(self::WHITE));
        $shape->createBreak();
        $shape->createTextRun($subtitle)->getFont()->setSize(20)->setColor(new Color('FF99F6E4'));
    }

    /**
     * @param  list<string>  $bullets
     */
    private function slideBullets(PhpPresentation $p, string $title, array $bullets): void
    {
        $slide = $p->createSlide();

        $header = $slide->createRichTextShape()->setHeight(70)->setWidth(900)->setOffsetX(40)->setOffsetY(30);
        $header->createTextRun($title)->getFont()->setBold(true)->setSize(32)->setColor(new Color(self::TEAL));

        $body = $slide->createRichTextShape()->setHeight(560)->setWidth(900)->setOffsetX(50)->setOffsetY(120);
        foreach ($bullets as $i => $bullet) {
            $run = $body->createTextRun($bullet)->getFont()->setSize(18)->setColor(new Color('FF1F2937'));
            if (str_starts_with($bullet, '  •')) {
                $run->setSize(16)->setColor(new Color(self::GRAY));
            }
            if ($i < count($bullets) - 1) {
                $body->createBreak();
            }
        }
    }

    private function save(PhpPresentation $p, string $filename): string
    {
        $path = storage_path('app/reports/'.$filename);
        @mkdir(dirname($path), 0755, true);
        IOFactory::createWriter($p, 'PowerPoint2007')->save($path);

        return $path;
    }
}
