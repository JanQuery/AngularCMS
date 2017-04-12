<?php

include dirname(__FILE__) . '/../db/connection.php';

$ip = $_SERVER['REMOTE_ADDR'];
$ref = $_POST['referer'];
$uri = $_POST['url'];

$db_connection = connect();

try
{
	$query = 	'INSERT INTO visitors' .
				' (visitor_ip, http_referer, request_uri, visited) VALUES' .
				' (:visitor_ip, :http_referer, :request_uri, NOW())';

	$statement = $db_connection->prepare($query);

	$statement->bindValue(':visitor_ip', $ip, PDO::PARAM_STR); 
	$statement->bindValue(':http_referer', $ref, PDO::PARAM_STR); 
	$statement->bindValue(':request_uri', $uri, PDO::PARAM_STR); 

	$statement->execute();
}
catch (PDOException $e)
{
}

$date_from = date("Y-m-d", strtotime('-1 days'));
$date_to = date("Y-m-d", time());

try
{
	// sprawdza, czy jest wpis w 'stat_main' na wczorajszy dzień:
	
	$query = "SELECT COUNT(*) AS licznik FROM stat_main WHERE date='".$date_from."'";

	$statement = $db_connection->prepare($query);
	
	$statement->execute();
	
	$row_item = $statement->fetch(PDO::FETCH_ASSOC);

	$data_found = intval($row_item['licznik']);
	
	if ($data_found == 0) // nie ma wpisu - dodajemy dla obu tabel
	{
		// tabela 'stat_main':

		$query = "INSERT INTO stat_main (id, date, start, contact, admin, login, register) 
					VALUES 
					(NULL, '".$date_from."', 
					(SELECT COUNT(*) from visitors WHERE request_uri = '/' AND visited BETWEEN '".$date_from."' AND '".$date_to."'), 
					(SELECT COUNT(*) from visitors WHERE request_uri = '/contact' AND visited BETWEEN '".$date_from."' AND '".$date_to."'), 
					(SELECT COUNT(*) from visitors WHERE request_uri = '/admin' AND visited BETWEEN '".$date_from."' AND '".$date_to."'), 
					(SELECT COUNT(*) from visitors WHERE request_uri = '/login' AND visited BETWEEN '".$date_from."' AND '".$date_to."'), 
					(SELECT COUNT(*) from visitors WHERE request_uri = '/register' AND visited BETWEEN '".$date_from."' AND '".$date_to."')
					)";

		$statement = $db_connection->prepare($query);

		$statement->execute();
		
		// tabela 'stat_ip':

		$query = "SELECT DISTINCT visitor_ip, COUNT(*) FROM visitors 
					WHERE visited BETWEEN '".$date_from."' AND '".$date_to."' 
					GROUP BY visitor_ip ORDER BY visitor_ip";

		$statement = $db_connection->prepare($query);

		$statement->execute();
		
		$rows = $statement->fetchAll(PDO::FETCH_ASSOC);
		
		foreach ($rows as $row)
		{
			$query = "INSERT INTO stat_ip (id, date, ip, counter) VALUES (NULL, '".$date_from."', '".$row['visitor_ip']."', 
						(SELECT COUNT(*) FROM visitors WHERE visitor_ip='".$row['visitor_ip']."' AND visited BETWEEN '".$date_from."' AND '".$date_to."'))";

			$statement = $db_connection->prepare($query);

			$statement->execute();
		}
	}
}
catch (PDOException $e)
{
}

?>

