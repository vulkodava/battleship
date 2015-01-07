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
        - Providing a simple input method for selecting coordinates for the shot for the web version. (TBD)
        - Providing a simple parameters based way of firing shots for the console application.
        - Implementing a backdoor cheat path by which the user can spot the computer's vessels.
        - Preparing the field for v2 features.
            - Setting up the database in order to accept v2 feature of user login, user statistics, game statistics,
              administration of the game configuration.
            - Setting up the code in a way that will provide easy way of adding user functionality.

    v2 implementation of more sophisticated features like:
        - User login.
        - User games statistics.
        - Game statistics.
        - Administration of the game configuration like battle field size, vessels types, vessels sizes, vessels count.