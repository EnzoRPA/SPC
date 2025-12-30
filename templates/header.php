<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPC Control | Sistema de Gestão</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .glass-nav {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            900: '#0B1120', // Deep Navy
                            800: '#151F32', // Lighter Navy
                            700: '#334155',
                            500: '#3B82F6', // Primary Blue
                            400: '#60A5FA',
                            accent: '#F59E0B', // Amber/Gold
                        }
                    },
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="h-full antialiased text-slate-800 bg-slate-50 flex flex-col">
    <!-- Navbar -->
    <nav class="glass-nav fixed w-full z-50 border-b border-white/10 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <!-- Logo -->
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-brand-900 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/20">
                        <i data-lucide="shield-check" class="text-white w-6 h-6"></i>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xl font-bold tracking-tight text-white leading-none">SPC <span class="text-blue-400">Control</span></span>
                        <span class="text-[10px] font-medium text-slate-400 uppercase tracking-wider">Gestão de Inadimplência</span>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="hidden md:flex items-center space-x-1">
                    <a href="index.php?page=dashboard" class="group flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium text-slate-300 hover:text-white hover:bg-white/5 transition-all <?php echo ($page === 'dashboard') ? 'bg-white/10 text-white shadow-inner' : ''; ?>">
                        <i data-lucide="layout-dashboard" class="w-4 h-4 <?php echo ($page === 'dashboard') ? 'text-blue-400' : 'text-slate-500 group-hover:text-blue-400'; ?> transition-colors"></i>
                        Dashboard
                    </a>
                    <a href="index.php?page=report" class="group flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium text-slate-300 hover:text-white hover:bg-white/5 transition-all <?php echo ($page === 'report') ? 'bg-white/10 text-white shadow-inner' : ''; ?>">
                        <i data-lucide="file-bar-chart" class="w-4 h-4 <?php echo ($page === 'report') ? 'text-blue-400' : 'text-slate-500 group-hover:text-blue-400'; ?> transition-colors"></i>
                        Relatórios
                    </a>
                    <div class="h-6 w-px bg-white/10 mx-2"></div>
                    <a href="index.php?page=admin" class="group flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium text-slate-300 hover:text-white hover:bg-white/5 transition-all <?php echo ($page === 'admin') ? 'bg-white/10 text-white shadow-inner' : ''; ?>">
                        <i data-lucide="settings" class="w-4 h-4 <?php echo ($page === 'admin') ? 'text-blue-400' : 'text-slate-500 group-hover:text-blue-400'; ?> transition-colors"></i>
                        Administração
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <?php if ($page !== 'admin'): ?>
    <main class="flex-grow container mx-auto px-4 sm:px-6 lg:px-8 py-8 pt-28 animate-fade-in-up">
    <?php else: ?>
    <main class="flex-grow pt-20 animate-fade-in-up">
    <?php endif; ?>
    <script>
        // Initialize Lucide Icons
        window.addEventListener('load', function() {
            lucide.createIcons();
        });
    </script>
    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-up {
            animation: fadeInUp 0.5s ease-out forwards;
        }
    </style>
