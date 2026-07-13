<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lig & Takım Yönetimi | Admin Panel</title>
    <!-- Google Fonts Outfit -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* Vanilla CSS */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #070708;
            color: #f3f4f6;
            line-height: 1.5;
            padding-bottom: 3rem;
        }
        header {
            border-bottom: 1px solid #1c1c1e;
            background-color: rgba(7, 7, 8, 0.85);
            backdrop-filter: blur(8px);
            position: sticky;
            top: 0;
            z-index: 50;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.8rem;
            border-radius: 0.5rem;
            background-color: #1c1c1e;
            border: 1px solid rgba(255,255,255,0.05);
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-back:hover {
            background-color: rgba(255,255,255,0.05);
            color: #fff;
        }
        .title {
            font-size: 1.25rem;
            font-weight: 900;
            text-transform: uppercase;
            font-style: italic;
            letter-spacing: -0.05em;
            color: #fff;
        }
        .title span {
            color: #10b981;
        }
        .badge {
            padding: 0.25rem 0.75rem;
            font-size: 0.7rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background-color: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 9999px;
        }
        main {
            max-width: 1200px;
            margin: 2rem auto 0;
            padding: 0 1.5rem;
        }
        /* Loader */
        .loader-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 6rem 0;
            gap: 1rem;
        }
        .loader {
            width: 3rem;
            height: 3rem;
            border: 4px solid #10b981;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .loader-text {
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            color: rgba(255,255,255,0.4);
            text-transform: uppercase;
            text-align: center;
            max-width: 500px;
            word-wrap: break-word;
        }
        .hidden {
            display: none !important;
        }
        /* Tabs */
        .tabs {
            display: flex;
            border-bottom: 1px solid #1c1c1e;
            margin-bottom: 2rem;
            gap: 0.5rem;
        }
        .tab-btn {
            padding: 0.75rem 1.5rem;
            font-weight: 900;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: rgba(255,255,255,0.4);
            text-decoration: none;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }
        .tab-btn:hover {
            color: #fff;
        }
        .tab-btn.active {
            border-color: #10b981;
            color: #10b981;
            background-color: rgba(16, 185, 129, 0.05);
        }
        /* Grid Layout */
        .grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
        }
        @media (min-width: 1024px) {
            .grid {
                grid-template-columns: 1fr 2fr;
            }
        }
        /* Cards */
        .card {
            background-color: #121214;
            border: 1px solid #1c1c1e;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .card-title {
            font-size: 1.1rem;
            font-weight: 900;
            text-transform: uppercase;
            color: #fff;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .card-title::before {
            content: '';
            display: block;
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 50%;
            background-color: #10b981;
        }
        /* Forms */
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-label {
            display: block;
            font-size: 0.65rem;
            font-weight: 900;
            color: rgba(255,255,255,0.4);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 0.5rem;
        }
        .form-input {
            width: 100%;
            height: 2.75rem;
            background-color: #070708;
            border: 1px solid #1c1c1e;
            border-radius: 0.75rem;
            padding: 0 1rem;
            font-size: 0.85rem;
            color: #fff;
            transition: border-color 0.2s;
        }
        .form-input:focus {
            outline: none;
            border-color: rgba(16, 185, 129, 0.5);
        }
        .btn-submit {
            width: 100%;
            height: 2.75rem;
            background-color: #10b981;
            color: #070708;
            font-weight: 900;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            border: none;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-submit:hover {
            background-color: #0d9668;
        }
        .btn-submit:active {
            transform: scale(0.98);
        }
        /* Tables */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }
        th {
            padding-bottom: 0.75rem;
            font-size: 0.65rem;
            font-weight: 900;
            color: rgba(255,255,255,0.4);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            border-bottom: 1px solid #1c1c1e;
        }
        td {
            padding: 1rem 0;
            border-bottom: 1px solid rgba(28, 28, 30, 0.5);
            font-size: 0.9rem;
        }
        .logo-img {
            width: 2rem;
            height: 2rem;
            border-radius: 0.5rem;
            object-fit: contain;
            background-color: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.05);
            padding: 0.25rem;
        }
        .btn-delete {
            padding: 0.4rem 0.8rem;
            background-color: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 0.5rem;
            font-size: 0.7rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-delete:hover {
            background-color: rgba(239, 68, 68, 0.2);
        }
        .btn-delete:active {
            transform: scale(0.95);
        }
        /* Toast */
        .toast {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            padding: 0.75rem 1.25rem;
            border-radius: 0.75rem;
            background-color: #10b981;
            color: #070708;
            font-weight: 700;
            font-size: 0.85rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            transition: all 0.3s transform, opacity;
            transform: translateY(6rem);
            opacity: 0;
            z-index: 100;
        }
        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }
    </style>
