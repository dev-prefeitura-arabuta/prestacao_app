<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Único - <?= esc($accountability['title']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .toolbar {
            background-color: #0f172a;
            border-bottom: 1px solid #1e293b;
            padding: 0.85rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .toolbar-info h1 {
            font-size: 1rem;
            font-weight: 700;
            color: #ffffff;
        }

        .toolbar-info p {
            font-size: 0.78rem;
            color: #94a3b8;
            margin-top: 0.15rem;
        }

        .toolbar-actions {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.84rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid #334155;
            background-color: #1e293b;
            color: #f8fafc;
            transition: all 0.15s ease;
        }

        .btn:hover:not(:disabled) {
            background-color: #334155;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-primary {
            background-color: #dd4814;
            border-color: #dd4814;
            color: #ffffff;
        }

        .btn-primary:hover:not(:disabled) {
            background-color: #c23c0f;
        }

        .btn-success {
            background-color: #16a34a;
            border-color: #16a34a;
            color: #ffffff;
        }

        .btn-success:hover:not(:disabled) {
            background-color: #15803d;
        }

        .viewer-wrap {
            flex: 1;
            position: relative;
            background-color: #1e293b;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .status-box {
            background-color: #0f172a;
            border: 1px solid #334155;
            border-radius: 10px;
            padding: 2rem 2.25rem;
            max-width: 520px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.35);
        }

        .spinner {
            width: 42px;
            height: 42px;
            border: 4px solid #334155;
            border-top-color: #dd4814;
            border-radius: 50%;
            animation: spin 0.85s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .status-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.4rem;
        }

        .status-sub {
            font-size: 0.85rem;
            color: #94a3b8;
            line-height: 1.5;
        }

        .progress-bar-track {
            width: 100%;
            height: 8px;
            background-color: #1e293b;
            border-radius: 999px;
            overflow: hidden;
            margin-top: 1.15rem;
        }

        .progress-bar-fill {
            height: 100%;
            width: 0%;
            background-color: #dd4814;
            transition: width 0.2s ease;
        }

        .pdf-iframe {
            width: 100%;
            height: 100%;
            border: none;
            display: none;
            background-color: #ffffff;
        }

        .warning-list {
            margin-top: 1rem;
            text-align: left;
            font-size: 0.78rem;
            color: #fbbf24;
            background-color: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            padding: 0.65rem 0.85rem;
            border-radius: 6px;
            display: none;
        }
    </style>
</head>

<body>
    <?php
    $accId       = (int) $accountability['id'];
    $appName     = $generalSettings['app_name'] ?? 'Sistema Patrimonial';
    $companyName = $generalSettings['company_name'] ?? 'Município de Arabutã';
    $companyCnpj = trim((string) ($generalSettings['cnpj'] ?? ''));

    $jsAttachments = array_map(static function ($att) {
        return [
            'id'            => (int) $att['id'],
            'item_number'   => (int) $att['item_number'],
            'item_title'    => (string) ($att['item_title'] ?? ''),
            'original_name' => (string) ($att['original_name'] ?? ''),
            'file_ext'      => strtolower((string) ($att['file_ext'] ?? '')),
            'uploader_name' => (string) ($att['uploader_name'] ?? ''),
            'created_at'    => ! empty($att['created_at']) ? date('d/m/Y H:i', strtotime((string) $att['created_at'])) : '',
            'url'           => (string) ($att['preview_url'] ?? ''),
        ];
    }, $attachments ?? []);
    ?>

    <header class="toolbar">
        <div class="toolbar-info">
            <h1>PDF Único — <?= esc($accountability['title']) ?></h1>
            <p id="toolbarMeta">
                <?= esc((string) count($jsAttachments)) ?> arquivo(s) anexado(s) • <?= esc($companyName) ?>
            </p>
        </div>

        <div class="toolbar-actions">
            <button type="button" class="btn btn-primary" id="btnPrintPdf" disabled>
                Imprimir
            </button>
            <a href="#" class="btn btn-success" id="btnDownloadPdf" style="pointer-events: none; opacity: 0.5;">
                Baixar PDF
            </a>
            <button type="button" class="btn" id="btnCloseTab" title="Fechar" onclick="window.close(); setTimeout(function(){ window.location.href = '<?= site_url('accountability/' . $accId) ?>'; }, 150);">
                <i class="fa-solid fa-xmark"></i> Fechar
            </button>
        </div>
    </header>

    <main class="viewer-wrap">
        <?php if (empty($jsAttachments)): ?>
            <div class="status-box">
                <div class="status-title">Nenhum arquivo anexado</div>
                <p class="status-sub">
                    Esta prestação de contas ainda não possui documentos anexados para consolidar em um PDF único.
                </p>
                <div style="margin-top: 1.25rem;">
                    <a href="<?= site_url('accountability/' . $accId) ?>" class="btn btn-primary">
                        Voltar para anexar documentos
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="status-box" id="statusBox">
                <div class="spinner" id="spinner"></div>
                <div class="status-title" id="statusTitle">Montando PDF Único...</div>
                <div class="status-sub" id="statusSub">
                    Carregando e unindo todos os documentos anexados na prestação de contas.
                </div>
                <div class="progress-bar-track">
                    <div class="progress-bar-fill" id="progressFill"></div>
                </div>
                <div class="warning-list" id="warningList"></div>
            </div>

            <iframe id="pdfFrame" class="pdf-iframe" title="PDF Único Consolidado"></iframe>
        <?php endif; ?>
    </main>

    <?php if (! empty($jsAttachments)): ?>
        <script>
            (async function() {
                const attachments = <?= json_encode($jsAttachments, JSON_UNESCAPED_UNICODE) ?>;
                const meta = {
                    title: <?= json_encode((string) $accountability['title'], JSON_UNESCAPED_UNICODE) ?>,
                    description: <?= json_encode((string) ($accountability['description'] ?? ''), JSON_UNESCAPED_UNICODE) ?>,
                    deadline: <?= json_encode(! empty($accountability['deadline']) ? date('d/m/Y', strtotime((string) $accountability['deadline'])) : 'Não definido', JSON_UNESCAPED_UNICODE) ?>,
                    appName: <?= json_encode((string) $appName, JSON_UNESCAPED_UNICODE) ?>,
                    companyName: <?= json_encode((string) $companyName, JSON_UNESCAPED_UNICODE) ?>,
                    companyCnpj: <?= json_encode((string) $companyCnpj, JSON_UNESCAPED_UNICODE) ?>,
                    logoUrl: <?= json_encode(site_url('app-logo'), JSON_UNESCAPED_UNICODE) ?>,
                    coverArtUrl: <?= json_encode(! empty($generalSettings['pdf_cover_art']) ? site_url('settings/pdf-cover/art') . '?v=' . md5((string) $generalSettings['pdf_cover_art']) : '', JSON_UNESCAPED_UNICODE) ?>,
                    generatedAt: <?= json_encode(date('d/m/Y H:i'), JSON_UNESCAPED_UNICODE) ?>,
                    safeFilename: <?= json_encode('prestacao_' . $accId . '_consolidado.pdf', JSON_UNESCAPED_UNICODE) ?>,
                    coverEnabled: <?= ! empty($generalSettings['pdf_cover_enabled']) ? 'true' : 'false' ?>,
                    indexEnabled: <?= ! empty($generalSettings['pdf_cover_index_enabled']) ? 'true' : 'false' ?>,
                    showLogo: <?= ! empty($generalSettings['pdf_cover_show_logo']) ? 'true' : 'false' ?>,
                    showBorder: <?= ! empty($generalSettings['pdf_cover_show_border']) ? 'true' : 'false' ?>,
                    coverLabel: <?= json_encode((string) ($generalSettings['pdf_cover_label'] ?? 'PRESTAÇÃO DE CONTAS'), JSON_UNESCAPED_UNICODE) ?>,
                    coverSubtitle: <?= json_encode((string) ($generalSettings['pdf_cover_subtitle'] ?? 'DOSSIÊ DOCUMENTAL CONSOLIDADO'), JSON_UNESCAPED_UNICODE) ?>,
                    coverFooter: <?= json_encode((string) ($generalSettings['pdf_cover_footer'] ?? ''), JSON_UNESCAPED_UNICODE) ?>,
                    primaryColorHex: <?= json_encode((string) ($generalSettings['pdf_cover_primary_color'] ?? '#0f172a'), JSON_UNESCAPED_UNICODE) ?>,
                    accentColorHex: <?= json_encode((string) ($generalSettings['pdf_cover_accent_color'] ?? '#dd4814'), JSON_UNESCAPED_UNICODE) ?>
                };

                const statusBox = document.getElementById('statusBox');
                const statusTitle = document.getElementById('statusTitle');
                const statusSub = document.getElementById('statusSub');
                const progressFill = document.getElementById('progressFill');
                const warningList = document.getElementById('warningList');
                const pdfFrame = document.getElementById('pdfFrame');
                const btnPrint = document.getElementById('btnPrintPdf');
                const btnDownload = document.getElementById('btnDownloadPdf');
                const toolbarMeta = document.getElementById('toolbarMeta');

                function toWinAnsi(str) {
                    return String(str || '')
                        .replace(/[\u2013\u2014]/g, '-')
                        .replace(/[\u2018\u2019]/g, "'")
                        .replace(/[\u201C\u201D]/g, '"')
                        .replace(/[^\x20-\x7E\xA0-\xFF]/g, ' ');
                }

                function wrapText(text, maxChars) {
                    const words = toWinAnsi(text).split(/\s+/);
                    const lines = [];
                    let current = '';
                    for (const w of words) {
                        if ((current + ' ' + w).trim().length > maxChars) {
                            if (current) lines.push(current);
                            current = w;
                        } else {
                            current = (current + ' ' + w).trim();
                        }
                    }
                    if (current) lines.push(current);
                    return lines;
                }

                function drawItemHeaderBar(page, att, fontBold) {
                    const { rgb } = PDFLib;
                    const pageW = 595.28;
                    const pageH = 841.89;
                    const marginX = 30;
                    const maxW = pageW - marginX * 2;
                    const barH = 24;
                    const barY = pageH - 24 - barH;

                    const itemPad = String(att.item_number).padStart(2, '0');
                    const labelText = `[Item ${itemPad}] ${att.item_title}`;
                    let lblSize = 9.5;
                    while (lblSize > 6.5 && fontBold.widthOfTextAtSize(toWinAnsi(labelText), lblSize) > (maxW - 16)) {
                        lblSize -= 0.35;
                    }

                    page.drawRectangle({
                        x: marginX,
                        y: barY,
                        width: maxW,
                        height: barH,
                        color: rgb(0.95, 0.96, 0.98),
                        borderColor: rgb(0.85, 0.88, 0.92),
                        borderWidth: 0.7
                    });
                    page.drawText(toWinAnsi(labelText), {
                        x: marginX + 8,
                        y: barY + 8,
                        size: lblSize,
                        font: fontBold,
                        color: rgb(0.06, 0.09, 0.16)
                    });

                    return barY - 10;
                }

                async function drawAttachmentOnPage(mergedPdf, page, yTop, att, bytes, fontBold) {
                    const pageW = 595.28;
                    const marginX = 30;
                    const maxW = pageW - marginX * 2;
                    const contentTopY = drawItemHeaderBar(page, att, fontBold);
                    const availH = contentTopY - 24;

                    let img;
                    if (att.file_ext === 'png') {
                        img = await mergedPdf.embedPng(bytes);
                    } else {
                        img = await mergedPdf.embedJpg(bytes);
                    }

                    const scale = Math.min(maxW / img.width, availH / img.height);
                    const drawW = img.width * scale;
                    const drawH = img.height * scale;

                    page.drawImage(img, {
                        x: (pageW - drawW) / 2,
                        y: 24 + (availH - drawH) / 2,
                        width: drawW,
                        height: drawH
                    });
                }

                async function embedPdfWithItemHeader(mergedPdf, bytes, att, fontBold) {
                    const { PDFDocument } = PDFLib;
                    const pageW = 595.28;
                    const pageH = 841.89;
                    const marginX = 30;
                    const srcPdf = await PDFDocument.load(bytes, {
                        ignoreEncryption: true
                    });
                    const srcIndices = srcPdf.getPageIndices();

                    for (let i = 0; i < srcIndices.length; i++) {
                        const srcPage = srcPdf.getPage(srcIndices[i]);
                        const { width: sw, height: sh } = srcPage.getSize();
                        const embeddedPage = await mergedPdf.embedPage(srcPage);
                        const a4Page = mergedPdf.addPage([pageW, pageH]);

                        // Coloca o título [Item XX] acima da 1ª página do PDF anexado
                        const contentTopY = (i === 0)
                            ? drawItemHeaderBar(a4Page, att, fontBold)
                            : (pageH - 24);
                        const availW = pageW - marginX * 2;
                        const availH = contentTopY - 24;
                        const fitScale = Math.min(availW / sw, availH / sh);
                        const dw = sw * fitScale;
                        const dh = sh * fitScale;

                        a4Page.drawPage(embeddedPage, {
                            x: (pageW - dw) / 2,
                            y: 24 + (availH - dh) / 2,
                            width: dw,
                            height: dh
                        });
                    }
                }

                async function convertImageBytesViaCanvasToPng(bytes, mime) {
                    const blob = new Blob([bytes], {
                        type: mime
                    });
                    const bmpUrl = URL.createObjectURL(blob);
                    try {
                        const imgEl = await new Promise((resolve, reject) => {
                            const el = new Image();
                            el.onload = () => resolve(el);
                            el.onerror = reject;
                            el.src = bmpUrl;
                        });
                        const canvas = document.createElement('canvas');
                        canvas.width = imgEl.naturalWidth || imgEl.width;
                        canvas.height = imgEl.naturalHeight || imgEl.height;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(imgEl, 0, 0);
                        const dataUrl = canvas.toDataURL('image/png');
                        const res = await fetch(dataUrl);
                        return new Uint8Array(await res.arrayBuffer());
                    } finally {
                        URL.revokeObjectURL(bmpUrl);
                    }
                }

                try {
                    const {
                        PDFDocument,
                        StandardFonts,
                        rgb
                    } = PDFLib;
                    const mergedPdf = await PDFDocument.create();
                    const fontBold = await mergedPdf.embedFont(StandardFonts.HelveticaBold);
                    const fontRegular = await mergedPdf.embedFont(StandardFonts.Helvetica);

                    // 1. Página de Capa Institucional Centralizada (configurável em Configurações > Capa do PDF Unificado)
                    const pageW = 595.28;
                    const pageH = 841.89;

                    function hexToPdfRgb(hex, fallback) {
                        const m = /^#?([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i.exec(String(hex || '').trim());
                        if (!m) return fallback;
                        return rgb(parseInt(m[1], 16) / 255, parseInt(m[2], 16) / 255, parseInt(m[3], 16) / 255);
                    }

                    const primaryCol = hexToPdfRgb(meta.primaryColorHex, rgb(0.06, 0.09, 0.16));
                    const accentCol = hexToPdfRgb(meta.accentColorHex, rgb(0.87, 0.28, 0.08));

                    function drawCenteredText(page, text, yPos, size, font, color) {
                        const clean = toWinAnsi(text);
                        const w = font.widthOfTextAtSize(clean, size);
                        page.drawText(clean, {
                            x: (pageW - w) / 2,
                            y: yPos,
                            size,
                            font,
                            color
                        });
                    }

                    if (meta.coverEnabled) {
                        const coverPage = mergedPdf.addPage([pageW, pageH]);

                        // Arte de fundo da capa (preenche toda a página A4 por trás dos textos)
                        if (meta.coverArtUrl) {
                            try {
                                const artResp = await fetch(meta.coverArtUrl, {
                                    credentials: 'same-origin'
                                });
                                if (artResp.ok) {
                                    const artMime = (artResp.headers.get('content-type') || '').toLowerCase();
                                    const artBytes = new Uint8Array(await artResp.arrayBuffer());
                                    let artImg = null;
                                    if (artMime.includes('png')) {
                                        artImg = await mergedPdf.embedPng(artBytes);
                                    } else if (artMime.includes('jpeg') || artMime.includes('jpg')) {
                                        artImg = await mergedPdf.embedJpg(artBytes);
                                    } else {
                                        const pngBytes = await convertImageBytesViaCanvasToPng(artBytes, artMime || 'image/png');
                                        artImg = await mergedPdf.embedPng(pngBytes);
                                    }
                                    if (artImg) {
                                        coverPage.drawImage(artImg, {
                                            x: 0,
                                            y: 0,
                                            width: pageW,
                                            height: pageH
                                        });
                                    }
                                }
                            } catch (_) {
                                // Prossegue sem arte de fundo caso falhe
                            }
                        }

                        if (meta.showBorder) {
                            coverPage.drawRectangle({
                                x: 28,
                                y: 28,
                                width: pageW - 56,
                                height: pageH - 56,
                                borderColor: rgb(0.82, 0.85, 0.90),
                                borderWidth: 1.2
                            });
                        }

                        // Faixa superior de destaque
                        coverPage.drawRectangle({
                            x: 28,
                            y: pageH - 36,
                            width: pageW - 56,
                            height: 8,
                            color: primaryCol
                        });

                        let cy = 720;

                        // Logo centralizada no topo da capa (se habilitada)
                        if (meta.showLogo && meta.logoUrl) {
                            try {
                                const logoResp = await fetch(meta.logoUrl, {
                                    credentials: 'same-origin'
                                });
                                if (logoResp.ok) {
                                    const logoMime = (logoResp.headers.get('content-type') || '').toLowerCase();
                                    const logoBytes = new Uint8Array(await logoResp.arrayBuffer());
                                    let logoImg = null;
                                    if (logoMime.includes('png')) {
                                        logoImg = await mergedPdf.embedPng(logoBytes);
                                    } else if (logoMime.includes('jpeg') || logoMime.includes('jpg')) {
                                        logoImg = await mergedPdf.embedJpg(logoBytes);
                                    } else {
                                        const pngBytes = await convertImageBytesViaCanvasToPng(logoBytes, logoMime || 'image/png');
                                        logoImg = await mergedPdf.embedPng(pngBytes);
                                    }
                                    if (logoImg) {
                                        const maxLogoW = 175;
                                        const maxLogoH = 85;
                                        const lScale = Math.min(maxLogoW / logoImg.width, maxLogoH / logoImg.height, 1);
                                        const lW = logoImg.width * lScale;
                                        const lH = logoImg.height * lScale;
                                        coverPage.drawImage(logoImg, {
                                            x: (pageW - lW) / 2,
                                            y: cy - lH,
                                            width: lW,
                                            height: lH
                                        });
                                        cy -= (lH + 26);
                                    }
                                }
                            } catch (_) {
                                // Prossegue sem logo caso não carregue
                            }
                        }

                        // Cabeçalho institucional centralizado
                        drawCenteredText(coverPage, meta.companyName.toUpperCase(), cy, 13, fontBold, primaryCol);
                        cy -= 18;

                        if (meta.companyCnpj) {
                            drawCenteredText(coverPage, 'CNPJ: ' + meta.companyCnpj, cy, 9.5, fontRegular, rgb(0.39, 0.45, 0.55));
                            cy -= 16;
                        }

                        drawCenteredText(coverPage, meta.appName, cy, 10, fontBold, accentCol);

                        // Bloco central da capa (Título do Documento)
                        let midY = 475;

                        coverPage.drawLine({
                            start: {
                                x: 110,
                                y: midY + 34
                            },
                            end: {
                                x: pageW - 110,
                                y: midY + 34
                            },
                            thickness: 1.5,
                            color: accentCol
                        });

                        drawCenteredText(coverPage, meta.coverLabel || 'PRESTACAO DE CONTAS', midY + 10, 11, fontBold, rgb(0.39, 0.45, 0.55));
                        midY -= 20;

                        for (const line of wrapText(meta.title, 42)) {
                            drawCenteredText(coverPage, line, midY, 20, fontBold, primaryCol);
                            midY -= 26;
                        }

                        if (meta.description) {
                            midY -= 6;
                            for (const line of wrapText(meta.description, 68).slice(0, 5)) {
                                drawCenteredText(coverPage, line, midY, 10.5, fontRegular, rgb(0.28, 0.33, 0.41));
                                midY -= 15;
                            }
                        }

                        coverPage.drawLine({
                            start: {
                                x: 110,
                                y: midY - 12
                            },
                            end: {
                                x: pageW - 110,
                                y: midY - 12
                            },
                            thickness: 1,
                            color: rgb(0.82, 0.85, 0.90)
                        });

                        // Rodapé centralizado da capa
                        const footerText = meta.coverFooter || (meta.companyName + ' - ' + meta.appName);
                        drawCenteredText(coverPage, footerText, 52, 8.5, fontRegular, rgb(0.45, 0.5, 0.58));
                    }

                    // 2. Incorpora cada documento anexado diretamente após a capa (sem página de índice)
                    const warnings = [];
                    let processed = 0;

                    for (let idxAtt = 0; idxAtt < attachments.length; idxAtt++) {
                        const att = attachments[idxAtt];
                        processed++;
                        const pct = Math.round((processed / attachments.length) * 100);
                        progressFill.style.width = pct + '%';
                        statusSub.textContent = `Processando (${processed}/${attachments.length})...`;

                        try {
                            const resp = await fetch(att.url, {
                                credentials: 'same-origin'
                            });
                            if (!resp.ok) {
                                warnings.push(`Não foi possível baixar: ${att.original_name}`);
                                continue;
                            }
                            const bytes = new Uint8Array(await resp.arrayBuffer());

                            if (att.file_ext === 'pdf') {
                                await embedPdfWithItemHeader(mergedPdf, bytes, att, fontBold);
                            } else if (['jpg', 'jpeg', 'png'].includes(att.file_ext)) {
                                const targetPage = mergedPdf.addPage([pageW, pageH]);
                                const startY = pageH - 28;
                                try {
                                    await drawAttachmentOnPage(mergedPdf, targetPage, startY, att, bytes, fontBold);
                                } catch (imgErr) {
                                    const pngBytes = await convertImageBytesViaCanvasToPng(bytes, att.file_ext === 'png' ? 'image/png' : 'image/jpeg');
                                    await drawAttachmentOnPage(mergedPdf, targetPage, startY, { ...att, file_ext: 'png' }, pngBytes, fontBold);
                                }
                            } else if (att.file_ext === 'zip' && typeof JSZip !== 'undefined') {
                                const zip = await JSZip.loadAsync(bytes);
                                let addedFromZip = 0;
                                for (const [entryName, zipEntry] of Object.entries(zip.files)) {
                                    if (zipEntry.dir) continue;
                                    const innerExt = (entryName.split('.').pop() || '').toLowerCase();
                                    if (innerExt === 'pdf') {
                                        const innerBytes = await zipEntry.async('uint8array');
                                        await embedPdfWithItemHeader(mergedPdf, innerBytes, att, fontBold);
                                        addedFromZip++;
                                    } else if (['jpg', 'jpeg', 'png'].includes(innerExt)) {
                                        const innerBytes = await zipEntry.async('uint8array');
                                        const zPage = mergedPdf.addPage([pageW, pageH]);
                                        await drawAttachmentOnPage(mergedPdf, zPage, pageH - 28, { ...att, file_ext: innerExt }, innerBytes, fontBold);
                                        addedFromZip++;
                                    }
                                }
                                if (addedFromZip === 0) {
                                    warnings.push(`Arquivo ZIP sem PDFs ou imagens imprimíveis: ${att.original_name}`);
                                }
                            } else {
                                warnings.push(`Formato não suportado para mesclagem direta: ${att.original_name}`);
                            }
                        } catch (err) {
                            warnings.push(`Falha ao incorporar "${att.original_name}": ${err.message || 'arquivo inválido ou protegido'}`);
                        }
                    }

                    const finalPdfBytes = await mergedPdf.save();
                    const pdfBlob = new Blob([finalPdfBytes], {
                        type: 'application/pdf'
                    });
                    const pdfBlobUrl = URL.createObjectURL(pdfBlob);

                    const totalPages = mergedPdf.getPageCount();
                    toolbarMeta.textContent = `${attachments.length} anexo(s) consolidado(s) • ${totalPages} página(s) no PDF único • ${meta.companyName}`;

                    btnDownload.href = pdfBlobUrl;
                    btnDownload.download = meta.safeFilename;
                    btnDownload.style.pointerEvents = 'auto';
                    btnDownload.style.opacity = '1';

                    btnPrint.disabled = false;
                    btnPrint.addEventListener('click', function() {
                        if (pdfFrame && pdfFrame.contentWindow) {
                            pdfFrame.contentWindow.focus();
                            pdfFrame.contentWindow.print();
                        }
                    });

                    if (warnings.length > 0) {
                        warningList.style.display = 'block';
                        warningList.innerHTML = '<strong>Avisos:</strong><br>' + warnings.map(w => '• ' + w).join('<br>');
                    }

                    pdfFrame.src = pdfBlobUrl;
                    pdfFrame.style.display = 'block';
                    statusBox.style.display = 'none';
                } catch (fatalErr) {
                    document.getElementById('spinner').style.display = 'none';
                    statusTitle.textContent = 'Erro ao gerar o PDF Único';
                    statusSub.textContent = fatalErr.message || 'Ocorreu um erro inesperado ao mesclar os arquivos.';
                }
            })();
        </script>
    <?php endif; ?>
</body>

</html>