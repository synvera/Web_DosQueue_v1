<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Antiran - Ambil Tiket</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- CDN SweetAlert2 untuk tampilan pop-up alert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-[#F4F4F4] text-black min-h-screen flex flex-col justify-between overflow-x-hidden relative selection:bg-black selection:text-white">

    <div class="w-full max-w-2xl mx-auto px-6 pt-12 md:pt-16 pb-6 flex-grow flex flex-col justify-between z-10">

        <div class="header-landing-guest flex flex-col mb-10 md:mb-14">
            <h1 class="text-6xl sm:text-7xl md:text-8xl font-alte font-bold tracking-tighter leading-[0.88]">Sistem</h1>
            <h1 class="text-6xl mt-[-8px] sm:text-7xl md:text-8xl font-alte font-bold tracking-tighter leading-[0.88]">Antrian</h1>
            <p class="text-base sm:text-lg md:text-xl -mt-[8px] font-mono text-neutral-700">Pilih Gelombang Antrian</p>
        </div>

        <div class="body-landing-guest my-auto">
            <p id="message" class="text-red-600 font-mono text-sm mb-2"></p>
            <div id="waves-container" class="flex flex-col border-t border-b border-black divide-y divide-black">
                <p class="py-8 font-mono text-sm">Memuat data gelombang...</p>
            </div>
        </div>

        <div class="footer-landing-guest mt-12 mb-4">
            <p class="font-mono text-[11px] sm:text-xs leading-relaxed text-neutral-800 max-w-md">
                *Silakan pilih batch yang aktif untuk mendapatkan tiket antrean Anda. Kuota tiket diperbarui secara otomatis berdasarkan ketersediaan.
            </p>
        </div>

    </div>

    <div class="w-full overflow-hidden select-none pointer-events-none leading-none flex justify-center items-end mt-2">
        <span class="font-alte font-extrabold text-[11vh] sm:text-[10vh] md:text-[14vw] tracking-[-8px] text-black translate-y-[18%] whitespace-nowrap">
            Antrian V01
        </span>
    </div>

    <script>
        const TOKEN_KEY = 'antiran_ticket_token';

        // Konfigurasi tema Brutalist / Monospace untuk SweetAlert2
        const customSwal = Swal.mixin({
            buttonsStyling: false,
            background: '#F4F4F4',
            color: '#000000',
            padding: '2rem',
            customClass: {
                popup: 'border-2 border-black rounded-none font-mono p-6',
                title: 'font-alte font-bold text-2xl sm:text-3xl tracking-tight text-black text-left !p-0',
                htmlContainer: 'font-mono text-sm text-neutral-800 text-left !m-0 !mt-3 leading-relaxed',
                actions: 'w-full flex justify-end gap-3 !mt-6 !p-0',
                confirmButton: 'bg-black text-white font-mono text-xs sm:text-sm px-5 py-2.5 border border-black hover:bg-neutral-800 transition-colors cursor-pointer',
                cancelButton: 'bg-transparent text-black font-mono text-xs sm:text-sm px-5 py-2.5 border border-black hover:bg-black/5 transition-colors cursor-pointer',
                timerProgressBar: 'bg-black'
            }
        });

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
                container.innerHTML = '<p class="py-8 font-mono text-sm">Tidak ada gelombang tersedia.</p>';
                return;
            }

            container.innerHTML = json.data.waves.map(wave => {
                let dotColor = 'bg-green-500';
                let statusText = 'Aktif';

                if (!wave.is_active) {
                    dotColor = 'bg-slate-400';
                    statusText = 'Non-Aktif';
                } else if (wave.remaining_quota <= 10) {
                    dotColor = 'bg-red-400';
                    statusText = 'Habis';
                } else if (wave.remaining_quota <= 100) {
                    dotColor = 'bg-yellow-400';
                    statusText = 'Hampir Penuh';
                }

                return `
                <button
                    type="button"
                    onclick="takeTicket(${wave.id}, '${wave.name}', ${wave.is_available}, this)"
                    class="group flex flex-col w-full py-6 sm:py-8 text-left font-mono transition-colors ${wave.is_available ? 'hover:bg-black/5 cursor-pointer' : 'opacity-70 cursor-not-allowed'}">
                    
                    <!-- Baris Atas: Nama Batch + Indikator & Kuota -->
                    <div class="w-full flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2.5">
                            <strong class="font-bold text-lg sm:text-xl md:text-2xl tracking-tight">
                                ${wave.name} [${wave.code}]
                            </strong>
                            <span class="inline-block w-3.5 h-3.5 rounded-full border border-black ${dotColor}"></span>
                        </div>
                        <span class="text-lg sm:text-xl md:text-2xl font-medium">
                            ${wave.remaining_quota}/${wave.max_quota}
                        </span>
                    </div>

                    <!-- Baris Bawah: Sesi, Status & Tombol Aksi -->
                    <div class="w-full flex items-start justify-between text-xs sm:text-sm md:text-base text-neutral-800">
                        <div class="flex flex-col space-y-0.5">
                            <p>Sesi: ${wave.start_time} - ${wave.end_time}</p>
                            <p>Status: ${statusText}</p>
                        </div>
                        <span class="font-medium text-black group-hover:underline">
                            ${wave.is_available ? '[Ambil Tiket]' : '[Tutup]'}
                        </span>
                    </div>

                </button>
                `;
            }).join('');
        }

        // Proses mengambil tiket baru dengan Custom Alert
        async function takeTicket(waveId, waveName, isAvailable, btn) {
            // 1. Alert jika gelombang sedang tutup
            if (!isAvailable) {
                customSwal.fire({
                    title: 'Gelombang Tutup',
                    text: `Maaf, ${waveName} sedang tidak aktif atau kuota sudah habis.`,
                    confirmButtonText: '[Mengerti]'
                });
                return;
            }

            // 2. Alert Konfirmasi
            const confirmResult = await customSwal.fire({
                title: 'Konfirmasi Tiket',
                text: `Apakah Anda yakin ingin mengambil tiket untuk ${waveName}?`,
                showCancelButton: true,
                confirmButtonText: '[Ya, Ambil Tiket]',
                cancelButtonText: '[Batal]'
            });

            if (!confirmResult.isConfirmed) return;

            const msgEl = document.getElementById('message');
            msgEl.textContent = '';
            btn.disabled = true;

            try {
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

                // 3. Alert jika gagal
                if (!res.ok || !json.success) {
                    const errorMsg = json.message || 'Gagal mengambil tiket.';
                    msgEl.textContent = errorMsg;

                    await customSwal.fire({
                        title: 'Gagal Mengambil Tiket',
                        text: errorMsg,
                        confirmButtonText: '[Tutup]'
                    });

                    btn.disabled = false;
                    await loadWaves();
                    return;
                }

                // 4. Alert jika berhasil
                await customSwal.fire({
                    title: 'Tiket Berhasil Diambil',
                    text: 'Mengalihkan ke halaman nomor antrian Anda...',
                    timer: 1500,
                    timerProgressBar: true,
                    showConfirmButton: false
                });

                localStorage.setItem(TOKEN_KEY, json.data.token);
                window.location.href = `/tiket/${json.data.token}`;

            } catch (error) {
                // 5. Alert jika error jaringan
                customSwal.fire({
                    title: 'Terjadi Kesalahan',
                    text: 'Gagal terhubung ke server. Silakan periksa koneksi internet Anda.',
                    confirmButtonText: '[Coba Lagi]'
                });
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>