</head>
<body>
    <!-- Navbar / Header -->
    <header>
        <div class="header-left">
            <a href="/panel/dashboard" class="btn-back">
                ← Panel'e Dön
            </a>
            <h1 class="title">
                LİG & TAKIM <span>YÖNETİMİ</span>
            </h1>
        </div>
        <div>
            <span id="admin-badge" class="badge">
                Sistem Yöneticisi
            </span>
        </div>
    </header>

    <!-- Main Container -->
    <main>
        <!-- Auth Loading Screen -->
        <div id="auth-loading" class="loader-container">
            <div class="loader"></div>
            <p class="loader-text" id="loader-status">Yönetici Yetkisi Doğrulanıyor...</p>
        </div>

        <!-- Dashboard Content (hidden until authorized) -->
        <div id="dashboard-content" class="hidden">
            <!-- Tabs -->
            <div class="tabs">
                <a href="/panel/leagues" class="tab-btn {{ $activeTab === 'leagues' ? 'active' : '' }}">
                    🏆 LİGLER
                </a>
                <a href="/panel/teams" class="tab-btn {{ $activeTab === 'teams' ? 'active' : '' }}">
                    ⚽ TAKIMLAR
                </a>
            </div>

            <!-- LEAGUES TAB -->
            @if($activeTab === 'leagues')
            <div class="grid">
                <!-- Create Form -->
                <div class="card">
                    <h2 class="card-title">Yeni Lig Ekle</h2>
                    <form id="create-league-form" onsubmit="handleLeagueSubmit(event)">
                        <div class="form-group">
                            <label class="form-label">Lig Adı *</label>
                            <input type="text" id="league-name" required placeholder="Örn: Trendyol Süper Lig" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ülke</label>
                            <input type="text" id="league-country" placeholder="Örn: Türkiye" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Lig Logosu (URL)</label>
                            <input type="url" id="league-logo" placeholder="https://..." class="form-input">
                        </div>
                        <button type="submit" class="btn-submit">
                            Lig Ekle 🚀
                        </button>
                    </form>
                </div>

                <!-- Leagues List -->
                <div class="card">
                    <h2 class="card-title">Mevcut Ligler</h2>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 3.5rem;">Logo</th>
                                    <th>Lig Adı</th>
                                    <th>Ülke</th>
                                    <th style="text-align: right;">İşlem</th>
                                </tr>
                            </thead>
                            <tbody id="leagues-table-body">
                                <tr>
                                    <td colspan="4" style="text-align: center; color: rgba(255,255,255,0.2); padding: 2rem 0;">Ligler yükleniyor...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- TEAMS TAB -->
            @if($activeTab === 'teams')
            <div class="grid">
                <!-- Create Form -->
                <div class="card">
                    <h2 class="card-title">Yeni Takım Ekle</h2>
                    <form id="create-team-form" onsubmit="handleTeamSubmit(event)">
                        <div class="form-group">
                            <label class="form-label">Bağlı Olduğu Lig *</label>
                            <select id="team-league-id" required class="form-input">
                                <option value="" disabled selected>Lig Seçiniz</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Takım Adı *</label>
                            <input type="text" id="team-name" required placeholder="Örn: Galatasaray" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Takım Logosu (URL)</label>
                            <input type="url" id="team-logo" placeholder="https://..." class="form-input">
                        </div>
                        <button type="submit" class="btn-submit">
                            Takım Ekle 🚀
                        </button>
                    </form>
                </div>

                <!-- Teams List -->
                <div class="card">
                    <h2 class="card-title">Mevcut Takımlar</h2>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 3.5rem;">Logo</th>
                                    <th>Takım Adı</th>
                                    <th>Bağlı Olduğu Lig</th>
                                    <th style="text-align: right;">İşlem</th>
                                </tr>
                            </thead>
                            <tbody id="teams-table-body">
                                <tr>
                                    <td colspan="4" style="text-align: center; color: rgba(255,255,255,0.2); padding: 2rem 0;">Takımlar yükleniyor...</td>
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
    <div id="toast" class="toast">
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
                toast.style.backgroundColor = '#ef4444';
                toast.style.color = '#fff';
            } else {
                toast.style.backgroundColor = '#10b981';
                toast.style.color = '#070708';
            }
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        // Show error status on page
        function showPageError(message) {
            const statusEl = document.getElementById('loader-status');
            if (statusEl) {
                statusEl.style.color = '#ef4444';
                statusEl.innerHTML = '<strong>HATA:</strong> ' + escapeHtml(message) + '<br><br><span style="font-size: 11px; text-transform: none; color: #fff;">Lütfen admin panelinden çıkış yapıp tekrar giriş yapmayı deneyin.</span>';
            }
        }

        // Search localStorage for Sanctum token
        function findToken() {
            // 1. Try direct keys
            const commonKeys = ['token', 'admin_token', 'auth_token', 'user_token'];
            for (const key of commonKeys) {
                const val = localStorage.getItem(key);
                if (val && (val.includes('|') || val.length > 20)) {
                    return val;
                }
            }
            // 2. Scan all keys for Sanctum structure (number followed by pipe)
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                try {
                    const val = localStorage.getItem(key);
                    if (val && /^\d+\|[A-Za-z0-9_-]+/.test(val)) {
                        return val;
                    }
                } catch (e) {}
            }
            return null;
        }

        // Verify Admin Access
        async function verifyAdmin() {
            try {
                authToken = findToken();
                if (!authToken) {
                    showPageError("Yönetici anahtarı (token) tarayıcıda bulunamadı.");
                    setTimeout(() => {
                        window.location.href = '/panel/login';
                    }, 2000);
                    return;
                }

                const response = await fetch('/api/admin/me', {
                    headers: {
                        'Authorization': `Bearer ${authToken}`,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    const errData = await response.json().catch(() => ({}));
                    throw new Error(errData.message || `Yetkilendirme başarısız (${response.status})`);
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
                showPageError(error.message);
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
                    tableBody.innerHTML = `<tr><td colspan="4" style="text-align: center; color: rgba(255,255,255,0.2); padding: 2rem 0;">Sisteme henüz hiç lig girilmemiş.</td></tr>`;
                    return;
                }

                tableBody.innerHTML = leagues.map(league => `
                    <tr>
                        <td>
                            <img src="${league.logo_url || '/placeholder.png'}" onerror="this.src='/placeholder.png'" class="logo-img" />
                        </td>
                        <td style="font-weight: 600; color: #fff;">${escapeHtml(league.name)}</td>
                        <td style="color: rgba(255,255,255,0.6);">${escapeHtml(league.country || '-')}</td>
                        <td style="text-align: right;">
                            <button onclick="deleteLeague(${league.id})" class="btn-delete">
                                Sil
                            </button>
                        </td>
                    </tr>
                `).join('');
            } catch (error) {
                showToast(error.message, true);
            }
        }

        async function handleLeagueSubmit(e) {
            e.preventDefault();
            const name = document.getElementById('league-name').value;
            const country = document.getElementById('league-country').value;
            const logo_url = document.getElementById('league-logo').value;

            try {
                await apiRequest('/api/admin/leagues', 'POST', { name, country, logo_url, is_active: true });
                showToast('Lig başarıyla eklendi!');
                document.getElementById('create-league-form').reset();
                loadLeagues();
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
                    tableBody.innerHTML = `<tr><td colspan="4" style="text-align: center; color: rgba(255,255,255,0.2); padding: 2rem 0;">Sisteme henüz hiç takım girilmemiş.</td></tr>`;
                    return;
                }

                tableBody.innerHTML = teams.map(team => `
                    <tr>
                        <td>
                            <img src="${team.logo_url || '/placeholder.png'}" onerror="this.src='/placeholder.png'" class="logo-img" />
                        </td>
                        <td style="font-weight: 600; color: #fff;">${escapeHtml(team.name)}</td>
                        <td style="color: rgba(255,255,255,0.6);">${escapeHtml(team.league ? team.league.name : '-')}</td>
                        <td style="text-align: right;">
                            <button onclick="deleteTeam(${team.id})" class="btn-delete">
                                Sil
                            </button>
                        </td>
                    </tr>
                `).join('');
            } catch (error) {
                showToast(error.message, true);
            }
        }

        async function handleTeamSubmit(e) {
            e.preventDefault();
            const league_id = document.getElementById('team-league-id').value;
            const name = document.getElementById('team-name').value;
            const logo_url = document.getElementById('team-logo').value;

            try {
                await apiRequest('/api/admin/teams', 'POST', { league_id, name, logo_url, is_active: true });
                showToast('Takım başarıyla eklendi!');
                document.getElementById('create-team-form').reset();
                loadTeamsAndLeagues();
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

        // Initialize verification
        verifyAdmin();

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
