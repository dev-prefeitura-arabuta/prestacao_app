<?php

namespace App\Controllers;

use App\Models\SmtpConfigModel;
use Throwable;

class Settings extends BaseController
{
    private function ensureAdmin()
    {
        $role = session()->get('user_role') ?? session()->get('role') ?? 'user';

        if ($role !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Acesso restrito a administradores.');
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

        return view('settings/index', [
            'userName'   => session()->get('user_name'),
            'username'   => session()->get('username'),
            'userEmail'  => session()->get('user_email'),
            'userRole'   => $role,
            'activeTab'  => $activeTab,
            'smtpConfig' => $smtpConfig,
        ]);
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
                'required' => 'O campo SMTP Host é obrigatório.',
            ],
            'smtp_port' => [
                'required' => 'O campo SMTP Port é obrigatório.',
            ],
            'smtp_encryption' => [
                'required' => 'Selecione o tipo de criptografia.',
            ],
            'smtp_username' => [
                'required' => 'O campo Username é obrigatório.',
            ],
            'email_address' => [
                'required'    => 'O campo From Email é obrigatório.',
                'valid_email' => 'Informe um From Email válido.',
            ],
            'email_name' => [
                'required' => 'O campo From Name é obrigatório.',
            ],
            'reply_to_email' => [
                'valid_email' => 'Informe um Reply-To Email válido.',
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
