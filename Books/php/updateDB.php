<?	//обновление БД

	if (!empty($_POST['pages']))
	{
		//подключение к БД
		require $_SERVER["DOCUMENT_ROOT"]."/lib/DB.php";
		$DB = new DB();

		if ($DB->Connect()) echo $DB->UpdateDB($_POST['pages']);
		else echo "Нет подключения к базе данных";
	}
	else echo 'Нет номеров страниц';