install:
	composer install
	cp .env.example .env

server:
	php -S localhost:8000 -t public/

deploy:
	git ftp push

qa: parallel-lint phpcs phpmd phpcpd

parallel-lint:
	vendor/bin/parallel-lint -e php public/index.php

phpcs:
	vendor/bin/phpcs --standard=PSR2 public/index.php

phpmd:
	vendor/bin/phpmd public/index.php text codesize,unusedcode,naming

phpcpd:
	vendor/bin/phpcpd public/index.php
