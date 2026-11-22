<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Petugas - Antiran</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-neutral-100 px-4 py-8 text-black sm:px-6 sm:py-10">
    <main class="mx-auto flex w-full max-w-4xl flex-col gap-6">
        <header class="flex items-start justify-between gap-4">
            <div>
                <h1 class="font-alte text-3xl font-bold sm:text-4xl">Dashboard Petugas</h1>
                <p id="staff-info" class="mt-2 font-mono text-sm text-neutral-600">
                    Memuat profil petugas...
                </p>
            </div>

            <button type="button" id="logout-button"
                class="flex items-center gap-2 min-h-11 shrink-0 border border-black px-4 py-2 font-mono text-sm hover:bg-black hover:text-white">
                <img src="{{ asset('icons/right-from-bracket.svg') }}" alt="" class="w-6">
                Logout
            </button>
        </header>

        <p id="dashboard-message" class="font-mono text-sm text-red-600" role="alert"></p>

        <section aria-labelledby="waves-heading" class="flex flex-col gap-3">
            <h2 id="waves-heading" class="font-alte text-xl font-bold">Pilih Gelombang</h2>
            <div id="wave-buttons" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <p class="font-mono text-sm text-neutral-600">Memuat gelombang...</p>
            </div>
        </section>
    </main>

    <script>
        const STAFF_TOKEN_KEY = 'antiran_staff_token';
        const WAVE_URL_TEMPLATE = @json(route('staff.waves.show', ['wave' => '__WAVE_ID__']));
        const token = localStorage.getItem(STAFF_TOKEN_KEY);
        let waves = [];
        let activeQueues = [];

        function authHeaders() {
            return {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            };
        }

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, character => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            })[character]);
        }

        document.addEventListener('DOMContentLoaded', async () => {
            if (!token) {
                window.location.href = '/staff/login';
                return;
            }

            document.getElementById('logout-button').addEventListener('click', logout);

            if (await loadDashboard()) {
                window.setInterval(loadQueues, 5000);
            }
        });

        async function loadDashboard() {
            const message = document.getElementById('dashboard-message');

            try {
                const [profileResponse, wavesResponse, queuesResponse] = await Promise.all([
                    fetch('/api/staff/me', { headers: authHeaders() }),
                    fetch('/api/waves', { headers: { 'Accept': 'application/json' } }),
                    fetch('/api/staff/queues', { headers: authHeaders() })
                ]);

                if (profileResponse.status === 401 || queuesResponse.status === 401) {
                    expireSession();
                    return false;
                }

                const [profileJson, wavesJson, queuesJson] = await Promise.all([
                    profileResponse.json(),
                    wavesResponse.json(),
                    queuesResponse.json()
                ]);

                if (!profileResponse.ok || !profileJson.success || !profileJson.data.room) {
                    throw new Error(profileJson.message || 'Akun petugas belum memiliki penugasan ruangan.');
                }
                if (!wavesResponse.ok || !wavesJson.success || !Array.isArray(wavesJson.data.waves)) {
                    throw new Error(wavesJson.message || 'Gagal memuat daftar gelombang.');
                }
                if (!queuesResponse.ok || !queuesJson.success || !Array.isArray(queuesJson.data)) {
                    throw new Error(queuesJson.message || 'Gagal memuat jumlah antrian.');
                }

                document.getElementById('staff-info').textContent =
                    `${profileJson.data.name} · ${profileJson.data.room.name}`;
                waves = wavesJson.data.waves;
                activeQueues = queuesJson.data;
                renderWaveButtons();
                message.textContent = '';

                return true;
            } catch (error) {
                console.error('Gagal memuat dashboard petugas:', error);
                message.textContent = error.message || 'Gagal memuat dashboard petugas.';
                return false;
            }
        }

        async function loadQueues() {
            try {
                const response = await fetch('/api/staff/queues', { headers: authHeaders() });
                if (response.status === 401) {
                    expireSession();
                    return;
                }

                const json = await response.json();
                if (!response.ok || !json.success || !Array.isArray(json.data)) {
                    throw new Error(json.message || 'Gagal memperbarui jumlah antrian.');
                }

                activeQueues = json.data;
                renderWaveButtons();
            } catch (error) {
                console.error('Gagal memperbarui jumlah antrian:', error);
                document.getElementById('dashboard-message').textContent =
                    error.message || 'Gagal memperbarui jumlah antrian.';
            }
        }

        function renderWaveButtons() {
            const container = document.getElementById('wave-buttons');

            if (waves.length === 0) {
                container.innerHTML = '<p class="font-mono text-sm text-neutral-600">Belum ada gelombang.</p>';
                return;
            }

            container.innerHTML = waves.map(wave => {
                const queueCount = activeQueues.filter(ticket => Number(ticket.wave?.id) === Number(wave.id)).length;
                const waveLabel = wave.name.replace(/^Gelombang\s*/i, 'Gel ');
                const waveUrl = WAVE_URL_TEMPLATE.replace('__WAVE_ID__', encodeURIComponent(wave.id));

                return `
                    <a href="${escapeHtml(waveUrl)}"
                        class="flex min-h-16 items-center justify-between gap-3 border-2 border-black bg-white px-4 py-3 font-mono text-black transition-colors hover:bg-black hover:text-white">
                        <span class="font-bold">${escapeHtml(waveLabel)} [${queueCount}]</span>
                        <span class="text-xs">Lihat</span>
                    </a>
                `;
            }).join('');
        }

        function expireSession() {
            localStorage.removeItem(STAFF_TOKEN_KEY);
            window.location.href = '/staff/login';
        }

        async function logout() {
            try {
                await fetch('/api/staff/logout', {
                    method: 'POST',
                    headers: authHeaders()
                });
            } finally {
                expireSession();
            }
        }
    </script>
</body>
</html>
