<style>
    .filter-dropdown {
        position: absolute;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        z-index: 50;
        min-width: 250px;
        max-height: 400px;
        display: flex;
        flex-direction: column;
        font-size: 0.875rem;
        color: #1e293b;
    }
    .filter-header {
        padding: 0.5rem;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .filter-body {
        overflow-y: auto;
        padding: 0.5rem;
        flex: 1;
    }
    .filter-footer {
        padding: 0.5rem;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
    }
    .filter-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.25rem 0;
    }
    .filter-icon {
        opacity: 0.3;
        transition: opacity 0.2s;
    }
    .filter-icon.active {
        opacity: 1;
        color: #2563eb;
    }
    th:hover .filter-icon {
        opacity: 0.7;
    }
</style>

<div class="flex min-h-[calc(100vh-80px)] bg-slate-100">
    <!-- Sidebar -->
    <div class="w-64 bg-brand-900 text-white flex flex-col border-r border-brand-800">
        <div class="p-4 text-xl font-bold text-white border-b border-brand-800 flex items-center gap-2">
            <i data-lucide="settings" class="w-5 h-5 text-blue-400"></i>
            Admin Panel
        </div>
        <nav class="flex-1 p-4 space-y-2">
            <a href="index.php?page=admin&table=spc_inclusos" class="flex items-center gap-2 px-4 py-2.5 rounded-lg hover:bg-brand-800 transition-colors <?php echo $table === 'spc_inclusos' ? 'bg-brand-800 text-blue-400 font-medium' : 'text-slate-300'; ?>">
                <i data-lucide="table" class="w-4 h-4"></i>
                SPC Inclusos
            </a>
            <a href="index.php?page=admin&table=parcelas_em_aberto" class="flex items-center gap-2 px-4 py-2.5 rounded-lg hover:bg-brand-800 transition-colors <?php echo $table === 'parcelas_em_aberto' ? 'bg-brand-800 text-blue-400 font-medium' : 'text-slate-300'; ?>">
                <i data-lucide="banknote" class="w-4 h-4"></i>
                Parcelas em Aberto
            </a>
            <a href="index.php?page=admin&table=pdd_perdas" class="flex items-center gap-2 px-4 py-2.5 rounded-lg hover:bg-brand-800 transition-colors <?php echo $table === 'pdd_perdas' ? 'bg-brand-800 text-blue-400 font-medium' : 'text-slate-300'; ?>">
                <i data-lucide="trending-down" class="w-4 h-4"></i>
                PDD Perdas
            </a>
            <a href="index.php?page=admin&table=pdd_pagos" class="flex items-center gap-2 px-4 py-2.5 rounded-lg hover:bg-brand-800 transition-colors <?php echo $table === 'pdd_pagos' ? 'bg-brand-800 text-blue-400 font-medium' : 'text-slate-300'; ?>">
                <i data-lucide="file-check" class="w-4 h-4"></i>
                PDD Pagos
            </a>
            <div class="border-t border-brand-800 my-2"></div>
            <a href="index.php?page=admin&table=import_batches" class="flex items-center gap-2 px-4 py-2.5 rounded-lg hover:bg-brand-800 transition-colors <?php echo $table === 'import_batches' ? 'bg-brand-800 text-blue-400 font-medium' : 'text-slate-300'; ?>">
                <i data-lucide="history" class="w-4 h-4"></i>
                Histórico de Importações
            </a>
            <a href="index.php?page=admin&table=spc_historico_removidos" class="flex items-center gap-2 px-4 py-2.5 rounded-lg hover:bg-brand-800 transition-colors <?php echo $table === 'spc_historico_removidos' ? 'bg-brand-800 text-blue-400 font-medium' : 'text-slate-300'; ?>">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                Histórico de Removidos
            </a>
            <a href="index.php?page=dashboard" class="flex items-center gap-2 px-4 py-2.5 rounded-lg hover:bg-brand-800 text-slate-400 mt-4 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Voltar ao Sistema
            </a>
        </nav>
    </div>

    <!-- Content -->
    <div class="flex-1 overflow-auto p-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-slate-800 capitalize"><?php echo str_replace('_', ' ', $table); ?></h2>
            <div class="flex gap-4">
                <form class="flex items-center gap-2">
                    <label class="text-sm text-slate-600">Exibir:</label>
                    <select onchange="changeLimit(this.value)" class="border rounded-lg px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                        <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                        <option value="all" <?php echo $limit === 'all' ? 'selected' : ''; ?>>Todos</option>
                    </select>
                </form>
                <form class="flex gap-2">
                    <input type="hidden" name="page" value="admin">
                    <input type="hidden" name="table" value="<?php echo $table; ?>">
                    <input type="hidden" name="limit" value="<?php echo htmlspecialchars($limit); ?>">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Buscar..." class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Buscar</button>
                </form>
                <a href="#" onclick="exportFiltered(event, '<?php echo $table; ?>')" class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors" title="Exportar tabela (respeitando filtros)">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Exportar Excel
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <?php if (!empty($result['data'])): ?>
                            <?php foreach (array_keys($result['data'][0]) as $col): ?>
                                <th class="px-3 py-2 text-left text-xs font-medium text-slate-500 uppercase tracking-wider whitespace-nowrap relative group">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="sortable cursor-pointer hover:text-slate-700" data-sort="<?php echo $col; ?>">
                                            <?php echo $col; ?> <span class="sort-arrow">↕</span>
                                        </span>
                                        <button class="filter-btn p-1 rounded hover:bg-slate-200" data-col="<?php echo $col; ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 filter-icon <?php echo isset($filters[$col]) ? 'active' : ''; ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                            </svg>
                                        </button>
                                    </div>
                                </th>
                            <?php endforeach; ?>
                            <th class="px-6 py-3 text-right">Ações</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    <?php foreach ($result['data'] as $row): ?>
                    <tr class="hover:bg-slate-50">
                        <?php foreach ($row as $col => $val): ?>
                            <td class="px-3 py-2 whitespace-nowrap text-sm text-slate-500 max-w-xs truncate editable-cell" 
                                data-col="<?php echo $col; ?>" 
                                data-id="<?php echo $row['id']; ?>"
                                title="<?php echo htmlspecialchars($val ?? ''); ?>">
                                <?php 
                                    if ($val !== null && $val !== '') {
                                        if (in_array($col, ['data_remocao', 'data_inclusao', 'vencimento', 'data_inclusao_spc', 'contratacao', 'emissao', 'nascimento'])) {
                                            // Try to convert to timestamp
                                            $ts = strtotime($val);
                                            echo $ts ? date('d/m/Y', $ts) : htmlspecialchars($val);
                                        } elseif (in_array($col, ['valor', 'debito', 'valor_titulo', 'valor_debito'])) {
                                            echo number_format((float)$val, 2, ',', '.');
                                        } else {
                                            echo htmlspecialchars($val);
                                        }
                                    } else {
                                        echo '-';
                                    }
                                ?>
                            </td>
                        <?php endforeach; ?>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick="deleteRow('<?php echo $table; ?>', <?php echo $row['id']; ?>)" class="text-red-600 hover:text-red-900 ml-4">
                                <?php echo $table === 'import_batches' ? 'Reverter' : 'Excluir'; ?>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4 flex justify-between items-center">
            <span class="text-sm text-slate-600">
                Página <?php echo $result['current_page']; ?> de <?php echo $result['pages']; ?> (Total: <?php echo $result['total']; ?>)
            </span>
            <div class="space-x-2">
                <?php 
                // Rebuild query string for pagination
                $queryParams = $_GET;
                unset($queryParams['p']);
                $queryString = http_build_query($queryParams);
                ?>
                <?php if ($result['current_page'] > 1): ?>
                    <a href="index.php?<?php echo $queryString; ?>&p=<?php echo $result['current_page'] - 1; ?>" class="px-3 py-1 border rounded hover:bg-slate-50">Anterior</a>
                <?php endif; ?>
                <?php if ($result['current_page'] < $result['pages']): ?>
                    <a href="index.php?<?php echo $queryString; ?>&p=<?php echo $result['current_page'] + 1; ?>" class="px-3 py-1 border rounded hover:bg-slate-50">Próxima</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function deleteRow(table, id) {
    const message = table === 'import_batches' 
        ? 'Tem certeza que deseja REVERTER esta importação? Todos os registros vinculados a ela serão apagados permanentemente.' 
        : 'Tem certeza que deseja excluir este registro?';
        
    if (confirm(message)) {
        console.log('Deleting:', table, id);
        fetch('index.php?page=admin_action&action=delete&table=' + table + '&id=' + id)
            .then(response => {
                console.log('Response status:', response.status);
                return response.text().then(text => {
                    console.log('Response text:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('Invalid JSON: ' + text);
                    }
                });
            })
            .then(data => {
                console.log('Data:', data);
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erro ao excluir: ' + (data.error || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('Erro na requisição: ' + error.message);
            });
    }
}

