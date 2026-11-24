# Sistema de Prestação de Contas (Monorepo)

Estrutura organizada em dois aplicativos independentes:

- **`web/`** — Portal Web & API REST em **Laravel 12** (PHP 8.2, MySQL, Tesseract OCR local, Groq/Gemini IA, OpenWA WhatsApp)
- **`mobile/`** — Aplicativo Mobile em **React Native + Expo (TypeScript)** conectado à API `/api/mobile/*` do backend Laravel

## Como executar

### 1. Web + API (Laravel)
```powershell
cd web
php artisan serve --host=0.0.0.0 --port=8000
```

### 2. Mobile (React Native Expo)
```powershell
cd mobile
npm start
```
- No app mobile, informe a URL do servidor Laravel na tela de login (`http://10.0.2.2:8000` no Emulador Android, ou `http://<SEU_IP_LOCAL>:8000` no celular via Expo Go).
