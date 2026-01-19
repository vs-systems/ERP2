<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

echo "<h1>Debug de Entidades</h1>";

try {
    $db = Vsys\Lib\Database::getInstance();

    // 1. Ver total de entidades
    $total = $db->query("SELECT COUNT(*) FROM entities")->fetchColumn();
    echo "<p>Total de entidades en la base: <strong>$total</strong></p>";

    // 2. Listar las primeras 50 para ver quó© hay
    $list = $db->query("SELECT id, name, type, contact_person, tax_id FROM entities LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Muestra de datos (Primeros 50):</h3>";
    echo "<table border='1'><tr><th>ID</th><th>Nombre</th><th>Tipo</th><th>Contacto</th><th>CUIT</th></tr>";
    foreach ($list as $r) {
        echo "<tr><td>{$r['id']}</td><td>{$r['name']}</td><td>{$r['type']}</td><td>{$r['contact_person']}</td><td>{$r['tax_id']}</td></tr>";
    }
    echo "</table>";

    // 3. Probar la bóºsqueda manual con 'javier'
    $q = "%javier%";
    $search = $db->prepare("SELECT * FROM entities WHERE LOWER(name) LIKE :q OR LOWER(contact_person) LIKE :q");
    $search->execute(['q' => $q]);
    $results = $search->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>Resultado de bóºsqueda para 'javier':</h3>";
    if (empty($results)) {
        echo "<p style='color:red'>No se encontró³ NADA con 'javier'.</p>";
    } else {
        echo "<ul>";
        foreach ($results as $res) {
            echo "<li>ID: {$res['id']} - Name: {$res['name']} - Contact: {$res['contact_person']}</li>";
        }
        echo "</ul>";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>