// Table Sorting Functionality
function setupTableSorting() {
    document.querySelectorAll('.sortable').forEach(header => {
        header.addEventListener('click', function() {
            const table = this.closest('table');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const columnIndex = Array.from(this.parentElement.parentElement.children).indexOf(this.parentElement);
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
                
                // Try to parse as number
                const aNum = parseFloat(aValue.replace(/[^\d.-]/g, ''));
                const bNum = parseFloat(bValue.replace(/[^\d.-]/g, ''));
                
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    // Numeric comparison
                    return newDirection === 'asc' ? aNum - bNum : bNum - aNum;
                } else if (aValue.match(/^\d{2}\/\d{2}\/\d{4}$/)) {
                    // Date comparison DD/MM/YYYY
                    const [aDay, aMonth, aYear] = aValue.split('/');
                    const [bDay, bMonth, bYear] = bValue.split('/');
                    const aDate = new Date(aYear, aMonth - 1, aDay).getTime();
                    const bDate = new Date(bYear, bMonth - 1, bDay).getTime();
                    return newDirection === 'asc' ? aDate - bDate : bDate - aDate;
                } else {
                    // String comparison
                    aValue = aValue.toLowerCase();
                    bValue = bValue.toLowerCase();
                    if (aValue < bValue) return newDirection === 'asc' ? -1 : 1;
                    if (aValue > bValue) return newDirection === 'asc' ? 1 : -1;
                    return 0;
                }
            });
            
            // Re-append sorted rows
            rows.forEach(row => tbody.appendChild(row));
        });
    });
}

