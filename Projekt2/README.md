# Vue-Laravel-Mysql PlayerBoard

## Frontend Implementation:
- The frontend code is implemented using the Vue.js framework.
- The main page is the "HomeView.vue" component, which represents the leaderboard screen.
- It displays a list of players with their names, points, and buttons to add or subtract points.
- The leaderboard can be sorted by name or points by clicking the corresponding headers.
- There is a search input box to filter players by name.
- Clicking on a player's name toggles the display of their details, including name, age, points, and address.
- There is a button to add a new player, which navigates to the "RegistrationForm.vue" component.
- In the registration form, the user can enter the player's name, age, and address. Points are automatically set to 0.
- The form performs client-side validation, checking the length of the name and age range.
- When the form is submitted, an HTTP POST request is made to the backend API to add the player.
- The API endpoints are defined in the Laravel backend.

## Backend Implementation:
- The backend is implemented using the Laravel framework.
- Make sure you have MySQL and Apache. Install XAMPP and run MySQL and Apache.
- The table is created in schema 'spring_ajay' and the table name is players.
- The "Players" model represents the players table in the database and defines the fillable fields.
- The fields used were Name, Age, Points and Address.
- The "AddPlayerRequest" class defines the validation rules for adding and updating players.
  - All the fields are required.
  - Age and Points are integer, and Name and Address are String.
  - Age must be between 3 to 150.
  - Name must have 3-20 characters and must be unique for every players.
- The API endpoints are defined in the "PlayerController" class in the "Api" namespace.
  - The "index" method returns all players from the database.
  - The "show" method retrieves a specific player based on the provided ID.
  - The "store" method receives a request with player data and creates a new player in the database.
  - The "update" method receives a request with player data and updates the corresponding player in the database.
  - The "destroy" method deletes a player from the database.
 
## Docker
- I also did using Docker containers.
- The Vue and Laravel containers worked correctly.
- I was not able verify MySQL container as I reached limit for number of images I can build.
- ### Frontend Docker :-
  - Build Stage:
    - FROM node:lts-alpine as build-stage: Specifies the base image for the build stage, which is an Alpine-based Node.js image.
    - WORKDIR /app: Sets the working directory inside the container to /app.
    - COPY package*.json ./: Copies the package.json and package-lock.json files from the host into the container's working directory.
    - RUN npm install: Runs the npm install command to install the dependencies specified in the package.json file.
    - COPY . .: Copies all the files and folders from the host into the container's working directory.
    - RUN npm run build: Executes the npm run build command, which typically builds the Vue.js or frontend application and generates the production-ready assets.
      
  -Production Stage:
    - FROM nginx:stable-alpine as production-stage: Specifies the base image for the production stage, which is an Alpine-based Nginx image.
    - COPY --from=build-stage /app/dist /usr/share/nginx/html: Copies the built assets from the build stage container (/app/dist) into the Nginx container's default web root directory (/usr/share/nginx/html), where the static files are served.
    - EXPOSE 80: Informs Docker that the container listens on port 80 and makes it available for communication with other containers or the host.
    - CMD ["nginx", "-g", "daemon off;"]: Defines the command to run when the container starts. In this case, it starts the Nginx web server in the foreground and keeps the container running with the daemon off; configuration.
  
  Overall, this Dockerfile is used to create a container image for a Vue.js or frontend application. The build stage installs the necessary dependencies, builds the application, and generates the production-ready assets. The production stage uses Nginx as the web server and copies the built assets into the appropriate location, making them available for serving to clients.

- ### Backend Docker :-
  - Base Image and Working Directory:
    - `FROM php:8.1-cli`: Sets the official PHP 8.1 image as the base image for the container.
    - `WORKDIR /var/www/html`: Sets the working directory inside the container to `/var/www/html`, where the application files will be placed.

  - System Dependencies:
    - `RUN apt-get update && apt-get install -y`: Updates the package repository and installs system dependencies.
    - `libonig-dev`: Installs the Oniguruma regular expression library.
    - `libzip-dev`: Installs the libzip library for working with ZIP archives.
    - `unzip`: Installs the Unzip utility for extracting ZIP archives.
    - `docker-php-ext-install pdo_mysql mbstring zip`: Installs PHP extensions required for Laravel, such as pdo_mysql, mbstring, and zip.

  - Copy Application Files:
    - `COPY . /var/www/html`: Copies all the files and folders from the host into the container's working directory (`/var/www/html`).

  - Composer Installation and Dependency Installation:
    - `RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer`: Downloads and installs Composer globally in the container.
    - `RUN composer install --optimize-autoloader --no-dev`: Runs `composer install` to install project dependencies specified in `composer.json`. The `--optimize-autoloader` flag optimizes the autoloader for better performance, and the `--no-dev` flag skips installing development dependencies.

  - Expose Port:
    - `EXPOSE 9000`: Informs Docker that the container listens on port 9000 and makes it available for communication with other containers or the host.

  - Migration and Laravel Development Server:
    - `CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=9000`: Specifies the command to run when the container starts. It runs Laravel database migrations using `php artisan migrate --force` and then starts the Laravel development server using `php artisan serve` on the specified host and port.

  This Dockerfile is specifically tailored for a Laravel application. It sets up the necessary PHP version, installs system dependencies, copies the application files, installs Composer, installs project dependencies, exposes the desired port, and runs database migrations and the Laravel development server within the container.    

###   PHP ARTISAN commands that were used:-
The following commands were used during the backend implementation to create the necessary files, set up the database, define the API endpoints, and handle the validation of player data.
- To create the Players model and migration, the following command was executed: 
     php artisan make:model Players -m
   This command generates the Players model class and creates a corresponding migration file.
