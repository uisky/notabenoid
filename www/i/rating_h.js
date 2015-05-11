// Адрес серверного скрипта, обрабатывающего голосование.
var vote_url = '/sys/vote.php';

// Символы отображающие положительный и отрицательный знак голоса.
var plus_sign = '+';
var minus_sign = '\u2013';


function Ratingize() {
	$('em').each(function() {
		if (!this.id) { return; }
		id = parse_em_id(this.id);
		if (!id || rating_vote_max[id['area']] == 0) { return; }
		$(this).mouseover(open_vote_box);
	});
}


// По загрузке документа обрабатываем все необходимые em-элементы и
// вешаем на них обработчик события mouseover.
$(function() { Ratingize(); });

// Функция обработки события наведения мышки на em-элемент.
// Создаем и открываем панель голосования.
function open_vote_box(event) {
	// Закрываем панель голосования на другом элементе,
	// если она еще показывается.
	close_vote_box();

	id = parse_em_id(event.target.id);
	if (!id) { return; }

	em = $(this);
	var document_width = $(document).width();
	var document_height = $(document).height();

	var vote_max = rating_vote_max[id['area']];

	// Создаем панель голосования с вариантами голосов
	// в зависимости от значения rating_vote_max соответствующей area
	var vote_box = '<div id="vote_box"><table><tr>';
	if (vote_max == 1) {
		vote_box += '<td><em class="vote">' + minus_sign + '</em></td>';
	} else {
		for (var i = 1, l = vote_max; i <= l; i++) {
			vote_box += '<td><em class="vote">' + minus_sign + i + '</em></td>';
		}
	}
	vote_box += '<td>' +
			"<em onclick=\"rate_click('" + event.target.id + "')\">" + em.html() + '</em>' +
			'</td>';
	if (vote_max == 1) {
		vote_box += '<td><em class="vote">' + plus_sign + '</em></td>';
	} else {
		for (var i = 1, l = vote_max; i <= l; i++) {
			vote_box += '<td><em class="vote">' + plus_sign + i + '</em></td>';
		}
	}
	vote_box += '</tr></table></div>';
//	em.parent().append(vote_box);
    $('body').append(vote_box);

	vote_box = $('div#vote_box');

	// Выставляем положение панели голосования в абсолютных коородинатах
	// в зависимости от размеров панели, размеров em-элемента и scroll-значений
	// документа. В случае, если панель вылезает за границы документа, сдвигаем ее
	// в соответствующую сторону.
	var offset = em.offset();
	if (jQuery.browser.msie && em.parent().attr('class') == 'x') {
		var parent_div = em.parent().parent();
		var foo = parent_div.attr('class').match(/^ind_(\d+)$/i);
		if (foo) {
			offset['left'] -= (parseInt(parent_div.css('margin-left')) + parseInt(parent_div.parent().css('margin-left')));
		}
	}
	var left = offset['left'] - ((vote_box.outerWidth() - em.outerWidth()) / 2) + offset['scrollLeft'];
	if (left < 0) {
		left = 0;
	} else if (left + vote_box.outerWidth() > document_width) {
		left = document_width - vote_box.outerWidth();
	}
	var top = offset['top'] - ((vote_box.outerHeight() - em.outerHeight()) / 2) + offset['scrollTop'];
	if (top < 0) {
		top = 0;
	} else if (top + vote_box.outerHeight() > document_height) {
		top = document_height - vote_box.outerHeight();
	}

	vote_box.css('left', left + 'px');
	vote_box.css('top', top + 'px');

	// На движение мыши внутри панели голосования вешаем обработчик, который
	// инициирует создание и появление подложки.
	vote_box.one('mouseover', function() {
		$('body').append('<div id="vote_box_bg" />');
		$('div#vote_box_bg').mouseover(close_vote_box)
				.css('height', document_height + 'px')
				.css('width', document_width + 'px');
	});

	// Для каждой кнопки голосования вешаем обработчик клика
	// и обработчик наведения мыши (для изменения стиля кнопки)
	vote_box.find('em.vote').click(function () {
		var v = 0, i;
		if (this.innerHTML == plus_sign) {
			v = 1;
		} else if (this.innerHTML == minus_sign) {						// !!!
			v = -1;
		} else {
			i = this.innerHTML.substr(0, 1) == minus_sign ? -1 : 1;
			v = i * parseInt(this.innerHTML.substr(1));
		}
		vote(id, v);
		close_vote_box();
	}).hover(
		function() { this.className = 'hovered'; },
		function() { this.className = ''; }
	);
};

// Функция закрытия панели голосования. Удаляет панель и подложку.
function close_vote_box() {
	$('div#vote_box').remove();
	$('div#vote_box_bg').remove();
};

// Функция обработки события клика по кнопке голосования.
// Определяет значение голоса и иницирует отправку запроса к серверному скрипту.
function vote(id, v) {
	$('em#'+id['em_id']).addClass('voted');
	data = {'area' : id['area'], 'id' : id['id'], 'v' : v}
	$.get(
		vote_url,
		data,
		function (data) {
			$('em#'+id['em_id']).removeClass('voted')
			data = eval(data);
			if(data.err != "") {
				alert(data.err);
				return false;
			}
			$('em#'+id['em_id']).html(data['rating']);
		}
	);
}

// Функция парсинга атрибута id em-элемента.
function parse_em_id(em_id) {
	var foo = em_id.match(/^x(.)(\d+)$/);
	if (!foo) { return null; }
	return {'area' : foo[1], 'id' : foo[2], 'em_id' : em_id};
}

var rateslist_opened = 0;

function rate_click(id) {
	if(rateslist_opened == id) {
		rateslist_close();
		return;
	}
	rateslist_close();

	rateslist_opened = id;
	var em = $("#" + id);
	em.after("<div id='RatesList'><img class='arr' src='/i/rateslist_arrow.gif' alt='' /><div>Минуточку...</div><p><a href='#' onclick='return rateslist_close()'>закрыть</a></p></div>");
	var offset = em.offset();
	$("#RatesList").css("left", offset.left - 292 + "px");
	$.get("/sys/rates_list.php", {id: id.substr(2)}, function(data) {
		$("#RatesList div").html(data);
	});
}

function rateslist_close() {
	if(rateslist_opened == 0) return;
	$("#RatesList").remove();
	rateslist_opened = 0;
	return false;
}
