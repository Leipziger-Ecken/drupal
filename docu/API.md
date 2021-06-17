# Leipziger Ecken JSON:API

*Last update: 18.0.2021*

## Introduction

Based on the [JSON:API project]([https://www.drupal.org/project/jsonapi](https://www.drupal.org/project/jsonapi)) (that is now part of Drupal core) and [JSON:API Extras project]([https://www.drupal.org/project/jsonapi_extras](https://www.drupal.org/project/jsonapi_extras)) we provide a public readonly(!) Leipziger Ecken API with full support for sorting, filtering, limiting, extending and paginating Akteure, events, categories, etc. entities through an unified endpoint.  

Full documentation for the JSON:API 1.0 format can be found under [https://jsonapi.org/format/](https://jsonapi.org/format/). In general, the idea behind JSON:API is to return all entities **as flat as possible** in contrast to a deeply nested data structure which contains all resolved relationships. Therefore, you will have to resolve any relationships between entities on the client (these are linked by their uuid).

## Endpoints

A list of all currently available endpoints can be found in the API root under [https://leipziger-ecken.de/jsonapi/](https://leipziger-ecken.de/jsonapi/). At the time of writing, these contain:

|Entity type|URL|
|--|--|
|*(Index)*|https://leipziger-ecken.de/jsonapi/|
|Akteure/Actors|https://leipziger-ecken.de/jsonapi/akteure|
|Veranstaltungen/Events|https://leipziger-ecken.de/jsonapi/events|
|Bezirke/Districts|https://leipziger-ecken.de/jsonapi/districts|
|Akteurtypen/Actor types|https://leipziger-ecken.de/jsonapi/akteur_types|
|Kategorien/Categories|https://leipziger-ecken.de/jsonapi/categories|
|Schlagwörter/Tags|https://leipziger-ecken.de/jsonapi/tags|
|Zielgruppen/Target groups|https://leipziger-ecken.de/jsonapi/target_groups|

 An extensive list of available JSON:API client-implementations for all major script/programming languages can be found [on the official JSON:API website](https://jsonapi.org/implementations/).

## Misc
  
* All field names are mapped to their English equivalent. 
* One requested page contains up to 50 *"data"* items. In order to jump between pages, pagination information is provided under *"links"*.
* To get any linked relationships between entities, use the *include*-parameter. The referenced relationships will be available under *"included"*. Example request containing all image-, category-, and district-data of an Akteur: ```https://leipziger-ecken.de/jsonapi/akteure?include=categories,district,image```
* Support for all CRUD-operations can be [requested](https://leipziger-ecken.de/kontakt) from the project maintainers.
* **Note that [our license](https://creativecommons.org/licenses/by/4.0/) forbids any abusive or incorrect use of the Leipziger Ecken API.** For use in real-world projects, please [inform](https://leipziger-ecken.de/kontakt) the project maintainers and give credits to the original source.
