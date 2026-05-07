<?php
$files = ['salles', 'promotions', 'cours', 'options'];
foreach ($files as $file) {
    $jsonPath = __DIR__ . "/data/$file.json";
    $csvPath = __DIR__ . "/data/$file.csv";
    if (file_exists($jsonPath)) {
        $data = json_decode(file_get_contents($jsonPath), true);
        if ($data) {
            $f = fopen($csvPath, 'w');
            foreach ($data as $row) {
                fputcsv($f, array_values($row));
            }
            fclose($f);
            echo "Converted $file to CSV\n";
        }
    }
}
?>
