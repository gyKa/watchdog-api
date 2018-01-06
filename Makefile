DATETIME=`date +'%Y%m%d%H%M%S'`

install:
	composer install --no-interaction
	cp .env.example .env

server:
	php -S localhost:8000 -t public/

deploy:
	composer install --no-interaction --no-dev
	git ftp push --disable-epsv
	composer install --no-interaction
	git tag $(DATETIME)
	git push origin $(DATETIME)

qa: parallel-lint phpcs phpmd phpcpd phpunit

parallel-lint:
	vendor/bin/parallel-lint -e php public/index.php

phpcs:
	vendor/bin/phpcs --standard=PSR2 public/index.php

phpmd:
	vendor/bin/phpmd public/index.php text codesize,unusedcode,naming

phpcpd:
	vendor/bin/phpcpd public/index.php

phpunit:
	vendor/bin/phpunit

coverage:
	vendor/bin/phpunit --coverage-clover=coverage.xml
