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

qa: parallel-lint phpcs phpmd phpcpd phpstan phpunit

parallel-lint:
	vendor/bin/parallel-lint -e php public/ src/ tests/

phpcs:
	vendor/bin/phpcs --standard=PSR2 public/ src/ tests/

phpmd:
	vendor/bin/phpmd public/,src/,tests/ text codesize,unusedcode,naming

phpcpd:
	vendor/bin/phpcpd public/ src/ tests/

phpunit:
	vendor/bin/phpunit

phpstan:
	vendor/bin/phpstan analyse --level 7 src/ tests/ public/

coverage:
	vendor/bin/phpunit --coverage-clover=coverage.xml
