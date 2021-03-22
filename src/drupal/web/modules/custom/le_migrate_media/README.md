## Migrate images to media

Turns image-fields of custom content-types into media-(image-)fields. Currently targets:

* le_event.field_le_event_image
* le_akteur.field_le_akteur_image

1. Activate ["Migrate File Entities to Media Entities"](https://www.drupal.org/project/migrate_file_to_media) module and "Leipziger Ecken Migrate file to media" module:

```bash
drush en le_migrate_media
```

2. Automaticly migrate all image fields to media fields (will keep existing images and checks for already migrated images):

```bash
drush le_migrate_media:image-to-media
```

Post-migration: Update display & form settings of each updated content-type; Remove "old" image-fields from node-types; replace all their references in modules & templates.
