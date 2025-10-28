cd ..
composer update
cd tests
phpunit
status=$?
exit $status