// Change Limit Functionality
function changeLimit(limit) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('limit', limit);
    urlParams.set('p', 1); // Reset to page 1
    window.location.search = urlParams.toString();
}

// Initialize sorting when page loads
document.addEventListener('DOMContentLoaded', () => {
    setupTableSorting();
    setupInlineEditing();
    setupColumnFiltering();
});

// Inline Editing Functionality
function setupInlineEditing() {
    document.querySelectorAll('.editable-cell').forEach(cell => {
        cell.addEventListener('dblclick', function() {
            if (this.querySelector('input')) return; // Already editing

            const currentText = this.innerText === '-' ? '' : this.innerText;
            const originalContent = this.innerHTML;
            const col = this.dataset.col;
            const id = this.dataset.id;
            const table = '<?php echo $table; ?>';

            if (col === 'id') return; // Cannot edit ID

            const input = document.createElement('input');
            input.type = 'text';
            input.value = currentText;
            input.className = 'w-full px-2 py-1 border rounded focus:ring-2 focus:ring-blue-500 outline-none text-sm';
            
            this.innerHTML = '';
            this.appendChild(input);
            input.focus();

            const save = () => {
                const newValue = input.value;
                if (newValue === currentText) {
                    this.innerHTML = originalContent;
                    return;
                }

                // Optimistic update
                this.innerText = newValue || '-';

                const formData = new FormData();
                formData.append('column', col);
                formData.append('value', newValue);
                formData.append('id', id);

                fetch(`index.php?page=admin_action&action=update_cell&table=${table}`, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        alert('Erro ao salvar: ' + (data.error || 'Erro desconhecido'));
                        this.innerHTML = originalContent; // Revert
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Erro na requisição');
                    this.innerHTML = originalContent; // Revert
                });
            };

            input.addEventListener('blur', save);
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    input.blur();
                } else if (e.key === 'Escape') {
                    this.innerHTML = originalContent;
                }
            });
        });
    });
}

