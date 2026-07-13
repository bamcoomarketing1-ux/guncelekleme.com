<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lig & Takım Yönetimi | Admin Panel</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        darkBg: '#070708',
                        darkCard: '#121214',
                        darkBorder: '#1c1c1e',
                        primary: '#10b981', // Emerald green from Nexuv1
                    }
                }
            }
        }
    </script>
    <!-- Google Fonts Outfit -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #070708;
            color: #f3f4f6;
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #070708;
        }
        ::-webkit-scrollbar-thumb {
            background: #1c1c1e;
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #10b981;
        }
    </style>
</head>
<body class="min-h-screen bg-darkBg pb-12">
    <!-- Navbar / Header -->
    <header class="border-b border-darkBorder bg-darkBg/80 backdrop-blur sticky top-0 z-50 px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="/panel/dashboard" class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-darkBorder hover:bg-white/5 transition border border-white/5 text-sm font-semibold text-white/70 hover:text-white">
                ← Panel'e Dön
            </a>
            <h1 class="text-xl font-black italic tracking-tighter uppercase text-white leading-none">
                LİG & TAKIM <span class="text-primary">YÖNETİMİ</span>
            </h1>
        </div>
        <div class="flex items-center gap-2">
            <span id="admin-badge" class="px-3 py-1 text-xs font-black uppercase tracking-wider bg-primary/10 text-primary border border-primary/20 rounded-full">
                Sistem Yöneticisi
            </span>
        </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-6xl mx-auto px-6 mt-8">
        <!-- Auth Loading Screen -->
        <div id="auth-loading" class="flex flex-col items-center justify-center py-24 gap-4">
            <div class="w-12 h-12 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
            <p class="text-sm font-semibold tracking-wider text-white/40 uppercase animate-pulse">Yönetici Yetkisi Doğrulanıyor...</p>
        </div>

        <!-- Dashboard Content (hidden until authorized) -->
        <div id="dashboard-content" class="hidden">
            <!-- Tabs -->
            <div class="flex border-b border-darkBorder mb-8 gap-2">
                <a href="/panel/leagues" class="px-6 py-3 border-b-2 font-black text-sm uppercase tracking-widest transition-all {{ $activeTab === 'leagues' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-white/40 hover:text-white' }}">
                    🏆 LİGLER
                </a>
                <a href="/panel/teams" class="px-6 py-3 border-b-2 font-black text-sm uppercase tracking-widest transition-all {{ $activeTab === 'teams' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-white/40 hover:text-white' }}">
                    ⚽ TAKIMLAR
                </a>
            </div>

            <!-- LEAGUES TAB -->
            @if($activeTab === 'leagues')
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Create Form -->
                <div class="lg:col-span-1 bg-darkCard border border-darkBorder rounded-2xl p-6 shadow-2xl h-fit">
                    <h2 class="text-lg font-black tracking-tight text-white uppercase mb-6 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-primary"></span> Yeni Lig Ekle
                    </h2>
                    <form id="create-league-form" class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-black text-white/40 uppercase tracking-widest mb-1.5">Lig Adı *</label>
                            <input type="text" id="league-name" required placeholder="Örn: Trendyol Süper Lig" class="w-full h-11 bg-darkBg border border-darkBorder rounded-xl px-4 text-sm text-white focus:outline-none focus:border-primary/50 transition">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-white/40 uppercase tracking-widest mb-1.5">Ülke</label>
                            <input type="text" id="league-country" placeholder="Örn: Türkiye" class="w-full h-11 bg-darkBg border border-darkBorder rounded-xl px-4 text-sm text-white focus:outline-none focus:border-primary/50 transition">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-white/40 uppercase tracking-widest mb-1.5">Lig Logosu (URL)</label>
                            <input type="url" id="league-logo" placeholder="https://..." class="w-full h-11 bg-darkBg border border-darkBorder rounded-xl px-4 text-sm text-white focus:outline-none focus:border-primary/50 transition">
                        </div>
                        <button type="submit" class="w-full h-11 bg-primary text-darkBg font-black uppercase tracking-widest text-xs rounded-xl hover:bg-primary/95 transition active:scale-95 flex items-center justify-center gap-2">
                            Lig Ekle 🚀
                        </button>
                    </form>
                </div>

                <!-- Leagues List -->
                <div class="lg:col-span-2 bg-darkCard border border-darkBorder rounded-2xl p-6 shadow-2xl">
                    <h2 class="text-lg font-black tracking-tight text-white uppercase mb-6 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-primary"></span> Mevcut Ligler
                    </h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-darkBorder text-[10px] font-black text-white/40 uppercase tracking-wider">
                                    <th class="pb-3 w-16">Logo</th>
                                    <th class="pb-3">Lig Adı</th>
                                    <th class="pb-3">Ülke</th>
                                    <th class="pb-3 text-right">İşlem</th>
                                </tr>
                            </thead>
                            <tbody id="leagues-table-body" class="divide-y divide-darkBorder/50">
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-sm text-white/20">Ligler yükleniyor...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- TEAMS TAB -->
            @if($activeTab === 'teams')
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Create Form -->
                <div class="lg:col-span-1 bg-darkCard border border-darkBorder rounded-2xl p-6 shadow-2xl h-fit">
                    <h2 class="text-lg font-black tracking-tight text-white uppercase mb-6 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-primary"></span> Yeni Takım Ekle
                    </h2>
                    <form id="create-team-form" class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-black text-white/40 uppercase tracking-widest mb-1.5">Bağlı Olduğu Lig *</label>
                            <select id="team-league-id" required class="w-full h-11 bg-darkBg border border-darkBorder rounded-xl px-4 text-sm text-white focus:outline-none focus:border-primary/50 transition">
                                <option value="" disabled selected>Lig Seçiniz</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-white/40 uppercase tracking-widest mb-1.5">Takım Adı *</label>
                            <input type="text" id="team-name" required placeholder="Örn: Galatasaray" class="w-full h-11 bg-darkBg border border-darkBorder rounded-xl px-4 text-sm text-white focus:outline-none focus:border-primary/50 transition">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-white/40 uppercase tracking-widest mb-1.5">Takım Logosu (URL)</label>
                            <input type="url" id="team-logo" placeholder="https://..." class="w-full h-11 bg-darkBg border border-darkBorder rounded-xl px-4 text-sm text-white focus:outline-none focus:border-primary/50 transition">
                        </div>
                        <button type="submit" class="w-full h-11 bg-primary text-darkBg font-black uppercase tracking-widest text-xs rounded-xl hover:bg-primary/95 transition active:scale-95 flex items-center justify-center gap-2">
                            Takım Ekle 🚀
                        </button>
                    </form>
                </div>

                <!-- Teams List -->
                <div class="lg:col-span-2 bg-darkCard border border-darkBorder rounded-2xl p-6 shadow-2xl">
                    <h2 class="text-lg font-black tracking-tight text-white uppercase mb-6 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-primary"></span> Mevcut Takımlar
                    </h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-darkBorder text-[10px] font-black text-white/40 uppercase tracking-wider">
                                    <th class="pb-3 w-16">Logo</th>
                                    <th class="pb-3">Takım Adı</th>
                                    <th class="pb-3">Bağlı Olduğu Lig</th>
                                    <th class="pb-3 text-right">İşlem</th>
                                </tr>
                            </thead>
                            <tbody id="teams-table-body" class="divide-y divide-darkBorder/50">
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-sm text-white/20">Takımlar yükleniyor...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </main>

    <!-- Notification Toast -->
    <div id="toast" class="fixed bottom-6 right-6 px-5 py-3 rounded-xl bg-primary text-darkBg font-bold shadow-2xl transition-all duration-300 transform translate-y-24 opacity-0 z-50 text-sm">
        İşlem başarılı!
    </div>

    <!-- Script Section -->
    <script>
        // Global variables
        let authToken = null;
        const activeTab = "{{ $activeTab }}";

        // Toast notification helper
        function showToast(message, isError = false) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            if (isError) {
                toast.classList.remove('bg-primary', 'text-darkBg');
                toast.classList.add('bg-red-500', 'text-white');
            } else {
                toast.classList.remove('bg-red-500', 'text-white');
                toast.classList.add('bg-primary', 'text-darkBg');
            }
            toast.classList.remove('translate-y-24', 'opacity-0');
            setTimeout(() => {
                toast.classList.add('translate-y-24', 'opacity-0');
            }, 3000);
        }

        // Search localStorage for Sanctum token
        function findToken() {
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                const val = localStorage.getItem(key);
                if (/^\d+\|[A-Za-z0-9]{40,}/.test(val)) {
                    return val;
                }
            }
            return null;
        }

        // Verify Admin Access
        async function verifyAdmin() {
            authToken = findToken();
            if (!authToken) {
                window.location.href = '/panel/login';
                return;
            }

            try {
                const response = await fetch('/api/admin/me', {
                    headers: {
                        'Authorization': `Bearer ${authToken}`,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error('Unauthorized');
                }

                const result = await response.json();
                document.getElementById('admin-badge').textContent = result.data.role;

                // Hide loading screen, show dashboard
                document.getElementById('auth-loading').classList.add('hidden');
                document.getElementById('dashboard-content').classList.remove('hidden');

                // Initialize views
                if (activeTab === 'leagues') {
                    loadLeagues();
                } else if (activeTab === 'teams') {
                    loadTeamsAndLeagues();
                }
            } catch (error) {
                console.error(error);
                localStorage.removeItem('token');
                window.location.href = '/panel/login';
            }
        }

        // Fetch API request helper
        async function apiRequest(url, method = 'GET', body = null) {
            const options = {
                method,
                headers: {
                    'Authorization': `Bearer ${authToken}`,
                    'Accept': 'application/json'
                }
            };
            if (body) {
                options.headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify(body);
            }

            const response = await fetch(url, options);
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || 'Bir hata oluştu.');
            }
            return data;
        }

        // --- LEAGUES LOGIC ---
        async function loadLeagues() {
            const tableBody = document.getElementById('leagues-table-body');
            try {
                const result = await apiRequest('/api/admin/leagues');
                const leagues = result.data || [];
                
                if (leagues.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="4" class="py-8 text-center text-sm text-white/20">Sisteme henüz hiç lig girilmemiş.</td></tr>`;
                    return;
                }

                tableBody.innerHTML = leagues.map(league => `
                    <tr class="hover:bg-white/[0.01] transition">
                        <td class="py-4">
                            <img src="${league.logo_url || '/placeholder.png'}" onerror="this.src='/placeholder.png'" class="w-8 h-8 rounded-lg object-contain bg-white/5 border border-white/5 p-1" />
                        </td>
                        <td class="py-4 font-semibold text-white">${escapeHtml(league.name)}</td>
                        <td class="py-4 text-white/60">${escapeHtml(league.country || '-')}</td>
                        <td class="py-4 text-right">
                            <button onclick="deleteLeague(${league.id})" class="px-3 py-1.5 bg-red-500/10 text-red-500 border border-red-500/20 text-xs font-black uppercase tracking-wider rounded-lg hover:bg-red-500/20 active:scale-95 transition">
                                Sil
                            </button>
                        </td>
                    </tr>
                `).join('');
            } catch (error) {
                showToast(error.message, true);
            }
        }

        async function deleteLeague(id) {
            if (!confirm('Bu ligi silmek istediğinize emin misiniz? Lige ait takımlar da etkilenebilir.')) return;
            try {
                await apiRequest(`/api/admin/leagues/${id}`, 'DELETE');
                showToast('Lig başarıyla silindi.');
                loadLeagues();
            } catch (error) {
                showToast(error.message, true);
            }
        }

        // --- TEAMS LOGIC ---
        async function loadTeamsAndLeagues() {
            const select = document.getElementById('team-league-id');
            const tableBody = document.getElementById('teams-table-body');
            
            try {
                // Load Leagues first
                const leaguesResult = await apiRequest('/api/admin/leagues');
                const leagues = leaguesResult.data || [];
                select.innerHTML = '<option value="" disabled selected>Lig Seçiniz</option>' + 
                    leagues.map(l => `<option value="${l.id}">${escapeHtml(l.name)} (${escapeHtml(l.country || 'Genel')})</option>`).join('');

                // Load Teams
                const teamsResult = await apiRequest('/api/admin/teams');
                const teams = teamsResult.data || [];

                if (teams.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="4" class="py-8 text-center text-sm text-white/20">Sisteme henüz hiç takım girilmemiş.</td></tr>`;
                    return;
                }

                tableBody.innerHTML = teams.map(team => `
                    <tr class="hover:bg-white/[0.01] transition">
                        <td class="py-4">
                            <img src="${team.logo_url || '/placeholder.png'}" onerror="this.src='/placeholder.png'" class="w-8 h-8 rounded-lg object-contain bg-white/5 border border-white/5 p-1" />
                        </td>
                        <td class="py-4 font-semibold text-white">${escapeHtml(team.name)}</td>
                        <td class="py-4 text-white/60">${escapeHtml(team.league ? team.league.name : '-')}</td>
                        <td class="py-4 text-right">
                            <button onclick="deleteTeam(${team.id})" class="px-3 py-1.5 bg-red-500/10 text-red-500 border border-red-500/20 text-xs font-black uppercase tracking-wider rounded-lg hover:bg-red-500/20 active:scale-95 transition">
                                Sil
                            </button>
                        </td>
                    </tr>
                `).join('');
            } catch (error) {
                showToast(error.message, true);
            }
        }

        async function deleteTeam(id) {
            if (!confirm('Bu takımı silmek istediğinize emin misiniz?')) return;
            try {
                await apiRequest(`/api/admin/teams/${id}`, 'DELETE');
                showToast('Takım başarıyla silindi.');
                loadTeamsAndLeagues();
            } catch (error) {
                showToast(error.message, true);
            }
        }

        // Form Submissions
        document.addEventListener('DOMContentLoaded', () => {
            // Verify access
            verifyAdmin();

            // League Form Submit
            const leagueForm = document.getElementById('create-league-form');
            if (leagueForm) {
                leagueForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const name = document.getElementById('league-name').value;
                    const country = document.getElementById('league-country').value;
                    const logo_url = document.getElementById('league-logo').value;

                    try {
                        await apiRequest('/api/admin/leagues', 'POST', { name, country, logo_url, is_active: true });
                        showToast('Lig başarıyla eklendi!');
                        leagueForm.reset();
                        loadLeagues();
                    } catch (error) {
                        showToast(error.message, true);
                    }
                });
            }

            // Team Form Submit
            const teamForm = document.getElementById('create-team-form');
            if (teamForm) {
                teamForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const league_id = document.getElementById('team-league-id').value;
                    const name = document.getElementById('team-name').value;
                    const logo_url = document.getElementById('team-logo').value;

                    try {
                        await apiRequest('/api/admin/teams', 'POST', { league_id, name, logo_url, is_active: true });
                        showToast('Takım başarıyla eklendi!');
                        teamForm.reset();
                        loadTeamsAndLeagues();
                    } catch (error) {
                        showToast(error.message, true);
                    }
                });
            }
        });

        // Escaper helper to prevent XSS
        function escapeHtml(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    </script>
</body>
</html>
