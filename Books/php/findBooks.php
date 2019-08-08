<?	//код для получения книг после применения фильтров

	$bookName = '';
	if (isset($_POST['name']))
		$bookName = $_POST['name'];
	$authorIDs = '';
	if (isset($_POST['authors'])) 
		$authorIDs = $_POST['authors'];
	$genreIDs = '';
	if (isset($_POST['genres']))
		$genreIDs = $_POST['genres'];

	//подключение к БД
	require $_SERVER["DOCUMENT_ROOT"]."/lib/DB.php";
	$DB = new DB();

	if ($DB->Connect())
	{
		//получение массива подходящих книг в формате json
		$books = $DB->getBooks($bookName, $authorIDs, $genreIDs);
		echo $books;
	}
	else echo "Нет подключения к базе данных";