<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Antiran - Ambil Tiket</title>
</head>
<body>
    <h1>Sistem Antrian (Antiran V1)</h1>
    <nav>
        <a href="/staff/login">Login Petugas</a>
    </nav>
    <hr>

    <h2>Pilih Gelombang Antrian</h2>
    <p id="message" style="color: red;"></p>
    <div id="waves-container">Memuat data gelombang...</div>

    <script>
        const TOKEN_KEY = 'antiran_ticket_token';

        document.addEventListener('DOMContentLoaded', async () => {
            await checkActiveTicket();
            await loadWaves();
        });

        // Cek apakah perangkat ini masih memiliki tiket WAITING atau CALLED
        async function checkActiveTicket() {
            const savedToken = localStorage.getItem(TOKEN_KEY);
            if (!savedToken) return;

            const res = await fetch('/api/tickets/active', {
                headers: {
                    'Accept': 'application/json',
                    'X-Ticket-Token': savedToken
                }
            });
            const json = await res.json();

            if (json.success && json.data.has_active_ticket) {
                window.location.href = `/tiket/${savedToken}`;
            } else {
                localStorage.removeItem(TOKEN_KEY);
            }
        }

        // Ambil daftar gelombang dari API
        async function loadWaves() {
            const container = document.getElementById('waves-container');
            const res = await fetch('/api/waves', {
                headers: { 'Accept': 'application/json' }
            });
            const json = await res.json();

            if (!json.success || json.data.waves.length === 0) {
                container.innerHTML = '<p>Tidak ada gelombang tersedia.</p>';
                return;
            }

            container.innerHTML = json.data.waves.map(wave => `
                <div>
                    <strong>${wave.name} (${wave.code})</strong> |
                    Sesi: ${wave.start_time} - ${wave.end_time} |
                    Kuota: ${wave.remaining_quota} / ${wave.max_quota} |
                    Status: ${wave.is_active ? 'Aktif' : 'Non-Aktif'}
                    <button
                        onclick="takeTicket(${wave.id}, this)"
                        ${!wave.is_available ? 'disabled' : ''}>
                        Ambil Tiket
                    </button>
                </div>
                <br>
            `).join('');
        }

        // Proses mengambil tiket baru
        async function takeTicket(waveId, btn) {
            const msgEl = document.getElementById('message');
            msgEl.textContent = '';
            btn.disabled = true;

            const res = await fetch('/api/tickets', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Ticket-Token': localStorage.getItem(TOKEN_KEY) || ''
                },
                body: JSON.stringify({ wave_id: waveId })
            });
            const json = await res.json();

            if (!res.ok || !json.success) {
                msgEl.textContent = json.message || 'Gagal mengambil tiket.';
                btn.disabled = false;
                await loadWaves();
                return;
            }

            localStorage.setItem(TOKEN_KEY, json.data.token);
            window.location.href = `/tiket/${json.data.token}`;
        }
    </script>
</body>
</html>