// Filter Functionality
function setupColumnFiltering() {
    const table = '<?php echo $table; ?>';
    let activeDropdown = null;

    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.stopPropagation();
            const col = btn.dataset.col;
            
            // Close existing
            if (activeDropdown) {
                activeDropdown.remove();
                if (activeDropdown.dataset.col === col) {
                    activeDropdown = null;
                    return;
                }
            }

            // Create dropdown
            const dropdown = document.createElement('div');
            dropdown.className = 'filter-dropdown';
            dropdown.dataset.col = col;
            
            // Position
            const rect = btn.getBoundingClientRect();
            dropdown.style.top = (rect.bottom + window.scrollY + 5) + 'px';
            dropdown.style.left = (rect.left + window.scrollX) + 'px';

            dropdown.innerHTML = `
                <div class="p-4 text-center text-slate-500">Carregando...</div>
            `;
            document.body.appendChild(dropdown);
            activeDropdown = dropdown;

            try {
                // Fetch values
                const response = await fetch(`index.php?page=admin_action&action=get_column_values&table=${table}&column=${col}`);
                const data = await response.json();
                
                if (!data.success) throw new Error(data.error);

                // Render UI
                renderFilterUI(dropdown, col, data.values);
            } catch (err) {
                dropdown.innerHTML = `<div class="p-4 text-red-500">Erro: ${err.message}</div>`;
            }
        });
    });

    // Close on click outside
    document.addEventListener('click', (e) => {
        if (activeDropdown && !activeDropdown.contains(e.target)) {
            activeDropdown.remove();
            activeDropdown = null;
        }
    });
}

function renderFilterUI(dropdown, col, values) {
    // Get current filters from URL
    const urlParams = new URLSearchParams(window.location.search);
    const currentFilters = [];
    for(const [key, value] of urlParams.entries()) {
        if(key.startsWith(`filter[${col}]`)) {
            currentFilters.push(value);
        }
    }

    dropdown.innerHTML = `
        <div class="filter-header">
            <div class="flex gap-2">
                <button class="flex-1 px-2 py-1 bg-slate-100 hover:bg-slate-200 rounded text-xs" onclick="sortColumn('${col}', 'asc')">A-Z</button>
                <button class="flex-1 px-2 py-1 bg-slate-100 hover:bg-slate-200 rounded text-xs" onclick="sortColumn('${col}', 'desc')">Z-A</button>
            </div>
            <input type="text" placeholder="Pesquisar..." class="w-full px-2 py-1 border rounded text-sm filter-search">
        </div>
        <div class="filter-body">
            <div class="filter-item">
                <input type="checkbox" id="filter-all-${col}" class="filter-all" ${currentFilters.length === 0 ? 'checked' : ''}>
                <label for="filter-all-${col}" class="font-medium">(Selecionar Tudo)</label>
            </div>
            ${values.map(v => `
                <div class="filter-item">
                    <input type="checkbox" value="${v.value}" class="filter-val" 
                        ${currentFilters.includes(v.value) || currentFilters.length === 0 ? 'checked' : ''}>
                    <label class="truncate" title="${v.value}">${v.value || '(Vazio)'} <span class="text-slate-400 text-xs">(${v.count})</span></label>
                </div>
            `).join('')}
        </div>
        <div class="filter-footer">
            <button class="px-3 py-1 text-slate-600 hover:bg-slate-100 rounded" onclick="clearFilter('${col}')">Limpar</button>
            <button class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700" onclick="applyFilter('${col}')">OK</button>
        </div>
    `;

    // Search functionality
    const searchInput = dropdown.querySelector('.filter-search');
    const items = dropdown.querySelectorAll('.filter-item:not(:first-child)');
    searchInput.addEventListener('input', (e) => {
        const term = e.target.value.toLowerCase();
        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            item.style.display = text.includes(term) ? 'flex' : 'none';
        });
    });

    // Select All functionality
    const allCheckbox = dropdown.querySelector('.filter-all');
    const valCheckboxes = dropdown.querySelectorAll('.filter-val');
    
    allCheckbox.addEventListener('change', (e) => {
        valCheckboxes.forEach(cb => {
            if (cb.closest('.filter-item').style.display !== 'none') {
                cb.checked = e.target.checked;
            }
        });
    });
}

