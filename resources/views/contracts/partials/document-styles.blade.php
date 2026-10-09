        :root {
            --ink: #1f2937;
            --muted: #6b7280;
            --line: #d1d5db;
            --soft: #f3f4f6;
            --brand: #0f3d6e;
            --ok: #047857;
            --warn: #b45309;
            --danger: #b91c1c;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #e5e7eb;
            color: var(--ink);
            font-family: "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            font-size: 13px;
            line-height: 1.45;
        }

        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
            padding: 10px 16px;
            background: #111827;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border: 1px solid transparent;
            border-radius: 6px;
            background: #fff;
            color: var(--ink);
            font: inherit;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-primary { background: var(--brand); color: #fff; }
        .btn-success { background: var(--ok); color: #fff; }
        .btn-outline { background: transparent; border-color: #9ca3af; color: #fff; }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-sm { padding: 4px 10px; font-size: 12px; }

        .page {
            max-width: 900px;
            margin: 20px auto;
            padding: 36px 40px;
            background: #fff;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .12);
        }

        .alert {
            max-width: 900px;
            margin: 16px auto 0;
            padding: 10px 14px;
            border-radius: 6px;
        }

        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        .alert ul { margin: 0; padding-left: 18px; }

        header.contract-header {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding-bottom: 14px;
            border-bottom: 3px solid var(--brand);
        }

        .company-logo { max-height: 70px; max-width: 220px; }
        .company-block { font-size: 12px; color: var(--muted); }
        .company-block strong { color: var(--ink); font-size: 15px; }
        .contract-meta { text-align: right; font-size: 12px; }
        .contract-meta h1 { margin: 0 0 6px; color: var(--brand); font-size: 19px; text-transform: uppercase; }

        .badge {
            display: inline-block;
            margin-top: 4px;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }

        .badge-signed { background: #d1fae5; color: var(--ok); }
        .badge-unsigned { background: #fef3c7; color: var(--warn); }

        section { margin-top: 20px; break-inside: avoid; }

        h2 {
            margin: 0 0 8px;
            padding: 5px 10px;
            background: var(--brand);
            color: #fff;
            font-size: 12px;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 24px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0 18px; }

        .row {
            display: flex;
            gap: 8px;
            padding: 4px 0;
            border-bottom: 1px dotted var(--line);
        }

        .row .label { flex: 0 0 42%; color: var(--muted); }
        .row .val { flex: 1; font-weight: 600; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px 8px; border: 1px solid var(--line); text-align: left; }
        th { background: var(--soft); font-size: 11px; text-transform: uppercase; }
        .text-right { text-align: right; }

        label.field { display: block; margin-bottom: 10px; }
        label.field span { display: block; margin-bottom: 3px; color: var(--muted); font-size: 12px; }

        input[type=text], input[type=number], select, textarea {
            width: 100%;
            padding: 7px 9px;
            border: 1px solid var(--line);
            border-radius: 5px;
            font: inherit;
            color: var(--ink);
            background: #fff;
        }

        textarea { min-height: 64px; resize: vertical; }

        .checklist { display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px 14px; }
        .checklist label { display: flex; gap: 6px; align-items: center; }
        .checklist input { width: 16px; height: 16px; }

        .photos { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; }
        .photo { position: relative; }
        .photo img { width: 120px; height: 90px; object-fit: cover; border: 1px solid var(--line); border-radius: 4px; }
        .photo label { position: absolute; top: 2px; right: 2px; padding: 0 4px; background: rgba(255, 255, 255, .9); border-radius: 3px; font-size: 11px; }

        .photo-requirement { margin: 0 0 8px; padding: 8px 10px; border-left: 3px solid var(--warn); background: #fffbeb; font-size: 12px; }
        .photo-requirement strong.ok { color: var(--ok); }
        .photo-requirement strong.missing { color: var(--danger); }

        .terms { font-size: 12px; text-align: justify; }
        .terms p { margin: 0 0 8px; }
        .blank-line { display: inline-block; min-width: 140px; border-bottom: 1px solid var(--ink); }

        .declaration { margin: 0 0 12px; padding: 8px 10px; background: var(--soft); font-size: 12px; font-style: italic; }

        .signatures { display: grid; grid-template-columns: 1fr; gap: 28px; max-width: 460px; margin: 0 auto; }

        .sig-pad {
            position: relative;
            width: 100%;
            height: 170px;
            border: 2px dashed #9ca3af;
            border-radius: 6px;
            background: #fafafa;
            touch-action: none;
        }

        .sig-pad canvas { width: 100%; height: 100%; display: block; cursor: crosshair; }

        .sig-pad .hint {
            position: absolute;
            inset: auto 0 8px;
            color: #9ca3af;
            font-size: 11px;
            text-align: center;
            pointer-events: none;
        }

        .sig-image { display: block; width: 100%; height: 150px; object-fit: contain; }
        .sig-line { margin-top: 4px; padding-top: 4px; border-top: 1px solid var(--ink); text-align: center; font-size: 12px; }
        .sig-line strong { display: block; }
        .sig-actions { margin: 6px 0; text-align: right; }

        .diagram-help { margin: 0 0 8px; color: var(--muted); font-size: 12px; }
        .damage-legend { display: flex; flex-wrap: wrap; gap: 6px 14px; margin-bottom: 8px; font-size: 12px; }
        .damage-type { display: inline-flex; align-items: center; gap: 5px; cursor: pointer; }
        .damage-type input { margin: 0; }
        .dot {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: var(--c);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .vehicle-diagram { display: block; width: 100%; height: auto; border: 1px solid var(--line); border-radius: 6px; background: #fff; }
        .diagram[data-editable="1"] .vehicle-diagram { cursor: crosshair; }
        .diagram[data-editable="1"] .damage-mark { cursor: pointer; }
        .damage-list { margin-top: 8px; font-size: 12px; }
        .damage-list td { padding: 4px 6px; vertical-align: middle; }
        .damage-list input { padding: 4px 6px; }
        .damage-empty td { color: var(--muted); text-align: center; }
        .diagram-purpose { margin: 6px 0 0; color: var(--muted); font-size: 11px; font-style: italic; }

        .footer-note { margin-top: 24px; padding-top: 10px; border-top: 1px solid var(--line); color: var(--muted); font-size: 11px; text-align: center; }

        .form-actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: flex-end; margin-top: 24px; }

        @media (max-width: 720px) {
            .page { margin: 0; padding: 20px 16px; box-shadow: none; }
            header.contract-header { flex-direction: column; }
            .contract-meta { text-align: left; }
            .grid, .grid-3, .signatures { grid-template-columns: 1fr; }
            .checklist { grid-template-columns: 1fr 1fr; }
        }

        @media print {
            @page { size: letter; margin: 12mm; }
            body { background: #fff; font-size: 11.5px; }
            .toolbar, .alert, .no-print, .sig-actions, .sig-pad .hint { display: none !important; }
            .page { margin: 0; padding: 0; max-width: none; box-shadow: none; }
            h2 { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            input[type=text], input[type=number], select, textarea {
                padding: 0 0 2px;
                border: 0;
                border-bottom: 1px solid var(--line);
                border-radius: 0;
                appearance: none;
                -webkit-appearance: none;
            }
            textarea { min-height: 0; resize: none; overflow: hidden; }
            .sig-pad { border-style: solid; background: #fff; }
        }
