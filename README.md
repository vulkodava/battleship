Momchil Milev Battleship Game.

Purposes of this project. 
    This is an open source project built on Zend Framework v2 with Doctrine ORM v2. This code is meant to be used for 
best practices implementation and creating a simple yet powerful version of the most popular game among the developers
recruitment - Battleships.

    v1 of the game implements several basic features 
        - Creation of game battlefield of 10x10 plates.
        - Selecting and random positioning of 3 vessels - 1 Battleship of 5 squares and 2 Destroyers of 4 squares.
        - Implementing a web and console versions of the game that share basic game logic behind the scene.
        - Providing a simple click way for firing a shot for web version.
        - Providing a simple input method for selecting coordinates for the shot for the web version.
        - Providing a simple parameters based way of firing shots for the console application.
        - Implementing a backdoor cheat path by which the user can spot the computer's vessels.
        - Preparing the field for v2 features.
            - Setting up the database in order to accept v2 feature of user login, user statistics, game statistics,
              administration of the game configuration.
            - Setting up the code in a way that will provide easy way of adding user functionality.
        - Creating of Install script that setups the initial minimal needed configuration:
            - Creates the database;
            - Inserts records for field size, shot sings.
            - Inserts records for vessel types.
            - Installs needed vendor code.
        - AJAX-ify the web and mobile apps. (TBD)

    v2 implementation of more sophisticated features like:
        - User login.
        - User games statistics.
        - Game statistics.
        - Administration of the game configuration like battle field size, vessels types, vessels sizes, vessels count.

    v3 implementation of Unit Tests.

INSTALL STEPS:
	* A Working demo of the game is available at http://battleship.momchil-milev.info/
	* A Git Repository of the code (Without the Vendors) is available at https://github.com/vulkodava/battleship

    v1
        1. Run (sudo) composer install in the project root dircetory or copy docs/vendor folder into htdocs/vendor;
        2. Import docs/zend_battleship2.sql or run forward engineering of Workbench using file docs/BattleshipDB_v2.mwb and then run the following INSERTS for a valid initial game configuration.
		!!! NOTE: Insert only if using Forwar Engieering. In the SQL file these inserts are included.
		INSERT INTO `game_config` (`id`, `name`, `value`, `created_at`, `updated_at`, `deleted_at`) VALUES
		(1, 'x', '10', '2015-01-06 08:44:47', NULL, NULL),
		(2, 'y', '10', '2015-01-06 08:44:47', NULL, NULL),
		(3, 'hit_sign', 'x', '2015-01-06 08:44:58', NULL, NULL),
		(4, 'miss_sign', '-', '2015-01-06 08:44:58', NULL, NULL),
		(5, 'no_shot_sign', '.', '2015-01-06 08:45:06', NULL, NULL);

		INSERT INTO `vessel_types` (`id`, `name`, `size`, `vessels_count`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
		(1, 'Battleship', 5, 1, 1, '2015-01-06 08:45:24', NULL, NULL),
		(2, 'Destroyer', 4, 2, 1, '2015-01-06 08:45:24', NULL, NULL);

    v2 - TBD - folder install/ will hold a Zend Framework Tool for installation of a new copy of the game, including all the shell commands for vendor libraries and SQL Statements.
