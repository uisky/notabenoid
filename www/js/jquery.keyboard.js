(function ($) {	
	$.fn.keyboard = function () {
		$k.bind(this, arguments);
		return this;
	}

	$.keyboard = function () {
		$k.bind($(document), arguments);
		return this;
	}
	
	var $k = {
		setup : {
			'strict' : true,
			'event'  : 'keydown',
			'preventDefault' : false
		},
		keys : {
			cont : [],
			getCodes : function () {
				var codes = [];
				for (var i = 0; i < $k.keys.cont.length; i++) {
					codes.push($k.keys.cont[i].keyCode);
				}
				return codes;
			},
			add : function (e) {
				if (e.keyCode == 0) {
					// throw 'ZeroKeyCodeException';
				} else {
					$k.keys.rm(e);
					$k.keys.cont.push(e);
					$k.keys.dump();
				}
			},
			rm : function (e) {
				for (var i = 0; i < $k.keys.cont.length; i++) {
					if ($k.keys.cont[i].keyCode == e.keyCode) {
						$k.keys.cont.splice(i, 1);
						return;
					}
				}
			},
			clear : function () {
				$k.keys.cont = [];
			},
			dump : function () {
				// console.log($k.keys.getCodes().join('; '));
			}
		},
		keyCodes : {
			// Alphabet
			a:65, b:66, c:67, d:68, e:69,
			f:70, g:71, h:72, i:73, j:74,
			k:75, l:76, m:77, n:78, o:79,
			p:80, q:81, r:82, s:83, t:84,
			u:85, v:86, w:87, x:88, y:89, z:90,
			// Numbers
			n0:48, n1:49, n2:50, n3:51, n4:52,
			n5:53, n6:54, n7:55, n8:56, n9:57,
			// Controls
			tab:  9, enter:13, shift:16, backspace:8,
			ctrl:17, alt  :18, esc  :27, space    :32,
			menu:93, pause:19, cmd  :91,
			insert  :45, home:36, pageup  :33,
			'delete':46, end :35, pagedown:34,
			// F*
			f1:112, f2:113, f3:114, f4 :115, f5 :116, f6 :117,
			f7:118, f8:119, f9:120, f10:121, f11:122, f12:123,
			// numpad
			np0: 96, np1: 97, np2: 98, np3: 99, np4:100,
			np5:101, np6:102, np7:103, np8:104, np9:105,
			npslash:11,npstar:106,nphyphen:109,npplus:107,npdot:110,
			// Lock
			capslock:20, numlock:144, scrolllock:145,
			
			// Symbols
			equals: 61, hyphen   :109, coma  :188, dot:190,
			gravis:192, backslash:220, sbopen:219, sbclose:221,
			slash :191, semicolon: 59, apostrophe : 222,
			// Arrows
			aleft:37, aup:  38, aright:39, adown:40
		},
		parseArgs : function (args) {
			if (typeof args[0] == 'object') {
				return {
					setup : args[0]
				};
			} else {
				var secondIsFunc = (typeof args[1] == 'function');
				var isDelete = !secondIsFunc && (typeof args[2] != 'function');
				var argsObj = {};
				argsObj.keys = args[0];
				if ($.isArray(argsObj.keys)) {
					argsObj.keys = argsObj.keys.join(' ');
				}
				if (isDelete) {
					argsObj.isDelete = true;
				} else {
					argsObj.func = secondIsFunc ? args[1] : args[2];
					argsObj.cfg  = secondIsFunc ? args[2] : args[1];
					if (typeof argsObj.cfg != 'object') {
						argsObj.cfg = {};
					}
					argsObj.cfg = $.extend(clone($k.setup), argsObj.cfg);
				}
				return argsObj;
			}
		},
		getIndex : function (keyCodes, order) {
			return (order == 'strict') ?
				's.' + keyCodes.join('.') :
				'f.' + clone(keyCodes).sort().join('.');
		},
		getIndexCode : function (index) {
			if ($k.keyCodes[index]) {
				return $k.keyCodes[index];
			} else {
				throw 'No such index: «' + index + '»';
			}
		},
		getRange : function (title) {
			var c = $k.keyCodes;
			var f = arguments.callee;
			switch (title) {
				case 'letters'  : return range (c['a']  ,   c['z']);
				case 'numbers'  : return range (c['n0'] ,   c['n9']);
				case 'numpad'   : return range (c['np0'],   c['np9']);
				case 'fkeys'    : return range (c['f1'] ,   c['f12']);
				case 'arrows'   : return range (c['aleft'], c['adown']);
				case 'symbols'  : return [
					c.equals, c.hyphen, c.coma, c.dot, c.gravis, c.backslash,
					c.sbopen, c.sbclose, c.slash, c.semicolon, c.apostrophe,
					c.npslash, c.npstar, c.nphyphen,c.npplus,c.npdot
				];
				case 'allnum'   : return f('numbers').concat(f('numpad'));
				case 'printable': return f('letters').concat(
				                         f('allnum') .concat(
				                         f('symbols')))
				default         : throw 'No such range: «' + title + '»';
			}
		},
		stringGetCodes : function (str) {
			var parts;
			str = str.toLowerCase();
			if (str.match(/^\[[\w\d\s\|\)\(\-]*\]$/i)) { // [ space | (letters) | (n4-n7) ]
				var codes = [];
				parts = str
					.substring(1, str.length-1)
					.replace(/\s/, '')
					.split('|');
				for (var i = 0; i < parts.length; i++) {
					var p = $k.stringGetCodes(parts[i])
					codes = codes.concat(p);
				}
				return codes;
			} else if (str.match(/^\([\w\d\s\-]*\)$/i)) { // (n4-n7)
				parts = str
					.substring(1, str.length-1)
					.replace(/\s/, '')
					.split('-');
				if(parts.length == 2) {
					return range(
						$k.getIndexCode(parts[0]),
						$k.getIndexCode(parts[1])
					);
				} else {
					return $k.getRange(parts[0]);
				}
			} else {
				return [$k.getIndexCode(str)];
			}
		},
		getCodes : function (keys) {
			// ['shift', 'ctrl'] => [16, 17]
			var keycodes = [];
			for (var i = 0; i < keys.length; i++) {
				var key = keys[i];
				if (!isNaN(key)) { // is_numeric
					key = [1 * key];
				} else if (typeof key == 'string') {
					key = $k.stringGetCodes(key);
				} else {
					throw 'Wrong key type: «' + (typeof key) + '»';
				}
				keycodes.push(key);
			}
			return keycodes;
		},
		parseKeysString : function (str) {
			var parts = str.split(',');
			for (var i = 0; i < parts.length; i++) {
				var string = $.trim(parts[i]);
				parts[i] = {};
				parts[i].order = string.indexOf('+') >= 0 ? 'strict' : 'float';
				parts[i].codes = $k.getCodes(
					string.split(parts[i].order == 'strict' ? '+' : ' ')
				);
				parts[i].index = $k.getIndex(parts[i].codes, parts[i].order);
				parts[i].group = i;
			}
			return parts;
		},
		match : function (bind) {
			var k, i, matched, cur = undefined;
			var cont  = $k.keys.getCodes();
			var codes = clone(bind.keys.codes);
			var eventIndexes = [];
			if (codes.length == 0) {
				return false;
			}
			if (bind.keys.order == 'strict') {
				for (i = 0; i < cont.length; i++) {
					if (!codes.length) {
						break;
					}
					if (cur === undefined) {
						cur = codes.shift();
					}
					if (inArray(cont[i], cur)) {
						cur = undefined;
						eventIndexes.push(i);
					} else if (bind.cfg.strict) {
						return false;
					}
				}
				return (codes.length === 0 && cur === undefined) ?
					eventIndexes : false;
			} else {
				for (i = 0; i < codes.length; i++) {
					matched = false;
					for (k = 0; k < codes[i].length; k++) {
						cur = $.inArray(codes[i][k], cont);
						if (cur >= 0) {
							eventIndexes.push(cur);
							matched = true;
							break;
						}
					}
					if (!matched) {
						return false;
					}
				}
				if (bind.cfg.strict) {
					for (i = 0; i < cont.length; i++) {
						matched = false;
						for (k in codes) {
							if (inArray(cont[i], codes[k])) {
								matched = true;
								break;
							}
						}
						if (!matched) {
							return false;
						}
					}
				}
				return eventIndexes;
			}
		},
		hasCurrent : function (bind, e) {
			var last = bind.keys.codes.length - 1;
			return (bind.keys.order == 'strict') ?
				inArray  (e.keyCode, bind.keys.codes[last]) :
				inArrayR (e.keyCode, bind.keys.codes);
		},
		checkBinds : function ($obj, e) {
			var ei, okb = $obj.keyboardBinds;
			for (var i in okb) {
				var bind = okb[i];
				if (bind.cfg.event == e.originalEvent.type) {
					ei = $k.match(bind);
					if ( ei && $k.hasCurrent(bind, e) ) {
						var backup = $obj.keyboardFunc;
						var events = [];
						for (var k in ei) {
							events.push($k.keys.cont[ei[k]])
						}
						$obj.keyboardFunc = bind.func;
						$obj.keyboardFunc(events, bind);
						$obj.keyboardFunc = backup;
						if (bind.cfg.preventDefault) {
							e.preventDefault();
						}
					}
				}
			}
		},
		bind : function ($obj, args) {
			args = $k.parseArgs(args);
			if (args.setup) {
				$k.setup = $.extend($k.setup, args.setup);
			} else {
				if (!$obj.keyboardBinds) {
					$obj.keyboardBinds = {};
					$obj
					.keydown(function (e) {
						$k.keys.add(e);
						$k.checkBinds($obj, e);
					})
					.keyup(function (e) {
						$k.checkBinds($obj, e);
					});
				}
				// {keys, func, cfg}
				var parts = $k.parseKeysString(args.keys);
				for (var i = 0; i < parts.length; i++) {
					if (args.keys.isDelete) {
						$obj.keyboardBinds[parts[i].index] = undefined;
					} else {
						$obj.keyboardBinds[parts[i].index] = clone(args);
						$obj.keyboardBinds[parts[i].index].keys = parts[i];
					}
				}
			}
		},
		init : function () {
			$(document)
				.keydown ( $k.keys.add   )
				.keyup (function (e) {
					setTimeout(function () {
						$k.keys.rm(e)
					}, 0);
				})
				.blur ( $k.keys.clear );
		}
	}

	var inArrayR = function (value, array) {
		for (var i = 0; i < array.length; i++) {
			if (typeof array[i] == 'object' || $.isArray(array[i])) {
				if (inArrayR(value, array[i])) {
					return true;
				}
			} else if (value == array[i]) {
				return true;
			}
		}
		return false;
	}

	var inArray = function (value, array) {
		return ($.inArray(value, array) != -1);
	};

	var range = function (from, to) {
		var r = [];
		do {
			r.push(from);
		} while (from++ < to)
		return r;
	};

	var clone = function (obj) {
		var newObj, i;
		if ($.isArray(obj)) {
			newObj = [];
			for (i = 0; i < obj.length; i++) {
				newObj[i] = (typeof obj[i] == 'object' || $.isArray(obj[i]))
					? clone(obj[i]) : obj[i];
			}
		} else {
			newObj = {};
			for (i in obj) {
				newObj[i] = (typeof obj[i] == 'object' || $.isArray(obj[i]))
					? clone(obj[i]) : obj[i];
			}
		}
		return newObj;
	};

	$k.init();
})(jQuery);
