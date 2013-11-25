EXT := $$(echo $$OS | egrep ^Windows >/dev/null && echo -n .bat)

SHOW_COVERAGE = \
	[ -e build/logs/coverage.txt ] && \
	echo && \
	echo ======== Code coverage ======== && \
	cat build/logs/coverage.txt | grep -A3 Summary | tail -n 3 && \
	echo ===============================

all: composer.lock
	# Dependencies are up-to-date

clean:
	[ -d vendor ] && rm -Rf vendor
	[ -e composer.lock ] && rm -f composer.lock
	[ -e composer.phar ] && rm -f composer.phar

# Testing

lint: composer.lock
	vendor/bin/phpcs$(EXT) --standard=PSR1 src/ tests/
	vendor/bin/phpcs$(EXT) --standard=PSR2 src/ tests/



unit: composer.lock tests/config.php
	make lint && \
	vendor/bin/phpunit$(EXT) --coverage-html build/logs/coverage --coverage-text=build/logs/coverage.txt && \
	$(SHOW_COVERAGE)

test: composer.lock tests/config.php
	make lint && \
	vendor/bin/phpunit$(EXT) --coverage-html build/logs/coverage --coverage-text=build/logs/coverage.txt && \
	vendor/bin/phpunit$(EXT) --configuration phpunit-functional.xml && \
	$(SHOW_COVERAGE)

# Composer

composer.lock: composer.phar composer.json
	[ -e composer.lock ] && php composer.phar update || php composer.phar install
	touch composer.lock

composer.phar:
	curl -s http://getcomposer.org/installer | php
