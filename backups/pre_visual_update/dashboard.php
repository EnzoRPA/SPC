<div class="max-w-6xl mx-auto">
    <div class="text-center mb-10">
        <h1 class="text-3xl font-bold text-slate-900">Importação de Arquivos</h1>
        <p class="mt-2 text-slate-600">Importe cada arquivo independentemente. O sistema fará o cruzamento automático nos relatórios.</p>
    </div>

    <?php if (!empty($message)): ?>
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded shadow-sm">
            <div class="flex">
                <div class="ml-3">
                    <p class="text-sm text-blue-700"><?php echo $message; ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- SPC Inclusos -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden border border-slate-100">
            <div class="p-6 border-b border-slate-100 bg-slate-50">
                <h3 class="text-lg font-semibold text-slate-800">1. SPC Inclusos</h3>
                <p class="text-xs text-slate-500">Adicionar (Append) • Excel</p>
            </div>
            <div class="p-6">
                <form action="index.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div class="flex items-center justify-center w-full">
                        <label class="flex flex-col w-full h-32 border-2 border-dashed hover:bg-gray-50 border-gray-300 rounded-lg cursor-pointer">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <p class="text-sm text-gray-500"><span class="font-semibold">Clique para selecionar</span></p>
                                <p class="text-xs text-gray-400" id="spc_name">Nenhum arquivo</p>
                            </div>
                            <input type="file" name="spc_file" class="hidden" onchange="updateName(this, 'spc_name')" accept=".xlsx, .xls, .csv" required />
                        </label>
                    </div>
                    <button type="submit" class="w-full py-2 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors text-sm font-medium">
                        Importar SPC
                    </button>
                </form>
            </div>
        </div>

        <!-- Parcelas -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden border border-slate-100">
            <div class="p-6 border-b border-slate-100 bg-slate-50">
                <h3 class="text-lg font-semibold text-slate-800">2. Parcelas em Aberto</h3>
                <p class="text-xs text-slate-500">Substituição Completa • Excel</p>
            </div>
            <div class="p-6">
                <form action="index.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div class="flex items-center justify-center w-full">
                        <label class="flex flex-col w-full h-32 border-2 border-dashed hover:bg-gray-50 border-gray-300 rounded-lg cursor-pointer">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <p class="text-sm text-gray-500"><span class="font-semibold">Clique para selecionar</span></p>
                                <p class="text-xs text-gray-400" id="parcelas_name">Nenhum arquivo</p>
                            </div>
                            <input type="file" name="parcelas_file" class="hidden" onchange="updateName(this, 'parcelas_name')" accept=".xlsx, .xls, .csv" required />
                        </label>
                    </div>
                    <button type="submit" class="w-full py-2 px-4 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors text-sm font-medium">
                        Importar Parcelas
                    </button>
                </form>
            </div>
        </div>

        <!-- PDD Perdas -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden border border-slate-100">
            <div class="p-6 border-b border-slate-100 bg-slate-50">
                <h3 class="text-lg font-semibold text-slate-800">3. PDD Perdas</h3>
                <p class="text-xs text-slate-500">Adicionar (Append) • Excel</p>
            </div>
            <div class="p-6">
                <form action="index.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div class="flex items-center justify-center w-full">
                        <label class="flex flex-col w-full h-32 border-2 border-dashed hover:bg-gray-50 border-gray-300 rounded-lg cursor-pointer">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <p class="text-sm text-gray-500"><span class="font-semibold">Clique para selecionar</span></p>
                                <p class="text-xs text-gray-400" id="perdas_name">Nenhum arquivo</p>
                            </div>
                            <input type="file" name="pdd_perdas_file" class="hidden" onchange="updateName(this, 'perdas_name')" accept=".xlsx, .xls, .csv" required />
                        </label>
                    </div>
                    <button type="submit" class="w-full py-2 px-4 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition-colors text-sm font-medium">
                        Importar PDD Perdas
                    </button>
                </form>
            </div>
        </div>

        <!-- PDD Pagos -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden border border-slate-100">
            <div class="p-6 border-b border-slate-100 bg-slate-50">
                <h3 class="text-lg font-semibold text-slate-800">4. PDD Pagos</h3>
                <p class="text-xs text-slate-500">Adicionar (Append) • PDF</p>
            </div>
            <div class="p-6">
                <form action="index.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div class="flex items-center justify-center w-full">
                        <label class="flex flex-col w-full h-32 border-2 border-dashed hover:bg-gray-50 border-gray-300 rounded-lg cursor-pointer">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <p class="text-sm text-gray-500"><span class="font-semibold">Clique para selecionar</span></p>
                                <p class="text-xs text-gray-400" id="pagos_name">Nenhum arquivo</p>
                            </div>
                            <input type="file" name="pdd_pagos_file" class="hidden" onchange="updateName(this, 'pagos_name')" accept=".pdf" required />
                        </label>
                    </div>
                    <button type="submit" class="w-full py-2 px-4 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors text-sm font-medium">
                        Importar PDD Pagos
                    </button>
                </form>
            </div>
        </div>

        <!-- Lista de Excluídos -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden border border-slate-100 md:col-span-2 lg:col-span-1 lg:col-start-2">
            <div class="p-6 border-b border-slate-100 bg-slate-50">
                <h3 class="text-lg font-semibold text-slate-800">5. Lista de Excluídos</h3>
                <p class="text-xs text-slate-500">Blacklist (Remove do Relatório) • Excel</p>
            </div>
            <div class="p-6">
                <form action="index.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div class="flex items-center justify-center w-full">
                        <label class="flex flex-col w-full h-32 border-2 border-dashed hover:bg-gray-50 border-gray-300 rounded-lg cursor-pointer">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <p class="text-sm text-gray-500"><span class="font-semibold">Clique para selecionar</span></p>
                                <p class="text-xs text-gray-400" id="excluidos_name">Nenhum arquivo</p>
                            </div>
                            <input type="file" name="spc_excluidos_file" class="hidden" onchange="updateName(this, 'excluidos_name')" accept=".xlsx, .xls, .csv" required />
                        </label>
                    </div>
                    <button type="submit" class="w-full py-2 px-4 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors text-sm font-medium">
                        Importar Lista de Excluídos
                    </button>
                </form>
            </div>
            </div>
        </div>

        <!-- Atualizar SPC Inclusos -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden border border-slate-100 md:col-span-2 lg:col-span-1">
            <div class="p-6 border-b border-slate-100 bg-slate-50">
                <h3 class="text-lg font-semibold text-slate-800">6. Atualizar SPC Inclusos</h3>
                <p class="text-xs text-slate-500">Confirmação de Inclusão (Append) • Excel</p>
            </div>
            <div class="p-6">
                <form action="index.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div class="flex items-center justify-center w-full">
                        <label class="flex flex-col w-full h-32 border-2 border-dashed hover:bg-gray-50 border-gray-300 rounded-lg cursor-pointer">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <p class="text-sm text-gray-500"><span class="font-semibold">Clique para selecionar</span></p>
                                <p class="text-xs text-gray-400" id="atualizacao_name">Nenhum arquivo</p>
                            </div>
                            <input type="file" name="spc_atualizacao_file" class="hidden" onchange="updateName(this, 'atualizacao_name')" accept=".xlsx, .xls, .csv" required />
                        </label>
                    </div>
                    <button type="submit" class="w-full py-2 px-4 bg-teal-600 hover:bg-teal-700 text-white rounded-lg transition-colors text-sm font-medium">
                        Atualizar Inclusos
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden flex items-center justify-center z-50 transition-opacity">
    <div class="bg-white p-8 rounded-2xl shadow-2xl flex flex-col items-center transform scale-100 transition-transform">
        <div class="animate-spin rounded-full h-16 w-16 border-4 border-slate-200 border-t-blue-600 mb-4"></div>
        <h3 class="text-xl font-bold text-slate-800">Processando Arquivo</h3>
        <p class="text-slate-500 mt-2">Isso pode levar alguns segundos...</p>
    </div>
</div>

<script>
    function updateName(input, id) {
        var fileName = input.files[0] ? input.files[0].name : 'Nenhum arquivo';
        document.getElementById(id).textContent = fileName;
    }

    // Show loading overlay on form submit
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function() {
            const overlay = document.getElementById('loadingOverlay');
            overlay.classList.remove('hidden');
        });
    });
</script>
