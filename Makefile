it-now: clean install test

clean:
	docker-compose kill
	docker-compose rm -f

install:
	docker-compose run --rm cli bash -c "composer install"

update:
	docker-compose run --rm cli bash -c "composer update"

test:
	docker-compose run --rm cli bash -c "cd /data/SilMock/tests; ./phpunit"

