@extends('layout')
@section('title', 'Contrôle des entrées')
@section('content')
<main class="container py-5" style="max-width:640px">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h1 class="h2 mb-0">Contrôle des entrées</h1>
        <a class="btn btn-outline-dark btn-sm" href="{{ route('admin.dashboard') }}">← Tableau de bord</a>
    </div>

    @if(session('scan'))
        <div class="alert {{ session('scan')[0] === 'ok' ? 'alert-success' : 'alert-danger' }}" role="status">{{ session('scan')[1] }}</div>
    @endif

    {{-- ═══════════ Scanner caméra ═══════════ --}}
    <section class="admin-event-panel scan-panel mb-4">
        <div class="scan-view" id="scan-view" hidden>
            <video id="scan-video" playsinline muted></video>
            <div class="scan-frame" aria-hidden="true"></div>
        </div>
        <canvas id="scan-canvas" hidden></canvas>

        <div id="scan-result" class="scan-result" hidden role="status" aria-live="assertive">
            <div class="scan-result-title" id="scan-result-title"></div>
            <div class="scan-result-detail" id="scan-result-detail"></div>
        </div>

        <p class="small text-body-secondary mb-3" id="scan-status">
            Le scanner utilise la caméra arrière du téléphone. Le site doit être ouvert en <strong>https</strong>.
        </p>
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-gold btn-lg flex-grow-1" id="scan-start">Activer la caméra</button>
            <button type="button" class="btn btn-outline-dark btn-lg" id="scan-stop" hidden>Arrêter</button>
        </div>
    </section>

    {{-- ═══════════ Saisie manuelle / lecteur USB ═══════════ --}}
    <section class="admin-event-panel mb-4">
        <h2 class="h5 mb-3">Saisie manuelle</h2>
        <form method="post" action="{{ route('admin.check') }}" id="manual-form">
            @csrf
            <div class="form-floating mb-3">
                <input id="code" name="code" class="form-control" placeholder="Code" autocomplete="off" required>
                <label for="code">Code du billet (BFW-XXXXXXXXXX)</label>
            </div>
            <button class="btn btn-outline-dark w-100">Valider l'entrée</button>
        </form>
        <p class="text-body-secondary small mt-3 mb-0">Un lecteur QR USB se comporte comme un clavier : cliquez dans le champ, scannez, le code s'écrit et « Entrée » valide.</p>
    </section>

    {{-- ═══════════ Derniers scans ═══════════ --}}
    <section class="admin-event-panel">
        <h2 class="h5 mb-3">Derniers billets scannés</h2>
        <ul class="list-unstyled mb-0 scan-history" id="scan-history">
            @forelse($recentScans as $t)
                <li>
                    <span><strong>{{ $t->code }}</strong> · {{ $t->order->buyer_name }}<br>
                        <span class="small text-body-secondary">{{ $t->order->ticketType->event->title }} · {{ $t->order->ticketType->name }}</span></span>
                    <span class="small text-end">{{ $t->checked_in_at->format('d/m H:i:s') }}<br>
                        <span class="text-body-secondary">{{ $t->checkedInBy?->name ?? '—' }}</span></span>
                </li>
            @empty
                <li class="text-body-secondary" data-empty>Aucun billet scanné pour le moment.</li>
            @endforelse
        </ul>
    </section>
</main>

