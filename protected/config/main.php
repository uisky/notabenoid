<?php
return array(
	"basePath" => dirname(__FILE__).DIRECTORY_SEPARATOR."..",
	"name" => "Notabenoid.Org",
	"language" => "ru",
	"sourceLanguage" => "en",

	"preload" => array("log", "bootstrap"),

	"import"=>array(
		"application.models.*",
		"application.components.*",
		"ext.yii-mail.YiiMailMessage",
	),

	"modules"=>array(
	),

	"components" => array(
		"request" => array(
			"enableCookieValidation" => true,
		),
		"urlManager" => array(
			"urlFormat" => "path",
			"showScriptName" => false,
			"rules" => array(
				'users/<id:\d+>' => 'users/books',
				'users/<id:\d+>/<action:\w+>' => 'users/<action>',
				'users/<id:\d+>/translations/<book_id:\d+>' => 'users/translations',

				'book/<book_id:\d+>/blog' => 'bookBlog/index',
				'book/<book_id:\d+>/blog/<post_id:\d+>' => 'bookBlog/post',
				'book/<book_id:\d+>/blog/<post_id:\d+>/c<comment_id:\d+>/<action>' => 'bookBlog/comment_<action>',
				'book/<book_id:\d+>/blog/<post_id:\d+>/<action:\w+>' => 'bookBlog/<action>',
				'book/<book_id:\d+>/blog/edit' => 'bookBlog/edit',

				'book/<book_id:\d+>/announces' => 'announces/book',
				'book/<book_id:\d+>/announces/<post_id:\d+>' => 'announces/post',
				'book/<book_id:\d+>/announces/<post_id:\d+>/c<comment_id:\d+>/<action>' => 'announces/comment_<action>',
				'book/<book_id:\d+>/announces/<post_id:\d+>/<action:\w+>' => 'announces/<action>',
				'book/<book_id:\d+>/announces/write' => 'announces/edit',

				'book/<book_id:\d+>/edit' => 'bookEdit/index',
				'book/<book_id:\d+>/edit/<action:\w+>' => 'bookEdit/<action>',

				'book/<book_id:\d+>' => 'book/index',
				'book/<book_id:\d+>/<chap_id:\d+>' => 'chapter/index',
				'book/<book_id:\d+>/<chap_id:\d+>/<orig_id:\d+>' => 'orig/index',
				'book/<book_id:\d+>/<chap_id:\d+>/<orig_id:\d+>/c<comment_id:\d+>/<action:\w+>' => 'orig/comment_<action>',
				'book/<book_id:\d+>/<chap_id:\d+>/<orig_id:\d+>/<action:\w+>' => 'orig/<action>',
				'book/<book_id:\d+>/<chap_id:\d+>/<action:\w+>' => 'chapter/<action>',
				'book/<book_id:\d+>/<action:\w+>' => 'book/<action>',

				'blog/<post_id:\d+>' => 'blog/post',
				'blog/<post_id:\d+>/c<comment_id:\d+>/<action>' => 'blog/comment_<action>',
				'blog/<post_id:\d+>/<action:\w+>' => 'blog/<action>',

				'chat/room/<room_id:\d+>' => 'chat/room',

				'my/comments' => 'myComments/index',
				'my/comments/<action:\w+>' => 'myComments/<action>',

				'my/bookmarks' => 'Bookmarks/index',
				'my/bookmarks/<action:\w+>' => 'Bookmarks/<action>',

				'my/mail/' => 'mail/index',
				'my/mail/<id:\d+>' => 'mail/message',
				'my/mail/<action:\w+>' => 'mail/<action>',

				'catalog/<cat_id:\d+>' => 'catalog/index',

				'site/login' => 'register/login',
			),
		),
		"db" => [
			"connectionString" => "pgsql:host=localhost;dbname=notabenoid",
			"username" => "notabenoid",
			"password" => "",
			"charset" => "utf8",
			"emulatePrepare" => true,
			"enableProfiling" => true,
			"schemaCachingDuration" => 6 * 60 * 60,
			"queryCacheID" => "cache",
		],
		"session" => [
			"class" => "CHttpSession",
			"cookieParams" => [
				"lifetime" => 86400 * 365
			],
			"timeout" => 86400 * 365
		],
		"cache" => [
			"class" => "system.caching.CMemCache",
			"servers" => [
				["host" => "localhost", "port" => 11211, "weight" => 100],
			],
			"keyPrefix" => "nb"
		],
		"readycache" => array(
			"class" => "application.components.ReadyCache",
			"directoryLevel" => "3",
			"gCProbability" => 0,	// garbage collection - только вручную, ну его нахуй
		),
		'errorHandler' => array(
			'errorAction'=>'site/error',
		),
		'log'=>array(
			'class' => 'CLogRouter',
			'routes' => [
				['class'=>'CFileLogRoute', 'levels'=>'error, warning'],
			],
		),
		"widgetFactory" => array(
			"widgets" => array(
				"CActiveForm" => array(
				),
				"TbPager" => array(
					"maxButtonCount" => 20,
					"header" => "<div class='pagination'>",
					"footer" => "</div>",
					"displayFirstAndLast" => true,
					"firstPageLabel" => "&laquo;&laquo;&laquo;",
					"lastPageLabel" => "&raquo;&raquo;&raquo;",
					"nextPageLabel" => "&raquo;",
					"prevPageLabel" => "&laquo;",
				),
				"CLinkPager" => array(
					"maxButtonCount" => 20,
					"cssFile" => "/css/pager.css",
					"header" => false,
					"firstPageLabel" => "&laquo;&laquo;&laquo;",
					"lastPageLabel" => "&raquo;&raquo;&raquo;",
					"nextPageLabel" => "&raquo;",
					"prevPageLabel" => "&laquo;",
				),
				"CGridView" => array(
					"template" => "{pager}\n{items}\n{pager}",
					"cssFile" => "/css/grid.css",
					"rowCssClass" => "",
					"selectableRows" => 0,
				),
				"CHtmlPurifier" => array(
					"options" => array(
						"HTML.Allowed" => "a[href],b,strong,i,em,u",
					)
				)
			),
		),

		/**
		* 3rd party-компоненты
		*/
		"mail" => array(
			"class" => "ext.yii-mail.YiiMail",
			"transportType" => "php",
			"viewPath" => "application.views.email",
		),
		"bootstrap" => array(
			'class' => 'ext.bootstrap.components.Bootstrap',
			'coreCss' => true,
			'responsiveCss' => true,
			'plugins' => array(
				'transition' => false, // disable CSS transitions
				'tooltip' => array(
					'selector' => 'a.tooltip', // bind the plugin tooltip to anchor tags with the 'tooltip' class
					'options' => array(
						'placement' => 'bottom', // place the tooltips below instead
					),
				),
			),
		),
		'curl' => array(
			'class' => 'application.extensions.curl.Curl',
			"options" => array(
				"timeout" => 30,
				"setOptions" => array(
					CURLOPT_USERAGENT => "Notabenoid.Org Translation Service (support@notabenoid.org)",
					CURLOPT_RANGE => "0-2048000",   // Качаем не более 2 мегов
					CURLOPT_TIMEOUT => 15,
				),
			),
		),
		"filecache" => array(
			"class" => "system.caching.CFileCache",
		),

		/**
		* Мои компоненты
		*/
		'user' => array(
			"class" => "application.components.WebUser",
			'allowAutoLogin' => true,
			"autoRenewCookie" => true,
		),
		"langs" => array(
			"class" => "application.components.Langs",
		),
		"parser" => array(
			"class" => "application.components.Parser",
		),
	),


	'params' => array(
		"domain" => "notabenoid.org",
		"passwordSalt" => "------------------------------------>>> ПРИДУМАЙТЕ СЮДА ЧТО-НИБУДЬ !!! <<<------------------",
		"adminEmail" => 'support@notabenoid.org',
		"commentEmail" => "comment@notabenoid.org",
		"systemEmail" => "no-reply@notabenoid.org",
		/**
		 * Тип регистрации: OPEN - открытая, INVITE - по инвайтам
		 */
		"registerType" => "OPEN",

		"HTMLPurifierOptions" => array(
			"HTML.Allowed" => "a[href],b,strong,i,em,u,s,blockquote,table,tr,th,td,ul,ol,li,dl,dt,dd,br,img[src],small,sub,sup,font[color],span,abbr,*[title],code,tt",
		),
		"sex" => array("m" => "мужчина", "f" => "женщина", "x" => "существо"),
		"countries" => array(
			"",
			1 => "Россия", 2 => "Украина", 3 => "Белоруссия", 4 => 'Абхазия', 5 => 'Австралия', 6 => 'Австрия', 7 => 'Азербайджан', 8 => 'Албания', 9 => 'Алжир',
			10 => 'Ангола', 11 => 'Андорра', 12 => 'Антигуа и Барбуда', 13 => 'Аргентина', 14 => 'Армения', 15 => 'Афганистан', 16 => 'Багамы', 17 => 'Бангладеш', 18 => 'Барбадос', 19 => 'Бахрейн',
			20 => 'Белиз', 21 => 'Бельгия', 22 => 'Бенин', 23 => 'Болгария', 24 => 'Боливия', 25 => 'Босния и Герцеговина', 26 => 'Ботсвана', 27 => 'Бразилия', 28 => 'Бруней', 29 => 'Буркина-Фасо',
			30 => 'Бурунди', 31 => 'Бутан', 32 => 'Вазиристан', 33 => 'Вануату', 34 => 'Ватикан', 35 => 'Великобритания', 36 => 'Венгрия', 37 => 'Венесуэла', 38 => 'Восточный Тимор (Тимор-Лешти)', 39 => 'Вьетнам',
			40 => 'Габон', 41 => 'Гаити', 42 => 'Гайана', 43 => 'Гамбия', 44 => 'Гана', 45 => 'Гватемала', 46 => 'Гвинея', 47 => 'Гвинея-Бисау', 48 => 'Германия', 49 => 'Гондурас',
			50 => 'Гренада', 51 => 'Греция', 52 => 'Грузия', 53 => 'Дания', 54 => 'Джибути', 55 => 'Доминика', 56 => 'Доминиканская Республика', 57 => 'Египет', 58 => 'Замбия', 59 => 'Зимбабве',
			60 => 'Израиль', 61 => 'Индия', 62 => 'Индонезия', 63 => 'Иордания', 64 => 'Ирак', 65 => 'Иран', 66 => 'Ирландия', 67 => 'Исландия', 68 => 'Испания', 69 => 'Италия',
			70 => 'Йемен', 71 => 'Кабо-Верде', 72 => 'Казахстан', 73 => 'Камбоджа', 74 => 'Камерун', 75 => 'Канада', 76 => 'Катар', 77 => 'Кения', 78 => 'Кипр', 79 => 'Киргизия Киргизия',
			80 => 'Кирибати', 81 => 'Китай', 82 => 'Коморские острова', 83 => 'Республика Конго', 84 => 'Конго, Демократическая Республика (Заир)', 85 => 'Колумбия', 86 => 'Корея (Северная)', 87 => 'Корея (Южная)', 88 => 'Косово', 89 => 'Коста-Рика',
			90 => 'Кот-д\'Ивуар', 91 => 'Куба', 92 => 'Кувейт', 93 => 'Лаос', 94 => 'Латвия', 95 => 'Лесото', 96 => 'Либерия', 97 => 'Ливан', 98 => 'Ливия', 99 => 'Литва',
			100 => 'Лихтенштейн', 101 => 'Люксембург', 102 => 'Маврикий', 103 => 'Мавритания', 104 => 'Мадагаскар', 105 => 'Македония', 106 => 'Малави', 107 => 'Малайзия', 108 => 'Мали', 109 => 'Мальдивы',
			110 => 'Мальта', 111 => 'Марокко', 112 => 'Маршалловы Острова', 113 => 'Мексика', 114 => 'Мозамбик', 115 => 'Молдавия', 116 => 'Монако', 117 => 'Монголия', 118 => 'Мьянма', 129 => 'Нагорно-Карабахская Республика',
			120 => 'Намибия', 121 => 'Науру', 122 => 'Непал', 123 => 'Нигер', 124 => 'Нигерия', 125 => 'Нидерланды', 126 => 'Никарагуа', 127 => 'Новая Зеландия', 128 => 'Норвегия', 139 => 'Объединённые Арабские Эмираты',
			130 => 'Оман', 131 => 'Пакистан', 132 => 'Палау', 133 => 'Панама', 134 => 'Папуа', 135 => 'Парагвай', 136 => 'Перу', 137 => 'Польша', 138 => 'Португалия', 149 => 'Приднестровская Молдавская Республика',
			140 => 'Пунтленд', 141 => 'Руанда', 142 => 'Румыния', 143 => 'Сальвадор', 144 => 'Самоа', 145 => 'Сан-Марино', 146 => 'Сан-Томе и Принсипи', 147 => 'Саудовская Аравия', 148 => 'Свазиленд', 159 => 'Сейшельские острова',
			150 => 'Сенегал', 151 => 'Сент-Винсент и Гренадины', 152 => 'Сент-Киттс и Невис', 153 => 'Сент-Люсия', 154 => 'Сербия', 155 => 'Силенд', 156 => 'Сингапур', 157 => 'Сирия Сирия', 158 => 'Словакия', 169 => 'Словения',
			160 => 'Соединённые Штаты Америки', 161 => 'Соломоновы Острова', 162 => 'Сомали', 163 => 'Сомалиленд', 164 => 'Судан', 165 => 'Суринам', 166 => 'Сьерра-Леоне', 167 => 'Таджикистан', 168 => 'Таиланд', 179 => 'Тайвань',
			170 => 'Тамил-Илам', 171 => 'Танзания', 172 => 'Того', 173 => 'Тонга', 174 => 'Тринидад и Тобаго', 175 => 'Тувалу', 176 => 'Тунис', 177 => 'Туркменистан', 178 => 'Турция', 189 => 'Турецкая Республика Северного Кипра',
			180 => 'Уганда', 181 => 'Узбекистан', 182 => 'Уругвай', 183 => 'Федеративные Штаты Микронезии', 184 => 'Фиджи', 185 => 'Филиппины', 186 => 'Финляндия', 187 => 'Франция', 188 => 'Хорватия', 199 => 'Центрально-Африканская Республика',
			190 => 'Чад', 191 => 'Черногория', 192 => 'Чехия', 193 => 'Чили', 194 => 'Швейцария', 195 => 'Швеция', 196 => 'Шри-Ланка', 197 => 'Эквадор', 198 => 'Экваториальная Гвинея', 199 => 'Эритрея',
			200 => 'Эстония', 201 => 'Эфиопия', 202 => 'Южно-Африканская Республика', 203 => 'Южная Осетия', 204 => 'Ямайка', 205 => 'Япония'
		),
		"month_acc" => array("", "января", "февраля", "марта", "апреля", "мая", "июня", "июля", "августа", "сентября", "октября", "ноября", "декабря"),
		"month_in" => array("", "январе", "феврале", "марте", "апреле", "мае", "июне", "июле", "августе", "сентябре", "октябре", "ноябре", "декабре"),
		"encodings" => array(
			// iconv-название => человеческое название
			"UTF-8" => "UTF-8",
			"CP1251" => "Windows-1251 (Кириллица Windows)",
			"CP1252" => "Windows-1252 (Западноевропейская)",
			"KOI8-R" => "KOI8-R (русская KOI8)",
			"KOI8-U" => "KOI8-U (украинская KOI8)",
			"utf-16" => "Unicode UTF-16 (16-битный юникод)",
			"MacCyrillic" => "MacCyrillic (Кириллица Macintosh)",
			"MacCentralEurope" => "MacCentralEurope (Центральная Европа Macintosh)"
		),
		"book_types" => array("A" => "текст", "S" => "субтитры"),
		"catalog_branches" => array(1 => "S", 2 => "A", 3 => "A"),
		"book_topics" => array(
			'S' => array(
				0 => "Сериал",
				1 => "Мультфильм",
				2 => "Документальный фильм",
				3 => "Фантастика",
				4 => "Комедия",
				5 => "Драма",
				6 => "Боевик, приключения",
				7 => "Ужасы, триллер",
				8 => "Детектив",
				9 => "Мелодрама",
				10 => "Мюзикл",
			),
			'A' => array(
				0 => "Классика",
				1 => "Художественная литература",
				10 => "Научная фантастика",
				2 => "Техническая литература",
				7 => "Для детей",
				3 => "Поэзия",
				4 => "Публицистика",
				5 => "Научные статьи",
				6 => "Коллективное творчество",
				8 => "Комиксы",
				9 => "Игры",
				11 => "Стихи и песни",
			),
		),
		"ac_areas" =>  array(
			"ac_read" => "войти", "ac_trread" => "видеть все версии", "ac_gen" => "скачивать", "ac_rate" => "оценивать", "ac_comment" => "комментировать", "ac_tr" => "переводить",
			"ac_blog_r" => "читать блог", "ac_blog_c" => "комментировать в блоге", "ac_blog_w" => "писать посты в блоге",
			"ac_announce" => "создавать анонсы перевода", "ac_membership" => "управлять членством в группе перевода",
			"ac_chap_edit" => "редактировать оригинал", "ac_book_edit" => "редактировать описание перевода",
		),
		"ac_areas_chap" => array("ac_read" => "читать", "ac_trread" => "видеть все версии", "ac_gen" => "скачивать", "ac_rate" => "оценивать", "ac_comment" => "комментировать", "ac_tr" => "переводить"),
		"ac_roles" => array("a" => "все", "g" => "группа", "m" => "модераторы", "o" => "никто"),
		"ac_roles_title" => array("a" => "все", "g" => "только члены группы перевода", "m" => "только модераторы", "o" => "только владелец"),

		"translation_statuses" => array(
			0 => "",
			1 => "идёт перевод",
			2 => "перевод редактируется",
			3 => "перевод готов",
		),
		"translation_statuses_short" => array(
			0 => "",
			1 => "переводится",
			2 => "редактируется",
			3 => "готово",
		),

		"blog_topics" => array(
			"book" => array(	// 1 - 19
				1 => "Обсуждение оригинала",
				2 => "Перевод",
				3 => "Общение",
			),
			"common" => array(	// 40 - 79
				64 => "Новости проекта",
				65 => "Техподдержка",
				66 => "Общение",
				67 => "Юмор",
				69 => "Как это перевести?",

//				70 => array("Стройплощадка", "can" => "betatest", "manifest" => "Этот блог доступен только участникам Стройплощадки.", "side_view" => "betatest_side"),
			),
			"announce" => array(	// 80 - 89
				81 => "Ищем переводчиков",
				82 => "Готово",
				89 => "Всякое"
			),
		),

		"ENVIRONMENT" => "production",
		"version" => "3.3"
	),
);