<?php
// Fetch Statistics
try {
    $stmt = $db->query("SELECT COUNT(*) FROM spc_inclusos");
    $totalInclusos = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) FROM spc_excluidos");
    $totalExcluidos = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT imported_at FROM import_batches ORDER BY id DESC LIMIT 1");
    $lastImport = $stmt->fetchColumn();
    $lastImportDate = $lastImport ? date('d/m/Y H:i', strtotime($lastImport)) : 'Nunca';
} catch (Exception $e) {
    $totalInclusos = 0;
    $totalExcluidos = 0;
    $lastImportDate = 'Erro';
}
?>

<div class="max-w-7xl mx-auto space-y-8">
    
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-brand-900 tracking-tight">Dashboard</h1>
            <p class="text-slate-500 mt-1">Visão geral e importação de arquivos.</p>
        </div>
        <div class="flex items-center gap-2 text-sm text-slate-500 bg-white px-4 py-2 rounded-full shadow-sm border border-slate-100">
            <i data-lucide="clock" class="w-4 h-4 text-brand-500"></i>
            Última atividade: <span class="font-medium text-brand-900"><?php echo $lastImportDate; ?></span>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Stat Card 1 -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute top-0 right-0 w-24 h-24 bg-blue-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-blue-100 text-blue-600 rounded-lg">
                        <i data-lucide="database" class="w-5 h-5"></i>
                    </div>
                    <h3 class="font-semibold text-slate-600">Total Inclusos</h3>
                </div>
                <p class="text-3xl font-bold text-brand-900"><?php echo number_format($totalInclusos, 0, ',', '.'); ?></p>
                <p class="text-xs text-slate-400 mt-1">Registros ativos na base</p>
            </div>
        </div>

        <!-- Stat Card 2 -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute top-0 right-0 w-24 h-24 bg-red-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-red-100 text-red-600 rounded-lg">
                        <i data-lucide="ban" class="w-5 h-5"></i>
                    </div>
                    <h3 class="font-semibold text-slate-600">Total Excluídos</h3>
                </div>
                <p class="text-3xl font-bold text-brand-900"><?php echo number_format($totalExcluidos, 0, ',', '.'); ?></p>
                <p class="text-xs text-slate-400 mt-1">Registros na blacklist</p>
            </div>
        </div>

        <!-- Stat Card 3 -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute top-0 right-0 w-24 h-24 bg-amber-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-amber-100 text-amber-600 rounded-lg">
                        <i data-lucide="activity" class="w-5 h-5"></i>
                    </div>
                    <h3 class="font-semibold text-slate-600">Status do Sistema</h3>
                </div>
                <p class="text-3xl font-bold text-brand-900">Ativo</p>
                <p class="text-xs text-slate-400 mt-1">Aguardando importação</p>
            </div>
        </div>
    </div>

    <!-- Recent Activity Timeline -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
        <h3 class="text-lg font-bold text-brand-900 mb-4 flex items-center gap-2">
            <i data-lucide="history" class="w-5 h-5 text-blue-500"></i>
            Atividade Recente
        </h3>
        <div class="relative pl-4 border-l-2 border-slate-100 space-y-6">
            <?php
            try {
                $stmt = $db->query("SELECT * FROM import_batches ORDER BY id DESC LIMIT 5");
                $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $activities = [];
            }

            if (empty($activities)): ?>
                <p class="text-sm text-slate-400 italic">Nenhuma atividade recente.</p>
            <?php else: 
                foreach ($activities as $activity): 
                    $date = date('d/m/Y', strtotime($activity['imported_at']));
                    $time = date('H:i', strtotime($activity['imported_at']));
            ?>
                <div class="relative">
                    <div class="absolute -left-[21px] top-1 w-3 h-3 bg-blue-500 rounded-full border-2 border-white shadow-sm"></div>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                        <div>
                            <p class="text-sm font-medium text-slate-800">
                                Importação de <span class="text-blue-600 font-semibold"><?php echo htmlspecialchars($activity['filename']); ?></span>
                            </p>
                            <p class="text-xs text-slate-500">Tipo: <?php echo ucfirst($activity['type']); ?></p>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-medium text-slate-600 bg-slate-100 px-2 py-1 rounded-full">
                                <?php echo $date; ?> às <?php echo $time; ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-xl shadow-sm flex items-start gap-3 animate-fade-in-up">
            <i data-lucide="info" class="w-5 h-5 text-blue-600 mt-0.5"></i>
            <div>
                <p class="text-sm text-blue-700 font-medium"><?php echo $message; ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Upload Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        
        <?php

        $cards = [
            [
                'title' => '1. SPC Inclusos',
                'subtitle' => 'Adicionar (Append) • Excel',
                'icon' => 'file-spreadsheet',
                'color' => 'blue',
                'name' => 'spc_file',
                'accept' => '.xlsx, .xls, .csv',
                'btn' => 'Importar SPC',
                'tooltip' => 'Planilha Excel importada após serem feitas as inclusões',
                'model' => 'modelo_spc_inclusos.xlsx'
            ],
            [
                'title' => '2. Parcelas em Aberto',
                'subtitle' => 'Substituição Completa • Excel',
                'icon' => 'banknote',
                'color' => 'emerald',
                'name' => 'parcelas_file',
                'accept' => '.xlsx, .xls, .csv',
                'btn' => 'Importar Parcelas',
                'tooltip' => 'Planilha baixada no Suporte TI (Financeiro-Parcelas em Aberto)',
                'model' => 'modelo_parcelas_aberto.xlsx'
            ],
            [
                'title' => '3. PDD Perdas',
                'subtitle' => 'Adicionar (Append) • Excel',
                'icon' => 'trending-down',
                'color' => 'orange',
                'name' => 'pdd_perdas_file',
                'accept' => '.xlsx, .xls, .csv',
                'btn' => 'Importar PDD Perdas',
                'tooltip' => 'Após serem feitas as baixas mensais de parcelas que foram para PDD, enviar planilha por essa opção, PF, PJ e Demitidos.',
                'model' => 'modelo_pdd_perdas.xlsx'
            ],
            [
                'title' => '4. PDD Pagos',
                'subtitle' => 'Adicionar (Append) • PDF',
                'icon' => 'file-text',
                'color' => 'purple',
                'name' => 'pdd_pagos_file',
                'accept' => '.pdf',
                'btn' => 'Importar PDD Pagos',
                'tooltip' => 'Relatorio baixado no Piramide (Financeiro-Contas a Receber-Relatorios-Por NDO) sendo CR0117 para PF e CR0118 para PJ',
                'model' => 'modelo_pdd_pagos.pdf'
            ],
            [
                'title' => '5. Lista de Excluídos',
                'subtitle' => 'Blacklist • Excel',
                'icon' => 'user-x',
                'color' => 'slate',
                'name' => 'spc_excluidos_file',
                'accept' => '.xlsx, .xls, .csv',
                'btn' => 'Importar Excluídos',
                'tooltip' => 'Após fazer a exclusão, importar planilha por esse caminho, para atualizar a lista a serem removidas do SPC',
                'model' => 'modelo_lista_excluidos.xlsx'
            ]
        ];

        foreach ($cards as $card): 
            $colorClass = "text-{$card['color']}-600 bg-{$card['color']}-50 border-{$card['color']}-100";
            $btnClass = "bg-{$card['color']}-600 hover:bg-{$card['color']}-700";
            if ($card['color'] === 'slate') {
                $colorClass = "text-slate-600 bg-slate-100 border-slate-200";
                $btnClass = "bg-slate-800 hover:bg-slate-900";
            }
        ?>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-visible hover:shadow-md transition-all duration-300 group relative">
            
            <!-- Tooltip -->
            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-3 w-64 p-3 bg-slate-800 text-white text-xs rounded-lg shadow-xl opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-50 text-center leading-relaxed">
                <?php echo $card['tooltip']; ?>
                <div class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1 border-4 border-transparent border-t-slate-800"></div>
            </div>

            <div class="p-6 border-b border-slate-50">
                <div class="flex items-start gap-3">
                    <div class="p-2.5 rounded-xl <?php echo $colorClass; ?> shrink-0">
                        <i data-lucide="<?php echo $card['icon']; ?>" class="w-5 h-5"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-slate-800"><?php echo $card['title']; ?></h3>
                        <p class="text-xs text-slate-400 font-medium mb-2"><?php echo $card['subtitle']; ?></p>
                        
                        <!-- Model Link -->
                        <a href="<?php echo $card['model']; ?>" download class="inline-flex items-center gap-1.5 text-xs font-medium text-blue-500 hover:text-blue-600 hover:underline transition-colors">
                            <i data-lucide="download" class="w-3 h-3"></i>
                            Baixar Modelo
                        </a>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <form action="index.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div class="relative w-full">
                        <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-slate-200 rounded-xl cursor-pointer bg-slate-50/50 hover:bg-blue-50/50 hover:border-blue-300 transition-all group-hover:border-slate-300" id="dropzone-<?php echo $card['name']; ?>">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6 text-center px-4">
                                <i data-lucide="upload-cloud" class="w-8 h-8 text-slate-300 mb-2 group-hover:text-blue-400 transition-colors"></i>
                                <p class="text-sm text-slate-500 font-medium"><span class="text-blue-500">Clique</span> ou arraste</p>
                                <p class="text-xs text-slate-400 mt-1 truncate max-w-full" id="name-<?php echo $card['name']; ?>">Nenhum arquivo selecionado</p>
                            </div>
                            <input type="file" name="<?php echo $card['name']; ?>" class="hidden" accept="<?php echo $card['accept']; ?>" required onchange="handleFileSelect(this, 'name-<?php echo $card['name']; ?>', 'dropzone-<?php echo $card['name']; ?>')" />
                        </label>
                    </div>
                    <button type="submit" class="w-full py-2.5 px-4 <?php echo $btnClass; ?> text-white rounded-xl transition-all shadow-sm hover:shadow-md font-medium text-sm flex items-center justify-center gap-2">
                        <span><?php echo $card['btn']; ?></span>
                        <i data-lucide="arrow-right" class="w-4 h-4 opacity-70"></i>
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
        
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="fixed inset-0 bg-brand-900/80 backdrop-blur-sm hidden flex items-center justify-center z-[60] transition-opacity opacity-0">
    <div class="bg-white p-8 rounded-2xl shadow-2xl flex flex-col items-center transform scale-95 transition-all duration-300" id="loadingModal">
        <div class="relative mb-4">
            <div class="w-16 h-16 border-4 border-slate-100 rounded-full"></div>
            <div class="w-16 h-16 border-4 border-blue-500 rounded-full animate-spin absolute top-0 left-0 border-t-transparent"></div>
        </div>
        <h3 class="text-xl font-bold text-brand-900">Processando</h3>
        <p class="text-slate-500 mt-2 text-sm">Aguarde enquanto importamos seus dados...</p>
    </div>
