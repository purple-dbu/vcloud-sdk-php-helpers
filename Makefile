EXT := $$(echo $$OS | egrep ^Windows >/dev/null && echo -n .bat)

SHOW_COVERAGE = \
	[ -e build/logs/coverage.txt ] \
	&& echo \
	&& echo ======== Code coverage ======== \
	&& cat build/logs/coverage.txt | grep -A3 Summary | tail -n 3 \
	&& echo ===============================

all: composer.lock
	# Dependencies are up-to-date

clean:
	[ ! -d vendor ] || rm -Rf vendor
	[ ! -e composer.lock ] || rm -f composer.lock
	[ ! -e composer.phar ] || rm -f composer.phar
	[ ! -d tests/_files ] || rm -Rf tests/_files
	[ ! -e tests/_files.tar.gz ] || rm -Rf tests/_files.tar.gz

# Testing

lint: composer.lock
	vendor/bin/phpcs$(EXT) --standard=PSR1 src/ tests/
	vendor/bin/phpcs$(EXT) --standard=PSR2 src/ tests/

tests/_files.tar.gz:
	make lint \
	&& [ ! -d tests/_files ] || rm -Rf tests/_files \
	&& [ ! -e tests/_files.tar.gz ] || rm -Rf tests/_files.tar.gz \
	&& vendor/bin/phpunit$(EXT) --configuration phpunit-integration.xml \
	&& cd tests/_files/ && tar cvfz ../_files.tar.gz *.json \
	&& [ ! -d tests/_files ] || rm -Rf tests/_files

tests/_files: tests/_files.tar.gz
	[ -d tests/_files ] || mkdir -p tests/_files
	tar xvfz tests/_files.tar.gz -C tests/_files

test: composer.lock tests/_files
	make lint \
	&& vendor/bin/phpunit$(EXT) --coverage-html build/logs/coverage --coverage-text=build/logs/coverage.txt \
	&& $(SHOW_COVERAGE)

# Composer

composer.lock: composer.phar composer.json
	[ -e composer.lock ] && php composer.phar update || php composer.phar install
	touch composer.lock

composer.phar:
	curl -s http://getcomposer.org/installer | php
