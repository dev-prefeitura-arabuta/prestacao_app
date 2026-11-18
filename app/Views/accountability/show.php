<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($accountability['title']) ?> - Checklist Documental</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
        }

        .app-layout {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        .main-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            width: 100%;
        }

        .container {
            width: 100%;
            max-width: 100%;
            padding: 1.75rem 2rem;
        }

        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .page-header h1 {
            font-size: 1.45rem;
            color: #0f172a;
        }

        .page-header p {
            color: #64748b;
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }

        .meta-pills {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            flex-wrap: wrap;
            margin-top: 0.6rem;
        }

        .meta-pill {
            font-size: 0.78rem;
            font-weight: 600;
            padding: 0.25rem 0.7rem;
            border-radius: 999px;
            background-color: #e2e8f0;
            color: #1e293b;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            padding: 0.55rem 0.95rem;
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            color: #1e293b;
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: background-color 0.15s ease;
        }

        .btn-back:hover {
            background-color: #f1f5f9;
        }

        .alert {
            padding: 0.85rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.25rem;
            font-size: 0.9rem;
        }

        .alert-success {
            background-color: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background-color: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        /* Painel Superior: Situação dos Documentos */
        .situation-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 1.5rem 1.75rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        }

        .situation-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .situation-top h2 {
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
        }

        .legend-row {
            display: flex;
            align-items: center;
            gap: 1.15rem;
            flex-wrap: wrap;
            margin-bottom: 1.15rem;
        }

        .legend-item {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.85rem;
            color: #334155;
            font-weight: 500;
        }

        /* Ícones circulares de status */
        .status-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.78rem;
            font-weight: 800;
        }

        .status-icon.lg {
            width: 32px;
            height: 32px;
            font-size: 0.9rem;
        }

        .status-icon-pendente {
            background-color: #ffedd5;
            border: 2px solid #f97316;
            color: #ea580c;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.15);
        }

        .status-icon-enviado {
            background-color: #dbeafe;
            color: #1d4ed8;
        }

        .status-icon-confirmado {
            background-color: #dcfce7;
            color: #15803d;
        }

        .status-icon-rejeitado {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .status-icon-prazo_vencido {
            background-color: #fef3c7;
            color: #d97706;
        }

        .search-box-wrap {
            position: relative;
            margin-bottom: 1rem;
        }

        .search-box-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            font-size: 0.9rem;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background-color: #fcfcfd;
            color: #0f172a;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .search-box-input:focus {
            border-color: #2563eb;
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .search-box-icon {
            position: absolute;
            left: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            width: 16px;
            height: 16px;
        }

        .filter-tabs {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            flex-wrap: wrap;
        }

        .filter-tab-btn {
            padding: 0.52rem 1rem;
            border-radius: 9px;
            border: 1px solid #e2e8f0;
            background-color: #ffffff;
            color: #475569;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .filter-tab-btn:hover {
            border-color: #cbd5e1;
            color: #0f172a;
        }

        .filter-tab-btn.active {
            background-color: #eff6ff;
            border-color: #2563eb;
            color: #1e40af;
            font-weight: 700;
        }

        /* Card Inferior: Checklist Documental */
        .checklist-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        }

        .checklist-header {
            padding: 1.35rem 1.75rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .checklist-header h2 {
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
        }

        .checklist-header p {
            font-size: 0.83rem;
            color: #64748b;
            margin-top: 0.18rem;
        }

        .checklist-total-count {
            font-size: 0.85rem;
            color: #64748b;
            font-weight: 600;
        }

        .checklist-row {
            padding: 1.15rem 1.75rem;
            border-bottom: 1px solid #f1f5f9;
            border-left: 4px solid transparent;
            transition: background-color 0.15s ease, border-color 0.15s ease;
            cursor: pointer;
        }

        .checklist-row:last-child {
            border-bottom: none;
        }

        .checklist-row:hover {
            background-color: #f8fafc;
        }

        .checklist-row[data-status="pendente"] {
            border-left-color: #f97316;
            background-color: #fffaf5;
        }

        .checklist-row[data-status="pendente"]:hover {
            background-color: #fff3e6;
        }

        .checklist-row[data-status="rejeitado"] {
            border-left-color: #dc2626;
            background-color: #fef2f2;
        }


        .checklist-row-main {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.25rem;
            flex-wrap: wrap;
        }

        .checklist-left {
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
            flex: 1;
            min-width: 260px;
        }

        .checklist-num {
            font-size: 0.88rem;
            color: #64748b;
            font-weight: 600;
            min-width: 26px;
            padding-top: 0.32rem;
        }

        .checklist-content {
            flex: 1;
        }

        .checklist-title {
            font-size: 0.96rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.45;
            margin-bottom: 0.4rem;
        }

        .checklist-badges-row {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            flex-wrap: wrap;
        }

        /* Pílulas de Status */
        .status-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.24rem 0.72rem;
            border-radius: 999px;
            font-size: 0.76rem;
            font-weight: 700;
        }

        .status-pill-pendente {
            background-color: #ffedd5;
            color: #c2410c;
            border: 1px solid #fdba74;
        }

        .status-pill-enviado {
            background-color: #dbeafe;
            color: #1d4ed8;
        }

        .status-pill-confirmado {
            background-color: #dcfce7;
            color: #15803d;
        }

        .status-pill-rejeitado {
            background-color: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .status-pill-prazo_vencido {
            background-color: #fef3c7;
            color: #b45309;
        }

        .att-count-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.22rem 0.65rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #475569;
        }

        .att-count-badge.has-files {
            background-color: #eff6ff;
            border-color: #bfdbfe;
            color: #1d4ed8;
        }

        .checklist-right {
            display: flex;
            align-items: center;
            gap: 0.65rem;
        }

        .btn-clip-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            min-width: 40px;
            height: 38px;
            padding: 0 0.7rem;
            border-radius: 9px;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            color: #94a3b8;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .btn-clip-icon svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }

        .btn-clip-icon:hover {
            background-color: #f1f5f9;
            border-color: #cbd5e1;
            color: #475569;
        }

        .btn-clip-icon.has-files {
            background-color: #dbeafe;
            border-color: #60a5fa;
            color: #1d4ed8;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .btn-clip-icon.has-files:hover {
            background-color: #2563eb;
            border-color: #2563eb;
            color: #ffffff;
        }

        .btn-submit-item-row {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.52rem 1rem;
            border-radius: 9px;
            border: none;
            background-color: #16a34a;
            color: #ffffff;
            font-size: 0.84rem;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        .btn-submit-item-row:hover {
            background-color: #15803d;
        }

        /* Modal de Gerenciamento / Confirmação / Anexos do Item */
        .item-modal-backdrop {
            position: fixed;
            inset: 0;
            background-color: rgba(15, 23, 42, 0.72);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9000;
            padding: 1.25rem;
        }

        .item-modal-backdrop.open {
            display: flex;
        }

        .item-modal-dialog {
            background-color: #ffffff;
            border-radius: 16px;
            width: 100%;
            max-width: 920px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45);
        }

        .item-modal-header {
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            background-color: #f8fafc;
        }

        .item-modal-header-left {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .item-modal-num {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: 700;
            font-size: 0.85rem;
            padding: 0.35rem 0.65rem;
            border-radius: 7px;
            flex-shrink: 0;
        }

        .item-modal-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.4;
        }

        .btn-close-item-modal {
            background: #e2e8f0;
            border: none;
            color: #0f172a;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            flex-shrink: 0;
        }

        .btn-close-item-modal:hover {
            background: #cbd5e1;
        }

        .item-modal-body {
            padding: 1.5rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1.35rem;
        }

        /* Bloco de Confirmação / Situação dentro do Modal */
        .modal-status-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.15rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .modal-status-box-title {
            font-size: 0.88rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.25rem;
        }

        .modal-status-box-sub {
            font-size: 0.8rem;
            color: #64748b;
        }

        .modal-status-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .status-select {
            padding: 0.55rem 0.9rem;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            background-color: #ffffff;
            color: #0f172a;
            font-size: 0.86rem;
            font-weight: 600;
            cursor: pointer;
            outline: none;
            min-width: 165px;
        }

        .btn-quick-confirm {
            padding: 0.55rem 1rem;
            background-color: #16a34a;
            color: #ffffff;
            border: none;
            border-radius: 9px;
            font-size: 0.84rem;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        .btn-quick-confirm:hover {
            background-color: #15803d;
        }

        .btn-quick-reject {
            padding: 0.55rem 0.9rem;
            background-color: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
            border-radius: 9px;
            font-size: 0.84rem;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-quick-reject:hover {
            background-color: #dc2626;
            color: #ffffff;
        }
        .btn-reopen-item {
            padding: 0.55rem 1.05rem;
            background-color: #0f172a;
            color: #ffffff;
            border: none;
            border-radius: 9px;
            font-size: 0.84rem;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }
        .btn-reopen-item:hover {
            background-color: #334155;
        }

        .no-docs-warning {
            font-size: 0.8rem;
            font-weight: 600;
            color: #b45309;
            background-color: #fef3c7;
            border: 1px solid #fde68a;
            padding: 0.45rem 0.8rem;
            border-radius: 8px;
        }

        .rejection-reason-alert {
            margin-top: 0.6rem;
            padding: 0.65rem 0.9rem;
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-left: 4px solid #dc2626;
            border-radius: 8px;
            color: #991b1b;
            font-size: 0.83rem;
            line-height: 1.45;
        }

        .rejection-reason-alert strong {
            color: #dc2626;
            display: inline-block;
            margin-right: 0.3rem;
        }

        .rejection-form-box {
            width: 100%;
            margin-top: 0.85rem;
            padding-top: 0.85rem;
            border-top: 1px dashed #cbd5e1;
            display: none;
            flex-direction: column;
            gap: 0.6rem;
        }

        .rejection-form-box.open {
            display: flex;
        }

        .rejection-form-box label {
            font-size: 0.82rem;
            font-weight: 700;
            color: #b91c1c;
        }

        .rejection-textarea {
            width: 100%;
            min-height: 78px;
            padding: 0.65rem 0.85rem;
            border: 1px solid #fca5a5;
            border-radius: 8px;
            font-size: 0.86rem;
            font-family: inherit;
            color: #0f172a;
            background-color: #ffffff;
            resize: vertical;
            outline: none;
        }

        .rejection-textarea:focus {
            border-color: #dc2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);
        }

        .rejection-form-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        .btn-cancel-reject {
            padding: 0.48rem 0.85rem;
            border-radius: 7px;
            border: 1px solid #cbd5e1;
            background-color: #ffffff;
            color: #475569;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-confirm-reject {
            padding: 0.48rem 1rem;
            border-radius: 7px;
            border: none;
            background-color: #dc2626;
            color: #ffffff;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-confirm-reject:hover {
            background-color: #b91c1c;
        }

        /* Caixa de Envio Final do Usuário (Após Anexar) */
        .user-submit-box {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            padding: 1.15rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .user-submit-box strong {
            display: block;
            color: #15803d;
            font-size: 0.92rem;
            margin-bottom: 0.2rem;
        }

        .user-submit-box span {
            color: #166534;
            font-size: 0.82rem;
        }

        .btn-submit-final {
            padding: 0.65rem 1.35rem;
            background-color: #16a34a;
            color: #ffffff;
            border: none;
            border-radius: 9px;
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        .btn-submit-final:hover {
            background-color: #15803d;
        }

        /* Upload e Lista de Anexos dentro do Modal */
        .modal-section-title {
            font-size: 0.92rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.65rem;
        }

        .upload-bar {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
            background-color: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            padding: 0.75rem 1rem;
        }

        .file-input {
            flex: 1;
            min-width: 200px;
            font-size: 0.84rem;
            color: #334155;
        }

        .file-input::file-selector-button {
            padding: 0.45rem 0.85rem;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            background-color: #ffffff;
            color: #0f172a;
            font-weight: 600;
            font-size: 0.78rem;
            cursor: pointer;
            margin-right: 0.65rem;
        }

        .allowed-formats {
            font-size: 0.75rem;
            color: #64748b;
        }

        .btn-upload {
            padding: 0.52rem 1.05rem;
            background-color: #dd4814;
            color: #ffffff;
            border: none;
            border-radius: 7px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
        }

        .btn-upload:hover {
            background-color: #c23c0f;
        }

        .selected-preview-strip {
            display: none;
            margin-top: 0.65rem;
            padding: 0.65rem 0.85rem;
            background-color: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 8px;
            gap: 0.55rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .selected-preview-strip.visible {
            display: flex;
        }

        .selected-preview-item {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            background: #ffffff;
            border: 1px solid #fdba74;
            border-radius: 7px;
            padding: 0.32rem 0.6rem;
            font-size: 0.78rem;
            color: #0f172a;
        }

        .selected-preview-thumb {
            width: 32px;
            height: 32px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
            cursor: pointer;
        }

        .attachments-list {
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
        }

        .attachment-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.65rem 0.9rem;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            flex-wrap: wrap;
        }

        .att-info {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            min-width: 0;
        }

        .att-thumb {
            width: 44px;
            height: 44px;
            border-radius: 6px;
            object-fit: cover;
            border: 1px solid #cbd5e1;
            cursor: pointer;
            flex-shrink: 0;
        }

        .ext-badge {
            font-size: 0.68rem;
            font-weight: 800;
            text-transform: uppercase;
            padding: 0.22rem 0.48rem;
            border-radius: 5px;
            background-color: #0f172a;
            color: #ffffff;
            flex-shrink: 0;
        }

        .ext-badge.ext-pdf {
            background-color: #dc2626;
        }

        .ext-badge.ext-zip {
            background-color: #7c3aed;
        }

        .ext-badge.ext-jpg,
        .ext-badge.ext-jpeg,
        .ext-badge.ext-png {
            background-color: #0284c7;
        }

        .att-name {
            font-size: 0.86rem;
            font-weight: 600;
            color: #0f172a;
            word-break: break-all;
        }

        .att-meta {
            font-size: 0.75rem;
            color: #64748b;
        }

        .att-actions {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            flex-wrap: wrap;
        }

        .btn-preview {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.36rem 0.72rem;
            background-color: #0284c7;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            font-size: 0.76rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-preview.pdf {
            background-color: #dc2626;
        }

        .btn-download {
            padding: 0.36rem 0.72rem;
            background-color: #e2e8f0;
            color: #0f172a;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.76rem;
            font-weight: 600;
        }

        .btn-remove-att {
            padding: 0.36rem 0.72rem;
            background-color: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
            border-radius: 6px;
            font-size: 0.76rem;
            font-weight: 600;
            cursor: pointer;
        }

        .empty-att-box {
            padding: 1.5rem;
            text-align: center;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            color: #64748b;
            font-size: 0.86rem;
        }

        .empty-permissions {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 2.5rem;
            text-align: center;
            color: #64748b;
        }

        /* Modal de Pré-visualização em Tela Cheia (Imagem / PDF) */
        .preview-modal-backdrop {
            position: fixed;
            inset: 0;
            background-color: rgba(15, 23, 42, 0.86);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 1.5rem;
        }

        .preview-modal-backdrop.open {
            display: flex;
        }

        .preview-modal-dialog {
            background-color: #ffffff;
            border-radius: 12px;
            width: 100%;
            max-width: 1040px;
            height: 88vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .preview-modal-header {
            padding: 0.9rem 1.25rem;
            background-color: #0f172a;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .preview-modal-title {
            font-size: 0.95rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .preview-modal-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-modal-newtab {
            padding: 0.38rem 0.75rem;
            border-radius: 6px;
            background-color: #334155;
            color: #ffffff;
            text-decoration: none;
            font-size: 0.78rem;
            font-weight: 600;
        }

        .btn-modal-close {
            padding: 0.38rem 0.75rem;
            border-radius: 6px;
            background-color: #dc2626;
            color: #ffffff;
            border: none;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
        }

        .preview-modal-body {
            flex: 1;
            background-color: #1e293b;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: auto;
        }

        .preview-modal-body img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .preview-modal-body iframe {
            width: 100%;
            height: 100%;
            border: none;
            background-color: #ffffff;
        }

        @media (max-width: 768px) {
            .app-layout {
                flex-direction: column;
            }

            .container {
                padding: 1.25rem 1rem;
            }
        }
    </style>
</head>

<body>
    <?php
    $isAdmin       = (($userRole ?? 'user') === 'admin');
    $accId         = (int) $accountability['id'];
    $currentUserId = (int) session()->get('user_id');
    $visibleCount  = count($visibleItems ?? []);

    $statusLabelsMap = [
        'pendente'      => 'Pendente',
        'enviado'       => 'Enviado',
        'confirmado'    => 'Confirmado',
        'rejeitado'     => 'Rejeitado',
        'prazo_vencido' => 'Prazo vencido',
    ];

    $statusIconsHtml = [
        'pendente'      => '◎',
        'enviado'       => '✓',
        'confirmado'    => '✓',
        'rejeitado'     => '✕',
        'prazo_vencido' => '!',
    ];
    ?>

    <div class="app-layout">
        <?= view('partials/sidebar_admin', [
            'userName'  => $userName ?? null,
            'userEmail' => $userEmail ?? null,
            'userRole'  => $userRole ?? null,
        ]) ?>

        <div class="main-wrapper">
            <?= view('partials/navbar', [
                'pageTitle' => '',
                'userName'  => $userName ?? null,
                'userEmail' => $userEmail ?? null,
                'userRole'  => $userRole ?? null,
            ]) ?>

            <main class="container">
                <div class="page-header">
                    <div>
                        <h1><?= esc($accountability['title']) ?></h1>
                        <?php if (! empty($accountability['description'])): ?>
                            <p><?= esc($accountability['description']) ?></p>
                        <?php endif; ?>

                        <div class="meta-pills">
                            <span class="meta-pill">
                                Data Limite: <?= ! empty($accountability['deadline']) ? esc(date('d/m/Y', strtotime((string) $accountability['deadline']))) : 'Não definido' ?>
                            </span>
                        </div>
                    </div>

                    <div style="display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap;">
                        <?php if (($readyToSubmitCount ?? 0) > 0): ?>
                            <form action="<?= site_url('accountability/' . $accId . '/submit') ?>" method="post">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn-submit-item-row" style="padding: 0.6rem 1.2rem; font-size: 0.88rem;">
                                    ✓ Enviar Prestação de Contas
                                </button>
                            </form>
                        <?php endif; ?>
                        <?php if ($isAdmin): ?>
                            <a href="<?= site_url('users') ?>" class="btn-back">Gerenciar Permissões de Usuários</a>
                        <?php endif; ?>
                        <a href="<?= site_url('accountability') ?>" class="btn-back">← Voltar</a>
                    </div>
                </div>

                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success">
                        <?= esc(session()->getFlashdata('success')) ?>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-error">
                        <?= esc(session()->getFlashdata('error')) ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($visibleItems)): ?>
                    <div class="empty-permissions">
                        <?php if (($allowedItemsCount ?? 0) > 0): ?>
                            <h2 style="color: #15803d; font-size: 1.2rem; margin-bottom: 0.5rem;">✓ Todos os seus documentos liberados já foram enviados!</h2>
                            <p>Se o Administrador rejeitar algum documento para correção, ele voltará a aparecer automaticamente nesta lista.</p>
                        <?php else: ?>
                            <h2 style="color: #0f172a; font-size: 1.2rem; margin-bottom: 0.5rem;">Nenhum campo de anexo liberado para o seu usuário</h2>
                            <p>Solicite ao Administrador a liberação dos itens (1 a 24) em <strong>Usuários e Permissões</strong> para anexar documentos nesta prestação de contas.</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- Card Superior: Situação dos Documentos -->
                    <div class="situation-card">
                        <div class="situation-top">
                            <h2>Situação dos documentos</h2>
                        </div>

                        <div class="legend-row">
                            <span class="legend-item">
                                <span class="status-icon status-icon-pendente">◎</span> Pendente
                            </span>
                            <span class="legend-item">
                                <span class="status-icon status-icon-enviado">✓</span> Enviado
                            </span>
                            <span class="legend-item">
                                <span class="status-icon status-icon-confirmado">✓</span> Confirmado
                            </span>
                            <span class="legend-item">
                                <span class="status-icon status-icon-rejeitado">✕</span> Rejeitado
                            </span>
                            <span class="legend-item">
                                <span class="status-icon status-icon-prazo_vencido">!</span> Prazo vencido
                            </span>
                        </div>

                        <div class="search-box-wrap">
                            <svg class="search-box-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            <input
                                type="text"
                                id="docSearchInput"
                                class="search-box-input"
                                placeholder="Pesquisar documento...">
                        </div>

                        <div class="filter-tabs" id="filterTabs">
                            <button type="button" class="filter-tab-btn active" data-filter="all">
                                Todos (<span id="count-all"><?= esc((string) $visibleCount) ?></span>)
                            </button>
                            <button type="button" class="filter-tab-btn" data-filter="pendente">
                                Pendentes (<span id="count-pendente">0</span>)
                            </button>
                            <?php if ($isAdmin): ?>
                                <button type="button" class="filter-tab-btn" data-filter="enviado">
                                    Enviados (<span id="count-enviado">0</span>)
                                </button>
                                <button type="button" class="filter-tab-btn" data-filter="confirmado">
                                    Confirmados (<span id="count-confirmado">0</span>)
                                </button>
                            <?php endif; ?>
                            <button type="button" class="filter-tab-btn" data-filter="rejeitado">
                                Rejeitados (<span id="count-rejeitado">0</span>)
                            </button>
                            <button type="button" class="filter-tab-btn" data-filter="prazo_vencido">
                                Prazo vencido (<span id="count-prazo_vencido">0</span>)
                            </button>
                        </div>
                    </div>
                    <div class="checklist-card">
                        <div class="checklist-header">
                            <div>
                                <h2>Checklist documental</h2>
                                <p><?= $isAdmin ? 'Clique em um item para abrir o modal, ver/pré-visualizar os anexos e confirmar ou rejeitar' : 'Anexe os documentos desejados em cada item e clique em Enviar para concluir' ?></p>
                            </div>
                            <span class="checklist-total-count"><?= esc((string) $visibleCount) ?> itens</span>
                        </div>

                        <div id="checklistContainer">
                            <?php foreach ($visibleItems as $num => $label): ?>
                                <?php
                                $itemAtts   = $attachmentsByItem[$num] ?? [];
                                $itemCount  = count($itemAtts);
                                $itemStatus = $itemStatuses[$num] ?? 'pendente';
                                $itemReason = $itemReasons[$num] ?? '';
                                $numPad     = str_pad((string) $num, 2, '0', STR_PAD_LEFT);
                                $cleanLabel = rtrim($label, ';.');
                                $canSubmit  = ($itemCount > 0 && in_array($itemStatus, ['pendente', 'rejeitado', 'prazo_vencido'], true));
                                ?>
                                <div
                                    class="checklist-row js-checklist-row"
                                    id="item-<?= esc((string) $num) ?>"
                                    data-status="<?= esc($itemStatus) ?>"
                                    data-item="<?= esc((string) $num) ?>"
                                    data-has-files="<?= $itemCount > 0 ? '1' : '0' ?>">
                                    <div class="checklist-row-main">
                                        <div
                                            class="checklist-left js-open-item-modal"
                                            data-modal-target="modal-item-<?= esc((string) $num) ?>">
                                            <span
                                                class="status-icon lg status-icon-<?= esc($itemStatus) ?>"
                                                id="icon-<?= esc((string) $num) ?>"><?= esc($statusIconsHtml[$itemStatus] ?? '◎') ?></span>

                                            <span class="checklist-num"><?= esc($numPad) ?>.</span>

                                            <div class="checklist-content">
                                                <div class="checklist-title"><?= esc($cleanLabel) ?></div>

                                                <div class="checklist-badges-row">
                                                    <span
                                                        class="status-pill status-pill-<?= esc($itemStatus) ?>"
                                                        id="pill-<?= esc((string) $num) ?>"><?= esc($statusLabelsMap[$itemStatus] ?? 'Pendente') ?></span>

                                                    <span class="att-count-badge <?= $itemCount > 0 ? 'has-files' : '' ?>">
                                                        📎 <?= $itemCount > 0 ? $itemCount . ' anexo(s)' : 'Sem anexos' ?>
                                                    </span>
                                                </div>

                                                <div
                                                    class="rejection-reason-alert"
                                                    id="row-reason-<?= esc((string) $num) ?>"
                                                    style="<?= ($itemStatus === 'rejeitado' && $itemReason !== '') ? 'display: block;' : 'display: none;' ?>">
                                                    <strong>⚠️ Motivo da rejeição:</strong>
                                                    <span id="row-reason-text-<?= esc((string) $num) ?>"><?= esc($itemReason) ?></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="checklist-right">
                                            <button
                                                type="button"
                                                class="btn-clip-icon <?= $itemCount > 0 ? 'has-files' : '' ?> js-open-item-modal"
                                                data-modal-target="modal-item-<?= esc((string) $num) ?>"
                                                title="<?= $itemCount > 0 ? $itemCount . ' anexo(s) — Clique para abrir e avaliar os anexos' : 'Sem anexos — Clique para abrir' ?>">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                                                </svg>
                                                <?php if ($itemCount > 0): ?>
                                                    <span><?= esc((string) $itemCount) ?></span>
                                                <?php endif; ?>
                                            </button>

                                            <?php if (! $isAdmin && $canSubmit): ?>
                                                <form action="<?= site_url('accountability/' . $accId . '/item/' . $num . '/submit') ?>" method="post">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn-submit-item-row" title="Confirmar e enviar este documento">
                                                        ✓ Confirmar
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Modais Individuais de Cada Item -->
                    <?php foreach ($visibleItems as $num => $label): ?>
                        <?php
                        $itemAtts   = $attachmentsByItem[$num] ?? [];
                        $itemCount  = count($itemAtts);
                        $itemStatus = $itemStatuses[$num] ?? 'pendente';
                        $itemReason = $itemReasons[$num] ?? '';
                        $numPad     = str_pad((string) $num, 2, '0', STR_PAD_LEFT);
                        $cleanLabel = rtrim($label, ';.');
                        $hasFiles   = ($itemCount > 0);
                        $canSubmit  = ($hasFiles && in_array($itemStatus, ['pendente', 'rejeitado', 'prazo_vencido'], true));
                        ?>
                        <div class="item-modal-backdrop" id="modal-item-<?= esc((string) $num) ?>">
                            <div class="item-modal-dialog">
                                <div class="item-modal-header">
                                    <div class="item-modal-header-left">
                                        <span class="item-modal-num">Item <?= esc($numPad) ?></span>
                                        <div>
                                            <div class="item-modal-title"><?= esc($cleanLabel) ?></div>
                                            <div style="margin-top: 0.35rem;">
                                                <span
                                                    class="status-pill status-pill-<?= esc($itemStatus) ?>"
                                                    id="modal-pill-<?= esc((string) $num) ?>"><?= esc($statusLabelsMap[$itemStatus] ?? 'Pendente') ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn-close-item-modal js-close-item-modal" title="Fechar">✕</button>
                                </div>

                                <div class="item-modal-body">
                                    <div
                                        class="rejection-reason-alert"
                                        id="modal-reason-<?= esc((string) $num) ?>"
                                        style="margin-top: 0; <?= ($itemStatus === 'rejeitado' && $itemReason !== '') ? 'display: block;' : 'display: none;' ?>">
                                        <strong>⚠️ Motivo da rejeição pelo Administrador:</strong>
                                        <span id="modal-reason-text-<?= esc((string) $num) ?>"><?= esc($itemReason) ?></span>
                                    </div>

                                    <?php if ($isAdmin): ?>
                                        <div class="modal-status-box">
                                            <div>
                                                <div class="modal-status-box-title">Avaliação do Administrador</div>
                                                <div class="modal-status-box-sub">
                                                    <?= $hasFiles
                                                        ? 'Confira os documentos anexados abaixo. Se rejeitar, o item voltará para a lista de pendências do usuário.'
                                                        : 'Este item ainda não contém documentos anexados.' ?>
                                                </div>
                                            </div>

                                            <div class="modal-status-controls">
                                                <?php if ($hasFiles): ?>
                                                    <div
                                                        id="eval-confirmed-controls-<?= esc((string) $num) ?>"
                                                        style="display: <?= $itemStatus === 'confirmado' ? 'flex' : 'none' ?>; align-items: center; gap: 0.65rem; flex-wrap: wrap;"
                                                    >
                                                        <span style="font-size: 0.82rem; font-weight: 700; color: #15803d;">
                                                            ✓ Documento confirmado (bloqueado para edição)
                                                        </span>
                                                        <button
                                                            type="button"
                                                            class="btn-reopen-item js-quick-status-btn"
                                                            data-item="<?= esc((string) $num) ?>"
                                                            data-status="enviado"
                                                        >
                                                            ↺ Reabrir para Alterar
                                                        </button>
                                                    </div>

                                                    <div
                                                        id="eval-open-controls-<?= esc((string) $num) ?>"
                                                        style="display: <?= $itemStatus === 'confirmado' ? 'none' : 'flex' ?>; align-items: center; gap: 0.5rem; flex-wrap: wrap;"
                                                    >
                                                        <button
                                                            type="button"
                                                            class="btn-quick-confirm js-quick-status-btn"
                                                            data-item="<?= esc((string) $num) ?>"
                                                            data-status="confirmado">
                                                            ✓ Confirmar
                                                        </button>
                                                        <button
                                                            type="button"
                                                            class="btn-quick-reject js-open-reject-form"
                                                            data-item="<?= esc((string) $num) ?>">
                                                            ✕ Rejeitar
                                                        </button>
                                                        <select
                                                            class="status-select js-status-select"
                                                            id="select-status-<?= esc((string) $num) ?>"
                                                            data-item="<?= esc((string) $num) ?>"
                                                            data-url="<?= site_url('accountability/' . $accId . '/item/' . $num . '/status') ?>">
                                                            <?php foreach ($statusLabelsMap as $stKey => $stLabel): ?>
                                                                <option value="<?= esc($stKey) ?>" <?= $itemStatus === $stKey ? 'selected' : '' ?>>
                                                                    <?= esc($stLabel) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="no-docs-warning">
                                                        Aguardando anexos para liberar Confirmação / Rejeição
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                            <?php if ($hasFiles): ?>
                                                <div class="rejection-form-box" id="reject-box-<?= esc((string) $num) ?>">
                                                    <label for="reject-reason-<?= esc((string) $num) ?>">
                                                        Informe o motivo da rejeição para orientar o usuário sobre o que precisa ser corrigido:
                                                    </label>
                                                    <textarea
                                                        id="reject-reason-<?= esc((string) $num) ?>"
                                                        class="rejection-textarea"
                                                        placeholder="Ex: A nota fiscal está ilegível ou faltou o comprovante assinado..."><?= esc($itemReason) ?></textarea>
                                                    <div class="rejection-form-actions">
                                                        <button
                                                            type="button"
                                                            class="btn-cancel-reject js-cancel-reject"
                                                            data-item="<?= esc((string) $num) ?>">
                                                            Cancelar
                                                        </button>
                                                        <button
                                                            type="button"
                                                            class="btn-confirm-reject js-confirm-reject"
                                                            data-item="<?= esc((string) $num) ?>">
                                                            Confirmar Rejeição e Devolver ao Usuário
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <div>
                                        <div class="modal-section-title">Arquivos Anexados (<?= esc((string) $itemCount) ?>)</div>

                                        <?php if (empty($itemAtts)): ?>
                                            <div class="empty-att-box">
                                                Nenhum arquivo anexado neste item até o momento.
                                            </div>
                                        <?php else: ?>
                                            <div class="attachments-list">
                                                <?php foreach ($itemAtts as $att): ?>
                                                    <?php
                                                    $attId      = (int) $att['id'];
                                                    $ext        = strtolower((string) ($att['file_ext'] ?? ''));
                                                    $kb         = round(((int) ($att['file_size'] ?? 0)) / 1024, 1);
                                                    $canDel     = $isAdmin || ((int) $att['user_id'] === $currentUserId);
                                                    $isImage    = in_array($ext, ['jpg', 'jpeg', 'png'], true);
                                                    $isPdf      = ($ext === 'pdf');
                                                    $previewUrl = site_url('accountability/attachment/' . $attId . '/preview');
                                                    ?>
                                                    <div class="attachment-row">
                                                        <div class="att-info">
                                                            <?php if ($isImage): ?>
                                                                <img
                                                                    src="<?= $previewUrl ?>"
                                                                    alt="<?= esc($att['original_name']) ?>"
                                                                    class="att-thumb js-open-preview"
                                                                    data-url="<?= $previewUrl ?>"
                                                                    data-type="image"
                                                                    data-title="<?= esc($att['original_name']) ?>"
                                                                    title="Pré-visualizar">
                                                            <?php endif; ?>

                                                            <span class="ext-badge ext-<?= esc($ext) ?>"><?= esc($ext ?: 'ARQ') ?></span>

                                                            <div>
                                                                <div class="att-name"><?= esc($att['original_name']) ?></div>
                                                                <div class="att-meta">
                                                                    <?= esc((string) $kb) ?> KB • Enviado por <strong><?= esc($att['uploader_name'] ?? 'Usuário') ?></strong>
                                                                    <?php if (! empty($att['created_at'])): ?>
                                                                        em <?= esc(date('d/m/Y H:i', strtotime((string) $att['created_at']))) ?>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="att-actions">
                                                            <?php if ($isImage): ?>
                                                                <button
                                                                    type="button"
                                                                    class="btn-preview js-open-preview"
                                                                    data-url="<?= $previewUrl ?>"
                                                                    data-type="image"
                                                                    data-title="<?= esc($att['original_name']) ?>">
                                                                    Pré-visualizar
                                                                </button>
                                                            <?php elseif ($isPdf): ?>
                                                                <button
                                                                    type="button"
                                                                    class="btn-preview pdf js-open-preview"
                                                                    data-url="<?= $previewUrl ?>"
                                                                    data-type="pdf"
                                                                    data-title="<?= esc($att['original_name']) ?>">
                                                                    Pré-visualizar
                                                                </button>
                                                            <?php endif; ?>

                                                            <a href="<?= site_url('accountability/attachment/' . $attId . '/download') ?>" class="btn-download">
                                                                Baixar
                                                            </a>

                                                            <?php if ($canDel): ?>
                                                                <form
                                                                    class="js-del-att-form-<?= esc((string) $num) ?>"
                                                                    style="<?= $itemStatus === 'confirmado' ? 'display: none;' : '' ?>"
                                                                    action="<?= site_url('accountability/attachment/' . $attId . '/delete') ?>"
                                                                    method="post"
                                                                    onsubmit="return confirm('Remover o anexo <?= esc($att['original_name'], 'js') ?>?');">
                                                                    <?= csrf_field() ?>
                                                                    <button type="submit" class="btn-remove-att">Remover</button>
                                                                </form>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div id="upload-section-<?= esc((string) $num) ?>" style="<?= $itemStatus === 'confirmado' ? 'display: none;' : '' ?>">
                                        <div class="modal-section-title">Anexar Arquivos neste Item</div>
                                        <form
                                            action="<?= site_url('accountability/' . $accId . '/upload/' . $num) ?>"
                                            method="post"
                                            enctype="multipart/form-data">
                                            <?= csrf_field() ?>
                                            <div class="upload-bar">
                                                <input
                                                    type="file"
                                                    name="attachments[]"
                                                    class="file-input js-file-input"
                                                    data-preview-target="preview-strip-<?= esc((string) $num) ?>"
                                                    multiple
                                                    accept=".zip,.jpg,.jpeg,.png,.pdf"
                                                    required>
                                                <span class="allowed-formats">ZIP, JPG, JPEG, PNG, PDF (múltiplos)</span>
                                                <button type="submit" class="btn-upload">
                                                    Anexar Arquivo
                                                </button>
                                            </div>
                                            <div class="selected-preview-strip" id="preview-strip-<?= esc((string) $num) ?>"></div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Modal de Pré-visualização (Imagens e PDFs) -->
    <div class="preview-modal-backdrop" id="previewModal">
        <div class="preview-modal-dialog">
            <div class="preview-modal-header">
                <div class="preview-modal-title" id="previewModalTitle">Pré-visualização</div>
                <div class="preview-modal-actions">
                    <a href="#" target="_blank" rel="noopener" class="btn-modal-newtab" id="previewModalNewTab">
                        Abrir em nova guia ↗
                    </a>
                    <button type="button" class="btn-modal-close" id="previewModalClose">
                        Fechar ✕
                    </button>
                </div>
            </div>
            <div class="preview-modal-body" id="previewModalBody"></div>
        </div>
    </div>

    <script>
        (function() {
            const statusIcons = {
                pendente: '◎',
                enviado: '✓',
                confirmado: '✓',
                rejeitado: '✕',
                prazo_vencido: '!'
            };
            const statusLabels = {
                pendente: 'Pendente',
                enviado: 'Enviado',
                confirmado: 'Confirmado',
                rejeitado: 'Rejeitado',
                prazo_vencido: 'Prazo vencido'
            };

            let csrfTokenName = '<?= esc(csrf_token()) ?>';
            let csrfHash = '<?= esc(csrf_hash()) ?>';
            let currentFilter = 'all';

            const rows = Array.from(document.querySelectorAll('.js-checklist-row'));
            const searchInput = document.getElementById('docSearchInput');
            const filterBtns = Array.from(document.querySelectorAll('#filterTabs .filter-tab-btn'));

            function updateCountsAndFilters() {
                const counts = {
                    all: rows.length,
                    pendente: 0,
                    enviado: 0,
                    confirmado: 0,
                    rejeitado: 0,
                    prazo_vencido: 0
                };

                const searchTerm = (searchInput ? searchInput.value : '').toLowerCase().trim();

                rows.forEach(function(row) {
                    const st = row.getAttribute('data-status') || 'pendente';
                    if (counts[st] !== undefined) {
                        counts[st]++;
                    }

                    const matchesFilter = (currentFilter === 'all' || st === currentFilter);
                    const matchesSearch = !searchTerm || row.textContent.toLowerCase().includes(searchTerm);
                    row.style.display = (matchesFilter && matchesSearch) ? '' : 'none';
                });

                Object.keys(counts).forEach(function(key) {
                    const el = document.getElementById('count-' + key);
                    if (el) el.textContent = String(counts[key]);
                });
            }

            filterBtns.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    filterBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentFilter = this.getAttribute('data-filter') || 'all';
                    updateCountsAndFilters();
                });
            });

            if (searchInput) {
                searchInput.addEventListener('input', updateCountsAndFilters);
            }

            function applyStatusChange(itemNum, newStatus, url, rejectionReason) {
                const row = document.getElementById('item-' + itemNum);
                const icon = document.getElementById('icon-' + itemNum);
                const pill = document.getElementById('pill-' + itemNum);
                const modalPill = document.getElementById('modal-pill-' + itemNum);
                const select = document.getElementById('select-status-' + itemNum);
                const rejectBox = document.getElementById('reject-box-' + itemNum);
                const rowReasonBox = document.getElementById('row-reason-' + itemNum);
                const rowReasonText = document.getElementById('row-reason-text-' + itemNum);
                const modalReasonBox = document.getElementById('modal-reason-' + itemNum);
                const modalReasonText = document.getElementById('modal-reason-text-' + itemNum);

                if (!url && select) {
                    url = select.getAttribute('data-url');
                }
                if (!url) return;

                const formData = new FormData();
                formData.append('status', newStatus);
                if (rejectionReason !== undefined) {
                    formData.append('rejection_reason', rejectionReason);
                }
                formData.append(csrfTokenName, csrfHash);

                fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data && data.csrf_hash) {
                            csrfHash = data.csrf_hash;
                            document.querySelectorAll('input[name="' + csrfTokenName + '"]').forEach(inp => {
                                inp.value = csrfHash;
                            });
                        }
                        if (data && data.error) {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Erro!',
                                    text: data.error,
                                    confirmButtonColor: '#dd4814'
                                });
                            } else {
                                alert(data.error);
                            }
                            return;
                        }
                        if (select && select.value !== newStatus) {
                            select.value = newStatus;
                        }
                        if (row && icon && pill) {
                            row.setAttribute('data-status', newStatus);
                            icon.className = 'status-icon lg status-icon-' + newStatus;
                            icon.textContent = statusIcons[newStatus] || '◎';
                            pill.className = 'status-pill status-pill-' + newStatus;
                            pill.textContent = statusLabels[newStatus] || 'Pendente';
                        }
                        if (modalPill) {
                            modalPill.className = 'status-pill status-pill-' + newStatus;
                            modalPill.textContent = statusLabels[newStatus] || 'Pendente';
                        }
                        if (rejectBox) {
                            rejectBox.classList.remove('open');
                        }

                        const confirmedControls = document.getElementById('eval-confirmed-controls-' + itemNum);
                        const openControls = document.getElementById('eval-open-controls-' + itemNum);
                        const uploadSection = document.getElementById('upload-section-' + itemNum);
                        const delForms = document.querySelectorAll('.js-del-att-form-' + itemNum);

                        if (newStatus === 'confirmado') {
                            if (confirmedControls) confirmedControls.style.display = 'flex';
                            if (openControls) openControls.style.display = 'none';
                            if (uploadSection) uploadSection.style.display = 'none';
                            delForms.forEach(f => { f.style.display = 'none'; });
                        } else {
                            if (confirmedControls) confirmedControls.style.display = 'none';
                            if (openControls) openControls.style.display = 'flex';
                            if (uploadSection) uploadSection.style.display = '';
                            delForms.forEach(f => { f.style.display = ''; });
                        }

                        const savedReason = (data && data.rejection_reason) ? data.rejection_reason : (rejectionReason || '');
                        if (newStatus === 'rejeitado' && savedReason) {
                            if (rowReasonText) rowReasonText.textContent = savedReason;
                            if (rowReasonBox) rowReasonBox.style.display = 'block';
                            if (modalReasonText) modalReasonText.textContent = savedReason;
                            if (modalReasonBox) modalReasonBox.style.display = 'block';
                        } else {
                            if (rowReasonBox) rowReasonBox.style.display = 'none';
                            if (modalReasonBox) modalReasonBox.style.display = 'none';
                        }

                        updateCountsAndFilters();

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Situação Atualizada!',
                                text: 'O Item ' + itemNum + ' foi marcado como "' + (statusLabels[newStatus] || newStatus) + '".',
                                confirmButtonColor: '#dd4814',
                                timer: 2200,
                                timerProgressBar: true
                            });
                        }
                    })
                    .catch(() => {});
            }

            document.querySelectorAll('.js-status-select').forEach(function(select) {
                select.addEventListener('change', function() {
                    const itemNum = this.getAttribute('data-item');
                    if (this.value === 'rejeitado') {
                        const rejectBox = document.getElementById('reject-box-' + itemNum);
                        const textarea = document.getElementById('reject-reason-' + itemNum);
                        if (rejectBox) {
                            rejectBox.classList.add('open');
                            if (textarea) textarea.focus();
                        }
                        return;
                    }
                    applyStatusChange(
                        itemNum,
                        this.value,
                        this.getAttribute('data-url'),
                        ''
                    );
                });
            });

            document.querySelectorAll('.js-quick-status-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    applyStatusChange(
                        this.getAttribute('data-item'),
                        this.getAttribute('data-status'),
                        null,
                        ''
                    );
                });
            });

            document.querySelectorAll('.js-open-reject-form').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const itemNum = this.getAttribute('data-item');
                    const rejectBox = document.getElementById('reject-box-' + itemNum);
                    const textarea = document.getElementById('reject-reason-' + itemNum);
                    if (rejectBox) {
                        rejectBox.classList.add('open');
                        if (textarea) textarea.focus();
                    }
                });
            });

            document.querySelectorAll('.js-cancel-reject').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const itemNum = this.getAttribute('data-item');
                    const rejectBox = document.getElementById('reject-box-' + itemNum);
                    const row = document.getElementById('item-' + itemNum);
                    const select = document.getElementById('select-status-' + itemNum);
                    if (rejectBox) rejectBox.classList.remove('open');
                    if (select && row) {
                        select.value = row.getAttribute('data-status') || 'enviado';
                    }
                });
            });

            document.querySelectorAll('.js-confirm-reject').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const itemNum = this.getAttribute('data-item');
                    const textarea = document.getElementById('reject-reason-' + itemNum);
                    const reason = textarea ? textarea.value.trim() : '';
                    if (!reason) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Motivo Obrigatório',
                                text: 'Por favor, informe o motivo da rejeição para orientar o usuário.',
                                confirmButtonColor: '#dd4814'
                            });
                        } else {
                            alert('Por favor, informe o motivo da rejeição para orientar o usuário.');
                        }
                        if (textarea) textarea.focus();
                        return;
                    }
                    applyStatusChange(itemNum, 'rejeitado', null, reason);
                });
            });

            // Abertura e fechamento do Modal de cada Item do Checklist
            function openItemModal(modalId) {
                const target = document.getElementById(modalId);
                if (target) {
                    target.classList.add('open');
                }
            }

            function closeAllItemModals() {
                document.querySelectorAll('.item-modal-backdrop.open').forEach(function(m) {
                    m.classList.remove('open');
                });
            }

            document.addEventListener('click', function(e) {
                const openTrigger = e.target.closest('.js-open-item-modal');
                if (openTrigger) {
                    e.preventDefault();
                    const modalId = openTrigger.getAttribute('data-modal-target');
                    if (modalId) openItemModal(modalId);
                    return;
                }

                const closeTrigger = e.target.closest('.js-close-item-modal');
                if (closeTrigger) {
                    e.preventDefault();
                    closeAllItemModals();
                    return;
                }

                if (e.target.classList && e.target.classList.contains('item-modal-backdrop')) {
                    closeAllItemModals();
                }
            });

            if (window.location.hash && window.location.hash.startsWith('#modal-item-')) {
                const targetId = window.location.hash.substring(1);
                openItemModal(targetId);
            }

            updateCountsAndFilters();

            // Modal de Pré-visualização de Imagem e PDF
            const previewModal = document.getElementById('previewModal');
            const modalTitle = document.getElementById('previewModalTitle');
            const modalBody = document.getElementById('previewModalBody');
            const modalNewTab = document.getElementById('previewModalNewTab');
            const modalClose = document.getElementById('previewModalClose');

            function openPreview(url, type, title) {
                if (!previewModal || !modalBody) return;
                modalTitle.textContent = title || 'Pré-visualização';
                modalNewTab.href = url;
                modalBody.innerHTML = '';

                if (type === 'image') {
                    const img = document.createElement('img');
                    img.src = url;
                    img.alt = title || 'Imagem';
                    modalBody.appendChild(img);
                } else if (type === 'pdf') {
                    const iframe = document.createElement('iframe');
                    iframe.src = url;
                    iframe.title = title || 'PDF';
                    modalBody.appendChild(iframe);
                }

                previewModal.classList.add('open');
            }

            function closePreview() {
                if (!previewModal || !modalBody) return;
                previewModal.classList.remove('open');
                modalBody.innerHTML = '';
            }

            document.addEventListener('click', function(e) {
                const trigger = e.target.closest('.js-open-preview');
                if (trigger) {
                    e.preventDefault();
                    openPreview(
                        trigger.getAttribute('data-url'),
                        trigger.getAttribute('data-type'),
                        trigger.getAttribute('data-title')
                    );
                }
            });

            if (modalClose) modalClose.addEventListener('click', closePreview);
            if (previewModal) {
                previewModal.addEventListener('click', function(e) {
                    if (e.target === previewModal) closePreview();
                });
            }
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    if (previewModal && previewModal.classList.contains('open')) {
                        closePreview();
                    } else {
                        closeAllItemModals();
                    }
                }
            });

            // Pré-visualização instantânea antes de anexar
            document.querySelectorAll('.js-file-input').forEach(function(input) {
                input.addEventListener('change', function() {
                    const stripId = this.getAttribute('data-preview-target');
                    const strip = document.getElementById(stripId);
                    if (!strip) return;

                    strip.innerHTML = '';
                    const files = Array.from(this.files || []);
                    if (files.length === 0) {
                        strip.classList.remove('visible');
                        return;
                    }

                    strip.classList.add('visible');

                    files.forEach(function(file) {
                        const ext = (file.name.split('.').pop() || '').toLowerCase();
                        const item = document.createElement('div');
                        item.className = 'selected-preview-item';

                        const isImg = ['jpg', 'jpeg', 'png'].includes(ext);
                        const isPdf = (ext === 'pdf');

                        if (isImg || isPdf) {
                            const objUrl = URL.createObjectURL(file);

                            if (isImg) {
                                const thumb = document.createElement('img');
                                thumb.src = objUrl;
                                thumb.className = 'selected-preview-thumb js-open-preview';
                                thumb.setAttribute('data-url', objUrl);
                                thumb.setAttribute('data-type', 'image');
                                thumb.setAttribute('data-title', file.name);
                                item.appendChild(thumb);
                            }

                            const nameSpan = document.createElement('span');
                            nameSpan.textContent = file.name;
                            item.appendChild(nameSpan);

                            const previewBtn = document.createElement('button');
                            previewBtn.type = 'button';
                            previewBtn.className = 'btn-preview ' + (isPdf ? 'pdf ' : '') + 'js-open-preview';
                            previewBtn.setAttribute('data-url', objUrl);
                            previewBtn.setAttribute('data-type', isPdf ? 'pdf' : 'image');
                            previewBtn.setAttribute('data-title', file.name);
                            previewBtn.textContent = isPdf ? 'Pré-visualizar' : 'Pré-visualizar';
                            item.appendChild(previewBtn);
                        } else {
                            const nameSpan = document.createElement('span');
                            nameSpan.textContent = '📦 ' + file.name;
                            item.appendChild(nameSpan);
                        }

                        strip.appendChild(item);
                    });
                });
            });
        })();
    </script>
</body>

</html>