function applyFilter(col) {
    const dropdown = document.querySelector(`.filter-dropdown[data-col="${col}"]`);
    if (!dropdown) return;

    const allChecked = dropdown.querySelector('.filter-all').checked;
    const checkedValues = Array.from(dropdown.querySelectorAll('.filter-val:checked')).map(cb => cb.value);
    
    const urlParams = new URLSearchParams(window.location.search);
    
    // Remove existing filters for this column
    const keysToRemove = [];
    for(const key of urlParams.keys()) {
        if (key.startsWith(`filter[${col}]`)) {
            keysToRemove.push(key);
        }
    }
    keysToRemove.forEach(k => urlParams.delete(k));

    // Add new filters if not "All" (or if we want to support explicit selection)
    // Simplification: If "Select All" is NOT checked, or if specific items are checked, we send them.
    // If everything is checked, we effectively clear the filter.
    const totalOptions = dropdown.querySelectorAll('.filter-val').length;
    
    if (checkedValues.length < totalOptions || !allChecked) {
        checkedValues.forEach(val => {
            urlParams.append(`filter[${col}][]`, val);
        });
    }

    urlParams.set('p', 1); // Reset page
    window.location.search = urlParams.toString();
}

function clearFilter(col) {
    const urlParams = new URLSearchParams(window.location.search);
    const keysToRemove = [];
    for(const key of urlParams.keys()) {
        if (key.startsWith(`filter[${col}]`)) {
            keysToRemove.push(key);
        }
    }
    keysToRemove.forEach(k => urlParams.delete(k));
    window.location.search = urlParams.toString();
}

function sortColumn(col, dir) {
    const header = document.querySelector(`th span[data-sort="${col}"]`);
    if (header) {
        // Force direction
        header.dataset.direction = dir === 'asc' ? 'desc' : 'asc'; // click flips it
        header.click();
    }
    
    // Close dropdown
    const dropdown = document.querySelector('.filter-dropdown');
    if (dropdown) dropdown.remove();
}

function exportFiltered(e, table) {
    e.preventDefault();
    
    // Get current URL parameters
    const currentParams = new URLSearchParams(window.location.search);
    
    // Build export URL
    const exportParams = new URLSearchParams();
    exportParams.set('page', 'admin_export');
    exportParams.set('table', table);
    
    // Copy search and filters
    if (currentParams.has('search')) {
        exportParams.set('search', currentParams.get('search'));
    }
    
    for (const [key, value] of currentParams.entries()) {
        if (key.startsWith('filter[')) {
            exportParams.append(key, value);
        }
    }
    
    // Redirect to export
    window.location.href = 'index.php?' + exportParams.toString();
}
</script>
