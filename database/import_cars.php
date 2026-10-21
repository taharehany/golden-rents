<?php
// Run once from a browser only after configuring app/config.local.php:
// https://example.com/database/import_cars.php?token=YOUR_SETUP_TOKEN
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
if (!hash_equals((string) setting('setup_token', ''), (string) ($_GET['token'] ?? ''))) {
	http_response_code(403);
	exit('Forbidden');
}
$cars = [
	['Marcedes c class', 'Marcedes', '2023', '550', 'img/services/04.jpg'],
	['Range velar', 'Range Rover', '2025', '650', 'img/services/02.jpeg'],
	['Rolls Royce cost', 'Rolls Royce', '2024', '2500', 'img/services/03.jpeg'],
	['Corvette', 'Chevrolet', '2024', '1200', 'img/services/01.jpeg'],
	['Range vogue 2023', 'Range Rover', '2023', '1500', 'img/services/05.jpg'],
	['lamborghini urus', 'Lamborghini', '2024', '2800', 'img/services/06.jpeg'],
	['Range Rover sport', 'Range Rover', '2024', '1200', 'img/services/07.jpeg'],
	['Rolls Royce cullinan', 'Rolls Royce', '2024', '3800', 'img/services/08.jpeg'],
	['Marcedes G class', 'Marcedes', '2025', '2000', 'img/services/09.jpeg'],
	['Marcedes Gls', 'Marcedes', '2024', '1800', 'img/services/10.jpeg'],
	['BMW520i', 'BMW', '2025', '600', 'img/services/11.jpeg'],
	['Ferrari 488', 'Ferrari', '2024', '3500', 'img/services/12.jpeg'],
	['Defender', 'Land Rover', '2025', '1100', 'img/services/13.jpeg'],
	['BMW 440i', 'BMW', '2023', '650', 'img/services/14.jpg'],
	['Cadillac Escalade', 'Cadillac', '2024', '1100', 'img/services/15.jpeg'],
	['Lamborghini spider', 'Lamborghini', '2024', '3700', 'img/services/16.jpeg'],
	['BMW740i', 'BMW', '2024', '1100', 'img/services/17.jpeg'],
];
$pdo = db();
$pdo->beginTransaction();
try {
	$check = $pdo->prepare('SELECT id FROM cars WHERE name=? AND image=? LIMIT 1');
	$insert = $pdo->prepare("INSERT INTO cars(name,brand,model,price,currency,image,status,sort_order) VALUES(?,?,?,?,? ,?,'published',?)");
	$added = 0;
	foreach ($cars as $i => $car) {
		$check->execute([$car[0], $car[4]]);
		if (!$check->fetchColumn()) {
			$insert->execute([$car[0], $car[1], $car[2], $car[3], 'AED', $car[4], $i + 1]);
			$added++;
		}
	}
	$pdo->commit();
	echo htmlspecialchars("Import complete. Added {$added}; existing matching records were preserved. Delete or protect this importer after use.", ENT_QUOTES, 'UTF-8');
} catch (Throwable $e) {
	$pdo->rollBack();
	http_response_code(500);
	echo 'Import failed. Check the server error log.';
}
