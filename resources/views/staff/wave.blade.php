<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $wave->name }} - Antrian Petugas</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-neutral-100 px-4 py-8 text-black sm:px-6 sm:py-10">
    <main class="mx-auto flex w-full max-w-4xl flex-col gap-6">
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <a href="{{ route('staff.dashboard') }}"
                    class="inline-flex min-h-10 items-center font-mono text-sm underline underline-offset-4">
                    ← Kembali ke gelombang
                </a>
                <h1 id="wave-heading" class="mt-3 font-alte text-3xl font-bold sm:text-4xl">
                    {{ $wave->name }}
                </h1>
                <p id="staff-info" class="mt-2 font-mono text-sm text-neutral-600">
                    Memuat profil petugas...
                </p>
            </div>

            <button type="button" id="logout-button"
                class="min-h-11 shrink-0 border border-black px-4 py-2 font-mono text-sm hover:bg-black hover:text-white">
                Logout
            </button>
        </header>

        <p id="page-message" class="font-mono text-sm text-red-600" role="alert"></p>

        <section aria-labelledby="queues-heading" class="border border-black bg-white">
            <div class="border-b border-black px-4 py-3 sm:px-5">
                <h2 id="queues-heading" class="font-alte text-xl font-bold">Antrian Aktif</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[36rem] text-left font-mono text-sm">
                    <thead class="bg-neutral-100">
                        <tr>
                            <th class="px-4 py-3">No. Tiket</th>
                            <th class="px-4 py-3">Waktu</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="active-queues-body">
                        <tr><td colspan="4" class="px-4 py-5 text-neutral-600">Memuat antrian...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section aria-labelledby="history-heading" class="border border-black bg-white">
            <div class="border-b border-black px-4 py-3 sm:px-5">
                <h2 id="history-heading" class="font-alte text-xl font-bold">Riwayat Pelayanan Selesai</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[36rem] text-left font-mono text-sm">
                    <thead class="bg-neutral-100">
                        <tr>
                            <th class="px-4 py-3">No. Tiket</th>
                            <th class="px-4 py-3">Waktu Selesai</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="history-body">
                        <tr><td colspan="4" class="px-4 py-5 text-neutral-600">Memuat riwayat...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        const STAFF_TOKEN_KEY = 'antiran_staff_token';
        const STAFF_DASHBOARD_URL = @json(route('staff.dashboard'));
        const WAVE_ID = @json($wave->id);
        const token = localStorage.getItem(STAFF_TOKEN_KEY);

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
            document.getElementById('active-queues-body').addEventListener('click', handleQueueAction);
            document.getElementById('history-body').addEventListener('click', handleHistoryAction);

            if (await loadPageData()) {
                window.setInterval(loadActiveQueues, 5000);
            }
        });

        async function loadPageData() {
            const message = document.getElementById('page-message');

            try {
                const [profileResponse, queuesResponse, historyResponse] = await Promise.all([
                    fetch('/api/staff/me', { headers: authHeaders() }),
                    fetch('/api/staff/queues', { headers: authHeaders() }),
                    fetch('/api/staff/history', { headers: authHeaders() })
                ]);

                if ([profileResponse, queuesResponse, historyResponse].some(response => response.status === 401)) {
                    expireSession();
                    return false;
                }

                const [profileJson, queuesJson, historyJson] = await Promise.all([
                    profileResponse.json(),
                    queuesResponse.json(),
                    historyResponse.json()
                ]);

                if (!profileResponse.ok || !profileJson.success || !profileJson.data.room) {
                    throw new Error(profileJson.message || 'Akun petugas belum memiliki penugasan ruangan.');
                }
                if (!queuesResponse.ok || !queuesJson.success || !Array.isArray(queuesJson.data)) {
                    throw new Error(queuesJson.message || 'Gagal memuat antrian.');
                }
                if (!historyResponse.ok || !historyJson.success || !Array.isArray(historyJson.data)) {
                    throw new Error(historyJson.message || 'Gagal memuat riwayat pelayanan.');
                }

                document.getElementById('staff-info').textContent =
                    `${profileJson.data.name} · ${profileJson.data.room.name}`;
                renderActiveQueues(queuesJson.data);
                renderHistory(historyJson.data);
                message.textContent = '';

                return true;
            } catch (error) {
                console.error('Gagal memuat halaman gelombang:', error);
                message.textContent = error.message || 'Gagal memuat halaman gelombang.';
                return false;
            }
        }

        async function loadActiveQueues() {
            try {
                const response = await fetch('/api/staff/queues', { headers: authHeaders() });
                if (response.status === 401) {
                    expireSession();
                    return;
                }

                const json = await response.json();
                if (!response.ok || !json.success || !Array.isArray(json.data)) {
                    throw new Error(json.message || 'Gagal memperbarui antrian.');
                }

                renderActiveQueues(json.data);
            } catch (error) {
                console.error('Gagal memperbarui antrian:', error);
                document.getElementById('page-message').textContent =
                    error.message || 'Gagal memperbarui antrian.';
            }
        }

        function renderActiveQueues(queues) {
            const selectedQueues = queues.filter(ticket => Number(ticket.wave?.id) === Number(WAVE_ID));
            const tbody = document.getElementById('active-queues-body');

            if (selectedQueues.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="px-4 py-5 text-neutral-600">Tidak ada antrian aktif di gelombang ini.</td></tr>';
                return;
            }

            tbody.innerHTML = selectedQueues.map(ticket => `
                <tr class="border-t border-neutral-200">
                    <td class="px-4 py-3 font-bold">${escapeHtml(ticket.ticket_number)}</td>
                    <td class="px-4 py-3">${escapeHtml(formatTime(ticket.created_at))}</td>
                    <td class="px-4 py-3">${escapeHtml(ticket.status)}</td>
                    <td class="px-4 py-3">
                        <button type="button" data-ticket-action="${ticket.status === 'WAITING' ? 'call' : 'complete'}"
                            data-ticket-id="${escapeHtml(ticket.id)}"
                            class="min-h-10 border border-black px-3 py-2 hover:bg-black hover:text-white">
                            ${ticket.status === 'WAITING' ? 'Panggil' : 'Selesai'}
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function renderHistory(history) {
            const selectedHistory = history.filter(ticket => Number(ticket.wave?.id) === Number(WAVE_ID));
            const tbody = document.getElementById('history-body');

            if (selectedHistory.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="px-4 py-5 text-neutral-600">Belum ada riwayat untuk gelombang ini.</td></tr>';
                return;
            }

            tbody.innerHTML = selectedHistory.map(ticket => `
                <tr class="border-t border-neutral-200">
                    <td class="px-4 py-3 font-bold">${escapeHtml(ticket.ticket_number)}</td>
                    <td class="px-4 py-3">${escapeHtml(formatTime(ticket.completed_at))}</td>
                    <td class="px-4 py-3">${escapeHtml(ticket.status)}</td>
                    <td class="px-4 py-3">
                        <button type="button" data-delete-ticket-id="${escapeHtml(ticket.id)}"
                            class="min-h-10 border border-black px-3 py-2 hover:bg-black hover:text-white">
                            Hapus
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        async function handleQueueAction(event) {
            const button = event.target.closest('[data-ticket-action]');
            if (!button) {
                return;
            }

            button.disabled = true;
            const action = button.dataset.ticketAction;
            const ticketId = button.dataset.ticketId;

            try {
                const response = await fetch(`/api/staff/tickets/${ticketId}/${action}`, {
                    method: 'POST',
                    headers: authHeaders()
                });
                const json = await response.json();

                if (response.status === 401) {
                    expireSession();
                    return;
                }
                if (!response.ok || !json.success) {
                    throw new Error(json.message || 'Aksi tiket gagal.');
                }

                if (action === 'call' && json.data.tts?.announcement_text) {
                    speakAnnouncement(json.data.tts.announcement_text);
                }

                document.getElementById('page-message').textContent = json.message || '';
                await Promise.all([loadActiveQueues(), loadHistory()]);
            } catch (error) {
                console.error('Gagal memproses tiket:', error);
                document.getElementById('page-message').textContent =
                    error.message || 'Gagal memproses tiket.';
                button.disabled = false;
            }
        }

        async function handleHistoryAction(event) {
            const button = event.target.closest('[data-delete-ticket-id]');
            if (!button || !window.confirm('Hapus tiket ini dari riwayat?')) {
                return;
            }

            button.disabled = true;

            try {
                const response = await fetch(`/api/staff/tickets/${button.dataset.deleteTicketId}`, {
                    method: 'DELETE',
                    headers: authHeaders()
                });
                const json = await response.json();

                if (response.status === 401) {
                    expireSession();
                    return;
                }
                if (!response.ok || !json.success) {
                    throw new Error(json.message || 'Gagal menghapus riwayat tiket.');
                }

                document.getElementById('page-message').textContent = json.message || '';
                await loadHistory();
            } catch (error) {
                console.error('Gagal menghapus riwayat:', error);
                document.getElementById('page-message').textContent =
                    error.message || 'Gagal menghapus riwayat tiket.';
                button.disabled = false;
            }
        }

        async function loadHistory() {
            const response = await fetch('/api/staff/history', { headers: authHeaders() });
            if (response.status === 401) {
                expireSession();
                return;
            }

            const json = await response.json();
            if (!response.ok || !json.success || !Array.isArray(json.data)) {
                throw new Error(json.message || 'Gagal memuat riwayat pelayanan.');
            }

            renderHistory(json.data);
        }

        function formatTime(value) {
            return value ? new Date(value).toLocaleTimeString('id-ID') : '-';
        }

        function speakAnnouncement(text) {
            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel();
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = 'id-ID';
                utterance.rate = 0.95;
                window.speechSynthesis.speak(utterance);
            }
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
