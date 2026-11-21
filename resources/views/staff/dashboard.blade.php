<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Petugas - Antiran</title>
</head>
<body>
    <h1>Dashboard Antrian Petugas</h1>
    <div id="staff-info">Memuat profil petugas...</div>
    <button onclick="logout()">Logout</button>
    <hr>

    <p id="action-msg" style="color: blue;"></p>

    <h2>Antrian Aktif (FIFO)</h2>
    <button onclick="loadAllData()">Refresh Manual</button>
    <br><br>
    <table border="1" cellpadding="6" cellspacing="0">
        <thead>
            <tr>
                <th>No. Tiket</th>
                <th>Waktu Ambil</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody id="active-queues-body">
            <tr><td colspan="4">Memuat data...</td></tr>
        </tbody>
    </table>

    <hr>

    <h2>Riwayat Pelayanan Selesai (COMPLETED)</h2>
    <table border="1" cellpadding="6" cellspacing="0">
        <thead>
            <tr>
                <th>No. Tiket</th>
                <th>Waktu Selesai</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody id="history-body">
            <tr><td colspan="4">Memuat data...</td></tr>
        </tbody>
    </table>

    <script>
        const STAFF_TOKEN_KEY = 'antiran_staff_token';
        const token = localStorage.getItem(STAFF_TOKEN_KEY);

        document.addEventListener('DOMContentLoaded', async () => {
            if (!token) {
                window.location.href = '/staff/login';
                return;
            }

            await loadProfile();
            await loadAllData();

            // Auto-refresh antrian aktif setiap 5 detik
            setInterval(loadActiveQueues, 5000);
        });

        function authHeaders() {
            return {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            };
        }

        async function loadProfile() {
            const res = await fetch('/api/staff/me', { headers: authHeaders() });
            if (!res.ok) {
                localStorage.removeItem(STAFF_TOKEN_KEY);
                window.location.href = '/staff/login';
                return;
            }
            const json = await res.json();
            const staff = json.data;
            document.getElementById('staff-info').innerHTML = `
                <p><strong>Petugas:</strong> ${staff.name} | <strong>Tugas:</strong> ${staff.wave.name} - ${staff.room.name}</p>
            `;
        }

        async function loadAllData() {
            await loadActiveQueues();
            await loadHistory();
        }

        async function loadActiveQueues() {
            const tbody = document.getElementById('active-queues-body');
            const res = await fetch('/api/staff/queues', { headers: authHeaders() });
            const json = await res.json();

            if (!json.success || json.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4">Tidak ada antrian aktif.</td></tr>';
                return;
            }

            tbody.innerHTML = json.data.map(ticket => `
                <tr>
                    <td><strong>${ticket.ticket_number}</strong></td>
                    <td>${new Date(ticket.created_at).toLocaleTimeString('id-ID')}</td>
                    <td>${ticket.status}</td>
                    <td>
                        ${ticket.status === 'WAITING'
                            ? `<button onclick="callTicket(${ticket.id})">Panggil</button>`
                            : `<button onclick="completeTicket(${ticket.id})">Selesai</button>`
                        }
                    </td>
                </tr>
            `).join('');
        }

        async function loadHistory() {
            const tbody = document.getElementById('history-body');
            const res = await fetch('/api/staff/history', { headers: authHeaders() });
            const json = await res.json();

            if (!json.success || json.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4">Belum ada riwayat selesai.</td></tr>';
                return;
            }

            tbody.innerHTML = json.data.map(ticket => `
                <tr>
                    <td>${ticket.ticket_number}</td>
                    <td>${ticket.completed_at ? new Date(ticket.completed_at).toLocaleTimeString('id-ID') : '-'}</td>
                    <td>${ticket.status}</td>
                    <td>
                        <button onclick="deleteHistory(${ticket.id})">Hapus Data</button>
                    </td>
                </tr>
            `).join('');
        }

        async function callTicket(id) {
            const msgEl = document.getElementById('action-msg');
            const res = await fetch(`/api/staff/tickets/${id}/call`, {
                method: 'POST',
                headers: authHeaders()
            });
            const json = await res.json();

            msgEl.textContent = json.message;
            if (res.ok && json.success) {
                speakAnnouncement(json.data.tts.announcement_text);
                await loadActiveQueues();
            }
        }

        async function completeTicket(id) {
            const msgEl = document.getElementById('action-msg');
            const res = await fetch(`/api/staff/tickets/${id}/complete`, {
                method: 'POST',
                headers: authHeaders()
            });
            const json = await res.json();

            msgEl.textContent = json.message;
            if (res.ok && json.success) {
                await loadAllData();
            }
        }

        async function deleteHistory(id) {
            if (!confirm('Hapus tiket ini dari riwayat?')) return;

            const msgEl = document.getElementById('action-msg');
            const res = await fetch(`/api/staff/tickets/${id}`, {
                method: 'DELETE',
                headers: authHeaders()
            });
            const json = await res.json();

            msgEl.textContent = json.message;
            if (res.ok && json.success) {
                await loadHistory();
            }
        }

        // Fitur Text-to-Speech (TTS) bawaan browser
        function speakAnnouncement(text) {
            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel();
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = 'id-ID';
                utterance.rate = 0.95;
                window.speechSynthesis.speak(utterance);
            }
        }

        async function logout() {
            await fetch('/api/staff/logout', {
                method: 'POST',
                headers: authHeaders()
            });
            localStorage.removeItem(STAFF_TOKEN_KEY);
            window.location.href = '/staff/login';
        }
    </script>
</body>
</html>