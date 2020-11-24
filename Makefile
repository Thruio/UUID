all: php-cs-fixer php-cs phpstan phpunit

phpstan:
	vendor/bin/phpstan analyse

phpunit:
	vendor/bin/phpunit

php-cs-fixer:
	vendor/bin/php-cs-fixer fix

php-cbf:
	vendor/bin/phpcbf --standard=PSR2 src/ tests/

php-cs:
	vendor/bin/phpcs --warning-severity=6 --standard=PSR2 src/ tests/