</div>

<script>
    function handleFileSelect(input, nameId, zoneId) {
        const fileName = input.files[0] ? input.files[0].name : 'Nenhum arquivo selecionado';
        const nameEl = document.getElementById(nameId);
        const zoneEl = document.getElementById(zoneId);
        
        nameEl.textContent = fileName;
        
        if (input.files[0]) {
            nameEl.classList.add('text-brand-900', 'font-semibold');
            nameEl.classList.remove('text-slate-400');
            zoneEl.classList.add('border-blue-500', 'bg-blue-50');
            zoneEl.classList.remove('border-slate-200', 'bg-slate-50/50');
        } else {
            nameEl.classList.remove('text-brand-900', 'font-semibold');
            nameEl.classList.add('text-slate-400');
            zoneEl.classList.remove('border-blue-500', 'bg-blue-50');
            zoneEl.classList.add('border-slate-200', 'bg-slate-50/50');
        }
    }

    // Drag and Drop Logic
    document.querySelectorAll('label[id^="dropzone-"]').forEach(zone => {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            zone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            zone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            zone.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            zone.classList.add('border-blue-400', 'bg-blue-50');
        }

        function unhighlight(e) {
            zone.classList.remove('border-blue-400', 'bg-blue-50');
        }

        zone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            const input = zone.querySelector('input[type="file"]');
            
            input.files = files;
            const nameId = zone.querySelector('p[id^="name-"]').id;
            handleFileSelect(input, nameId, zone.id);
        }
    });

    // Loading Overlay
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const overlay = document.getElementById('loadingOverlay');
            const modal = document.getElementById('loadingModal');
            overlay.classList.remove('hidden');
            // Small delay to allow display:block to apply before opacity transition
            setTimeout(() => {
                overlay.classList.remove('opacity-0');
                modal.classList.remove('scale-95');
                modal.classList.add('scale-100');
            }, 10);
        });
    });
</script>
