<?php

require_once 'vendor/autoload.php';
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
$loader = new FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader, [
	'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());
$servername = "localhost";
$username = "store";
$password = "XVm25obIpOYad7yV";
$dbname = "store";
// Create connection

$pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
// set the PDO error mode to exception
$pdo->setAttribute (PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


$request = $_SERVER['REQUEST_URI'];

switch ($request) {
		case '/install':
				$sh = $pdo->prepare("
				CREATE TABLE IF NOT EXISTS types(
					id INT AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(255)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
				");
				$sh->execute();



				$sh = $pdo->prepare("
			INSERT IGNORE INTO types
				(name)
			VALUES
				('movie'),
				('game'),
				('subscription')
				");
				$sh->execute();


				$sh = $pdo->prepare("
				CREATE TABLE IF NOT EXISTS attributes (
					id INT AUTO_INCREMENT PRIMARY KEY,
					type VARCHAR(255),
					name VARCHAR(255),
					value VARCHAR(255)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
				");
				$sh->execute();

				$sh = $pdo->prepare("
			INSERT IGNORE INTO attributes
				(type, name, value)
			VALUES
				('movie', 'length', 'time'),
				('game', 'size', 'megabytes'),
				('game', 'developer', 'company name'),
				('subscription', 'duration', 'months')
				");
				$sh->execute();



				$sh = $pdo->prepare("
				CREATE TABLE IF NOT EXISTS items(
					id INT AUTO_INCREMENT PRIMARY KEY,
					sku VARCHAR(255) UNIQUE NOT NULL,
					name VARCHAR(255) NOT NULL,
					price VARCHAR(255) NOT NULL,
					type VARCHAR(255) NOT NULL,
					attribute VARCHAR(255) NOT NULL
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
				");
				$sh->execute();
				echo '<a href="/add">store</a>';
				break;
		case '/delete':
			if (!empty($_POST["items"]))
			{
				foreach($_POST["items"] as $item)
				{
					$sh = $pdo->prepare("
					DELETE FROM `items` WHERE id = ?
					");
					$sh->execute([$item]);
				}

			}
			header('Location: /');
			break;
		case '/list':
		case '/':

			$sh = $pdo->prepare("
			SELECT * FROM types
			");
			$sh->execute();
			$r = $sh->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, "Type");

			$types = array();
			foreach ($r as $type)
			{
				$types[$type->id] = $type;
			}
			$sh = $pdo->prepare("
			SELECT * FROM items
			");
			$sh->execute();
			$items = $sh->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, "Item");
			foreach ($items as $item)
			{
				$item->attribute = (array)json_decode($item->attribute);
			}
			echo $twig->render('list.html', [
				'title' => 'List',
				'items' => $items,
				'types' => $types
			]);
			break;
    case '/add':
			$err = [];
			if (!empty($_POST))
			{
				$f = ['sku', 'name', 'price', 'type'];

				foreach ($f as &$field) {
				   if (empty($_POST[$field]))
					{
						array_push($err, $field);
					}
				}
				if (empty($err))
				{
						$sh = $pdo->prepare("
						INSERT INTO items
							(sku, name, price, type, attribute)
							VALUES
							(?, ?, ?, ?, ?)
						");

						try {
							$sh->execute([
								$_POST['sku'],
								$_POST['name'],
								$_POST['price'],
								$_POST['type'],
								json_encode($_POST['attributes']),
							]);
						} catch(Exception $e)
						{
							switch($e->errorInfo[1])
							{
								case 1062:
									array_push($err, 'sku');
									echo "Duplicate SKU.";
									break;
								default:
									echo $e->getMessage();
								break;
							}


						}
						if (empty($err))
							echo "Item added!";
					}
				}
				$sh = $pdo->prepare("
				SELECT * FROM types
				");
				$sh->execute();
				$types = $sh->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, "Type");

				foreach ($types as $type)
				{
					$sh = $pdo->prepare("
					SELECT * FROM attributes
					WHERE type = ?
					");
					$sh->execute([$type->name]);
					$r = $sh->fetchAll();
					$attributes = [];
					foreach($r as $attribute)
					{
						$attributes[$attribute['id']] = [
							$attribute['name'] => $attribute['value'],
						];
					}
					$type->attributes = $attributes;
				}
				echo $twig->render('add.html', [
					'title' => 'Add item',
					'types' => $types,
					'err' => $err
				]);
        break;
    default:
        http_response_code(404);
        require __DIR__ . '/views/404.php';
        break;
}
class Type
{
}
class Item
{
}
?>
