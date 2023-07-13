Files To Media Migrate
-----------------------

# Drush Batch Commands Migrating files to media.

This modules provides custom drush commands from migrating files to media and 
create media field.

## Table of contents (TOC)

- Requirements
- Installation
- Commands

## Requirements

Before you can start, you need to install the drush & media,media_library, file, 
migrate, migrate_drupal modules of Drupal Core.

## INSTALLATION

*Install as you would normally install a contributed Drupal module. Visit
  [Installing Modules](https://www.drupal.org/docs/extending-drupal/installing-modules) for further information.


## Commands
-------------

This module provides us with Drush commands:

# Create media entity suffixed by field_name.

"drush create-media-field <bundle> <type> <target_media_bundle> <entity_type>" 

In this command we have four arguments.

  1. bundle: the content type which you need to create media entity for 
  files entity.

  2. type: The type of the field e.g image or file.

  3. target_media_bundle: the bundle of media type.
  E.g image, document, video etc.

  4. entity_type: The bundle type e.g node, block_content.



# migrating files entities to media.

"drush files-to-media <field_name> <type> <entity_type>"

In this command we have three arguments.

  1. field_name: The name of the field which you need to migrate.

  2. type: The type of the field e.g image or file.

  3. entity_type: The bundle type e.g node, block_content.


# To use

* drush create-media-field <article> <image> <image> <node>.

When you hit the above command it will be automatically created fields For 
all file like images, audio, video the corresponding media entity reference 
fields suffixed by <field_name>_media.

* drush files-to-media <field_featured_image> <image> <node>.

When you hit the above command it will migrate all the field_featured_image to 
the field_featured_image_media field.

# Note
After migrated File Entity to Media Entity, you need to enable media fields 
from Manage Form Display & Manage Display.

# Maintainers

- Pankaj kumar  - (https://www.drupal.org/u/pankaj_lnweb)
- Shikha Dawar  - (https://www.drupal.org/u/shikha_lnweb)
- Vivek kumar  - (https://www.drupal.org/u/vivek_lnwebworks)
- Aman Naudiyal  - (https://www.drupal.org/u/amanln_webworks)
