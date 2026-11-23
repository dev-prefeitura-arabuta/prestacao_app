<?php

namespace App\Http\Controllers;

use App\Models\GeneralSettings;
use App\Models\SmtpConfig;
use App\Services\OllamaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class SettingsController extends Controller
{
    private function ensureAdmin()
    {
        $role = session('user_role') ?? session('role') ?? 'user';

        if ($role !== 'admin') {
            return redirect('/accountability')->with('error', 'Acesso restrito a administradores.');
        }

        return null;
    }

    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        $role       = session('user_role') ?? session('role') ?? 'admin';
        $activeTab  = (string) ($request->query('tab', 'smtp'));
        $smtpConfig = null;

        try {
            $row        = SmtpConfig::first();
            $smtpConfig = $row ? $row->toArray() : null;
        } catch (Throwable) {
            $smtpConfig = null;
        }

        $ollamaService = new OllamaService();
        $ollamaConfig  = $ollamaService->loadConfig();
        $ollamaStatus  = ($activeTab === 'ollama') ? $ollamaService->getStatus() : ['online' => false, 'models' => []];
        $generalConfig = GeneralSettings::getSettings();

        return view('settings.index', [
            'userName'      => session('user_name'),
            'username'      => session('username'),
            'userEmail'     => session('user_email'),
            'userRole'      => $role,
            'activeTab'     => $activeTab,
            'smtpConfig'    => $smtpConfig,
            'ollamaConfig'  => $ollamaConfig,
            'ollamaStatus'  => $ollamaStatus,
            'generalConfig' => $generalConfig,
        ]);
    }

    public function saveGeneral(Request $request)
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        $validator = Validator::make($request->all(), [
            'app_name'     => 'required|min:2|max:150',
            'company_name' => 'required|min:2|max:200',
            'cnpj'         => 'nullable|max:30',
        ], [
            'app_name.required'     => 'O campo Nome do App é obrigatório.',
            'company_name.required' => 'O campo Empresa / Órgão é obrigatório.',
        ]);

        if ($validator->fails()) {
            return redirect('/settings?tab=general')->withInput()->with('errors_list', $validator->errors()->all());
        }

        try {
            $existing = GeneralSettings::first();
            $logoPath = $existing?->app_logo;

            if ($request->hasFile('app_logo')) {
                $logoFile = $request->file('app_logo');
                if (! $logoFile->isValid()) {
                    return redirect('/settings?tab=general')->withInput()->with('error', 'Não foi possível processar a imagem da logo.');
                }

                $ext = strtolower((string) $logoFile->getClientOriginalExtension());
                if (! in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg'], true)) {
                    return redirect('/settings?tab=general')->withInput()->with('error', 'Formato de logo inválido. Utilize PNG, JPG, JPEG, WEBP ou SVG.');
                }

                if ($logoFile->getSize() > 5 * 1024 * 1024) {
                    return redirect('/settings?tab=general')->withInput()->with('error', 'A imagem da logo deve ter no máximo 5 MB.');
                }

                $targetDir = storage_path('app/uploads/branding');
                if (! is_dir($targetDir)) {
                    mkdir($targetDir, 0775, true);
                }

                if (! empty($logoPath)) {
                    $oldFullPath = storage_path('app/' . $logoPath);
                    if (is_file($oldFullPath)) {
                        @unlink($oldFullPath);
                    }
                }

                $newName = 'app_logo_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
                $logoFile->move($targetDir, $newName);
                $logoPath = 'uploads/branding/' . $newName;
            }

            $data = [
                'app_name'     => trim((string) $request->input('app_name')),
                'company_name' => trim((string) $request->input('company_name')),
                'cnpj'         => trim((string) $request->input('cnpj')),
                'app_logo'     => $logoPath,
            ];

            if ($existing !== null) {
                $existing->update($data);
            } else {
                GeneralSettings::create($data);
            }

            GeneralSettings::clearCache();

            return redirect('/settings?tab=general')->with('success', 'Configurações gerais salvas com sucesso!');
        } catch (Throwable $e) {
            return redirect('/settings?tab=general')->withInput()->with('error', 'Erro ao salvar configurações gerais: ' . $e->getMessage());
        }
    }

    public function removeLogo()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        try {
            $existing = GeneralSettings::first();
            if ($existing !== null && ! empty($existing->app_logo)) {
                $fullPath = storage_path('app/' . $existing->app_logo);
                if (is_file($fullPath)) {
                    @unlink($fullPath);
                }
                $existing->update(['app_logo' => null]);
                GeneralSettings::clearCache();
            }

            return redirect('/settings?tab=general')->with('success', 'Logo personalizada removida. O sistema voltou a usar a logo padrão.');
        } catch (Throwable $e) {
            return redirect('/settings?tab=general')->with('error', 'Erro ao remover logo: ' . $e->getMessage());
        }
    }

    public function savePdfCover(Request $request)
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        try {
            $existing = GeneralSettings::first();
            $artPath  = $existing?->pdf_cover_art;

            if ($request->hasFile('pdf_cover_art')) {
                $artFile = $request->file('pdf_cover_art');
                if (! $artFile->isValid()) {
                    return redirect('/settings?tab=pdf_cover')->withInput()->with('error', 'Não foi possível processar a imagem da arte de capa.');
                }

                $ext = strtolower((string) $artFile->getClientOriginalExtension());
                if (! in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
                    return redirect('/settings?tab=pdf_cover')->withInput()->with('error', 'Formato inválido para arte de capa. Utilize PNG, JPG, JPEG ou WEBP.');
                }

                if ($artFile->getSize() > 10 * 1024 * 1024) {
                    return redirect('/settings?tab=pdf_cover')->withInput()->with('error', 'A imagem da arte de capa deve ter no máximo 10 MB.');
                }

                $targetDir = storage_path('app/uploads/branding');
                if (! is_dir($targetDir)) {
                    mkdir($targetDir, 0775, true);
                }

                if (! empty($artPath)) {
                    $oldFullPath = storage_path('app/' . $artPath);
                    if (is_file($oldFullPath)) {
                        @unlink($oldFullPath);
                    }
                }

                $newName = 'pdf_cover_art_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
                $artFile->move($targetDir, $newName);
                $artPath = 'uploads/branding/' . $newName;
            }

            $primaryColor = trim((string) $request->input('pdf_cover_primary_color'));
            $accentColor  = trim((string) $request->input('pdf_cover_accent_color'));

            $data = [
                'pdf_cover_enabled'       => $request->has('pdf_cover_enabled') ? 1 : 0,
                'pdf_cover_index_enabled' => $request->has('pdf_cover_index_enabled') ? 1 : 0,
                'pdf_cover_show_logo'     => $request->has('pdf_cover_show_logo') ? 1 : 0,
                'pdf_cover_show_border'   => $request->has('pdf_cover_show_border') ? 1 : 0,
                'pdf_cover_label'         => trim((string) $request->input('pdf_cover_label')) ?: 'PRESTAÇÃO DE CONTAS',
                'pdf_cover_subtitle'      => trim((string) $request->input('pdf_cover_subtitle')) ?: 'DOSSIÊ DOCUMENTAL CONSOLIDADO',
                'pdf_cover_footer'        => trim((string) $request->input('pdf_cover_footer')),
                'pdf_cover_primary_color' => preg_match('/^#[0-9a-fA-F]{6}$/', $primaryColor) ? $primaryColor : '#0f172a',
                'pdf_cover_accent_color'  => preg_match('/^#[0-9a-fA-F]{6}$/', $accentColor) ? $accentColor : '#dd4814',
                'pdf_cover_art'           => $artPath,
            ];

            if ($existing !== null) {
                $existing->update($data);
            } else {
                $data['app_name']     = 'Sistema Patrimonial';
                $data['company_name'] = 'Município de Arabutã';
                GeneralSettings::create($data);
            }

            GeneralSettings::clearCache();

            return redirect('/settings?tab=pdf_cover')->with('success', 'Configurações da capa do PDF unificado salvas com sucesso!');
        } catch (Throwable $e) {
            return redirect('/settings?tab=pdf_cover')->withInput()->with('error', 'Erro ao salvar configurações da capa: ' . $e->getMessage());
        }
    }

    public function removePdfCoverArt()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        try {
            $existing = GeneralSettings::first();
            if ($existing !== null && ! empty($existing->pdf_cover_art)) {
                $fullPath = storage_path('app/' . $existing->pdf_cover_art);
                if (is_file($fullPath)) {
                    @unlink($fullPath);
                }
                $existing->update(['pdf_cover_art' => null]);
                GeneralSettings::clearCache();
            }

            return redirect('/settings?tab=pdf_cover')->with('success', 'Arte personalizada da capa removida com sucesso!');
        } catch (Throwable $e) {
            return redirect('/settings?tab=pdf_cover')->with('error', 'Erro ao remover arte da capa: ' . $e->getMessage());
        }
    }

    public function pdfCoverArt()
    {
        $settings = GeneralSettings::getSettings();
        $artRel   = $settings['pdf_cover_art'] ?? null;

        if (empty($artRel)) {
            abort(404);
        }

        $fullPath = storage_path('app/' . $artRel);
        if (! is_file($fullPath)) {
            abort(404);
        }

        return response()->file($fullPath, ['Cache-Control' => 'no-cache, must-revalidate']);
    }

    public function appLogo()
    {
        $settings = GeneralSettings::getSettings();
        $logoRel  = $settings['app_logo'] ?? null;

        if (! empty($logoRel)) {
            $fullPath = storage_path('app/' . $logoRel);
            if (is_file($fullPath)) {
                return response()->file($fullPath, ['Cache-Control' => 'no-cache, must-revalidate']);
            }
        }

        $fallbackPath = public_path('assets/images/logo.png');
        if (is_file($fallbackPath)) {
            return response()->file($fallbackPath);
        }

        abort(404);
    }

    public function saveOllama(Request $request)
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        $ollamaService = new OllamaService();
        $ollamaService->saveConfig([
            'provider'           => $request->input('provider'),
            'gemini_api_key'     => $request->input('gemini_api_key'),
            'gemini_model'       => $request->input('gemini_model'),
            'openrouter_api_key' => $request->input('openrouter_api_key'),
            'openrouter_model'   => $request->input('openrouter_model'),
            'groq_api_key'       => $request->input('groq_api_key'),
            'groq_model'         => $request->input('groq_model'),
            'base_url'           => $request->input('base_url'),
            'model'              => $request->input('model'),
            'timeout'            => $request->input('timeout', 180),
            'system_prompt'      => $request->input('system_prompt'),
        ]);

        return redirect('/settings?tab=ollama')->with('success', 'Configurações de Inteligência Artificial salvas com sucesso!');
    }

    public function saveSmtp(Request $request)
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        $validator = Validator::make($request->all(), [
            'smtp_host'       => 'required|max:255',
            'smtp_port'       => 'required|max:10',
            'smtp_encryption' => 'required|in:STARTTLS,SSL,TLS,NONE',
            'smtp_username'   => 'required|max:255',
            'email_address'   => 'required|email|max:255',
            'email_name'      => 'required|max:255',
            'reply_to_email'  => 'nullable|email|max:255',
        ], [
            'smtp_host.required'       => 'O campo Servidor SMTP (Host) é obrigatório.',
            'smtp_port.required'       => 'O campo Porta SMTP é obrigatório.',
            'smtp_encryption.required' => 'Selecione o tipo de segurança/criptografia.',
            'smtp_username.required'   => 'O campo Usuário / E-mail de Login é obrigatório.',
            'email_address.required'   => 'O campo E-mail do Remetente é obrigatório.',
            'email_address.email'      => 'Informe um E-mail do Remetente válido.',
            'email_name.required'      => 'O campo Nome de Exibição do Remetente é obrigatório.',
            'reply_to_email.email'     => 'Informe um E-mail para Resposta válido.',
        ]);

        if ($validator->fails()) {
            return redirect('/settings?tab=smtp')->withInput()->with('errors_list', $validator->errors()->all());
        }

        try {
            $existing      = SmtpConfig::first();
            $passwordInput = (string) $request->input('smtp_password');

            $data = [
                'smtp_host'       => trim((string) $request->input('smtp_host')),
                'smtp_port'       => trim((string) $request->input('smtp_port')),
                'smtp_encryption' => trim((string) $request->input('smtp_encryption')),
                'smtp_username'   => trim((string) $request->input('smtp_username')),
                'email_address'   => trim((string) $request->input('email_address')),
                'email_name'      => trim((string) $request->input('email_name')),
                'reply_to_email'  => trim((string) $request->input('reply_to_email')),
                'smtp_auth'       => $request->has('smtp_auth') ? 1 : 0,
                'smtp_verify_ssl' => $request->has('smtp_verify_ssl') ? 1 : 0,
                'smtp_timeout'    => (int) ($request->input('smtp_timeout') ?: 30),
                'status'          => $request->has('status') ? 1 : 0,
            ];

            if ($passwordInput !== '') {
                $data['smtp_password'] = $passwordInput;
            } elseif ($existing === null) {
                $data['smtp_password'] = '';
            }

            if ($existing !== null) {
                $existing->update($data);
            } else {
                SmtpConfig::create($data);
            }

            return redirect('/settings?tab=smtp')->with('success', 'Configurações de SMTP salvas com sucesso!');
        } catch (Throwable $e) {
            return redirect('/settings?tab=smtp')->withInput()->with('error', 'Erro ao salvar configurações SMTP: ' . $e->getMessage());
        }
    }

    public function toggleSmtpStatus()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        try {
            $existing = SmtpConfig::first();
            if ($existing === null) {
                return redirect('/settings?tab=smtp')->with('error', 'Salve as configurações de SMTP primeiro antes de ativar ou desativar.');
            }

            $newStatus = ((int) ($existing->status ?? 0) === 1) ? 0 : 1;
            $existing->update(['status' => $newStatus]);

            $msg = $newStatus === 1
                ? 'Envio de e-mails (SMTP) ATIVADO com sucesso!'
                : 'Envio de e-mails (SMTP) DESATIVADO com sucesso!';

            return redirect('/settings?tab=smtp')->with('success', $msg);
        } catch (Throwable $e) {
            return redirect('/settings?tab=smtp')->with('error', 'Erro ao alternar status do SMTP: ' . $e->getMessage());
        }
    }
}