- The migration file created in the previous step was used to define the players table structure. To migrate the database and create the players table, the following command was executed:  
     php artisan migrate
- To create the PlayerController, which handles the API endpoints for players, the following command was executed:  
     php artisan make:controller Api/PlayerController
   This command generates the PlayerController class in the `Api` namespace.
- The `route:list` command was used to view the list of registered routes in the application. It provides an overview of the available API endpoints and their corresponding controller methods. The command was executed as follows:
     php artisan route:list
- The AddPlayerRequest class was created to define the validation rules for adding and updating players. This request class extends the FormRequest class provided by Laravel. The following command was executed to generate the AddPlayerRequest class:
     php artisan make:request AddPlayerRequest
- To generate the PlayerFactory class, which is used for seeding the players table with dummy data, the following command was executed:
     php artisan make:factory PlayerFactory --model=Players
  This command generates the PlayerFactory class and associates it with the Players model.

## Instructions to Run the Application:
- Clone this repository and navigate to the project directory.
### In backend/
- Make sure you have Node.js and Composer installed on your system.
- Install the backend dependencies by running the following command:
    composer install
- Create a copy of the .env.example file and rename it to .env.
- Generate a new application key by running the following command:
    php artisan key:generate
- Create a new MySQL database for the application.
- Update the database configuration in the .env file with your MySQL database credentials.
- Migrate the database by running the following command:
    php artisan migrate
- Start the Laravel development server by running the following command:
    php artisan serve
### In vue-frontend/
- Install the frontend dependencies by running the following command:
    npm install
- In a separate terminal, start the frontend development server by running the following command:
    npm run serve
- Open your web browser and access the application at the provided URL (e.g., http://localhost:8080).

### To run the application using Docker:-
  - No need to follow above mentioned steps.
  - Run the following two commands in top folder:
      docker-compose build --no-cache
      docker-compose up -d --force-recreate 

## Unit Test Cases for both frontend and backend

### For Backend

The provided test cases demonstrate how to write feature tests for the Laravel backend. These tests ensure that the API endpoints for player operations are functioning correctly. Here's a breakdown of the test cases:

-testGetAllPlayers:
  - This test case verifies that the API endpoint for retrieving all players returns a successful response with the correct JSON structure.
  - It uses the Players model factory to create three player records in the database.
  - The test sends a GET request to the /api/players endpoint and asserts that the response status is 200 (OK) and that the JSON response contains three players.

-testGetSinglePlayer:
  - This test case verifies that the API endpoint for retrieving a single player returns a successful response with the correct JSON structure.
  - It uses the Players model factory to create a player record in the database.
  - The test sends a GET request to the /api/players/{id} endpoint, where {id} is the ID of the created player, and asserts that the response status is 200 (OK) and that the JSON response matches the player's name, age, points, and address.

- testCreatePlayer:
  - This test case verifies that the API endpoint for creating a new player returns a successful response.
  - It sends a POST request to the /api/players endpoint with the required player data.
  - The test asserts that the response status is 200 (OK) and that the JSON response contains the message "Player added".

- testUpdatePlayer:
  - This test case verifies that the API endpoint for updating a player returns a successful response.
  -  It uses the Players model factory to create a player record in the database.
  - The test sends a PUT request to the /api/players/{id} endpoint, where {id} is the ID of the created player, with the updated player data.
  - The test asserts that the response status is 200 (OK) and that the JSON response contains the message "Player updated".

-testDeletePlayer:
  - This test case verifies that the API endpoint for deleting a player returns a successful response.
  - It uses the Players model factory to create a player record in the database.
  - The test sends a DELETE request to the /api/players/{id} endpoint, where {id} is the ID of the created player.
  - The test asserts that the response status is 200 (OK) and that the JSON response contains the message "Player deleted".

### For Frontend

The provided test cases demonstrate how to write unit tests for the frontend components using Vue Test Utils and the `vitest` testing library. Here's a breakdown of the test cases for both the HomeView and RegistrationForm components:

- HomeView Test Cases:
  - `renders leaderboard heading correctly`: This test verifies that the leaderboard heading is rendered correctly in the HomeView component. It mounts the HomeView component, finds the heading element, and asserts that its text matches the expected value.
  - `filters players by name correctly`: This test simulates filtering players by name in the HomeView component. It sets a search query in the input field, retrieves the filtered players, and checks if all filtered players have names that include the search query.
  - `clears filter when "Clear Filter" button is clicked`: This test verifies that the filter is cleared correctly when the "Clear Filter" button is clicked. It sets a search query, triggers the click event on the clear filter button, and checks if the filtered players array is reset to the original list of all players.
  - `sorts players by name correctly`: This test checks if the players are sorted by name correctly when the "Sort by Name" button is clicked. It triggers the click event on the sort by name button and compares the sorted players array with the expected sorted array.

- RegistrationForm Test Cases:
  - `displays the "Player Registration" heading correctly`: This test ensures that the "Player Registration" heading is displayed correctly in the RegistrationForm component. It mounts the RegistrationForm component, finds the heading element, and asserts that its text matches the expected value.
  - `correctly binds form fields to the player data object`: This test checks if the form fields in the RegistrationForm component correctly bind to the player data object. It sets values for the form fields, triggers the input events, and asserts that the player object's properties are updated accordingly.

These test cases cover various aspects of the frontend components, such as rendering, filtering, clearing filters, sorting, and form field binding. They help ensure that the components behave as expected and provide the desired functionality.
