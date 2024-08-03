test:
	php vendor/bin/phpunit

consume_game_scheduler:
	php bin/console messenger:consume scheduler_game -vv

consume_async:
	php bin/console messenger:consume async -vv
