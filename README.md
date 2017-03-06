PlugInto
=======================

Introduction
------------
This is ZF2 Application :)

Running (DEV)
------------

    php composer.phar update
	
    ./vendor/bin/doctrine-module orm:validate-schema # E:\xampp\htdocs\pluginto>e:/xampp/htdocs/pluginto/vendor/bin/doctrine-module orm:schema-tool:update

    # ./vendor/bin/doctrine-module orm:schema-tool:drop

    # ./vendor/bin/doctrine-module orm:schema-tool:create

    ./vendor/bin/doctrine-module orm:schema-tool:update
    # check output of update command

    php -S 0.0.0.0:8080 -t public/


UnitTests
------------
    php test/phpunit.phar -c test/phpunit.xml 

