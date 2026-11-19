<?php

namespace App\Controllers;

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

        return view('settings/index', [
            'userName'     => session()->get('user_name'),
            'username'     => session()->get('username'),
            'userEmail'    => session()->get('user_email'),
            'userRole'     => $role,
            'activeTab'    => $activeTab,
            'smtpConfig'   => $smtpConfig,
            'ollamaConfig' => $ollamaConfig,
            'ollamaStatus' => $ollamaStatus,
        ]);
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
