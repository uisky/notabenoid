// РђРґСЂРµСЃ СЃРµСЂРІРµСЂРЅРѕРіРѕ СЃРєСЂРёРїС‚Р°, РѕР±СЂР°Р±Р°С‚С‹РІР°СЋС‰РµРіРѕ РіРѕР»РѕСЃРѕРІР°РЅРёРµ.
var vote_url = '/sys/vote.php';

// РЎРёРјРІРѕР»С‹ РѕС‚РѕР±СЂР°Р¶Р°СЋС‰РёРµ РїРѕР»РѕР¶РёС‚РµР»СЊРЅС‹Р№ Рё РѕС‚СЂРёС†Р°С‚РµР»СЊРЅС‹Р№ Р·РЅР°Рє РіРѕР»РѕСЃР°.
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


// РџРѕ Р·Р°РіСЂСѓР·РєРµ РґРѕРєСѓРјРµРЅС‚Р° РѕР±СЂР°Р±Р°С‚С‹РІР°РµРј РІСЃРµ РЅРµРѕР±С…РѕРґРёРјС‹Рµ em-СЌР»РµРјРµРЅС‚С‹ Рё
// РІРµС€Р°РµРј РЅР° РЅРёС… РѕР±СЂР°Р±РѕС‚С‡РёРє СЃРѕР±С‹С‚РёСЏ mouseover.
$(function() { Ratingize(); });

