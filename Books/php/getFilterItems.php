<?	//код для получения всех авторов и жанров из базы для фильтров

	//подключение к базе
	require $_SERVER["DOCUMENT_ROOT"]."/lib/DB.php";
	$DB = new DB();

	if ($DB->Connect())
	{
		//получение жанров
		$genres_tbl = $DB->_select('genres');
		$genres = array();
		if ($genres_tbl)
		{
			while ($row = $genres_tbl->fetch_assoc()) {
				$genres[] = $row;
			}
		}

		//получение авторов
		$authors_tbl = $DB->_select('authors');
		$authors = array();
		if ($authors_tbl)
		{
			while ($row = $authors_tbl->fetch_assoc()) {
				$authors[] = $row;
			}
		}

		$data = array('genres' => $genres, 'authors' => $authors);
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
	}
	else echo "Нет подключения к базе данных";