
//добавление книг на страницу и наполнение фильтра
$(document).ready(function(){
	UpdateBookList();
	UpdateFilter();
});

//фильтрация книг
$('form').submit(function(e){
	e.preventDefault();

	//получение id необходимых жанров
	let checkedGenres = " ";
	$(".genres input:checkbox:checked").each(function(){
		checkedGenres += $(this).val()+",";
	});
	checkedGenres = checkedGenres.substring(0, checkedGenres.length -1);
	
	//получение id необходимых авторов
	let checkedAuthors = " ";
	$(".authors input:checkbox:checked").each(function(){
		checkedAuthors += $(this).val()+",";
	});
	checkedAuthors = checkedAuthors.substring(0, checkedAuthors.length -1);

	//обновление списка книг на странице
	UpdateBookList($("#bookName").val(), checkedGenres, checkedAuthors);
});

//обновление базы данных
$('#upd_db').click(function(){
	//получение номеров страниц для парсинга
	var pagesStr = $(".pages").val();
	var pagesArr = pagesStr.match(/\d+/g);
	if (pagesArr)
	{
		//обновление базы и страницы
		$.ajax({
			type: "POST",
			data: {pages: pagesArr},
			url: "/php/updateDB.php",
			success: function(message){
				if (message != '') alert(message);
				else
				{
					$(".pages").val("");
					UpdateFilter();
					UpdateBookList();
				}
			},
			error: function(error){
				alert(error);
			}
		})
	}
	else alert("Введите номера страниц");
});

/**
* функция для обновления списка книг на странице
* @param string _name	  название книги или его часть
* @param string _genres	  id жанров
* @param string _authors  id авторов
*/
function UpdateBookList(_name = '', _genres = '', _authors = '')
{
	$(".bookslist-row").empty();
	$.ajax({
		type: "POST",
		url: "/php/findBooks.php",
		data: ({name: _name, genres: _genres, authors: _authors}),
		success: function(books){
			try{
				var books = $.parseJSON(books);
				$("span#booksCount").text("Всего книг: "+books.length);
				books.forEach(function(item){
					$("<div class='col-md-5 book-div' id='book"+item.id+"'>"+
						"<img src='"+item.picture+"'><br>"+
						"<b><span class='name'>"+item.name+"</span></b><br>"+
						"<i>Автор: </i><span class='author'>"+item.authors+"</span><br>"+
						"<i>Жанр: </i><span class='genre'>"+item.genres+"</span><br>"+
						"<i>Описание: </i><span class='description'>"+item.description+"</span><br>"+
						"</div>").appendTo('.bookslist-row');
				});
			}
			catch(error){ alert(error); }
		},
		error: function(error){
			alert(error);
		}
	});
}

/**
* функция для обновления фильтра
*/
function UpdateFilter()
{
	$.ajax({
		type: "POST",
		url: "/php/getFilterItems.php",
		success: function(jsData){
			try{
				let data = $.parseJSON(jsData);
				var genres = data['genres'];
				var authors = data['authors'];
				
				//обновление фильтра жанров
				$(".genres").empty();
				$("<summary><b>Жанры</b></summary>").appendTo(".genres");
				genres.forEach(function(genre){
					$("<input type='checkbox' name='genres' id='"+genre.id+genre.name+"' value='"+genre.id+"'>"+
						"<label for='"+genre.id+genre.name+"'>"+genre.name+"</label><br>")
						.appendTo(".genres");
				});

				//обновление фильтра авторов
				$(".authors").empty();
				$("<summary><b>Авторы</b></summary>").appendTo(".authors");
				authors.forEach(function(author){
					$("<input type='checkbox' name='authors' id='"+author.id+author.name+"' value='"+author.id+"'>"+
						"<label for='"+author.id+author.name+"'>"+author.name+"</label><br>")
						.appendTo(".authors");
				});
			}
			catch(error){ alert(error); }
		},
		error: function(){
			alert("error");
		}
	});
}