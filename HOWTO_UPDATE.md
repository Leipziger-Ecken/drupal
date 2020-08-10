# Howto update

As our code is controlled by **git**, its dependencies managed by **composer** and the page configurations *(e.g. content-types, fields, views, profile settings)* are im- and exported by the great **features** module, there are some specific steps that you have to run from time to time. 

### Why and when do I have to update?
**Following a common update path guarantees that we are all working on the same set on data structure, modules and drupal core version.** If you run "git pull" and see that things are not working smoothly anymore (e.g. random error messages thrown by Drupal), that's a good moment to update your dependencies and look for (yet) unimported features. Alternatively, you can also check the feature status page under */admin/config/development/features/diff* and see if there are any differences between your database- and the filesystem-configuration.

### Step-by-step guide

Before running these steps, ensure that there is nothing in your data-structure (e.g. edited views or added content-type fields) that has not yet been commited by you by exporting it as a feature-module.

1. Open the terminal and navigate to the root of this repository. Run:
```
git pull 
# or "git pull origin master"
```
2. In order to update the dependencies run
```
composer install
```
3. Navigate to *[your-drupal-location]/admin/config/development/features/diff*, check all boxes and click "Import Changes" / "Änderungen importieren".
4. Check the notification message to see whether everything was imported correctly. Even better, go again to the "differences"-page and see if there are any entries left - normally that is the case if a "feature" requires a dependency to a Drupal-module that has not yet been activated. If you see such messages, go to "Extend" / "Erweiterungen", look for the mentioned modules, activate them and try to run the features-import again.

### Troubleshooting

If you can still not import all the features, look in Github's commit-history to identify the last developer who worked on this feature. Eventually, he/she forgot to commit his/her updated composer.json or to mark a features' dependency. A common problem with features is that it does not indicate removed fields/views. Inform the developer about this, he/she will investigate on the issue and eventually report a manual update path.
