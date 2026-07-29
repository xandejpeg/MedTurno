<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Bem-vindo ao DoctorTurn</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f6f7; color: #17213a; font-family: Arial, Helvetica, sans-serif;">
	<div style="display: none; max-height: 0; overflow: hidden; opacity: 0;">Informações sobre suas notificações administrativas no DoctorTurn.</div>
	<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width: 100%; background-color: #f3f6f7;">
		<tr>
			<td align="center" style="padding: 32px 16px;">
				<table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="width: 100%; max-width: 600px; background-color: #ffffff; border: 1px solid #e2e8ea; border-radius: 8px; overflow: hidden;">
					<tr>
						<td align="center" style="padding: 30px 36px 24px; border-bottom: 3px solid #70d400;">
							<img src="{{ $message->embed(public_path('images/doctor-turn-email-cover.jpeg')) }}" alt="DoctorTurn" width="460" style="display: block; width: 100%; max-width: 460px; height: auto; border: 0;">
						</td>
					</tr>
					<tr>
						<td style="padding: 34px 40px 30px; font-size: 16px; line-height: 1.6;">
							<h1 style="margin: 0 0 20px; color: #07152f; font-size: 24px; line-height: 1.3; font-weight: 700;">Olá, {{ $recipientName }}!</h1>
							<p style="margin: 0 0 20px;">A partir de agora, este endereço receberá notificações administrativas relacionadas à gestão de escalas:</p>
							<p style="margin: 0 0 22px; padding: 13px 16px; background-color: #f2fbf7; border-left: 4px solid #13b8b2; color: #07152f; font-weight: 700; word-break: break-word;">{{ $recipientEmail }}</p>
							<p style="margin: 0 0 20px;">Esta mensagem é direcionada exclusivamente aos administradores e gestores.</p>
							<p style="margin: 0;">Caso queira alterar o e-mail de recebimento, basta responder a esta mensagem informando o novo endereço desejado.</p>
						</td>
					</tr>
					<tr>
						<td align="center" style="padding: 24px 30px; background-color: #07152f; color: #d8e1e7; font-size: 12px; line-height: 1.6;">
							<strong style="display: block; margin-bottom: 3px; color: #ffffff; font-size: 14px;">DoctorTurn</strong>
							Gestão inteligente de escalas médicas<br>
							<span style="color: #aebbc5;">© 2026 DoctorTurn. Todos os direitos reservados.</span>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>