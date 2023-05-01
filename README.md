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

After install successfully, you will see:
- A custom menu "Admin > Jokes FWW", include 4 sub-menus: List, Settings, Migrate, Logs, Manage data.
- A custom Content Type and it fields. The configuration files (.yml) are stored in [config/install] folder and will be loaded when a module is enabled.
```
 Custom Entity: 'jokes'
 Custom fields: 
 - field_id: joke id
 - field_content: joke message
 - field_url: joke url
 - field_created: 
```

## Screens / Features
1. Settings screen: Provide a user interface for configuring the API connection, including
API endpoint URL and data mapping settings.

Access "Admin > Jokes FFW > Settings", or url:
```
 url: /jokes_api/settings
 - api url: default is 'https://api.chucknorris.io/jokes/random'
 - custom entity type: default is 'jokes'
 - page size: the number of rows per page, also a number of rows per import.
 - auto publish: default is 'False'. Auto assign entity status to 1 / 0 to entity after migration.
 - show published only: only show published entities (with status = 1)
```

2. Migrate screen: Provide a user interface for importing data from the API into Drupal,
including selecting which data to import.

Access "Admin > Jokes FFW > Migrate", or url:
```
 url: /jokes_api/migrate
 input params:
 - number of imported rows per time: 5
 - api_url: will get value from settings screen.
 - entity_type: will get value from settings screen.
 - auto publish: will get value from settings screen.
```

Or you can manually imgate data, using drush command:
```
vendor/bin/drush migrate:import jokes_api_json 
```

3. Jokes list screen. Provide a view for displaying the imported data on the site, including
filtering and sorting options.

Access "Admin > Jokes FFW > List", or url:
```
 /jokes_api/list
```
This screen contains two parts:
- Part 1: show random joke. It get data directly from api_url and display it randomly each time.
- Part 2: a table with pager: show imported jokes with pager.

5. Logs screen: Show error handling and logging for API communication and data import errors.

Access "Admin > Jokes FFW > Logs", or url:
```
/admin/reports/dblog?type%5B%5D=jokes_api
```

6. Manage data: Show jokes data with commands: edit/ addnew/ publish/ delete one or many jokes

Access "Admin > Jokes FFW > Manage data", or url:
```
/admin/content?type=jokes
```

## Technical hightlights: bellow is techniques / features applied in this project
- Use Clean architecture: use Service class for all common functions.
- Use Dependency Injection to inject Service class into Block, Controller.
- Use GuzzleHttp and Promise (Async) to get multiple data from api: Sync function will not work.
- Use migrate_plus to allow manual migration using drush.
- Dynamically create Block instance and render in page 
- Best practices of Drupal custom module development: Cusom Block, Service, Form, Controller, Custom Node config, Install config, Migrate config, Log ..
