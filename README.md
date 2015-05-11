# Notabenoid
> Система коллективного перевода текстов

Давным-давно был такой умучанный копирастами сайт — http://notabenoid.com/. А здесь мы можем
видеть исходники этого сайта, распространяемые под лицензией Beerware, что означает, что вы можете
использовать весь этот говнокод как хотите.

С вашей стороны при использовании кода было бы любезно где-нибудь на сайте оставить ссылку на
[автора](http://facebook.com/uisky), каковой автор является мрачным и необщительным типом, а потому
**не даёт справок и не консультирует по вопросам установки, модификации и поддержки программы**,
а также не интересуется ничьим мнением о качестве кода, тем более что и сам придерживается о нём весьма
невысокого мнения.

Исходники эти выложены с минимальными правками и не были расчитаны на свободное распространение, поэтому
в процессе установки вас может поджидать много сюрпризов. Будет здорово, если какие-нибудь пряморукие люди
улучшат код в сторону его более простой распространяемости и напишут более человеческую документацию. 

## Требования
Нам понадобятся:

  * php 5.5 или выше
  * phpшные модули: gd, pdo-pgsql, curl, memcache
  * postgresql 9.1 или выше
  * memcached

## Установка
1. Клонируем репозиторий в какую-нибудь директорию, допустим, `/srv/notabenoid.com`
2. Натравливаем веб-сервер отдавать статику из `/srv/notabenoid.com/www` и все прочие запросы редиректить в index.php.
   В терминах nginx это будет выглядеть так:

		server {
			server_name notabenoid.com;
			listen 80;
			root /srv/notabenoid.com/www;
			index index.php;
			location / {
				try_files $uri $uri/ /index.php?$args;
			}
			location ~ \.php$ {
				fastcgi_split_path_info ^(.+\.php)(/.+)$;
				fastcgi_pass unix:/var/run/php5-fpm.sock;
				fastcgi_index index.php;
				include fastcgi_params;
			}
			location ~ ^/(asserts|img|js|css) {
				try_files $uri =404;
			}
		}

3. Веб-сервер должен уметь писать в следующие директории:
     * /www/assets
     * /www/i/book
     * /www/i/upic
     * /www/i/tmp
     * /protected/runtime

4. Создаём в постгресе базу, юзера и скармливаем дамп:

        sudo -u postgres createuser -E -P notabenoid
        sudo -u postgres createdb -O notabenoid notabenoid
        psql -U notabenoid < init.sql

5. Настало время охуительных конфигов! В /protected/config/main.php найдите строки

		"connectionString" => "pgsql:host=localhost;dbname=notabenoid",
		"username" => "notabenoid",
		"password" => "",

	и пропишите туда название постгресной базы, юзера и пароль. Чуть ниже в строках 

		"passwordSalt" => "Ел сам в Акчарлаке кал рачка в масле",
		"domain" => "notabenoid.org",
		"adminEmail" => 'support@notabenoid.org',
		"commentEmail" => "comment@notabenoid.org",
		"systemEmail" => "no-reply@notabenoid.org",

	напишите любую херню в элементе "passwordSalt", а в остальных элементах - название вашего домена и почтовые
	адреса, которые будут стоять в поле "From" всякого спама, который рассылает сайт. Аналогичный трюк надбно
	провести с файлом `/protected/config/console.php`

6. В крон прописываем:

		0 0 * * * /usr/bin/php /srv/notabenoid.com/protected/yiic maintain midnight
		0 4 * * * /usr/bin/php /srv/notabenoid.com/protected/yiic maintain dailyfixes
		
	и последнюю команду (`/usr/bin/php /srv/notabenoid.com/protected/yiic maintain dailyfixes`) непременно
	исполняем сами.

7. Теперь, по идее, вся эта херня должна взлететь. Зарегистрируйте первого пользователя и пропишите его
   логин в группах со спецправами в переменной `private static $roles` в файле `/protected/components/WebUser.php`.
   Полагаю, также будет мудро несколько подправить основной шаблон (`/protected/views/layouts/v3.php`) и морду
   (`/protected/views/site/index.php`).
   
*чмг-лов, Митя Уйский.*
