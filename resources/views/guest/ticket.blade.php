<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiket Antrian - Antiran</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <style>
        /* Teks gelombang vertikal 90 derajat seperti referensi gambar */
        .vertical-wave {
            writing-mode: vertical-rl;
            text-orientation: mixed;
        }
    </style>
</head>
<body class="bg-neutral-200 min-h-screen flex justify-center text-black antialiased">

    <!-- Container Mobile 360px -->
    <div id="ticket-app" data-token="{{ $token }}"
        class="w-full max-w-[360px] min-h-screen bg-[#F5F5F4] flex flex-col justify-between p-6 border-x border-black/15 relative">

        <!-- 1. HEADER ATAS: Pojok Kiri Logo Ticket & Pojok Kanan Detail Antrian Berjalan -->
        <header class="flex items-start justify-between border-b-2 border-black pb-4">
            <!-- Pojok Kiri: Logo Ticket -->
            <div class="flex items-center gap-2">
                <div class="w-9 h-9 flex items-center justify-center bg-white">
                     <img src="{{ asset('icons/confirmation_number.svg') }}" alt="Icon Tiket" class="w-5 h-5">
                </div>
            </div>

            <!-- Pojok Kanan: Detail Antrian Berjalan & Sisa Antrian -->
            <div class="text-right font-mono-ticket">
                <span class="block text-[10px] uppercase tracking-wider text-neutral-500">Sedang Dipanggil</span>
                <span id="header-current-serving" class="block text-sm font-bold text-black mt-0.5">-</span>
                <span id="header-queues-ahead" class="block text-[11px] text-neutral-600 mt-0.5">Menunggu...</span>
            </div>
        </header>

        <!-- 2. BODY: Card Tiket Referensi Gambar -->
        <main id="ticket-body" class="my-auto py-8 transition-all duration-300">
            <!-- Label kecil di atas kanan card -->
            <div class="flex justify-between items-end mb-2 font-mono-ticket text-xs">

            </div>

            <!-- Main Ticket Box (Swiss / Boarding Pass Style) -->
            <div class="w-full border-[2.5px] border-black bg-white flex items-stretch relative rounded-[12px]">

                <!-- Bagian Kiri: Detail Utama Tiket -->
                <div class="flex-1 p-4 flex flex-col justify-between">
                    <!-- Atas: Label & Nomor Tiket Besar -->
                    <div>
                        <div class="top flex justify-between items-center">
                        <span class="block font-mono-ticket text-[10px] uppercase tracking-widest text-neutral-500">
                            Nomor Antrian
                        </span>
                        <span id="ticket-status-badge" class="font-bold uppercase text-[10px] text-neutral-500">
                            MEMUAT...
                        </span>
                        </div>
                        <span id="ticket-number-display" class="block font-mono-ticket text-[28px] font-bold tracking-tight text-black leading-tight mt-0.5">
                            ---
                        </span>
                    </div>

                    <!-- Tengah: Garis Barcode Dekoratif -->
                    <div id="ticket-barcode" class="my-3 h-5 w-full flex items-stretch justify-between overflow-hidden opacity-80"></div>

                    <!-- Bawah: Ruangan & Token Terpisah Rapi -->
                    <div class="pt-2.5 border-t border-black/20 flex items-center justify-between gap-2 font-mono-ticket">
                        <div>
                            <span class="block text-[9px] uppercase text-neutral-400">Ruangan</span>
                            <span id="ticket-room-display" class="text-xs font-bold text-black">-</span>
                        </div>
                        <div class="text-right max-w-[115px]">
                            <span class="block text-[9px] uppercase text-neutral-400">ID Token</span>
                            <span class="block text-[10px] text-neutral-600 truncate" title="{{ $token }}">{{ $token }}</span>
                        </div>
                    </div>
                </div>

                <!-- Bagian Kanan: Sobekan Gelombang (Hanya sudut kanan yang melengkung) -->
                <div class="w-[58px] border-l-2 border-dashed border-black bg-black text-white flex flex-col items-center justify-center py-3 px-1 shrink-0 rounded-r-[9px]">
                    <span id="ticket-wave-display" class="vertical-wave text-lg font-bold tracking-widest uppercase whitespace-nowrap">
                        Gelombang -
                    </span>
                </div>
            </div>

            <!-- Keterangan Bawah Card -->
            <div id="ticket-footer-note" class="mt-4 font-mono-ticket text-[11px] text-neutral-600 leading-relaxed">
                *Harap bersiap di area tunggu. Halaman ini diperbarui otomatis setiap 4 detik.
            </div>
        </main>

        <!-- 3. FOOTER -->
        <footer class="border-t border-black/20 pt-3 flex justify-between items-center font-mono-ticket text-[11px] text-neutral-500">
            <a href="/" class="hover:text-black underline">← Beranda</a>
            <span>Antiran V01</span>
        </footer>

        <!-- 4. POP-UP MODAL SAAT STATUS DIPANGGIL (CALLED) -->
        <div id="called-modal" class="hidden fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
            <div class="w-full max-w-[320px] bg-white border-[3px] border-black p-6 text-center shadow-[6px_6px_0px_0px_rgba(0,0,0,1)]">
                <h2 class="text-xl font-bold tracking-tight mb-2">Panggilan Antrian</h2>
                <p id="called-modal-text" class="font-mono-ticket text-sm text-neutral-800 leading-relaxed mb-5">
                    Anda sudah dipanggil, harap ke ruangan tujuan Anda.
                </p>
                <button onclick="closeCalledModal()"
                    class="w-full py-2.5 px-4 bg-black text-white font-mono-ticket text-xs uppercase tracking-wider font-bold hover:bg-neutral-800 cursor-pointer">
                    Mengerti 
                </button>
            </div>
        </div>

    </div>

    <script>
        const appEl = document.getElementById('ticket-app');
        const TOKEN = appEl.dataset.token;
        const TOKEN_KEY = 'antiran_ticket_token';
        const POPUP_SHOWN_KEY = `antiran_called_popup_${TOKEN}`;

        let pollingTimer = null;

        document.addEventListener('DOMContentLoaded', async () => {
            localStorage.setItem(TOKEN_KEY, TOKEN);
            renderTokenBarcode(TOKEN);
            await fetchMonitor();
            pollingTimer = setInterval(fetchMonitor, 4000);
        });

        async function fetchMonitor() {
            try {
                const res = await fetch(`/api/tickets/${TOKEN}/monitor`, {
                    headers: { 'Accept': 'application/json' }
                });
                const json = await res.json();

                if (!res.ok || !json.success) {
                    clearInterval(pollingTimer);
                    localStorage.removeItem(TOKEN_KEY);
                    document.getElementById('ticket-body').innerHTML = `
                        <div class="border-2 border-black bg-white p-5 text-center font-mono-ticket">
                            <p class="text-sm text-red-600 mb-4">${json.message || 'Tiket tidak ditemukan.'}</p>
                            <a href="/" class="inline-block px-4 py-2 bg-black text-white text-xs">[ Ambil Tiket Baru ]</a>
                        </div>
                    `;
                    return;
                }

                const data = json.data;
                renderTicketUI(data);
            } catch (err) {
                console.error('Polling error:', err);
            }
        }

        function renderTicketUI(data) {
            const isCalled = data.status === 'CALLED';
            const isCompleted = data.status === 'COMPLETED';

            // Update Header (Antrian Berjalan & Sisa Antrian)
            document.getElementById('header-current-serving').textContent = data.current_serving_ticket ?? '-';
            document.getElementById('header-queues-ahead').textContent = isCompleted
                ? 'Selesai dilayani'
                : (isCalled ? 'Giliran Anda!' : `Sisa: ${data.queues_ahead} antrian`);

            // Update Isi Card
            document.getElementById('ticket-number-display').textContent = data.ticket_number;
            document.getElementById('ticket-room-display').textContent = ` ${data.room.name}`;
            // Menampilkan nama gelombang singkat (misal "Gel 1" dari "Gelombang 1")
            const shortWaveName = data.wave.name.replace('Gelombang', 'Gel');
            document.getElementById('ticket-wave-display').textContent = shortWaveName;

            // Update Status Badge & Style Card
            const badgeEl = document.getElementById('ticket-status-badge');
            const bodyEl = document.getElementById('ticket-body');
            const noteEl = document.getElementById('ticket-footer-note');

            badgeEl.textContent = data.status;

            if (isCalled) {
                badgeEl.className = 'px-2 py-0.5 bg-amber-300 font-bold uppercase text-[10px]';
                noteEl.innerHTML = `<strong class="text-black">*GILIRAN ANDA!</strong> Silakan segera menuju <strong>${data.room.name}</strong>.`;

                // Munculkan pop-up panggilan sekali saat status berubah menjadi CALLED
                if (!sessionStorage.getItem(POPUP_SHOWN_KEY)) {
                    document.getElementById('called-modal-text').textContent =
                        `Anda sudah dipanggil, harap ke ruangan ${data.room.name}`;
                    document.getElementById('called-modal').classList.remove('hidden');
                    sessionStorage.setItem(POPUP_SHOWN_KEY, '1');
                }
            } else if (isCompleted) {
                clearInterval(pollingTimer);
                if (localStorage.getItem(TOKEN_KEY) === TOKEN) {
                    localStorage.removeItem(TOKEN_KEY);
                }

                // Efek grayscale & redup sesuai spesifikasi saat COMPLETED
                bodyEl.classList.add('grayscale', 'opacity-50');
                badgeEl.className = 'px-2 py-0.5 bg-neutral-300 font-bold uppercase text-[10px]';
                noteEl.innerHTML = `Pelayanan Anda telah selesai. <a href="/" class="underline font-bold text-black">Ambil tiket baru →</a>`;
            } else {
                badgeEl.className = 'font-bold uppercase text-[10px] text-neutral-500';
            }
        }

        function renderTokenBarcode(token) {
            const barcodeEl = document.getElementById('ticket-barcode');
            if (!barcodeEl || !token) return;

            let barsHtml = '';
            for (let i = 0; i < token.length; i++) {
                const code = token.charCodeAt(i);
                // Menentukan ketebalan garis hitam (1px - 3px) & jarak spasi (1px - 3px) dari kode ASCII karakter token
                const barWidth = (code % 3) + 1;
                const gapWidth = ((code >> 2) % 3) + 1;
                barsHtml += `<span style="width:${barWidth}px; margin-right:${gapWidth}px;" class="bg-black h-full inline-block shrink-0"></span>`;
            }
            barcodeEl.innerHTML = barsHtml;
        }

        function closeCalledModal() {
            document.getElementById('called-modal').classList.add('hidden');
        }
    </script>
</body>
</html>