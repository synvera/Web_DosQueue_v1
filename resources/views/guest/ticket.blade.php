<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tiket Anda - Antiran</title>
</head>
<body>
    <h1>Detail Tiket Antrian</h1>
    <nav>
        <a href="/">Kembali ke Beranda</a>
    </nav>
    <hr>

    <div id="ticket-container">Memuat informasi tiket...</div>

    <script>
        const TOKEN = @json($token);
        const TOKEN_KEY = 'antiran_ticket_token';
        let pollingTimer = null;

        document.addEventListener('DOMContentLoaded', async () => {
            // Simpan token ke LocalStorage jika pengunjung membuka link /tiket/{token} secara langsung
            localStorage.setItem(TOKEN_KEY, TOKEN);
            await fetchMonitor();

            // Short Polling setiap 4 detik
            pollingTimer = setInterval(fetchMonitor, 4000);
        });

        async function fetchMonitor() {
            const container = document.getElementById('ticket-container');

            const res = await fetch(`/api/tickets/${TOKEN}/monitor`, {
                headers: { 'Accept': 'application/json' }
            });
            const json = await res.json();

            if (!res.ok || !json.success) {
                clearInterval(pollingTimer);
                localStorage.removeItem(TOKEN_KEY);
                container.innerHTML = `<p style="color:red;">${json.message}</p><a href="/">Ambil Tiket Baru</a>`;
                return;
            }

            const data = json.data;
            const isCompleted = data.status === 'COMPLETED';

            // Jika sudah COMPLETED, hentikan polling dan bersihkan sesi aktif agar bisa ambil tiket baru
            if (isCompleted) {
                clearInterval(pollingTimer);
                if (localStorage.getItem(TOKEN_KEY) === TOKEN) {
                    localStorage.removeItem(TOKEN_KEY);
                }
            }

            container.innerHTML = `
                <div style="${isCompleted ? 'opacity: 0.5;' : ''}">
                    <p><strong>Nomor Tiket:</strong> ${data.ticket_number}</p>
                    <p><strong>Status Tiket:</strong> ${data.status}</p>
                    <p><strong>Gelombang:</strong> ${data.wave.name}</p>
                    <p><strong>Ruangan Tujuan:</strong> ${data.room.name}</p>
                    <hr>
                    <h3>Live Monitor (${data.room.name})</h3>
                    <p><strong>Sedang Dipanggil Saat Ini:</strong> ${data.current_serving_ticket ?? '-'}</p>
                    <p><strong>Sisa Antrian di Depan Anda:</strong> ${data.queues_ahead} orang</p>
                    ${isCompleted ? '<p><strong>Pelayanan Anda telah selesai.</strong> <a href="/">Ambil Tiket Baru</a></p>' : '<p><small>Memperbarui otomatis setiap 4 detik...</small></p>'}
                </div>
            `;
        }
    </script>
</body>
</html>