<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; line-height: 1.5; }
  h1 { color: #0f766e; font-size: 24px; border-bottom: 3px solid #0f766e; padding-bottom: 8px; }
  h2 { color: #073f3d; font-size: 16px; margin-top: 22px; border-left: 4px solid #0f766e; padding-left: 8px; }
  ul { margin: 6px 0; padding-left: 18px; }
  li { margin-bottom: 3px; }
  .done { color: #059669; }
  .todo { color: #b45309; }
  .buy { color: #b91c1c; }
  .meta { color: #6b7280; font-size: 11px; }
  .box { background: #f0fdfa; border: 1px solid #99f6e4; border-radius: 6px; padding: 10px 14px; margin: 10px 0; }
  ol { margin: 6px 0; padding-left: 20px; }
</style>
</head>
<body>
  <h1>Parte Financeira — DoctorTurn</h1>
  <p class="meta">Documento gerado em {{ now()->format('d/m/Y H:i') }} · O que precisamos construir e contratar</p>

  <div class="box">
    <strong>Escopo:</strong> tudo que precisamos fazer na parte financeira do DoctorTurn para atender aos editais TR 027/2021 e Cotação 68/2025 — o que construir no sistema e o que contratar/fazer na vida real.
  </div>

  <h2>1. O que os editais pedem (confirmado nos documentos)</h2>
  <ul>
    <li><strong>TR 027/2021:</strong> Extrato Financeiro completo (por profissional, por equipe, por turno, com bônus e filtros por TAG), Relatórios personalizados (Metabase) e Nota Fiscal de Serviços (o pagamento é feito após a emissão da NFS).</li>
    <li><strong>Cotação 68/2025:</strong> gestão financeira, valores distintos por escala, profissional, turno e plantão, e relatórios/extrato consolidado.</li>
  </ul>

  <h2>2. O que já temos</h2>
  <ul>
    <li class="done">✔ Valor por plantão e valor padrão por hospital.</li>
    <li class="done">✔ Valor por médico (por vínculo com o hospital).</li>
    <li class="done">✔ Valor por tipo de turno.</li>
    <li class="done">✔ Faturamento mensal básico por médico.</li>
  </ul>

  <h2>3. O que precisamos CONSTRUIR no sistema</h2>
  <ul>
    <li class="todo">• Extrato financeiro consolidado por profissional (quanto pagar a cada médico).</li>
    <li class="todo">• Extrato financeiro consolidado por equipe (por hora ou por alocação).</li>
    <li class="todo">• Relatório analítico por turno (detalhe de cada turno trabalhado).</li>
    <li class="todo">• Bônus por plantão (noturno, fim de semana, sobreaviso), com opção de contabilizar ou não.</li>
    <li class="todo">• Filtros por escala, equipe, profissional e TAGs.</li>
    <li class="todo">• Exportação para Excel (xlsx) com seleção de período e colunas.</li>
    <li class="todo">• Relatório base para emissão de NFS-e (itens, valores, período, tomador).</li>
    <li class="todo">• Registro de NFS emitidas (número, data, valor, status).</li>
    <li class="todo">• Demonstrativo de repasse por médico em PDF.</li>
    <li class="todo">• Integração com Metabase para relatórios personalizados.</li>
  </ul>

  <h2>4. O que precisamos CONTRATAR / fazer na vida real</h2>
  <ul>
    <li class="buy">• <strong>Provedor de NFS-e:</strong> API da prefeitura do tomador ou serviço terceiro (ex.: eNotas, NFE.io, FocusNFe) para emitir as Notas Fiscais de Serviços.</li>
    <li class="buy">• <strong>Metabase:</strong> hospedagem própria via Docker ou Metabase Cloud para relatórios personalizados.</li>
    <li class="buy">• <strong>Contador:</strong> para emissão, envio e escrituração das NFS-e aos tomadores.</li>
    <li class="buy">• <strong>Certificado digital:</strong> se exigido pelo provedor de NFS-e ou pela prefeitura.</li>
    <li class="buy">• <strong>Conta bancária PJ e dados bancários:</strong> para receber os pagamentos e incluir nas propostas.</li>
    <li class="buy">• <strong>Balanços financeiros atualizados:</strong> para a qualificação econômico-financeira das licitações.</li>
  </ul>

  <h2>5. Ordem de execução (financeiro)</h2>
  <ol>
    <li>Extrato financeiro consolidado (profissional, equipe, turno) + filtros + exportação xlsx.</li>
    <li>Bônus por plantão (noturno, fim de semana, sobreaviso).</li>
    <li>Relatório base para emissão de NFS-e + registro de NFS emitidas.</li>
    <li>Demonstrativo de repasse por médico em PDF.</li>
    <li>Integração com Metabase.</li>
    <li>Contratar provedor de NFS-e e contador (em paralelo).</li>
  </ol>

  <p class="meta" style="margin-top:20px">DoctorTurn — Gestão de escalas de plantão médico.</p>
</body>
</html>
