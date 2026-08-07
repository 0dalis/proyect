<!-- ===== NAVBAR PÚBLICO COMPARTIDO ===== -->
<header class="fixed top-0 left-0 right-0 bg-white/90 dark:bg-slate-950/90 backdrop-blur-md border-b border-slate-200 dark:border-slate-800/80 z-50 transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="{{ route('landing') }}" class="flex items-center gap-2.5">
                <div class="w-8 h-8 bg-gradient-to-br from-brand-500 to-indigo-700 rounded-lg flex items-center justify-center shadow-lg shadow-brand-500/20">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
                <span class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Asist<span class="text-brand-600 dark:text-brand-400">Control</span></span>
            </a>

            <nav class="hidden md:flex items-center gap-6 text-xs font-medium text-slate-500 dark:text-slate-400">
                <a href="{{ route('landing') }}#servicios" class="hover:text-slate-900 dark:hover:text-white transition-colors">Servicios</a>
                <a href="{{ route('sistema') }}" class="hover:text-slate-900 dark:hover:text-white transition-colors">Sistema</a>
                <a href="{{ route('landing') }}#planes" class="hover:text-slate-900 dark:hover:text-white transition-colors">Planes</a>
                <a href="{{ route('planes-detalle') }}" class="hover:text-slate-900 dark:hover:text-white transition-colors">Comparativa</a>
            </nav>

            <div class="hidden md:flex items-center gap-3">
                <a href="{{ route('acceso') }}" class="text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white px-3 py-2 transition-colors">
                    Iniciar Sesión
                </a>
                <a href="{{ route('acceso') }}#registro" class="inline-flex items-center justify-center px-4 py-2 bg-brand-600 hover:bg-brand-500 text-white text-xs font-semibold rounded-lg shadow-sm shadow-brand-600/20 transition-all">
                    Probar Gratis
                </a>
                <button id="themeToggle" class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-white transition-all" title="Cambiar tema" aria-label="Cambiar tema claro/oscuro">
                    <svg id="sunIcon" class="w-4 h-4 text-amber-500 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <svg id="moonIcon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                </button>
            </div>

            <!-- Mobile menu button -->
            <button id="mobileMenuBtn" class="md:hidden p-2 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white focus:outline-none" aria-label="Menú">
                <svg id="menuIconOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <svg id="menuIconClose" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Mobile menu -->
        <div id="mobileMenu" class="md:hidden hidden pb-6 pt-2 border-t border-slate-200 dark:border-slate-800/60 space-y-2">
            <a href="{{ route('landing') }}#servicios" class="block px-3 py-2 rounded-md text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-900 hover:text-slate-900 dark:hover:text-white">Servicios</a>
            <a href="{{ route('sistema') }}" class="block px-3 py-2 rounded-md text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-900 hover:text-slate-900 dark:hover:text-white">Sistema</a>
            <a href="{{ route('landing') }}#planes" class="block px-3 py-2 rounded-md text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-900 hover:text-slate-900 dark:hover:text-white">Planes</a>
            <a href="{{ route('planes-detalle') }}" class="block px-3 py-2 rounded-md text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-900 hover:text-slate-900 dark:hover:text-white">Comparativa</a>
            <div class="pt-3 border-t border-slate-200 dark:border-slate-800/80 flex flex-col gap-2">
                <a href="{{ route('acceso') }}" class="block px-3 py-2 text-center text-sm font-semibold text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-800 rounded-lg">Iniciar Sesión</a>
                <a href="{{ route('acceso') }}#registro" class="block px-3 py-2 text-center text-sm font-semibold text-white bg-brand-600 rounded-lg">Comenzar gratis</a>
                <button id="themeToggleMobile" class="block px-3 py-2 text-center text-sm font-semibold text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-800 rounded-lg">
                    <span id="themeLabelMobile">Modo Oscuro</span>
                </button>
            </div>
        </div>
    </div>
</header>

<script>
(function() {
    const html = document.documentElement;
    const sunIcon = document.getElementById('sunIcon');
    const moonIcon = document.getElementById('moonIcon');
    const themeLabelMobile = document.getElementById('themeLabelMobile');

    function applyTheme(isDark) {
        if (isDark) {
            html.classList.add('dark');
            if (sunIcon) { sunIcon.classList.remove('hidden'); }
            if (moonIcon) { moonIcon.classList.add('hidden'); }
            if (themeLabelMobile) { themeLabelMobile.textContent = 'Modo Claro'; }
            localStorage.setItem('theme', 'dark');
        } else {
            html.classList.remove('dark');
            if (sunIcon) { sunIcon.classList.add('hidden'); }
            if (moonIcon) { moonIcon.classList.remove('hidden'); }
            if (themeLabelMobile) { themeLabelMobile.textContent = 'Modo Oscuro'; }
            localStorage.setItem('theme', 'light');
        }
    }

    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    applyTheme(savedTheme === 'dark' || (!savedTheme && prefersDark));

    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            applyTheme(!html.classList.contains('dark'));
        });
    }

    const themeToggleMobile = document.getElementById('themeToggleMobile');
    if (themeToggleMobile) {
        themeToggleMobile.addEventListener('click', function() {
            applyTheme(!html.classList.contains('dark'));
        });
    }

    // Mobile menu
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileMenu = document.getElementById('mobileMenu');
    const menuIconOpen = document.getElementById('menuIconOpen');
    const menuIconClose = document.getElementById('menuIconClose');
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
            if (menuIconOpen) menuIconOpen.classList.toggle('hidden');
            if (menuIconClose) menuIconClose.classList.toggle('hidden');
        });
    }
})();
</script>
