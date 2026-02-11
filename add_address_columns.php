<?php
/**
 * Adiciona colunas individuais de endereço na tabela pdd_perdas
 */

require_once __DIR__ . '/config/db.php';

$db = (new Database())->getConnection();

echo "=== ADICIONANDO COLUNAS DE ENDEREÇO EM pdd_perdas ===\n\n";

// Lista de colunas a adicionar
$columns = [
    'rua' => 'VARCHAR(255)',
    'numero' => 'VARCHAR(50)',
    'bairro' => 'VARCHAR(100)',
    'cep' => 'VARCHAR(20)',
    'cidade' => 'VARCHAR(100)',
    'estado' => 'VARCHAR(2)',
];

// Verificar quais colunas já existem
$stmt = $db->query("DESCRIBE pdd_perdas");
$existingColumns = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $existingColumns[] = strtolower($row['Field']);
}

echo "Verificando colunas existentes...\n";

$added = 0;
foreach ($columns as $column => $type) {
    if (in_array(strtolower($column), $existingColumns)) {
        echo "  ✓ Coluna '$column' já existe\n";
    } else {
        echo "  + Adicionando coluna '$column' ($type)...\n";
        try {
            $db->exec("ALTER TABLE pdd_perdas ADD COLUMN $column $type NULL");
            echo "    ✓ Sucesso!\n";
            $added++;
        } catch (PDOException $e) {
            echo "    ✗ Erro: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n";
if ($added > 0) {
    echo "✅ $added colunas adicionadas com sucesso!\n";
} else {
    echo "✓ Todas as colunas já existiam.\n";
}

echo "\nEstrutura atualizada de pdd_perdas:\n";
$stmt = $db->query("DESCRIBE pdd_perdas");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (in_array($row['Field'], array_keys($columns))) {
        echo "  ✓ {$row['Field']} ({$row['Type']})\n";
    }
}
