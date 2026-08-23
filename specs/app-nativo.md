# Publicação do App Nativo — DoctorTurn

> Guia para publicar o DoctorTurn como aplicativo na **Google Play** e **App Store**, usando **TWA (Trusted Web Activity)** para Android e **PWA** para iOS. É o caminho mais rápido (reaproveita o PWA existente, sem reescrever o app).

---

## 1. Android — Google Play (TWA)

O TWA empacota o PWA como um app nativo na Play Store, abrindo em tela cheia sem barra de navegador.

### 1.1 Pré-requisitos
- Conta de desenvolvedor Google Play (US$ 25, pagamento único).
- **Bubblewrap** (ferramenta oficial do Google para TWA): `npm install -g @bubblewrap/cli`.
- JDK instalado (para assinar o APK/AAB).

### 1.2 Passos

1. **Inicializar o projeto TWA:**
   ```bash
   bubblewrap init --manifest https://doctorturn.com.br/manifest.json
   ```
   Isso gera o projeto Android com base no nosso `manifest.json`.

2. **Configurar o pacote:**
   - `package_name`: `br.com.doctorturn.app`
   - `name`: DoctorTurn
   - Ícones: usar `public/images/icon-192.png` e `icon-512.png`.

3. **Gerar o certificado de assinatura:**
   ```bash
   keytool -genkey -v -keystore doctorturn.keystore -alias doctorturn -keyalg RSA -keysize 2048 -validity 10000
   ```

4. **Obter a fingerprint SHA-256 do certificado:**
   ```bash
   keytool -list -v -keystore doctorturn.keystore -alias doctorturn
   ```
   Copiar a fingerprint e colar em `public/.well-known/assetlinks.json` (substituir `SUBSTITUIR_PELA_FINGERPRINT_DO_CERTIFICADO_DE_ASSINATURA`).

5. **Publicar o assetlinks.json** (já está em `public/.well-known/assetlinks.json` — o deploy o serve em `https://doctorturn.com.br/.well-known/assetlinks.json`).

6. **Gerar o AAB (Android App Bundle):**
   ```bash
   bubblewrap build
   bubblewrap sign --keystore doctorturn.keystore
   ```

7. **Enviar para a Play Console:**
   - Criar o app na Play Console.
   - Enviar o AAB assinado.
   - Preencher a ficha da loja (descrição, screenshots, categoria "Medicina" ou "Produtividade").
   - Enviar para revisão.

### 1.3 Notificações push (Android)
- O TWA suporta notificações push via **Firebase Cloud Messaging (FCM)**.
- Criar um projeto no Firebase, adicionar o app Android e configurar o `google-services.json` no projeto TWA.

---

## 2. iOS — App Store (PWA)

A Apple **não aceita TWA**, mas aceita PWAs instalados via Safari ("Adicionar à Tela de Início"). Para uma presença na App Store, há duas opções:

### 2.1 Opção A — PWA via Safari (sem custo, imediato)
- O usuário abre `doctorturn.com.br` no Safari e toca em **"Adicionar à Tela de Início"**.
- O PWA já está configurado (manifesto, ícones, `apple-touch-icon`).
- **Vantagem:** zero custo e zero revisão. **Limitação:** não aparece na App Store.

### 2.2 Opção B — App nativo wrapper (Capacitor) — para estar na App Store
- Usar **Capacitor** (Ionic) para empacotar o PWA como app iOS nativo.
- Requer: conta de desenvolvedor Apple (US$ 99/ano), um Mac com Xcode.
- Passos:
  1. `npm install @capacitor/core @capacitor/cli @capacitor/ios`
  2. `npx cap init DoctorTurn br.com.doctorturn.app --web-dir public`
  3. `npx cap add ios`
  4. Configurar o `capacitor.config.ts` com `server.url = 'https://doctorturn.com.br'`.
  5. Abrir no Xcode, configurar assinatura e enviar para a App Store.

### 2.3 Notificações push (iOS)
- Via **Apple Push Notification service (APNs)**, configurado no projeto Capacitor/Xcode.

---

## 3. Checklist de publicação

### Android (Google Play)
- [ ] Conta Google Play criada.
- [ ] Bubblewrap instalado e projeto TWA gerado.
- [ ] Certificado de assinatura criado.
- [ ] Fingerprint SHA-256 no `assetlinks.json` (deploy feito).
- [ ] AAB gerado e assinado.
- [ ] Ficha da loja preenchida e enviada para revisão.
- [ ] Firebase/FCM configurado (push).

### iOS (App Store)
- [ ] Conta Apple Developer criada.
- [ ] Capacitor configurado e projeto iOS gerado.
- [ ] Assinatura configurada no Xcode.
- [ ] App enviado para a App Store.
- [ ] APNs configurado (push).

---

## 4. Observações

- **Caminho mais rápido para "app nas lojas":** começar pelo **Android (TWA)** — é o que o TR 027 pede explicitamente e é o mais direto.
- O **iOS** pode começar como **PWA via Safari** (zero custo) e evoluir para Capacitor quando necessário.
- O `assetlinks.json` precisa estar acessível publicamente em `https://doctorturn.com.br/.well-known/assetlinks.json` para o TWA funcionar (já está em `public/.well-known/`).

---

*Guia de publicação do app nativo DoctorTurn. Atualizado em 02/08/2026.*