// Р¤СѓРЅРєС†РёСЏ РѕР±СЂР°Р±РѕС‚РєРё СЃРѕР±С‹С‚РёСЏ РЅР°РІРµРґРµРЅРёСЏ РјС‹С€РєРё РЅР° em-СЌР»РµРјРµРЅС‚. 
// РЎРѕР·РґР°РµРј Рё РѕС‚РєСЂС‹РІР°РµРј РїР°РЅРµР»СЊ РіРѕР»РѕСЃРѕРІР°РЅРёСЏ.
function open_vote_box(event) {
	// Р—Р°РєСЂС‹РІР°РµРј РїР°РЅРµР»СЊ РіРѕР»РѕСЃРѕРІР°РЅРёСЏ РЅР° РґСЂСѓРіРѕРј СЌР»РµРјРµРЅС‚Рµ, 
	// РµСЃР»Рё РѕРЅР° РµС‰Рµ РїРѕРєР°Р·С‹РІР°РµС‚СЃСЏ.
	close_vote_box();
	
	id = parse_em_id(event.target.id);
	if (!id) { return; }
	
	em = $(this);
	var document_width = $(document).width();
	var document_height = $(document).height();
	
	var vote_max = rating_vote_max[id['area']];
	
	// РЎРѕР·РґР°РµРј РїР°РЅРµР»СЊ РіРѕР»РѕСЃРѕРІР°РЅРёСЏ СЃ РІР°СЂРёР°РЅС‚Р°РјРё РіРѕР»РѕСЃРѕРІ 
	// РІ Р·Р°РІРёСЃРёРјРѕСЃС‚Рё РѕС‚ Р·РЅР°С‡РµРЅРёСЏ rating_vote_max СЃРѕРѕС‚РІРµС‚СЃС‚РІСѓСЋС‰РµР№ area
	var vote_box = '<div id="vote_box"><table><tr>';
	if (vote_max == 1) {
		vote_box += '<td><em class="vote">' + plus_sign + '</em></td>';
	} else {
		for (var i = 1, l = vote_max; i <= l; i++) {
			vote_box += '<td><em class="vote">' + plus_sign + i + '</em></td>';
		}
	}
	vote_box += '</tr><tr><td colspan="' + vote_max + '"' +
			' style="text-align: center"><em>' + em.html() + '</em>' +
			'</td></tr><tr>';
	if (vote_max == 1) {
		vote_box += '<td><em class="vote">' + minus_sign + '</em></td>';			// !!!
	} else {
		for (var i = 1, l = vote_max; i <= l; i++) {
			vote_box += '<td><em class="vote">' + minus_sign + i + '</em></td>';
		}
	}
	vote_box += '</tr></table></div>';
//	em.parent().append(vote_box);
    $('body').append(vote_box);

	vote_box = $('div#vote_box');
	
	// Р’С‹СЃС‚Р°РІР»СЏРµРј РїРѕР»РѕР¶РµРЅРёРµ РїР°РЅРµР»Рё РіРѕР»РѕСЃРѕРІР°РЅРёСЏ РІ Р°Р±СЃРѕР»СЋС‚РЅС‹С… РєРѕРѕСЂРѕРґРёРЅР°С‚Р°С…
	// РІ Р·Р°РІРёСЃРёРјРѕСЃС‚Рё РѕС‚ СЂР°Р·РјРµСЂРѕРІ РїР°РЅРµР»Рё, СЂР°Р·РјРµСЂРѕРІ em-СЌР»РµРјРµРЅС‚Р° Рё scroll-Р·РЅР°С‡РµРЅРёР№
	// РґРѕРєСѓРјРµРЅС‚Р°. Р’ СЃР»СѓС‡Р°Рµ, РµСЃР»Рё РїР°РЅРµР»СЊ РІС‹Р»РµР·Р°РµС‚ Р·Р° РіСЂР°РЅРёС†С‹ РґРѕРєСѓРјРµРЅС‚Р°, СЃРґРІРёРіР°РµРј РµРµ
	// РІ СЃРѕРѕС‚РІРµС‚СЃС‚РІСѓСЋС‰СѓСЋ СЃС‚РѕСЂРѕРЅСѓ.
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
	
	// РќР° РґРІРёР¶РµРЅРёРµ РјС‹С€Рё РІРЅСѓС‚СЂРё РїР°РЅРµР»Рё РіРѕР»РѕСЃРѕРІР°РЅРёСЏ РІРµС€Р°РµРј РѕР±СЂР°Р±РѕС‚С‡РёРє, РєРѕС‚РѕСЂС‹Р№
	// РёРЅРёС†РёРёСЂСѓРµС‚ СЃРѕР·РґР°РЅРёРµ Рё РїРѕСЏРІР»РµРЅРёРµ РїРѕРґР»РѕР¶РєРё.
	vote_box.one('mouseover', function() {
		$('body').append('<div id="vote_box_bg" />');
		$('div#vote_box_bg').mouseover(close_vote_box)
				.css('height', document_height + 'px')
				.css('width', document_width + 'px');
	});
	
	// Р”Р»СЏ РєР°Р¶РґРѕР№ РєРЅРѕРїРєРё РіРѕР»РѕСЃРѕРІР°РЅРёСЏ РІРµС€Р°РµРј РѕР±СЂР°Р±РѕС‚С‡РёРє РєР»РёРєР° 
	// Рё РѕР±СЂР°Р±РѕС‚С‡РёРє РЅР°РІРµРґРµРЅРёСЏ РјС‹С€Рё (РґР»СЏ РёР·РјРµРЅРµРЅРёСЏ СЃС‚РёР»СЏ РєРЅРѕРїРєРё)
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

// Р¤СѓРЅРєС†РёСЏ Р·Р°РєСЂС‹С‚РёСЏ РїР°РЅРµР»Рё РіРѕР»РѕСЃРѕРІР°РЅРёСЏ. РЈРґР°Р»СЏРµС‚ РїР°РЅРµР»СЊ Рё РїРѕРґР»РѕР¶РєСѓ.
function close_vote_box() {
	$('div#vote_box').remove();
	$('div#vote_box_bg').remove();
};

// Р¤СѓРЅРєС†РёСЏ РѕР±СЂР°Р±РѕС‚РєРё СЃРѕР±С‹С‚РёСЏ РєР»РёРєР° РїРѕ РєРЅРѕРїРєРµ РіРѕР»РѕСЃРѕРІР°РЅРёСЏ.
// РћРїСЂРµРґРµР»СЏРµС‚ Р·РЅР°С‡РµРЅРёРµ РіРѕР»РѕСЃР° Рё РёРЅРёС†РёСЂСѓРµС‚ РѕС‚РїСЂР°РІРєСѓ Р·Р°РїСЂРѕСЃР° Рє СЃРµСЂРІРµСЂРЅРѕРјСѓ СЃРєСЂРёРїС‚Сѓ.
function vote(id, v) {
	$('em#'+id['em_id']).addClass('voted');
	data = {'area' : id['area'], 'id' : id['id'], 'v' : v}
	$.get(
		vote_url,
		data,
		function (data) {
			data = eval(data);
			$('em#'+id['em_id']).html(data['rating']).removeClass('voted');
		}
	);
}

// Р¤СѓРЅРєС†РёСЏ РїР°СЂСЃРёРЅРіР° Р°С‚СЂРёР±СѓС‚Р° id em-СЌР»РµРјРµРЅС‚Р°. 
function parse_em_id(em_id) {
	var foo = em_id.match(/^x(.)(\d+)$/);
	if (!foo) { return null; }
	return {'area' : foo[1], 'id' : foo[2], 'em_id' : em_id};
}