@push('scripts')
<script>
(function () {
    'use strict';
    var $ = function (id) { return document.getElementById(id); };
    var checkUrl = @json(route('admin.check'));
    var csrf = @json(csrf_token());
    var video = $('scan-video'), canvas = $('scan-canvas'), ctx = canvas.getContext('2d', { willReadFrequently: true });
    var state = { stream: null, detector: null, running: false, paused: false, lastCode: '', lastAt: 0 };

    function setStatus(text) { $('scan-status').textContent = text; }

    function loadJsQR() {
        if (window.jsQR) { return Promise.resolve(); }
        return new Promise(function (resolve, reject) {
            var s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js';
            s.onload = resolve;
            s.onerror = function () { reject(new Error('Module de lecture QR indisponible (connexion Internet ?).')); };
            document.head.appendChild(s);
        });
    }

    function prepareDecoder() {
        if ('BarcodeDetector' in window) {
            return BarcodeDetector.getSupportedFormats().then(function (formats) {
                if (formats.indexOf('qr_code') !== -1) { state.detector = new BarcodeDetector({ formats: ['qr_code'] }); return; }
                return loadJsQR();
            }).catch(loadJsQR);
        }
        return loadJsQR();
    }

    function decode() {
        if (state.detector) {
            return state.detector.detect(video).then(function (codes) { return codes.length ? codes[0].rawValue : null; });
        }
        var scale = Math.min(1, 720 / Math.max(video.videoWidth, video.videoHeight));
        canvas.width = Math.round(video.videoWidth * scale);
        canvas.height = Math.round(video.videoHeight * scale);
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        var img = ctx.getImageData(0, 0, canvas.width, canvas.height);
        var code = window.jsQR(img.data, img.width, img.height, { inversionAttempts: 'dontInvert' });
        return Promise.resolve(code ? code.data : null);
    }

    var lastTick = 0;
    function tick(now) {
        if (!state.running) { return; }
        if (!state.paused && now - lastTick > 200 && video.readyState >= 2) {
            lastTick = now;
            decode().then(function (value) { if (value && state.running && !state.paused) { onCode(value); } }).catch(function () {});
        }
        requestAnimationFrame(tick);
    }

    function start() {
        if (!window.isSecureContext) { setStatus('La caméra exige une connexion sécurisée (https). Utilisez la saisie manuelle.'); return; }
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) { setStatus('Caméra indisponible dans ce navigateur. Utilisez la saisie manuelle.'); return; }
        $('scan-start').disabled = true;
        setStatus('Démarrage de la caméra…');
        prepareDecoder().then(function () {
            return navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false });
        }).then(function (stream) {
            state.stream = stream;
            video.srcObject = stream;
            return video.play();
        }).then(function () {
            state.running = true;
            state.paused = false;
            $('scan-view').hidden = false;
            $('scan-start').hidden = true;
            $('scan-stop').hidden = false;
            setStatus('Placez le QR code du billet dans le cadre.');
            requestAnimationFrame(tick);
        }).catch(function (err) {
            setStatus(err && err.name === 'NotAllowedError'
                ? 'Accès à la caméra refusé : autorisez-le dans les réglages du navigateur.'
                : (err && err.message) || 'Impossible d\'ouvrir la caméra.');
        }).finally(function () { $('scan-start').disabled = false; });
    }

    function stop() {
        state.running = false;
        if (state.stream) { state.stream.getTracks().forEach(function (t) { t.stop(); }); state.stream = null; }
        video.srcObject = null;
        $('scan-view').hidden = true;
        $('scan-start').hidden = false;
        $('scan-stop').hidden = true;
        setStatus('Caméra arrêtée.');
    }

    function onCode(value) {
        // Le même QR reste souvent devant l'objectif : on ignore la relecture immédiate du même code.
        var now = Date.now();
        if (value === state.lastCode && now - state.lastAt < 4000) { return; }
        state.lastCode = value;
        state.lastAt = now;
        state.paused = true;
        setStatus('Vérification du billet…');
        send(value).then(function () {
            setTimeout(function () { state.paused = false; if (state.running) { setStatus('Prêt pour le billet suivant.'); } }, 1800);
        });
    }

    function send(code) {
        return fetch(checkUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ code: code })
        }).then(function (r) {
            if (r.status === 401 || r.status === 419) { return { ok: false, status: 'session', message: 'Session expirée : rechargez la page et reconnectez-vous.' }; }
            if (r.status === 429) { return { ok: false, status: 'throttle', message: 'Trop de scans en une minute : patientez quelques secondes.' }; }
            return r.json();
        }).catch(function () {
            return { ok: false, status: 'network', message: 'Pas de connexion au serveur : billet NON validé, réessayez.' };
        }).then(showResult);
    }

    function showResult(res) {
        var box = $('scan-result');
        box.hidden = false;
        box.className = 'scan-result ' + (res.ok ? 'is-ok' : (res.status === 'already_used' ? 'is-warn' : 'is-bad'));
        $('scan-result-title').textContent = (res.ok ? '✓ ' : '✕ ') + res.message;
        var t = res.ticket, detail = $('scan-result-detail');
        detail.textContent = '';
        if (t) {
            [t.code + ' · Commande ' + t.order, t.buyer, t.event + ' · ' + t.type,
             t.checked_in_at ? 'Scanné le ' + t.checked_in_at + (t.checked_in_by ? ' par ' + t.checked_in_by : '') : '']
                .filter(Boolean).forEach(function (line) { var d = document.createElement('div'); d.textContent = line; detail.appendChild(d); });
        }
        if (navigator.vibrate) { navigator.vibrate(res.ok ? 80 : [200, 100, 200]); }
        if (res.ok && t) { addHistory(t); }
    }

    function addHistory(t) {
        var list = $('scan-history');
        var empty = list.querySelector('[data-empty]');
        if (empty) { empty.remove(); }
        var li = document.createElement('li');
        var left = document.createElement('span'), right = document.createElement('span');
        var strong = document.createElement('strong'); strong.textContent = t.code;
        left.appendChild(strong); left.appendChild(document.createTextNode(' · ' + t.buyer));
        left.appendChild(document.createElement('br'));
        var sub = document.createElement('span'); sub.className = 'small text-body-secondary'; sub.textContent = t.event + ' · ' + t.type;
        left.appendChild(sub);
        right.className = 'small text-end';
        right.textContent = t.checked_in_at || '';
        li.appendChild(left); li.appendChild(right);
        list.insertBefore(li, list.firstChild);
        while (list.children.length > 10) { list.removeChild(list.lastChild); }
    }

    $('scan-start').addEventListener('click', start);
    $('scan-stop').addEventListener('click', stop);
    window.addEventListener('pagehide', stop);
})();
</script>
@endpush
@endsection
