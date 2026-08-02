<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Privacidade e LGPD — {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-50 font-sans text-gray-800 antialiased">
    <div class="mx-auto max-w-3xl px-6 py-12">
        <a href="/" class="inline-block text-teal-600 hover:underline text-sm mb-6">← Voltar</a>
        <h1 class="text-2xl font-bold text-gray-900">Política de Privacidade e LGPD</h1>
        <p class="mt-1 text-sm text-gray-500">Última atualização: 02/08/2026</p>

        <div class="mt-6 space-y-5 text-sm leading-relaxed">
            <p>O {{ config('app.name') }} é uma plataforma de gestão de escalas de plantão médico. Esta política descreve como coletamos, usamos e protegemos seus dados pessoais, em conformidade com a Lei Geral de Proteção de Dados (LGPD — Lei nº 13.709/2018).</p>

            <h2 class="text-lg font-semibold text-gray-900">1. Dados que coletamos</h2>
            <ul class="list-disc pl-5 space-y-1">
                <li><strong>Cadastro:</strong> nome, e-mail, CPF, telefone, CRM, especialidade e gênero.</li>
                <li><strong>Operação:</strong> plantões, escalas, trocas, ausências e registros de check-in/check-out (incluindo localização, quando autorizado).</li>
                <li><strong>Comunicação:</strong> registros de e-mails e mensagens enviadas.</li>
            </ul>

            <h2 class="text-lg font-semibold text-gray-900">2. Finalidade</h2>
            <p>Usamos os dados para operar a plataforma: montar e publicar escalas, gerenciar trocas e ausências, registrar presença, enviar notificações e gerar relatórios para o hospital contratante.</p>

            <h2 class="text-lg font-semibold text-gray-900">3. Base legal</h2>
            <p>O tratamento é feito com base na execução de contrato (prestação do serviço ao hospital e ao profissional) e no legítimo interesse, respeitados os direitos do titular.</p>

            <h2 class="text-lg font-semibold text-gray-900">4. Compartilhamento</h2>
            <p>Os dados são compartilhados apenas com o hospital ao qual o profissional está vinculado e com operadores essenciais (hospedagem, e-mail e WhatsApp/Meta), sempre sob obrigação de confidencialidade. Não vendemos dados pessoais.</p>

            <h2 class="text-lg font-semibold text-gray-900">5. Geolocalização</h2>
            <p>A localização é coletada apenas no momento do check-in/check-out, mediante autorização do dispositivo, e usada exclusivamente para validar a presença no local do plantão.</p>

            <h2 class="text-lg font-semibold text-gray-900">6. Segurança</h2>
            <p>Aplicamos medidas técnicas e organizacionais para proteger os dados: criptografia em trânsito (HTTPS), controle de acesso por perfil, autenticação e backups.</p>

            <h2 class="text-lg font-semibold text-gray-900">7. Seus direitos</h2>
            <p>Você pode, a qualquer momento: confirmar a existência de tratamento, acessar, corrigir, anonizar, bloquear ou eliminar seus dados, e revogar consentimentos, conforme o art. 18 da LGPD. Para exercer, fale com o seu hospital ou com o suporte.</p>

            <h2 class="text-lg font-semibold text-gray-900">8. Retenção</h2>
            <p>Conservamos os dados pelo tempo necessário à prestação do serviço e ao cumprimento de obrigações legais e contratuais.</p>

            <h2 class="text-lg font-semibold text-gray-900">9. Contato e encarregado (DPO)</h2>
            <p>Para dúvidas sobre privacidade e proteção de dados, entre em contato pelo e-mail de suporte do {{ config('app.name') }}.</p>
        </div>
    </div>
</body>
</html>
