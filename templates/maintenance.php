<?php
// Handle Maintenance Actions
$maintenanceMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'clean_duplicates') {
            // Archive before delete
            $sqlArchive = "INSERT INTO spc_historico_removidos (original_id, contrato, tp_contrato, contratante, cpf_cnpj, valor, vencimento, data_inclusao_spc, motivo_remocao)
                           SELECT t1.id, t1.contrato, t1.tp_contrato, t1.contratante, t1.cpf_cnpj, t1.debito, t1.vencimento, t1.data_inclusao, 'Duplicata de Contrato (Manutenção)'
                           FROM spc_inclusos t1
                           INNER JOIN spc_inclusos t2 
                           WHERE t1.id < t2.id AND t1.contrato = t2.contrato";
            $db->query($sqlArchive);

            // Remove duplicates from spc_inclusos based on contrato
            $sql = "DELETE t1 FROM spc_inclusos t1
                    INNER JOIN spc_inclusos t2 
                    WHERE t1.id < t2.id AND t1.contrato = t2.contrato";
            $stmt = $db->query($sql);
            $count = $stmt->rowCount();
            $maintenanceMessage = "Limpeza concluída: $count duplicatas removidas.";
        } elseif ($_POST['action'] === 'clean_expired') {
            // Archive before delete
            $sqlArchive = "INSERT INTO spc_historico_removidos (original_id, contrato, tp_contrato, contratante, cpf_cnpj, valor, vencimento, data_inclusao_spc, motivo_remocao)
                           SELECT id, contrato, tp_contrato, contratante, cpf_cnpj, debito, vencimento, data_inclusao, 'Prescrito (> 5 anos) (Manutenção)'
                           FROM spc_inclusos 
                           WHERE vencimento < DATE_SUB(NOW(), INTERVAL 5 YEAR)";
            $db->query($sqlArchive);

            // Remove records older than 5 years (example logic)
            // Assuming 'vencimento' is in YYYY-MM-DD format
            $sql = "DELETE FROM spc_inclusos WHERE vencimento < DATE_SUB(NOW(), INTERVAL 5 YEAR)";
            $stmt = $db->query($sql);
            $count = $stmt->rowCount();
            $maintenanceMessage = "Limpeza concluída: $count registros prescritos removidos.";
        }
    } catch (Exception $e) {
        $maintenanceMessage = "Erro: " . $e->getMessage();
    }
}
?>

<div class="flex min-h-[calc(100vh-80px)] bg-slate-100">
    <!-- Sidebar (Reused from admin.php structure, ideally should be a component) -->
    <div class="w-64 bg-brand-900 text-white flex flex-col border-r border-brand-800">
        <div class="p-4 text-xl font-bold text-white border-b border-brand-800 flex items-center gap-2">
            <i data-lucide="settings" class="w-5 h-5 text-blue-400"></i>
            Admin Panel
        </div>
        <nav class="flex-1 p-4 space-y-2">
            <a href="index.php?page=admin&table=spc_inclusos" class="flex items-center gap-2 px-4 py-2.5 rounded-lg hover:bg-brand-800 transition-colors text-slate-300">
                <i data-lucide="table" class="w-4 h-4"></i>
                SPC Inclusos
            </a>
            <!-- ... other links ... -->
            <a href="index.php?page=maintenance" class="flex items-center gap-2 px-4 py-2.5 rounded-lg bg-brand-800 text-blue-400 font-medium transition-colors">
                <i data-lucide="wrench" class="w-4 h-4"></i>
                Manutenção
            </a>
            <div class="border-t border-brand-800 my-2"></div>
            <a href="index.php?page=dashboard" class="flex items-center gap-2 px-4 py-2.5 rounded-lg hover:bg-brand-800 text-slate-400 mt-4 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Voltar ao Sistema
            </a>
        </nav>
    </div>

    <!-- Content -->
    <div class="flex-1 overflow-auto p-8">
        <h2 class="text-2xl font-bold text-slate-800 mb-6 flex items-center gap-2">
            <i data-lucide="wrench" class="w-6 h-6 text-brand-500"></i>
            Manutenção do Sistema
        </h2>

        <?php if ($maintenanceMessage): ?>
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded-xl shadow-sm flex items-start gap-3 animate-fade-in-up">
                <i data-lucide="info" class="w-5 h-5 text-blue-600 mt-0.5"></i>
                <p class="text-sm text-blue-700 font-medium"><?php echo htmlspecialchars($maintenanceMessage); ?></p>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Card 1: Remove Duplicates -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-orange-100 text-orange-600 rounded-lg">
                        <i data-lucide="copy" class="w-5 h-5"></i>
                    </div>
                    <h3 class="font-bold text-slate-800">Remover Duplicatas</h3>
                </div>
                <p class="text-sm text-slate-500 mb-6">
                    Remove registros duplicados na tabela SPC Inclusos baseando-se no número do contrato. Mantém apenas o registro mais recente.
                </p>
                <form method="POST" onsubmit="return confirm('Tem certeza? Essa ação não pode ser desfeita.');">
                    <input type="hidden" name="action" value="clean_duplicates">
                    <button type="submit" class="w-full py-2 px-4 bg-orange-50 text-orange-600 hover:bg-orange-100 border border-orange-200 rounded-lg transition-colors font-medium flex items-center justify-center gap-2">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                        Executar Limpeza
                    </button>
                </form>
            </div>

            <!-- Card 2: Remove Expired -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-red-100 text-red-600 rounded-lg">
                        <i data-lucide="calendar-x" class="w-5 h-5"></i>
                    </div>
                    <h3 class="font-bold text-slate-800">Remover Prescritos (> 5 Anos)</h3>
                </div>
                <p class="text-sm text-slate-500 mb-6">
                    Remove registros com data de vencimento superior a 5 anos. Útil para manter a base limpa e em conformidade.
                </p>
                <form method="POST" onsubmit="return confirm('Tem certeza? Essa ação apagará registros antigos permanentemente.');">
                    <input type="hidden" name="action" value="clean_expired">
                    <button type="submit" class="w-full py-2 px-4 bg-red-50 text-red-600 hover:bg-red-100 border border-red-200 rounded-lg transition-colors font-medium flex items-center justify-center gap-2">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                        Executar Limpeza
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
