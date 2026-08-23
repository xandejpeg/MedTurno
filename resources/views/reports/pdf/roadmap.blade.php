<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; line-height: 1.5; }
  h1 { color: #0f766e; font-size: 24px; border-bottom: 3px solid #0f766e; padding-bottom: 8px; }
  h2 { color: #073f3d; font-size: 16px; margin-top: 22px; border-left: 4px solid #0f766e; padding-left: 8px; }
  h3 { color: #0f766e; font-size: 13px; margin-top: 14px; }
  ul { margin: 6px 0; padding-left: 18px; }
  li { margin-bottom: 3px; }
  .done { color: #059669; }
  .todo { color: #b45309; }
  .meta { color: #6b7280; font-size: 11px; }
  .box { background: #f0fdfa; border: 1px solid #99f6e4; border-radius: 6px; padding: 10px 14px; margin: 10px 0; }
  table { width: 100%; border-collapse: collapse; margin: 8px 0; }
  th, td { border: 1px solid #d1d5db; padding: 5px 8px; text-align: left; font-size: 11px; }
  th { background: #0f766e; color: #fff; }
</style>
</head>
<body>
  <h1>DoctorTurn — Roadmap de Preparação para Licitações</h1>
  <p class="meta">Documento gerado em {{ now()->format('d/m/Y H:i') }} · A partir de 02/08/2026</p>

  <div class="box">
    <strong>Objetivo:</strong> deixar o DoctorTurn 100% apto e competitivo nas licitações <strong>TR 027/2021</strong> (AEBES/Hospital Jayme Santos Neves) e <strong>Cotação 68/2025</strong> (AgSUS/Atenção Primária), incluindo a parte financeira/fiscal. Estratégia: desenvolver e homologar em ambiente de teste antes de promover para produção.
  </div>

  <h2>1. O que já temos</h2>
  <ul>
    <li class="done">✔ Escalas mensais com turnos dia/noite e montagem visual (arrastar e soltar + preenchimento inteligente).</li>
    <li class="done">✔ Trocas e anúncios com aprovação do gestor (toggle livre/com aprovação).</li>
    <li class="done">✔ Notificações por e-mail e WhatsApp (template aprovado na Meta, com negrito e link).</li>
    <li class="done">✔ Gestão de ausências, limite de horas por médico e regras de conformidade (tempo máximo, descanso, conflito de agenda).</li>
    <li class="done">✔ Check-in/check-out por GPS e QR Code, com painel de presenças do gestor.</li>
    <li class="done">✔ Recorrências avançadas (semanal, quinzenal, mensal, dia do mês, intervalo, semana do mês) e sistema de TAGs.</li>
    <li class="done">✔ Unidades (UBS) e painel "escala do dia" por unidade, com contatos.</li>
    <li class="done">✔ Feed iCal da escala (Google/Apple/Outlook) e mural de recados.</li>
    <li class="done">✔ Perfis gestor, médico, admin, financeiro e gestor municipal.</li>
    <li class="done">✔ API pública (/api/v1) e valores por médico e por turno.</li>
    <li class="done">✔ PWA instalável e página de Privacidade/LGPD.</li>
  </ul>

  <h2>2. O que falta construir</h2>
  <ul>
    <li class="todo">• Grade de alocações avançada (cores por equipe, visão semanal, bloqueio de vagas, saldo de horas, sobreaviso, anúncio em lote, divisão de turno, fixar vagas).</li>
    <li class="todo">• Dados cadastrais completos (apelido, CBO, tipo de conselho, matrícula, data de ingresso).</li>
    <li class="todo">• Tratamento automático de ausências em turnos já publicados (substituir ou anunciar cobertura).</li>
    <li class="todo">• Painel de tratamento de check-in/check-out (ajustar, restaurar, consolidar horários).</li>
    <li class="todo">• Lembretes programáveis de plantão e de check-in/out.</li>
    <li class="todo">• Perfil de gestor municipal completo e fluxo dedicado de substituição.</li>
    <li class="todo">• Dashboards executivos e relatórios avançados.</li>
    <li class="todo">• Aplicativo nativo nas lojas e check-in/out offline.</li>
  </ul>

  <h2>3. Parte financeira e fiscal</h2>
  <h3>O que os editais pedem (confirmado)</h3>
  <ul>
    <li>TR 027: Extrato Financeiro completo (profissional, equipe, turno, bônus, TAGs) + Metabase + Nota Fiscal de Serviços.</li>
    <li>Cotação 68: gestão financeira, valores por escala/profissional/turno/plantão, relatórios/extrato consolidado.</li>
  </ul>
  <h3>O que precisamos construir</h3>
  <ul>
    <li class="todo">• Extrato financeiro consolidado por profissional, equipe e turno.</li>
    <li class="todo">• Bônus por plantão (noturno, fim de semana, sobreaviso).</li>
    <li class="todo">• Filtros por escala, equipe, profissional e TAGs + exportação xlsx.</li>
    <li class="todo">• Relatório base para emissão de NFS-e e registro de NFS emitidas.</li>
    <li class="todo">• Demonstrativo de repasse por médico (PDF).</li>
    <li class="todo">• Integração com Metabase.</li>
  </ul>
  <h3>O que precisamos contratar / fazer na vida real</h3>
  <ul>
    <li class="todo">• Provedor de NFS-e (API da prefeitura ou serviço terceiro, ex.: eNotas, NFE.io).</li>
    <li class="todo">• Metabase (Docker próprio ou Metabase Cloud).</li>
    <li class="todo">• Contador para emissão e envio das NFS-e.</li>
    <li class="todo">• Certificado digital (se exigido pelo provedor de NFS-e).</li>
    <li class="todo">• Conta bancária PJ e balanços financeiros atualizados.</li>
  </ul>

  <h2>4. Aderência aos editais</h2>
  <table>
    <tr><th>Edital</th><th>Aderência técnica</th><th>Principais pendências</th></tr>
    <tr><td>TR 027/2021 (AEBES)</td><td>~55%</td><td>Grade rica, ausência automática, check-in, financeiro, NFS-e, app nativo, habilitação jurídica.</td></tr>
    <tr><td>Cotação 68/2025 (AgSUS)</td><td>~70%</td><td>Lembretes, gestor municipal, substituição, dashboards, qualificação técnica/econômica.</td></tr>
  </table>
  <p class="meta">Sem conflitos entre os dois: construir para o mais exigente (TR 027) atende automaticamente a Cotação 68.</p>

  <h2>5. Cronograma (sprints)</h2>
  <table>
    <tr><th>Sprint</th><th>Escopo</th><th>Estimativa</th></tr>
    <tr><td>1</td><td>Financeiro base (extrato, bônus, xlsx)</td><td>3–5 dias</td></tr>
    <tr><td>2</td><td>Gerador de relatórios PDF + PowerPoint</td><td>3–4 dias</td></tr>
    <tr><td>3</td><td>Grade rica, ausências, check-in</td><td>5–7 dias</td></tr>
    <tr><td>4</td><td>Lembretes, gestor municipal, substituição</td><td>3–4 dias</td></tr>
    <tr><td>5</td><td>NFS-e e Metabase</td><td>4–6 dias</td></tr>
    <tr><td>6</td><td>App nativo e offline</td><td>7–10 dias</td></tr>
    <tr><td>—</td><td>Habilitação e documentos (paralelo)</td><td>contínuo</td></tr>
  </table>

  <p class="meta" style="margin-top:20px">DoctorTurn — Gestão de escalas de plantão médico.</p>
</body>
</html>
