        (function () {
            const pads = {};

            document.querySelectorAll('.sig-pad').forEach(function (wrapper) {
                const canvas = wrapper.querySelector('canvas');
                const hint = wrapper.querySelector('.hint');
                const ctx = canvas.getContext('2d');
                const pad = { canvas: canvas, empty: true };
                let drawing = false;
                let last = null;

                function resize() {
                    const ratio = Math.max(window.devicePixelRatio || 1, 1);
                    const rect = canvas.getBoundingClientRect();
                    const snapshot = pad.empty ? null : canvas.toDataURL();

                    canvas.width = rect.width * ratio;
                    canvas.height = rect.height * ratio;
                    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
                    ctx.lineWidth = 2.2;
                    ctx.lineCap = 'round';
                    ctx.lineJoin = 'round';
                    ctx.strokeStyle = '#111827';

                    if (snapshot) {
                        const img = new Image();
                        img.onload = function () { ctx.drawImage(img, 0, 0, rect.width, rect.height); };
                        img.src = snapshot;
                    }
                }

                function point(e) {
                    const rect = canvas.getBoundingClientRect();
                    return { x: e.clientX - rect.left, y: e.clientY - rect.top };
                }

                canvas.addEventListener('pointerdown', function (e) {
                    e.preventDefault();
                    canvas.setPointerCapture(e.pointerId);
                    drawing = true;
                    last = point(e);
                    ctx.beginPath();
                    ctx.arc(last.x, last.y, 1, 0, Math.PI * 2);
                    ctx.fillStyle = '#111827';
                    ctx.fill();
                    pad.empty = false;
                    hint.style.display = 'none';
                });

                canvas.addEventListener('pointermove', function (e) {
                    if (!drawing) return;
                    const p = point(e);
                    ctx.beginPath();
                    ctx.moveTo(last.x, last.y);
                    ctx.lineTo(p.x, p.y);
                    ctx.stroke();
                    last = p;
                });

                ['pointerup', 'pointercancel', 'pointerleave'].forEach(function (type) {
                    canvas.addEventListener(type, function () { drawing = false; });
                });

                pad.clear = function () {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    pad.empty = true;
                    hint.style.display = '';
                };

                window.addEventListener('resize', resize);
                resize();
                pads[wrapper.dataset.target] = pad;
            });

            document.querySelectorAll('[data-clear]').forEach(function (button) {
                button.addEventListener('click', function () { pads[button.dataset.clear].clear(); });
            });

            // Vehicle damage diagram
            (function () {
                const diagram = document.querySelector('.diagram[data-editable="1"]');
                if (!diagram) return;

                const types = @js($damageTypes);
                const svg = diagram.querySelector('.vehicle-diagram');
                const layer = svg.querySelector('.damage-marks');
                const list = diagram.querySelector('.damage-list tbody');
                const input = diagram.querySelector('input.diagram-marks-input');
                const NS = 'http://www.w3.org/2000/svg';
                const noteLabel = @js('Detalle (opcional)');
                const removeLabel = @js('Quitar');
                const emptyLabel = @js('Sin daños marcados.');
                let marks = [];

                try { marks = JSON.parse(input.value) || []; } catch (e) { marks = []; }

                function svgEl(name, attrs, text) {
                    const el = document.createElementNS(NS, name);
                    Object.keys(attrs).forEach(function (key) { el.setAttribute(key, attrs[key]); });
                    if (text !== undefined) el.textContent = text;
                    return el;
                }

                function sync() { input.value = JSON.stringify(marks); }

                function render() {
                    layer.innerHTML = '';
                    list.innerHTML = '';

                    marks.forEach(function (mark, i) {
                        const type = types[mark.type];
                        const g = svgEl('g', { 'class': 'damage-mark', 'data-index': i });
                        g.appendChild(svgEl('circle', { cx: mark.x, cy: mark.y, r: 9, fill: type.color, stroke: '#fff', 'stroke-width': 1.5 }));
                        g.appendChild(svgEl('text', { x: mark.x, y: mark.y + 3.5, fill: '#fff', 'font-size': 10, 'font-weight': 700, 'text-anchor': 'middle' }, type.code));
                        g.appendChild(svgEl('text', { x: mark.x + 10, y: mark.y - 8, fill: '#111827', 'font-size': 10, 'font-weight': 700 }, i + 1));
                        layer.appendChild(g);

                        const row = document.createElement('tr');
                        row.innerHTML = '<td style="width: 32px"><strong></strong></td><td style="width: 190px"><span class="dot"></span> <span class="label"></span></td><td><input type="text" maxlength="150"></td><td style="width: 70px" class="no-print"><button type="button" class="btn btn-sm"></button></td>';
                        row.querySelector('strong').textContent = i + 1;
                        row.querySelector('.dot').style.setProperty('--c', type.color);
                        row.querySelector('.dot').textContent = type.code;
                        row.querySelector('.label').textContent = type.label;
                        const note = row.querySelector('input');
                        note.placeholder = noteLabel;
                        note.value = mark.note || '';
                        note.addEventListener('input', function () { mark.note = note.value; sync(); });
                        const remove = row.querySelector('button');
                        remove.textContent = removeLabel;
                        remove.addEventListener('click', function () { marks.splice(i, 1); render(); });
                        list.appendChild(row);
                    });

                    if (!marks.length) {
                        list.innerHTML = '<tr class="damage-empty"><td colspan="4"></td></tr>';
                        list.querySelector('td').textContent = emptyLabel;
                    }

                    sync();
                }

                svg.addEventListener('click', function (e) {
                    const markEl = e.target.closest('.damage-mark');
                    if (markEl) {
                        marks.splice(parseInt(markEl.dataset.index, 10), 1);
                        render();
                        return;
                    }

                    if (marks.length >= 60) return;

                    const pt = svg.createSVGPoint();
                    pt.x = e.clientX;
                    pt.y = e.clientY;
                    const p = pt.matrixTransform(svg.getScreenCTM().inverse());
                    const checked = diagram.querySelector('input[name="_damage_type"]:checked');

                    marks.push({
                        x: Math.round(p.x * 10) / 10,
                        y: Math.round(p.y * 10) / 10,
                        type: checked ? checked.value : Object.keys(types)[0],
                        note: '',
                    });
                    render();
                });

                render();
            })();

            // Live photo counter (kept photos + newly selected files)
            const photoCount = document.getElementById('photo-count');
            const photoInput = document.querySelector('input[name="{{ $photoInputName }}"]');
            const photoLabel = @js(':current de :count');

            function countPhotos() {
                const kept = document.querySelectorAll('input[name="existing_images[]"]:checked').length;
                return kept + (photoInput ? photoInput.files.length : 0);
            }

            function updatePhotoCount() {
                if (!photoCount) return;
                const total = countPhotos();
                const min = parseInt(photoCount.dataset.min, 10);
                photoCount.textContent = photoLabel.replace(':current', total).replace(':count', min);
                photoCount.className = total >= min ? 'ok' : 'missing';
            }

            document.querySelectorAll('input[name="existing_images[]"]').forEach(function (el) { el.addEventListener('change', updatePhotoCount); });
            if (photoInput) photoInput.addEventListener('change', updatePhotoCount);
            updatePhotoCount();

            const form = document.querySelector('form.document-form');
            let action = 'save';

            form.querySelectorAll('button[data-action]').forEach(function (button) {
                button.addEventListener('click', function () { action = button.dataset.action; });
            });

            form.addEventListener('submit', function (e) {
                document.getElementById('contract-action').value = action;

                Object.keys(pads).forEach(function (name) {
                    document.getElementById(name).value = pads[name].empty ? '' : pads[name].canvas.toDataURL('image/png');
                });

                if (action !== 'sign') return;

                if (photoCount && countPhotos() < parseInt(photoCount.dataset.min, 10)) {
                    e.preventDefault();
                    alert(@js(__('Sube al menos :count fotos del vehículo antes de firmar.', ['count' => $minPhotos])));
                    photoCount.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }

                if (Object.keys(pads).some(function (name) { return pads[name].empty; })) {
                    e.preventDefault();
                    alert(@js('Se requieren la firma del arrendatario y la de la arrendadora.'));
                }
            });
        })();
