# Weather App

### Introduction
This is an application that allows a user to view the observed (in the past 30 days) or forecasted (in the future) daily weather conditions for a given location using the [Dark Sky API](https://darksky.net/dev/docs).

**Demo URL:** [https://darksky-app.herokuapp.com](https://darksky-app.herokuapp.com/)


### Tech Stack

- The Frontend of the app is built using tailwindcss, webpack and JQuery. I intend to use VeuJS to do the frontend in the near future.

- PHPUnit is used for both unit tests and integration tests. (PS: It's advisable to run the tests using docker) 


### Local Setup

Follow the steps below to run this application locally

1 - Clone this git repository and `cd` into it

```bash
$ git clone git@bitbucket.org:bePolite/technical-assignment.git
$ cd technical-assignment
```

2 - Copy the `.env.example` file into `.env`

```bash
$ cp .env.example .env
```

3 - Run the docker container

```bash
$ docker-compose up -d
```

4 - Run the bash shell of the workspace docker container

```bash
$ docker exec -it dark-sky-workspace /bin/bash
```

5 - Install `composer` dependencies and `npm` dependencies with `yarn` inside the docker container

```bash
$ composer install
$ yarn
```

6 - Generate the laravel application key

```bash
$ php artisan key:generate
```

8 - Open your browser and visit localhost: [http://localhost:8080](http://localhost:8080).

### Running Tests

To run tests, setup the application using the setup process shown above and run `phpunit` inside the workspace container


```bash
$ docker exec -it --user=laradock laradock_workspace_1 /bin/bash
$ phpunit
```