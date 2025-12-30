<?php
// Helper para corrigir data 00XX -> 20XX
function formatDate($date) {
    if (empty($date)) return '-';
    $ts = strtotime($date);
    if (!$ts) return '-';
    
    $year = date('Y', $ts);
    if ($year < 1000) {
        // Fix year 00XX -> 20XX
        $newYear = intval($year) + 2000;
        return date('d/m/', $ts) . $newYear;
    }
    
    return date('d/m/Y', $ts);
}

// Cálculos de Totais
$totalExclusaoQtd = count($exclusao);
$totalExclusaoValor = 0;
foreach ($exclusao as $e) $totalExclusaoValor += ($e['valor_debito'] ?? $e['valor'] ?? 0);

$totalInclusaoQtd = count($inclusao);
$totalInclusaoValor = 0;
foreach ($inclusao as $i) $totalInclusaoValor += ($i['debito'] ?? $i['valor'] ?? 0);
?>

<div class="space-y-8">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Relatório Avançado (Fase 2)</h1>
            <p class="mt-1 text-slate-600">Análise cruzada de SPC, Parcelas e PDD.</p>
        </div>
    <div class="flex space-x-3">
            <a href="index.php?page=dashboard" class="inline-flex items-center px-4 py-2 border border-slate-300 rounded-md shadow-sm text-sm font-medium text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Nova Importação
            </a>
            <a href="run_cleanup.php" target="_blank" class="inline-flex items-center px-4 py-2 border border-orange-300 rounded-md shadow-sm text-sm font-medium text-orange-700 bg-orange-50 hover:bg-orange-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                🧹 Limpar Duplicatas
            </a>
            
            <!-- Export Form with Date Filter -->
            <form action="export.php" method="GET" class="flex items-center gap-2 bg-white p-1 rounded-lg border border-slate-200 shadow-sm">
                <div class="flex items-center gap-1 px-2">
                    <span class="text-xs text-slate-500 font-medium">Vencimento:</span>
                    <input type="date" name="data_inicio" class="text-xs border-slate-200 rounded focus:ring-blue-500 focus:border-blue-500 py-1">
                    <span class="text-xs text-slate-400">até</span>
                    <input type="date" name="data_fim" class="text-xs border-slate-200 rounded focus:ring-blue-500 focus:border-blue-500 py-1">
                </div>
                <button type="submit" class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <i data-lucide="download" class="w-4 h-4 mr-1"></i> Exportar
                </button>
            </form>
        </div>
    </div>

    <!-- Resumo Geral -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Card Exclusão -->
        <div class="bg-red-50 border border-red-200 rounded-lg p-6 flex justify-between items-center shadow-sm">
            <div>
                <h4 class="text-red-800 text-lg font-semibold">Total Para Exclusão</h4>
                <p class="text-red-600 text-sm mt-1">Registros a remover do SPC</p>
            </div>
            <div class="text-right">
                <div class="text-3xl font-bold text-red-700"><?php echo number_format($totalExclusaoQtd, 0, ',', '.'); ?></div>
                <div class="text-red-600 font-medium">R$ <?php echo number_format($totalExclusaoValor, 2, ',', '.'); ?></div>
            </div>
        </div>

        <!-- Card Inclusão -->
        <div class="bg-green-50 border border-green-200 rounded-lg p-6 flex justify-between items-center shadow-sm">
            <div>
                <h4 class="text-green-800 text-lg font-semibold">Total Para Inclusão</h4>
                <p class="text-green-600 text-sm mt-1">Novos registros para o SPC</p>
            </div>
            <div class="text-right">
                <div class="text-3xl font-bold text-green-700"><?php echo number_format($totalInclusaoQtd, 0, ',', '.'); ?></div>
                <div class="text-green-600 font-medium">R$ <?php echo number_format($totalInclusaoValor, 2, ',', '.'); ?></div>
            </div>
        </div>
    </div>

    <!-- Filtro Global -->
    <div class="bg-white p-4 rounded-lg shadow-sm border border-slate-200">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                </svg>
            </div>
            <input type="text" id="tableSearch" class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-md leading-5 bg-white placeholder-slate-500 focus:outline-none focus:placeholder-slate-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Buscar por CPF, Nome, Contrato...">
        </div>
    </div>

    <!-- Tables Section -->
    <div class="flex flex-col space-y-12">
        <!-- Para Exclusão -->
        <div class="bg-white shadow-lg rounded-lg overflow-hidden border border-slate-200 flex flex-col">
            <div class="px-6 py-4 border-b border-slate-200 bg-red-50 flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-medium leading-6 text-red-800">Para Exclusão do SPC</h3>
                    <p class="mt-1 text-sm text-red-600">Parcelas que não estão em aberto ou são PDDs já pagos.</p>
                </div>
                <select class="rows-per-page border-red-300 text-red-800 text-sm rounded-md focus:ring-red-500 focus:border-red-500" data-target="table-exclusao">
                    <option value="10">10 por pág.</option>
                    <option value="50">50 por pág.</option>
                    <option value="100">100 por pág.</option>
                    <option value="all">Todos</option>
                </select>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 search-table" id="table-exclusao">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="sortable px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider cursor-pointer hover:bg-slate-100" data-sort="cpf">CPF/CNPJ <span class="sort-arrow">↕</span></th>
                            <th scope="col" class="sortable px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider cursor-pointer hover:bg-slate-100" data-sort="contrato">Contrato <span class="sort-arrow">↕</span></th>
                            <th scope="col" class="sortable px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider cursor-pointer hover:bg-slate-100" data-sort="venda">Venda <span class="sort-arrow">↕</span></th>
                            <th scope="col" class="sortable px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider cursor-pointer hover:bg-slate-100" data-sort="vencimento">Vencimento <span class="sort-arrow">↕</span></th>
                            <th scope="col" class="sortable px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider cursor-pointer hover:bg-slate-100" data-sort="data_inclusao">Data Inclusão <span class="sort-arrow">↕</span></th>
                            <th scope="col" class="sortable px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider cursor-pointer hover:bg-slate-100" data-sort="valor">Valor <span class="sort-arrow">↕</span></th>
                            <th scope="col" class="sortable px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider cursor-pointer hover:bg-slate-100" data-sort="motivo">Motivo <span class="sort-arrow">↕</span></th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <?php if (empty($exclusao)): ?>
                            <tr><td colspan="8" class="px-6 py-4 text-center text-sm text-slate-500">Nenhum registro encontrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($exclusao as $row): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900"><?php echo !empty($row['cpf_cnpj']) ? htmlspecialchars($row['cpf_cnpj']) : '-'; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?php echo !empty($row['contrato']) ? htmlspecialchars($row['contrato']) : '-'; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?php echo !empty($row['venda']) ? htmlspecialchars($row['venda']) : '-'; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?php echo formatDate($row['vencimento']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?php echo formatDate($row['data_inclusao']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">R$ <?php echo number_format($row['valor_debito'] ?? $row['valor'], 2, ',', '.'); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                    <?php 
                                        $badgeClass = 'bg-yellow-100 text-yellow-800'; // Default: Sem Parcela
                                        if (($row['motivo'] ?? '') == 'PDD PAGO') {
                                            $badgeClass = 'bg-green-100 text-green-800';
                                        } elseif (($row['motivo'] ?? '') == 'MAIS DE 5 ANOS') {
                                            $badgeClass = 'bg-purple-100 text-purple-800';
                                        } elseif (($row['motivo'] ?? '') == 'CPF Divergente') {
                                            $badgeClass = 'bg-orange-100 text-orange-800';
                                        }
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $badgeClass; ?>">
                                        <?php echo !empty($row['motivo']) ? htmlspecialchars($row['motivo']) : '-'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                    <button onclick="deleteRecord(<?php echo $row['id']; ?>, 'spc_inclusos', this)" class="text-red-600 hover:text-red-900 font-medium hover:underline">
                                        Excluir
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination Controls -->
            <div class="px-4 py-3 border-t border-slate-200 bg-slate-50 flex justify-between items-center pagination-controls" data-target="table-exclusao">
                <span class="text-sm text-slate-600 page-info">Mostrando 0-0 de 0</span>
                <div class="space-x-1">
                    <button class="prev-page px-3 py-1 border rounded bg-white text-slate-600 hover:bg-slate-100 disabled:opacity-50">Anterior</button>
                    <button class="next-page px-3 py-1 border rounded bg-white text-slate-600 hover:bg-slate-100 disabled:opacity-50">Próxima</button>
                </div>
            </div>
        </div>

        <!-- Para Inclusão -->
        <div class="bg-white shadow-lg rounded-lg overflow-hidden border border-slate-200 flex flex-col">
            <div class="px-6 py-4 border-b border-slate-200 bg-green-50 flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-medium leading-6 text-green-800">Para Inclusão no SPC</h3>
                    <p class="mt-1 text-sm text-green-600">Dívida em aberto e não paga.</p>
                </div>
                <select class="rows-per-page border-green-300 text-green-800 text-sm rounded-md focus:ring-green-500 focus:border-green-500" data-target="table-inclusao">
                    <option value="10">10 por pág.</option>
                    <option value="50">50 por pág.</option>
                    <option value="100">100 por pág.</option>
                    <option value="all">Todos</option>
                </select>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 search-table" id="table-inclusao">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="sortable px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider cursor-pointer hover:bg-slate-100" data-sort="cpf">CPF/CNPJ <span class="sort-arrow">↕</span></th>
                            <th scope="col" class="sortable px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider cursor-pointer hover:bg-slate-100" data-sort="nome">Nome <span class="sort-arrow">↕</span></th>
                            <th scope="col" class="sortable px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider cursor-pointer hover:bg-slate-100" data-sort="contrato">Contrato <span class="sort-arrow">↕</span></th>
                            <th scope="col" class="sortable px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider cursor-pointer hover:bg-slate-100" data-sort="vencimento">Vencimento <span class="sort-arrow">↕</span></th>
                            <th scope="col" class="sortable px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider cursor-pointer hover:bg-slate-100" data-sort="valor">Valor <span class="sort-arrow">↕</span></th>
                            <th scope="col" class="sortable px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider cursor-pointer hover:bg-slate-100" data-sort="motivo">Motivo <span class="sort-arrow">↕</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <?php if (empty($inclusao)): ?>
                            <tr><td colspan="6" class="px-6 py-4 text-center text-sm text-slate-500">Nenhum registro encontrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($inclusao as $row): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900"><?php echo !empty($row['cpf_cnpj']) ? htmlspecialchars($row['cpf_cnpj']) : '-'; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?php $nome = $row['contratante'] ?? $row['nome'] ?? ''; echo !empty($nome) ? htmlspecialchars($nome) : '-'; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?php echo !empty($row['contrato']) ? htmlspecialchars($row['contrato']) : '-'; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?php $venc = $row['vencimento'] ?? $row['data_vencimento']; echo formatDate($venc); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">R$ <?php echo number_format($row['debito'] ?? $row['valor'], 2, ',', '.'); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo ($row['motivo'] ?? '') == 'PDD PERDAS' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'; ?>">
                                        <?php echo !empty($row['motivo']) ? htmlspecialchars($row['motivo']) : 'EM ABERTO'; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination Controls -->
            <div class="px-4 py-3 border-t border-slate-200 bg-slate-50 flex justify-between items-center pagination-controls" data-target="table-inclusao">
                <span class="text-sm text-slate-600 page-info">Mostrando 0-0 de 0</span>
                <div class="space-x-1">
                    <button class="prev-page px-3 py-1 border rounded bg-white text-slate-600 hover:bg-slate-100 disabled:opacity-50">Anterior</button>
                    <button class="next-page px-3 py-1 border rounded bg-white text-slate-600 hover:bg-slate-100 disabled:opacity-50">Próxima</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Search Functionality
    document.getElementById('tableSearch').addEventListener('keyup', function() {
        var searchTerm = this.value.toLowerCase();
        var tables = document.querySelectorAll('.search-table');
        
        tables.forEach(function(table) {
            var rows = table.querySelectorAll('tbody tr');
            rows.forEach(function(row) {
                var text = row.textContent.toLowerCase();
                // Reset display first to allow pagination to take over if search is empty
                // But here we want search to filter ALL rows, then pagination to show subset?
                // For simplicity: Search overrides pagination (shows all matches), or we re-paginate matches.
                // Let's make search simple: it hides non-matches. Pagination script needs to respect visible rows.
                
                if (text.includes(searchTerm)) {
                    row.classList.remove('hidden-by-search');
                } else {
                    row.classList.add('hidden-by-search');
                }
            });
            
            // Trigger pagination update for this table
            updatePagination(table.id);
        });
    });

    // Pagination Logic
    const state = {
        'table-exclusao': { page: 1, limit: 10 },
        'table-inclusao': { page: 1, limit: 10 }
    };

    function updatePagination(tableId) {
        const table = document.getElementById(tableId);
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const config = state[tableId];
        
        // Filter rows that are NOT hidden by search
        const visibleRows = rows.filter(row => !row.classList.contains('hidden-by-search'));
        const totalRows = visibleRows.length;
        
        let limit = config.limit;
        if (limit === 'all') limit = totalRows > 0 ? totalRows : 1;
        
        const totalPages = Math.ceil(totalRows / limit);
        
        // Ensure current page is valid
        if (config.page > totalPages) config.page = totalPages > 0 ? totalPages : 1;
        if (config.page < 1) config.page = 1;
        
        const start = (config.page - 1) * limit;
        const end = start + limit;
        
        // Hide all rows first (visually)
        rows.forEach(row => row.style.display = 'none');
        
        // Show only rows for current page that match search
        visibleRows.slice(start, end).forEach(row => row.style.display = '');
        
        // Update Controls
        const controls = document.querySelector(`.pagination-controls[data-target="${tableId}"]`);
        if (controls) {
            const info = controls.querySelector('.page-info');
            const prevBtn = controls.querySelector('.prev-page');
            const nextBtn = controls.querySelector('.next-page');
            
            const showStart = totalRows === 0 ? 0 : start + 1;
            const showEnd = Math.min(end, totalRows);
            
            info.textContent = `Mostrando ${showStart}-${showEnd} de ${totalRows}`;
            
            prevBtn.disabled = config.page === 1;
            nextBtn.disabled = config.page === totalPages || totalPages === 0;
            
            // Event Listeners (ensure we don't duplicate)
            prevBtn.onclick = () => {
                if (config.page > 1) {
                    config.page--;
                    updatePagination(tableId);
                }
            };
            nextBtn.onclick = () => {
                if (config.page < totalPages) {
                    config.page++;
                    updatePagination(tableId);
                }
            };
        }
    }

    // Initialize
    document.querySelectorAll('.rows-per-page').forEach(select => {
        select.addEventListener('change', function() {
            const target = this.dataset.target;
            state[target].limit = this.value === 'all' ? 'all' : parseInt(this.value);
            state[target].page = 1; // Reset to page 1
            updatePagination(target);
        });
    });

    // Initial Render
    updatePagination('table-exclusao');
    updatePagination('table-inclusao');

    // Delete Functionality
    function deleteRecord(id, table, btn) {
        if (!confirm('Tem certeza que deseja excluir este registro permanentemente?')) {
            return;
        }

        // Visual feedback
        const originalText = btn.textContent;
        btn.textContent = 'Excluindo...';
        btn.disabled = true;

        fetch(`index.php?page=admin_action&action=delete&table=${table}&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove row from DOM
                    const row = btn.closest('tr');
                    row.remove();
                    
                    // Update totals (simple reload for now or just decrement visual counters if we want to be fancy)
                    // For simplicity, let's just remove the row. The user can reload to see updated totals.
                    // Or we can reload the page:
                    // window.location.reload();
                    
                    // Re-run pagination to fix layout
                    updatePagination('table-exclusao');
                } else {
                    alert('Erro ao excluir: ' + (data.error || 'Erro desconhecido'));
                    btn.textContent = originalText;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erro na requisição.');
                btn.textContent = originalText;
                btn.disabled = false;
            });
    }

    // Table Sorting Functionality
    function setupTableSorting() {
        document.querySelectorAll('.sortable').forEach(header => {
            header.addEventListener('click', function() {
                const table = this.closest('table');
                const tbody = table.querySelector('tbody');
                const rows = Array.from(tbody.querySelectorAll('tr')).filter(row => !row.classList.contains('hidden-by-search'));
                const columnIndex = Array.from(this.parentElement.children).indexOf(this);
                const sortType = this.dataset.sort;
                const currentDirection = this.dataset.direction || 'asc';
                const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
                
                // Reset all arrows in this table
                table.querySelectorAll('.sortable').forEach(th => {
                    th.dataset.direction = '';
                    th.querySelector('.sort-arrow').textContent = '↕';
                });
                
                // Set current arrow
                this.dataset.direction = newDirection;
                this.querySelector('.sort-arrow').textContent = newDirection === 'asc' ? '↑' : '↓';
                
                // Sort rows
                rows.sort((a, b) => {
                    let aValue = a.children[columnIndex].textContent.trim();
                    let bValue = b.children[columnIndex].textContent.trim();
                    
                    // Handle different data types
                    if (sortType === 'valor') {
                        // Remove R$ and convert to number
                        aValue = parseFloat(aValue.replace('R$', '').replace(/\./g, '').replace(',', '.')) || 0;
                        bValue = parseFloat(bValue.replace('R$', '').replace(/\./g, '').replace(',', '.')) || 0;
                    } else if (sortType === 'vencimento' || sortType === 'data_inclusao') {
                        // Convert date DD/MM/YYYY to comparable format
                        aValue = aValue === '-' ? '01/01/1900' : aValue;
                        bValue = bValue === '-' ? '01/01/1900' : bValue;
                        const [aDay, aMonth, aYear] = aValue.split('/');
                        const [bDay, bMonth, bYear] = bValue.split('/');
                        aValue = new Date(aYear, aMonth - 1, aDay).getTime();
                        bValue = new Date(bYear, bMonth - 1, bDay).getTime();
                    } else {
                        // String comparison
                        aValue = aValue.toLowerCase();
                        bValue = bValue.toLowerCase();
                    }
                    
                    if (aValue < bValue) return newDirection === 'asc' ? -1 : 1;
                    if (aValue > bValue) return newDirection === 'asc' ? 1 : -1;
                    return 0;
                });
                
                // Re-append sorted rows
                rows.forEach(row => tbody.appendChild(row));
                
                // Update pagination after sorting
                const tableId = table.id;
                if (tableId) {
                    updatePagination(tableId);
                }
            });
        });
    }
    
    // Initialize sorting
    setupTableSorting();
</script>
