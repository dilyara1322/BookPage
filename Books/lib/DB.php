<?
	require $_SERVER["DOCUMENT_ROOT"]."/config.php";

	/**
	* класс для работы с базой данных
	*/
	class DB
	{
		/**
		* ссылка на подключение к БД
		* @var null
		*/
		private $db;

		/**
		* конструктор
		*/
		public function __construct()
		{
			$this->db = null;
		}

		/**
		* метод для подключения к БД
		* @param string $DBname - имя базы данных
		* @return bool - успешность подключения
		*/
		public function Connect($DBname = "books_db_")
		{
			$this->db = new mysqli(HOST, LOGIN, PASS, $DBname);
			if ($error = $this->db->connect_error) return false;
			$this->db->set_charset("utf8");
			return true;
		}

		/**
		* метод для осуществления выборки из таблицы
		* @param string $table - имя таблицы
		* @param string $fields - поля таблицы
		* @param string $where - условие where
		* @return ответ на sql-запрос
		*/
		public function _select($table, $fields = " * ", $where = "")
		{
			$table = $this->db->real_escape_string($table);
			$fields = $this->db->real_escape_string($fields);
			//$where = $this->db->real_escape_string($where);

			$query = "SELECT $fields FROM $table";
			if (!empty($where)) $query .= " WHERE $where";
			return $this->db->query($query);
		}

		/**
		* метод для добавления строк в базу
		* @param string $table - название таблицы
		* @param array{array(string), array(string)} $fvalues - массив с именами полей и значениями
		*/
		public function _insert($table, $fvalues)
		{
			if (is_array($fvalues) && array_key_exists(0, $fvalues) && array_key_exists(1, $fvalues) && is_array($fvalues[0]) && is_array($fvalues[1]))
			{
				$table = $this->db->real_escape_string($table);
				foreach ($fvalues[0] as $key => $value) 
				{
					$fvalues[0][$key] = $this->db->real_escape_string($value);
				}
				foreach ($fvalues[1] as $key => $value) 
				{
					$fvalues[1][$key] = $this->db->real_escape_string($value);
				}

				$fields = "`" . implode("`, `", $fvalues[0]) . "`";
				$values = "'" . implode("', '", $fvalues[1]) . "'"; 

				$this->db->query("INSERT INTO $table ($fields) VALUES ($values)");
			}
		}

		// /**
		// * метод для осуществления сложных запросов (небезопасный)
		// * @param string $query - запрос
		// * @return ответ на sql-запрос
		// */
		// public function _query($query)
		// {
		// 	return $this->db->query($query);
		// }

		/**
		* метод для получения книг из базы по названию, авторам и/или жанрам
		* @param string $bookName - название книги или его часть
		* @param string|array(int) $authorIDs - id авторов
		* @param string|array(int) $genreIDs - id жанров
		* @return string - массив книг в формате json
		*/
		public function getBooks($bookName, $authorIDs, $genreIDs)
		{
			$bookName = $this->db->real_escape_string($bookName);
			$authorIDs = $this->db->real_escape_string($authorIDs);
			$genreIDs = $this->db->real_escape_string($genreIDs);
			
			$select = $this->db->query("SELECT * FROM (SELECT * FROM `books` ".$this->getAthorsBooks($authorIDs)." ) AS `books` ".$this->getGenresBooks($genreIDs)." WHERE `name` LIKE '%". $bookName."%'");
			if ($select && $select->num_rows > 0)
					return $this->MakeBooksArray($select);
			else return "";
		}

		/**
		* метод, возвращающий часть запроса для получения книг с определенными авторами
		* @param string|array(int) $authorIDs - id авторов
		* @return string
		*/
		private function getAthorsBooks($authorIDs)
		{
			// $authorsStr = "";
			if (is_array($authorIDs)) $authorIDs = implode(",", $authorIDs);
			// else $authorsStr = $authorIDs;
			$where = "";
			if (strlen($authorIDs) != 0) 
				$where = " WHERE `authors`.`id` IN (".$authorIDs.")";
			$authorsStr = " JOIN
						(SELECT `name` as `author`, `book_id` FROM `authors` JOIN `authors_books` 
							ON (`authors`.`id` = `authors_books`.`author_id`) ".$where." ) AS `ab`
						ON (`books`.`id` = `ab`.`book_id`)" ;
			return $authorsStr;
		}

		/**
		* метод, возвращающий часть запроса для получения книг с определенными жанрами
		* @param string|array(int) $genreIDs - id жанров
		* @return string
		*/
		private function getGenresBooks($genreIDs)
		{
			if (is_array($genreIDs)) $genreIDs = implode(",", $genreIDs);
			$where = "";
			if (strlen($genreIDs) != 0) 
				$where = " WHERE `genres`.`id` IN (".$genreIDs.")";
			$genresStr = " JOIN
						(SELECT `name` as `genre`, `book_id` FROM `genres` JOIN `genres_books` 
							ON (`genres`.`id` = `genres_books`.`genre_id`)".$where.") AS `gb`
						ON (`books`.`id` = `gb`.`book_id`)";
			return $genresStr;
		}

		/**
		* метод для преобразования ответа на запрос в массив книг без повторений
		* @param sql-table $select - таблица с книгами
		* @return string - массив книг в формате json
		*/
		private function MakeBooksArray($select)
		{
			$books = array();
			//удаление повторений
			while ($row = $select->fetch_assoc()) {
				//флаг на существование книги в массиве
				$exists = false;
				//поиск очередной книги из $select в массиве
				foreach ($books as &$book) {
					if ($book['id'] == $row['id']){
						//добавление жанров книги в массив
						if (!in_array($row['genre'], $book['genres']))
							array_push($book['genres'], $row['genre']);
						//добавление авторов книги в массив
						if (!in_array($row['author'], $book['authors']))
							array_push($book['authors'], $row['author']);
						$exists = true; 
						break;
					}
				}
				//добавление книги в массив, если ее еще там нет
				if (!$exists) {
					$books[] = array('id' => $row['id'], 
									 'name' => $row['name'], 
									 'authors' => array($row['author']),
									 'genres' => array($row['genre']),
									 'description' => $row['description'],
									 'picture' => $row['picture']);
				}
			}

			return json_encode($books, JSON_UNESCAPED_UNICODE);
		}

		/**
		* метод для очистки таблиц в базе
		*/
		public function ClearDB()
		{
			$this->db->query("DELETE FROM `books`");
			$this->db->query("ALTER TABLE `books` AUTO_INCREMENT = 0");
			$this->db->query("DELETE FROM `authors`");
			$this->db->query("ALTER TABLE `authors` AUTO_INCREMENT = 0");
			$this->db->query("DELETE FROM `genres`");
			$this->db->query("ALTER TABLE `genres` AUTO_INCREMENT = 0");
		}

		/**
		* метод-парсер, полностью обновляющий базу данных
		* @param array(int) $pages - массив с номерами страниц, откуда загрузить данные
		* @return 
		*/
		public function UpdateDB($pages = array(1))
		{
			//очистка базы
			$this->ClearDB();
			
			//страницы для парсинга
			$urls = array();
			foreach ($pages as $page) {
				$urls[] = 'https://www.litmir.me/bs?rs=5%7C1%7C0&p='.$page;
			}

			//парсинг
			foreach ($urls as $url) 
			{
				//получение страницы с книгами
				$ch = curl_init($url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$result = curl_exec($ch);
				$errno = curl_errno($ch);
				$errmsg = curl_error($ch);
				curl_close($ch);
				if ($errno > 0) return $errmsg;

				//рег. выражения для вычленения различной информации о книге:
				// - области с информацией об одной книге
				$island_re = '/<table class="island"([\s\S]*?)<\/table>/mui';
				// - пути к картинке
				$image_re = '/data-src="(.*?)"/i';
				// - названия книги
				$name_re = '/<span itemprop="name">(.*?)<\/span>/ui';
				// - списка авторов
				$authors_re = '/<span itemprop="author"(.*?)<\/span>/ui';
				// - ФИО одного автора
				$author_re = '/<a(.*?)>(.*?)<\/a>/ui';
				// - списка жанров
				$genres_re = '/<span itemprop="genre"(.*?)<\/span>/ui';
				// - наименования одного жанра
				$genre_re = '/<a(.*?)>(.*?)<\/a>/ui';
				// - описания книги
				$descr_re = '/class="description"><div class="BBHtmlCode"><div class="BBHtmlCodeInner"><p>(.*?)<\/p><\/div>/ui';

				//получение массива, в каждой ячейке - информация по одной книге
				$islands = array();
				preg_match_all($island_re, $result, $islands);
				$islands = $islands[0];
				
				//вычленение различных характеристик книги
				foreach ($islands as $island) 
				{
					//адрес картинки
					$image = "";
					if (preg_match($image_re, $island, $matches))
					{
						$image = "https://www.litmir.me".$matches[1];
					}

					//название книги
					$name = "";
					if (preg_match($name_re, $island, $matches))
					{
						$name =  $matches[1];
					}

					//авторы книги
					$authors = array();
					if (preg_match($authors_re, $island, $matches))
					{
						if (preg_match_all($author_re, $matches[1], $authors_html) > 0)
							$authors = $authors_html[2];
					}			

					//жанры книги
					$genres = array();
					if (preg_match($genres_re, $island, $matches))
					{
						if (preg_match_all($genre_re, $matches[1], $genres_html) > 0)
							$genres = $genres_html[2];
					}

					//описание книги
					$description = "";
					if (preg_match($descr_re, $island, $matches))
					{
						$description = preg_replace('/<p>|<\/p>/ui', '', $matches[1]);
					}

					//добавление новых авторов в базу
					foreach ($authors as $author) 
					{
						$r = $this->_select('authors', '*', "`name` = '$author'");
						if ($r && $r->num_rows == 0)
						{
							$this->_insert('authors', array(array('name'), array($author)) );
						}
					}

					//добавление новых жанров в базу
					foreach ($genres as $genre) 
					{
						$r = $this->_select('genres', 'name', "`name` = '$genre'");
						if ($r && $r->num_rows == 0)
						{
							$this->_insert('genres', array(array('name'), array($genre)) );
						}
					}

					//получение id авторов
					$authorIDs = array();
					foreach ($authors as $author) 
					{
						if ($ids = $this->_select('authors', 'id', "`name` = '$author'"))
						{
							while ($row = $ids->fetch_assoc()) 
							{
								$authorIDs[] = $row['id'];
							} 
						}
					}

					//получение id жанров
					$genreIDs = array();
					foreach ($genres as $genre) 
					{
						if ($ids = $this->_select('genres', 'id', "`name` = '$genre'"))
						{
							while ($row = $ids->fetch_assoc()) 
							{
								$genreIDs[] = $row['id'];
							} 
						}
					}

					//проверка, есть ли книга в базе
					$r = $this->_select('books', "*", "`name` = '$name' AND `picture` = '$image'");
					if ($r && $r->num_rows == 0)
					{
						//добавление книги в базу
						$this->_insert('books', array(array('name', 'description', 'picture'), array($name, $description, $image)));

						//получение id книги
						$r = $this->_select('books', "*", "`name` = '$name' AND `picture` = '$image'");
						if ($r && $r->num_rows > 0)
						{
							$bookID = $r->fetch_assoc();
							$bookID = $bookID['id'];

							//добавление связей книги с авторами и жанрами
							foreach ($authorIDs as $id) 
							{
								$this->_insert('authors_books', array(array('author_id', 'book_id'), array($id, $bookID)));
							}
							foreach ($genreIDs as $id) 
							{
								$this->_insert('genres_books', array(array('genre_id', 'book_id'), array($id, $bookID)));
							}
						}
					}
				}
			}
		}
	}