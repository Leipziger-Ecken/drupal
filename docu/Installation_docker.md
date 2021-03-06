# Installing/Running Leipziger Ecken with docker

## Preparations

- copy `.env.example` to `.env` and adjust the file

## Start in development

```
$ cd bin
$ ./start_development.sh
```

Now open `http://localhost` in your browser and follow the instructions.
When prompted for the database connection select `Mysql` as Database type and enter the appropriate values for Mysql host, name, user and password as previously entered into the .env file.

Also create your admin user.

## Start in production

```
$ cd bin
$ ./start_production.sh
```

Application will run on port 9000

## Stop

```
$ cd bin
$ ./stop.sh
```

## Update for production

```
$ cd bin
$ ./update_production.sh
```

This will:

- pull the latest code
- fix file permissions
- import config
- update the database
- clear the cache


## Get a terminal into the running container

```
$ cd bin
$ ./bash.sh
```

The terminal will run under the `www-data` user.

## Fix file permissions

Sometimes you get errors due to file permissions.
To fix them do

```
$ cd bin
$ ./fix_permissions.sh
```
