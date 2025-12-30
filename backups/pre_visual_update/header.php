<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Controle SPC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0f172a', // Slate 900
                        secondary: '#3b82f6', // Blue 500
                        accent: '#0ea5e9', // Sky 500
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
    <div class="min-h-screen flex flex-col">
        <!-- Navbar -->
        <nav class="bg-primary text-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <span class="text-xl font-bold tracking-tight text-blue-400">SPC</span>
                        <span class="text-xl font-bold tracking-tight ml-1">Control</span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="index.php?page=dashboard" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-slate-800 transition-colors">Dashboard</a>
                        <a href="index.php?page=report" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-slate-800 transition-colors">Relatórios</a>
                        <a href="index.php?page=admin" class="px-3 py-2 rounded-md text-sm font-medium bg-slate-800 text-blue-400 hover:bg-slate-700 transition-colors">Admin</a>
                    </div>
                </div>
            </div>
        </nav>
        
        <main class="flex-grow container mx-auto px-4 sm:px-6 lg:px-8 py-8">
