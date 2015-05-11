CBook.prototype.bookmark = function(book_id) {
	BM.set(book_id, null, function(data) {
		if(data.status == "rm") {
			$("#btn-bookmark").html("<i class='icon-star-empty'></i> Поставить закладку");
		} else {
			$("#btn-bookmark").html("<i class='icon-star'></i> Изменить закладку");
		}
	});
};
