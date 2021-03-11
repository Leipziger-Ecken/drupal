## Migrate images to media

Turns image-fields of custom content-types into media-(image-)fields. Currently targets:

* le_event.field_le_event_image
* le_akteur.field_le_akteur_image

1. Activate ["Migrate File Entities to Media Entities"](https://www.drupal.org/project/migrate_file_to_media) module and "Leipziger Ecken Migrate file to media" module:

```bash
drush en migrate_file_to_media
drush en le_migrate_media
```

2. Detect & clone image-fields (adds a "_media" suffix per each):

```bash
drush migrate:file-media-fields node le_akteur image image
drush migrate:file-media-fields node le_event image image
```

3. Prepare media entities import (skips any duplicates):

```bash
drush migrate:duplicate-file-detection le_migrate_media_le_akteur_step1
drush migrate:duplicate-file-detection le_migrate_media_le_akteur_step2
drush migrate:duplicate-file-detection le_migrate_media_le_event_step1
drush migrate:duplicate-file-detection le_migrate_media_le_event_step2
```
3.1. Check migrations (optional):

```bash
drush ms
```

4. Run migrations:

```bash
drush migrate:import le_migrate_media_le_akteur_step1
drush migrate:import le_migrate_media_le_akteur_step2
drush migrate:import le_migrate_media_le_event_step1
drush migrate:import le_migrate_media_le_event_step1
```

Post-migration: Update display & form settings of each updated content-type; Remove "old" image-fields from node-types; replace all their references in modules & templates.
