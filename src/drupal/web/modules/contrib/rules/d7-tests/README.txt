The files in this directory are here in order to keep track of tests that were
present in Drupal 7 but have not yet been ported to Drupal 8.

The tests are split into five separate files, each containing one class, because
that is more manageable than one huge file.

Any functions in these tests that have a Drupal 8 equivalent may removed. If
you find something that needs to be removed please open an issue as a "task"
in the Rules issue queue at https://www.drupal.org/project/issues/rules and
post a patch to remove the test. All issues should mention what D7 test is
being removed and which D8 test replaced it. All issues should have their
"Parent issue" set to https://www.drupal.org/project/rules/issues/2800779 so
we may keep track of progress on the overall task of finishing the port of the
tests.
