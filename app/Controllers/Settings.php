<?php

namespace App\Controllers;

use App\Models\GeneralSettingsModel;
use App\Models\SmtpConfigModel;
use App\Services\OllamaService;
use Throwable;

class Settings extends BaseController
{
    private function ensureAdmin()
    {
        $role = session()->get('user_role') ?? session()->get('role') ?? 'user';

        if ($role !== 'admin') {
            return redirect()->to('/accountability')->with('error', 'Acesso restrito a administradores.');
        }

        return null;
    }

    public function index()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        $role       = session()->get('user_role') ?? session()->get('role') ?? 'admin';
        $activeTab  = (string) ($this->request->getGet('tab') ?? 'smtp');
        $smtpConfig = null;

        try {
            $smtpModel  = new SmtpConfigModel();
            $smtpConfig = $smtpModel->first();
        } catch (Throwable) {
            $smtpConfig = null;
        }

        $ollamaService = new OllamaService();
        $ollamaConfig  = $ollamaService->loadConfig();
        $ollamaStatus  = ($activeTab === 'ollama') ? $ollamaService->getStatus() : ['online' => false, 'models' => []];
        $generalConfig = GeneralSettingsModel::getSettings();

        return view('settings/index', [
            'userName'      => session()->get('user_name'),
            'username'      => session()->get('username'),
            'userEmail'     => session()->get('user_email'),
            'userRole'      => $role,
            'activeTab'     => $activeTab,
            'smtpConfig'    => $smtpConfig,
            'ollamaConfig'  => $ollamaConfig,
            'ollamaStatus'  => $ollamaStatus,
            'generalConfig' => $generalConfig,
        ]);
    }

    public function saveGeneral()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        $rules = [
            'app_name'     => 'required|min_length[2]|max_length[150]',
            'company_name' => 'required|min_length[2]|max_length[200]',
            'cnpj'         => 'permit_empty|max_length[30]',
        ];

        $messages = [
            'app_name' => [
                'required' => 'O campo Nome do App é obrigatório.',
            ],
            'company_name' => [
                'required' => 'O campo Empresa / Órgão é obrigatório.',
            ],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->to('/settings?tab=general')->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $model    = new GeneralSettingsModel();
            $existing = $model->first();
            $logoPath = $existing['app_logo'] ?? null;

            $logoFile = $this->request->getFile('app_logo');
            if ($logoFile !== null && $logoFile->getError() !== UPLOAD_ERR_NO_FILE) {
                if (! $logoFile->isValid() || $logoFile->hasMoved()) {
                    return redirect()->to('/settings?tab=general')->withInput()->with('error', 'Não foi possível processar a imagem da logo.');
                }

                $ext = strtolower((string) $logoFile->getClientExtension());
                if (! in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg'], true)) {
                    return redirect()->to('/settings?tab=general')->withInput()->with('error', 'Formato de logo inválido. Utilize PNG, JPG, JPEG, WEBP ou SVG.');
                }

                if ((int) $logoFile->getSize() > 5 * 1024 * 1024) {
                    return redirect()->to('/settings?tab=general')->withInput()->with('error', 'A imagem da logo deve ter no máximo 5 MB.');
                }

                $targetDir = WRITEPATH . 'uploads/branding';
                if (! is_dir($targetDir)) {
                    mkdir($targetDir, 0775, true);
                }

                if (! empty($logoPath)) {
                    $oldFullPath = WRITEPATH . $logoPath;
                    if (is_file($oldFullPath)) {
                        @unlink($oldFullPath);
                    }
                }

                $newName = 'app_logo_' . $logoFile->getRandomName();
                $logoFile->move($targetDir, $newName);
                $logoPath = 'uploads/branding/' . $newName;
            }

            $data = [
                'app_name'     => trim((string) $this->request->getPost('app_name')),
                'company_name' => trim((string) $this->request->getPost('company_name')),
                'cnpj'         => trim((string) $this->request->getPost('cnpj')),
                'app_logo'     => $logoPath,
            ];

            if ($existing !== null) {
                $model->update((int) $existing['id'], $data);
            } else {
                $model->insert($data);
            }

            GeneralSettingsModel::clearCache();

            return redirect()->to('/settings?tab=general')->with('success', 'Configurações gerais salvas com sucesso!');
        } catch (Throwable $e) {
            return redirect()->to('/settings?tab=general')->withInput()->with('error', 'Erro ao salvar configurações gerais: ' . $e->getMessage());
        }
    }

    public function removeLogo()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        try {
            $model    = new GeneralSettingsModel();
            $existing = $model->first();

            if ($existing !== null && ! empty($existing['app_logo'])) {
                $fullPath = WRITEPATH . $existing['app_logo'];
                if (is_file($fullPath)) {
                    @unlink($fullPath);
                }
                $model->update((int) $existing['id'], ['app_logo' => null]);
                GeneralSettingsModel::clearCache();
            }

            return redirect()->to('/settings?tab=general')->with('success', 'Logo personalizada removida. O sistema voltou a usar a logo padrão.');
        } catch (Throwable $e) {
            return redirect()->to('/settings?tab=general')->with('error', 'Erro ao remover logo: ' . $e->getMessage());
        }
    }

    public function savePdfCover()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        $rules = [
            'pdf_cover_label'         => 'permit_empty|max_length[120]',
            'pdf_cover_subtitle'      => 'permit_empty|max_length[255]',
            'pdf_cover_footer'        => 'permit_empty|max_length[255]',
            'pdf_cover_primary_color' => 'permit_empty|max_length[20]',
            'pdf_cover_accent_color'  => 'permit_empty|max_length[20]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to('/settings?tab=pdf_cover')->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $model    = new GeneralSettingsModel();
            $existing = $model->first();
            $artPath  = $existing['pdf_cover_art'] ?? null;

            $artFile = $this->request->getFile('pdf_cover_art');
            if ($artFile !== null && $artFile->getError() !== UPLOAD_ERR_NO_FILE) {
                if (! $artFile->isValid() || $artFile->hasMoved()) {
                    return redirect()->to('/settings?tab=pdf_cover')->withInput()->with('error', 'Não foi possível processar a imagem da arte de capa.');
                }

                $ext = strtolower((string) $artFile->getClientExtension());
                if (! in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
                    return redirect()->to('/settings?tab=pdf_cover')->withInput()->with('error', 'Formato inválido para arte de capa. Utilize PNG, JPG, JPEG ou WEBP.');
                }

                if ((int) $artFile->getSize() > 10 * 1024 * 1024) {
                    return redirect()->to('/settings?tab=pdf_cover')->withInput()->with('error', 'A imagem da arte de capa deve ter no máximo 10 MB.');
                }

                $targetDir = WRITEPATH . 'uploads/branding';
                if (! is_dir($targetDir)) {
                    mkdir($targetDir, 0775, true);
                }

                if (! empty($artPath)) {
                    $oldFullPath = WRITEPATH . $artPath;
                    if (is_file($oldFullPath)) {
                        @unlink($oldFullPath);
                    }
                }

                $newName = 'pdf_cover_art_' . $artFile->getRandomName();
                $artFile->move($targetDir, $newName);
                $artPath = 'uploads/branding/' . $newName;
            }

            $primaryColor = trim((string) $this->request->getPost('pdf_cover_primary_color'));
            $accentColor  = trim((string) $this->request->getPost('pdf_cover_accent_color'));

            $data = [
                'pdf_cover_enabled'       => $this->request->getPost('pdf_cover_enabled') ? 1 : 0,
                'pdf_cover_index_enabled' => $this->request->getPost('pdf_cover_index_enabled') ? 1 : 0,
                'pdf_cover_show_logo'     => $this->request->getPost('pdf_cover_show_logo') ? 1 : 0,
                'pdf_cover_show_border'   => $this->request->getPost('pdf_cover_show_border') ? 1 : 0,
                'pdf_cover_label'         => trim((string) $this->request->getPost('pdf_cover_label')) ?: 'PRESTAÇÃO DE CONTAS',
                'pdf_cover_subtitle'      => trim((string) $this->request->getPost('pdf_cover_subtitle')) ?: 'DOSSIÊ DOCUMENTAL CONSOLIDADO',
                'pdf_cover_footer'        => trim((string) $this->request->getPost('pdf_cover_footer')),
                'pdf_cover_primary_color' => preg_match('/^#[0-9a-fA-F]{6}$/', $primaryColor) ? $primaryColor : '#0f172a',
                'pdf_cover_accent_color'  => preg_match('/^#[0-9a-fA-F]{6}$/', $accentColor) ? $accentColor : '#dd4814',
                'pdf_cover_art'           => $artPath,
            ];

            if ($existing !== null) {
                $model->update((int) $existing['id'], $data);
            } else {
                $data['app_name']     = 'Sistema Patrimonial';
                $data['company_name'] = 'Município de Arabutã';
                $model->insert($data);
            }

            GeneralSettingsModel::clearCache();

            return redirect()->to('/settings?tab=pdf_cover')->with('success', 'Configurações da capa do PDF unificado salvas com sucesso!');
        } catch (Throwable $e) {
            return redirect()->to('/settings?tab=pdf_cover')->withInput()->with('error', 'Erro ao salvar configurações da capa: ' . $e->getMessage());
        }
    }

    public function removePdfCoverArt()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        try {
            $model    = new GeneralSettingsModel();
            $existing = $model->first();

            if ($existing !== null && ! empty($existing['pdf_cover_art'])) {
                $fullPath = WRITEPATH . $existing['pdf_cover_art'];
                if (is_file($fullPath)) {
                    @unlink($fullPath);
                }
                $model->update((int) $existing['id'], ['pdf_cover_art' => null]);
                GeneralSettingsModel::clearCache();
            }

            return redirect()->to('/settings?tab=pdf_cover')->with('success', 'Arte personalizada da capa removida com sucesso!');
        } catch (Throwable $e) {
            return redirect()->to('/settings?tab=pdf_cover')->with('error', 'Erro ao remover arte da capa: ' . $e->getMessage());
        }
    }

    public function pdfCoverArt()
    {
        $settings = GeneralSettingsModel::getSettings();
        $artRel   = $settings['pdf_cover_art'] ?? null;

        if (empty($artRel)) {
            return $this->response->setStatusCode(404);
        }

        $fullPath = WRITEPATH . $artRel;
        if (! is_file($fullPath)) {
            return $this->response->setStatusCode(404);
        }

        $ext     = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mimeMap = [
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
        ];
        $mimeType = $mimeMap[$ext] ?? (mime_content_type($fullPath) ?: 'image/png');

        return $this->response
            ->setHeader('Content-Type', $mimeType)
            ->setHeader('Cache-Control', 'no-cache, must-revalidate')
            ->setBody((string) file_get_contents($fullPath));
    }

    public function appLogo()
    {
        $settings = GeneralSettingsModel::getSettings();
        $logoRel  = $settings['app_logo'] ?? null;

        if (! empty($logoRel)) {
            $fullPath = WRITEPATH . $logoRel;
            if (is_file($fullPath)) {
                $ext     = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                $mimeMap = [
                    'png'  => 'image/png',
                    'jpg'  => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'webp' => 'image/webp',
                    'svg'  => 'image/svg+xml',
                ];
                $mimeType = $mimeMap[$ext] ?? (mime_content_type($fullPath) ?: 'image/png');

                return $this->response
                    ->setHeader('Content-Type', $mimeType)
                    ->setHeader('Cache-Control', 'no-cache, must-revalidate')
                    ->setBody((string) file_get_contents($fullPath));
            }
        }

        $fallbackPath = FCPATH . 'assets/images/logo-arabuta.png';
        if (is_file($fallbackPath)) {
            return $this->response
                ->setHeader('Content-Type', 'image/png')
                ->setBody((string) file_get_contents($fallbackPath));
        }

        return $this->response->setStatusCode(404);
    }

    public function saveOllama()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        $rules = [
            'provider' => 'required|in_list[gemini,openrouter,groq,ollama]',
            'timeout'  => 'required|integer|greater_than_equal_to[15]|less_than_equal_to[600]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to('/settings?tab=ollama')->withInput()->with('errors', $this->validator->getErrors());
        }

        $ollamaService = new OllamaService();
        $ollamaService->saveConfig([
            'provider'           => $this->request->getPost('provider'),
            'gemini_api_key'     => $this->request->getPost('gemini_api_key'),
            'gemini_model'       => $this->request->getPost('gemini_model'),
            'openrouter_api_key' => $this->request->getPost('openrouter_api_key'),
            'openrouter_model'   => $this->request->getPost('openrouter_model'),
            'groq_api_key'       => $this->request->getPost('groq_api_key'),
            'groq_model'         => $this->request->getPost('groq_model'),
            'base_url'           => $this->request->getPost('base_url'),
            'model'              => $this->request->getPost('model'),
            'timeout'            => $this->request->getPost('timeout'),
            'system_prompt'      => $this->request->getPost('system_prompt'),
        ]);

        return redirect()->to('/settings?tab=ollama')->with('success', 'Configurações de Inteligência Artificial salvas com sucesso!');
    }

    public function saveSmtp()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        $rules = [
            'smtp_host'       => 'required|max_length[255]',
            'smtp_port'       => 'required|max_length[10]',
            'smtp_encryption' => 'required|in_list[STARTTLS,SSL,TLS,NONE]',
            'smtp_username'   => 'required|max_length[255]',
            'email_address'   => 'required|valid_email|max_length[255]',
            'email_name'      => 'required|max_length[255]',
            'reply_to_email'  => 'permit_empty|valid_email|max_length[255]',
        ];

        $messages = [
            'smtp_host' => [
                'required' => 'O campo Servidor SMTP (Host) é obrigatório.',
            ],
            'smtp_port' => [
                'required' => 'O campo Porta SMTP é obrigatório.',
            ],
            'smtp_encryption' => [
                'required' => 'Selecione o tipo de segurança/criptografia.',
            ],
            'smtp_username' => [
                'required' => 'O campo Usuário / E-mail de Login é obrigatório.',
            ],
            'email_address' => [
                'required'    => 'O campo E-mail do Remetente é obrigatório.',
                'valid_email' => 'Informe um E-mail do Remetente válido.',
            ],
            'email_name' => [
                'required' => 'O campo Nome de Exibição do Remetente é obrigatório.',
            ],
            'reply_to_email' => [
                'valid_email' => 'Informe um E-mail para Resposta válido.',
            ],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->to('/settings?tab=smtp')->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $smtpModel = new SmtpConfigModel();
            $existing  = $smtpModel->first();

            $passwordInput = (string) $this->request->getPost('smtp_password');

            $data = [
                'smtp_host'       => trim((string) $this->request->getPost('smtp_host')),
                'smtp_port'       => trim((string) $this->request->getPost('smtp_port')),
                'smtp_encryption' => trim((string) $this->request->getPost('smtp_encryption')),
                'smtp_username'   => trim((string) $this->request->getPost('smtp_username')),
                'email_address'   => trim((string) $this->request->getPost('email_address')),
                'email_name'      => trim((string) $this->request->getPost('email_name')),
                'reply_to_email'  => trim((string) $this->request->getPost('reply_to_email')),
                'smtp_auth'       => $this->request->getPost('smtp_auth') ? 1 : 0,
                'smtp_verify_ssl' => $this->request->getPost('smtp_verify_ssl') ? 1 : 0,
                'smtp_timeout'    => (int) ($this->request->getPost('smtp_timeout') ?: 30),
                'status'          => $this->request->getPost('status') ? 1 : 0,
            ];

            if ($passwordInput !== '') {
                $data['smtp_password'] = $passwordInput;
            } elseif ($existing === null) {
                $data['smtp_password'] = '';
            }

            if ($existing !== null) {
                $smtpModel->update($existing['id'], $data);
            } else {
                $smtpModel->insert($data);
            }

            return redirect()->to('/settings?tab=smtp')->with('success', 'Configurações de SMTP salvas com sucesso!');
        } catch (Throwable $e) {
            return redirect()->to('/settings?tab=smtp')->withInput()->with('error', 'Erro ao salvar configurações SMTP: ' . $e->getMessage());
        }
    }
}
