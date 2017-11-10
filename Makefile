install:
	composer install
	cp .env.example .env

server:
	php -S localhost:8000 -t public/

deploy:
	git ftp push

qa: phpcs phpmd

phpcs:
	vendor/bin/phpcs --standard=PSR2 public/index.php

phpmd:
	vendor/bin/phpmd public/index.php text codesize,unusedcode,naming
