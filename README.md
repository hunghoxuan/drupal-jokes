# drupal-jokes
# Project description
Develop a custom Drupal module that integrates with a third-party
API (https://api.chucknorris.io/jokes/random). The module should allow users to import data from
the API into Drupal and display it on the site.

Module files structure:
```
├── jokes_api
    ├── config
    │   └── install: configuration for custom entity node ('jokes') with fields ('field_id', 'field_url', 'field_content', 'field_created') 
    ├── css: custom css
    ├── js: custom javascript
    ├── migrations: configuration for migrate_plus file
    ├── src
    │   ├── Controller: logic & ui build of List screen
    │   ├── Form: logic & ui build Settings, Migration forms
    │   ├── Service: Service class for common functions
    │   └── Plugin:
    │       ├── Block: Custom block to display Jokes
    │       └── migrate_plus: Custom data parser to convert 
    ├── templates: include twig theme templates
    ├── tests: test folder
    ├── jokes_api.module: include custom hook for install & uninstall (delete entities with node 'jokes')
    ├── jokes_api.routing.yml: routing file, include 3 files
    └── jokes_api.info.yml
```
## Setup
1. Requirements
- drupal 10
- drush/drush: latest
- drupal/migrate_plus: latest

2. Install module:
Option 1: Use UI: 
- copy jokes_api folder into drupal project [\{drupalroot}\web\custom\jokes_api]
  or use command:
 ```
 cd drupal_root\web\modules\custom
 git clone https://github.com/hunghoxuan/drupal-jokes jokes_api 
 ```
- goto Admin > Extends > Select module Jokes_api > click Install

Option 2: Manually install:
- enable jokes_api in Drupal UI (Admin > Manage > Extends)

Option 3: Use drush
```
drush en jokes_api 
```

3. Migrate/ Import data:
Option 1: User UI:
- goto Admin > Jokes > Migrate (/jokes_api/migrate). Enter params (auto published / number of imported rows) and click [Migrate]

Option 2: use drush
```
vendor/bin/drush migrate:import jokes_api_json 
```

## Results / Features
1. Developed & tested on Environment:
- PHP 8.2.0
- SQLite
- Drupal 10.x
2. Provide a user interface for configuring the API connection, including
API endpoint URL and data mapping settings.
```
 url: /jokes_api/settings
 - api url: 'https://api.chucknorris.io/jokes/random'
 - custom entity type: 'jokes'
```
3. Provide a user interface for importing data from the API into Drupal,
including selecting which data to import.
```
 url: /jokes_api/migrate
 input params:
 - number of imported rows per time: 5
 - api_url
 - entity_type
```
4. The imported data is stored in custom Drupal entities that are defined by the
module.
```
 Custom Entity: 'jokes'
 Custom fields: 
 - field_id: joke id
 - field_content: joke message
 - field_url: joke url
 - field_created: 
```
5. Provide a view for displaying the imported data on the site, including
filtering and sorting options.
```
 /jokes_api/list
```
6. The module should implement proper error handling and logging for API communication
and data import errors.

7. The module should include appropriate documentation, including installation instructions,
configuration options, and usage examples.

## Technical hightlights: bellow is techniques / features applied in this project
- Use Clean architecture: use Service class for all common functions.
- Use Dependency Injection to inject Service class into Block, Controller.
- Use GuzzleHttp and Promise (Async) to get multiple data from api: Sync function will not work.
- Use migrate_plus to allow manual migration using drush.
- Dynamically create Block instance and render in page 
- Best practices of Drupal custom module development: Cusom Block, Service, Form, Controller, Custom Node config, Install config, Migrate config, Log ..

